<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/NotificationService.php';

// Force shop context to Mdg (ID 162)
$_SESSION['shop_id'] = 162;
$pdo = getShopDBConnectionById(162);

if (!$pdo) {
    die("Error: Could not connect to Mdg shop database.\n");
}

echo "Connected to Mdg shop database.\n";

// 1. Check if email notifications are enabled
$stmt = $pdo->prepare("SELECT valeur FROM parametres WHERE cle = 'email_notifications_enabled'");
$stmt->execute();
$enabled = $stmt->fetchColumn();
echo "Email notifications globally enabled: " . ($enabled === '1' ? 'YES' : 'NO') . "\n";

// 2. Check if we have valid-looking SMTP settings
$keys = ['smtp_host', 'smtp_user', 'smtp_port'];
foreach ($keys as $key) {
    $stmt = $pdo->prepare("SELECT valeur FROM parametres WHERE cle = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    echo "Param $key: " . ($val ? $val : 'MISSING') . "\n";
}

// 3. Test send for a specific user (using user ID 1 for test, or current logged in if exists)
$test_user_id = 1; // Change this to a valid user ID if needed
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$test_user_id]);
$email = $stmt->fetchColumn();

if (!$email) {
    echo "Warning: No email found for user ID $test_user_id. Trying to find any user with an email...\n";
    $stmt = $pdo->query("SELECT id, email FROM users WHERE email IS NOT NULL AND email != '' LIMIT 1");
    $any_user = $stmt->fetch();
    if ($any_user) {
        $test_user_id = $any_user['id'];
        $email = $any_user['email'];
        echo "Found user ID $test_user_id with email $email\n";
    } else {
        die("Error: No users with emails found in the database.\n");
    }
}

echo "Testing notification for user ID $test_user_id ($email)...\n";

// 4. Temporarily force email preference for this user and type 'test'
// But first ensure 'reparation_start' or similar exists
$type = 'reparation_start';
$stmt = $pdo->prepare("REPLACE INTO notification_preferences (user_id, type_notification, active, email_notification, push_notification) VALUES (?, ?, 1, 1, 0)");
$stmt->execute([$test_user_id, $type]);

echo "Forced email preference for $type to TRUE for user $test_user_id\n";

// 5. Trigger notification
$success = NotificationService::send($test_user_id, $type, "Test Notification Email", "Ceci est un test du nouveau système de notification par email. Si vous recevez ceci, tout fonctionne !");

if ($success) {
    echo "NotificationService::send returned SUCCESS. Check logs or inbox.\n";
} else {
    echo "NotificationService::send returned FAILURE.\n";
}
?>
