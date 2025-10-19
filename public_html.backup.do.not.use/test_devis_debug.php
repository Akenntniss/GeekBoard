<?php
session_start();

// Simuler une session utilisateur pour les tests
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 63; // ID du magasin mkmkmk
    $_SESSION['shop_name'] = 'mkmkmk';
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test User';
}

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Débogage Modal Devis</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .debug-section { background: white; border-radius: 10px; padding: 20px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .console-log { background: #000; color: #0f0; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .test-card { border: 2px solid #007bff; }
    </style>
</head>
<body>";

echo "<div class='container'>
    <h1 class='text-center text-primary'><i class='fas fa-bug'></i> Test Débogage Modal Devis</h1>
    
    <div class='debug-section'>
        <h3><i class='fas fa-info-circle text-info'></i> Informations de Session</h3>
        <ul>
            <li><strong>Shop ID:</strong> " . ($_SESSION['shop_id'] ?? 'NON DÉFINI') . "</li>
            <li><strong>Shop Name:</strong> " . ($_SESSION['shop_name'] ?? 'NON DÉFINI') . "</li>
            <li><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'NON DÉFINI') . "</li>
        </ul>
    </div>
    
    <div class='debug-section'>
        <h3><i class='fas fa-cogs text-warning'></i> Test d'Ouverture du Modal</h3>
        <p>Cliquez sur le bouton ci-dessous pour ouvrir le modal devisModalClean :</p>
        <button type='button' class='btn btn-primary btn-lg test-card' onclick='ouvrirDevisClean(123)'>
            <i class='fas fa-file-invoice'></i> Tester Modal Devis (ID: 123)
        </button>
    </div>
    
    <div class='debug-section'>
        <h3><i class='fas fa-terminal text-success'></i> Console Debug</h3>
        <p>Ouvrez la console de votre navigateur (F12) pour voir les logs de débogage.</p>
        <div class='console-log'>
            <strong>Instructions :</strong><br>
            1. Ouvrez la console du navigateur (F12)<br>
            2. Cliquez sur 'Tester Modal Devis'<br>
            3. Naviguez jusqu'à l'étape 3<br>
            4. Essayez de cliquer sur 'Sauvegarder'<br>
            5. Observez les logs pour identifier le problème
        </div>
    </div>
    
    <div class='debug-section'>
        <h3><i class='fas fa-code text-danger'></i> Test JavaScript Direct</h3>
        <button type='button' class='btn btn-warning' onclick='testJavaScript()'>
            <i class='fas fa-play'></i> Test Direct JavaScript
        </button>
        <div id='jsTestResult' class='mt-3'></div>
    </div>
</div>";

// Inclure le modal devis
include 'components/modals/devis_modal_clean.php';

echo "
<!-- Bootstrap JS -->
<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>

<!-- Script de débogage -->
<script>
console.log('🔧 [DEBUG] Script de débogage chargé');

// Fonction de test JavaScript
function testJavaScript() {
    const result = document.getElementById('jsTestResult');
    let logs = '';
    
    // Test 1: Vérifier si devisCleanManager existe
    if (typeof window.devisCleanManager !== 'undefined') {
        logs += '✅ devisCleanManager existe<br>';
        logs += 'Current Step: ' + window.devisCleanManager.currentStep + '<br>';
        logs += 'Reparation ID: ' + window.devisCleanManager.reparationId + '<br>';
    } else {
        logs += '❌ devisCleanManager n\\'existe pas<br>';
    }
    
    // Test 2: Vérifier le bouton sauvegarder
    const sauvegarderBtn = document.getElementById('sauvegarderBtn');
    if (sauvegarderBtn) {
        logs += '✅ Bouton sauvegarder trouvé<br>';
        logs += 'Display: ' + window.getComputedStyle(sauvegarderBtn).display + '<br>';
        logs += 'Visible: ' + (sauvegarderBtn.offsetParent !== null) + '<br>';
        
        // Test d'événement
        sauvegarderBtn.addEventListener('click', function() {
            console.log('🔴 [TEST] Clic détecté sur le bouton sauvegarder !');
            logs += '🔴 Clic détecté !<br>';
            result.innerHTML = logs;
        }, { once: true });
        
    } else {
        logs += '❌ Bouton sauvegarder non trouvé<br>';
    }
    
    // Test 3: Vérifier le modal
    const modal = document.getElementById('devisModalClean');
    if (modal) {
        logs += '✅ Modal trouvé<br>';
    } else {
        logs += '❌ Modal non trouvé<br>';
    }
    
    result.innerHTML = '<div class=\"alert alert-info\">' + logs + '</div>';
}

// Override console.log pour capturer les messages
const originalLog = console.log;
console.log = function(...args) {
    originalLog.apply(console, args);
    
    // Afficher aussi dans la page
    if (args[0] && args[0].includes('[DEVIS-CLEAN]')) {
        const debugDiv = document.createElement('div');
        debugDiv.className = 'alert alert-primary mt-2';
        debugDiv.innerHTML = '📝 ' + args.join(' ');
        document.body.appendChild(debugDiv);
        
        // Supprimer après 5 secondes
        setTimeout(() => {
            if (debugDiv.parentNode) {
                debugDiv.parentNode.removeChild(debugDiv);
            }
        }, 5000);
    }
};

// Test automatique au chargement
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 [DEBUG] DOM chargé, début des tests...');
    
    setTimeout(function() {
        console.log('🔧 [DEBUG] Test différé...');
        testJavaScript();
    }, 2000);
});
</script>

<!-- Inclure le script devis-clean.js -->
<script src='assets/js/devis-clean.js'></script>

</body>
</html>";
?>
