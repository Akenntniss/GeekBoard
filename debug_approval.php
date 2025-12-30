<?php
$host = 'localhost';
$dbname = 'geekboard_mdg';
$user = 'gb_mdg';
$pass = 'Admin123!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PLANNING CRÉÉ ===\n";
    $stmt = $pdo->query("
        SELECT es.*, u.username 
        FROM employee_schedules es
        LEFT JOIN users u ON es.user_id = u.id
        ORDER BY es.created_at DESC
        LIMIT 5
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | User: {$row['username']} ({$row['user_id']}) | Date: {$row['schedule_date']}\n";
        echo "  Horaires: {$row['start_time']} - {$row['end_time']}\n";
        echo "  Type: {$row['schedule_type']}\n";
        echo "  Notes: {$row['notes']}\n";
        echo "---\n";
    }
    
    echo "\n=== DERNIER POINTAGE ===\n";
    $stmt = $pdo->query("
        SELECT tt.*, u.username 
        FROM time_tracking tt
        LEFT JOIN users u ON tt.user_id = u.id
        ORDER BY tt.created_at DESC
        LIMIT 3
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | User: {$row['username']} ({$row['user_id']})\n";
        echo "  Clock In: {$row['clock_in']}\n";
        echo "  Clock Out: " . ($row['clock_out'] ?? 'En cours') . "\n";
        echo "  Auto-Approved: " . ($row['auto_approved'] ? 'OUI ✅' : 'NON ❌') . "\n";
        echo "  Admin-Approved: " . ($row['admin_approved'] ? 'OUI' : 'NON') . "\n";
        echo "  Raison: {$row['approval_reason']}\n";
        echo "---\n";
    }
    
    echo "\n=== VÉRIFICATION USER 'admin' ou 'Administrateur' ===\n";
    $stmt = $pdo->query("SELECT id, username, full_name FROM users WHERE username LIKE '%admin%' OR full_name LIKE '%admin%' LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Username: {$row['username']} | Full Name: {$row['full_name']}\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
