<?php
// Script de diagnostic ULTRA-SIMPLE - aucune dépendance
session_start();

header('Content-Type: text/plain');

echo "=== DIAGNOSTIC NOTIFICATIONS PUSH ===\n\n";

// 1. Session
echo "SESSION:\n";
echo "- Connecté: " . (isset($_SESSION['user_id']) ? "OUI (ID: {$_SESSION['user_id']})" : "NON") . "\n";
if (isset($_SESSION['user_id'])) {
    echo "- Nom: " . ($_SESSION['full_name'] ?? 'Inconnu') . "\n";
    echo "- Database: " . ($_SESSION['current_database'] ?? 'Non définie') . "\n";
}
echo "\n";

// 2. Connexion DB manuelle
if (isset($_SESSION['current_database'])) {
    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname={$_SESSION['current_database']};charset=utf8mb4",
            "root",
            "OP,koM3#"
        );
        echo "DATABASE:\n";
        echo "- Connexion: OK\n";
        
        // Compter abonnements
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $count = $stmt->fetchColumn();
        echo "- Vos abonnements: $count\n";
        
        $total = $pdo->query("SELECT COUNT(*) FROM push_subscriptions")->fetchColumn();
        echo "- Total abonnements: $total\n";
        
        // Détails de vos abonnements
        if ($count > 0) {
            $stmt = $pdo->prepare("SELECT endpoint, created_at FROM push_subscriptions WHERE user_id = ? LIMIT 3");
            $stmt->execute([$_SESSION['user_id']]);
            echo "\nDétails abonnements:\n";
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $sub) {
                $endpoint_short = substr($sub['endpoint'], -30);
                echo "  - ...{$endpoint_short} (créé: {$sub['created_at']})\n";
            }
        }
        
    } catch (Exception $e) {
        echo "DATABASE: ERREUR\n";
        echo "- Message: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// 3. WebPush
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
echo "WEBPUSH:\n";
echo "- Autoload existe: " . (file_exists($autoload) ? "OUI" : "NON") . "\n";
if (file_exists($autoload)) {
    require_once $autoload;
    echo "- Classe WebPush: " . (class_exists('Minishlink\WebPush\WebPush') ? "OUI" : "NON") . "\n";
}
echo "\n";

// 4. Test d'envoi
if (isset($_GET['send']) && isset($_SESSION['user_id']) && isset($pdo)) {
    echo "TEST D'ENVOI:\n";
    try {
        require_once dirname(__DIR__) . '/includes/PushNotifications.php';
        $pushService = new PushNotifications($pdo);
        $result = $pushService->sendToUser(
            $_SESSION['user_id'],
            "🔔 Test",
            "Envoyé à " . date('H:i:s'),
            ['url' => '/', 'tag' => 'test']
        );
        echo "- Résultat: " . ($result['success'] ? "SUCCÈS" : "ÉCHEC") . "\n";
        echo "- Message: " . $result['message'] . "\n";
        if (isset($result['details'])) {
            echo "- Détails: " . print_r($result['details'], true) . "\n";
        }
    } catch (Exception $e) {
        echo "- ERREUR: " . $e->getMessage() . "\n";
    }
}

echo "\n=== FIN DIAGNOSTIC ===\n";
