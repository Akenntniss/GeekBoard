<?php
// Debug pour ajuster_stock.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "=== DEBUG AJUSTER STOCK ===\n";
echo "SESSION: " . json_encode($_SESSION) . "\n";
echo "POST: " . json_encode($_POST) . "\n";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";

// Test inclusion du fichier de config
echo "\n=== TEST INCLUSION CONFIG ===\n";
try {
    require_once '../config/database.php';
    echo "✅ database.php inclus avec succès\n";
} catch (Exception $e) {
    echo "❌ Erreur inclusion database.php: " . $e->getMessage() . "\n";
}

// Test initializeShopSession
echo "\n=== TEST INITIALIZE SHOP SESSION ===\n";
try {
    if (function_exists('initializeShopSession')) {
        initializeShopSession();
        echo "✅ initializeShopSession() exécuté avec succès\n";
    } else {
        echo "❌ Fonction initializeShopSession() non trouvée\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur initializeShopSession: " . $e->getMessage() . "\n";
}

// Test connexion DB
echo "\n=== TEST CONNEXION DB ===\n";
try {
    if (function_exists('getShopDBConnection')) {
        $shop_pdo = getShopDBConnection();
        echo "✅ Connexion DB obtenue avec succès\n";
        echo "DB Info: " . $shop_pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "\n";
    } else {
        echo "❌ Fonction getShopDBConnection() non trouvée\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur connexion DB: " . $e->getMessage() . "\n";
}

// Test avec données simulées
echo "\n=== TEST AVEC DONNÉES SIMULÉES ===\n";
$_POST['produit_id'] = '1';
$_POST['nouvelle_quantite'] = '10';
$_SESSION['user_id'] = '1';

echo "Simulation POST: produit_id=1, nouvelle_quantite=10\n";
echo "Simulation SESSION: user_id=1\n";

// Test requête produit
try {
    $stmt = $shop_pdo->prepare("SELECT id, nom, quantite FROM produits WHERE id = ? LIMIT 1");
    $stmt->execute([1]);
    $produit = $stmt->fetch();
    
    if ($produit) {
        echo "✅ Produit trouvé: " . json_encode($produit) . "\n";
    } else {
        echo "❌ Aucun produit trouvé avec ID=1\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur requête produit: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?>
