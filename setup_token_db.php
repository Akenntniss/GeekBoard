<?php
// setup_token_db.php
require_once 'config/database.php';

echo "<pre>";
try {
    $pdo = getMainDBConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS subscription_sessions (
        token VARCHAR(64) PRIMARY KEY,
        shop_id INT NOT NULL,
        user_id INT NOT NULL,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
    );";
    
    $pdo->exec($sql);
    echo "SUCCESS: Table 'subscription_sessions' créée ou déjà existante.\n";
    
    // Ajout index séparément au cas où
    try {
        $pdo->exec("CREATE INDEX idx_expires_at ON subscription_sessions(expires_at)");
        echo "Index créé.\n";
    } catch (Exception $e) {
        echo "Index déjà existant ou erreur mineure: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>
