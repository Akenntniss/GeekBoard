<?php
// Désactiver l'affichage des erreurs pour ne pas polluer le JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Sessions et sous-domaine
require_once '../config/session_config.php';
require_once '../config/subdomain_config.php';

header('Content-Type: application/json');

require_once '../config/database.php';

// Vérifier utilisateur connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

try {
    $pdo = getShopDBConnection();
    if ($pdo === null) {
        echo json_encode(['success' => false, 'message' => 'Connexion base magasin indisponible']);
        exit;
    }

    // Créer table de préférences utilisateur si absente (au niveau base magasin)
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        preference_key VARCHAR(100) NOT NULL,
        preference_value TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_pref (user_id, preference_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $userId = (int)$_SESSION['user_id'];
    $action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'get');

    if ($action === 'set') {
        // Lecture données
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $deviceId = isset($data['deviceId']) ? trim($data['deviceId']) : '';
        $label = isset($data['label']) ? trim($data['label']) : '';
        $facingMode = isset($data['facingMode']) ? trim($data['facingMode']) : '';

        $payload = json_encode([
            'deviceId' => $deviceId,
            'label' => $label,
            'facingMode' => $facingMode,
        ], JSON_UNESCAPED_UNICODE);

        $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id, preference_key, preference_value)
                                VALUES (?, 'camera_device', ?)
                                ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([$userId, $payload]);

        echo json_encode(['success' => true]);
        exit;
    }

    // action=get (par défaut)
    $stmt = $pdo->prepare("SELECT preference_value FROM user_preferences WHERE user_id = ? AND preference_key = 'camera_device' LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $value = null;
    if ($row && !empty($row['preference_value'])) {
        $decoded = json_decode($row['preference_value'], true);
        if (is_array($decoded)) {
            $value = $decoded;
        }
    }

    echo json_encode(['success' => true, 'preference' => $value]);
} catch (Throwable $e) {
    error_log('camera_preference.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>


