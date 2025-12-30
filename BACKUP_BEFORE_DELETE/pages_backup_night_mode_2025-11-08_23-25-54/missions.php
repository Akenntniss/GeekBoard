<?php
/**
 * Page de redirection pour les missions
 * Redirige vers mes_missions ou admin_missions selon le rôle de l'utilisateur
 */

// Vérification de l'authentification
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    set_message("Vous devez être connecté pour accéder aux missions.", "error");
    redirect('accueil');
    exit;
}

// Redirection selon le rôle de l'utilisateur
if ($_SESSION['user_role'] === 'admin') {
    // Les administrateurs vont vers la page d'administration des missions
    redirect('admin_missions');
} else {
    // Les utilisateurs normaux vont vers leurs missions personnelles
    redirect('mes_missions');
}
?>
