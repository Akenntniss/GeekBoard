<?php
// Upload de fichiers pour la base de connaissances
header('Content-Type: application/json');

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/subdomain_config.php';

// Vérifier que l'utilisateur est connecté et a les droits
if (!isset($_SESSION['user_id']) || 
    (!isset($_SESSION['role']) && !isset($_SESSION['user_role'])) || 
    (
        (isset($_SESSION['role']) && !in_array($_SESSION['role'], ['admin', 'manager'])) &&
        (isset($_SESSION['user_role']) && !in_array($_SESSION['user_role'], ['admin', 'manager']))
    )) {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit;
}

try {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Aucun fichier reçu ou erreur d\'upload');
    }

    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmpName = $file['tmp_name'];
    $fileType = $file['type'];

    // Vérifications de sécurité
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($fileSize > $maxSize) {
        throw new Exception('Fichier trop volumineux (max 10MB)');
    }

    // Extensions autorisées
    $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', // Images
        'pdf', // PDF
        'doc', 'docx', // Word
        'txt', // Texte
        'mp4', 'avi', 'mov', // Vidéos
        'zip', 'rar' // Archives
    ];

    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Type de fichier non autorisé');
    }

    // Vérification du type MIME pour sécurité supplémentaire
    $allowedMimeTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'video/mp4', 'video/x-msvideo', 'video/quicktime',
        'application/zip', 'application/x-rar-compressed'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmpName);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimeTypes)) {
        throw new Exception('Type MIME non autorisé: ' . $mimeType);
    }

    // Créer le dossier de destination s'il n'existe pas
    $uploadDir = __DIR__ . '/../uploads/kb/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Impossible de créer le dossier d\'upload');
        }
    }

    // Générer un nom de fichier unique
    $uniqueFileName = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $uniqueFileName;

    // Déplacer le fichier
    if (!move_uploaded_file($fileTmpName, $uploadPath)) {
        throw new Exception('Erreur lors du déplacement du fichier');
    }

    // URL publique du fichier
    $fileUrl = '/uploads/kb/' . $uniqueFileName;

    // Enregistrer dans la base de données (optionnel)
    try {
        $shop_pdo = getShopDBConnection();
        $stmt = $shop_pdo->prepare("
            INSERT INTO kb_files (filename, original_name, file_path, file_size, mime_type, uploaded_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $uniqueFileName,
            $fileName,
            $fileUrl,
            $fileSize,
            $mimeType,
            $_SESSION['user_id']
        ]);
    } catch (Exception $e) {
        // Si la table n'existe pas, on continue sans erreur
        error_log("Erreur lors de l'enregistrement du fichier en BDD: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'url' => $fileUrl,
        'filename' => $fileName,
        'size' => $fileSize,
        'type' => $mimeType
    ]);

} catch (Exception $e) {
    error_log("Erreur upload KB: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
