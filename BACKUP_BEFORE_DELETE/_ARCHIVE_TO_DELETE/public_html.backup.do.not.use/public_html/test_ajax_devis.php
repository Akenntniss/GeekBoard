<?php
session_start();

// Simuler une session utilisateur pour les tests
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 63;
    $_SESSION['shop_name'] = 'mkmkmk';
    $_SESSION['user_id'] = 6;
    $_SESSION['user_name'] = 'Test User';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test AJAX Devis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .test-card { background: white; border-radius: 10px; padding: 20px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .console-output { background: #000; color: #0f0; padding: 15px; border-radius: 5px; font-family: monospace; height: 300px; overflow-y: auto; }
        .json-output { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center">🚀 Test AJAX Devis</h1>
        
        <div class="test-card">
            <h3><i class="fas fa-info-circle"></i> Session Actuelle</h3>
            <ul>
                <li><strong>Shop ID:</strong> <?= $_SESSION['shop_id'] ?? 'NON DÉFINI' ?></li>
                <li><strong>User ID:</strong> <?= $_SESSION['user_id'] ?? 'NON DÉFINI' ?></li>
                <li><strong>Shop Name:</strong> <?= $_SESSION['shop_name'] ?? 'NON DÉFINI' ?></li>
            </ul>
        </div>
        
        <div class="test-card">
            <h3><i class="fas fa-play-circle"></i> Tests AJAX</h3>
            <div class="row">
                <div class="col-md-6">
                    <button type="button" class="btn btn-primary w-100 mb-2" onclick="testAjaxBasic()">
                        <i class="fas fa-paper-plane"></i> Test AJAX Basique
                    </button>
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-success w-100 mb-2" onclick="testAjaxComplete()">
                        <i class="fas fa-file-invoice"></i> Test Devis Complet
                    </button>
                </div>
            </div>
        </div>
        
        <div class="test-card">
            <h3><i class="fas fa-terminal"></i> Console Log</h3>
            <div id="consoleOutput" class="console-output">
                Prêt pour les tests AJAX...
            </div>
        </div>
        
        <div class="test-card">
            <h3><i class="fas fa-code"></i> Réponse Dernière Requête</h3>
            <div id="responseOutput" class="json-output">
                Aucune requête encore...
            </div>
        </div>
    </div>

    <script>
        let consoleDiv = document.getElementById('consoleOutput');
        let responseDiv = document.getElementById('responseOutput');
        
        function customLog(message, type = 'info') {
            const colors = {
                info: '#0f0',
                error: '#f00',
                warning: '#ff0',
                success: '#0ff'
            };
            
            const timestamp = new Date().toLocaleTimeString();
            consoleDiv.innerHTML += `<div style="color: ${colors[type] || '#0f0'}">[${timestamp}] ${message}</div>`;
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
        }
        
        async function testAjaxBasic() {
            customLog('🔧 Test AJAX basique vers creer_devis_clean.php...', 'info');
            
            const testData = {
                reparation_id: 999,
                titre: "Test Devis AJAX",
                description: "Test de base",
                solutions: [
                    {
                        nom: "Solution test",
                        description: "Description test",
                        prix: 50.00
                    }
                ]
            };
            
            try {
                customLog('📤 Envoi des données de test...', 'info');
                customLog(`📋 Données: ${JSON.stringify(testData, null, 2)}`, 'info');
                
                const response = await fetch('ajax/creer_devis_clean.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(testData)
                });
                
                customLog(`📡 Statut HTTP: ${response.status} ${response.statusText}`, response.ok ? 'success' : 'error');
                
                const responseText = await response.text();
                customLog(`📥 Réponse brute reçue (${responseText.length} caractères)`, 'info');
                
                responseDiv.textContent = responseText;
                
                try {
                    const result = JSON.parse(responseText);
                    customLog('✅ JSON valide reçu', 'success');
                    customLog(`Succès: ${result.success}`, result.success ? 'success' : 'error');
                    
                    if (result.success) {
                        customLog(`📄 Numéro devis: ${result.numero_devis}`, 'success');
                        customLog(`💰 Total TTC: ${result.data.total_ttc}€`, 'success');
                    } else {
                        customLog(`❌ Erreur: ${result.message}`, 'error');
                    }
                } catch (jsonError) {
                    customLog('❌ Erreur de parsing JSON:', 'error');
                    customLog(jsonError.message, 'error');
                }
                
            } catch (error) {
                customLog('❌ Erreur de requête:', 'error');
                customLog(error.message, 'error');
                responseDiv.textContent = `Erreur: ${error.message}`;
            }
        }
        
        async function testAjaxComplete() {
            customLog('🔧 Test AJAX complet avec données réalistes...', 'info');
            
            const testData = {
                reparation_id: 123,
                titre: "Réparation écran iPhone 12",
                description: "Remplacement écran complet avec vitre et LCD",
                garantie: "3 mois",
                pannes: [
                    {
                        nom: "Écran cassé",
                        description: "Écran complètement brisé, impossible à utiliser",
                        gravite: "elevee"
                    }
                ],
                solutions: [
                    {
                        nom: "Remplacement écran original",
                        description: "Écran d'origine Apple avec garantie constructeur",
                        prix: 280.00,
                        garantie: "6 mois"
                    },
                    {
                        nom: "Remplacement écran compatible",
                        description: "Écran compatible haute qualité",
                        prix: 180.00,
                        garantie: "3 mois"
                    }
                ]
            };
            
            try {
                customLog('📤 Envoi devis complet...', 'info');
                customLog(`📋 Nb pannes: ${testData.pannes.length}`, 'info');
                customLog(`📋 Nb solutions: ${testData.solutions.length}`, 'info');
                
                const response = await fetch('ajax/creer_devis_clean.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(testData)
                });
                
                customLog(`📡 Statut HTTP: ${response.status} ${response.statusText}`, response.ok ? 'success' : 'error');
                
                const responseText = await response.text();
                responseDiv.textContent = responseText;
                
                try {
                    const result = JSON.parse(responseText);
                    customLog('✅ Devis traité avec succès', 'success');
                    
                    if (result.success) {
                        customLog(`📄 Numéro: ${result.numero_devis}`, 'success');
                        customLog(`💰 HT: ${result.data.total_ht}€`, 'success');
                        customLog(`💰 TTC: ${result.data.total_ttc}€`, 'success');
                        customLog(`📱 SMS: ${result.sms_sent ? 'Envoyé' : 'Non envoyé'}`, result.sms_sent ? 'success' : 'warning');
                        customLog(`🔗 Lien: ${result.data.lien_complet}`, 'info');
                    } else {
                        customLog(`❌ Échec: ${result.message}`, 'error');
                        if (result.debug) {
                            customLog(`🐛 Debug: ${JSON.stringify(result.debug)}`, 'warning');
                        }
                    }
                } catch (jsonError) {
                    customLog('❌ Réponse non-JSON:', 'error');
                    customLog(jsonError.message, 'error');
                }
                
            } catch (error) {
                customLog('❌ Erreur réseau:', 'error');
                customLog(error.message, 'error');
                responseDiv.textContent = `Erreur réseau: ${error.message}`;
            }
        }
        
        // Auto-test au chargement
        document.addEventListener('DOMContentLoaded', function() {
            customLog('🚀 Page chargée, prêt pour les tests', 'success');
        });
    </script>
</body>
</html>
