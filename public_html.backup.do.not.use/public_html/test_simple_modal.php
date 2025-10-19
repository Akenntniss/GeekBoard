<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Simple Modal Devis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 10px; }
        .console-log { background: #000; color: #0f0; padding: 10px; border-radius: 5px; font-family: monospace; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center">🔧 Test Simple Modal Devis</h1>
        
        <div class="test-section">
            <h3>Test 1: Ouverture du Modal</h3>
            <button type="button" class="btn btn-primary" onclick="testModalOpen()">
                <i class="fas fa-file-invoice"></i> Ouvrir Modal Test
            </button>
        </div>
        
        <div class="test-section">
            <h3>Test 2: Logs de la Console</h3>
            <div id="consoleOutput" class="console-log">
                Attendez les logs...
            </div>
        </div>
        
        <div class="test-section">
            <h3>Test 3: Vérification des Scripts</h3>
            <div id="scriptStatus"></div>
            <button type="button" class="btn btn-info" onclick="checkScripts()">
                <i class="fas fa-search"></i> Vérifier Scripts
            </button>
        </div>
    </div>

    <!-- Modal Devis -->
    <div class="modal fade" id="devisModalClean" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h4 class="modal-title">
                        <i class="fas fa-file-invoice me-2"></i>Créer un devis
                    </h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="step-content" id="step-1">
                        <h5>Étape 1: Test Simple</h5>
                        <input type="text" class="form-control" id="devis_titre" placeholder="Titre du devis">
                        <input type="hidden" id="devis_reparation_id" value="123">
                    </div>
                    
                    <div class="step-content" id="step-3" style="display: none;">
                        <h5>Étape 3: Solutions</h5>
                        <div id="solutionsContainer">
                            <div class="solution-item card mb-3">
                                <div class="card-body">
                                    <input type="text" class="form-control solution-nom mb-2" value="Test Solution">
                                    <input type="number" class="form-control solution-prix" value="50.00">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" id="suivantBtn" onclick="goToStep3()">Aller à l'étape 3</button>
                    <button type="button" class="btn btn-success" id="sauvegarderBtn" style="display: none;" onclick="testSave()">
                        <i class="fas fa-save"></i> Sauvegarder TEST
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let consoleDiv = document.getElementById('consoleOutput');
        let originalLog = console.log;
        let originalError = console.error;
        
        // Capturer tous les logs
        function addToConsole(message, type = 'log') {
            const color = type === 'error' ? '#f00' : '#0f0';
            consoleDiv.innerHTML += '<div style="color: ' + color + '">[' + new Date().toLocaleTimeString() + '] ' + message + '</div>';
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
        }
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            addToConsole(args.join(' '));
        };
        
        console.error = function(...args) {
            originalError.apply(console, args);
            addToConsole(args.join(' '), 'error');
        };
        
        // Variables de test
        let currentStep = 1;
        let reparationId = 123;
        
        function testModalOpen() {
            console.log('🔧 [TEST] Ouverture du modal...');
            const modal = new bootstrap.Modal(document.getElementById('devisModalClean'));
            modal.show();
        }
        
        function goToStep3() {
            console.log('🔧 [TEST] Navigation vers étape 3...');
            currentStep = 3;
            
            // Cacher étape 1
            document.getElementById('step-1').style.display = 'none';
            // Afficher étape 3
            document.getElementById('step-3').style.display = 'block';
            
            // Cacher bouton suivant, afficher sauvegarder
            document.getElementById('suivantBtn').style.display = 'none';
            document.getElementById('sauvegarderBtn').style.display = 'block';
            
            console.log('✅ [TEST] Bouton sauvegarder maintenant visible');
        }
        
        function testSave() {
            console.log('🔴 [TEST] BOUTON SAUVEGARDER CLIQUÉ !');
            console.log('🔴 [TEST] Réparation ID:', reparationId);
            
            // Test de données
            const titre = document.getElementById('devis_titre').value || 'Test Devis';
            const solutions = [];
            
            document.querySelectorAll('.solution-item').forEach(solution => {
                const nom = solution.querySelector('.solution-nom').value;
                const prix = solution.querySelector('.solution-prix').value;
                solutions.push({ nom, prix });
            });
            
            console.log('📝 [TEST] Données collectées:', { titre, solutions });
            
            // Test d'envoi Ajax
            testAjaxCall({ 
                reparation_id: reparationId, 
                titre: titre, 
                solutions: solutions 
            });
        }
        
        async function testAjaxCall(data) {
            console.log('📤 [TEST] Test d\'envoi Ajax...');
            
            try {
                const response = await fetch('ajax/creer_devis_clean.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                console.log('📡 [TEST] Réponse HTTP:', response.status, response.statusText);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('✅ [TEST] Réponse JSON:', result);
                
                if (result.success) {
                    alert('✅ Test réussi ! Devis créé: ' + result.numero_devis);
                } else {
                    alert('❌ Erreur: ' + result.message);
                }
                
            } catch (error) {
                console.error('❌ [TEST] Erreur Ajax:', error);
                alert('❌ Erreur de communication: ' + error.message);
            }
        }
        
        function checkScripts() {
            console.log('🔍 [TEST] Vérification des scripts...');
            
            let status = '';
            
            // Vérifier Bootstrap
            if (typeof bootstrap !== 'undefined') {
                status += '✅ Bootstrap chargé<br>';
            } else {
                status += '❌ Bootstrap non chargé<br>';
            }
            
            // Vérifier le modal
            const modal = document.getElementById('devisModalClean');
            if (modal) {
                status += '✅ Modal trouvé<br>';
            } else {
                status += '❌ Modal non trouvé<br>';
            }
            
            // Vérifier le bouton
            const btn = document.getElementById('sauvegarderBtn');
            if (btn) {
                status += '✅ Bouton sauvegarder trouvé<br>';
                status += 'Display: ' + window.getComputedStyle(btn).display + '<br>';
            } else {
                status += '❌ Bouton non trouvé<br>';
            }
            
            document.getElementById('scriptStatus').innerHTML = status;
        }
        
        // Test automatique au chargement
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 [TEST] DOM chargé, début des tests automatiques...');
            setTimeout(checkScripts, 1000);
        });
    </script>
</body>
</html>
