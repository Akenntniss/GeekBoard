<?php
require_once 'config/database.php';

try {
    $pdo = getMainDBConnection();
    $sql = file_get_contents('create_token_table.sql');
    $pdo->exec($sql);
    echo "Table 'subscription_sessions' créée avec succès.\n";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
?>
