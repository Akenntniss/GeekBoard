<?php
// force_add_failed_sms.php
// Ajoute 5 SMS en échec pour tester le système
// À SUPPRIMER APRÈS USAGE

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/session_config.php';
require_once BASE_PATH . '/config/database.php';

// Force session init
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$shop_pdo = getShopDBConnection();

if (!$shop_pdo) {
    die("ERREUR: Impossible de se connecter à la base de données du magasin.");
}

$phoneNumber = '33782962906';
$count = 5;

echo "<h1>Génération de données de test</h1>";

try {
    $shop_pdo->beginTransaction();
    
    $stmt = $shop_pdo->prepare("
        INSERT INTO sms_logs (recipient, message, status, response, date_envoi, reference_type, reference_id) 
        VALUES (?, ?, 0, '{\"success\":false,\"message\":\"Simulated Failure\"}', NOW(), 'manual_test', 0)
    ");

    for ($i = 1; $i <= $count; $i++) {
        $msg = "Test SMS Failure #$i - " . date('H:i:s');
        $stmt->execute([$phoneNumber, $msg]);
        echo "<p>SMS #$i ajouté pour $phoneNumber (Statut: Echec)</p>";
    }

    $shop_pdo->commit();
    echo "<h2 style='color:green'>Succès ! $count SMS en échec ajoutés.</h2>";
    echo "<a href='index.php?page=sms_historique' class='btn'>Retour à l'historique</a>";

} catch (Exception $e) {
    $shop_pdo->rollBack();
    echo "<h2 style='color:red'>Erreur : " . $e->getMessage() . "</h2>";
}
