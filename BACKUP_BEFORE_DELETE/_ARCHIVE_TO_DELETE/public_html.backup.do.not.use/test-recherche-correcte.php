<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Modal Recherche - IDs Corrects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/recherche-modal-fix.css" rel="stylesheet">
    <style>
        .debug-panel {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            z-index: 9999;
            max-width: 400px;
            font-size: 12px;
        }
        .debug-success { color: #28a745; }
        .debug-error { color: #dc3545; }
        .debug-warning { color: #ffc107; }
        .test-buttons {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Test Modal Recherche - IDs Corrects</h1>
        
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Test du Modal de Recherche</h5>
                    </div>
                    <div class="card-body">
                        <p>Ce test vérifie que le modal de recherche fonctionne correctement avec les IDs corrects.</p>
                        
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#rechercheModal">
                            <i class="fas fa-search me-2"></i>
                            Ouvrir le Modal de Recherche
                        </button>
                        
                        <div class="mt-4">
                            <h6>Instructions de test :</h6>
                            <ol>
                                <li>Cliquez sur le bouton ci-dessus pour ouvrir le modal</li>
                                <li>Vérifiez que tous les éléments sont détectés dans le panneau de debug</li>
                                <li>Tapez un terme de recherche (minimum 2 caractères)</li>
                                <li>Cliquez sur "Rechercher" ou appuyez sur Entrée</li>
                                <li>Vérifiez que les tableaux s'affichent correctement</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panneau de debug -->
    <div class="debug-panel">
        <h6>🔍 Debug - Éléments DOM</h6>
        <div id="debugInfo">
            <p class="text-muted">Ouvrez le modal pour voir les informations de debug</p>
        </div>
    </div>

    <!-- Boutons de test -->
    <div class="test-buttons">
        <button class="btn btn-success" onclick="testModalDisplay()">
            <i class="fas fa-play"></i> Test Affichage
        </button>
        <button class="btn btn-warning" onclick="forceDisplayTables()">
            <i class="fas fa-eye"></i> Forcer Affichage
        </button>
        <button class="btn btn-info" onclick="debugElements()">
            <i class="fas fa-bug"></i> Debug Elements
        </button>
    </div>

    <!-- Inclure le modal -->
    <?php include 'components/modal-recherche-simple.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/recherche-modal-correct.js"></script>
    
    <script>
        // Scripts de test additionnels
        function testModalDisplay() {
            console.log('🧪 Test d\'affichage lancé');
            
            if (typeof window.testModalDisplay === 'function') {
                window.testModalDisplay();
            } else {
                console.error('❌ Fonction testModalDisplay non trouvée');
            }
        }
        
        function forceDisplayTables() {
            console.log('🔧 Forçage de l\'affichage des tableaux');
            
            // Forcer l'affichage de tous les éléments critiques
            const elements = [
                '#rechercheResults',
                '#resultTabContent',
                '.tab-pane',
                '.table-responsive',
                '.table',
                '.table tbody',
                '.table thead'
            ];
            
            elements.forEach(selector => {
                const els = document.querySelectorAll(selector);
                els.forEach(el => {
                    el.style.display = 'block';
                    el.style.visibility = 'visible';
                    el.style.opacity = '1';
                    el.classList.add('force-display');
                });
            });
            
            // Ajouter la classe de forçage au modal
            const modal = document.getElementById('rechercheModal');
            if (modal) {
                modal.classList.add('modal-force-display');
            }
            
            console.log('✅ Forçage terminé');
        }
        
        function debugElements() {
            console.log('🔍 Debug des éléments DOM');
            const debugInfo = document.getElementById('debugInfo');
            
            const elements = {
                'rechercheModal': document.getElementById('rechercheModal'),
                'rechercheInput': document.getElementById('rechercheInput'),
                'rechercheBtn': document.getElementById('rechercheBtn'),
                'rechercheLoading': document.getElementById('rechercheLoading'),
                'rechercheResults': document.getElementById('rechercheResults'),
                'rechercheEmpty': document.getElementById('rechercheEmpty'),
                'clientsCount': document.getElementById('clientsCount'),
                'reparationsCount': document.getElementById('reparationsCount'),
                'commandesCount': document.getElementById('commandesCount'),
                'clientsTableBody': document.getElementById('clientsTableBody'),
                'reparationsTableBody': document.getElementById('reparationsTableBody'),
                'commandesTableBody': document.getElementById('commandesTableBody'),
                'resultTabContent': document.getElementById('resultTabContent'),
                'clients-pane': document.getElementById('clients-pane'),
                'reparations-pane': document.getElementById('reparations-pane'),
                'commandes-pane': document.getElementById('commandes-pane')
            };

            debugInfo.innerHTML = '<h6>🔍 État des éléments DOM :</h6>';
            
            for (const [name, element] of Object.entries(elements)) {
                if (element) {
                    const style = window.getComputedStyle(element);
                    const display = style.display;
                    const visibility = style.visibility;
                    const opacity = style.opacity;
                    
                    debugInfo.innerHTML += `
                        <div class="debug-success">
                            ✅ ${name}: OK
                            <br>&nbsp;&nbsp;&nbsp;display: ${display}
                            <br>&nbsp;&nbsp;&nbsp;visibility: ${visibility}
                            <br>&nbsp;&nbsp;&nbsp;opacity: ${opacity}
                        </div>
                    `;
                } else {
                    debugInfo.innerHTML += `
                        <div class="debug-error">
                            ❌ ${name}: MANQUANT
                        </div>
                    `;
                }
            }
            
            // Vérifier les événements
            const input = document.getElementById('rechercheInput');
            const btn = document.getElementById('rechercheBtn');
            
            if (input && btn) {
                debugInfo.innerHTML += '<div class="debug-success">✅ Événements prêts</div>';
            } else {
                debugInfo.innerHTML += '<div class="debug-error">❌ Événements manquants</div>';
            }
        }
        
        // Auto-debug quand le modal s'ouvre
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('rechercheModal');
            if (modal) {
                modal.addEventListener('shown.bs.modal', function() {
                    setTimeout(debugElements, 500);
                });
            }
        });
    </script>
</body>
</html> 