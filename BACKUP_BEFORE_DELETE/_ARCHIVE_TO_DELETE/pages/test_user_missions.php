<?php
// Forcer le rôle utilisateur normal pour les tests
$_SESSION['user_id'] = 2;
$_SESSION['user_role'] = 'user';
$_SESSION['full_name'] = 'Utilisateur Test';

// Inclure la page des missions
include 'admin_missions_moderne.php';
?>
