<?php
// Test des logs
session_start();

// Définir un fichier de log spécifique
ini_set('error_log', '/var/www/mdgeek.top/debug.log');
ini_set('log_errors', '1');

error_log("TEST: Début du test de logs");

// Simuler les conditions
$_GET['page'] = 'template_sms';
$_SESSION['allow_template_sms_access'] = true;

$allow_no_auth = (isset($_GET['page']) && $_GET['page'] === 'template_sms') || 
                 (isset($_SESSION['allow_template_sms_access']) && $_SESSION['allow_template_sms_access'] === true);

error_log("TEST: user_id=" . ($_SESSION['user_id'] ?? 'non défini') . 
          ", page=" . ($_GET['page'] ?? 'non défini') . 
          ", allow_template_sms_access=" . ($_SESSION['allow_template_sms_access'] ?? 'non défini') . 
          ", allow_no_auth=" . ($allow_no_auth ? 'true' : 'false'));

if (!isset($_SESSION['user_id']) && !$allow_no_auth) {
    error_log("TEST: Accès refusé");
    echo "Accès refusé\n";
} else {
    error_log("TEST: Accès autorisé");
    echo "Accès autorisé\n";
}

error_log("TEST: Fin du test");
echo "Test terminé\n";
?>
