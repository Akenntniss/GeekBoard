<?php
/**
 * Script de migration pour les signatures de messages
 * Doit être appelé via HTTP avec le bon sous-domaine pour cibler la bonne base de données.
 */

// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Initialiser la session shop (obligatoire pour getShopDBConnection)
if (function_exists('initializeShopSession')) {
    if (!initializeShopSession()) {
        die("Erreur: Impossible d'initialiser la session shop. Vérifiez le sous-domaine.");
    }
}

// Obtenir la connexion
$pdo = getShopDBConnection();

if (!$pdo) {
    die("Erreur: Impossible de se connecter à la base de données du magasin.");
}

echo "Connexion réussie à la base de données.\n";

// 1. Ajouter requires_signature à la table messages
try {
    $sql = "SHOW COLUMNS FROM messages LIKE 'requires_signature'";
    $stmt = $pdo->query($sql);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN requires_signature BOOLEAN DEFAULT 0");
        echo "Colonne 'requires_signature' ajoutée à la table 'messages'.\n";
    } else {
        echo "Colonne 'requires_signature' existe déjà dans 'messages'.\n";
    }
} catch (PDOException $e) {
    echo "Erreur lors de la modification de la table messages: " . $e->getMessage() . "\n";
}

// 2. Créer la table message_signatures
try {
    $sql = "CREATE TABLE IF NOT EXISTS message_signatures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        user_id INT NOT NULL,
        signed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        signature_data TEXT,
        ip_address VARCHAR(45),
        INDEX (message_id),
        INDEX (user_id),
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_signature (message_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "Table 'message_signatures' créée ou existe déjà.\n";
} catch (PDOException $e) {
    echo "Erreur lors de la création de message_signatures: " . $e->getMessage() . "\n";
}

echo "Migration terminée avec succès.";
?>
