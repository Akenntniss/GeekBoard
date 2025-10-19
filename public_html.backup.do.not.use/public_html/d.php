<?php
/**
 * ================================================================================
 * GESTIONNAIRE DEVIS PUBLIC - URL Courte d.php?[hash]
 * ================================================================================
 */

// Récupérer le lien sécurisé depuis l'URL (premier paramètre GET quelque soit son nom)
$lien_securise = '';

// Récupérer le premier paramètre GET (peu importe son nom)
if (!empty($_GET)) {
    $lien_securise = array_keys($_GET)[0];
}

// Si pas de paramètre, vérifier QUERY_STRING directement
if (empty($lien_securise) && !empty($_SERVER['QUERY_STRING'])) {
    // Si c'est juste le hash sans nom de paramètre (ex: d.php?5517852ccd...)
    $lien_securise = $_SERVER['QUERY_STRING'];
}

if (empty($lien_securise)) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><title>Erreur</title></head><body><h1>Lien de devis manquant</h1><p>Utilisation: d.php?[lien_securise]</p></body></html>";
    exit;
}

// Nettoyer le lien sécurisé (garder uniquement les caractères alphanumériques)
$lien_securise = preg_replace('/[^a-zA-Z0-9]/', '', $lien_securise);

// Vérifier que c'est bien un hash MD5 (32 caractères hexadécimaux)
if (!preg_match('/^[a-f0-9]{32}$/', $lien_securise)) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><title>Erreur</title></head><body><h1>Lien de devis invalide</h1></body></html>";
    exit;
}

// Rediriger vers la vraie page devis_client.php
header('Location: /pages/devis_client.php?lien=' . $lien_securise);
exit;
?>
