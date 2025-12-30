<?php
/**
 * ================================================================================
 * PAGE CLIENT - CONSULTATION ET ACCEPTATION DE DEVIS
 * ================================================================================
 * Description: Interface moderne pour que le client consulte et accepte/refuse son devis
 * Date: 2025-01-27
 * ================================================================================
 */

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Inclure les dépendances
require_once '../config/subdomain_database_detector.php';

// Fonction utilitaire pour nettoyer les entrées
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_NOQUOTES, 'UTF-8');
    return $data;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Récupérer le lien sécurisé depuis l'URL
$lien_securise = $_GET['lien'] ?? '';
$devis_id = $_GET['id'] ?? '';

// Si on a un ID au lieu d'un lien sécurisé, le convertir
if (empty($lien_securise) && !empty($devis_id)) {
    try {
        // Connexion temporaire pour récupérer le lien sécurisé
        $detector = new SubdomainDatabaseDetector();
        $temp_pdo = $detector->getConnection();
        if ($temp_pdo) {
            $stmt = $temp_pdo->prepare("SELECT lien_securise FROM devis WHERE id = ?");
            $stmt->execute([$devis_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $lien_securise = $result['lien_securise'];
            }
        }
    } catch (Exception $e) {
        error_log("Erreur conversion ID vers lien sécurisé: " . $e->getMessage());
    }
}

if (empty($lien_securise)) {
    http_response_code(404);
    include '../templates/error.php';
    exit;
}

// Nettoyer le lien sécurisé
$lien_securise = preg_replace('/[^a-zA-Z0-9]/', '', $lien_securise);

try {
    // Récupérer la connexion à la base de données
    $detector = new SubdomainDatabaseDetector();
    $shop_pdo = $detector->getConnection();
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }

    // Récupérer le devis complet avec toutes les informations
    $stmt = $shop_pdo->prepare("
        SELECT 
            d.*,
            c.nom as client_nom,
            c.prenom as client_prenom,
            c.telephone as client_telephone,
            c.email as client_email,
            r.type_appareil,
            r.modele as appareil_modele,
            r.description_probleme,
            e.nom as employe_nom,
            e.prenom as employe_prenom
        FROM devis d
        LEFT JOIN clients c ON d.client_id = c.id
        LEFT JOIN reparations r ON d.reparation_id = r.id
        LEFT JOIN employes e ON d.employe_id = e.id
        WHERE d.lien_securise = ? AND d.statut IN ('envoye', 'accepte', 'refuse')
    ");
    $stmt->execute([$lien_securise]);
    $devis = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$devis) {
        http_response_code(404);
        $error_message = "Devis non trouvé ou non accessible";
        include '../templates/error.php';
        exit;
    }

    // Vérifier si le devis n'est pas expiré
    $date_expiration = new DateTime($devis['date_expiration']);
    $maintenant = new DateTime();
    $devis_expire = $maintenant > $date_expiration;

    // Si le devis est expiré et n'a pas encore été marqué comme tel
    if ($devis_expire && $devis['statut'] == 'envoye') {
        $stmt = $shop_pdo->prepare("UPDATE devis SET statut = 'expire' WHERE id = ?");
        $stmt->execute([$devis['id']]);
        $devis['statut'] = 'expire';
    }

    // Récupérer les pannes identifiées
    $stmt = $shop_pdo->prepare("
        SELECT * FROM devis_pannes 
        WHERE devis_id = ? 
        ORDER BY ordre ASC, id ASC
    ");
    $stmt->execute([$devis['id']]);
    $pannes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les solutions proposées avec leurs éléments
    $stmt = $shop_pdo->prepare("
        SELECT ds.*, 
               GROUP_CONCAT(
                   CONCAT(dsi.nom, '|', dsi.quantite, '|', dsi.prix_unitaire, '|', dsi.type)
                   SEPARATOR ';;;'
               ) as elements_concat
        FROM devis_solutions ds
        LEFT JOIN devis_solutions_items dsi ON ds.id = dsi.solution_id
        WHERE ds.devis_id = ?
        GROUP BY ds.id
        ORDER BY ds.ordre ASC, ds.id ASC
    ");
    $stmt->execute([$devis['id']]);
    $solutions_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Traiter les éléments de chaque solution
    $solutions = [];
    foreach ($solutions_raw as $solution) {
        $solution['elements'] = [];
        
        if (!empty($solution['elements_concat'])) {
            $elements_data = explode(';;;', $solution['elements_concat']);
            foreach ($elements_data as $element_data) {
                $parts = explode('|', $element_data);
                if (count($parts) >= 4) {
                    $solution['elements'][] = [
                        'nom' => $parts[0],
                        'quantite' => intval($parts[1]),
                        'prix_unitaire' => floatval($parts[2]),
                        'type' => $parts[3]
                    ];
                }
            }
        }
        
        unset($solution['elements_concat']);
        $solutions[] = $solution;
    }

    // Récupérer l'historique des actions si le devis a été traité
    $logs = [];
    if ($devis['statut'] != 'envoye') {
        $stmt = $shop_pdo->prepare("
            SELECT * FROM devis_logs 
            WHERE devis_id = ? 
            ORDER BY date_action DESC
        ");
        $stmt->execute([$devis['id']]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les détails de la solution choisie si le devis a été accepté
    $solution_choisie = null;
    if ($devis['statut'] == 'accepte' && !empty($devis['solution_choisie_id'])) {
        foreach ($solutions as $solution) {
            if ($solution['id'] == $devis['solution_choisie_id']) {
                $solution_choisie = $solution;
                break;
            }
        }
    }

    // Récupérer les informations du magasin
    $shopInfo = [];
    $shopPhone = '';
    try {
        $stmt = $shop_pdo->prepare('SELECT cle, valeur FROM parametres WHERE cle IN ("company_name", "company_phone", "company_email", "company_address", "company_logo")');
        $stmt->execute();
        $params = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $shopInfo = [
            'name' => $params['company_name'] ?? 'MAISON DU GEEK',
            'phone' => $params['company_phone'] ?? '04 93 46 71 63',
            'email' => $params['company_email'] ?? '',
            'address' => $params['company_address'] ?? '',
            'logo' => $params['company_logo'] ?? ''
        ];
        $shopPhone = $shopInfo['phone'];
    } catch (Exception $e) {
        $shopInfo = [
            'name' => 'MAISON DU GEEK',
            'phone' => '04 93 46 71 63',
            'email' => '',
            'address' => '',
            'logo' => ''
        ];
        $shopPhone = '04 93 46 71 63';
    }

} catch (Exception $e) {
    error_log("ERREUR PAGE DEVIS CLIENT: " . $e->getMessage());
    http_response_code(500);
    $error_message = "Une erreur s'est produite lors du chargement du devis";
    include '../templates/error.php';
    exit;
}

// Déterminer le titre de la page
$page_title = "Devis #" . $devis['numero_devis'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo h($page_title); ?> - Consultation de devis</title>
    
    <!-- Signature Pad -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <!-- SweetAlert2 pour les notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Exo+2:wght@300;400;600&display=swap');
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Exo 2',sans-serif;
            background:linear-gradient(135deg,#0a0a0a 0%,#1a1a2e 25%,#16213e 50%,#0f3460 75%,#533483 100%);
            min-height:100vh; overflow-x:hidden; position:relative; color:#fff;
        }
        .container {
            position:relative; z-index:10; max-width:720px; margin:20px auto 100px; padding:20px;
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
        .status-envoye { background: #3b82f6; }
        .status-accepte { background: #10b981; }
        .status-refuse { background: #ef4444; }
        .status-expire { background: #6b7280; }
        
        .row { display:flex; gap:12px; flex-wrap:wrap; }
        .col { flex:1 1 240px; }
        .item { display:flex; gap:10px; margin:10px 0; position:relative; padding-left:40px; }
        .label { font-size:12px; opacity:.8; text-transform:uppercase; letter-spacing:.6px; }
        .value { font-size:15px; }
        
        .info-section {
            background:rgba(255,255,255,0.04); border-radius:12px; padding:16px; margin:12px 0;
            border-left:4px solid #00bfff;
        }
        
        .panne-card {
            background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3);
            border-radius:12px; padding:12px; margin:8px 0;
            border-left:4px solid #ef4444;
        }
        
        .solution-card {
            background:rgba(255,255,255,0.06); border:2px solid rgba(255,255,255,0.1);
            border-radius:15px; padding:16px; margin:8px 0; cursor:pointer;
            transition:all 0.3s ease; position:relative;
        }
        .solution-card:hover {
            border-color:#00bfff; background:rgba(0,191,255,0.08);
            transform:translateY(-2px); box-shadow:0 8px 25px rgba(0,191,255,0.15);
        }
        .solution-card.selected {
            border-color:#10b981; background:rgba(16,185,129,0.1);
            box-shadow:0 8px 25px rgba(16,185,129,0.15);
        }
        .solution-card.recommandee::before {
            content:"Recommandée"; position:absolute; top:-8px; right:20px;
            background:#f59e0b; color:#fff; padding:4px 12px; border-radius:50px;
            font-size:11px; font-weight:700; letter-spacing:0.5px;
        }
        
        .price-highlight {
            font-size:2rem; font-weight:700; color:#10b981;
            font-family:'Orbitron',monospace;
        }
        
        .elements-list {
            background:rgba(255,255,255,0.03); border-radius:8px; padding:10px; margin-top:8px;
        }
        .element-item {
            display:flex; justify-content:space-between; align-items:center;
            padding:4px 0; border-bottom:1px solid rgba(255,255,255,0.1);
        }
        .element-item:last-child { border-bottom:none; }
        
        .signature-section {
            background:rgba(255,255,255,0.04); border-radius:15px; padding:24px; margin:20px 0;
            border:2px dashed rgba(255,255,255,0.2);
        }
        #signature-canvas {
            border:2px solid rgba(255,255,255,0.2); border-radius:10px;
            background:rgba(255,255,255,0.9); cursor:crosshair; display:block; margin:0 auto;
        }
        
        .countdown {
            background:linear-gradient(45deg,#ff6b6b,#feca57); color:#fff;
            padding:16px; border-radius:12px; text-align:center; margin:12px 0; font-weight:600;
        }
        
        .btn {
            display:inline-block; padding:12px 16px; border-radius:12px; text-decoration:none; 
            color:#fff; font-weight:700; text-align:center; border:none; cursor:pointer;
            transition:all 0.3s ease;
        }
        .btn-primary { background:linear-gradient(135deg,#00bfff 0%, #0080ff 100%); }
        .btn-success { background:linear-gradient(135deg,#10b981 0%, #059669 100%); }
        .btn-danger { background:linear-gradient(135deg,#ef4444 0%, #dc2626 100%); }
        .btn-info { background:linear-gradient(135deg,#6366f1 0%, #4f46e5 100%); }
        .btn:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(0,0,0,0.2); }
        .btn:disabled { opacity:0.5; cursor:not-allowed; transform:none; }
        
        /* Boutons animés */
        .btn-animated {
            position: relative; padding: 12px 20px; border-radius: 10px;
            border: 2px solid #00bfff; font-size: 14px; text-transform: uppercase;
            font-weight: 600; letter-spacing: 1px; background: transparent; color: #fff;
            overflow: hidden; transition: all 0.3s ease; text-decoration: none;
            display: inline-block; cursor: pointer;
        }
        .btn-animated:hover {
            background: #00bfff; color: #fff;
            box-shadow: 0 0 30px 5px rgba(0, 191, 255, 0.5);
        }
        .btn-animated:hover::before {
            animation: sh02 0.5s 0s linear;
        }
        .btn-animated::before {
            content: ''; display: block; width: 0px; height: 86%;
            position: absolute; top: 7%; left: 0%; opacity: 0;
            background: #fff; box-shadow: 0 0 50px 30px #fff;
            transform: skewX(-20deg);
        }
        @keyframes sh02 {
            from { opacity: 0; left: 0%; }
            50% { opacity: 1; }
            to { opacity: 0; left: 100%; }
        }
        
        .btn-green { border-color: #25d366; }
        .btn-green:hover { background: #25d366; }
        .btn-orange { border-color: #ff8c00; }
        .btn-orange:hover { background: #ff8c00; }
        .btn-red { border-color: #ef4444; }
        .btn-red:hover { background: #ef4444; }
        
        .action-buttons {
            text-align:center; padding:20px 0; gap:12px; display:flex; 
            flex-wrap:wrap; justify-content:center; align-items:center;
        }
        
        .alert {
            padding:16px; border-radius:12px; margin:16px 0;
        }
        .alert-info { background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.3); color:#93c5fd; }
        .alert-warning { background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); color:#fbbf24; }
        .alert-success { background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); color:#6ee7b7; }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        
        @media (max-width:768px){
            .container{ padding:16px; margin:16px auto 88px; }
            .price-highlight{ font-size:1.5rem; }
            .action-buttons{ flex-direction:column; }
            .btn{ width:100%; margin:4px 0; }
            .row{ flex-direction:column; }
            #signature-canvas{ width:100%; max-width:400px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card header">
            <img class="logo" src="<?php echo !empty($shopInfo['logo']) && file_exists('/var/www/mdgeek.top/' . $shopInfo['logo']) ? '/' . h($shopInfo['logo']) : '/assets/images/logo/logoservo.png'; ?>" alt="Logo">
            <div class="title">Consultation de devis</div>
            <div class="subtitle">Devis <?php echo h($devis['numero_devis']); ?> • <?php echo h($devis['titre']); ?></div>
        </div>

        <!-- Statut et prix principal -->
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:12px;">
                <div>
                    <div style="font-weight:900; font-size:18px; margin-bottom:8px;">Devis #<?php echo h($devis['numero_devis']); ?></div>
                    <span class="badge status-<?php echo $devis['statut']; ?>">
                        <?php
                        $status_labels = [
                            'envoye' => 'En attente',
                            'accepte' => 'Accepté',
                            'refuse' => 'Refusé',
                            'expire' => 'Expiré'
                        ];
                        echo $status_labels[$devis['statut']];
                        ?>
                    </span>
                </div>
                <div class="price-highlight">
                    <?php echo number_format($devis['total_ttc'], 2, ',', ' '); ?> €
                    <div style="font-size:12px; opacity:0.8; font-family:'Exo 2',sans-serif;">TTC</div>
                </div>
            </div>
        </div>

        <!-- Informations générales -->
        <div class="card">
            <div style="font-weight:900; margin-bottom:12px;">Informations</div>
            <div class="row">
                <div class="col">
                    <div class="item"><div>
                        <div class="label">Client</div>
                        <div class="value"><?php echo h($devis['client_nom'] . ' ' . $devis['client_prenom']); ?></div>
                    </div></div>
                    <div class="item"><div>
                        <div class="label">Téléphone</div>
                        <div class="value"><?php echo h($devis['client_telephone']); ?></div>
                    </div></div>
                    <?php if ($devis['client_email']): ?>
                    <div class="item"><div>
                        <div class="label">Email</div>
                        <div class="value"><?php echo h($devis['client_email']); ?></div>
                    </div></div>
                    <?php endif; ?>
                </div>
                <div class="col">
                    <div class="item"><div>
                        <div class="label">Appareil</div>
                        <div class="value"><?php echo h($devis['type_appareil']); ?></div>
                    </div></div>
                    <div class="item"><div>
                        <div class="label">Modèle</div>
                        <div class="value"><?php echo h($devis['appareil_modele']); ?></div>
                    </div></div>
                    <div class="item"><div>
                        <div class="label">Problème</div>
                        <div class="value"><?php echo h($devis['description_probleme']); ?></div>
                    </div></div>
                </div>
            </div>
        </div>

        <!-- Délai d'expiration si applicable -->
        <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>
        <div class="countdown">
            <div style="font-size:16px; margin-bottom:4px;">⏰ Ce devis expire le <?php echo date('d/m/Y à H:i', strtotime($devis['date_expiration'])); ?></div>
            <div id="countdown-timer"></div>
        </div>
        <?php endif; ?>

        <!-- Description générale -->
        <?php if (!empty($devis['description_generale'])): ?>
        <div class="card">
            <div style="font-weight:900; margin-bottom:8px;">📋 Description</div>
            <div class="value"><?php echo nl2br(h($devis['description_generale'])); ?></div>
        </div>
        <?php endif; ?>

        <!-- Pannes identifiées -->
        <?php if (!empty($pannes)): ?>
        <div class="card">
            <div style="font-weight:900; margin-bottom:12px;">⚠️ Pannes identifiées</div>
            <?php foreach ($pannes as $panne): ?>
            <div class="panne-card">
                <div style="font-weight:700; margin-bottom:4px;">
                    <?php
                    $gravite_icons = [
                        'faible' => '🟢',
                        'moyenne' => '🟡',
                        'elevee' => '🟠',
                        'critique' => '🔴'
                    ];
                    echo $gravite_icons[$panne['gravite']] ?? '🟡';
                    ?>
                    <?php echo h($panne['titre']); ?>
                </div>
                <?php if (!empty($panne['description'])): ?>
                <div style="opacity:0.9; font-size:14px;"><?php echo nl2br(h($panne['description'])); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Solutions proposées -->
        <div class="card">
            <div style="font-weight:900; margin-bottom:12px;">🔧 Solutions proposées</div>
            
            <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>
            <div style="opacity:0.8; margin-bottom:16px;">Cliquez sur la solution de votre choix pour la sélectionner :</div>
            <?php endif; ?>

            <div id="solutions-container">
                <?php foreach ($solutions as $index => $solution): ?>
                <div class="solution-card <?php echo $solution['recommandee'] ? 'recommandee' : ''; ?>" 
                     data-solution-id="<?php echo $solution['id']; ?>"
                     <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>onclick="selectSolution(<?php echo $solution['id']; ?>)"<?php endif; ?>>
                    
                    <div class="row" style="align-items:center;">
                        <div class="col">
                            <div style="font-weight:700; font-size:16px; color:#00bfff; margin-bottom:6px;">
                                Solution <?php echo chr(65 + $index); ?>: <?php echo h($solution['nom']); ?>
                                <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>
                                <input type="radio" name="solution_choisie" value="<?php echo $solution['id']; ?>" 
                                       style="margin-left:12px; transform:scale(1.2);">
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($solution['description'])): ?>
                            <div style="opacity:0.8; margin-bottom:6px;"><?php echo nl2br(h($solution['description'])); ?></div>
                            <?php endif; ?>
                            
                            <div class="row" style="font-size:13px; opacity:0.9; margin-top:4px;">
                                <?php if (!empty($solution['duree_reparation'])): ?>
                                <div class="col" style="margin-bottom:2px;">
                                    ⏱️ <strong>Durée:</strong> <?php echo h($solution['duree_reparation']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($solution['garantie'])): ?>
                                <div class="col" style="margin-bottom:2px;">
                                    🛡️ <strong>Garantie:</strong> <?php echo h($solution['garantie']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="text-align:right; min-width:140px;">
                            <div class="price-highlight" style="font-size:1.6rem;">
                                <?php echo number_format($solution['prix_total'], 2, ',', ' '); ?> €
                            </div>
                            <div style="font-size:11px; opacity:0.7;">TTC</div>
                        </div>
                    </div>

                    <!-- Détail des éléments si disponible -->
                    <?php if (!empty($solution['elements'])): ?>
                    <div class="elements-list">
                        <div style="font-weight:600; margin-bottom:6px; font-size:13px;">📋 Détail :</div>
                        <?php foreach ($solution['elements'] as $element): ?>
                        <div class="element-item">
                            <span>
                                <?php echo h($element['nom']); ?>
                                <?php if ($element['quantite'] > 1): ?>
                                <span style="opacity:0.7;">(x<?php echo $element['quantite']; ?>)</span>
                                <?php endif; ?>
                            </span>
                            <strong><?php echo number_format($element['prix_unitaire'] * $element['quantite'], 2, ',', ' '); ?> €</strong>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section signature et acceptation (uniquement si le devis est en attente) -->
        <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>
        <div class="signature-section" id="signature-section" style="display: none;">
            <div style="font-weight:900; text-align:center; margin-bottom:16px; font-size:18px;">
                ✍️ Signature électronique
            </div>
            
            <div style="text-align:center; margin-bottom:16px; opacity:0.9;">
                Signez dans le cadre ci-dessous pour confirmer votre acceptation du devis :
            </div>
            
            <div style="text-align:center; margin-bottom:12px;">
                <canvas id="signature-canvas" width="600" height="200"></canvas>
            </div>
            
            <div style="text-align:center;">
                <button type="button" class="btn-animated btn-orange" onclick="clearSignature()">
                    🗑️ Effacer
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Message si déjà traité -->
        <?php if ($devis['statut'] != 'envoye'): ?>
        <div class="alert alert-info">
            <div style="font-weight:700; margin-bottom:8px;">ℹ️ Statut du devis</div>
            <?php if ($devis['statut'] == 'accepte'): ?>
            <div style="margin-bottom:8px;">Votre devis a été accepté le <?php echo date('d/m/Y à H:i', strtotime($devis['date_reponse'])); ?>. 
               Nous allons procéder à la réparation de votre appareil.</div>
            
            <?php if ($solution_choisie): ?>
            <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:8px; padding:12px; margin-top:12px;">
                <div style="font-weight:700; color:#10b981; margin-bottom:8px;">
                    ✅ Solution choisie
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                    <div>
                        <strong><?php echo h($solution_choisie['nom']); ?></strong>
                        <?php if (!empty($solution_choisie['description'])): ?>
                        <br><span style="opacity:0.8; font-size:13px;"><?php echo nl2br(h($solution_choisie['description'])); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="price-highlight" style="font-size:1.2rem;">
                        <?php echo number_format($solution_choisie['prix_total'], 2, ',', ' '); ?> €
                    </div>
                </div>
                
                <?php if (!empty($solution_choisie['elements'])): ?>
                <div style="margin-top:8px; font-size:12px; opacity:0.9;">
                    <strong>Détail :</strong>
                    <?php foreach ($solution_choisie['elements'] as $element): ?>
                    <div style="margin:2px 0;">
                        • <?php echo h($element['nom']); ?>
                        <?php if ($element['quantite'] > 1): ?>
                        (x<?php echo $element['quantite']; ?>)
                        <?php endif; ?>
                        : <strong><?php echo number_format($element['prix_unitaire'] * $element['quantite'], 2, ',', ' '); ?> €</strong>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php elseif ($devis['statut'] == 'refuse'): ?>
            <div>Vous avez refusé ce devis le <?php echo date('d/m/Y à H:i', strtotime($devis['date_reponse'])); ?>. 
               Votre appareil vous attend en magasin.</div>
            <?php elseif ($devis['statut'] == 'expire'): ?>
            <div>Ce devis a expiré le <?php echo date('d/m/Y à H:i', strtotime($devis['date_expiration'])); ?>. 
               Contactez-nous pour établir un nouveau devis.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Message si expiré -->
        <?php if ($devis_expire && $devis['statut'] == 'envoye'): ?>
        <div class="alert alert-warning">
            <div style="font-weight:700; margin-bottom:8px;">⚠️ Devis expiré</div>
            <div>Ce devis a expiré le <?php echo date('d/m/Y à H:i', strtotime($devis['date_expiration'])); ?>. 
               Contactez-nous pour établir un nouveau devis.</div>
        </div>
        <?php endif; ?>

        <!-- Boutons d'action -->
        <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>
        <div class="action-buttons">
            <button type="button" class="btn-animated btn-green" id="btn-accepter" onclick="accepterDevis()" disabled>
                ✅ Accepter le devis
            </button>
            <button type="button" class="btn-animated btn-red" onclick="refuserDevis()">
                ❌ Refuser le devis
            </button>
        </div>
        <?php endif; ?>

        <!-- Boutons disponibles pour tous les statuts -->
        <div class="action-buttons">
            <a href="tel:<?php echo h(preg_replace('/\s+/', '', $shopPhone)); ?>" class="btn-animated btn-green">
                📞 Appelez-nous<br><small><?php echo h($shopPhone); ?></small>
            </a>
            <a href="devis_print.php?lien=<?php echo h($lien_securise); ?>&print=1" 
               class="btn-animated btn-orange" target="_blank">
                🖨️ Imprimer / PDF
            </a>
        </div>

        <!-- Informations du magasin -->
        <div class="card" style="text-align:center; background:linear-gradient(135deg, rgba(0,191,255,0.1), rgba(83,52,131,0.1)); border:1px solid rgba(0,191,255,0.2);">
            <div style="font-size:20px; font-weight:bold; color:#00bfff; margin-bottom:12px; font-family:'Orbitron',monospace;">
                <?php echo h($shopInfo['name']); ?>
            </div>
            
            <?php if (!empty($shopInfo['address'])): ?>
            <div style="font-size:14px; margin-bottom:8px; display:flex; align-items:center; justify-content:center; gap:8px;">
                <span>📍</span>
                <span><?php echo h($shopInfo['address']); ?></span>
            </div>
            <?php endif; ?>
            
            <div style="display:flex; justify-content:center; gap:20px; margin-bottom:12px; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:6px;">
                    <span>📞</span>
                    <span style="font-size:14px;"><?php echo h($shopInfo['phone']); ?></span>
                </div>
                
                <?php if (!empty($shopInfo['email'])): ?>
                <div style="display:flex; align-items:center; gap:6px;">
                    <span>✉️</span>
                    <span style="font-size:14px;"><?php echo h($shopInfo['email']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div style="font-size:12px; opacity:0.8; border-top:1px solid rgba(255,255,255,0.1); padding-top:12px; margin-top:12px;">
                🔧 Réparation professionnelle • ⭐ Service de qualité • ✅ Satisfaction garantie
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let signaturePad;
        let solutionChoisie = null;
        const devisId = <?php echo $devis['id']; ?>;
        const devisExpiration = new Date('<?php echo $devis['date_expiration']; ?>');

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎯 Initialisation de la page devis client');
            
            // Initialiser la signature
            initSignature();
            
            // Initialiser le compte à rebours si nécessaire
            <?php if ($devis['statut'] == 'envoye' && !$devis_expire): ?>
            initCountdown();
            <?php endif; ?>
            
            // Auto-sélectionner si une seule solution est disponible
            autoSelectSingleSolution();
        });

        // Initialiser le pad de signature
        function initSignature() {
            const canvas = document.getElementById('signature-canvas');
            if (!canvas) return;
            
            // Ajuster la taille du canvas pour la responsivité
            function resizeCanvas() {
                const container = canvas.parentElement;
                const containerWidth = container.offsetWidth;
                const newWidth = Math.min(600, containerWidth - 40);
                
                canvas.width = newWidth;
                canvas.height = 200;
                
                if (signaturePad) {
                    signaturePad.clear();
                }
            }
            
            resizeCanvas();
            
            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgba(255,255,255,0.9)',
                penColor: '#000000',
                minWidth: 2,
                maxWidth: 4
            });
            
            // Écouter les événements de signature pour activer/désactiver le bouton
            signaturePad.addEventListener('beginStroke', function() {
                console.log('🖊️ Début de signature');
            });
            
            signaturePad.addEventListener('endStroke', function() {
                console.log('✅ Fin de signature - vérification du bouton');
                checkAcceptButton();
            });
            
            // Redimensionner au redimensionnement de la fenêtre
            window.addEventListener('resize', resizeCanvas);
        }

        // Sélectionner une solution
        function selectSolution(solutionId) {
            console.log('✅ Solution sélectionnée:', solutionId);
            
            // Désélectionner toutes les cartes
            document.querySelectorAll('.solution-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Sélectionner la carte cliquée
            const selectedCard = document.querySelector(`[data-solution-id="${solutionId}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }
            
            // Cocher le radio button
            const radio = document.querySelector(`input[name="solution_choisie"][value="${solutionId}"]`);
            if (radio) {
                radio.checked = true;
            }
            
            solutionChoisie = solutionId;
            
            // Afficher la section signature
            const signatureSection = document.getElementById('signature-section');
            if (signatureSection) {
                signatureSection.style.display = 'block';
                signatureSection.scrollIntoView({ behavior: 'smooth' });
            }
            
            // Vérifier si on peut activer le bouton accepter
            checkAcceptButton();
        }

        // Vérifier si on peut activer le bouton d'acceptation
        function checkAcceptButton() {
            const btnAccepter = document.getElementById('btn-accepter');
            if (!btnAccepter) return;
            
            const hasSignature = signaturePad && !signaturePad.isEmpty();
            
            if (solutionChoisie && hasSignature) {
                btnAccepter.disabled = false;
                btnAccepter.classList.add('pulse');
            } else {
                btnAccepter.disabled = true;
                btnAccepter.classList.remove('pulse');
            }
        }

        // Auto-sélectionner une solution unique
        function autoSelectSingleSolution() {
            const solutionCards = document.querySelectorAll('.solution-card[data-solution-id]');
            
            console.log(`📊 Nombre de solutions trouvées: ${solutionCards.length}`);
            
            // Si une seule solution est disponible, la sélectionner automatiquement
            if (solutionCards.length === 1) {
                const solutionId = solutionCards[0].getAttribute('data-solution-id');
                console.log(`🎯 Auto-sélection de la solution unique: ${solutionId}`);
                
                // Attendre un peu pour que tout soit initialisé
                setTimeout(() => {
                    selectSolution(parseInt(solutionId));
                }, 500);
            }
        }

        // Effacer la signature
        function clearSignature() {
            if (signaturePad) {
                signaturePad.clear();
                checkAcceptButton();
            }
        }

        // Accepter le devis
        async function accepterDevis() {
            if (!solutionChoisie) {
                Swal.fire('Erreur', 'Veuillez sélectionner une solution', 'error');
                return;
            }
            
            if (!signaturePad || signaturePad.isEmpty()) {
                Swal.fire('Erreur', 'Veuillez signer le devis', 'error');
                return;
            }
            
            // Confirmation
            const result = await Swal.fire({
                title: '✨ Confirmer l\'acceptation',
                html: `
                    <div style="text-align:center; padding:20px;">
                        <div style="font-size:3rem; margin-bottom:1rem;">🤝</div>
                        <p style="font-size:1.1rem; color:#374151; line-height:1.6;">
                            Vous vous apprêtez à <strong>accepter ce devis</strong>.<br>
                            Cette action est <strong>définitive</strong> et lancera immédiatement le processus de réparation.
                        </p>
                    </div>
                `,
                icon: null,
                showCancelButton: true,
                confirmButtonText: '✅ Oui, j\'accepte',
                cancelButtonText: '❌ Annuler',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280'
            });
            
            if (!result.isConfirmed) return;
            
            // Afficher le loader
            Swal.fire({
                title: 'Traitement en cours...',
                html: '<div style="text-align:center; padding:20px;"><div style="font-size:2rem; margin-bottom:1rem;">⚙️</div>Enregistrement de votre acceptation</div>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });
            
            try {
                // Préparer les données
                const donnees = {
                    action: 'accepter',
                    devis_id: devisId,
                    solution_choisie_id: solutionChoisie,
                    signature: signaturePad.toDataURL()
                };
                
                const response = await fetch('../ajax/traiter_devis_client.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(donnees)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    await Swal.fire({
                        title: '🎉 Devis accepté !',
                        html: `
                            <div style="text-align:center; padding:20px;">
                                <div style="font-size:4rem; margin-bottom:1rem;">✅</div>
                                <h3 style="color:#10b981; margin-bottom:1rem;">Merci pour votre confiance !</h3>
                                <p style="font-size:1.1rem; color:#374151; line-height:1.6; margin-bottom:1.5rem;">
                                    Votre demande de réparation a été enregistrée avec succès.
                                </p>
                                <div style="background:rgba(16,185,129,0.1); padding:1rem; border-radius:8px; margin:1rem 0;">
                                    <p style="color:#374151; margin:0;">
                                        📧 <strong>Nos équipes seront notifiées</strong> rapidement<br>
                                        🔧 Nous <strong>organiserons votre réparation</strong> dans les plus brefs délais<br>
                                        📱 <strong>Un SMS vous sera envoyé</strong> dès que votre appareil sera prêt
                                    </p>
                                </div>
                            </div>
                        `,
                        icon: null,
                        confirmButtonText: '👍 Parfait !',
                        confirmButtonColor: '#10b981'
                    });
                    
                    // Recharger la page
                    location.reload();
                } else {
                    throw new Error(result.message || 'Erreur lors de l\'acceptation');
                }
                
            } catch (error) {
                console.error('Erreur:', error);
                Swal.fire('Erreur', error.message, 'error');
            }
        }

        // Refuser le devis
        async function refuserDevis() {
            const result = await Swal.fire({
                title: '💭 Refuser le devis',
                html: `
                    <div style="text-align:center; padding:20px;">
                        <div style="font-size:3rem; margin-bottom:1rem;">💬</div>
                        <p style="font-size:1.1rem; color:#374151; line-height:1.6; margin-bottom:1.5rem;">
                            Nous comprenons que ce devis ne corresponde pas à vos attentes.<br>
                            <strong>Souhaitez-vous nous faire part de vos remarques ?</strong>
                        </p>
                    </div>
                `,
                input: 'textarea',
                inputPlaceholder: 'Vos commentaires nous aident à améliorer nos services (optionnel)...',
                showCancelButton: true,
                confirmButtonText: '❌ Confirmer le refus',
                cancelButtonText: '⬅️ Retour',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280'
            });
            
            if (!result.isConfirmed) return;
            
            // Afficher le loader
            Swal.fire({
                title: 'Traitement en cours...',
                html: '<div style="text-align:center; padding:20px;"><div style="font-size:2rem; margin-bottom:1rem;">⚙️</div>Enregistrement de votre réponse</div>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });
            
            try {
                const donnees = {
                    action: 'refuser',
                    devis_id: devisId,
                    raison_refus: result.value || ''
                };
                
                const response = await fetch('../ajax/traiter_devis_client.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(donnees)
                });
                
                const resultData = await response.json();
                
                if (resultData.success) {
                    await Swal.fire({
                        title: 'ℹ️ Devis refusé',
                        html: `
                            <div style="text-align:center; padding:20px;">
                                <div style="font-size:4rem; margin-bottom:1rem;">📝</div>
                                <p style="font-size:1.2rem; color:#3b82f6; font-weight:600; margin-bottom:1.5rem;">
                                    <strong>Votre réponse a été enregistrée.</strong>
                                </p>
                                <p style="font-size:1.1rem; color:#374151; margin-bottom:1.5rem;">
                                    Nous respectons votre décision.
                                </p>
                                <div style="background:rgba(59,130,246,0.1); padding:1rem; border-radius:8px; margin:1rem 0;">
                                    <p style="color:#374151; margin:0;">
                                        📱 <strong>Votre appareil vous attend</strong> en magasin<br>
                                        💬 <strong>Vos commentaires nous aident</strong> à améliorer nos services<br>
                                        🤝 Nous restons <strong>à votre disposition</strong> pour toute question
                                    </p>
                                </div>
                                <p style="color:#6b7280; font-size:0.95rem; margin-top:1rem;">
                                    📞 <strong>Besoin d'aide ?</strong> Contactez-nous au <?php echo h($shopPhone); ?>
                                </p>
                            </div>
                        `,
                        icon: null,
                        confirmButtonText: '✅ Compris',
                        confirmButtonColor: '#3b82f6'
                    });
                    
                    // Recharger la page
                    location.reload();
                } else {
                    throw new Error(resultData.message || 'Erreur lors du refus');
                }
                
            } catch (error) {
                console.error('Erreur:', error);
                Swal.fire('Erreur', error.message, 'error');
            }
        }

        // Initialiser le compte à rebours
        function initCountdown() {
            const countdownElement = document.getElementById('countdown-timer');
            if (!countdownElement) return;
            
            function updateCountdown() {
                const maintenant = new Date();
                const diff = devisExpiration - maintenant;
                
                if (diff <= 0) {
                    countdownElement.innerHTML = '<strong>EXPIRÉ</strong>';
                    location.reload(); // Recharger pour mettre à jour le statut
                    return;
                }
                
                const jours = Math.floor(diff / (1000 * 60 * 60 * 24));
                const heures = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const secondes = Math.floor((diff % (1000 * 60)) / 1000);
                
                let texte = '';
                if (jours > 0) texte += `${jours}j `;
                if (heures > 0) texte += `${heures}h `;
                if (minutes > 0) texte += `${minutes}m `;
                texte += `${secondes}s`;
                
                countdownElement.innerHTML = `Temps restant: <strong>${texte}</strong>`;
            }
            
            // Mettre à jour immédiatement puis toutes les secondes
            updateCountdown();
            setInterval(updateCountdown, 1000);
        }
    </script>
</body>
</html>