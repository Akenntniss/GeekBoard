<?php
// Gestionnaire d'export dédié - Aucun HTML ici !

// Charger la configuration de base
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Charger la session et les fonctions de base
require_once BASE_PATH . '/config/session_config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Variables d'authentification
$current_user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Obtenir la connexion à la base de données
if (function_exists('getShopDBConnection')) {
    $shop_pdo = getShopDBConnection();
} else {
    // Fallback si la fonction n'existe pas
    http_response_code(500);
    die('Erreur de configuration de la base de données');
}

// Vérification de la connexion utilisateur
if (!$current_user_id) {
    // Rediriger vers la page de connexion
    header('Location: index.php');
    exit;
}

// Vérification des droits
if (!$shop_pdo || (!$is_admin && !$current_user_id)) {
    http_response_code(403);
    die('Accès refusé');
}

// Récupérer les paramètres d'export
$export_user = $_POST['export_user'] ?? '';
$export_type = $_POST['export_type'] ?? '';
$export_status = $_POST['export_status'] ?? '';
$export_date_start = $_POST['export_date_start'] ?? '';
$export_date_end = $_POST['export_date_end'] ?? '';
$export_format = $_POST['export_format'] ?? 'csv';
$orientation = $_POST['orientation'] ?? 'portrait';
$columns = $_POST['columns'] ?? ['user', 'type', 'date_start', 'status'];
$report_title = $_POST['report_title'] ?? 'Rapport des événements de présence';

try {
    // Construire la requête d'export
    $query = "
        SELECT 
            pe.id,
            COALESCE(u.full_name, u.username) as user_name,
            pt.name as type_name,
            pt.color as type_color,
            pe.date_start,
            pe.date_end,
            pe.duration_minutes,
            pe.status,
            pe.comment,
            pe.created_at,
            COALESCE(approver.full_name, approver.username) as approver_name
        FROM presence_events pe
        LEFT JOIN users u ON pe.employee_id = u.id
        LEFT JOIN presence_types pt ON pe.type_id = pt.id
        LEFT JOIN users approver ON pe.approved_by = approver.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Filtres
    if ($export_user && $export_user !== 'all') {
        $query .= " AND pe.employee_id = ?";
        $params[] = $export_user;
    } elseif (!$is_admin && $current_user_id) {
        // Si pas admin, limiter aux propres événements
        $query .= " AND pe.employee_id = ?";
        $params[] = $current_user_id;
    }
    
    if ($export_type && $export_type !== 'all') {
        $query .= " AND pe.type_id = ?";
        $params[] = $export_type;
    }
    
    if ($export_status && $export_status !== 'all') {
        $query .= " AND pe.status = ?";
        $params[] = $export_status;
    }
    
    if ($export_date_start) {
        $query .= " AND pe.date_start >= ?";
        $params[] = $export_date_start . ' 00:00:00';
    }
    
    if ($export_date_end) {
        $query .= " AND pe.date_start <= ?";
        $params[] = $export_date_end . ' 23:59:59';
    }
    
    $query .= " ORDER BY pe.date_start DESC";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Export selon le format demandé
    switch ($export_format) {
        case 'csv':
            // Headers CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="presence_export_' . date('Y-m-d_H-i-s') . '.csv"');
            header('Cache-Control: max-age=0');
            
            $output = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-têtes CSV
            $headers = [];
            if (in_array('user', $columns)) $headers[] = 'Utilisateur';
            if (in_array('type', $columns)) $headers[] = 'Type';
            if (in_array('date_start', $columns)) $headers[] = 'Date début';
            if (in_array('date_end', $columns)) $headers[] = 'Date fin';
            if (in_array('duration', $columns)) $headers[] = 'Durée (min)';
            if (in_array('status', $columns)) $headers[] = 'Statut';
            if (in_array('comment', $columns)) $headers[] = 'Commentaire';
            if (in_array('created_at', $columns)) $headers[] = 'Créé le';
            if (in_array('approver', $columns)) $headers[] = 'Approuvé par';
            
            fputcsv($output, $headers, ';'); // Point-virgule pour Excel français
            
            // Données
            foreach ($events as $event) {
                $row = [];
                if (in_array('user', $columns)) $row[] = $event['user_name'];
                if (in_array('type', $columns)) $row[] = $event['type_name'];
                if (in_array('date_start', $columns)) $row[] = date('d/m/Y H:i', strtotime($event['date_start']));
                if (in_array('date_end', $columns)) $row[] = $event['date_end'] ? date('d/m/Y H:i', strtotime($event['date_end'])) : '';
                if (in_array('duration', $columns)) $row[] = $event['duration_minutes'] ?: '';
                if (in_array('status', $columns)) {
                    $status_labels = [
                        'pending' => 'En attente',
                        'approved' => 'Approuvé', 
                        'rejected' => 'Rejeté',
                        'cancelled' => 'Annulé'
                    ];
                    $row[] = $status_labels[$event['status']] ?? $event['status'];
                }
                if (in_array('comment', $columns)) $row[] = $event['comment'];
                if (in_array('created_at', $columns)) $row[] = date('d/m/Y H:i', strtotime($event['created_at']));
                if (in_array('approver', $columns)) $row[] = $event['approver_name'] ?: '';
                
                fputcsv($output, $row, ';');
            }
            
            fclose($output);
            break;
            
        case 'excel':
            // Headers Excel
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="presence_export_' . date('Y-m-d_H-i-s') . '.xls"');
            header('Cache-Control: max-age=0');
            
            echo chr(0xEF).chr(0xBB).chr(0xBF); // BOM UTF-8
            
            // En-têtes Excel
            echo implode("\t", array_filter([
                in_array('user', $columns) ? 'Utilisateur' : null,
                in_array('type', $columns) ? 'Type' : null,
                in_array('date_start', $columns) ? 'Date début' : null,
                in_array('date_end', $columns) ? 'Date fin' : null,
                in_array('duration', $columns) ? 'Durée (min)' : null,
                in_array('status', $columns) ? 'Statut' : null,
                in_array('comment', $columns) ? 'Commentaire' : null,
                in_array('created_at', $columns) ? 'Créé le' : null,
                in_array('approver', $columns) ? 'Approuvé par' : null
            ])) . "\n";
            
            // Données Excel
            foreach ($events as $event) {
                $row = [];
                if (in_array('user', $columns)) $row[] = $event['user_name'];
                if (in_array('type', $columns)) $row[] = $event['type_name'];
                if (in_array('date_start', $columns)) $row[] = date('d/m/Y H:i', strtotime($event['date_start']));
                if (in_array('date_end', $columns)) $row[] = $event['date_end'] ? date('d/m/Y H:i', strtotime($event['date_end'])) : '';
                if (in_array('duration', $columns)) $row[] = $event['duration_minutes'] ?: '';
                if (in_array('status', $columns)) {
                    $status_labels = [
                        'pending' => 'En attente',
                        'approved' => 'Approuvé', 
                        'rejected' => 'Rejeté',
                        'cancelled' => 'Annulé'
                    ];
                    $row[] = $status_labels[$event['status']] ?? $event['status'];
                }
                if (in_array('comment', $columns)) $row[] = str_replace(["\r", "\n", "\t"], ' ', $event['comment']);
                if (in_array('created_at', $columns)) $row[] = date('d/m/Y H:i', strtotime($event['created_at']));
                if (in_array('approver', $columns)) $row[] = $event['approver_name'] ?: '';
                
                echo implode("\t", $row) . "\n";
            }
            break;
            
        default: // PDF
            http_response_code(501);
            die('Format PDF non supporté actuellement. Utilisez CSV ou Excel.');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    die('Erreur lors de l\'export : ' . $e->getMessage());
}
?>


