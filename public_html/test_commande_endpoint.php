<?php
session_start();
header('Content-Type: application/json');

// Simuler une session pour zebilamcj
$_SESSION['shop_id'] = 150; // ID pour zebilamcj

// Simuler des données POST pour test
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Test avec données simulées
    $_POST = [
        'client_id' => '1',
        'fournisseur_id' => '1', 
        'nom_piece' => 'Test Pièce ' . date('H:i:s'),
        'quantite' => '1',
        'prix_estime' => '99.99',
        'code_barre' => 'TEST' . time(),
        'statut' => 'en_attente'
    ];
    $_SERVER['REQUEST_METHOD'] = 'POST';
}

echo "<h1>Test Endpoint Commande</h1>";
echo "<p><strong>Données POST simulées :</strong></p>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<p><strong>Session :</strong></p>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<p><strong>Appel de l'endpoint :</strong></p>";
echo "<iframe src='ajax/simple_commande_no_user.php' width='100%' height='400'></iframe>";

// Test direct
echo "<h2>Test Direct</h2>";

// Initialiser la connexion à la base de données
require_once __DIR__ . '/public_html/config/database.php';

try {
    $shop_pdo = getShopDBConnection();
    
    if ($shop_pdo) {
        $db_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
        $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Base de données connectée : <strong>" . ($db_info['current_db'] ?? 'Inconnue') . "</strong></p>";
        
        // Compter les commandes
        $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM commandes_pieces WHERE DATE(date_creation) = CURDATE()");
        $today_count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Commandes créées aujourd'hui : <strong>" . ($today_count['count'] ?? 0) . "</strong></p>";
        
        // Dernières commandes
        $stmt = $shop_pdo->query("SELECT id, reference, nom_piece, date_creation FROM commandes_pieces ORDER BY date_creation DESC LIMIT 5");
        $recent_commands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p><strong>5 dernières commandes :</strong></p>";
        echo "<pre>" . print_r($recent_commands, true) . "</pre>";
        
    } else {
        echo "<p style='color: red;'>Erreur : Impossible de se connecter à la base de données</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception : " . $e->getMessage() . "</p>";
}
?>
