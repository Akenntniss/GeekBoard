<?php
// Export autonome pour les présences - Version simplifiée
session_start();

// Configuration de base de données directe
$host = 'localhost';
$dbname = 'geekboard_mkmkmk';
$username = 'root';
$password = 'Mamanmaman01#';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion: " . $e->getMessage());
}

// Vérification de la session
$current_user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

if (!$current_user_id) {
    die("Vous devez être connecté pour exporter");
}

// Récupérer les paramètres d'export
$export_user = $_POST['export_user'] ?? '';
$export_type = $_POST['export_type'] ?? '';
$export_status = $_POST['export_status'] ?? '';
$export_date_start = $_POST['export_date_start'] ?? '';
$export_date_end = $_POST['export_date_end'] ?? '';
$export_format = $_POST['export_format'] ?? 'csv';
$columns = $_POST['columns'] ?? ['user', 'type', 'date_start', 'status'];

// Construire la requête
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

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Export CSV
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

fputcsv($output, $headers, ';');

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
exit;
?>
