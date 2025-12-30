<?php
/**
 * Script d'ajout de la colonne sms_api_key à la table shops
 * À exécuter une seule fois pour ajouter le support multi-API SMS
 */
require_once('../config/database.php');

try {
    $pdo = getMainDBConnection();
    
    // Vérifier si la colonne existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM shops LIKE 'sms_api_key'");
    
    if ($stmt->rowCount() > 0) {
        echo "✅ La colonne 'sms_api_key' existe déjà dans la table shops.<br>";
    } else {
        // Ajouter la colonne sms_api_key avec une valeur par défaut
        $sql = "ALTER TABLE shops ADD COLUMN sms_api_key VARCHAR(255) DEFAULT '1234' AFTER subdomain";
        $pdo->exec($sql);
        echo "✅ Colonne 'sms_api_key' ajoutée avec succès à la table shops.<br>";
        
        // Mettre à jour tous les magasins existants avec la clé par défaut
        $pdo->exec("UPDATE shops SET sms_api_key = '1234' WHERE sms_api_key IS NULL OR sms_api_key = ''");
        echo "✅ Tous les magasins existants ont été mis à jour avec la clé API par défaut '1234'.<br>";
    }
    
    echo "<br><strong>Migration terminée avec succès!</strong><br>";
    echo "<a href='api_manager.php'>→ Aller à la gestion des clés API SMS</a>";
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?>
