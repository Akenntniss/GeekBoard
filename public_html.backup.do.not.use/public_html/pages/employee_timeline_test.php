<?php
// Test simple pour vérifier l'authentification et la base de données
require_once('../config/database.php');

$shop_pdo = getShopDBConnection();

if (!isset($_SESSION['user_id'])) {
    echo "Erreur : Utilisateur non connecté";
    exit();
}

echo "Page employee_timeline accessible !<br>";
echo "Shop ID : " . ($_SESSION['shop_id'] ?? 'Non défini') . "<br>";
echo "User ID : " . ($_SESSION['user_id'] ?? 'Non défini') . "<br>";

// Test simple de récupération des employés
try {
    $employees_query = "SELECT id, full_name, username FROM users WHERE role IN ('admin', 'employee', 'technicien') ORDER BY full_name LIMIT 5";
    $employees_stmt = $shop_pdo->prepare($employees_query);
    $employees_stmt->execute();
    $employees = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Employés trouvés : " . count($employees) . "<br>";
    foreach ($employees as $emp) {
        echo "- " . ($emp['full_name'] ?: $emp['username']) . " (ID: " . $emp['id'] . ")<br>";
    }
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?>

<h1>Test de la page Employee Timeline</h1>
<p>Si vous voyez ce message, la page fonctionne correctement !</p>
<p><a href="?page=employee_timeline_test">Recharger</a> | <a href="?page=accueil">Retour à l'accueil</a></p>
