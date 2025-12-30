<?php
/**
 * Test simple de session - pour debug
 */

require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/subdomain_config.php';

header('Content-Type: application/json');

echo json_encode([
    'session_exists' => session_status() === PHP_SESSION_ACTIVE,
    'user_id' => $_SESSION['user_id'] ?? $_SESSION['id'] ?? null,
    'user_role' => $_SESSION['role'] ?? $_SESSION['user_role'] ?? null,
    'session_data' => array_keys($_SESSION ?? [])
]);