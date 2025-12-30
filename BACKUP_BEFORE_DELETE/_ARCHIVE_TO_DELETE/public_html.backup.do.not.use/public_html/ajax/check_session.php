<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive',
    'session_id' => session_id(),
    'user_role' => $_SESSION['user_role'] ?? 'non défini',
    'user_id' => $_SESSION['user_id'] ?? 'non défini',
    'shop_id' => $_SESSION['shop_id'] ?? 'non défini',
    'is_logged_in' => $_SESSION['is_logged_in'] ?? false,
    'full_name' => $_SESSION['full_name'] ?? 'non défini',
    'all_session_vars' => array_keys($_SESSION)
]);
?>