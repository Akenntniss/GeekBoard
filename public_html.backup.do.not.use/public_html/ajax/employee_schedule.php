<?php
session_start();

// Vérification des droits
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo '<div class="alert alert-danger">Accès refusé</div>';
    exit;
}

require_once __DIR__ . '/../config/database.php';

$employee_id = $_GET['id'] ?? 0;

if (!$employee_id) {
    echo '<div class="alert alert-warning">ID d\'employé manquant</div>';
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    // Récupérer les horaires de l'employé
    $stmt = $shop_pdo->prepare("
        SELECT * FROM employee_schedules 
        WHERE employee_id = ? 
        AND (effective_to IS NULL OR effective_to >= CURDATE())
        ORDER BY day_of_week
    ");
    $stmt->execute([$employee_id]);
    $schedules = $stmt->fetchAll();
    
    // Récupérer le nom de l'employé
    $stmt = $shop_pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee_name = $stmt->fetchColumn();

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

$days = [
    1 => 'Lundi',
    2 => 'Mardi', 
    3 => 'Mercredi',
    4 => 'Jeudi',
    5 => 'Vendredi',
    6 => 'Samedi',
    7 => 'Dimanche'
];

if (empty($schedules)) {
    echo '<div class="alert alert-info">';
    echo '<h6><i class="fas fa-info-circle me-2"></i>Aucun horaire défini</h6>';
    echo '<p class="mb-0">Aucun horaire de travail n\'est défini pour ' . htmlspecialchars($employee_name) . '.</p>';
    echo '<p class="mb-0">L\'horaire par défaut (8h00-17h00) sera utilisé pour le calcul des retards.</p>';
    echo '</div>';
} else {
    echo '<h6><i class="fas fa-clock me-2"></i>Horaires de ' . htmlspecialchars($employee_name) . '</h6>';
    echo '<div class="table-responsive">';
    echo '<table class="table table-sm">';
    echo '<thead><tr><th>Jour</th><th>Horaires</th><th>Pause</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($schedules as $schedule) {
        $day_name = $days[$schedule['day_of_week']] ?? 'Jour ' . $schedule['day_of_week'];
        
        echo '<tr>';
        echo '<td><strong>' . $day_name . '</strong></td>';
        
        if ($schedule['is_working_day']) {
            echo '<td>';
            echo date('H:i', strtotime($schedule['start_time']));
            echo ' - ';
            echo date('H:i', strtotime($schedule['end_time']));
            echo '</td>';
            
            echo '<td>';
            if ($schedule['break_start_time'] && $schedule['break_end_time']) {
                echo date('H:i', strtotime($schedule['break_start_time']));
                echo ' - ';
                echo date('H:i', strtotime($schedule['break_end_time']));
            } else {
                echo '<span class="text-muted">-</span>';
            }
            echo '</td>';
        } else {
            echo '<td colspan="2"><span class="text-muted">Jour non travaillé</span></td>';
        }
        
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    // Afficher la période d'effectivité
    $effective_from = min(array_column($schedules, 'effective_from'));
    $effective_to = max(array_filter(array_column($schedules, 'effective_to')));
    
    echo '<small class="text-muted">';
    echo '<i class="fas fa-calendar me-1"></i>';
    echo 'Effectif depuis le ' . date('d/m/Y', strtotime($effective_from));
    if ($effective_to) {
        echo ' jusqu\'au ' . date('d/m/Y', strtotime($effective_to));
    }
    echo '</small>';
}
?>







