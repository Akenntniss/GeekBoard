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
    <title>État du Système Devis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .test-card { background: white; border-radius: 10px; padding: 20px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .console-output { background: #000; color: #0f0; padding: 15px; border-radius: 5px; font-family: monospace; height: 300px; overflow-y: auto; }
        .test-button { margin: 10px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center">🔧 État du Système Devis</h1>
        
        <div class="test-card">
            <h3><i class="fas fa-info-circle"></i> Informations Session</h3>
            <ul>
                <li><strong>Shop ID:</strong> <?= $_SESSION['shop_id'] ?? 'NON DÉFINI' ?></li>
                <li><strong>User ID:</strong> <?= $_SESSION['user_id'] ?? 'NON DÉFINI' ?></li>
                <li><strong>Shop Name:</strong> <?= $_SESSION['shop_name'] ?? 'NON DÉFINI' ?></li>
            </ul>
        </div>
        
        <div class="test-card">
            <h3><i class="fas fa-play-circle"></i> Tests Rapides</h3>
            <div class="row">
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary test-button w-100" onclick="testManagerExistence()">
                        <i class="fas fa-search"></i> Test Gestionnaire
                    </button>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-success test-button w-100" onclick="testModalOpen()">
                        <i class="fas fa-window-restore"></i> Test Modal
                    </button>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-warning test-button w-100" onclick="testSaveButton()">
                        <i class="fas fa-save"></i> Test Bouton
                    </button>
                </div>
            </div>
        </div>
        
        <div class="test-card">
            <h3><i class="fas fa-terminal"></i> Console de Débogage</h3>
            <div id="consoleOutput" class="console-output">
                Prêt pour les tests...
            </div>
        </div>
        
        <div class="test-card">
            <h3><i class="fas fa-cogs"></i> État du Système</h3>
            <div id="systemStatus">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        </div>
        
        <div class="test-card">
            <h3><i class="fas fa-rocket"></i> Test Direct</h3>
            <p>Ce bouton devrait déclencher directement la fonction d'ouverture du modal :</p>
            <button type="button" class="btn btn-lg btn-primary" onclick="ouvrirDevisClean(123)">
                <i class="fas fa-file-invoice"></i> OUVERTURE DIRECTE MODAL DEVIS
            </button>
        </div>
    </div>

    <!-- Inclure le modal devis -->
    <?php include 'components/modals/devis_modal_clean.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script devis-clean EXACT comme dans la page réparations -->
    <script src="assets/js/devis-clean.js"></script>
    
    <script>
        let consoleDiv = document.getElementById('consoleOutput');
        let systemStatusDiv = document.getElementById('systemStatus');
        
        // Fonction pour logger dans notre console custom
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
        
        // Intercepter les logs console
        const originalLog = console.log;
        const originalError = console.error;
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            customLog(args.join(' '), 'info');
        };
        
        console.error = function(...args) {
            originalError.apply(console, args);
            customLog(args.join(' '), 'error');
        };
        
        // Tests spécifiques
        function testManagerExistence() {
            customLog('🔍 Test du gestionnaire de devis...', 'info');
            
            if (typeof window.devisCleanManager !== 'undefined') {
                customLog('✅ devisCleanManager existe', 'success');
                customLog(`Étape courante: ${window.devisCleanManager.currentStep}`, 'info');
                customLog(`ID réparation: ${window.devisCleanManager.reparationId || 'Non défini'}`, 'info');
            } else {
                customLog('❌ devisCleanManager non trouvé', 'error');
            }
            
            // Test des fonctions globales
            const functions = ['ouvrirDevisClean', 'ouvrirModalDevis', 'ouvrirNouveauModalDevis'];
            functions.forEach(func => {
                if (typeof window[func] === 'function') {
                    customLog(`✅ Fonction ${func} existe`, 'success');
                } else {
                    customLog(`❌ Fonction ${func} manquante`, 'error');
                }
            });
        }
        
        function testModalOpen() {
            customLog('🚀 Test d\'ouverture du modal...', 'info');
            
            try {
                if (typeof ouvrirDevisClean === 'function') {
                    customLog('📂 Ouverture via ouvrirDevisClean(999)', 'info');
                    ouvrirDevisClean(999);
                } else {
                    customLog('❌ Fonction ouvrirDevisClean non disponible', 'error');
                }
            } catch (error) {
                customLog(`❌ Erreur: ${error.message}`, 'error');
            }
        }
        
        function testSaveButton() {
            customLog('💾 Test du bouton sauvegarder...', 'info');
            
            const modal = document.getElementById('devisModalClean');
            const saveBtn = document.getElementById('sauvegarderBtn');
            
            if (!modal) {
                customLog('❌ Modal devisModalClean non trouvé', 'error');
                return;
            }
            
            if (!saveBtn) {
                customLog('❌ Bouton sauvegarder non trouvé', 'error');
                return;
            }
            
            customLog('✅ Modal et bouton trouvés', 'success');
            customLog(`Bouton display: ${window.getComputedStyle(saveBtn).display}`, 'info');
            customLog(`Bouton visible: ${saveBtn.offsetParent !== null}`, 'info');
            
            // Simuler l'étape 3 pour voir le bouton
            if (typeof window.devisCleanManager !== 'undefined') {
                customLog('🔄 Simulation de navigation vers étape 3...', 'info');
                window.devisCleanManager.goToStep(3);
                
                setTimeout(() => {
                    customLog(`Bouton après étape 3 - display: ${window.getComputedStyle(saveBtn).display}`, 'info');
                    customLog(`Bouton après étape 3 - visible: ${saveBtn.offsetParent !== null}`, 'info');
                }, 100);
            }
        }
        
        // Vérification automatique du système
        function checkSystemStatus() {
            let status = '<ul>';
            
            // Vérifier Bootstrap
            if (typeof bootstrap !== 'undefined') {
                status += '<li class="status-ok"><i class="fas fa-check"></i> Bootstrap chargé</li>';
            } else {
                status += '<li class="status-error"><i class="fas fa-times"></i> Bootstrap manquant</li>';
            }
            
            // Vérifier jQuery (si utilisé)
            if (typeof $ !== 'undefined') {
                status += '<li class="status-ok"><i class="fas fa-check"></i> jQuery chargé</li>';
            } else {
                status += '<li class="status-error"><i class="fas fa-times"></i> jQuery non chargé</li>';
            }
            
            // Vérifier le gestionnaire de devis
            if (typeof window.devisCleanManager !== 'undefined') {
                status += '<li class="status-ok"><i class="fas fa-check"></i> Gestionnaire de devis chargé</li>';
            } else {
                status += '<li class="status-error"><i class="fas fa-times"></i> Gestionnaire de devis manquant</li>';
            }
            
            // Vérifier le modal
            const modal = document.getElementById('devisModalClean');
            if (modal) {
                status += '<li class="status-ok"><i class="fas fa-check"></i> Modal devis présent</li>';
            } else {
                status += '<li class="status-error"><i class="fas fa-times"></i> Modal devis manquant</li>';
            }
            
            // Vérifier le bouton sauvegarder
            const saveBtn = document.getElementById('sauvegarderBtn');
            if (saveBtn) {
                status += '<li class="status-ok"><i class="fas fa-check"></i> Bouton sauvegarder présent</li>';
            } else {
                status += '<li class="status-error"><i class="fas fa-times"></i> Bouton sauvegarder manquant</li>';
            }
            
            status += '</ul>';
            systemStatusDiv.innerHTML = status;
        }
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            customLog('🚀 DOM chargé, initialisation des tests...', 'info');
            
            setTimeout(() => {
                checkSystemStatus();
                testManagerExistence();
            }, 1000);
        });
        
        // Événement de test quand le modal s'ouvre
        const modal = document.getElementById('devisModalClean');
        if (modal) {
            modal.addEventListener('show.bs.modal', function(e) {
                customLog('📂 Événement show.bs.modal détecté', 'success');
            });
            
            modal.addEventListener('shown.bs.modal', function(e) {
                customLog('✅ Modal complètement ouvert', 'success');
            });
        }
    </script>
</body>
</html>
