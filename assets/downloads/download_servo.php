<?php
// Script pour forcer le téléchargement avec le bon nom de fichier
// Empêche le navigateur de renommer le fichier avec un UUID

// Chemin vers le fichier ZIP (dans le même dossier que ce script)
$file = 'servo-extension.zip';
$filepath = __DIR__ . '/' . $file;

// Vérifier si le fichier existe
if (file_exists($filepath)) {
    // Vider le tampon de sortie pour éviter tout caractère parasite
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Définir les en-têtes pour forcer le téléchargement
    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="servo-extension.zip"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filepath));
    
    // Lire le fichier et l'envoyer vers la sortie
    readfile($filepath);
    exit;
} else {
    // Si le fichier n'existe pas, erreur 404
    header("HTTP/1.0 404 Not Found");
    echo "Fichier extension introuvable.";
    exit;
}
?>
