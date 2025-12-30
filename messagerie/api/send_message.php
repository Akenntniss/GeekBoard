<?php
/**
 * API - Envoyer un message dans une conversation
 */

// Initialiser la session via la configuration globale AVANT database.php
require_once __DIR__ . '/../../config/session_config.php';

// Activer l'affichage des erreurs pour le débogage (mais pas dans la sortie standard pour JSON)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Inclure la configuration de base de données
require_once '../../config/database.php';

// Obtenir la connexion à la base de données de la boutique
$shop_pdo = getShopDBConnection();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Inclure les fonctions
require_once '../includes/functions.php';

// DEBUG: Log everything
$logFile = __DIR__ . '/../debug_send.log';
$debugData = [
    'time' => date('Y-m-d H:i:s'),
    'POST' => $_POST,
    'JSON_INPUT' => json_decode(file_get_contents('php://input'), true),
    'SESSION_ROLE' => $_SESSION['role'] ?? 'NULL',
    'SESSION_ID' => $_SESSION['user_id'] ?? 'NULL'
];
file_put_contents($logFile, print_r($debugData, true), FILE_APPEND);

// Récupérer les données
$conversation_id = null;
$contenu = '';

try {
    // Si c'est un formulaire normal
    if (isset($_POST['conversation_id'])) {
        $conversation_id = (int)$_POST['conversation_id'];
        $contenu = isset($_POST['contenu']) ? trim($_POST['contenu']) : '';
    } 
    // Si c'est une requête JSON
    else {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input && isset($input['conversation_id'])) {
            $conversation_id = (int)$input['conversation_id'];
            $contenu = isset($input['contenu']) ? trim($input['contenu']) : '';
        }
    }
    
    // Validation des données
    if (!$conversation_id) {
        throw new Exception('ID de conversation manquant ou invalide');
    }
    
    // Vérifier l'accès à la conversation
    $access = user_has_conversation_access($_SESSION['user_id'], $conversation_id);
    if (!$access) {
        throw new Exception('Accès refusé à cette conversation');
    }
    
    // Envoyer le message
    global $shop_pdo;
    
    // Vérifier si une signature est demandée
    $requires_signature = 0;
    $req_sig_input = isset($_POST['requires_signature']) ? $_POST['requires_signature'] : (isset($input['requires_signature']) ? $input['requires_signature'] : 0);
    
    if ($req_sig_input == 1) {
        // Vérifier le rôle utilisateur
        $user_role = $_SESSION['role'] ?? null;
        
        if (!$user_role) {
            $stmt = $shop_pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_role = $stmt->fetchColumn();
        }
        
        if (in_array(strtolower($user_role), ['admin', 'superadmin'])) {
            $requires_signature = 1;
        }
    }
    
    // DEBUG: Log calculated flag
    file_put_contents($logFile, "Calculated requires_signature: $requires_signature\n", FILE_APPEND);

    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    // Insérer le message (en utilisant directement PDO au lieu de la fonction)
    $stmt = $shop_pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, contenu, type, date_envoi, requires_signature)
        VALUES (:conversation_id, :sender_id, :contenu, 'text', NOW(), :requires_signature)
    ");
    
    $stmt->execute([
        ':conversation_id' => $conversation_id,
        ':sender_id' => $_SESSION['user_id'],
        ':contenu' => $contenu,
        ':requires_signature' => $requires_signature
    ]);
    
    $message_id = $shop_pdo->lastInsertId();
    
    // Mettre à jour la dernière activité de la conversation
    $stmt = $shop_pdo->prepare("
        UPDATE conversations 
        SET derniere_activite = NOW() 
        WHERE id = :conversation_id
    ");
    
    $stmt->execute([':conversation_id' => $conversation_id]);
    
    // Marquer le message comme lu par l'expéditeur
    $stmt = $shop_pdo->prepare("
        INSERT IGNORE INTO message_reads (message_id, user_id, date_lecture)
        VALUES (:message_id, :user_id, NOW())
    ");
    
    $stmt->execute([
        ':message_id' => $message_id,
        ':user_id' => $_SESSION['user_id']
    ]);
    
    // Valider la transaction
    $shop_pdo->commit();
    
    // === ENVOI NOTIFICATION PUSH ===
    try {
        require_once '../../includes/NotificationService.php';
        
        // Récupérer les participants (destinataires) - tous sauf l'expéditeur
        $stmt_recipients = $shop_pdo->prepare("
            SELECT user_id 
            FROM conversation_participants 
            WHERE conversation_id = ? AND user_id != ?
        ");
        $stmt_recipients->execute([$conversation_id, $_SESSION['user_id']]);
        $recipients = $stmt_recipients->fetchAll(PDO::FETCH_COLUMN);
        
        // Récupérer le nom de l'expéditeur
        $stmt_sender = $shop_pdo->prepare("SELECT full_name, username FROM users WHERE id = ?");
        $stmt_sender->execute([$_SESSION['user_id']]);
        $sender_data = $stmt_sender->fetch(PDO::FETCH_ASSOC);
            $sender_name = $sender_data['full_name'] ?: $sender_data['username'] ?: 'Un utilisateur';
            
            // Envoyer une notification push à chaque destinataire
            foreach ($recipients as $recipient_id) {
                // Determine notification type based on signature requirement
                $notif_type = ($requires_signature == 1) ? 'message_admin_signature' : 'new_message';
                
                $title = ($requires_signature == 1) ? "Signature requise" : "Nouveau message";
                $preview = mb_strlen($contenu) > 50 ? mb_substr($contenu, 0, 50) . '...' : $contenu;
                $body = "$sender_name: $preview";
                
                NotificationService::send($recipient_id, $notif_type, $title, $body, [
                    'url' => "/index.php?page=messagerie&conversation=$conversation_id",
                    'related_id' => $message_id,
                    'related_type' => 'message'
                ]);
            }
        
        error_log("NOTIFICATION: Message notification sent for message #$message_id to " . count($recipients) . " recipient(s)");
    } catch (Exception $e) {
        error_log("NOTIFICATION ERROR (message): " . $e->getMessage());
    }
    
    // Créer des notifications pour tous les participants (sauf l'expéditeur)
    try {
        $stmt_participants = $shop_pdo->prepare("
            SELECT user_id 
            FROM conversation_participants 
            WHERE conversation_id = :conversation_id 
              AND user_id != :sender_id
        ");
        
        $stmt_participants->execute([
            ':conversation_id' => $conversation_id,
            ':sender_id' => $_SESSION['user_id']
        ]);
        
        $participants = $stmt_participants->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($participants)) {
            $stmt_notif = $shop_pdo->prepare("
                INSERT INTO notification_messagerie (user_id, conversation_id, message_id, lu, date_creation)
                VALUES (:user_id, :conversation_id, :message_id, 0, NOW())
            ");
            
            foreach ($participants as $participant_id) {
                $stmt_notif->execute([
                    ':user_id' => $participant_id,
                    ':conversation_id' => $conversation_id,
                    ':message_id' => $message_id
                ]);
            }
        }
    } catch (Exception $e_notif) {
        // On ne bloque pas l'envoi du message si la notification échoue
        if (function_exists('log_error')) {
            log_error('Erreur lors de la création des notifications', $e_notif->getMessage());
        }
    }
    
    // Réponse de succès
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Message envoyé avec succès',
        'message_id' => $message_id
    ]);
    
} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
        $shop_pdo->rollback();
    }
    
    // Journaliser l'erreur
    log_error('Erreur lors de l\'envoi du message', $e->getMessage() . ' - ' . $e->getTraceAsString());
    
    // Réponse d'erreur
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()
    ]);
}
exit;

/**
 * Crée une miniature d'une image
 * 
 * @param string $source Chemin de l'image source
 * @param string $destination Chemin de destination de la miniature
 * @param int $width Largeur maximale
 * @param int $height Hauteur maximale
 * @return bool Succès ou échec
 */
function create_thumbnail($source, $destination, $width, $height) {
    if (!function_exists('imagecreatetruecolor')) {
        return false;
    }
    
    // Obtenir les dimensions de l'image
    list($img_width, $img_height, $img_type) = getimagesize($source);
    
    // Calculer les dimensions de la miniature en conservant le ratio
    $ratio = min($width / $img_width, $height / $img_height);
    $new_width = $img_width * $ratio;
    $new_height = $img_height * $ratio;
    
    // Créer une image vide avec les nouvelles dimensions
    $thumb = imagecreatetruecolor($new_width, $new_height);
    
    // Charger l'image source selon son type
    switch ($img_type) {
        case IMAGETYPE_JPEG:
            $src_img = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $src_img = imagecreatefrompng($source);
            // Préserver la transparence
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            break;
        case IMAGETYPE_GIF:
            $src_img = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Redimensionner l'image
    imagecopyresampled($thumb, $src_img, 0, 0, 0, 0, $new_width, $new_height, $img_width, $img_height);
    
    // Sauvegarder la miniature selon le type de l'image source
    switch ($img_type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $destination, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumb, $destination, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $destination);
            break;
        default:
            return false;
    }
    
    // Libérer la mémoire
    imagedestroy($thumb);
    imagedestroy($src_img);
    
    return true;
} 