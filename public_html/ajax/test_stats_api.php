<?php
/**
 * Test de l'API des statistiques
 */

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

try {
    // Obtenir la connexion à la base de données de la boutique
    $shop_pdo = getShopDBConnection();
    
    if (!isset($shop_pdo) || $shop_pdo === null) {
        throw new Exception('Erreur de connexion à la base de données');
    }
    
    // Test simple des statistiques
    $today = date('Y-m-d');
    
    // Test nouvelles réparations
    $stmt = $shop_pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reparations 
        WHERE DATE(date_reception) = ? AND statut_categorie = 1
    ");
    $stmt->execute([$today]);
    $nouvelles = $stmt->fetchColumn();
    
    // Test réparations effectuées
    $stmt = $shop_pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reparations 
        WHERE DATE(date_modification) = ? AND (statut = 'reparation_effectue' OR statut_categorie = 4)
    ");
    $stmt->execute([$today]);
    $effectuees = $stmt->fetchColumn();
    
    // Test réparations restituées
    $stmt = $shop_pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reparations 
        WHERE DATE(date_modification) = ? AND statut = 'restitue'
    ");
    $stmt->execute([$today]);
    $restituees = $stmt->fetchColumn();
    
    // Test devis envoyés
    $devis = 0;
    try {
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count 
            FROM devis 
            WHERE DATE(date_envoi) = ? AND statut = 'envoye'
        ");
        $stmt->execute([$today]);
        $devis = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Table devis n'existe peut-être pas
        $devis = 0;
    }
    
    echo json_encode([
        'success' => true,
        'date' => $today,
        'nouvelles_reparations' => (int) $nouvelles,
        'reparations_effectuees' => (int) $effectuees,
        'reparations_restituees' => (int) $restituees,
        'devis_envoyes' => (int) $devis,
        'database_connected' => true
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'database_connected' => false
    ]);
}
?>
