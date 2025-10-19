<?php
/**
 * ================================================================================
 * PAGE DEVIS PUBLIC - Accès direct sans authentification
 * ================================================================================
 * Description: Page publique pour consulter un devis via son lien sécurisé
 * Date: 2025-01-27
 * ================================================================================
 */

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Récupérer le lien sécurisé depuis l'URL
$lien_securise = $_GET['lien'] ?? '';

if (empty($lien_securise)) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><title>Erreur</title></head><body><h1>Lien de devis manquant</h1></body></html>";
    exit;
}

// Nettoyer le lien sécurisé
$lien_securise = preg_replace('/[^a-zA-Z0-9]/', '', $lien_securise);

// Rediriger directement vers la vraie page devis_client.php
header('Location: /pages/devis_client.php?lien=' . $lien_securise);
exit;
?>
