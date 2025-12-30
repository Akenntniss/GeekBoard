<?php
// Attestation de rachat professionnelle - Design moderne

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Connexion dynamique à la base du shop actuel
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Erreur de connexion à la base de données du magasin');
    }
    
    // Récupérer l'ID obligatoire
    $rachat_id = $_GET['id'] ?? null;
    
    if (!$rachat_id) {
        throw new Exception('ID de rachat requis');
    }
    
    // Récupérer les données du rachat avec informations client
    $stmt = $pdo->prepare('
        SELECT ra.*, c.nom, c.prenom, c.telephone, c.email, c.adresse 
        FROM rachat_appareils ra 
        LEFT JOIN clients c ON ra.client_id = c.id 
        WHERE ra.id = ?
    ');
    $stmt->execute([$rachat_id]);
    $rachat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rachat) {
        throw new Exception('Rachat non trouvé');
    }
    
    // Headers pour affichage HTML (pas de téléchargement forcé)
    header('Content-Type: text/html; charset=utf-8');
    
    // Données pour l'attestation
    $date_rachat = date('d/m/Y', strtotime($rachat['date_rachat']));
    $heure_rachat = date('H:i', strtotime($rachat['date_rachat']));
    $client_nom = trim(($rachat['prenom'] ?? '') . ' ' . ($rachat['nom'] ?? '')) ?: 'Client';
    $prix_total = number_format($rachat['prix'] ?? 0, 2, ',', ' ');
    
    echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attestation de Rachat #' . $rachat_id . '</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
            padding: 20px;
        }
        
        .attestation {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        
        .header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Ccircle cx="30" cy="30" r="4"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.3;
        }
        
        .header-content {
            position: relative;
            z-index: 2;
        }
        
        .logo {
            font-size: 2.5em;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .subtitle {
            font-size: 1.2em;
            opacity: 0.9;
            font-weight: 300;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .attestation-title {
            text-align: center;
            font-size: 2.2em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 30px;
            position: relative;
        }
        
        .attestation-title::after {
            content: "";
            display: block;
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            margin: 15px auto;
            border-radius: 2px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 40px 0;
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }
        
        .info-section h3 {
            color: #2c3e50;
            font-size: 1.3em;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .info-item {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
            min-width: 120px;
            margin-right: 10px;
        }
        
        .info-value {
            color: #333;
            flex: 1;
        }
        
        .device-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: center;
        }
        
        .device-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .device-name {
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .device-details {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .price-section {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            margin: 30px 0;
        }
        
        .price-label {
            font-size: 1.2em;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .price-amount {
            font-size: 3em;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .conditions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .conditions h4 {
            color: #856404;
            margin-bottom: 15px;
            font-size: 1.2em;
        }
        
        .conditions ul {
            list-style: none;
            padding-left: 0;
        }
        
        .conditions li {
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }
        
        .conditions li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-top: 40px;
            text-align: center;
        }
        
        .signature-box {
            border-top: 2px solid #ddd;
            padding-top: 15px;
        }
        
        .signature-label {
            font-weight: 600;
            color: #666;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .print-btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 6em;
            color: rgba(102, 126, 234, 0.05);
            font-weight: 900;
            pointer-events: none;
            z-index: 1;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Imprimer</button>
    
    <div class="attestation">
        <div class="watermark">GEEKBOARD</div>
        
        <div class="header">
            <div class="header-content">
                <div class="logo">📱 GEEKBOARD</div>
                <div class="subtitle">Expert en Rachat d\'Appareils Électroniques</div>
            </div>
        </div>
        
        <div class="content">
            <h1 class="attestation-title">ATTESTATION DE RACHAT</h1>
            
            <div class="info-grid">
                <div class="info-section">
                    <h3>📋 Informations du Rachat</h3>
                    <div class="info-item">
                        <span class="info-label">N° Rachat :</span>
                        <span class="info-value"><strong>#' . str_pad($rachat_id, 6, '0', STR_PAD_LEFT) . '</strong></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date :</span>
                        <span class="info-value">' . $date_rachat . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Heure :</span>
                        <span class="info-value">' . $heure_rachat . '</span>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3>👤 Informations Client</h3>
                    <div class="info-item">
                        <span class="info-label">Nom :</span>
                        <span class="info-value"><strong>' . htmlspecialchars($client_nom) . '</strong></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Téléphone :</span>
                        <span class="info-value">' . htmlspecialchars($rachat['telephone'] ?? 'Non renseigné') . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email :</span>
                        <span class="info-value">' . htmlspecialchars($rachat['email'] ?? 'Non renseigné') . '</span>
                    </div>
                </div>
            </div>
            
            <div class="device-section">
                <div class="device-icon">' . ($rachat['type_appareil'] == 'iPhone' ? '📱' : ($rachat['type_appareil'] == 'iPad' || $rachat['type_appareil'] == 'tablette' ? '📱' : '💻')) . '</div>
                <div class="device-name">' . htmlspecialchars($rachat['type_appareil'] ?? 'Appareil') . '</div>
                <div class="device-details">
                    <strong>Modèle :</strong> ' . htmlspecialchars($rachat['modele'] ?? 'Non spécifié') . '<br>
                    <strong>État :</strong> ' . ($rachat['fonctionnel'] ? 'Fonctionnel ✅' : 'Non fonctionnel ❌') . '<br>
                    ' . ($rachat['numero_serie'] ? '<strong>N° Série :</strong> ' . htmlspecialchars($rachat['numero_serie']) : '') . '
                </div>
            </div>
            
            <div class="price-section">
                <div class="price-label">Montant du Rachat</div>
                <div class="price-amount">' . $prix_total . ' €</div>
            </div>
            
            <div class="conditions">
                <h4>⚠️ Conditions du Rachat</h4>
                <ul>
                    <li>L\'appareil a été expertisé et évalué selon nos critères qualité</li>
                    <li>Le prix proposé est ferme et définitif à la date du rachat</li>
                    <li>Le client certifie être propriétaire légitime de l\'appareil</li>
                    <li>Aucun recours ne sera possible après signature de cette attestation</li>
                    <li>Les données personnelles seront effacées selon la réglementation RGPD</li>
                </ul>
            </div>
            
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-label">Signature du Client</div>
                    <div style="height: 60px;"></div>
                    <div>' . htmlspecialchars($client_nom) . '</div>
                </div>
                <div class="signature-box">
                    <div class="signature-label">Signature GeekBoard</div>
                    <div style="height: 60px;"></div>
                    <div>Expert Agréé</div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>📱 GEEKBOARD</strong> - Expert en Rachat d\'Appareils Électroniques</p>
            <p>📍 Adresse du magasin • 📞 Téléphone • 🌐 www.geekboard.fr</p>
            <p style="margin-top: 15px; font-size: 0.9em; opacity: 0.8;">
                Document généré automatiquement le ' . date('d/m/Y à H:i') . ' • Référence: GB-' . $rachat_id . '-' . date('Ymd') . '
            </p>
        </div>
    </div>
    
    <script>
        // Auto-focus pour impression
        window.addEventListener("load", function() {
            // Optionnel : ouvrir automatiquement la boîte de dialogue d\'impression
            // setTimeout(() => window.print(), 1000);
        });
    </script>
</body>
</html>';

} catch (Exception $e) {
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Erreur</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; text-align: center; }
        .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="error">
        <h2>❌ Erreur</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p><a href="javascript:history.back()">← Retour</a></p>
    </div>
</body>
</html>';
}
?>
