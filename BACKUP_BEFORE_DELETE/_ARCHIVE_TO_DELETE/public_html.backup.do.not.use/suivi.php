<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mainPdo = null;
try { $mainPdo = getMainDBConnection(); } catch (Exception $e) { $mainPdo = null; }

// Détection du magasin par le host si shop_id absent
if (!isset($_SESSION['shop_id']) && $mainPdo) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    try {
        $stmt = $mainPdo->query("SELECT id, subdomain FROM shops WHERE active = 1 AND subdomain IS NOT NULL AND subdomain <> ''");
        $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($shops as $s) {
            $md = $s['subdomain'] . '.mdgeek.top';
            $sv = $s['subdomain'] . '.servo.tools';
            if ($host === $md || $host === $sv) {
                $_SESSION['shop_id'] = (int)$s['id'];
                break;
            }
        }
    } catch (Exception $e) {}
}

// Initialiser la session shop si possible (fallback)
if (!isset($_SESSION['shop_id'])) {
    initializeShopSession();
}

$shopPdo = getShopDBConnection();

$shopId = isset($_SESSION['shop_id']) ? (int)$_SESSION['shop_id'] : null;
$shopPhone = '';
$shopInfo = [];

// Récupérer les informations de l'entreprise depuis la table parametres du magasin
if ($shopPdo) {
    try {
        $stmt = $shopPdo->prepare('SELECT cle, valeur FROM parametres WHERE cle IN ("company_name", "company_phone", "company_email", "company_address", "company_logo")');
        $stmt->execute();
        $params = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $shopInfo = [
            'name' => $params['company_name'] ?? '',
            'phone' => $params['company_phone'] ?? '',
            'email' => $params['company_email'] ?? '',
            'address' => $params['company_address'] ?? '',
            'logo' => $params['company_logo'] ?? ''
        ];
        $shopPhone = $shopInfo['phone'];
    } catch (Exception $e) {
        // Fallback vers la table shops si la table parametres n'existe pas
        if ($mainPdo && $shopId) {
            try {
                $stmt = $mainPdo->prepare('SELECT * FROM shops WHERE id = ?');
                $stmt->execute([$shopId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $shopInfo = $row ?: [];
                $shopPhone = $row['phone'] ?? '';
            } catch (Exception $e2) {
                $shopPhone = '';
                $shopInfo = [];
            }
        }
    }
}

$repairId = isset($_GET['id']) ? trim($_GET['id']) : '';
$repair = null;
$statusHistory = [];
$photos = [];
$quotes = [];
$errorMsg = '';

if ($shopPdo && $repairId !== '') {
    try {
        $stmt = $shopPdo->prepare(
            "SELECT r.*, c.prenom AS client_prenom, c.nom AS client_nom, c.telephone AS client_telephone, c.email AS client_email
             FROM reparations r
             LEFT JOIN clients c ON r.client_id = c.id
             WHERE r.id = ?")
        ;
        $stmt->execute([$repairId]);
        $repair = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$repair) {
            $errorMsg = "Aucune réparation trouvée pour cet identifiant.";
        } else {
            // Valeurs par défaut de statut si tables annexes absentes
            $repair['statut_nom'] = $repair['statut'] ?? '';
            $repair['statut_couleur'] = '#6c757d';

            // Déterminer textes affichés selon colonnes disponibles
            $deviceType = $repair['type_appareil'] ?? ($repair['type'] ?? ($repair['appareil_type'] ?? ($repair['appareil'] ?? '')));
            $brand = $repair['marque'] ?? ($repair['brand'] ?? ($repair['fabricant'] ?? ''));
            $model = $repair['modele'] ?? ($repair['model'] ?? ($repair['modele_appareil'] ?? ''));
            $GLOBALS['__device_text'] = trim(implode(' ', array_filter([$deviceType, $brand, $model])));

            $problem = $repair['description_probleme'] ?? ($repair['probleme'] ?? ($repair['description'] ?? ($repair['panne'] ?? ($repair['details_probleme'] ?? ''))));
            $GLOBALS['__problem_text'] = $problem;

            $recept = $repair['date_reception'] ?? ($repair['created_at'] ?? ($repair['date_creation'] ?? ($repair['date_entree'] ?? null)));
            $GLOBALS['__reception_dt'] = $recept;
            // Construire un historique complet depuis plusieurs sources
            $statusHistory = [];
            
            // 1. Date de réception
            if (!empty($repair['date_reception'])) {
                $statusHistory[] = [
                    'date_action' => $repair['date_reception'],
                    'type' => 'reception',
                    'message' => 'Appareil reçu au magasin'
                ];
            }
            
            // 2. Logs de réparation
            try {
                $stmt = $shopPdo->prepare(
                    "SELECT rl.date_action, rl.statut_avant, rl.statut_apres, rl.details, rl.action_type, rl.employe_id
                     FROM reparation_logs rl
                     WHERE rl.reparation_id = ?
                     ORDER BY rl.date_action ASC")
                ;
                $stmt->execute([$repairId]);
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                
                foreach ($logs as $log) {
                    $message = '';
                    switch ($log['action_type']) {
                        case 'demarrage':
                            $message = 'Début de l\'intervention par un technicien';
                            break;
                        case 'terminer':
                            $message = 'Intervention terminée';
                            break;
                        case 'changement_statut':
                            $message = getStatusMessage($log['statut_avant'], $log['statut_apres']);
                            break;
                        case 'modification':
                            $message = 'Mise à jour des informations de réparation';
                            break;
                        case 'ajout_note':
                            $message = 'Note ajoutée au dossier';
                            break;
                        default:
                            $message = 'Mise à jour du dossier';
                    }
                    
                    $statusHistory[] = [
                        'date_action' => $log['date_action'],
                        'type' => 'log',
                        'message' => $message
                    ];
                }
            } catch (Exception $e) {}
            
            // 3. Événements de devis
            try {
                $stmt = $shopPdo->prepare(
                    "SELECT date_creation, date_envoi, date_reponse, statut, numero_devis
                     FROM devis
                     WHERE reparation_id = ?
                     ORDER BY date_creation ASC")
                ;
                $stmt->execute([$repairId]);
                $devisEvents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                
                foreach ($devisEvents as $devis) {
                    // Création du devis
                    if (!empty($devis['date_creation'])) {
                        $statusHistory[] = [
                            'date_action' => $devis['date_creation'],
                            'type' => 'devis_creation',
                            'message' => 'Devis ' . ($devis['numero_devis'] ?: 'créé') . ' établi'
                        ];
                    }
                    
                    // Envoi du devis
                    if (!empty($devis['date_envoi'])) {
                        $statusHistory[] = [
                            'date_action' => $devis['date_envoi'],
                            'type' => 'devis_envoi',
                            'message' => 'Devis envoyé au client'
                        ];
                    }
                    
                    // Réponse du client (acceptation/refus)
                    if (!empty($devis['date_reponse'])) {
                        $reponse = '';
                        $iconType = 'devis_reponse';
                        switch ($devis['statut']) {
                            case 'accepte':
                                $reponse = 'Devis accepté par le client';
                                $iconType = 'devis_accepte';
                                break;
                            case 'refuse':
                                $reponse = 'Devis refusé par le client';  
                                $iconType = 'devis_refuse';
                                break;
                            default:
                                $reponse = 'Réponse du client reçue';
                        }
                        
                        $statusHistory[] = [
                            'date_action' => $devis['date_reponse'],
                            'type' => $iconType,
                            'message' => $reponse
                        ];
                    }
                }
            } catch (Exception $e) {}
            
            // 4. Date de gardiennage
            if (!empty($repair['date_gardiennage'])) {
                $statusHistory[] = [
                    'date_action' => $repair['date_gardiennage'],
                    'type' => 'gardiennage',
                    'message' => 'Début du gardiennage de l\'appareil'
                ];
            }
            
            // 5. Date de restitution (si l'appareil a été rendu)
            $statutsRestitution = ['livre', 'rendu', 'restitue', 'fini', 'termine', 'recupere'];
            if (in_array(strtolower($repair['statut']), $statutsRestitution) && !empty($repair['date_modification'])) {
                $statusHistory[] = [
                    'date_action' => $repair['date_modification'],
                    'type' => 'restitution',
                    'message' => 'Appareil restitué au client'
                ];
            }
            
            // Trier tout l'historique par date
            usort($statusHistory, function($a, $b) {
                return strtotime($a['date_action']) - strtotime($b['date_action']);
            });

            try {
                $stmt = $shopPdo->prepare(
                    "SELECT url, description FROM photos_reparation WHERE reparation_id = ? ORDER BY date_upload DESC")
                ;
                $stmt->execute([$repairId]);
                $photos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Exception $e) {
                $photos = [];
            }

            // Fallback photos si table absente/vide
            if (empty($photos)) {
                $fallback = [];
                if (!empty($repair['photo_appareil'])) {
                    $fallback[] = ['url' => $repair['photo_appareil'], 'description' => ''];
                }
                if (!empty($repair['photos'])) {
                    $p = $repair['photos'];
                    $arr = null;
                    if (is_string($p)) {
                        $tmp = json_decode($p, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) { $arr = $tmp; }
                        else { $arr = array_filter(array_map('trim', explode(',', $p))); }
                    } elseif (is_array($p)) { $arr = $p; }
                    if ($arr) {
                        foreach ($arr as $u) {
                            if (is_string($u) && $u !== '') { $fallback[] = ['url' => $u, 'description' => '']; }
                            elseif (is_array($u) && !empty($u['url'])) { $fallback[] = ['url' => $u['url'], 'description' => $u['description'] ?? '']; }
                        }
                    }
                }
                if (!empty($fallback)) { $photos = $fallback; }
            }

            try {
                $stmt = $shopPdo->prepare(
                    "SELECT id, numero_devis, statut, total_ttc, lien_securise, date_creation
                     FROM devis
                     WHERE reparation_id = ?
                     ORDER BY date_creation DESC")
                ;
                $stmt->execute([$repairId]);
                $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Exception $e) {
                $quotes = [];
            }
        }
    } catch (Exception $e) {
        $errorMsg = "Erreur lors du chargement des informations.";
    }
} elseif (!$shopPdo) {
    $errorMsg = "Service indisponible pour ce sous-domaine.";
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function getStatusMessage($avant, $apres) {
    $statusMessages = [
        'nouvelle_intervention' => 'en attente de prise en charge',
        'en_cours_diagnostic' => 'en cours de diagnostic',
        'en_attente_accord_client' => 'en attente de l\'accord du client',
        'en_cours_intervention' => 'en cours de réparation',
        'reparation_effectue' => 'réparation terminée',
        'en_attente_livraison' => 'en attente de livraison',
        'livre' => 'livré au client',
        'rendu' => 'rendu au client',
        'annule' => 'annulé',
        'refuse' => 'refusé'
    ];
    
    $avantTxt = $statusMessages[strtolower($avant)] ?? $avant;
    $apresTxt = $statusMessages[strtolower($apres)] ?? $apres;
    
    if ($avantTxt === $apresTxt) {
        return "Statut mis à jour : $apresTxt";
    }
    
    return "Passage de \"$avantTxt\" à \"$apresTxt\"";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Suivi de réparation</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Exo+2:wght@300;400;600&display=swap');
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Exo 2',sans-serif;
            background:linear-gradient(135deg,#0a0a0a 0%,#1a1a2e 25%,#16213e 50%,#0f3460 75%,#533483 100%);
            min-height:100vh; overflow-x:hidden; position:relative; color:#fff;
        }
        .container {
            position:relative; z-index:10; max-width:520px; margin:20px auto 100px; padding:20px;
        }
        .card {
            background:rgba(15,15,30,0.95); backdrop-filter:blur(16px);
            border:1px solid rgba(255,255,255,0.08); border-radius:18px; padding:20px; margin-bottom:16px;
            box-shadow:0 20px 40px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.06);
            overflow:hidden;
        }
        .header { text-align:center; margin-bottom:16px; }
        .logo { width:64px; height:64px; margin:0 auto 10px; filter:drop-shadow(0 0 16px rgba(0,191,255,.5)); }
        .title { font-family:'Orbitron',monospace; font-size:22px; font-weight:900; color:#00bfff; letter-spacing:2px; }
        .subtitle { font-size:13px; opacity:.8; }
        .badge {
            display:inline-block; padding:6px 12px; border-radius:24px; font-weight:700; font-size:12px; color:#fff;
        }
        .row { display:flex; gap:12px; flex-wrap:wrap; }
        .col { flex:1 1 240px; }
        .item { display:flex; gap:10px; margin:10px 0; position:relative; padding-left:40px; }
        .label { font-size:12px; opacity:.8; text-transform:uppercase; letter-spacing:.6px; }
        .value { font-size:15px; }
        .photos { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
        .photo { width:96px; height:96px; border-radius:10px; overflow:hidden; border:1px solid rgba(255,255,255,0.08); }
        .photo img { width:100%; height:100%; object-fit:cover; display:block; }
        .quotes .quote { display:flex; justify-content:space-between; align-items:center; padding:12px; border-radius:12px; background:rgba(255,255,255,.04); margin:8px 0; }
        .quotes .quote .q-meta { font-size:13px; opacity:.85; }
        .btn {
            display:inline-block; padding:12px 16px; border-radius:12px; text-decoration:none; color:#fff; font-weight:700; background:linear-gradient(135deg,#00bfff 0%, #0080ff 100%);
        }
        .btn.secondary { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); font-weight:600; }
        
        /* Boutons animés */
        .btn-animated {
            position: relative;
            padding: 10px 20px;
            border-radius: 7px;
            border: 1px solid rgb(61, 106, 255);
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 2px;
            background: transparent;
            color: #fff;
            overflow: hidden;
            box-shadow: 0 0 0 0 transparent;
            transition: all 0.2s ease-in;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
        }
        .btn-animated:hover {
            background: rgb(61, 106, 255);
            box-shadow: 0 0 30px 5px rgba(0, 142, 236, 0.815);
            transition: all 0.2s ease-out;
            color: #fff;
        }
        .btn-animated:hover::before {
            animation: sh02 0.5s 0s linear;
        }
        .btn-animated::before {
            content: '';
            display: block;
            width: 0px;
            height: 86%;
            position: absolute;
            top: 7%;
            left: 0%;
            opacity: 0;
            background: #fff;
            box-shadow: 0 0 50px 30px #fff;
            transform: skewX(-20deg);
        }
        @keyframes sh02 {
            from { opacity: 0; left: 0%; }
            50% { opacity: 1; }
            to { opacity: 0; left: 100%; }
        }
        .btn-animated:active {
            box-shadow: 0 0 0 0 transparent;
            transition: box-shadow 0.2s ease-in;
        }
        
        .btn-green {
            border-color: #25d366;
        }
        .btn-green:hover {
            background: #25d366;
        }
        
        .btn-orange {
            border-color: #ff8c00;
        }
        .btn-orange:hover {
            background: #ff8c00;
        }
        .error { background:rgba(220,53,69,.12); border:1px solid rgba(220,53,69,.4); color:#ff6b7a; padding:14px; border-radius:12px; }
        .fab-call {
            position:fixed; bottom:24px; left:50%; transform:translateX(-50%);
            display:flex; align-items:center; gap:10px; padding:14px 18px; border-radius:999px;
            background:linear-gradient(135deg,#25d366 0%, #128C7E 100%);
            color:#fff; text-decoration:none; font-weight:800; box-shadow:0 10px 30px rgba(0,0,0,.4);
            z-index:50;
        }
        @media (max-width:480px){ .container{ padding:16px; margin:16px auto 88px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card header">
            <img class="logo" src="<?php echo !empty($shopInfo['logo']) && file_exists('/var/www/mdgeek.top/' . $shopInfo['logo']) ? '/' . htmlspecialchars($shopInfo['logo']) : '/assets/images/logo/logoservo.png'; ?>" alt="Logo">
            <div class="title">Suivi de réparation</div>
            <div class="subtitle">Consultez l'état de votre appareil</div>
        </div>

        <?php if ($errorMsg): ?>
            <div class="card error"><?php echo h($errorMsg); ?></div>
        <?php elseif ($repair): ?>
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <div style="font-weight:900; font-size:18px;">Réparation #<?php echo h($repair['id']); ?></div>
                    <span class="badge" style="background: <?php echo h($repair['statut_couleur']); ?>;"><?php echo h($repair['statut_nom']); ?></span>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="item"><div>
                            <div class="label">Appareil</div>
                            <div class="value"><?php echo h($GLOBALS['__device_text'] ?? ''); ?></div>
                        </div></div>
                        <div class="item"><div>
                            <div class="label">Problème</div>
                            <div class="value"><?php echo nl2br(h($GLOBALS['__problem_text'] ?? '')); ?></div>
                        </div></div>
                        <div class="item"><div>
                            <div class="label">Prix</div>
                            <div class="value"><?php
                                $price = null;
                                foreach (['prix_reparation','prix','prix_estime','estimate','montant'] as $pf) {
                                    if (isset($repair[$pf])) { $price = $repair[$pf]; break; }
                                }
                                $isWaiting = ($price === null || $price === '' || (is_numeric($price) && (float)$price == 0.0));
                                echo $isWaiting ? 'En Attente' : (number_format((float)$price, 2, ',', ' ') . ' €');
                            ?></div>
                        </div></div>
                    </div>
                    <div class="col">
                        <div class="item"><div>
                            <div class="label">Réception</div>
                            <div class="value"><?php echo !empty($GLOBALS['__reception_dt']) ? date('d/m/Y', strtotime($GLOBALS['__reception_dt'])) : '-'; ?></div>
                        </div></div>
                        <div class="item"><div>
                            <div class="label">Client</div>
                            <div class="value"><?php echo h(trim(($repair['client_prenom'] ?? '') . ' ' . ($repair['client_nom'] ?? ''))); ?></div>
                        </div></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($photos)): ?>
                <div class="card" id="photosCard" style="display:none;">
                    <div style="font-weight:900; margin-bottom:8px;">Photos de l'appareil</div>
                    <div class="photos">
                        <?php foreach ($photos as $p): ?>
                            <div class="photo"><img src="<?php echo h($p['url']); ?>" alt="photo"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card" id="photosToggleCard">
                    <a href="#" class="btn-animated btn-green" id="togglePhotosBtn">📸 Voir les photos (<?php echo count($photos); ?>)</a>
                </div>
            <?php endif; ?>

            <div class="card" id="historyCard" style="display:none;">
                <div style="font-weight:900; margin-bottom:8px;">Historique détaillé</div>
                <?php if (!empty($statusHistory)): ?>
                    <?php foreach ($statusHistory as $event): ?>
                        <div class="item" style="align-items:flex-start;">
                            <div>
                                <div class="label"><?php echo date('d/m/Y', strtotime($event['date_action'])); ?></div>
                                <div class="value"><?php echo h($event['message']); ?></div>
                                <?php 
                                // Ajouter une icône selon le type d'événement
                                $icon = '';
                                switch ($event['type']) {
                                    case 'reception': $icon = '📦'; break;
                                    case 'log': $icon = '🔧'; break;
                                    case 'devis_creation': $icon = '📄'; break;
                                    case 'devis_envoi': $icon = '📧'; break;
                                    case 'devis_accepte': $icon = '✅'; break;
                                    case 'devis_refuse': $icon = '❌'; break;
                                    case 'devis_reponse': $icon = '💬'; break;
                                    case 'gardiennage': $icon = '🏪'; break;
                                    case 'restitution': $icon = '🎉'; break;
                                    default: $icon = '📝';
                                }
                                ?>
                                <div style="position:absolute; left:10px; top:50%; transform:translateY(-50%); font-size:18px; width:20px; text-align:center;"><?php echo $icon; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="item">
                        <div>
                            <div class="label"><?php echo !empty($GLOBALS['__reception_dt']) ? date('d/m/Y', strtotime($GLOBALS['__reception_dt'])) : date('d/m/Y'); ?></div>
                            <div class="value">Appareil reçu au magasin</div>
                            <div style="position:absolute; left:10px; top:50%; transform:translateY(-50%); font-size:18px; width:20px; text-align:center;">📦</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card" id="historyToggleCard">
                <a href="#" class="btn-animated btn-orange" id="toggleHistoryBtn">📋 Afficher l'historique (<?php echo count($statusHistory); ?> étapes)</a>
            </div>

            <div class="card quotes">
                <div style="font-weight:900; margin-bottom:8px;">Devis</div>
                <?php if (!empty($quotes)): ?>
                    <?php foreach ($quotes as $q): ?>
                        <div class="quote">
                            <div class="q-meta">
                                <div><strong><?php echo h($q['numero_devis'] ?: ('Devis #' . $q['id'])); ?></strong></div>
                                <div style="opacity:.8; font-size:12px;">Statut: <?php echo h($q['statut']); ?> • <?php echo $q['date_creation'] ? date('d/m/Y', strtotime($q['date_creation'])) : ''; ?></div>
                            </div>
                            <?php if (!empty($q['lien_securise'])): ?>
                                <a class="btn-animated" href="/pages/devis_client.php?lien=<?php echo h($q['lien_securise']); ?>">💼 Voir</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="item"><div class="value">Aucun devis n'a été émis pour le moment.</div></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($shopInfo): ?>
            <div class="card" style="text-align:center; background:linear-gradient(135deg, rgba(0,191,255,0.1), rgba(83,52,131,0.1)); border:1px solid rgba(0,191,255,0.2);">
                <div style="font-size:20px; font-weight:bold; color:#00bfff; margin-bottom:12px; font-family:'Orbitron',monospace;">
                    <?php echo h($shopInfo['name'] ?: 'MAISON DU GEEK'); ?>
                </div>
                
                <?php if (!empty($shopInfo['address'])): ?>
                    <div style="font-size:14px; margin-bottom:8px; display:flex; align-items:center; justify-content:center; gap:8px;">
                        <span style="font-size:16px;">📍</span>
                        <span><?php echo h($shopInfo['address']); ?></span>
                    </div>
                <?php endif; ?>
                
                <div style="display:flex; justify-content:center; gap:20px; margin-bottom:12px; flex-wrap:wrap;">
                    <?php if (!empty($shopInfo['phone'])): ?>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="font-size:16px;">📞</span>
                            <span style="font-size:14px;"><?php echo h($shopInfo['phone']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($shopInfo['email'])): ?>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="font-size:16px;">✉️</span>
                            <span style="font-size:14px;"><?php echo h($shopInfo['email']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($shopPhone)): ?>
                    <div style="margin-bottom:12px;">
                        <a class="btn-animated btn-green" href="tel:<?php echo h(preg_replace('/\s+/', '', $shopPhone)); ?>">📞 Appelez le magasin</a>
                    </div>
                <?php endif; ?>
                
                <div style="font-size:12px; opacity:0.8; border-top:1px solid rgba(255,255,255,0.1); padding-top:12px; margin-top:12px;">
                    🔧 Réparation professionnelle • ⭐ Service de qualité • ✅ Satisfaction garantie
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle Photos
        var toggleBtn = document.getElementById('togglePhotosBtn');
        var photosCard = document.getElementById('photosCard');
        if (toggleBtn && photosCard) {
            toggleBtn.addEventListener('click', function(e){
                e.preventDefault();
                var isHidden = photosCard.style.display === 'none';
                photosCard.style.display = isHidden ? 'block' : 'none';
                toggleBtn.innerHTML = isHidden ? '📸 Masquer les photos' : '📸 Voir les photos (<?php echo count($photos); ?>)';
            });
        }

        // Toggle History
        var toggleHistoryBtn = document.getElementById('toggleHistoryBtn');
        var historyCard = document.getElementById('historyCard');
        if (toggleHistoryBtn && historyCard) {
            toggleHistoryBtn.addEventListener('click', function(e){
                e.preventDefault();
                var isHidden = historyCard.style.display === 'none';
                historyCard.style.display = isHidden ? 'block' : 'none';
                toggleHistoryBtn.innerHTML = isHidden ? '📋 Masquer l\'historique' : '📋 Afficher l\'historique (<?php echo count($statusHistory); ?> étapes)';
            });
        }

        // Fullscreen modal for photos
        const imgs = document.querySelectorAll('.photo img');
        if (imgs.length) {
            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.inset = '0';
            overlay.style.background = 'rgba(0,0,0,0.9)';
            overlay.style.display = 'none';
            overlay.style.justifyContent = 'center';
            overlay.style.alignItems = 'center';
            overlay.style.zIndex = '9999';
            const big = document.createElement('img');
            big.style.maxWidth = '95%';
            big.style.maxHeight = '95%';
            big.style.borderRadius = '8px';
            overlay.appendChild(big);
            overlay.addEventListener('click', function(){ overlay.style.display='none'; });
            document.body.appendChild(overlay);
            imgs.forEach(function(im){
                im.addEventListener('click', function(){
                    big.src = im.src;
                    overlay.style.display = 'flex';
                });
            });
        }
    });
    </script>
</body>
</html>


