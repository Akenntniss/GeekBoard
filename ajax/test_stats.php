<?php
/**
 * TEST SIMPLE POUR L'API DE STATISTIQUES
 */

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST API STATISTIQUES ===\n";

// Test 1: Vérifier les includes
echo "1. Test des includes...\n";
try {
    require_once '../config/database.php';
    echo "✅ database.php chargé\n";
} catch (Exception $e) {
    echo "❌ Erreur database.php: " . $e->getMessage() . "\n";
    exit;
}

try {
    require_once '../includes/functions.php';
    echo "✅ functions.php chargé\n";
} catch (Exception $e) {
    echo "❌ Erreur functions.php: " . $e->getMessage() . "\n";
    exit;
}

// Test 2: Initialiser la session
echo "2. Test de la session...\n";
try {
    session_start();
    initializeShopSession();
    echo "✅ Session initialisée\n";
    echo "   - shop_id: " . ($_SESSION['shop_id'] ?? 'non défini') . "\n";
} catch (Exception $e) {
    echo "❌ Erreur session: " . $e->getMessage() . "\n";
}

// Test 3: Connexion à la base
echo "3. Test de la connexion BDD...\n";
try {
    $pdo = getShopDBConnection();
    echo "✅ Connexion BDD réussie\n";
    
    // Test simple
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reparations");
    $result = $stmt->fetch();
    echo "   - Nombre de réparations: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur BDD: " . $e->getMessage() . "\n";
}

// Test 4: Simuler l'appel API
echo "4. Test de l'API...\n";
try {
    // Simuler les données POST
    $input = [
        'type' => 'nouvelles_reparations',
        'period' => 'today',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d')
    ];
    
    echo "   - Données test: " . json_encode($input) . "\n";
    
    // Appeler l'API en interne
    $_POST = $input;
    $_SESSION['user_id'] = 1; // Forcer un user_id
    
    echo "✅ Test API préparé\n";
    
} catch (Exception $e) {
    echo "❌ Erreur test API: " . $e->getMessage() . "\n";
}

echo "=== FIN DU TEST ===\n";
?>
