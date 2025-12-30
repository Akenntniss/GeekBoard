<?php
/**
 * Test spécifique pour les boutons de pointage dans la navbar
 */

// Variables d'authentification (simuler un utilisateur connecté)
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['full_name'] = 'Test Admin';
$_SESSION['shop_name'] = 'Test Shop';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Navbar Boutons Pointage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <h1 class="container mt-4">🧪 Test Navbar avec Boutons Pointage</h1>
    
    <div class="container mt-4">
        <div class="alert alert-info">
            <h4>Test 1: Inclusion directe de la navbar</h4>
            <p>Testons si les boutons apparaissent quand on inclut la navbar modifiée :</p>
        </div>
        
        <!-- Test inclusion navbar -->
        <div class="border p-3 mb-4">
            <h5>Navbar incluse :</h5>
            <?php
            try {
                // Inclure la navbar modifiée
                include '/var/www/mdgeek.top/includes/navbar.php';
                echo "<div class='alert alert-success mt-2'>✅ Navbar incluse avec succès</div>";
            } catch (Exception $e) {
                echo "<div class='alert alert-danger mt-2'>❌ Erreur inclusion navbar: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
        
        <div class="alert alert-warning">
            <h4>Test 2: Boutons manuels</h4>
            <p>Test manuel des boutons de pointage :</p>
        </div>
        
        <!-- Test boutons manuels -->
        <div class="border p-3 mb-4">
            <h5>Boutons de test manuels :</h5>
            
            <!-- Statut actuel -->
            <div id="time-status-display" class="mb-2">
                <small class="text-muted">Chargement...</small>
            </div>
            
            <!-- Bouton principal Clock-In/Clock-Out -->
            <button id="clock-button" class="btn btn-success btn-sm mx-1" onclick="alert('Clock-In test')">
                <i class="fas fa-sign-in-alt"></i> Clock-In
            </button>
            
            <!-- Bouton Pause -->
            <button id="break-button" class="btn btn-outline-secondary btn-sm mx-1" style="display: none;" onclick="alert('Pause test')">
                <i class="fas fa-pause"></i> Pause
            </button>
            
            <div class="mt-2">
                <small class="text-muted">Si vous voyez ces boutons, le HTML fonctionne.</small>
            </div>
        </div>
        
        <div class="alert alert-primary">
            <h4>Test 3: JavaScript time_tracking.js</h4>
            <p>Test du chargement du script JavaScript :</p>
        </div>
        
        <div class="border p-3 mb-4">
            <button class="btn btn-info" onclick="testTimeTrackingScript()">Test Script JS</button>
            <div id="js-test-result" class="mt-2"></div>
        </div>
        
        <div class="alert alert-secondary">
            <h4>Test 4: Variables de session</h4>
        </div>
        
        <div class="border p-3">
            <h5>Variables $_SESSION :</h5>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
    </div>
    
    <!-- Include du script time_tracking -->
    <script src="/assets/js/time_tracking.js"></script>
    
    <script>
        function testTimeTrackingScript() {
            const result = document.getElementById('js-test-result');
            
            // Test 1: Vérifier si le script est chargé
            if (typeof TimeTracking !== 'undefined') {
                result.innerHTML += '<div class="alert alert-success">✅ Classe TimeTracking disponible</div>';
            } else {
                result.innerHTML += '<div class="alert alert-danger">❌ Classe TimeTracking non disponible</div>';
            }
            
            // Test 2: Vérifier l'instance globale
            if (typeof timeTrackingInstance !== 'undefined') {
                result.innerHTML += '<div class="alert alert-success">✅ Instance timeTrackingInstance disponible</div>';
            } else {
                result.innerHTML += '<div class="alert alert-warning">⚠️ Instance timeTrackingInstance non encore créée</div>';
            }
            
            // Test 3: Vérifier window.timeTracking
            if (typeof window.timeTracking !== 'undefined') {
                result.innerHTML += '<div class="alert alert-success">✅ window.timeTracking disponible</div>';
            } else {
                result.innerHTML += '<div class="alert alert-warning">⚠️ window.timeTracking non disponible</div>';
            }
            
            // Test 4: Test API direct
            fetch('/time_tracking_api.php?action=get_status')
                .then(response => response.json())
                .then(data => {
                    result.innerHTML += '<div class="alert alert-info">📡 API Response: <pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
                })
                .catch(error => {
                    result.innerHTML += '<div class="alert alert-danger">❌ Erreur API: ' + error + '</div>';
                });
        }
        
        // Test de chargement automatique
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                testTimeTrackingScript();
            }, 2000);
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
