<?php
/**
 * Version simplifiée pour diagnostiquer l'erreur 500
 */

// Activer l'affichage des erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Headers de sécurité
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Démarrer la session
session_start();

// Debug: vérifier la session
$debug_session = [
    'session_id' => session_id(),
    'shop_id_exists' => isset($_SESSION['shop_id']),
    'shop_id_value' => $_SESSION['shop_id'] ?? null,
    'user_id_exists' => isset($_SESSION['user_id']),
    'user_id_value' => $_SESSION['user_id'] ?? null
];

// Vérifier l'authentification avec shop_id (système GeekBoard)
if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentification requise - Session shop_id manquante',
        'debug_session' => $debug_session
    ]);
    exit;
}

// Si on arrive ici, l'authentification est OK
echo json_encode([
    'success' => true,
    'message' => 'Authentification OK - Test simple réussi',
    'debug_session' => $debug_session,
    'method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
















