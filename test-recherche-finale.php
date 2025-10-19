<?php
session_start();
$page_title = "Test Recherche Finale";

// Simuler une session pour les tests
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 1;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include 'components/head.php'; ?>
    <style>
        .test-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .test-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .debug-log {
            background: #000;
            color: #0f0;
            font-family: monospace;
            padding: 1rem;
            border-radius: 0.25rem;
            max-height: 300px;
            overflow-y: auto;
            font-size: 0.875rem;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        .status-success { background-color: #28a745; }
        .status-error { background-color: #dc3545; }
        .status-warning { background-color: #ffc107; }
    </style>
</head>
<body>
    
    <div class="container-fluid">
        <div class="test-container">
            <h1 class="mb-4">🔍 Test Final - Recherche Universelle</h1>
            
            <!-- Section de test du bouton de recherche -->
            <div class="test-section">
                <h3>1. Test du Bouton de Recherche</h3>
                <p>Cliquez sur le bouton de recherche dans la barre de navigation pour ouvrir le modal.</p>
                <div id="button-test-status">
                    <span class="status-indicator status-warning"></span>
                    <span>En attente du test...</span>
                </div>
            </div>
            
            <!-- Section de test de la recherche -->
            <div class="test-section">
                <h3>2. Test de Recherche Directe</h3>
                <div class="mb-3">
                    <label for="testSearchInput" class="form-label">Terme de recherche :</label>
                    <input type="text" class="form-control" id="testSearchInput" placeholder="Tapez votre recherche...">
                </div>
                <button type="button" class="btn btn-primary" id="testSearchBtn">
                    <i class="fas fa-search"></i> Tester la Recherche
                </button>
                <div id="search-test-status" class="mt-2">
                    <span class="status-indicator status-warning"></span>
                    <span>Prêt pour le test</span>
                </div>
            </div>
            
            <!-- Section des résultats -->
            <div class="test-section">
                <h3>3. Résultats de Test</h3>
                <div id="test-results" class="alert alert-info">
                    Aucun test effectué pour le moment.
                </div>
            </div>
            
            <!-- Section de debug -->
            <div class="test-section">
                <h3>4. Journal de Debug</h3>
                <div id="debug-log" class="debug-log">
                    [INIT] Page de test chargée...<br>
                </div>
                <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="clearDebugLog()">
                    Effacer le journal
                </button>
            </div>
        </div>
    </div>
    
    <!-- Inclure le modal de recherche -->
    <?php include 'components/modal-recherche-universel.php'; ?>
    
    <!-- Scripts -->
    <?php include 'components/footer.php'; ?>
    
    <script>
    // Script de test pour vérifier le fonctionnement
    class TestRecherche {
        constructor() {
            this.debugLog = document.getElementById('debug-log');
            this.init();
        }
        
        init() {
            this.log('Initialisation du test de recherche...');
            
            // Vérifier la présence des éléments
            this.checkElements();
            
            // Configurer les événements de test
            this.setupTestEvents();
            
            // Surveiller l'ouverture du modal
            this.monitorModal();
        }
        
        checkElements() {
            const elements = [
                { id: 'rechercheInput', name: 'Input de recherche modal' },
                { id: 'rechercheBtn', name: 'Bouton de recherche modal' },
                { id: 'rechercheModal', name: 'Modal de recherche' },
                { id: 'universalSearchInput', name: 'Input universel (compatibilité)' },
                { id: 'universalSearchBtn', name: 'Bouton universel (compatibilité)' }
            ];
            
            elements.forEach(element => {
                const el = document.getElementById(element.id);
                if (el) {
                    this.log(`✅ ${element.name} trouvé`);
                } else {
                    this.log(`❌ ${element.name} manquant`);
                }
            });
        }
        
        setupTestEvents() {
            // Test direct de recherche
            const testBtn = document.getElementById('testSearchBtn');
            if (testBtn) {
                testBtn.addEventListener('click', () => this.testDirectSearch());
            }
            
            // Surveiller les clics sur le bouton de recherche de la navbar
            document.addEventListener('click', (e) => {
                if (e.target.closest('[data-bs-target="#rechercheModal"]')) {
                    this.log('🔍 Bouton de recherche navbar cliqué');
                    this.updateButtonStatus('success', 'Bouton fonctionne !');
                }
            });
        }
        
        monitorModal() {
            const modal = document.getElementById('rechercheModal');
            if (modal) {
                modal.addEventListener('shown.bs.modal', () => {
                    this.log('📱 Modal de recherche ouvert');
                });
                
                modal.addEventListener('hidden.bs.modal', () => {
                    this.log('📱 Modal de recherche fermé');
                });
            }
        }
        
        async testDirectSearch() {
            const input = document.getElementById('testSearchInput');
            const searchTerm = input.value.trim();
            
            if (!searchTerm) {
                this.log('⚠️ Veuillez saisir un terme de recherche');
                return;
            }
            
            this.log(`🔍 Test de recherche pour: "${searchTerm}"`);
            this.updateSearchStatus('warning', 'Recherche en cours...');
            
            try {
                // Tester l'endpoint de recherche
                const response = await fetch('ajax/recherche-universelle-new.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `search=${encodeURIComponent(searchTerm)}&shop_id=${<?php echo $_SESSION['shop_id']; ?>}`
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.log(`✅ Recherche réussie: ${JSON.stringify(data)}`);
                    this.updateSearchStatus('success', 'Recherche réussie !');
                    this.displayResults(data);
                } else {
                    throw new Error(`HTTP ${response.status}`);
                }
            } catch (error) {
                this.log(`❌ Erreur de recherche: ${error.message}`);
                this.updateSearchStatus('error', 'Erreur de recherche');
            }
        }
        
        displayResults(data) {
            const resultsDiv = document.getElementById('test-results');
            let html = '<h5>Résultats de la recherche :</h5>';
            
            if (data.clients && data.clients.length > 0) {
                html += `<p><strong>Clients :</strong> ${data.clients.length} trouvé(s)</p>`;
            }
            if (data.reparations && data.reparations.length > 0) {
                html += `<p><strong>Réparations :</strong> ${data.reparations.length} trouvée(s)</p>`;
            }
            if (data.commandes && data.commandes.length > 0) {
                html += `<p><strong>Commandes :</strong> ${data.commandes.length} trouvée(s)</p>`;
            }
            
            if (!data.clients?.length && !data.reparations?.length && !data.commandes?.length) {
                html += '<p class="text-muted">Aucun résultat trouvé</p>';
            }
            
            resultsDiv.innerHTML = html;
            resultsDiv.className = 'alert alert-success';
        }
        
        updateButtonStatus(type, message) {
            const statusDiv = document.getElementById('button-test-status');
            const indicator = statusDiv.querySelector('.status-indicator');
            const text = statusDiv.querySelector('span:last-child');
            
            indicator.className = `status-indicator status-${type}`;
            text.textContent = message;
        }
        
        updateSearchStatus(type, message) {
            const statusDiv = document.getElementById('search-test-status');
            const indicator = statusDiv.querySelector('.status-indicator');
            const text = statusDiv.querySelector('span:last-child');
            
            indicator.className = `status-indicator status-${type}`;
            text.textContent = message;
        }
        
        log(message) {
            const timestamp = new Date().toLocaleTimeString();
            this.debugLog.innerHTML += `[${timestamp}] ${message}<br>`;
            this.debugLog.scrollTop = this.debugLog.scrollHeight;
            console.log(`[TestRecherche] ${message}`);
        }
    }
    
    function clearDebugLog() {
        document.getElementById('debug-log').innerHTML = '[CLEAR] Journal effacé...<br>';
    }
    
    // Initialiser le test quand la page est chargée
    document.addEventListener('DOMContentLoaded', () => {
        new TestRecherche();
    });
    </script>
</body>
</html>