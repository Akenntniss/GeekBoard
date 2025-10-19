<?php
try {
    echo "1. Début du test...<br>";
    
    // Inclure la configuration de session avant de démarrer la session
    require_once __DIR__ . '/config/session_config.php';
    echo "2. session_config.php chargé<br>";
    
    // Inclure la configuration pour la gestion des sous-domaines
    require_once __DIR__ . '/config/subdomain_config.php';
    echo "3. subdomain_config.php chargé<br>";
    
    // Définir le chemin de base seulement s'il n'est pas déjà défini
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', __DIR__ . '/.');
    }
    echo "4. BASE_PATH défini: " . BASE_PATH . "<br>";
    
    // Inclure les fichiers de configuration et de connexion à la base de données
    require_once BASE_PATH . '/config/database.php';
    echo "5. database.php chargé<br>";
    
    require_once BASE_PATH . '/includes/functions.php';
    echo "6. functions.php chargé<br>";
    
    // Vérification de l'authentification GeekBoard
    if (!isset($_SESSION['shop_id'])) {
        echo "7. ERREUR: shop_id non défini dans la session<br>";
    } else {
        echo "7. shop_id trouvé: " . $_SESSION['shop_id'] . "<br>";
    }
    
    if (!isset($_SESSION['user_id'])) {
        echo "8. ERREUR: user_id non défini dans la session<br>";
    } else {
        echo "8. user_id trouvé: " . $_SESSION['user_id'] . "<br>";
    }
    
    // Test de connexion à la base de données
    $shop_pdo = getShopDBConnection();
    echo "9. Connexion à la base réussie<br>";
    
    // Test de requête simple
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "10. Nombre d'utilisateurs trouvés: " . $count . "<br>";
    
    echo "11. Test terminé avec succès !<br>";
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "<br>";
    echo "Fichier: " . $e->getFile() . "<br>";
    echo "Ligne: " . $e->getLine() . "<br>";
}
?> 