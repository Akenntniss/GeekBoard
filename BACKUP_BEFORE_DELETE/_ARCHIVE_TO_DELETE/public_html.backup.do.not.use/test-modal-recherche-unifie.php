<?php
/**
 * Page de test pour le modal de recherche unifié
 * Test des fonctionnalités et de l'affichage
 */

// Démarrer la session pour simuler un environnement normal
session_start();

// Simuler des variables de session
$_SESSION['shop_id'] = 'mkmkmk';
$_SESSION['shop_name'] = 'Test Shop';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Modal Recherche Unifié</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        
        .test-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .test-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-success { background-color: #28a745; }
        .status-warning { background-color: #ffc107; }
        .status-error { background-color: #dc3545; }
        
        .test-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
            font-family: monospace;
            font-size: 14px;
        }
        
        .debug-title {
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
        }
        
        #actionLog {
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-card">
            <h1><i class="fas fa-search text-primary"></i> Test Modal Recherche Unifié</h1>
            <p class="text-muted">Test de fonctionnement du modal de recherche universelle</p>
            
            <!-- Informations de l'environnement -->
            <div class="alert alert-info">
                <strong><i class="fas fa-info-circle"></i> Environnement de test :</strong><br>
                <strong>Shop ID :</strong> <?php echo $_SESSION['shop_id']; ?><br>
                <strong>Shop Name :</strong> <?php echo $_SESSION['shop_name']; ?><br>
                <strong>Timestamp :</strong> <?php echo date('Y-m-d H:i:s'); ?>
            </div>
        </div>
        
        <div class="test-card">
            <h3><i class="fas fa-play text-success"></i> Tests de Fonctionnalité</h3>
            
            <div class="test-buttons">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rechercheModal">
                    <i class="fas fa-search"></i> Ouvrir Modal de Recherche
                </button>
                
                <button class="btn btn-success" onclick="testElementsDOM()">
                    <i class="fas fa-check"></i> Test Éléments DOM
                </button>
                
                <button class="btn btn-warning" onclick="testBootstrap()">
                    <i class="fas fa-cog"></i> Test Bootstrap
                </button>
                
                <button class="btn btn-info" onclick="testAjax()">
                    <i class="fas fa-cloud"></i> Test AJAX
                </button>
                
                <button class="btn btn-secondary" onclick="clearLog()">
                    <i class="fas fa-trash"></i> Vider Log
                </button>
            </div>
        </div>
        
        <div class="test-card">
            <h3><i class="fas fa-clipboard-check text-primary"></i> Résultats des Tests</h3>
            
            <div id="testResults">
                <div class="alert alert-secondary">
                    <i class="fas fa-clock"></i> En attente des tests...
                </div>
            </div>
        </div>
        
        <div class="test-card">
            <h3><i class="fas fa-terminal text-dark"></i> Journal des Actions</h3>
            
            <div class="debug-info">
                <div class="debug-title">📝 Log en temps réel</div>
                <div id="actionLog">
                    [<?php echo date('H:i:s'); ?>] Page de test chargée<br>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Inclure le modal -->
    <?php include 'components/modal-recherche-universel.php'; ?>
    
    <script>
        // Variables globales pour les tests
        let testResults = [];
        
        // Fonction pour ajouter au log
        function addToLog(message, type = 'info') {
            const log = document.getElementById('actionLog');
            const timestamp = new Date().toLocaleTimeString();
            const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️';
            log.innerHTML += `[${timestamp}] ${icon} ${message}<br>`;
            log.scrollTop = log.scrollHeight;
        }
        
        // Fonction pour mettre à jour les résultats
        function updateResults() {
            const resultsDiv = document.getElementById('testResults');
            
            if (testResults.length === 0) {
                resultsDiv.innerHTML = '<div class="alert alert-secondary"><i class="fas fa-clock"></i> En attente des tests...</div>';
                return;
            }
            
            let html = '';
            testResults.forEach(result => {
                const statusClass = result.status === 'success' ? 'status-success' : 
                                   result.status === 'warning' ? 'status-warning' : 'status-error';
                const alertClass = result.status === 'success' ? 'alert-success' : 
                                   result.status === 'warning' ? 'alert-warning' : 'alert-danger';
                
                html += `
                    <div class="alert ${alertClass}">
                        <span class="status-indicator ${statusClass}"></span>
                        <strong>${result.test}:</strong> ${result.message}
                    </div>
                `;
            });
            
            resultsDiv.innerHTML = html;
        }
        
        // Test des éléments DOM
        function testElementsDOM() {
            addToLog('Début du test des éléments DOM', 'info');
            
            const elements = [
                { id: 'rechercheModal', name: 'Modal de recherche' },
                { id: 'rechercheInput', name: 'Champ de recherche' },
                { id: 'rechercheBtn', name: 'Bouton de recherche' },
                { id: 'rechercheLoading', name: 'Zone de chargement' },
                { id: 'clientsTableBody', name: 'Tableau clients' },
                { id: 'reparationsTableBody', name: 'Tableau réparations' },
                { id: 'commandesTableBody', name: 'Tableau commandes' }
            ];
            
            let allFound = true;
            let foundCount = 0;
            
            elements.forEach(element => {
                const el = document.getElementById(element.id);
                if (el) {
                    foundCount++;
                    addToLog(`✅ ${element.name} trouvé`, 'success');
                } else {
                    allFound = false;
                    addToLog(`❌ ${element.name} NON trouvé`, 'error');
                }
            });
            
            const result = {
                test: 'Éléments DOM',
                status: allFound ? 'success' : 'error',
                message: `${foundCount}/${elements.length} éléments trouvés`
            };
            
            testResults.push(result);
            updateResults();
            
            addToLog(`Test DOM terminé: ${result.message}`, allFound ? 'success' : 'error');
        }
        
        // Test de Bootstrap
        function testBootstrap() {
            addToLog('Début du test Bootstrap', 'info');
            
            let bootstrapOk = false;
            let modalOk = false;
            
            if (typeof bootstrap !== 'undefined') {
                bootstrapOk = true;
                addToLog('✅ Bootstrap chargé', 'success');
                
                if (bootstrap.Modal) {
                    modalOk = true;
                    addToLog('✅ Bootstrap Modal disponible', 'success');
                    
                    // Tester l'initialisation d'un modal
                    try {
                        const modal = document.getElementById('rechercheModal');
                        if (modal) {
                            const bsModal = new bootstrap.Modal(modal);
                            addToLog('✅ Modal peut être initialisé', 'success');
                        }
                    } catch (e) {
                        addToLog(`❌ Erreur initialisation modal: ${e.message}`, 'error');
                        modalOk = false;
                    }
                } else {
                    addToLog('❌ Bootstrap Modal non disponible', 'error');
                }
            } else {
                addToLog('❌ Bootstrap non chargé', 'error');
            }
            
            const result = {
                test: 'Bootstrap',
                status: bootstrapOk && modalOk ? 'success' : 'error',
                message: bootstrapOk ? (modalOk ? 'Bootstrap et Modal OK' : 'Bootstrap OK, Modal KO') : 'Bootstrap non chargé'
            };
            
            testResults.push(result);
            updateResults();
            
            addToLog(`Test Bootstrap terminé: ${result.message}`, result.status);
        }
        
        // Test AJAX
        function testAjax() {
            addToLog('Début du test AJAX', 'info');
            
            fetch('ajax/recherche_universelle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'terme=test'
            })
            .then(response => {
                addToLog(`Réponse HTTP: ${response.status}`, response.ok ? 'success' : 'warning');
                return response.json();
            })
            .then(data => {
                addToLog('✅ Réponse JSON reçue', 'success');
                
                const result = {
                    test: 'AJAX',
                    status: 'success',
                    message: 'Connexion AJAX fonctionnelle'
                };
                
                testResults.push(result);
                updateResults();
                
                addToLog('Test AJAX terminé avec succès', 'success');
            })
            .catch(error => {
                addToLog(`❌ Erreur AJAX: ${error.message}`, 'error');
                
                const result = {
                    test: 'AJAX',
                    status: 'error',
                    message: `Erreur: ${error.message}`
                };
                
                testResults.push(result);
                updateResults();
            });
        }
        
        // Vider le log
        function clearLog() {
            document.getElementById('actionLog').innerHTML = `[${new Date().toLocaleTimeString()}] Log vidé<br>`;
            testResults = [];
            updateResults();
        }
        
        // Écouter les événements du modal
        document.addEventListener('DOMContentLoaded', function() {
            addToLog('DOM chargé, initialisation des événements', 'info');
            
            const modal = document.getElementById('rechercheModal');
            if (modal) {
                modal.addEventListener('show.bs.modal', function() {
                    addToLog('🚀 Modal en cours d\'ouverture', 'info');
                });
                
                modal.addEventListener('shown.bs.modal', function() {
                    addToLog('✅ Modal ouvert avec succès', 'success');
                });
                
                modal.addEventListener('hide.bs.modal', function() {
                    addToLog('Modal en cours de fermeture', 'info');
                });
                
                modal.addEventListener('hidden.bs.modal', function() {
                    addToLog('Modal fermé', 'info');
                });
            }
            
            // Test automatique au chargement
            setTimeout(() => {
                addToLog('Lancement des tests automatiques', 'info');
                testElementsDOM();
                setTimeout(() => testBootstrap(), 500);
            }, 1000);
        });
    </script>
</body>
</html> 