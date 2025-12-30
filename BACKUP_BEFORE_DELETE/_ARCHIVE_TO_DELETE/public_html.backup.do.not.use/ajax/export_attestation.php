<?php
// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de la configuration des sous-domaines pour la détection automatique du magasin
require_once __DIR__ . '/../config/subdomain_config.php';

// Inclure les fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';

// Aucune restriction d'accès - tous les utilisateurs peuvent accéder à ces données
// Si vous souhaitez rétablir la restriction plus tard, décommentez le code ci-dessous
/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}
*/

// Vérifier l'ID du rachat
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID invalide']);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    if ($pdo === null) {
        throw new Exception("La connexion à la base de données n'est pas disponible");
    }

    // Vérifier d'abord quelles colonnes existent dans la table clients
    $columns_to_select = ['c.nom', 'c.prenom', 'c.telephone'];
    
    // Vérifier si la colonne adresse existe
    try {
        $check_adresse = $pdo->query("SHOW COLUMNS FROM clients LIKE 'adresse'");
        if ($check_adresse && $check_adresse->rowCount() > 0) {
            $columns_to_select[] = 'c.adresse';
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de la colonne adresse: " . $e->getMessage());
    }
    
    $client_columns = implode(', ', $columns_to_select);
    
    $stmt = $pdo->prepare("SELECT 
        r.id,
        r.date_rachat,
        r.type_appareil,
        r.modele,
        r.sin,
        r.prix,
        r.fonctionnel,
        r.photo_identite,
        r.photo_appareil,
        r.client_photo,
        r.signature,
        {$client_columns}
    FROM rachat_appareils r
    JOIN clients c ON r.client_id = c.id
    WHERE r.id = ?");
    
    $stmt->execute([$_GET['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Rachat introuvable']);
        exit;
    }
    
    // Récupérer les informations de la boutique
    $shop_id = $_SESSION['shop_id'] ?? null;
    $shop_info = null;
    if ($shop_id) {
        $main_pdo = getMainDBConnection();
        $stmt_shop = $main_pdo->prepare("
            SELECT name, address, city, postal_code, country, phone, email
            FROM shops 
            WHERE id = ?
        ");
        $stmt_shop->execute([$shop_id]);
        $shop_info = $stmt_shop->fetch(PDO::FETCH_ASSOC);
    }
    
    // S'assurer que la clé adresse existe même si la colonne n'est pas en base
    if (!isset($result['adresse'])) {
        $result['adresse'] = '';
    }

    // Traiter toutes les images de manière uniforme - convertir en base64
    $image_fields = ['photo_identite', 'photo_appareil', 'client_photo', 'signature'];
    
    foreach ($image_fields as $field) {
        if ($result[$field]) {
            $image_path = __DIR__ . '/../assets/images/rachat/' . $result[$field];
            if (file_exists($image_path)) {
                $image_content = base64_encode(file_get_contents($image_path));
                // Déterminer le type MIME basé sur l'extension
                $extension = strtolower(pathinfo($result[$field], PATHINFO_EXTENSION));
                $mime_type = 'image/jpeg'; // par défaut
                if ($extension === 'png') {
                    $mime_type = 'image/png';
                } elseif ($extension === 'gif') {
                    $mime_type = 'image/gif';
                }
                $result[$field] = 'data:' . $mime_type . ';base64,' . $image_content;
            } else {
                $result[$field] = null;
            }
        } else {
            $result[$field] = null;
        }
    }

    // Formater la date
    $date = new DateTime($result['date_rachat']);
    $result['date_formatted'] = $date->format('d/m/Y');

    // Formater le prix avec le symbole euro
    $result['prix_formatted'] = number_format($result['prix'], 2, ',', ' ') . ' €';

    // Générer le HTML de l'attestation moderne et professionnelle
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Attestation de Rachat Professionnelle #<?= $result['id'] ?></title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
            html, body {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
                background: #f4f6fa;
            }
            body {
                font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: #222;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .a4-container {
                width: 190mm;
                min-height: 277mm;
                margin: 10mm auto;
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 4px 24px rgba(0,0,0,0.10);
                padding: 0;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }
            .attest-header {
                background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
                color: #fff;
                padding: 16px 32px 12px 32px;
                position: relative;
            }
            .attest-header .doc-num {
                position: absolute;
                top: 16px;
                right: 32px;
                background: rgba(255,255,255,0.15);
                padding: 4px 14px;
                border-radius: 16px;
                font-size: 12px;
                font-weight: 500;
            }
            .attest-header .company {
                font-size: 12px;
                opacity: 0.95;
                margin-bottom: 4px;
            }
            .attest-header .title {
                font-size: 24px;
                font-weight: 700;
                letter-spacing: 0.5px;
                margin-bottom: 2px;
            }
            .attest-header .subtitle {
                font-size: 13px;
                font-weight: 400;
                opacity: 0.92;
            }
            .attest-header .date-badge {
                position: absolute;
                top: 47px;
                right: 32px;
                background: rgba(255,255,255,0.9);
                color: #333;
                border-radius: 12px;
                padding: 3px 10px;
                font-size: 11px;
                font-weight: 600;
                box-shadow: 0 1px 4px rgba(102,126,234,0.12);
            }
            .attest-content {
                padding: 20px 32px 0 32px;
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            .info-row {
                display: flex;
                gap: 20px;
                margin-bottom: 14px;
            }
            .info-block {
                flex: 1;
                background: #f8f9fa;
                border-radius: 6px;
                padding: 12px 16px;
                border-left: 3px solid #667eea;
            }
            .info-block .block-title {
                font-size: 13px;
                color: #6c757d;
                font-weight: 600;
                margin-bottom: 7px;
                text-transform: uppercase;
            }
            .info-block .block-content {
                font-size: 16px;
                color: #222;
                font-weight: 500;
            }
            .device-block {
                background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
                border-radius: 6px;
                padding: 12px 16px;
                margin-bottom: 14px;
                border: 1px solid #dee2e6;
            }
            .device-block .device-title {
                font-size: 15px;
                font-weight: 700;
                color: #333;
                margin-bottom: 10px;
            }
            .device-details {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 8px 24px;
            }
            .device-detail {
                font-size: 14px;
                display: flex;
                justify-content: space-between;
                border-bottom: 1px solid #e9ecef;
                padding: 2px 0 2px 0;
            }
            .device-detail:last-child {
                border-bottom: none;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 16px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .status-functional {
                background: #d4edda;
                color: #155724;
            }
            .status-non-functional {
                background: #f8d7da;
                color: #721c24;
            }
            .images-section {
                margin: 12px 0 0 0;
                padding: 0;
            }

            .images-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(2, 1fr);
                gap: 20px;
                align-items: center;
                justify-items: center;
                margin-bottom: 10px;
                max-width: 650px;
                margin-left: auto;
                margin-right: auto;
            }
            .img-block {
                background: #f8f9fa;
                border-radius: 6px;
                border: 1px solid #dee2e6;
                padding: 10px;
                width: 100%;
                max-width: 260px;
                height: 260px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 8px rgba(102,126,234,0.08);
            }
            .img-block img {
                width: 100%;
                height: calc(100% - 20px);
                border-radius: 4px;
                object-fit: cover;
                background: #fff;
                box-shadow: 0 1px 4px rgba(102,126,234,0.1);
            }

            .conditions-section {
                background: #f8f9fa;
                border-radius: 6px;
                padding: 10px;
                margin-top: 10px;
                border: 1px solid #dee2e6;
                font-size: 9px;
            }
            .conditions-title {
                font-size: 13px;
                font-weight: 700;
                color: #2c3e50;
                margin-bottom: 6px;
                text-align: center;
            }
            .conditions-content {
                font-size: 9px;
                line-height: 1.2;
                color: #495057;
                text-align: justify;
            }
            .footer {
                background: #f8f9fa;
                padding: 8px 32px;
                text-align: center;
                border-top: 1px solid #dee2e6;
                font-size: 9px;
                color: #6c757d;
                margin-top: auto;
            }
            @media print {
                html, body {
                    width: 210mm;
                    height: 297mm;
                    margin: 0;
                    padding: 0;
                }
                .a4-container {
                    box-shadow: none;
                    border-radius: 0;
                    margin: 0;
                    width: 210mm;
                    min-height: 297mm;
                    padding: 0;
                }
                .attest-content {
                    padding: 18px 20px 0 20px;
                }
                .footer {
                    padding: 6px 20px;
                }
                .images-grid {
                    max-width: 546px;
                    gap: 18px;
                }
                .img-block {
                    max-width: 234px;
                    height: 234px;
                }
                .img-block img {
                    width: 100%;
                    height: calc(100% - 20px);
                }
                .attest-header {
                    padding: 12px 20px 8px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="a4-container">
            <div class="attest-header">
                <div class="doc-num">N° <?= htmlspecialchars($result['id']) ?></div>
                <?php if ($shop_info): ?>
                <div class="company">
                    <strong><?= htmlspecialchars($shop_info['name'] ?? '') ?></strong> - <?= htmlspecialchars($shop_info['address'] ?? '') ?>, <?= htmlspecialchars($shop_info['postal_code'] ?? '') ?> <?= htmlspecialchars($shop_info['city'] ?? '') ?>
                </div>
                <?php endif; ?>
                <div class="title">ATTESTATION DE RACHAT</div>
                <div class="subtitle">Document officiel de transaction</div>
                <div class="date-badge">📅 <?= htmlspecialchars($result['date_formatted']) ?></div>
            </div>
            <div class="attest-content">
                <div class="info-row">
                    <div class="info-block">
                        <div class="block-title">👤 Client</div>
                        <div class="block-content">
                            <strong><?= htmlspecialchars($result['nom'] . ' ' . $result['prenom']) ?></strong><br>
                            📞 <?= htmlspecialchars($result['telephone']) ?><br>
                            <?php if (!empty($result['adresse'])): ?>📍 <?= htmlspecialchars($result['adresse']) ?><br><?php endif; ?>
                            <?php if (!empty($result['email'])): ?>✉️ <?= htmlspecialchars($result['email']) ?><?php endif; ?>
                        </div>
                    </div>
                    <div class="info-block">
                        <div class="block-title">💰 Montant</div>
                        <div class="block-content" style="font-size:22px;color:#28a745;">
                            <?= htmlspecialchars($result['prix_formatted']) ?>
                        </div>
                        <div style="font-size:11px;color:#6c757d;">Montant convenu et accepté</div>
                    </div>
                </div>
                <div class="device-block">
                    <div class="device-title">Détails de l'Appareil</div>
                    <div class="device-details">
                        <div class="device-detail"><span>Type :</span><span><?= htmlspecialchars($result['type_appareil']) ?></span></div>
                        <div class="device-detail"><span>Modèle :</span><span><?= htmlspecialchars($result['modele']) ?></span></div>
                        <div class="device-detail"><span>SIN/IMEI :</span><span><?= htmlspecialchars($result['sin']) ?></span></div>
                        <div class="device-detail"><span>État :</span><span><span class="status-badge <?= $result['fonctionnel'] ? 'status-functional' : 'status-non-functional' ?>"><?= $result['fonctionnel'] ? '✅ Fonctionnel' : '❌ Non fonctionnel' ?></span></span></div>
                    </div>
                </div>
                <div class="images-section">
                    <div class="images-grid">
                        <div class="img-block">
                            <?php if (!empty($result['client_photo'])): ?>
                                <img src="<?= htmlspecialchars($result['client_photo']) ?>" alt="Photo client">
                            <?php else: ?>
                                <div style="width:80px;height:100px;background:#eee;border-radius:4px;"></div>
                            <?php endif; ?>

                        </div>
                        <div class="img-block">
                            <?php if (!empty($result['photo_appareil'])): ?>
                                <img src="<?= htmlspecialchars($result['photo_appareil']) ?>" alt="Photo appareil">
                            <?php else: ?>
                                <div style="width:80px;height:100px;background:#eee;border-radius:4px;"></div>
                            <?php endif; ?>

                        </div>
                        <div class="img-block">
                            <?php if (!empty($result['signature'])): ?>
                                <img src="<?= htmlspecialchars($result['signature']) ?>" alt="Signature client">
                            <?php else: ?>
                                <div style="width:80px;height:100px;background:#eee;border-radius:4px;"></div>
                            <?php endif; ?>

                        </div>
                        <div class="img-block">
                            <?php if (!empty($result['photo_identite'])): ?>
                                <img src="<?= htmlspecialchars($result['photo_identite']) ?>" alt="Pièce d'identité">
                            <?php else: ?>
                                <div style="width:80px;height:100px;background:#eee;border-radius:4px;"></div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
                <div class="conditions-section">
                    <div class="conditions-title">📋 Conditions Générales de Rachat</div>
                    <div class="conditions-content">
                        <p><strong>1. OBJET :</strong> La présente attestation confirme le rachat de l'appareil décrit ci-dessus dans l'état où il se trouvait au moment de la transaction.</p>
                        <p><strong>2. GARANTIES :</strong></p>
                        <ul>
                            <li>Le vendeur garantit être le propriétaire légitime de l'appareil et avoir le droit de le céder.</li>
                            <li>L'appareil est vendu en l'état, sans garantie de fonctionnement ultérieur.</li>
                            <li>Le vendeur certifie que l'appareil n'est pas volé, sous gage ou sous saisie.</li>
                        </ul>
                        <p><strong>3. RESPONSABILITÉS :</strong></p>
                        <ul>
                            <li>L'acheteur s'engage à procéder à l'effacement sécurisé des données.</li>
                            <li>Le vendeur déclare avoir sauvegardé toutes ses données personnelles.</li>
                            <li>Aucune réclamation ne sera acceptée après signature de la présente.</li>
                        </ul>
                        <p><strong>4. PRIX :</strong> Le prix convenu est ferme et définitif. Aucun complément de prix ne pourra être réclamé.</p>
                        <p><strong>5. ACCEPTATION :</strong> La signature ou l'acceptation électronique vaut acceptation pleine et entière des présentes conditions.</p>
                    </div>
                </div>
            </div>
            <div class="footer">
            </div>
        </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    if ($html) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'id' => $result['id']
        ]);
    } else {
        throw new Exception("Erreur lors de la génération du HTML");
    }

} catch (Exception $e) {
    error_log('Erreur: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur: ' . $e->getMessage()]);
}
?> 