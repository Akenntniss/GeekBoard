<?php
// Script de diagnostic pour les problèmes de session du modal
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Démarrer la session comme dans les APIs
if (isset($_COOKIE['PHPSESSID']) || isset($_GET['PHPSESSID'])) {
    if (isset($_GET['PHPSESSID'])) {
        session_id($_GET['PHPSESSID']);
    }
    
    ini_set('session.use_cookies', 1);
    ini_set('session.use_trans_sid', 1);
    ini_set('session.cache_limiter', 'nocache');
    ini_set('session.gc_maxlifetime', 86400);
    
    session_start();
} else {
    session_start();
}

// Inclure les fichiers nécessaires
require_once 'public_html/config/database.php';
require_once 'public_html/includes/functions.php';

// Initialiser la session shop
if (function_exists('initializeShopSession')) {
    initializeShopSession();
}

// S'assurer que le shop_id est disponible
if (!isset($_SESSION['shop_id']) && isset($_GET['shop_id'])) {
    $_SESSION['shop_id'] = $_GET['shop_id'];
}

echo "<h1>🔍 Diagnostic Session Modal</h1>";
echo "<h2>Informations Session</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "\nContenu Session:\n";
print_r($_SESSION);
echo "\nCookies:\n";
print_r($_COOKIE);
echo "\nGET Parameters:\n";
print_r($_GET);
echo "</pre>";

echo "<h2>Test Authentification</h2>";
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo "❌ <strong>PROBLÈME:</strong> Utilisateur non connecté (user_id manquant)<br>";
} else {
    echo "✅ Utilisateur connecté - ID: " . $_SESSION['user_id'] . "<br>";
}

if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
    echo "❌ <strong>PROBLÈME:</strong> Shop ID manquant<br>";
} else {
    echo "✅ Shop ID disponible: " . $_SESSION['shop_id'] . "<br>";
}

echo "<h2>Test Connexion Base de Données</h2>";
try {
    $shop_pdo = getShopDBConnection();
    echo "✅ Connexion shop réussie<br>";
    
    // Test requête utilisateurs
    $stmt = $shop_pdo->query("SELECT id, full_name, role FROM users ORDER BY role DESC, full_name ASC LIMIT 3");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Requête utilisateurs réussie - " . count($users) . " utilisateurs trouvés<br>";
    echo "<pre>";
    print_r($users);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "❌ <strong>ERREUR Base:</strong> " . $e->getMessage() . "<br>";
}

echo "<h2>Test URLs</h2>";
echo "<p>Testez ces URLs:</p>";
echo "<ul>";
echo "<li><a href='?shop_id=63' target='_blank'>Avec shop_id=63</a></li>";
echo "<li><a href='diagnostic_session_modal.php?PHPSESSID=" . session_id() . "&shop_id=63' target='_blank'>Avec PHPSESSID et shop_id</a></li>";
echo "</ul>";

// Test direct de l'API
echo "<h2>Test API Direct</h2>";
echo "<script>
fetch('ajax_handlers/ajouter_tache_ajax.php?shop_id=63', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'titre=Test&description=Test&priorite=haute&statut=a_faire'
})
.then(response => response.json())
.then(data => {
    console.log('Réponse API:', data);
    document.getElementById('api-result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
})
.catch(error => {
    console.error('Erreur API:', error);
    document.getElementById('api-result').innerHTML = '<pre>Erreur: ' + error + '</pre>';
});
</script>";
echo "<div id='api-result'>Chargement du test API...</div>";
?>
