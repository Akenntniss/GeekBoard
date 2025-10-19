<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de la configuration des sous-domaines pour la détection automatique du magasin
require_once __DIR__ . '/../config/subdomain_config.php';

// Vérifier l'accès au magasin (pas besoin d'utilisateur connecté pour cette page)
if (!isset($_SESSION['shop_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo 'Accès non autorisé - Magasin non détecté';
    exit();
}

// Vérifier les paramètres
if (!isset($_GET['file']) || !isset($_GET['name'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Paramètres manquants';
    exit();
}

$filename = $_GET['file'];
$downloadName = $_GET['name'];

// Sécurité : vérifier que le fichier est dans le dossier temporaire
$tempDir = sys_get_temp_dir();
$filepath = $tempDir . '/' . $filename;

// Vérifier que le fichier existe et est dans le bon dossier
if (!file_exists($filepath) || !is_readable($filepath)) {
    header('HTTP/1.1 404 Not Found');
    echo 'Fichier non trouvé';
    exit();
}

// Vérifier que le fichier est bien dans un dossier temporaire de rachat
if (strpos($filepath, 'rachat_exports_') === false) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Accès interdit';
    exit();
}

// Déterminer le type MIME
$mimeType = 'application/octet-stream';
$extension = pathinfo($downloadName, PATHINFO_EXTENSION);

switch (strtolower($extension)) {
    case 'pdf':
        $mimeType = 'application/pdf';
        break;
    case 'zip':
        $mimeType = 'application/zip';
        break;
    case 'html':
        $mimeType = 'text/html';
        break;
}

// Préparer les headers pour le téléchargement
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Lire et envoyer le fichier
readfile($filepath);

// Nettoyer le fichier temporaire après téléchargement
// (optionnel - les fichiers temporaires sont normalement nettoyés automatiquement)
// unlink($filepath);

exit();
?> 