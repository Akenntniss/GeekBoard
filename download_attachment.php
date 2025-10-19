<?php
// Inclure la configuration de session
require_once __DIR__ . '/config/session_config.php';

// Inclusion de la configuration des sous-domaines pour la détection automatique du shop_id
require_once __DIR__ . '/config/subdomain_config.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Accès non autorisé';
    exit;
}

// Vérification si l'ID de la pièce jointe est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    echo 'ID de pièce jointe manquant';
    exit;
}

// Inclusion des fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';

// Nettoyage de l'ID
$attachment_id = (int)$_GET['id'];

try {
    // Utilisation de getShopDBConnection() pour la connexion dynamique
    $pdo = getShopDBConnection();
    
    // Récupération des détails de la pièce jointe
    $stmt = $pdo->prepare("
        SELECT ta.*, t.titre as tache_titre
        FROM tache_attachments ta
        JOIN taches t ON ta.tache_id = t.id
        WHERE ta.id = ?
    ");
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attachment) {
        header('HTTP/1.0 404 Not Found');
        echo 'Pièce jointe non trouvée';
        exit;
    }
    
    // Vérifier que le fichier existe
    $file_path = $attachment['file_path'];
    if (!file_exists($file_path)) {
        header('HTTP/1.0 404 Not Found');
        echo 'Fichier non trouvé sur le serveur';
        exit;
    }
    
    // Déterminer le type MIME
    $mime_type = 'application/octet-stream';
    $file_extension = strtolower(pathinfo($attachment['file_name'], PATHINFO_EXTENSION));
    
    $mime_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls' => 'application/vnd.ms-excel',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed'
    ];
    
    if (isset($mime_types[$file_extension])) {
        $mime_type = $mime_types[$file_extension];
    }
    
    // Définir les en-têtes pour le téléchargement
    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($file_path));
    header('Content-Disposition: attachment; filename="' . $attachment['file_name'] . '"');
    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
    header('Pragma: public');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    
    // Envoyer le fichier
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    error_log('Erreur dans download_attachment.php: ' . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Erreur lors du téléchargement du fichier';
    exit;
}
?>
