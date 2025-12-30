<?php
header('Content-Type: application/json');

// Capture errors to JSON instead of blank 500
error_reporting(E_ALL);
ini_set('display_errors', 0);
set_error_handler(function($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo json_encode(['success' => false, 'message' => 'Erreur fatale: ' . $e['message']]);
    }
});

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/subdomain_config.php';

function json_fail($msg) {
    echo json_encode([ 'success' => false, 'message' => $msg ]);
    exit;
}

try {
    if (function_exists('initializeShopSession')) {
        initializeShopSession();
    }
    $pdo = getShopDBConnection();
    if (!$pdo) {
        json_fail('Connexion base de données introuvable.');
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($id <= 0) json_fail('ID invalide.');
    if ($username === '' || $full_name === '') json_fail("Champs obligatoires manquants.");
    if (!in_array($role, ['admin','technicien'])) json_fail('Rôle invalide.');

    // Vérifier doublon username (autre utilisateur)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
    $stmt->execute([$username, $id]);
    if ($stmt->fetchColumn() > 0) {
        json_fail("Ce nom d'utilisateur est déjà utilisé.");
    }

    if ($password !== '') {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET username = ?, password = ?, full_name = ?, role = ? WHERE id = ?');
        $stmt->execute([$username, $hashed, $full_name, $role, $id]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET username = ?, full_name = ?, role = ? WHERE id = ?');
        $stmt->execute([$username, $full_name, $role, $id]);
    }

    echo json_encode([ 'success' => true ]);
} catch (Throwable $e) {
    json_fail('Erreur: ' . $e->getMessage());
}

