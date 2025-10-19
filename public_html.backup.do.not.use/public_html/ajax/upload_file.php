<?php
// Script de téléchargement de fichiers pour les articles KB
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier que l'utilisateur est connecté et a les droits
if (!isset($_SESSION['user_id']) || 
    (!isset($_SESSION['role']) && !isset($_SESSION['user_role'])) || 
    (
        (isset($_SESSION['role']) && !in_array($_SESSION['role'], ['admin', 'manager'])) &&
        (isset($_SESSION['user_role']) && !in_array($_SESSION['user_role'], ['admin', 'manager']))
    )) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

// Configuration
$upload_dir = BASE_PATH . '/uploads/kb_files/';
$web_path = '/uploads/kb_files/';
$max_file_size = 10 * 1024 * 1024; // 10MB
$allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov'];

// Créer le dossier d'upload s'il n'existe pas
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Traitement de l'upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Vérifications de base
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Erreur lors du téléchargement: ' . $file['error']]);
        exit;
    }
    
    if ($file['size'] > $max_file_size) {
        echo json_encode(['error' => 'Le fichier est trop volumineux (max 10MB)']);
        exit;
    }
    
    // Vérifier l'extension
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);
    
    if (!in_array($extension, $allowed_extensions)) {
        echo json_encode(['error' => 'Type de fichier non autorisé']);
        exit;
    }
    
    // Générer un nom de fichier unique
    $filename = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
    $filepath = $upload_dir . $filename;
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Enregistrer en base de données (optionnel)
        try {
            $shop_pdo = getShopDBConnection();
            $stmt = $shop_pdo->prepare("INSERT INTO kb_files (filename, original_name, file_path, file_size, file_type, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $filename,
                $file['name'],
                $web_path . $filename,
                $file['size'],
                $file['type'],
                $_SESSION['user_id']
            ]);
            $file_id = $shop_pdo->lastInsertId();
        } catch (PDOException $e) {
            // Si la table n'existe pas, on continue sans enregistrer en base
            error_log("Erreur insertion fichier KB: " . $e->getMessage());
            $file_id = null;
        }
        
        // Retourner les informations du fichier
        echo json_encode([
            'success' => true,
            'filename' => $filename,
            'original_name' => $file['name'],
            'url' => $web_path . $filename,
            'size' => formatBytes($file['size']),
            'file_id' => $file_id,
            'html' => '<a href="' . $web_path . $filename . '" download="' . htmlspecialchars($file['name']) . '" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Télécharger ' . htmlspecialchars($file['name']) . '</a>'
        ]);
    } else {
        echo json_encode(['error' => 'Erreur lors du déplacement du fichier']);
    }
} else {
    echo json_encode(['error' => 'Aucun fichier reçu']);
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}
?>