<?php
/**
 * API REST v2 - Vérification Token
 * GeekBoard Desktop Application
 * 
 * GET /api/v2/auth/verify
 * Header: Authorization: Bearer <token>
 */

require_once __DIR__ . '/../config.php';

// Vérifier l'authentification
$payload = require_auth();

// Token valide, retourner les informations
success_response([
    'user' => [
        'id' => $payload['user_id'],
        'email' => $payload['email'],
        'nom' => $payload['nom'] ?? '',
        'prenom' => $payload['prenom'] ?? '',
        'role' => $payload['role'] ?? 'user'
    ],
    'shop' => [
        'id' => $payload['shop_id'],
        'name' => $payload['shop_name'],
        'subdomain' => $payload['subdomain']
    ],
    'expires_at' => $payload['exp']
], 'Token valide');
?>
