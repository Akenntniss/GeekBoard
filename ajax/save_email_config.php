<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

$shop_pdo = getShopDBConnection();

if (!$shop_pdo) {
    echo json_encode(['success' => false, 'message' => 'Impossible de se connecter à la base de données du magasin']);
    exit;
}

$email_keys = [
    'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_encryption',
    'imap_host', 'imap_port', 'imap_encryption', 'email_from_name',
    'email_notifications_enabled'
];

try {
    foreach ($email_keys as $key) {
        if ($key === 'email_notifications_enabled') {
            $valeur = isset($_POST[$key]) ? '1' : '0';
        } else {
            $valeur = $_POST[$key] ?? '';
        }
        
        // Vérifier si la clé existe déjà
        $stmt = $shop_pdo->prepare("SELECT id FROM parametres WHERE cle = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $stmt = $shop_pdo->prepare("UPDATE parametres SET valeur = ? WHERE cle = ?");
            $stmt->execute([$valeur, $key]);
        } else {
            // Insérer une description par défaut pour les nouvelles clés
            $description = "Paramètre email: " . str_replace('_', ' ', $key);
            $stmt = $shop_pdo->prepare("INSERT INTO parametres (cle, valeur, description) VALUES (?, ?, ?)");
            $stmt->execute([$key, $valeur, $description]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Configuration enregistrée avec succès']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur DB: ' . $e->getMessage()]);
}
