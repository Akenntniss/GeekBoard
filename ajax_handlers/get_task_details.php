<?php
// Configuration et session sécurisée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Fonctions utilitaires pour les fichiers
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round(($bytes / pow($k, $i)), 2) . ' ' . $sizes[$i];
}

function getFileIcon($fileType) {
    $imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
    $documentTypes = ['pdf', 'doc', 'docx', 'txt'];
    $spreadsheetTypes = ['xlsx', 'xls'];
    $archiveTypes = ['zip', 'rar'];
    
    if (in_array(strtolower($fileType), $imageTypes)) {
        return ['icon' => 'fas fa-image', 'class' => 'image', 'color' => '#28a745'];
    } elseif (in_array(strtolower($fileType), $documentTypes)) {
        return ['icon' => 'fas fa-file-alt', 'class' => 'document', 'color' => '#dc3545'];
    } elseif (in_array(strtolower($fileType), $spreadsheetTypes)) {
        return ['icon' => 'fas fa-file-excel', 'class' => 'spreadsheet', 'color' => '#198754'];
    } elseif (in_array(strtolower($fileType), $archiveTypes)) {
        return ['icon' => 'fas fa-file-archive', 'class' => 'archive', 'color' => '#fd7e14'];
    } else {
        return ['icon' => 'fas fa-file', 'class' => 'other', 'color' => '#6c757d'];
    }
}

// Permettre l'accès même sans authentification pour le debug
// if (!isset($_SESSION['user_id'])) {
//     header('Content-Type: application/json');
//     echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
//     exit;
// }

// Vérifier l'ID de la tâche
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de tâche invalide']);
    exit;
}

$task_id = intval($_GET['id']);

try {
    // Initialiser la session magasin si nécessaire
    if (!isset($_SESSION['shop_id'])) {
        initializeShopSession();
    }
    
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base du magasin. Vérifiez la configuration.');
    }
    
    // Requête pour récupérer tous les détails de la tâche
    $stmt = $shop_pdo->prepare("
        SELECT t.*, 
               e.full_name as employe_nom,
               c.full_name as createur_nom,
               DATE_FORMAT(t.date_creation, '%d/%m/%Y à %H:%i') as date_creation_formatted,
               DATE_FORMAT(t.date_limite, '%d/%m/%Y') as date_limite_formatted
        FROM taches t
        LEFT JOIN users e ON t.employe_id = e.id
        LEFT JOIN users c ON t.created_by = c.id
        WHERE t.id = ?
    ");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($task) {
        // Formater le statut pour l'affichage
        $status_display = '';
        switch($task['statut']) {
            case 'a_faire':
                $status_display = 'À faire';
                break;
            case 'en_cours':
                $status_display = 'En cours';
                break;
            case 'termine':
                $status_display = 'Terminé';
                break;
            default:
                $status_display = ucfirst($task['statut']);
        }
        
        $task['statut_display'] = $status_display;
        
        // Récupérer les pièces jointes de la tâche
        $attachments = [];
        try {
            $stmt_attachments = $shop_pdo->prepare("
                SELECT id, file_path, file_name, file_type, file_size, est_image, date_upload,
                       u.full_name as uploaded_by_name
                FROM tache_attachments ta
                LEFT JOIN users u ON ta.uploaded_by = u.id
                WHERE ta.tache_id = ?
                ORDER BY ta.date_upload ASC
            ");
            $stmt_attachments->execute([$task_id]);
            $attachments = $stmt_attachments->fetchAll(PDO::FETCH_ASSOC);
            
            // Formater les pièces jointes
            foreach ($attachments as &$attachment) {
                $attachment['file_size_formatted'] = formatFileSize($attachment['file_size']);
                $attachment['date_upload_formatted'] = date('d/m/Y à H:i', strtotime($attachment['date_upload']));
                $attachment['file_url'] = '/' . $attachment['file_path']; // URL relative
                $attachment['file_icon'] = getFileIcon($attachment['file_type']);
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des pièces jointes: " . $e->getMessage());
            // Continuer sans les pièces jointes
        }
        
        $task['attachments'] = $attachments;
        $task['attachments_count'] = count($attachments);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'task' => $task
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Tâche non trouvée'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des détails de la tâche: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des détails'
    ]);
}
?>
