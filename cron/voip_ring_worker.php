<?php
/**
 * Script d'envoi de notifications répétées pour les appels VOIP
 * Ce script est lancé en arrière-plan et envoie des notifications toutes les 3 secondes
 * jusqu'à ce que l'appel soit répondu, refusé, ou que le temps soit écoulé.
 * 
 * Paramètres (via arguments CLI) :
 * - call_id : ID de l'appel
 * - receiver_id : ID du destinataire
 * - caller_name : Nom de l'appelant
 * - call_type : Type d'appel (audio/video)
 * - caller_id : ID de l'appelant
 */

// Ignorer la déconnexion du client (script en arrière-plan)
ignore_user_abort(true);
set_time_limit(120); // Max 120 secondes

// Récupérer les arguments
$call_id = $argv[1] ?? null;
$receiver_id = $argv[2] ?? null;
$caller_name = $argv[3] ?? 'Inconnu';
$call_type = $argv[4] ?? 'video';
$caller_id = $argv[5] ?? null;
$shop_id = $argv[6] ?? null;

if (!$call_id || !$receiver_id) {
    error_log("VOIP Ring Worker: Paramètres manquants");
    exit(1);
}

error_log("VOIP Ring Worker: Démarrage pour appel #$call_id (Shop ID: " . ($shop_id ?? 'N/A') . ")");

// Charger UNIQUEMENT la base de données et les pushs
try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/PushNotifications.php';

    // Connexion à la base de données
    if ($shop_id) {
        $pdo = getShopDBConnectionById($shop_id);
    } else {
        // Fallback si pas de shop_id (ex: admin global)
        $pdo = getShopDBConnection();
    }
    
    if (!$pdo) {
        throw new Exception("Impossible de se connecter à la base de données (Shop ID: $shop_id)");
    }
    
    $pushNotifications = new PushNotifications($pdo);
} catch (Exception $e) {
    error_log("VOIP Ring Worker Critical Error: " . $e->getMessage());
    exit(1);
}

// Configuration
$MAX_RINGS = 30;        // 30 * 2s = 60 secondes total
$RING_INTERVAL = 2;     // Secondes entre chaque notification (réduit de 5 à 2)
$callTypeLabel = $call_type === 'audio' ? 'audio' : 'vidéo';

/**
 * Vérifie le statut actuel de l'appel
 */
function getCallStatus($pdo, $call_id) {
    $stmt = $pdo->prepare("SELECT status FROM voip_calls WHERE id = ?");
    $stmt->execute([$call_id]);
    return $stmt->fetchColumn();
}

/**
 * Enregistre une notification d'appel manqué en base de données
 */
function saveMissedCallNotification($pdo, $receiver_id, $caller_name, $call_type, $caller_id, $call_id) {
    try {
        $callTypeLabel = $call_type === 'audio' ? 'audio' : 'vidéo';
        $message = "Appel $callTypeLabel manqué de $caller_name";
        $actionUrl = "/index.php?page=appels&missed_call=$call_id";
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications 
            (user_id, notification_type, message, related_id, related_type, action_url, is_important, is_broadcast, created_by, created_at)
            VALUES (?, 'voip_missed_call', ?, ?, 'voip_call', ?, 1, 0, ?, NOW())
        ");
        $stmt->execute([
            $receiver_id,
            $message,
            $call_id,
            $actionUrl,
            $caller_id
        ]);
        
        error_log("VOIP Ring Worker: Notification d'appel manqué enregistrée pour user #$receiver_id");
        return true;
    } catch (Exception $e) {
        error_log("VOIP Ring Worker: Erreur sauvegarde notification - " . $e->getMessage());
        return false;
    }
}

// Boucle d'envoi des notifications
for ($i = 1; $i <= $MAX_RINGS; $i++) {
    // Attendre avant d'envoyer la notification (sauf pour la toute première si on veut enchainer, mais ici on veut espacer de l'API)
    sleep($RING_INTERVAL);

    // Vérifier le statut de l'appel
    $status = getCallStatus($pdo, $call_id);
    
    // Si l'appel n'est plus en "calling", arrêter
    if ($status !== 'calling') {
        error_log("VOIP Ring Worker: Appel #$call_id terminé avec statut '$status' avant sonnerie $i");
        exit(0);
    }
    
    // Envoyer la notification push
    try {
        $result = $pushNotifications->sendToUser($receiver_id, 
            '📞 Appel entrant', 
            "Appel $callTypeLabel de $caller_name",
            [
                'url' => '/index.php?page=appels&incoming_call=' . $call_id,
                'tag' => 'voip-call-' . $call_id, // Même tag = notifications se remplacent
                'renotify' => true,
                'type' => 'voip-call',
                'vibrate' => [300, 200, 300, 200, 300, 200, 300, 200, 300, 200, 300],
                'actions' => [
                    ['action' => 'answer', 'title' => '✅ Répondre'],
                    ['action' => 'reject', 'title' => '❌ Refuser']
                ],
                'data' => [
                    'call_id' => $call_id,
                    'caller_id' => $caller_id,
                    'caller_name' => $caller_name,
                    'call_type' => $call_type,
                    'ring_number' => $i
                ]
            ]
        );
        
        error_log("VOIP Ring Worker: Sonnerie $i/$MAX_RINGS envoyée pour appel #$call_id");
    } catch (Exception $e) {
        error_log("VOIP Ring Worker: Erreur envoi notification - " . $e->getMessage());
    }
}

// Vérifier une dernière fois le statut
$finalStatus = getCallStatus($pdo, $call_id);

if ($finalStatus === 'calling') {
    // L'appel n'a pas été répondu - marquer comme manqué
    error_log("VOIP Ring Worker: Appel #$call_id manqué - mise à jour du statut");
    
    // Mettre à jour le statut de l'appel
    $stmt = $pdo->prepare("UPDATE voip_calls SET status = 'missed', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$call_id]);
    
    // Enregistrer une notification d'appel manqué
    saveMissedCallNotification($pdo, $receiver_id, $caller_name, $call_type, $caller_id, $call_id);
}

error_log("VOIP Ring Worker: Terminé pour appel #$call_id");
