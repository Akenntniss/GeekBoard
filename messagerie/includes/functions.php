<?php
/**
 * Fonctions du module de messagerie
 * Version 2.0
 */

// Assurer que la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la connexion à la base de données s'il n'est pas déjà inclus
require_once __DIR__ . '/../../config/database.php';

// Obtenir la connexion à la base de données de la boutique
$shop_pdo = getShopDBConnection();

/**
 * Récupère les conversations d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param array $filters Filtres optionnels (type, favorites, archived, etc.)
 * @return array Liste des conversations
 */
function get_user_conversations($user_id, $filters = []) {
    global $shop_pdo;
    
    try {
        // Ajouter un log détaillé pour le débogage
        error_log("DEBUG - Récupération des conversations pour l'utilisateur $user_id avec filtres: " . json_encode($filters));
        
        // Version simplifiée sans les fonctions JSON pour une meilleure compatibilité
        // Ignorer les contraintes de date dans la requête principale
        $query = "
            SELECT c.id, c.titre, c.type, c.created_by, c.date_creation, c.derniere_activite,
                cp.role, cp.est_favoris, cp.est_archive, cp.notification_mute, cp.date_derniere_lecture,
                u.full_name AS created_by_name,
                0 AS unread_count
            FROM conversations c
            JOIN conversation_participants cp ON c.id = cp.conversation_id
            LEFT JOIN users u ON c.created_by = u.id
            WHERE cp.user_id = :user_id
        ";
        
        $params = [':user_id' => $user_id];
        
        // Appliquer les filtres
        if (!empty($filters)) {
            if (isset($filters['type']) && !empty($filters['type'])) {
                $query .= " AND c.type = :type";
                $params[':type'] = $filters['type'];
            }
            
            if (isset($filters['favorites']) && $filters['favorites']) {
                $query .= " AND cp.est_favoris = 1";
            }
            
            if (isset($filters['archived'])) {
                if ($filters['archived']) {
                    $query .= " AND cp.est_archive = 1";
                } else {
                    $query .= " AND cp.est_archive = 0";
                }
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $query .= " AND (
                    c.titre LIKE :search
                    OR EXISTS (
                        SELECT 1 FROM messages m2
                        WHERE m2.conversation_id = c.id
                        AND m2.contenu LIKE :search_content
                    )
                )";
                $search_term = "%" . $filters['search'] . "%";
                $params[':search'] = $search_term;
                $params[':search_content'] = $search_term;
            }
        }
        
        // Tri des conversations par ID (pour éviter les problèmes de date)
        $query .= " ORDER BY cp.est_favoris DESC, c.id DESC";
        
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute($params);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Nombre de conversations récupérées: " . count($conversations));
        
        // Traiter les résultats
        foreach ($conversations as &$conversation) {
            try {
                // Récupérer le dernier message séparément
                $last_message = get_last_message($conversation['id']);
                $conversation['last_message'] = $last_message;
                
                // Calculer les messages non lus pour cette conversation manuellement
                // car nous avons ignoré le calcul dans la requête principale
                $unread_count = 0;
                if (isset($conversation['date_derniere_lecture'])) {
                    $last_read = $conversation['date_derniere_lecture'];
                    // Si null, utiliser une date ancienne
                    if (!$last_read) $last_read = '2000-01-01 00:00:00';
                    
                    $query_unread = "
                        SELECT COUNT(*) as count
                        FROM messages m
                        WHERE m.conversation_id = :conversation_id
                        AND m.date_envoi > :last_read
                        AND (m.sender_id IS NULL OR m.sender_id != :user_id)
                        AND m.est_supprime = 0
                    ";
                    
                    $stmt_unread = $shop_pdo->prepare($query_unread);
                    $stmt_unread->execute([
                        ':conversation_id' => $conversation['id'],
                        ':user_id' => $user_id,
                        ':last_read' => $last_read
                    ]);
                    
                    $unread_data = $stmt_unread->fetch(PDO::FETCH_ASSOC);
                    $unread_count = $unread_data['count'];
                }
                $conversation['unread_count'] = $unread_count;
                
                // Obtenir les participants en mode robuste
                $participants = get_conversation_participants($conversation['id']);
                $conversation['participants'] = $participants;
                
                // Corriger les dates si elles sont problématiques
                if (isset($conversation['date_creation']) && strtotime($conversation['date_creation']) > time()) {
                    // Si la date est dans le futur, la remplacer par la date actuelle
                    $conversation['date_creation'] = date('Y-m-d H:i:s');
                }
                
                if (isset($conversation['derniere_activite']) && strtotime($conversation['derniere_activite']) > time()) {
                    // Si la date est dans le futur, la remplacer par la date actuelle
                    $conversation['derniere_activite'] = date('Y-m-d H:i:s');
                }
            } catch (Exception $e) {
                error_log("Erreur lors du traitement de la conversation {$conversation['id']}: " . $e->getMessage());
                // Assurer des valeurs par défaut pour éviter les erreurs
                if (!isset($conversation['last_message'])) {
                    $conversation['last_message'] = null;
                }
                if (!isset($conversation['participants'])) {
                    $conversation['participants'] = [];
                }
            }
        }
        unset($conversation); // Rompre la référence

        // Filtrer par non-lu si demandé (fait en PHP car le calcul est complexe)
        if (isset($filters['unread']) && $filters['unread']) {
            $conversations = array_filter($conversations, function($c) {
                return isset($c['unread_count']) && $c['unread_count'] > 0;
            });
            // Réindexer le tableau
            $conversations = array_values($conversations);
        }
        
        error_log("Conversations traitées: " . count($conversations));
        return $conversations;
    } catch (PDOException $e) {
        log_error('Erreur lors de la récupération des conversations', $e->getMessage() . ' - ' . $e->getTraceAsString());
        error_log("Erreur SQL: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère le dernier message d'une conversation
 * 
 * @param int $conversation_id ID de la conversation
 * @return array|null Informations sur le dernier message ou null si aucun message
 */
function get_last_message($conversation_id) {
    global $shop_pdo;
    
    try {
        // Log pour le débogage
        error_log("Récupération du dernier message pour la conversation $conversation_id");
        
        $query = "
            SELECT m.id, m.sender_id, m.contenu, m.type, m.date_envoi, u.full_name AS sender_name
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = :conversation_id
            AND m.est_supprime = 0
            ORDER BY m.id DESC
            LIMIT 1
        ";
        
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([':conversation_id' => $conversation_id]);
        
        if ($stmt->rowCount() > 0) {
            $message = $stmt->fetch(PDO::FETCH_ASSOC);
            // Formater la date pour l'affichage
            $message['formatted_date'] = format_message_date($message['date_envoi']);
            
            // Corriger la date si elle est dans le futur
            if (strtotime($message['date_envoi']) > time()) {
                $message['date_envoi'] = date('Y-m-d H:i:s');
                $message['formatted_date'] = format_message_date($message['date_envoi']);
            }
            
            error_log("Dernier message trouvé : ID {$message['id']}");
            return $message;
        }
        
        error_log("Aucun message trouvé pour la conversation $conversation_id");
        return null;
    } catch (PDOException $e) {
        log_error('Erreur lors de la récupération du dernier message', $e->getMessage());
        error_log("Erreur lors de la récupération du dernier message : " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère les messages d'une conversation
 * 
 * @param int $conversation_id ID de la conversation
 * @param int $user_id ID de l'utilisateur actuel
 * @param int $limit Nombre de messages à récupérer
 * @param int $offset Offset pour la pagination
 * @return array Messages
 */
function get_conversation_messages($conversation_id, $user_id, $limit = 50, $offset = 0) {
    global $shop_pdo;
    
    // Vérifier l'accès à la conversation
    if (!user_has_conversation_access($user_id, $conversation_id)) {
        return ['error' => 'Accès refusé à cette conversation'];
    }
    
    try {
        // Mettre à jour la date de dernière lecture
        update_last_read($conversation_id, $user_id);
        
        // Version simplifiée sans fonctions JSON avancées
        $query = "
            SELECT m.*, u.full_name AS sender_name, ms.signed_at as my_signature_date
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.id
            LEFT JOIN message_signatures ms ON m.id = ms.message_id AND ms.user_id = :user_id
            WHERE m.conversation_id = :conversation_id
            AND m.est_supprime = 0
            ORDER BY m.date_envoi DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $shop_pdo->prepare($query);
        $stmt->bindValue(':conversation_id', $conversation_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Traiter les résultats
        foreach ($messages as &$message) {
            $message['is_mine'] = $message['sender_id'] == $user_id;
            $message['is_signed'] = !empty($message['my_signature_date']);
            $message['signed_at'] = $message['my_signature_date'];
            
            // Récupérer les pièces jointes séparément
            $attachments_query = "
                SELECT id, file_path, file_name, file_type, file_size, thumbnail_path, est_image
                FROM message_attachments
                WHERE message_id = :message_id
            ";
            $stmt_attachments = $shop_pdo->prepare($attachments_query);
            $stmt_attachments->execute([':message_id' => $message['id']]);
            $message['attachments'] = $stmt_attachments->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupérer le nombre de lectures
            $reads_query = "
                SELECT COUNT(*) as count
                FROM message_reads
                WHERE message_id = :message_id
            ";
            $stmt_reads = $shop_pdo->prepare($reads_query);
            $stmt_reads->execute([':message_id' => $message['id']]);
            $message['read_count'] = $stmt_reads->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Récupérer les réactions
            $reactions_query = "
                SELECT reaction, COUNT(*) as count
                FROM message_reactions
                WHERE message_id = :message_id
                GROUP BY reaction
            ";
            $stmt_reactions = $shop_pdo->prepare($reactions_query);
            $stmt_reactions->execute([':message_id' => $message['id']]);
            $reactions_data = $stmt_reactions->fetchAll(PDO::FETCH_ASSOC);
            
            $reactions = [];
            foreach ($reactions_data as $reaction) {
                $reactions[$reaction['reaction']] = $reaction['count'];
            }
            $message['reactions'] = $reactions;
            
            // Formater la date
            $message['formatted_date'] = format_message_date($message['date_envoi']);
        }
        
        // Inverser pour avoir du plus ancien au plus récent
        return array_reverse($messages);
    } catch (PDOException $e) {
        log_error('Erreur lors de la récupération des messages', $e->getMessage());
        error_log("Erreur lors de la récupération des messages: " . $e->getMessage());
        return ['error' => 'Erreur lors de la récupération des messages'];
    }
}

/**
 * Crée une nouvelle conversation
 *
 * @param string $titre Titre de la conversation
 * @param string $type Type de conversation (direct, groupe, annonce)
 * @param int $creator_id ID du créateur
 * @param array $participants IDs des participants
 * @param string|null $objet Objet de la conversation (optionnel)
 * @param string|null $priorite Priorité de la conversation (optionnel)
 * @return int|array ID de la conversation créée ou tableau d'erreur
 */
function create_conversation($titre, $type, $creator_id, $participants, $objet = null, $priorite = 'normale') {
    $pdo = getShopDBConnection();
    
    // Validation
    if (empty($participants)) {
        return ['error' => 'Aucun participant sélectionné'];
    }
    
    // Si c'est un message direct, vérifier si une conversation existe déjà
    if ($type === 'direct' && count($participants) === 1) {
        $participant_id = $participants[0];
        
        // Chercher une conversation directe existante entre ces deux utilisateurs
        $stmt = $pdo->prepare("
            SELECT c.id 
            FROM conversations c
            JOIN conversation_participants cp1 ON c.id = cp1.conversation_id AND cp1.user_id = ?
            JOIN conversation_participants cp2 ON c.id = cp2.conversation_id AND cp2.user_id = ?
            WHERE c.type = 'direct'
        ");
        $stmt->execute([$creator_id, $participant_id]);
        $existing = $stmt->fetchColumn();
        
        if ($existing) {
            // Si l'objet est différent, on pourrait vouloir créer une nouvelle conversation
            // Mais pour simplifier, on retourne l'existante pour l'instant
            // Sauf si un objet est spécifié explicitement, dans ce cas on crée un nouveau "thread" style email
            if (empty($objet)) {
                return $existing;
            }
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Créer la conversation
        // Note: On utilise IGNORE ou on vérifie les colonnes avant si on n'est pas sûr de la structure
        // Mais ici on suppose que la migration a été faite
        $stmt = $pdo->prepare("
            INSERT INTO conversations (titre, type, created_by, date_creation, derniere_activite, objet, priorite, statut) 
            VALUES (:titre, :type, :created_by, NOW(), NOW(), :objet, :priorite, 'active')
        ");
        
        $stmt->execute([
            ':titre' => $titre,
            ':type' => $type,
            ':created_by' => $creator_id,
            ':objet' => $objet,
            ':priorite' => $priorite ?: 'normale'
        ]);
        
        $conversation_id = $pdo->lastInsertId();
        
        // Ajouter le créateur aux participants
        $stmt = $pdo->prepare("
            INSERT INTO conversation_participants (conversation_id, user_id, role, date_ajout)
            VALUES (?, ?, 'admin', NOW())
        ");
        $stmt->execute([$conversation_id, $creator_id]);
        
        // Ajouter les autres participants
        $stmt = $pdo->prepare("
            INSERT INTO conversation_participants (conversation_id, user_id, role, date_ajout)
            VALUES (:conv_id, :user_id, :role, NOW())
        ");
        
        foreach ($participants as $user_id) {
            // Éviter d'ajouter le créateur deux fois
            if ($user_id != $creator_id) {
                $stmt->execute([
                    ':conv_id' => $conversation_id,
                    ':user_id' => $user_id,
                    ':role' => ($type === 'groupe' ? 'membre' : 'membre')
                ]);
            }
        }
        
        $pdo->commit();
        return $conversation_id;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['error' => 'Erreur lors de la création de la conversation: ' . $e->getMessage()];
    }
}

/**
 * Envoie un message dans une conversation
 * 
 * @param int $conversation_id ID de la conversation
 * @param int $sender_id ID de l'expéditeur
 * @param string $contenu Contenu du message
 * @param string $type Type de message (text, file, image, system, info)
 * @param array $attachments Pièces jointes (optionnel)
 * @return int|array ID du message créé ou tableau d'erreur
 */
function send_message($conversation_id, $sender_id, $contenu, $type = 'text', $attachments = [], $requires_signature = 0) {
    global $shop_pdo;
    
    // Vérifier l'accès à la conversation
    if (!user_has_conversation_access($sender_id, $conversation_id)) {
        return ['error' => 'Accès refusé à cette conversation'];
    }
    
    try {
        $shop_pdo->beginTransaction();
        
        // Insérer le message
        $stmt = $shop_pdo->prepare("
            INSERT INTO messages (conversation_id, sender_id, contenu, type, date_envoi, requires_signature)
            VALUES (:conversation_id, :sender_id, :contenu, :type, NOW(), :requires_signature)
        ");
        
        $stmt->execute([
            ':conversation_id' => $conversation_id,
            ':sender_id' => $sender_id,
            ':contenu' => $contenu,
            ':type' => $type,
            ':requires_signature' => $requires_signature
        ]);
        
        $message_id = $shop_pdo->lastInsertId();
        
        // Ajouter les pièces jointes si présentes
        if (!empty($attachments) && is_array($attachments)) {
            foreach ($attachments as $attachment) {
                if (
                    isset($attachment['file_path']) && 
                    isset($attachment['file_name']) && 
                    isset($attachment['file_type']) && 
                    isset($attachment['file_size'])
                ) {
                    // Déterminer si c'est une image
                    $est_image = 0;
                    if (strpos($attachment['file_type'], 'image/') === 0) {
                        $est_image = 1;
                    }
                    
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO message_attachments (
                            message_id, file_path, file_name, file_type, 
                            file_size, thumbnail_path, est_image, date_upload
                        )
                        VALUES (
                            :message_id, :file_path, :file_name, :file_type,
                            :file_size, :thumbnail_path, :est_image, NOW()
                        )
                    ");
                    
                    $stmt->execute([
                        ':message_id' => $message_id,
                        ':file_path' => $attachment['file_path'],
                        ':file_name' => $attachment['file_name'],
                        ':file_type' => $attachment['file_type'],
                        ':file_size' => $attachment['file_size'],
                        ':thumbnail_path' => $attachment['thumbnail_path'] ?? null,
                        ':est_image' => $est_image
                    ]);
                }
            }
        }
        
        // Mettre à jour la date de dernière activité de la conversation
        $stmt = $shop_pdo->prepare("
            UPDATE conversations 
            SET derniere_activite = NOW() 
            WHERE id = :conversation_id
        ");
        
        $stmt->execute([
            ':conversation_id' => $conversation_id
        ]);
        
        // Marquer comme lu pour l'expéditeur
        mark_message_as_read($message_id, $sender_id);
        
        $shop_pdo->commit();
        
        // Retourner le message avec ses infos complètes
        $messages = get_conversation_messages($conversation_id, $sender_id, 1, 0);
        if (isset($messages[0])) {
            return $messages[0];
        }
        
        return $message_id;
    } catch (PDOException $e) {
        $shop_pdo->rollBack();
        log_error('Erreur lors de l\'envoi du message', $e->getMessage());
        return ['error' => 'Erreur lors de l\'envoi du message'];
    }
}

/**
 * Vérifie si un utilisateur a accès à une conversation
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $conversation_id ID de la conversation
 * @return bool True si l'utilisateur a accès, false sinon
 */
function user_has_conversation_access($user_id, $conversation_id) {
    global $shop_pdo;
    
    try {
        $stmt = $shop_pdo->prepare("
            SELECT 1 FROM conversation_participants
            WHERE conversation_id = :conversation_id
            AND user_id = :user_id
        ");
        
        $stmt->execute([
            ':conversation_id' => $conversation_id,
            ':user_id' => $user_id
        ]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        log_error('Erreur lors de la vérification de l\'accès à la conversation', $e->getMessage());
        return false;
    }
}

/**
 * Récupère les participants d'une conversation
 * 
 * @param int $conversation_id ID de la conversation
 * @return array Liste des participants
 */
function get_conversation_participants($conversation_id) {
    global $shop_pdo;
    
    try {
        // Log pour le débogage
        error_log("Récupération des participants pour la conversation $conversation_id");
        
        // Version plus robuste qui fonctionne même si certains utilisateurs n'existent plus
        $stmt = $shop_pdo->prepare("
            SELECT cp.conversation_id, cp.user_id, cp.role, cp.date_ajout, 
                   cp.date_derniere_lecture, cp.est_favoris, cp.est_archive, 
                   cp.notification_mute, 
                   u.full_name, u.username
            FROM conversation_participants cp
            LEFT JOIN users u ON cp.user_id = u.id
            WHERE cp.conversation_id = :conversation_id
        ");
        
        $stmt->execute([
            ':conversation_id' => $conversation_id
        ]);
        
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Nombre de participants trouvés: " . count($participants));
        
        // Assurer des valeurs par défaut pour les champs manquants
        foreach ($participants as &$participant) {
            if (empty($participant['full_name'])) {
                // Essayer d'abord le nom d'utilisateur comme fallback
                if (!empty($participant['username'])) {
                    $participant['full_name'] = $participant['username'];
                } else {
                    $participant['full_name'] = 'Utilisateur ' . $participant['user_id'];
                }
            }
        }
        
        // Vérifions si le tableau est vide
        if (count($participants) === 0) {
            error_log("AVERTISSEMENT: Aucun participant trouvé pour la conversation $conversation_id");
        }
        
        return $participants;
    } catch (PDOException $e) {
        // Enregistrer l'erreur détaillée
        log_error('Erreur lors de la récupération des participants', $e->getMessage() . "\nRequête: conversation_id = " . $conversation_id);
        error_log("Erreur SQL lors de la récupération des participants: " . $e->getMessage());
        
        // En cas d'échec, retourner un tableau vide plutôt que de faire échouer toute la fonction
        return [];
    }
}

/**
 * Marque un message comme lu par un utilisateur
 * 
 * @param int $message_id ID du message
 * @param int $user_id ID de l'utilisateur
 * @return bool Succès ou échec
 */
function mark_message_as_read($message_id, $user_id) {
    global $shop_pdo;
    
    try {
        $stmt = $shop_pdo->prepare("
            INSERT IGNORE INTO message_reads (message_id, user_id, date_lecture)
            VALUES (:message_id, :user_id, NOW())
        ");
        
        $stmt->execute([
            ':message_id' => $message_id,
            ':user_id' => $user_id
        ]);
        
        return true;
    } catch (PDOException $e) {
        log_error('Erreur lors du marquage de message comme lu', $e->getMessage());
        return false;
    }
}

/**
 * Met à jour la date de dernière lecture d'une conversation
 * 
 * @param int $conversation_id ID de la conversation
 * @param int $user_id ID de l'utilisateur
 * @return bool Succès ou échec
 */
function update_last_read($conversation_id, $user_id) {
    global $shop_pdo;
    
    try {
        $stmt = $shop_pdo->prepare("
            UPDATE conversation_participants
            SET date_derniere_lecture = NOW()
            WHERE conversation_id = :conversation_id
            AND user_id = :user_id
        ");
        
        $stmt->execute([
            ':conversation_id' => $conversation_id,
            ':user_id' => $user_id
        ]);
        
        return true;
    } catch (PDOException $e) {
        log_error('Erreur lors de la mise à jour de la date de dernière lecture', $e->getMessage());
        return false;
    }
}

/**
 * Formate une date pour l'affichage des messages
 * 
 * @param string $date Date à formater
 * @return string Date formatée
 */
function format_message_date($date) {
    $message_date = new DateTime($date);
    $now = new DateTime();
    $yesterday = new DateTime('-1 day');
    
    // Aujourd'hui - afficher l'heure uniquement
    if ($message_date->format('Y-m-d') === $now->format('Y-m-d')) {
        return $message_date->format('H:i');
    }
    
    // Hier - afficher "Hier" + heure
    if ($message_date->format('Y-m-d') === $yesterday->format('Y-m-d')) {
        return 'Hier ' . $message_date->format('H:i');
    }
    
    // Cette semaine - afficher le jour + heure
    $diff = $now->diff($message_date);
    if ($diff->days < 7) {
        $jours_fr = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        return $jours_fr[$message_date->format('w')] . ' ' . $message_date->format('H:i');
    }
    
    // Cette année - afficher jour/mois + heure
    if ($message_date->format('Y') === $now->format('Y')) {
        return $message_date->format('d/m H:i');
    }
    
    // Autre - afficher date complète
    return $message_date->format('d/m/Y H:i');
}

/**
 * Récupère le nombre de messages non lus pour un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return int Nombre de messages non lus
 */
function count_unread_messages($user_id) {
    global $shop_pdo;
    
    try {
        $stmt = $shop_pdo->prepare("
            SELECT SUM(unread_count) as total
            FROM (
                SELECT 
                    c.id,
                    (
                        SELECT COUNT(*)
                        FROM messages m
                        WHERE m.conversation_id = c.id
                        AND m.date_envoi > COALESCE(cp.date_derniere_lecture, '2000-01-01')
                        AND (m.sender_id IS NULL OR m.sender_id != :user_id)
                        AND m.est_supprime = 0
                    ) AS unread_count
                FROM conversations c
                JOIN conversation_participants cp ON c.id = cp.conversation_id
                WHERE cp.user_id = :user_id
                AND cp.est_archive = 0
            ) as counts
        ");
        
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$result['total'];
    } catch (PDOException $e) {
        log_error('Erreur lors du comptage des messages non lus', $e->getMessage());
        return 0;
    }
}

/**
 * Enregistre une erreur dans le fichier de log
 * 
 * @param string $message Message d'erreur
 * @param string $details Détails supplémentaires
 * @return void
 */
function log_error($message, $details) {
    $log_file = __DIR__ . '/../logs/messagerie_errors.log';
    $log_dir = dirname($log_file);
    
    // Créer le répertoire des logs s'il n'existe pas
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $date = date('Y-m-d H:i:s');
    $log_entry = "[$date] $message: $details" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    // Aussi utiliser error_log natif de PHP
    error_log("Messagerie: $message - $details");
}

/**
 * Récupère les informations d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array|false Informations de l'utilisateur ou false si non trouvé
 */
function get_user_info($user_id) {
    global $shop_pdo;
    
    $query = "
        SELECT id, username, full_name, role
        FROM users
        WHERE id = :user_id
    ";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si l'utilisateur n'a pas de full_name, utiliser le username
    if ($user && empty($user['full_name'])) {
        $user['full_name'] = $user['username'];
    }
    
    return $user;
}

/**
 * Récupère les informations d'un message
 * 
 * @param int $message_id ID du message
 * @return array|false Informations du message ou false si non trouvé
 */
function get_message_info($message_id) {
    global $shop_pdo;
    
    $query = "
        SELECT m.*, 
               u.full_name AS sender_name,
               c.titre AS conversation_title
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        LEFT JOIN conversations c ON m.conversation_id = c.id
        WHERE m.id = :message_id
    ";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([':message_id' => $message_id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Récupère les utilisateurs en train de taper dans une conversation
 * 
 * @param int $conversation_id ID de la conversation
 * @param int $current_user_id ID de l'utilisateur courant (à exclure)
 * @return array Liste des utilisateurs en train de taper
 */
function get_typing_users($conversation_id, $current_user_id) {
    global $shop_pdo;
    
    // Supprimer les statuts de frappe obsolètes (plus de 6 secondes)
    $cleanupQuery = "
        DELETE FROM typing_status
        WHERE timestamp < DATE_SUB(NOW(), INTERVAL 6 SECOND)
    ";
    $shop_pdo->query($cleanupQuery);
    
    // Récupérer les utilisateurs actuellement en train de taper
    $query = "
        SELECT ts.user_id, u.full_name, ts.timestamp
        FROM typing_status ts
        JOIN users u ON ts.user_id = u.id
        WHERE ts.conversation_id = :conversation_id
        AND ts.user_id != :current_user_id
        ORDER BY ts.timestamp DESC
    ";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([
        ':conversation_id' => $conversation_id,
        ':current_user_id' => $current_user_id
    ]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les réactions à un message
 * 
 * @param int $message_id ID du message
 * @return array Liste des réactions
 */
function get_message_reactions($message_id) {
    global $shop_pdo;
    
    $query = "
        SELECT mr.*, u.full_name AS user_name
        FROM message_reactions mr
        JOIN users u ON mr.user_id = u.id
        WHERE mr.message_id = :message_id
        ORDER BY mr.date_reaction ASC
    ";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([':message_id' => $message_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Modifie le contenu d'un message
 * 
 * @param int $message_id ID du message
 * @param string $content Nouveau contenu
 * @param int $user_id ID de l'utilisateur qui modifie le message
 * @return bool True si la modification a réussi, false sinon
 */
function edit_message($message_id, $content, $user_id) {
    global $shop_pdo;
    
    // Vérifier que l'utilisateur est bien l'expéditeur du message
    $query = "
        SELECT sender_id
        FROM messages
        WHERE id = :message_id
    ";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([':message_id' => $message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message || $message['sender_id'] != $user_id) {
        return false;
    }
    
    // Mettre à jour le message
    $query = "
        UPDATE messages
        SET contenu = :content,
            est_modifie = 1,
            date_modification = NOW()
        WHERE id = :message_id
    ";
    
    $stmt = $shop_pdo->prepare($query);
    return $stmt->execute([
        ':message_id' => $message_id,
        ':content' => $content
    ]);
} 