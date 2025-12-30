<?php
// Attestation de rachat - Export de masse
// Interface moderne optimisée impression

error_reporting(0);
ini_set('display_errors', 0);

try {
    // Connexion à la base de données du magasin via système multi-magasin
    require_once __DIR__ . '/../config/database.php';
    
    // Démarrer la session si pas déjà fait
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Initialiser la session magasin
    initializeShopSession();
    
    // Obtenir la connexion à la base du magasin actuel
    $pdo = getShopDBConnection();
    if (!$pdo) {
        throw new Exception('Impossible de se connecter à la base du magasin');
    }
    
    // Récupérer les IDs (liste séparée par des virgules)
    $ids_param = $_GET['ids'] ?? null;
    
    if (!$ids_param) {
        throw new Exception('Aucun rachat sélectionné');
    }
    
    $rachat_ids = explode(',', $ids_param);
    $rachat_ids = array_map('intval', $rachat_ids); // Sécuriser les IDs
    $rachat_ids = array_filter($rachat_ids); // Enlever les valeurs vides/nulles
    
    if (empty($rachat_ids)) {
        throw new Exception('Aucun ID valide fourni');
    }
    
    // Récupérer les paramètres d'entreprise (une seule fois pour tous les rachats)
    try {
        $stmt = $pdo->prepare('SELECT * FROM company_settings WHERE shop_id = ?');
        $stmt->execute([$_SESSION['shop_id']]);
        $company_settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Valeurs par défaut si aucun paramètre n'existe
        if (!$company_settings) {
            $company_settings = [
                'company_name' => 'GEEKBOARD',
                'company_phone' => '',
                'company_email' => '',
                'company_address' => '',
                'company_hours' => ''
            ];
        }
    } catch (PDOException $e) {
        // En cas d'erreur, utiliser les valeurs par défaut
        $company_settings = [
            'company_name' => 'GEEKBOARD',
            'company_phone' => '',
            'company_email' => '',
            'company_address' => '',
            'company_hours' => ''
        ];
    }
    
    // Headers pour affichage HTML
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Rachats - GeekBoard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        
        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body { 
                margin: 0 !important; 
                padding: 0 !important;
                background: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .page-break {
                page-break-after: always;
            }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.3;
            color: #1e293b;
            background: #f1f5f9;
            font-size: 9pt; /* Taille de base optimisée pour A4 */
        }
        
        .container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin-bottom: 10mm; /* Espace entre les pages à l\'écran */
        }
        
        /* En-tête Premium */
        .header {
            background: #0f172a;
            color: white;
            padding: 4mm 10mm 2mm 10mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 3px solid #6366f1;
        }

        .header-brand {
            display: flex;
            flex-direction: column;
        }
        
        .logo {
            font-size: 20pt;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #818cf8 0%, #c7d2fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2mm;
        }
        
        .doc-title {
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.8;
            font-weight: 500;
        }

        .header-meta {
            text-align: right;
        }

        .attestation-number {
            font-family: "Courier New", monospace;
            background: rgba(255,255,255,0.1);
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11pt;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .content {
            padding: 8mm 10mm;
            flex: 1;
        }
        
        /* Barre informations unifiée */
        .info-bar {
            display: flex;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 4mm;
            margin-bottom: 6mm;
            justify-content: space-between;
        }

        .info-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .info-group label {
            font-size: 7pt;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .info-group span {
            font-size: 10pt;
            font-weight: 600;
            color: #0f172a;
        }
        
        /* Section Appareil & Prix */
        .device-section {
            display: flex;
            gap: 6mm;
            margin-bottom: 6mm;
        }

        .device-card {
            flex: 2;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0;
            overflow: hidden;
            display: flex;
        }

        .device-icon-zone {
            width: 50px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20pt;
            border-right: 1px solid #e2e8f0;
        }

        .device-details {
            padding: 4mm;
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3mm;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-item label {
            font-size: 7pt;
            color: #64748b;
        }

        .detail-item span {
            font-size: 9pt;
            font-weight: 500;
        }

        .price-card {
            flex: 1;
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            border-radius: 8px;
            padding: 4mm;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }

        .price-label {
            font-size: 8pt;
            opacity: 0.9;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .price-value {
            font-size: 20pt;
            font-weight: 800;
        }
        
        /* Galerie Photos */
        .gallery-section {
            margin-bottom: 6mm;
        }

        .section-title {
            font-size: 9pt;
            font-weight: 700;
            color: #334155;
            margin-bottom: 3mm;
            display: flex;
            align-items: center;
            gap: 2mm;
        }

        .section-title::after {
            content: \'\';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 4mm;
            justify-content: center;
            max-width: 400px;
            margin: 0 auto;
        }

        .gallery-item {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 2mm;
            background: white;
        }

        .gallery-label {
            font-size: 7pt;
            color: #64748b;
            margin-bottom: 2mm;
            text-align: center;
            display: block;
        }

        .gallery-frame {
            height: 50mm;
            background: #f8fafc;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #f1f5f9;
        }

        .gallery-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .placeholder-icon {
            font-size: 16pt;
            opacity: 0.3;
        }
        
        /* Conditions */
        .conditions-section {
            margin-bottom: 6mm;
            background: #f8fafc;
            border-radius: 8px;
            padding: 4mm;
            border: 1px solid #e2e8f0;
        }

        .conditions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6mm;
        }

        .condition-col h4 {
            font-size: 8pt;
            color: #0f172a;
            margin-bottom: 2mm;
            font-weight: 700;
        }

        .condition-list {
            list-style: none;
        }

        .condition-list li {
            font-size: 7pt;
            color: #475569;
            margin-bottom: 1.5mm;
            padding-left: 3mm;
            position: relative;
            line-height: 1.2;
        }

        .condition-list li::before {
            content: "•";
            color: #6366f1;
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        
        /* Large Photos Section */
        .large-photos-section {
            display: flex;
            gap: 10mm;
            margin-top: auto;
            flex: 1;
            min-height: 80mm;
        }

        .photo-box {
            flex: 1;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 4mm;
            background: white;
            display: flex;
            flex-direction: column;
        }

        .photo-header {
            font-size: 9pt;
            font-weight: 700;
            color: #334155;
            margin-bottom: 3mm;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .photo-content {
            flex: 1;
            background: #f8fafc;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #f1f5f9;
        }

        .photo-content img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .placeholder-large {
            font-size: 40pt;
            opacity: 0.2;
        }
        
        /* Footer */
        .footer {
            padding: 3mm 10mm;
            border-top: 1px solid #e2e8f0;
            font-size: 7pt;
            color: #64748b;
            text-align: center;
            background: white;
        }
        
        .footer-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2mm;
            flex-wrap: nowrap;
        }
        
        .footer-info {
            white-space: nowrap;
        }
        
        .footer-separator {
            color: #cbd5e0;
            margin: 0 1mm;
        }
        
        /* Masquer les éléments non désirés */
        .title-section, .title-underline, .subtitle, .watermark { display: none; }
    </style>
</head>
<body>';

    // Boucle sur chaque ID pour générer une page
    foreach ($rachat_ids as $index => $rachat_id) {
        // Récupérer les données du rachat
        $stmt = $pdo->prepare('
            SELECT ra.*, c.nom, c.prenom, c.telephone, c.email 
            FROM rachat_appareils ra 
            LEFT JOIN clients c ON ra.client_id = c.id 
            WHERE ra.id = ?
        ');
        $stmt->execute([$rachat_id]);
        $rachat = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rachat) {
            continue; // Passer si le rachat n'existe pas
        }
        
        // Données pour l'attestation
        $date_rachat = date('d/m/Y', strtotime($rachat['date_rachat']));
        $heure_rachat = date('H:i', strtotime($rachat['date_rachat']));
        $client_nom = trim(($rachat['prenom'] ?? '') . ' ' . ($rachat['nom'] ?? '')) ?: 'Client';
        $prix_total = number_format($rachat['prix'] ?? 0, 2, ',', ' ');
        
        // URLs des photos
        $photo_appareil = $rachat['photo_appareil'] ? '/assets/images/rachat/' . $rachat['photo_appareil'] : null;
        $photo_identite = $rachat['photo_identite'] ? '/assets/images/rachat/' . $rachat['photo_identite'] : null;
        $client_photo = $rachat['client_photo'] ? '/assets/images/rachat/' . $rachat['client_photo'] : null;
        $signature_data = $rachat['signature'] ? '/assets/images/rachat/' . $rachat['signature'] : null;
        
        // Ajouter un saut de page si ce n'est pas le premier élément
        if ($index > 0) {
            echo '<div class="page-break"></div>';
        }
        
        echo '<div class="container">
            <!-- Header -->
            <div class="header">
                <div class="header-brand">
                    <div class="logo">' . htmlspecialchars($company_settings['company_name'] ?: 'GEEKBOARD') . '</div>
                    <div class="doc-title">Attestation de Rachat</div>
                </div>
                <div class="header-meta">
                    <div class="attestation-number">#' . str_pad($rachat_id, 6, "0", STR_PAD_LEFT) . '</div>
                </div>
            </div>
            
            <div class="content">
                <!-- Info Bar -->
                <div class="info-bar">
                    <div class="info-group">
                        <label>Date</label>
                        <span>' . $date_rachat . ' ' . $heure_rachat . '</span>
                    </div>
                    <div class="info-group">
                        <label>Client</label>
                        <span>' . htmlspecialchars($client_nom) . '</span>
                    </div>
                    <div class="info-group">
                        <label>Téléphone</label>
                        <span>' . htmlspecialchars($rachat['telephone'] ?? 'N/A') . '</span>
                    </div>
                    <div class="info-group">
                        <label>Statut</label>
                        <span style="color: #059669;">Validé</span>
                    </div>
                </div>
                
                <!-- Device & Price -->
                <div class="device-section">
                    <div class="device-card">
                        <div class="device-icon-zone">
                            ' . ($rachat['type_appareil'] == 'iPhone' ? '📱' : ($rachat['type_appareil'] == 'iPad' || $rachat['type_appareil'] == 'tablette' ? '📱' : '💻')) . '
                        </div>
                        <div class="device-details">
                            <div class="detail-item">
                                <label>Appareil</label>
                                <span>' . htmlspecialchars($rachat['type_appareil'] ?? 'Appareil') . '</span>
                            </div>
                            <div class="detail-item">
                                <label>Modèle</label>
                                <span>' . htmlspecialchars($rachat['modele'] ?? 'Non spécifié') . '</span>
                            </div>
                            <div class="detail-item">
                                <label>N° Série</label>
                                <span>' . htmlspecialchars($rachat['sin'] ?? 'Non renseigné') . '</span>
                            </div>
                            <div class="detail-item">
                                <label>État</label>
                                <span>' . ($rachat['fonctionnel'] ? 'Fonctionnel' : 'Défaillant') . '</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="price-card">
                        <div class="price-label">Montant Net</div>
                        <div class="price-value">' . $prix_total . ' €</div>
                    </div>
                </div>
                
                <!-- Gallery -->
                <div class="gallery-section">
                    <div class="section-title">Documentation Visuelle</div>
                    <div class="gallery-grid">
                        <div class="gallery-item">
                            <span class="gallery-label">Appareil</span>
                            <div class="gallery-frame">
                                ' . ($photo_appareil ? '<img src="' . htmlspecialchars($photo_appareil) . '" alt="Appareil">' : '<span class="placeholder-icon">📱</span>') . '
                            </div>
                        </div>
                        <div class="gallery-item">
                            <span class="gallery-label">Signature</span>
                            <div class="gallery-frame">
                                ' . ($signature_data ? '<img src="' . htmlspecialchars($signature_data) . '" alt="Signature">' : '<span class="placeholder-icon">✍️</span>') . '
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Conditions -->
                <div class="conditions-section">
                    <div class="conditions-grid">
                        <div class="condition-col">
                            <h4>Expertise & État</h4>
                            <ul class="condition-list">
                                <li>Appareil testé et vérifié</li>
                                <li>Conformité déclarée</li>
                                <li>Authenticité validée</li>
                            </ul>
                        </div>
                        <div class="condition-col">
                            <h4>Transaction</h4>
                            <ul class="condition-list">
                                <li>Prix ferme et définitif</li>
                                <li>Paiement immédiat</li>
                                <li>Transfert de propriété</li>
                            </ul>
                        </div>
                        <div class="condition-col">
                            <h4>Responsabilités</h4>
                            <ul class="condition-list">
                                <li>Propriétaire légitime</li>
                                <li>Libre de tout gage</li>
                                <li>Données effacées (RGPD)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Large Photos Section -->
                <div class="large-photos-section">
                    <div class="photo-box">
                        <div class="photo-header">Pièce d\'Identité</div>
                        <div class="photo-content">
                            ' . ($photo_identite ? '<img src="' . htmlspecialchars($photo_identite) . '" alt="Identité">' : '<div class="placeholder-large">🆔</div>') . '
                        </div>
                    </div>
                    <div class="photo-box">
                        <div class="photo-header">Photo Client</div>
                        <div class="photo-content">
                            ' . ($client_photo ? '<img src="' . htmlspecialchars($client_photo) . '" alt="Client">' : '<div class="placeholder-large">👤</div>') . '
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                <div class="footer-content">
                    ' . ($company_settings['company_phone'] ? '<span class="footer-info">' . htmlspecialchars($company_settings['company_phone']) . '</span><span class="footer-separator">|</span>' : '') . '
                    ' . ($company_settings['company_email'] ? '<span class="footer-info">' . htmlspecialchars($company_settings['company_email']) . '</span><span class="footer-separator">|</span>' : '') . '
                    <span class="footer-info">' . htmlspecialchars($company_settings['company_name'] ?: 'GEEKBOARD') . '</span>
                    ' . ($company_settings['company_address'] ? '<span class="footer-separator">|</span><span class="footer-info">' . htmlspecialchars($company_settings['company_address']) . '</span>' : '') . '
                    ' . ($company_settings['company_number'] ? '<span class="footer-separator">|</span><span class="footer-info">' . htmlspecialchars($company_settings['company_number']) . '</span>' : '') . '
                </div>
            </div>
        </div>';
    }

    echo '</body>
</html>';

} catch (Exception $e) {
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Erreur</title>
    <style>
        body { 
            font-family: "Inter", sans-serif; 
            padding: 40px; 
            text-align: center; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error { 
            background: white; 
            color: #e53e3e; 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            max-width: 500px;
        }
        .error h2 {
            margin-bottom: 20px;
            color: #2d3748;
        }
    </style>
</head>
<body>
    <div class="error">
        <h2>❌ Erreur</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p style="margin-top: 20px;"><a href="javascript:history.back()" style="color: #667eea; text-decoration: none;">← Retour</a></p>
    </div>
</body>
</html>';
}
?>
