<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_config.php';

// Simuler une session admin
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1; // ID admin

// Paramètres de test (à ajuster selon les données réelles)
$user_id = 6; // ID de l'employé testé (vu dans les logs précédents)
$date = date('Y-m-d');

// URL de l'API
$url = "http://localhost/ajax/get_employee_daily_activity.php?user_id=$user_id&date=$date";

// Exécuter la logique directement pour voir les données brutes
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->prepare("
        SELECT 
            rl.id,
            r.modele as repair_model,
            r.description_probleme as repair_problem,
            c.nom as client_nom
        FROM reparation_logs rl
        LEFT JOIN reparations r ON rl.reparation_id = r.id
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE rl.employe_id = ? 
          AND DATE(rl.date_action) = ?
        ORDER BY rl.date_action DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id, $date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Données brutes pour user $user_id le $date:\n";
    print_r($result);
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
