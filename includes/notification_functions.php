<?php
/**
 * Fonctions de gestion des notifications
 * Ce fichier contient toutes les fonctions liées à la gestion des notifications
 */

/**
 * Définit un message flash qui sera affiché sur la page suivante
 * @param string $text Le texte du message
 * @param string $title Le titre du message (par défaut "Information")
 * @param string $type Le type de message (success, warning, danger, info)
 */
function set_flash_message($text, $title = 'Information', $type = 'success') {
    $_SESSION['flash_message'] = [
        'text' => $text,
        'title' => $title,
        'type' => $type
    ];
}

/**
 * Envoie une notification à l'utilisateur
 * @param int $user_id ID de l'utilisateur cible
 * @param string $title Titre de la notification
 * @param string $message Contenu de la notification
 * @param string $link Lien optionnel
 * @param string $type Type de notification (info, success, warning, danger)
 * @return bool Succès ou échec
 */
function send_notification($user_id, $title, $message, $link = null, $type = 'info') {
    $shop_pdo = getShopDBConnection();
    
    try {
        $sql = "INSERT INTO notifications (user_id, notification_type, message, action_url, related_id, related_type, created_at, status) 
                VALUES (:user_id, :type, :message, :link, NULL, NULL, NOW(), 'new')";
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':type' => $type,
            ':message' => $message,
            ':link' => $link
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erreur lors de l'envoi de la notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les notifications non lues d'un utilisateur
 * @param int $user_id ID de l'utilisateur
 * @param int $limit Nombre maximum de notifications à récupérer
 * @return array Tableau de notifications
 */
function get_unread_notifications($user_id, $limit = 10) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $sql = "SELECT id, message, action_url, notification_type, created_at, status 
                FROM notifications 
                WHERE user_id = :user_id AND status = 'new'
                ORDER BY created_at DESC
                LIMIT :limit";
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ajouter les icônes et couleurs en fonction du type
        foreach ($notifications as &$notification) {
            $notification['time_ago'] = format_time_ago(strtotime($notification['created_at']));
            
            // Mapper les icônes et couleurs selon le type
            switch ($notification['notification_type']) {
                case 'reparation_start':
                case 'reparation_update':
                case 'reparation_finish':
                    $notification['icon'] = 'fas fa-tools';
                    $notification['color'] = '#4361ee';
                    break;
                case 'task_assigned':
                case 'task_completed':
                    $notification['icon'] = 'fas fa-tasks';
                    $notification['color'] = '#7209b7';
                    break;
                case 'new_order':
                    $notification['icon'] = 'fas fa-shopping-cart';
                    $notification['color'] = '#06d6a0';
                    break;
                case 'stock_low':
                    $notification['icon'] = 'fas fa-exclamation-triangle';
                    $notification['color'] = '#f77f00';
                    break;
                case 'system_alert':
                    $notification['icon'] = 'fas fa-exclamation-circle';
                    $notification['color'] = '#e63946';
                    break;
                case 'message_received':
                    $notification['icon'] = 'fas fa-envelope';
                    $notification['color'] = '#00d4ff';
                    break;
                default:
                    $notification['icon'] = 'fas fa-bell';
                    $notification['color'] = '#4361ee';
                    break;
            }
        }
        
        return $notifications;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les notifications d'un utilisateur avec filtres et pagination
 * @param int $user_id ID de l'utilisateur
 * @param string $filter Filtre (all, new, read)
 * @param int $limit Limite
 * @param int $offset Offset
 * @return array
 */
function get_user_notifications($user_id, $filter = 'all', $limit = 15, $offset = 0) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $where = "WHERE user_id = :user_id";
        if ($filter === 'new') {
            $where .= " AND is_read = 0";
        } elseif ($filter === 'read') {
            $where .= " AND is_read = 1";
        }
        
        $sql = "SELECT * FROM notifications 
                $where 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($notifications as &$n) {
            $n['time_ago'] = format_time_ago(strtotime($n['created_at']));
            // Fallback pour les colonnes attendues par pages/notifications.php
            if (!isset($n['is_read'])) {
                $n['is_read'] = ($n['status'] === 'read') ? 1 : 0;
            }
        }
        
        return $notifications;
    } catch (PDOException $e) {
        error_log("Erreur get_user_notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Marque une notification comme lue
 * @param int $notification_id ID de la notification
 * @param int $user_id ID de l'utilisateur (pour vérification)
 * @return bool Succès ou échec
 */
function mark_notification_as_read($notification_id, $user_id) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $sql = "UPDATE notifications 
                SET status = 'read', read_at = NOW() 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute([
            ':id' => $notification_id,
            ':user_id' => $user_id
        ]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Erreur lors du marquage de la notification comme lue: " . $e->getMessage());
        return false;
    }
}

/**
 * Marque toutes les notifications d'un utilisateur comme lues
 * @param int $user_id ID de l'utilisateur
 * @return bool Succès ou échec
 */
function mark_all_notifications_as_read($user_id) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $sql = "UPDATE notifications 
                SET status = 'read', read_at = NOW() 
                WHERE user_id = :user_id AND status = 'new'";
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erreur lors du marquage de toutes les notifications comme lues: " . $e->getMessage());
        return false;
    }
}

/**
 * Formate un timestamp en texte indiquant le temps écoulé
 * @param int $timestamp Timestamp à formater
 * @return string Texte formaté (ex: "il y a 5 minutes")
 */
function format_time_ago($timestamp) {
    $current_time = time();
    $time_difference = $current_time - $timestamp;
    
    if ($time_difference < 60) {
        return "À l'instant";
    } elseif ($time_difference < 3600) {
        $minutes = floor($time_difference / 60);
        return "Il y a " . $minutes . " minute" . ($minutes > 1 ? "s" : "");
    } elseif ($time_difference < 86400) {
        $hours = floor($time_difference / 3600);
        return "Il y a " . $hours . " heure" . ($hours > 1 ? "s" : "");
    } elseif ($time_difference < 604800) {
        $days = floor($time_difference / 86400);
        return "Il y a " . $days . " jour" . ($days > 1 ? "s" : "");
    } elseif ($time_difference < 2592000) {
        $weeks = floor($time_difference / 604800);
        return "Il y a " . $weeks . " semaine" . ($weeks > 1 ? "s" : "");
    } else {
        return date('d/m/Y', $timestamp);
    }
}

/**
 * Compte le nombre de notifications non lues pour un utilisateur
 * @param int $user_id ID de l'utilisateur
 * @return int Nombre de notifications non lues
 */
function count_unread_notifications($user_id) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'new'");
        $stmt->execute([$user_id]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des notifications non lues: " . $e->getMessage());
        return 0;
    }
}

/**
 * Initialise les préférences de notification par défaut pour un utilisateur
 */
function set_default_notification_preferences($user_id) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $stmt = $shop_pdo->prepare("SELECT type_code FROM notification_types");
        $stmt->execute();
        $notification_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($notification_types as $type) {
            $stmt = $shop_pdo->prepare("
                SELECT id FROM notification_preferences 
                WHERE user_id = ? AND type_notification = ?
            ");
            $stmt->execute([$user_id, $type]);
            
            if (!$stmt->fetch()) {
                $stmt = $shop_pdo->prepare("
                    INSERT INTO notification_preferences 
                    (user_id, type_notification, active, email_notification, push_notification) 
                    VALUES (?, ?, 1, 0, 1)
                ");
                $stmt->execute([$user_id, $type]);
            }
        }
    } catch (PDOException $e) {
        error_log("Error setting default notification preferences: " . $e->getMessage());
    }
}

/**
 * Récupère les préférences de notification d'un utilisateur
 */
function get_notification_preferences($user_id) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $stmt = $shop_pdo->prepare("
            SELECT * FROM notification_preferences 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting notification preferences: " . $e->getMessage());
        return [];
    }
}

/**
 * Met à jour une préférence de notification
 */
function update_notification_preference($user_id, $type, $active, $email, $push) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $stmt = $shop_pdo->prepare("
            SELECT id FROM notification_preferences 
            WHERE user_id = ? AND type_notification = ?
        ");
        $stmt->execute([$user_id, $type]);
        
        if ($stmt->fetch()) {
            $stmt = $shop_pdo->prepare("
                UPDATE notification_preferences 
                SET active = ?, email_notification = ?, push_notification = ? 
                WHERE user_id = ? AND type_notification = ?
            ");
            $stmt->execute([$active, $email, $push, $user_id, $type]);
        } else {
            $stmt = $shop_pdo->prepare("
                INSERT INTO notification_preferences 
                (user_id, type_notification, active, email_notification, push_notification) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $type, $active, $email, $push]);
        }
    } catch (PDOException $e) {
        error_log("Error updating notification preference: " . $e->getMessage());
    }
}

/**
 * Récupère les statistiques des notifications par type pour les derniers jours
 * @param int $user_id ID de l'utilisateur
 * @param int $days Nombre de jours en arrière
 * @return array
 */
function get_notification_stats($user_id, $days = 7) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $sql = "SELECT notification_type, 
                COUNT(*) as total, 
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as `read`
                FROM notifications 
                WHERE user_id = :user_id 
                AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY notification_type";
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':days', (int)$days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur get_notification_stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Calcule le temps écoulé depuis une date donnée (Alias pour format_time_ago)
 * @param string $datetime Date au format Y-m-d H:i:s
 * @param bool $full Optionnel, pour plus de détails (non utilisé ici)
 * @return string
 */
function time_elapsed_string($datetime, $full = false) {
    if (empty($datetime)) return "Date inconnue";
    $timestamp = strtotime($datetime);
    if (!$timestamp) return "Date invalide";
    return format_time_ago($timestamp);
}

// ========================================
// ROLE-BASED NOTIFICATION PREFERENCES
// ========================================

/**
 * Get notification preferences for a specific role group
 * @param string $role_group 'admin' or 'technicien'
 * @return array
 */
function get_role_notification_preferences($role_group) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $stmt = $shop_pdo->prepare("
            SELECT * FROM notification_role_preferences 
            WHERE role_group = ?
        ");
        $stmt->execute([$role_group]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting role notification preferences: " . $e->getMessage());
        return [];
    }
}

/**
 * Set default role preferences if they don't exist
 * @param string $role_group 'admin' or 'technicien'
 */
function set_default_role_notification_preferences($role_group) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $stmt = $shop_pdo->prepare("SELECT type_code FROM notification_types");
        $stmt->execute();
        $notification_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($notification_types as $type) {
            $stmt = $shop_pdo->prepare("
                SELECT id FROM notification_role_preferences 
                WHERE role_group = ? AND type_notification = ?
            ");
            $stmt->execute([$role_group, $type]);
            
            if (!$stmt->fetch()) {
                $stmt = $shop_pdo->prepare("
                    INSERT INTO notification_role_preferences 
                    (role_group, type_notification, active, email_notification, push_notification) 
                    VALUES (?, ?, 1, 0, 1)
                ");
                $stmt->execute([$role_group, $type]);
            }
        }
    } catch (PDOException $e) {
        error_log("Error setting default role notification preferences: " . $e->getMessage());
    }
}

/**
 * Update a role notification preference
 * @param string $role_group 'admin' or 'technicien'
 * @param string $type Notification type code
 * @param int $active
 * @param int $email
 * @param int $push
 */
function update_role_notification_preference($role_group, $type, $active, $email, $push) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $stmt = $shop_pdo->prepare("
            SELECT id FROM notification_role_preferences 
            WHERE role_group = ? AND type_notification = ?
        ");
        $stmt->execute([$role_group, $type]);
        
        if ($stmt->fetch()) {
            $stmt = $shop_pdo->prepare("
                UPDATE notification_role_preferences 
                SET active = ?, email_notification = ?, push_notification = ?, updated_at = NOW()
                WHERE role_group = ? AND type_notification = ?
            ");
            $stmt->execute([$active, $email, $push, $role_group, $type]);
        } else {
            $stmt = $shop_pdo->prepare("
                INSERT INTO notification_role_preferences 
                (role_group, type_notification, active, email_notification, push_notification) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$role_group, $type, $active, $email, $push]);
        }
    } catch (PDOException $e) {
        error_log("Error updating role notification preference: " . $e->getMessage());
    }
}

/**
 * Apply role preferences to all users of that role
 * @param string $role_group 'admin' or 'technicien'
 * @return int Number of users updated
 */
function apply_role_preferences_to_users($role_group) {
    $shop_pdo = getShopDBConnection();
    $updated_count = 0;
    
    try {
        // Get all users with this role
        $stmt = $shop_pdo->prepare("SELECT id FROM users WHERE role = ?");
        $stmt->execute([$role_group]);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get role preferences
        $role_prefs = get_role_notification_preferences($role_group);
        
        foreach ($users as $user_id) {
            foreach ($role_prefs as $pref) {
                update_notification_preference(
                    $user_id, 
                    $pref['type_notification'], 
                    $pref['active'], 
                    $pref['email_notification'], 
                    $pref['push_notification']
                );
            }
            $updated_count++;
        }
        
        return $updated_count;
    } catch (PDOException $e) {
        error_log("Error applying role preferences to users: " . $e->getMessage());
        return 0;
    }
}

 