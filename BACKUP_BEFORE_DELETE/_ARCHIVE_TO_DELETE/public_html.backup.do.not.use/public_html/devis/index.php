<?php
/**
 * ================================================================================
 * GESTIONNAIRE DEVIS PUBLIC - Sans authentification
 * ================================================================================
 */

// Récupérer le lien sécurisé depuis l'URL
$lien_securise = $_GET['lien'] ?? '';

// Si pas de lien fourni, essayer de l'extraire depuis l'URL
if (empty($lien_securise)) {
    $uri = $_SERVER['REQUEST_URI'];
    // Extraire le hash MD5 depuis l'URL comme /devis/[hash]
    if (preg_match('/\/devis\/([a-f0-9]{32})\/?/', $uri, $matches)) {
        $lien_securise = $matches[1];
    }
}

if (empty($lien_securise)) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><title>Erreur</title></head><body><h1>Lien de devis manquant</h1></body></html>";
    exit;
}

// Nettoyer le lien sécurisé
$lien_securise = preg_replace('/[^a-zA-Z0-9]/', '', $lien_securise);

// Rediriger vers la vraie page devis_client.php
header('Location: /pages/devis_client.php?lien=' . $lien_securise);
exit;
?>
