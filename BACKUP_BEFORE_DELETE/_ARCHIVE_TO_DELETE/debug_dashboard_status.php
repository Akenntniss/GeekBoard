<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

try {
    $pdo = getShopDBConnection();
    
    echo "<h1>Debug Dashboard Status</h1>";
    
    // 1. Check current session
    session_start();
    echo "<h2>Session</h2>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    $current_user_id = $_SESSION['user_id'] ?? 0;
    
    // 2. Check User Data
    echo "<h2>User Data (ID: $current_user_id)</h2>";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    // 3. Check Time Tracking for this user
    echo "<h2>Time Tracking (Active)</h2>";
    $stmt = $pdo->prepare("SELECT * FROM time_tracking WHERE user_id = ? AND clock_out IS NULL");
    $stmt->execute([$current_user_id]);
    $tt = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($tt);
    echo "</pre>";
    
    // 4. Test the Dashboard Query
    echo "<h2>Dashboard Query Test</h2>";
    $sql = "
            SELECT 
                u.id as user_id,
                u.full_name,
                u.role,
                u.active_repair_id,
                tt.clock_in as clock_in_time,
                r.id as reparation_id,
                r.appareil as model,
                r.description_probleme as probleme,
                r.date_reception,
                c.nom as client_nom,
                c.prenom as client_prenom
            FROM users u
            LEFT JOIN time_tracking tt ON u.id = tt.user_id AND tt.clock_out IS NULL
            LEFT JOIN reparations r ON u.active_repair_id = r.id
            LEFT JOIN clients c ON r.client_id = c.id
            WHERE u.role IN ('admin', 'technicien', 'reparateur') 
               OR (u.role != 'client' AND tt.id IS NOT NULL)
            ORDER BY 
                CASE WHEN tt.id IS NOT NULL THEN 0 ELSE 1 END,
                u.full_name
    ";
    
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($results);
    echo "</pre>";
    
    // 5. Check all users roles
    echo "<h2>All Users Roles</h2>";
    $stmt = $pdo->query("SELECT id, username, role, full_name FROM users");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
