<?php
// Désactiver le cache
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Content-Type: application/json");

// Inclure la configuration de session (AVANT tout session_start)
require_once __DIR__ . '/../../config/session_config.php';

// Inclure les configurations nécessaires
require_once __DIR__ . '/../../config/subdomain_config.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non authentifié']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Utiliser la connexion shop appropriée
$pdo = getShopDBConnection();

// Vérifier si la colonne is_online existe, sinon l'ajouter
try {
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_online'")->fetchAll();
    if (empty($columns)) {
        // Ajouter la colonne is_online si elle n'existe pas
        $pdo->exec("ALTER TABLE users ADD COLUMN is_online TINYINT(1) DEFAULT 0 AFTER active_repair_id");
        error_log("VOIP: Colonne is_online ajoutée à la table users");
    }
} catch (PDOException $e) {
    error_log("VOIP: Erreur vérification/ajout colonne is_online: " . $e->getMessage());
}

// Vérifier si la colonne call_type existe dans voip_calls, sinon l'ajouter
try {
    $columns = $pdo->query("SHOW COLUMNS FROM voip_calls LIKE 'call_type'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE voip_calls ADD COLUMN call_type VARCHAR(10) DEFAULT 'video' AFTER sdp_offer");
        error_log("VOIP: Colonne call_type ajoutée à la table voip_calls");
    }
} catch (PDOException $e) {
    error_log("VOIP: Erreur vérification/ajout colonne call_type: " . $e->getMessage());
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_online_users':
            // Récupérer tous les utilisateurs (sauf soi-même)
            // D'abord vérifier quelles colonnes existent
            $hasIsOnline = false;
            $hasTechbusy = false;
            
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
                $hasIsOnline = in_array('is_online', $cols);
                $hasTechbusy = in_array('techbusy', $cols);
            } catch (Exception $e) {
                // Ignorer, on utilisera une requête basique
            }
            
            // Construire la requête selon les colonnes disponibles
            if ($hasIsOnline && $hasTechbusy) {
                $stmt = $pdo->prepare("SELECT id, username, full_name, is_online, techbusy FROM users WHERE id != ? ORDER BY is_online DESC, full_name ASC");
            } elseif ($hasIsOnline) {
                $stmt = $pdo->prepare("SELECT id, username, full_name, is_online, 0 as techbusy FROM users WHERE id != ? ORDER BY is_online DESC, full_name ASC");
            } elseif ($hasTechbusy) {
                $stmt = $pdo->prepare("SELECT id, username, full_name, 0 as is_online, techbusy FROM users WHERE id != ? ORDER BY full_name ASC");
            } else {
                $stmt = $pdo->prepare("SELECT id, username, full_name, 0 as is_online, 0 as techbusy FROM users WHERE id != ? ORDER BY full_name ASC");
            }
            
            $stmt->execute([$current_user_id]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ajouter un statut calculé
            foreach ($users as &$user) {
                // Si techbusy est un timestamp récent (moins de 5 min), on le considère actif
                $isActiveRecently = ($user['techbusy'] > (time() - 300));
                
                // On force le status online si actif récemment
                if ($isActiveRecently) {
                    $user['is_online'] = 1; 
                }
                
                $user['status_label'] = ($user['is_online'] == 1) ? 'En ligne' : 'Hors ligne';
            }
            
            echo json_encode(['status' => 'success', 'users' => $users]);
            break;

        case 'initiate_call':
            $receiver_id = $data['receiver_id'];
            $offer = $data['offer']; // SDP Offer
            $call_type = $data['call_type'] ?? 'video'; // 'audio' or 'video'

            // Récupérer le nom de l'appelant
            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$current_user_id]);
            $caller = $stmt->fetch(PDO::FETCH_ASSOC);
            $caller_name = $caller ? $caller['full_name'] : 'Inconnu';

            // Créer une nouvelle entrée d'appel
            $stmt = $pdo->prepare("INSERT INTO voip_calls (caller_id, receiver_id, status, sdp_offer, call_type, created_at) VALUES (?, ?, 'calling', ?, ?, NOW())");
            $stmt->execute([$current_user_id, $receiver_id, $offer, $call_type]);
            $call_id = $pdo->lastInsertId();

            // Envoyer la première notification push IMMÉDIATEMENT
            try {
                require_once __DIR__ . '/../../includes/PushNotifications.php';
                $pushNotifications = new PushNotifications($pdo);
                
                $callTypeLabel = $call_type === 'audio' ? 'audio' : 'vidéo';
                $pushNotifications->sendToUser($receiver_id, 
                    '📞 Appel entrant', 
                    "Appel $callTypeLabel de $caller_name",
                    [
                        'url' => '/index.php?page=appels&incoming_call=' . $call_id,
                        'tag' => 'voip-call-' . $call_id,
                        'renotify' => true,
                        'type' => 'voip-call',
                        'vibrate' => [300, 200, 300, 200, 300, 200, 300, 200, 300, 200, 300],
                        'requireInteraction' => true,
                        'actions' => [
                            ['action' => 'answer', 'title' => '✅ Répondre'],
                            ['action' => 'reject', 'title' => '❌ Refuser']
                        ],
                        'data' => [
                            'call_id' => $call_id,
                            'caller_id' => $current_user_id,
                            'caller_name' => $caller_name,
                            'call_type' => $call_type,
                            'ring_number' => 1
                        ]
                    ]
                );
                error_log("VOIP: Première notification push envoyée pour appel #$call_id");
            } catch (Exception $e) {
                error_log("VOIP Push Error: " . $e->getMessage());
            }

            // Répondre immédiatement au client pour ne pas bloquer l'interface
            echo json_encode(['status' => 'success', 'call_id' => $call_id]);
            
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                ob_end_flush();
                flush();
            }

            // Lancer le worker de sonnerie en arrière-plan pour les notifications suivantes
            // Utilise exec() pour détacher complètement le processus
            try {
                $workerScript = __DIR__ . '/../../cron/voip_ring_worker.php';
                $shop_id = $_SESSION['shop_id'] ?? 0; // Récupérer l'ID du magasin actuel
                
                // Commande pour lancer le script en arrière-plan sans attendre la fin
                // Ajout de shop_id en 6ème argument
                $cmd = "/usr/bin/php " . escapeshellarg($workerScript) . " " . 
                       escapeshellarg($call_id) . " " . 
                       escapeshellarg($receiver_id) . " " . 
                       escapeshellarg($caller_name) . " " . 
                       escapeshellarg($call_type) . " " . 
                       escapeshellarg($current_user_id) . " " .
                       escapeshellarg($shop_id) . 
                       " > /tmp/voip_worker_$call_id.log 2>&1 &";
                
                exec($cmd);
                error_log("VOIP: Worker lancé avec shop_id=$shop_id: $cmd");
            } catch (Exception $e) {
                error_log("VOIP Worker Error: " . $e->getMessage());
            }
            
            // Terminer le script principal ici pour être sûr
            exit;
            break;

        case 'answer_call':
            $call_id = $data['call_id'];
            $answer = $data['answer']; // SDP Answer

            // Mettre à jour l'appel avec la réponse
            $stmt = $pdo->prepare("UPDATE voip_calls SET status = 'accepted', sdp_answer = ?, updated_at = NOW() WHERE id = ? AND receiver_id = ?");
            $stmt->execute([$answer, $call_id, $current_user_id]);

            echo json_encode(['status' => 'success']);
            break;

        case 'reject_call':
            $call_id = $data['call_id'];
            $stmt = $pdo->prepare("UPDATE voip_calls SET status = 'rejected', updated_at = NOW() WHERE id = ? AND receiver_id = ?");
            $stmt->execute([$call_id, $current_user_id]);
            echo json_encode(['status' => 'success']);
            break;

        case 'hangup_call':
            $call_id = $data['call_id'];
            // Peut être appelé par l'appelant ou le receveur
            $stmt = $pdo->prepare("UPDATE voip_calls SET status = 'ended', updated_at = NOW() WHERE id = ? AND (caller_id = ? OR receiver_id = ?)");
            $stmt->execute([$call_id, $current_user_id, $current_user_id]);
            echo json_encode(['status' => 'success']);
            break;

        case 'send_ice_candidate':
            $call_id = $data['call_id'];
            $candidate = $data['candidate']; // JSON string
            
            // Récupérer les candidats actuels
            $stmt = $pdo->prepare("SELECT ice_candidates FROM voip_calls WHERE id = ?");
            $stmt->execute([$call_id]);
            $current = $stmt->fetchColumn();
            
            $candidatesArray = $current ? json_decode($current, true) : [];
            if (!is_array($candidatesArray)) $candidatesArray = [];
            
            $candidatesArray[] = [
                'sender_id' => $current_user_id,
                'candidate' => $candidate
            ];
            
            $newCandidates = json_encode($candidatesArray);
            
            $stmt = $pdo->prepare("UPDATE voip_calls SET ice_candidates = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newCandidates, $call_id]);
            
            echo json_encode(['status' => 'success']);
            break;

        case 'check_incoming':
            // Vérifier s'il y a un appel entrant en attente ("calling")
            $stmt = $pdo->prepare("SELECT id, caller_id, sdp_offer, call_type FROM voip_calls WHERE receiver_id = ? AND status = 'calling' AND created_at > (NOW() - INTERVAL 1 MINUTE) ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$current_user_id]);
            $incoming = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($incoming) {
                // Récupérer infos de l'appelant
                $stmtUser = $pdo->prepare("SELECT username, full_name FROM users WHERE id = ?");
                $stmtUser->execute([$incoming['caller_id']]);
                $caller = $stmtUser->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['status' => 'incoming', 'call' => $incoming, 'caller_name' => $caller['full_name']]);
            } else {
                echo json_encode(['status' => 'none']);
            }
            break;

        case 'poll_call_status':
            // Pour l'appelant : vérifier si l'appel a été accepté/rejeté
            // Pour les deux : vérifier les nouveaux ICE candidates
            $call_id = $data['call_id'];
            
            $stmt = $pdo->prepare("SELECT * FROM voip_calls WHERE id = ?");
            $stmt->execute([$call_id]);
            $call = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$call) {
                echo json_encode(['status' => 'error', 'message' => 'Call not found']);
                exit;
            }
            
            // Récupérer les candidats ICE envoyés par L'AUTRE partie
            $allCandidates = $call['ice_candidates'] ? json_decode($call['ice_candidates'], true) : [];
            $newCandidates = [];
            
            if (is_array($allCandidates)) {
                foreach ($allCandidates as $cand) {
                    if ($cand['sender_id'] != $current_user_id) {
                        $newCandidates[] = $cand['candidate'];
                    }
                }
            }
            
            echo json_encode([
                'status' => 'success',
                'call_status' => $call['status'],
                'sdp_answer' => $call['sdp_answer'],
                'remote_candidates' => $newCandidates
            ]);
            break;
            
        case 'heartbeat':
             // Mettre à jour le statut en ligne de l'utilisateur
             // Vérifier si la colonne is_online existe
             try {
                 $stmt = $pdo->prepare("UPDATE users SET is_online = 1, techbusy = UNIX_TIMESTAMP() WHERE id = ?");
                 $stmt->execute([$current_user_id]);
             } catch (PDOException $e) {
                 // Si is_online n'existe pas, essayer juste avec techbusy
                 try {
                     $stmt = $pdo->prepare("UPDATE users SET techbusy = UNIX_TIMESTAMP() WHERE id = ?");
                     $stmt->execute([$current_user_id]);
                 } catch (PDOException $e2) {
                     // Ignorer silencieusement
                 }
             }
             echo json_encode(['status' => 'success']);
             break;

        case 'get_call_history':
            // Récupérer l'historique des appels pour l'utilisateur actuel
            $limit = isset($data['limit']) ? intval($data['limit']) : 20;
            if ($limit > 50) $limit = 50;
            
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.caller_id,
                    c.receiver_id,
                    c.status,
                    c.call_type,
                    c.created_at,
                    c.updated_at,
                    TIMESTAMPDIFF(SECOND, c.created_at, COALESCE(c.updated_at, c.created_at)) as duration_seconds,
                    caller.full_name as caller_name,
                    receiver.full_name as receiver_name
                FROM voip_calls c
                LEFT JOIN users caller ON c.caller_id = caller.id
                LEFT JOIN users receiver ON c.receiver_id = receiver.id
                WHERE c.caller_id = ? OR c.receiver_id = ?
                ORDER BY c.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$current_user_id, $current_user_id, $limit]);
            $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Enrichir les données
            foreach ($calls as &$call) {
                $call['is_outgoing'] = ($call['caller_id'] == $current_user_id);
                $call['contact_name'] = $call['is_outgoing'] ? $call['receiver_name'] : $call['caller_name'];
                $call['contact_id'] = $call['is_outgoing'] ? $call['receiver_id'] : $call['caller_id'];
                
                // Formater la durée
                $secs = intval($call['duration_seconds']);
                if ($secs < 60) {
                    $call['duration_formatted'] = $secs . 's';
                } else {
                    $call['duration_formatted'] = floor($secs/60) . 'min ' . ($secs % 60) . 's';
                }
                
                // Status label
                switch($call['status']) {
                    case 'ended': $call['status_label'] = 'Terminé'; break;
                    case 'accepted': $call['status_label'] = 'En cours'; break;
                    case 'rejected': $call['status_label'] = 'Refusé'; break;
                    case 'missed': $call['status_label'] = 'Manqué'; break;
                    case 'calling': $call['status_label'] = 'En attente'; break;
                    default: $call['status_label'] = $call['status'];
                }
            }
            
            echo json_encode(['status' => 'success', 'calls' => $calls]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Action inconnue']);
            break;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
