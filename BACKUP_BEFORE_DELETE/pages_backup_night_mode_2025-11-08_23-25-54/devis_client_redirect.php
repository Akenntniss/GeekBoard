<?php
/**
 * ================================================================================
 * REDIRECTION POUR DEVIS CLIENT - Page publique sans authentification
 * ================================================================================
 * Description: Redirecteur simple vers la vraie page devis_client.php
 * Date: 2025-01-27
 * ================================================================================
 */

// Récupérer le lien sécurisé depuis l'URL
$lien_securise = $_GET['lien'] ?? '';

if (empty($lien_securise)) {
    http_response_code(404);
    echo "Lien de devis manquant";
    exit;
}

// Nettoyer le lien sécurisé
$lien_securise = preg_replace('/[^a-zA-Z0-9]/', '', $lien_securise);

// Rediriger vers la vraie page devis_client.php
header('Location: devis_client.php?lien=' . $lien_securise);
exit;
?>
