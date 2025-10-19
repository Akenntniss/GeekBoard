<?php
/**
 * Page de debug pour l'administration SERVO
 */

// Configuration basique
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Admin SERVO</h1>";
echo "<p>Timestamp: " . date('d/m/Y H:i:s') . "</p>";

// Test de la base de données
echo "<h2>Test Base de Données</h2>";
try {
    require_once '../config/database.php';
    $pdo = getMainDBConnection();
    echo "<p style='color: green;'>✅ Connexion à la base de données : OK</p>";
    
    // Test de la table contact_requests
    $count = $pdo->query("SELECT COUNT(*) FROM contact_requests")->fetchColumn();
    echo "<p style='color: green;'>✅ Table contact_requests : $count entrées</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur base de données : " . $e->getMessage() . "</p>";
}

// Test de la configuration email
echo "<h2>Test Configuration Email</h2>";
try {
    require_once '../config/email.php';
    
    if (defined('SMTP_HOST')) {
        echo "<p style='color: green;'>✅ Configuration SMTP chargée</p>";
        echo "<ul>";
        echo "<li>Serveur : " . SMTP_HOST . ":" . SMTP_PORT . "</li>";
        echo "<li>Utilisateur : " . SMTP_USERNAME . "</li>";
        echo "<li>Encryption : " . SMTP_ENCRYPTION . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ Configuration SMTP non chargée</p>";
    }
    
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "<p style='color: green;'>✅ PHPMailer disponible</p>";
    } else {
        echo "<p style='color: red;'>❌ PHPMailer non disponible</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur configuration email : " . $e->getMessage() . "</p>";
}

// Informations serveur
echo "<h2>Informations Serveur</h2>";
echo "<ul>";
echo "<li>PHP Version : " . PHP_VERSION . "</li>";
echo "<li>Document Root : " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li>Script Name : " . $_SERVER['SCRIPT_NAME'] . "</li>";
echo "<li>Server Name : " . $_SERVER['SERVER_NAME'] . "</li>";
echo "</ul>";

// Liens rapides
echo "<h2>Liens Rapides</h2>";
echo "<ul>";
echo "<li><a href='simple_contact_viewer.php'>Voir les soumissions de contact</a></li>";
echo "<li><a href='simple_email_test.php'>Tester les emails</a></li>";
echo "<li><a href='../contact_handler.php' target='_blank'>Handler de contact</a></li>";
echo "<li><a href='https://servo.tools/contact' target='_blank'>Page de contact public</a></li>";
echo "</ul>";

// Test des fichiers
echo "<h2>Test des Fichiers</h2>";
$files_to_check = [
    '../config/database.php',
    '../config/email.php',
    '../contact_handler.php',
    'simple_contact_viewer.php',
    'simple_email_test.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file existe</p>";
    } else {
        echo "<p style='color: red;'>❌ $file manquant</p>";
    }
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
ul { margin-left: 20px; }
</style>
