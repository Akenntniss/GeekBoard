<?php
session_start();
header('Content-Type: application/json');

$debug = [
    'session_id' => session_id(),
    'session_status' => session_status(),
    'session_data' => $_SESSION ?? [],
    'cookies' => $_COOKIE ?? [],
    'server_vars' => [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
        'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null
    ]
];

echo json_encode($debug, JSON_PRETTY_PRINT);
?>