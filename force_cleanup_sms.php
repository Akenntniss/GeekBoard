<?php
// force_cleanup_sms.php
// Script de nettoyage manuel des SMS en échec
// À EXECUTER UNE SEULE FOIS PUIS SUPPRIMER

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/session_config.php';
require_once BASE_PATH . '/config/database.php';

// Force session init if needed
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$shop_pdo = getShopDBConnection();

if (!$shop_pdo) {
    die("ERREUR: Impossible de se connecter à la base de données du magasin. Vérifiez que vous êtes bien logué.");
}

$target_number_pattern = '%782962906%'; // Matche +3378..., 078... et 3378...

echo "<h1>Nettoyage SMS en échec</h1>";
echo "<p>Base de données connectée.</p>";

try {
    $shop_pdo->beginTransaction();

    // 1. Supprimer TOUS les échecs SAUF ceux du numéro cible
    // Table sms_logs
    $sql1 = "DELETE FROM sms_logs 
             WHERE status = 0 
             AND recipient NOT LIKE ?";
    $stmt1 = $shop_pdo->prepare($sql1);
    $stmt1->execute([$target_number_pattern]);
    $deleted_logs = $stmt1->rowCount();
    echo "<p>SMS Logs supprimés (autres numéros) : <strong>$deleted_logs</strong></p>";

    // Table reparation_sms
    $sql2 = "DELETE FROM reparation_sms 
             WHERE statut_id = 0 
             AND telephone NOT LIKE ?";
    $stmt2 = $shop_pdo->prepare($sql2);
    $stmt2->execute([$target_number_pattern]);
    $deleted_rep = $stmt2->rowCount();
    echo "<p>Reparation SMS supprimés (autres numéros) : <strong>$deleted_rep</strong></p>";


    // 2. Pour le numéro cible, ne garder que les 10 derniers échecs
    // On doit identifier les IDs à supprimer
    
    // SMS Logs cible
    $stmt3 = $shop_pdo->prepare("SELECT id FROM sms_logs 
                                 WHERE status = 0 AND recipient LIKE ? 
                                 ORDER BY date_envoi DESC 
                                 LIMIT 1000 OFFSET 10"); // Tout ce qui est après le 10ème
    $stmt3->execute([$target_number_pattern]);
    $ids_to_delete_logs = $stmt3->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($ids_to_delete_logs)) {
        $in_query = implode(',', array_fill(0, count($ids_to_delete_logs), '?'));
        $del_stmt = $shop_pdo->prepare("DELETE FROM sms_logs WHERE id IN ($in_query)");
        $del_stmt->execute($ids_to_delete_logs);
        echo "<p>Nettoyage cible (SMS Logs, garder 10 max) : " . count($ids_to_delete_logs) . " supprimés.</p>";
    } else {
        echo "<p>Nettoyage cible (SMS Logs) : Rien à supprimer (<= 10 restants).</p>";
    }

    // Reparation SMS cible
    $stmt4 = $shop_pdo->prepare("SELECT id FROM reparation_sms 
                                 WHERE statut_id = 0 AND telephone LIKE ? 
                                 ORDER BY date_envoi DESC 
                                 LIMIT 1000 OFFSET 10");
    $stmt4->execute([$target_number_pattern]);
    $ids_to_delete_rep = $stmt4->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($ids_to_delete_rep)) {
        $in_query = implode(',', array_fill(0, count($ids_to_delete_rep), '?'));
        $del_stmt = $shop_pdo->prepare("DELETE FROM reparation_sms WHERE id IN ($in_query)");
        $del_stmt->execute($ids_to_delete_rep);
        echo "<p>Nettoyage cible (Reparation SMS, garder 10 max) : " . count($ids_to_delete_rep) . " supprimés.</p>";
    } else {
        echo "<p>Nettoyage cible (Reparation SMS) : Rien à supprimer (<= 10 restants).</p>";
    }

    $shop_pdo->commit();
    echo "<h2 style='color:green'>Succès ! Nettoyage terminé.</h2>";
    echo "<p>Vous pouvez supprimer ce fichier 'force_cleanup_sms.php' maintenant.</p>";
    echo "<a href='index.php?page=sms_historique' class='btn'>Retour à l'historique</a>";

} catch (Exception $e) {
    $shop_pdo->rollBack();
    echo "<h2 style='color:red'>Erreur : " . $e->getMessage() . "</h2>";
}
