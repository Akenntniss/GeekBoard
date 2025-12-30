<?php
// ajax/resend_failed_sms.php
// Script pour renvoyer les SMS en échec avec protection anti-doublon

// Autoriser l'accès seulement si connecté (sera vérifié via session)
define('BASE_PATH', dirname(__DIR__));

// Important : Utiliser la configuration de session globale pour récupérer la session utilisateur existante
require_once BASE_PATH . '/config/session_config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';
// Inclure les fonctions SMS si elles ne sont pas déjà chargées par functions.php
if (!function_exists('send_sms')) {
    $sms_functions_path = BASE_PATH . '/includes/sms_functions.php';
    if (file_exists($sms_functions_path)) {
        require_once $sms_functions_path;
    }
}

$shop_pdo = getShopDBConnection();
if (!$shop_pdo) {
    echo json_encode(['success' => false, 'message' => 'Erreur connexion DB']);
    exit;
}

$action = $_POST['action'] ?? '';

header('Content-Type: application/json');

try {
    if ($action === 'count') {
        // Compter uniquement les vrais échecs (ceux qui n'ont pas été suivis d'un succès identique)
        // On exclut ceux où il existe un message identique (tel + msg) en statut success (1) avec une date POSTERIEURE
        
        $sql_logs_count = "SELECT COUNT(*) FROM sms_logs s1 
                           WHERE status = 0 
                           AND NOT EXISTS (
                               SELECT 1 FROM sms_logs s2 
                               WHERE s2.recipient = s1.recipient 
                               AND s2.message = s1.message 
                               AND s2.status = 1 
                               AND s2.date_envoi > s1.date_envoi
                           )";
        $stmt1 = $shop_pdo->query($sql_logs_count);
        $count1 = $stmt1->fetchColumn();
        
        $sql_rep_count = "SELECT COUNT(*) FROM reparation_sms rs1 
                          WHERE statut_id = 0 
                          AND NOT EXISTS (
                              SELECT 1 FROM reparation_sms rs2 
                              WHERE rs2.telephone = rs1.telephone 
                              AND rs2.message = rs1.message 
                              AND rs2.statut_id = 1 
                              AND rs2.date_envoi > rs1.date_envoi
                          )";
        $stmt2 = $shop_pdo->query($sql_rep_count);
        $count2 = $stmt2->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'total_failed' => $count1 + $count2,
            'details' => [
                'sms_logs' => $count1,
                'reparation_sms' => $count2
            ]
        ]);
        exit;
    }
    
    if ($action === 'batch_resend') {
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 1;
        $processed = [];
        $total_sent = 0;
        $total_skipped = 0;
        $total_errors = 0;
        
        // 1. Récupérer les échecs NON RÉSOLUS
        
        // SMS Logs
        $sql_logs = "SELECT id, recipient as telephone, message, 'sms_logs' as source_table, reparation_id 
                     FROM sms_logs s1
                     WHERE status = 0 
                     AND NOT EXISTS (
                        SELECT 1 FROM sms_logs s2 
                        WHERE s2.recipient = s1.recipient 
                        AND s2.message = s1.message 
                        AND s2.status = 1 
                        AND s2.date_envoi > s1.date_envoi
                     )
                     ORDER BY date_envoi DESC 
                     LIMIT " . intval($limit);
        
        $stmt_logs = $shop_pdo->query($sql_logs);
        $logs_results = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);
        
        $remaining_limit = $limit - count($logs_results);
        $rep_results = [];
        
        if ($remaining_limit > 0) {
            $sql_rep = "SELECT id, telephone, message, 'reparation_sms' as source_table, reparation_id 
                        FROM reparation_sms rs1
                        WHERE statut_id = 0 
                        AND NOT EXISTS (
                            SELECT 1 FROM reparation_sms rs2 
                            WHERE rs2.telephone = rs1.telephone 
                            AND rs2.message = rs1.message 
                            AND rs2.statut_id = 1 
                            AND rs2.date_envoi > rs1.date_envoi
                        )
                        ORDER BY date_envoi DESC 
                        LIMIT " . intval($remaining_limit);
            $stmt_rep = $shop_pdo->query($sql_rep);
            $rep_results = $stmt_rep->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $batch = array_merge($logs_results, $rep_results);
        
        if (empty($batch)) {
            echo json_encode(['success' => true, 'message' => 'Aucun SMS à renvoyer', 'processed' => []]);
            exit;
        }
        
        foreach ($batch as $sms) {
            $id = $sms['id'];
            $phone = trim($sms['telephone']);
            $msg = trim($sms['message']);
            $source = $sms['source_table'];
            $reparation_id = $sms['reparation_id'];
            
            // --- PROTECTION ANTI-DOUBLON ---
            // Vérifier si un SMS IDENTIQUE (même numéro, même contenu) a été envoyé avec succès
            // dans les dernières 24 heures (élargi par sécurité, l'user parlait d'éviter 2 fois le même)
            
            $is_duplicate = false;
            
            // Check sms_logs duplicates
            $check_sql1 = "SELECT COUNT(*) FROM sms_logs 
                           WHERE recipient = ? AND message = ? AND status = 1 
                           AND date_envoi > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $stmt_check1 = $shop_pdo->prepare($check_sql1);
            $stmt_check1->execute([$phone, $msg]);
            if ($stmt_check1->fetchColumn() > 0) $is_duplicate = true;
            
            // Check reparation_sms duplicates
            if (!$is_duplicate) {
                $check_sql2 = "SELECT COUNT(*) FROM reparation_sms 
                               WHERE telephone = ? AND message = ? AND statut_id = 1 
                               AND date_envoi > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
                $stmt_check2 = $shop_pdo->prepare($check_sql2);
                $stmt_check2->execute([$phone, $msg]);
                if ($stmt_check2->fetchColumn() > 0) $is_duplicate = true;
            }
            
            if ($is_duplicate) {
                // Marquer comme traité pour ne plus le voir dans la liste des échecs
                // On met status = 1 mais on note dans processed que c'était un skip
                if ($source === 'sms_logs') {
                    $upd = $shop_pdo->prepare("UPDATE sms_logs SET status = 1 WHERE id = ?"); // 1 = Sent (même si skipped, on le sort de l'erreur)
                } else {
                    $upd = $shop_pdo->prepare("UPDATE reparation_sms SET statut_id = 1 WHERE id = ?");
                }
                $upd->execute([$id]);
                
                $processed[] = [
                    'id' => $id,
                    'phone' => $phone,
                    'status' => 'skipped',
                    'reason' => 'Doublon détecté (déjà envoyé récemment)'
                ];
                $total_skipped++;
                continue;
            }
            
            // --- TENTATIVE D'ENVOI ---
            // Simuler l'envoi si fonction manquante (sécurité dev)
            if (!function_exists('send_sms')) {
                 $result = false; // error
                 $error_msg = "Fonction send_sms introuvable";
            } else {
                // Determine reference for the new log
                // Si lié à une réparation, on veut que le nouveau log le soit aussi
                if ($reparation_id) {
                    $ref_type = 'manual_sms'; // 'manual_sms' assure l'insertion dans reparation_sms via log_sms_to_database
                    $ref_id = $reparation_id;
                } else {
                    $ref_type = 'resend_batch'; // Pas de réparation liée
                    $ref_id = $id; // Reference l'ancien ID pour tracer
                }
                
                // send_sms va créer un NOUVEAU log avec statut success/fail
                $result = send_sms($phone, $msg, $ref_type, $ref_id, $_SESSION['user_id']);
            }
            
            // Si l'envoi a réussi (ou qu'on assume que la fonction a géré), on met à jour l'ancien statut
            // ATTENTION: Si send_sms créé un NOUVEAU log, on a maintenant un doublon de log (un fail, un success).
            // Pour nettoyer la liste "Echecs", on doit passer l'AVANT (le fail) à un statut "traité" ou "supprimé".
            // Le plus simple est de le passer à 1 (Sent) pour qu'il sorte de la liste des échecs, car le but est de vider cette liste.
            
            if ($result) {
                if ($source === 'sms_logs') {
                    $upd = $shop_pdo->prepare("UPDATE sms_logs SET status = 1 WHERE id = ?");
                } else {
                    $upd = $shop_pdo->prepare("UPDATE reparation_sms SET statut_id = 1 WHERE id = ?");
                }
                $upd->execute([$id]);
                
                $processed[] = [
                    'id' => $id,
                    'phone' => $phone,
                    'status' => 'sent',
                    'msg' => 'Renvoyé avec succès'
                ];
                $total_sent++;
            } else {
                // Echec de l'envoi (API erreur, crédit insuffisant, etc)
                // On laisse le statut à 0 pour qu'il reste dans la liste des échecs à retenter plus tard
                $processed[] = [
                    'id' => $id,
                    'phone' => $phone,
                    'status' => 'error',
                    'msg' => 'Echec envoi API'
                ];
                $total_errors++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'processed' => $processed,
            'stats' => [
                'sent' => $total_sent,
                'skipped' => $total_skipped,
                'errors' => $total_errors
            ]
        ]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
