<?php
session_start();
// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Démarrer le buffer de sortie pour capturer les sorties indésirables
ob_start();

try {
    // Utiliser le système de configuration multi-magasin
    $config_path = realpath(__DIR__ . '/../config/database.php');
    
    if (!file_exists($config_path)) {
        throw new Exception('Fichier de configuration introuvable');
    }
    
    require_once $config_path;

    // Initialiser la session du magasin
    initializeShopSession();

    // Obtenir la connexion à la base de données du magasin de l'utilisateur
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }

    // Requête pour récupérer tous les utilisateurs
    $query = "
        SELECT 
            id,
            username,
            full_name,
            role
        FROM users 
        ORDER BY full_name, username
    ";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute();
    
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Nettoyer le buffer avant d'envoyer la réponse JSON
    ob_clean();
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => true,
        'technicians' => $technicians,
        'count' => count($technicians)
    ]);

} catch (Exception $e) {
    // Nettoyer le buffer en cas d'erreur
    ob_clean();
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des techniciens : ' . $e->getMessage()
    ]);
}
?>
