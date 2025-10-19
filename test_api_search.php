<?php
// Script de test pour diagnostiquer le problème de recherche client

// Simuler une session pour mkmkmk
session_start();
$_SESSION['shop_id'] = 63;
$_SESSION['subdomain'] = 'mkmkmk';

// Tester la recherche directement
$_POST['terme'] = 'saber';

// Inclure le script de recherche
include 'ajax/recherche_clients.php';
?>
