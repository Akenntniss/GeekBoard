<?php
// Test simple pour debug des campagnes SMS
require_once 'public_html/config/database.php';

// Initialiser la session shop
initializeShopSession();

echo "<h1>Debug Campagnes SMS</h1>";

try {
    $shop_pdo = getShopDBConnection();
    
    // Afficher la base de données actuelle
    $db_name = $shop_pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "<p><strong>Base de données actuelle:</strong> $db_name</p>";
    
    // Tester la requête campagnes
    $sql = "
        SELECT c.*, u.full_name as user_full_name
        FROM sms_campaigns c
        LEFT JOIN users u ON c.user_id = u.id
        ORDER BY c.date_envoi DESC
        LIMIT 10
    ";
    
    echo "<h2>Requête SQL:</h2>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    $stmt = $shop_pdo->query($sql);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Résultats (" . count($campaigns) . " campagnes trouvées):</h2>";
    
    if (empty($campaigns)) {
        echo "<p style='color: red;'>Aucune campagne trouvée!</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Date</th><th>Destinataires</th><th>Envoyés</th><th>Utilisateur</th></tr>";
        
        foreach ($campaigns as $campaign) {
            echo "<tr>";
            echo "<td>" . $campaign['id'] . "</td>";
            echo "<td>" . htmlspecialchars($campaign['nom']) . "</td>";
            echo "<td>" . $campaign['date_envoi'] . "</td>";
            echo "<td>" . $campaign['nb_destinataires'] . "</td>";
            echo "<td>" . $campaign['nb_envoyes'] . "</td>";
            echo "<td>" . ($campaign['user_full_name'] ? htmlspecialchars($campaign['user_full_name']) : 'Système') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Test des templates
    echo "<h2>Templates SMS disponibles:</h2>";
    $stmt = $shop_pdo->query("SELECT id, nom FROM sms_templates WHERE est_actif = 1");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($templates)) {
        echo "<p style='color: orange;'>Aucun template SMS trouvé</p>";
    } else {
        echo "<ul>";
        foreach ($templates as $template) {
            echo "<li>ID " . $template['id'] . ": " . htmlspecialchars($template['nom']) . "</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erreur:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Informations de session:</h2>";
echo "<pre>";
print_r([
    'shop_id' => $_SESSION['shop_id'] ?? 'Non défini',
    'user_id' => $_SESSION['user_id'] ?? 'Non défini',
    'role' => $_SESSION['role'] ?? 'Non défini'
]);
echo "</pre>";
?>
