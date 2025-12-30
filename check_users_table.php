<?php
// Script temporaire pour examiner la structure de la table users
require_once __DIR__ . '/config/database.php';

try {
    // Utiliser getShopDBConnection() comme recommandé
    $pdo = getShopDBConnection();
    
    // Décrire la table users
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Structure de la table users ===\n\n";
    foreach ($columns as $col) {
        echo sprintf("%-20s %-20s %-10s %-10s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key']
        );
    }
    
    echo "\n=== Recherche de colonnes liées au statut ===\n\n";
    foreach ($columns as $col) {
        if (stripos($col['Field'], 'status') !== false || 
            stripos($col['Field'], 'online') !== false ||
            stripos($col['Field'], 'available') !== false) {
            echo "Trouvé: {$col['Field']} ({$col['Type']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?>
