<?php
/**
 * API de gestion des notes magasin
 * Gestion des remarques contextuelles globales du magasin (fermetures, travaux, événements)
 * 
 * Actions:
 * - get_notes : Liste des notes (filtrable)
 * - get_note :  Note spécifique
 * - create_note : Créer note
 * - update_note : Modifier note
 * - delete_note : Supprimer note
 * - toggle_ai_inclusion : Activer/désactiver inclusion IA
 * - get_active_notes : Notes actives pour une période
 * - get_shop_context : Contexte formaté pour l'IA
 * - get_statistics : Statistiques
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialiser la session magasin
initializeShopSession();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// Vérification admin
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;

if (!$user_id || $user_role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès refusé - Admin uniquement']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = getShopDBConnection();
    
    switch ($action) {
        case 'get_notes':
            $result = getNotes($pdo, $_GET);
            break;
            
        case 'get_note':
            $result = getNote($pdo, $_GET['id'] ?? 0);
            break;
            
        case 'create_note':
            $result = createNote($pdo, $_POST, $user_id);
            break;
            
        case 'update_note':
            $result = updateNote($pdo, $_POST);
            break;
            
        case 'delete_note':
            $result = deleteNote($pdo, $_POST['id'] ?? 0);
            break;
            
        case 'toggle_ai_inclusion':
            $result = toggleAiInclusion($pdo, $_POST['id'] ?? 0);
            break;
            
        case 'get_active_notes':
            $result = getActiveNotes($pdo, $_GET['date_start'] ?? '', $_GET['date_end'] ?? '');
            break;
            
        case 'get_shop_context':
            $result = getShopContext($pdo, $_GET['date_start'] ?? '', $_GET['date_end'] ?? '');
            break;
            
        case 'get_statistics':
            $result = getStatistics($pdo);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Liste des notes avec filtres
 */
function getNotes($pdo, $filters) {
    $where = ['1=1'];
    $params = [];
    
    if (!empty($filters['type'])) {
        $where[] = 'note_type = ?';
        $params[] = $filters['type'];
    }
    
    if (!empty($filters['impact_level'])) {
        $where[] = 'impact_level = ?';
        $params[] = $filters['impact_level'];
    }
    
    if (isset($filters['affects_kpi'])) {
        $where[] = 'affects_kpi = ?';
        $params[] = $filters['affects_kpi'] ? 1 : 0;
    }
    
    if (isset($filters['include_in_ai'])) {
        $where[] = 'include_in_ai_analysis = ?';
        $params[] = $filters['include_in_ai'] ? 1 : 0;
    }
    
    if (!empty($filters['date_start'])) {
        $where[] = '(date_end IS NULL OR date_end >= ?)';
        $params[] = $filters['date_start'];
    }
    
    if (!empty($filters['date_end'])) {
        $where[] = 'date_start <= ?';
        $params[] = $filters['date_end'];
    }
    
    $sql = "
        SELECT 
            sn.*,
            u.full_name as created_by_name,
            DATEDIFF(COALESCE(date_end, CURDATE()), date_start) + 1 as duration_days,
            CASE 
                WHEN date_end IS NULL OR (date_start <= CURDATE() AND date_end >= CURDATE())
                THEN 1 
                ELSE 0 
            END as is_active
        FROM shop_notes sn
        LEFT JOIN users u ON sn.created_by = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY sn.date_start DESC, sn.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Note spécifique
 */
function getNote($pdo, $id) {
    $sql = "
        SELECT 
            sn.*,
            u.full_name as created_by_name,
            DATEDIFF(COALESCE(date_end, CURDATE()), date_start) + 1 as duration_days
        FROM shop_notes sn
        LEFT JOIN users u ON sn.created_by = u.id
        WHERE sn.id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Créer une note
 */
function createNote($pdo, $data, $user_id) {
    $sql = "
        INSERT INTO shop_notes (
            note_type, title, description, date_start, date_end,
            impact_level, affects_kpi, include_in_ai_analysis, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['note_type'],
        $data['title'],
        $data['description'],
        $data['date_start'],
        $data['date_end'] ?? null,
        $data['impact_level'] ?? 'info',
        $data['affects_kpi'] ?? 1,
        $data['include_in_ai_analysis'] ?? 1,
        $user_id
    ]);
    
    return ['id' => $pdo->lastInsertId(), 'message' => 'Note magasin créée'];
}

/**
 * Modifier une note
 */
function updateNote($pdo, $data) {
    $sql = "
        UPDATE shop_notes SET
            note_type = ?,
            title = ?,
            description = ?,
            date_start = ?,
            date_end = ?,
            impact_level = ?,
            affects_kpi = ?,
            include_in_ai_analysis = ?
        WHERE id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['note_type'],
        $data['title'],
        $data['description'],
        $data['date_start'],
        $data['date_end'] ?? null,
        $data['impact_level'],
        $data['affects_kpi'] ?? 1,
        $data['include_in_ai_analysis'] ?? 1,
        $data['id']
    ]);
    
    return ['message' => 'Note magasin modifiée'];
}

/**
 * Supprimer une note
 */
function deleteNote($pdo, $id) {
    $sql = "DELETE FROM shop_notes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    return ['message' => 'Note magasin supprimée'];
}

/**
 * Toggle inclusion IA
 */
function toggleAiInclusion($pdo, $id) {
    $sql = "UPDATE shop_notes SET include_in_ai_analysis = NOT include_in_ai_analysis WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    return ['message' => 'Inclusion IA modifiée'];
}

/**
 * Notes actives pour une période
 */
function getActiveNotes($pdo, $date_start, $date_end) {
    $sql = "
        SELECT *,
            DATEDIFF(COALESCE(date_end, CURDATE()), date_start) + 1 as duration_days
        FROM shop_notes
        WHERE include_in_ai_analysis = 1
        AND (
            (date_start BETWEEN ? AND ?)
            OR (date_end BETWEEN ? AND ?)
            OR (date_start <= ? AND (date_end >= ? OR date_end IS NULL))
        )
        ORDER BY impact_level DESC, date_start DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date_start, $date_end, $date_start, $date_end, $date_start, $date_end]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Contexte formaté pour l'IA
 */
function getShopContext($pdo, $date_start, $date_end) {
    $notes = getActiveNotes($pdo, $date_start, $date_end);
    
    if (empty($notes)) {
        return ['context' => '', 'notes_count' => 0];
    }
    
    $context = "CONTEXTE MAGASIN POUR LA PÉRIODE [" . date('d/m/Y', strtotime($date_start)) . " - " . date('d/m/Y', strtotime($date_end)) . "] :\n\n";
    
    $type_labels = [
        'fermeture' => 'FERMETURE',
        'travaux' => 'TRAVAUX',
        'evenement' => 'ÉVÉNEMENT',
        'probleme_technique' => 'PROBLÈME TECHNIQUE',
        'stock' => 'STOCK/APPROVISIONNEMENT',
        'autre' => 'AUTRE'
    ];
    
    $impact_labels = [
        'info' => 'INFO',
        'low' => 'IMPACT FAIBLE',
        'medium' => 'IMPACT MOYEN',
        'high' => 'IMPACT ÉLEVÉ',
        'critical' => 'IMPACT CRITIQUE'
    ];
    
    foreach ($notes as $note) {
        $type = $type_labels[$note['note_type']] ?? strtoupper($note['note_type']);
        $impact = $impact_labels[$note['impact_level']] ?? $note['impact_level'];
        $date_str = date('d/m/Y', strtotime($note['date_start']));
        
        if ($note['date_end']) {
            $date_str .= ' au ' . date('d/m/Y', strtotime($note['date_end']));
            $date_str .= ' (' . $note['duration_days'] . ' jours)';
        }
        
        $context .= "[$type - $impact - $date_str]\n";
        $context .= $note['title'] . "\n";
        if (!empty($note['description'])) {
            $context .= $note['description'] . "\n";
        }
        if ($note['affects_kpi']) {
            $context .= "⮕ Impact: Affecte les KPI\n";
        }
        $context .= "\n";
    }
    
    return [
        'context' => $context,
        'notes_count' => count($notes),
        'notes' => $notes
    ];
}

/**
 * Statistiques
 */
function getStatistics($pdo) {
    $sql = "
        SELECT 
            COUNT(*) as total_notes,
            COUNT(CASE WHEN note_type = 'fermeture' THEN 1 END) as nb_fermetures,
            COUNT(CASE WHEN note_type = 'travaux' THEN 1 END) as nb_travaux,
            COUNT(CASE WHEN note_type = 'evenement' THEN 1 END) as nb_evenements,
            COUNT(CASE WHEN impact_level IN ('high', 'critical') THEN 1 END) as nb_impact_fort,
            COUNT(CASE WHEN affects_kpi = 1 THEN 1 END) as nb_affecting_kpi,
            COUNT(CASE WHEN include_in_ai_analysis = 1 THEN 1 END) as nb_in_ai,
            COUNT(CASE WHEN date_end IS NULL OR (date_start <= CURDATE() AND date_end >= CURDATE()) THEN 1 END) as nb_actives
        FROM shop_notes
    ";
    
    $stmt = $pdo->query($sql);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
