<?php
// debug_stripe_status.php
require_once 'config/database.php';

try {
    $pdo = getMainDBConnection();
    
    // Récupérer le dernier shop accédé ou tous les shops
    // On va lister les shops et leurs infos d'abonnement
    $stmt = $pdo->query("
        SELECT s.id, s.name, s.subdomain, sub.status, sub.stripe_customer_id, sub.stripe_subscription_id 
        FROM shops s
        LEFT JOIN subscriptions sub ON s.id = sub.shop_id
    ");
    
    echo "<h1>État des Abonnements</h1>";
    echo "<table border='1' cellpadding='5'>
            <tr>
                <th>Shop ID</th>
                <th>Nom</th>
                <th>Sous-domaine</th>
                <th>Statut Abo</th>
                <th>Stripe Customer ID</th>
                <th>Stripe Sub ID</th>
            </tr>";
            
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['subdomain']}</td>";
        echo "<td>" . ($row['status'] ?? 'Aucun') . "</td>";
        echo "<td>" . ($row['stripe_customer_id'] ?? '<span style="color:red">MANQUANT</span>') . "</td>";
        echo "<td>" . ($row['stripe_subscription_id'] ?? ' - ') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
