<?php
/**
 * Fonctions pour la gestion des notifications
 */

/**
 * Crée une nouvelle notification
 */
function create_notification($user_id, $type, $message, $reference_id = null, $related_type = null, $action_url = null, $is_important = 0, $is_broadcast = 0, $created_by = null) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $stmt = $shop_pdo->prepare("INSERT INTO notifications (user_id, notification_type, message, related_id, related_type, action_url, is_important, is_broadcast, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')");
        return $stmt->execute([$user_id, $type, $message, $reference_id, $related_type, $action_url, $is_important, $is_broadcast, $created_by]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la création d'une notification : " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les notifications non lues d'un utilisateur
 */
function get_unread_notifications($user_id, $limit = 10) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $stmt = $shop_pdo->prepare("SELECT id, notification_type as type, message, action_url as link, created_at, status FROM notifications WHERE user_id = ? AND status = 'new' ORDER BY created_at DESC LIMIT ?");
        // Convertir limit en entier pour execute()
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($notifications as &$n) {
            $n['is_read'] = 0; // Pour compatibilité JS
        }
        return $notifications;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des notifications : " . $e->getMessage());
        return [];
    }
}

/**
 * Compte le nombre de notifications non lues d'un utilisateur
 */
function count_unread_notifications($user_id) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'new'");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['count'];
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des notifications : " . $e->getMessage());
        return 0;
    }
}

/**
 * Marque une notification comme lue
 */
function mark_notification_as_read($notification_id) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $stmt = $shop_pdo->prepare("UPDATE notifications SET status = 'read', read_at = NOW() WHERE id = ?");
        return $stmt->execute([$notification_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors du marquage de la notification : " . $e->getMessage());
        return false;
    }
}

/**
 * Marque toutes les notifications d'un utilisateur comme lues
 */
function mark_all_notifications_as_read($user_id) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $stmt = $shop_pdo->prepare("UPDATE notifications SET status = 'read', read_at = NOW() WHERE user_id = ? AND status = 'new'");
        return $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors du marquage de toutes les notifications : " . $e->getMessage());
        return false;
    }
}