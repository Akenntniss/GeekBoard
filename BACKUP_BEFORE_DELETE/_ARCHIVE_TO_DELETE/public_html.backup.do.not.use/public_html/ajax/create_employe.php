<?php
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
set_error_handler(function($sev,$msg,$file,$line){ throw new ErrorException($msg,0,$sev,$file,$line); });
register_shutdown_function(function(){ $e=error_get_last(); if($e && in_array($e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR])) echo json_encode(['success'=>false,'message'=>'Erreur fatale: '.$e['message']]); });

require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/subdomain_config.php';

function fail($m){ echo json_encode(['success'=>false,'message'=>$m]); exit; }

try {
    if (function_exists('initializeShopSession')) { initializeShopSession(); }
    $pdo = getShopDBConnection();
    if (!$pdo) fail('Connexion base indisponible.');

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';

    if ($username === '' || $password === '' || $full_name === '') fail('Champs obligatoires manquants.');
    if (!in_array($role, ['admin','technicien'])) fail('Rôle invalide.');

    // Unicité username
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) fail("Ce nom d'utilisateur existe déjà.");

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username,password,full_name,role,created_at) VALUES (?,?,?,?,NOW())');
    $stmt->execute([$username,$hashed,$full_name,$role]);

    echo json_encode(['success'=>true]);
} catch (Throwable $e) {
    fail('Erreur: '.$e->getMessage());
}


