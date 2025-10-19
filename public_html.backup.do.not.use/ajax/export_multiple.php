<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de la configuration des sous-domaines pour la détection automatique du magasin
require_once __DIR__ . '/../config/subdomain_config.php';

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';

// Vérifier l'accès au magasin (pas besoin d'utilisateur connecté pour cette page)
if (!isset($_SESSION['shop_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Accès non autorisé - Magasin non détecté']);
    exit();
}

header('Content-Type: application/json');

try {
    // Vérifier que des IDs ont été fournis
    if (!isset($_POST['ids']) || empty($_POST['ids'])) {
        throw new Exception("Aucun élément sélectionné pour l'export");
    }

    // Obtenir la connexion à la base de données
    $pdo = getShopDBConnection();
    if ($pdo === null) {
        throw new Exception("La connexion à la base de données n'est pas disponible");
    }

    // Récupérer les IDs des rachats à exporter
    $ids = json_decode($_POST['ids'], true);
    if (!is_array($ids) || empty($ids)) {
        throw new Exception("Format des IDs invalide");
    }

    // Valider les IDs (s'assurer qu'ils sont numériques)
    $ids = array_filter($ids, 'is_numeric');
    if (empty($ids)) {
        throw new Exception("Aucun ID valide fourni");
    }

    // Créer les placeholders pour la requête
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Récupérer les données des rachats
    $sql = "SELECT 
                r.id,
                r.date_rachat,
                r.type_appareil,
                r.modele,
                r.sin,
                r.fonctionnel,
                r.prix,
                r.photo_appareil,
                r.photo_identite,
                r.client_photo,
                r.signature,
                c.nom,
                c.prenom,
                c.telephone,
                c.email
            FROM rachat_appareils r
            JOIN clients c ON r.client_id = c.id
            WHERE r.id IN ($placeholders)
            ORDER BY r.date_rachat DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    $rachats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rachats)) {
        throw new Exception("Aucun rachat trouvé avec les IDs fournis");
    }



    $generatedFiles = [];
    $errors = [];

    // Générer le HTML pour chaque rachat
    foreach ($rachats as $rachat) {
        try {
            // Générer le HTML pour ce rachat
            $html = generateRachatHTML($rachat);
            
            // Nom du fichier PDF
            $filename = sprintf(
                "attestation_rachat_%s_%s_%s.pdf",
                $rachat['id'],
                preg_replace('/[^a-zA-Z0-9]/', '_', $rachat['nom']),
                preg_replace('/[^a-zA-Z0-9]/', '_', $rachat['prenom'])
            );
            
            $generatedFiles[] = [
                'id' => $rachat['id'],
                'filename' => $filename,
                'html' => $html,
                'client' => $rachat['nom'] . ' ' . $rachat['prenom']
            ];
            
        } catch (Exception $e) {
            $errors[] = "Erreur lors de la génération du HTML pour le rachat ID " . $rachat['id'] . ": " . $e->getMessage();
        }
    }

    if (empty($generatedFiles)) {
        throw new Exception("Aucun HTML n'a pu être généré");
    }

    // Retourner les données HTML au client pour génération côté client
    $response = [
        'success' => true,
        'type' => count($generatedFiles) === 1 ? 'single' : 'multiple',
        'count' => count($generatedFiles),
        'files' => $generatedFiles
    ];

    // Ajouter les erreurs s'il y en a
    if (!empty($errors)) {
        $response['warnings'] = $errors;
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Erreur dans export_multiple.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}



/**
 * Génère le HTML pour l'attestation de rachat
 */
function generateRachatHTML($rachat) {
    global $pdo;
    
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
    
    // Traiter toutes les images de manière uniforme - convertir en base64
    $image_fields = ['photo_identite', 'photo_appareil', 'client_photo', 'signature'];
    
    foreach ($image_fields as $field) {
        if ($rachat[$field]) {
            $image_path = __DIR__ . '/../assets/images/rachat/' . $rachat[$field];
            if (file_exists($image_path)) {
                $image_content = base64_encode(file_get_contents($image_path));
                // Déterminer le type MIME basé sur l'extension
                $extension = strtolower(pathinfo($rachat[$field], PATHINFO_EXTENSION));
                $mime_type = 'image/jpeg'; // par défaut
                if ($extension === 'png') {
                    $mime_type = 'image/png';
                } elseif ($extension === 'gif') {
                    $mime_type = 'image/gif';
                }
                $rachat[$field] = 'data:' . $mime_type . ';base64,' . $image_content;
            } else {
                $rachat[$field] = null;
            }
        } else {
            $rachat[$field] = null;
        }
    }

    // Formater la date
    $date = new DateTime($rachat['date_rachat']);
    $rachat['date_formatted'] = $date->format('d/m/Y');

    // Formater le prix avec le symbole euro
    $rachat['prix_formatted'] = number_format($rachat['prix'], 2, ',', ' ') . ' €';

    // S'assurer que la clé adresse existe même si la colonne n'est pas en base
    if (!isset($rachat['adresse'])) {
        $rachat['adresse'] = '';
    }

    // Générer le HTML de l'attestation moderne et professionnelle
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Attestation de Rachat Professionnelle #<?= $rachat['id'] ?></title>
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
                <div class="doc-num">N° <?= htmlspecialchars($rachat['id']) ?></div>
                <?php if ($shop_info): ?>
                <div class="company">
                    <strong><?= htmlspecialchars($shop_info['name'] ?? '') ?></strong> - <?= htmlspecialchars($shop_info['address'] ?? '') ?>, <?= htmlspecialchars($shop_info['postal_code'] ?? '') ?> <?= htmlspecialchars($shop_info['city'] ?? '') ?>
                </div>
                <?php endif; ?>
                <div class="title">ATTESTATION DE RACHAT</div>
                <div class="subtitle">Document officiel de transaction</div>
                <div class="date-badge">📅 <?= htmlspecialchars($rachat['date_formatted']) ?></div>
            </div>
            <div class="attest-content">
                <div class="info-row">
                    <div class="info-block">
                        <div class="block-title">👤 Client</div>
                        <div class="block-content">
                            <strong><?= htmlspecialchars($rachat['nom'] . ' ' . $rachat['prenom']) ?></strong><br>
                            📞 <?= htmlspecialchars($rachat['telephone']) ?><br>
                            <?php if (!empty($rachat['adresse'])): ?>📍 <?= htmlspecialchars($rachat['adresse']) ?><br><?php endif; ?>
                            <?php if (!empty($rachat['email'])): ?>✉️ <?= htmlspecialchars($rachat['email']) ?><?php endif; ?>
                        </div>
                    </div>
                    <div class="info-block">
                        <div class="block-title">💰 Montant</div>
                        <div class="block-content" style="font-size:22px;color:#28a745;">
                            <?= htmlspecialchars($rachat['prix_formatted']) ?>
                        </div>
                        <div style="font-size:11px;color:#6c757d;">Montant convenu et accepté</div>
                    </div>
                </div>
                <div class="device-block">
                    <div class="device-title">Détails de l'Appareil</div>
                    <div class="device-details">
                        <div class="device-detail"><span>Type :</span><span><?= htmlspecialchars($rachat['type_appareil']) ?></span></div>
                        <div class="device-detail"><span>Modèle :</span><span><?= htmlspecialchars($rachat['modele']) ?></span></div>
                        <div class="device-detail"><span>SIN/IMEI :</span><span><?= htmlspecialchars($rachat['sin']) ?></span></div>
                        <div class="device-detail"><span>État :</span><span><span class="status-badge <?= $rachat['fonctionnel'] ? 'status-functional' : 'status-non-functional' ?>"><?= $rachat['fonctionnel'] ? '✅ Fonctionnel' : '❌ Non fonctionnel' ?></span></span></div>
                    </div>
                </div>
                <div class="images-section">
                    <div class="images-grid">
                        <div class="img-block">
                            <?php if (!empty($rachat['client_photo'])): ?>
                                <img src="<?= htmlspecialchars($rachat['client_photo']) ?>" alt="Photo client">
                            <?php else: ?>
                                <div style="width:80px;height:100px;background:#eee;border-radius:4px;"></div>
                            <?php endif; ?>

                        </div>
                        <div class="img-block">
                            <?php if (!empty($rachat['photo_appareil'])): ?>
                                <img src="<?= htmlspecialchars($rachat['photo_appareil']) ?>" alt="Photo appareil">
                            <?php else: ?>
                                <div style="width:80px;height:100px;background:#eee;border-radius:4px;"></div>
                            <?php endif; ?>

                        </div>
                        <div class="img-block">
                            <?php if (!empty($rachat['signature'])): ?>
                                <img src="<?= htmlspecialchars($rachat['signature']) ?>" alt="Signature client">
                            <?php else: ?>
                                <div style="width:80px;height:100px;background:#eee;border-radius:4px;"></div>
                            <?php endif; ?>

                        </div>
                        <div class="img-block">
                            <?php if (!empty($rachat['photo_identite'])): ?>
                                <img src="<?= htmlspecialchars($rachat['photo_identite']) ?>" alt="Pièce d'identité">
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
    return ob_get_clean();
}


?> 