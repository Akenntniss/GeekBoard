<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG SIMPLE ===\n";

// Test de base
echo "1. PHP fonctionne\n";

// Test de connexion DB
try {
    require_once __DIR__ . '/../config/database.php';
    echo "2. Fichiers inclus OK\n";
    
    // Initialiser la session magasin
    $_SESSION['shop_id'] = 1;
    initializeShopSession();
    
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        echo "3. Connexion DB ECHEC - Impossible de se connecter à la base du magasin\n";
        throw new Exception("Connexion à la base du magasin impossible");
    }
    
    // Afficher quelle base est utilisée
    $db_name = $shop_pdo->query("SELECT DATABASE()")->fetchColumn();
    $shop_info = $_SESSION['shop_name'] ?? 'Inconnu';
    echo "3. Connexion DB OK - Magasin: $shop_info - Base: $db_name\n";
    
    // Test requête simple
    $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM devis WHERE statut = 'envoye'");
    $result = $stmt->fetch();
    echo "4. Devis envoyés: " . $result['count'] . "\n";
    
    // Test templates
    $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM sms_templates WHERE est_actif = 1");
    $result = $stmt->fetch();
    echo "5. Templates SMS actifs: " . $result['count'] . "\n";
    
    // Inclure les fonctions SMS
    require_once __DIR__ . '/../includes/sms_functions.php';
    
    // Test fonction SMS
    if (function_exists('send_sms')) {
        echo "6. Fonction send_sms existe\n";
        
        // Test d'envoi SMS (sans vraiment envoyer)
        echo "7. Test SMS simulation...\n";
        // $result = send_sms("0123456789", "Test message");
        // echo "   Résultat: " . json_encode($result) . "\n";
        
    } else {
        echo "6. Fonction send_sms TOUJOURS MANQUANTE\n";
    }
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}
?>
