<?php
require_once __DIR__ . '/config/database.php';

try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->query("SELECT id, nom, prenom, telephone FROM clients WHERE nom LIKE '%magasin%' OR nom LIKE '%atelier%' OR prenom LIKE '%magasin%' OR prenom LIKE '%atelier%'");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Clients trouvés:\n";
    print_r($clients);
    
    // Also check structure of commandes_pieces table
    $stmt2 = $shop_pdo->query("DESCRIBE commandes_pieces");
    $structure = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n\nStructure de commandes_pieces:\n";
    print_r($structure);
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
