<?php
// Fichier d'export CSV dédié pour éviter les conflits de headers
session_start();

// Vérifier l'authentification
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit('Accès non autorisé');
}

// Inclure la configuration de base de données
if (!file_exists(__DIR__ . '/config/database.php')) {
    http_response_code(500);
    exit('Fichier de configuration de base de données non trouvé');
}

require_once __DIR__ . '/config/database.php';

// Vérifier que les fonctions existent
if (!function_exists('initializeShopSession')) {
    http_response_code(500);
    exit('Fonction initializeShopSession non trouvée');
}

if (!function_exists('getShopDBConnection')) {
    http_response_code(500);
    exit('Fonction getShopDBConnection non trouvée');
}

// Initialiser la session magasin
try {
    initializeShopSession();
    $shop_pdo = getShopDBConnection();
} catch (Exception $e) {
    http_response_code(500);
    exit('Erreur lors de l\'initialisation: ' . $e->getMessage());
}

if (!$shop_pdo) {
    http_response_code(500);
    exit('Erreur de connexion à la base de données');
}

// Vérifier les paramètres requis
if (!isset($_GET['date_start']) || !isset($_GET['date_end'])) {
    http_response_code(400);
    exit('Paramètres manquants: date_start et date_end requis');
}

try {
    $export_date_start = $_GET['date_start'];
    $export_date_end = $_GET['date_end'];

    // Valider les dates
    if (!strtotime($export_date_start) || !strtotime($export_date_end)) {
        http_response_code(400);
        exit('Format de date invalide');
    }

    if (strtotime($export_date_start) > strtotime($export_date_end)) {
        http_response_code(400);
        exit('La date de début doit être antérieure à la date de fin');
    }

    // Requête pour récupérer les données
    $stmt = $shop_pdo->prepare("
        SELECT tt.*, u.full_name, u.username, u.role,
               DATE(tt.clock_in) as date_pointage,
               TIME(tt.clock_in) as heure_entree,
               TIME(tt.clock_out) as heure_sortie,
               ROUND(tt.total_hours, 2) as heures_totales,
               ROUND(tt.work_duration, 2) as heures_travail,
               ROUND(tt.break_duration, 2) as heures_pause
        FROM time_tracking tt
        JOIN users u ON tt.user_id = u.id
        WHERE DATE(tt.clock_in) BETWEEN ? AND ?
        ORDER BY tt.clock_in DESC
    ");
    
    $stmt->execute([$export_date_start, $export_date_end]);
    $export_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nettoyer complètement le buffer de sortie
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Définir les headers CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pointages_' . $export_date_start . '_' . $export_date_end . '.csv"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header('Pragma: no-cache');

    // Créer le fichier CSV
    $output = fopen('php://output', 'w');

    // Ajouter le BOM UTF-8 pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // En-têtes CSV
    fputcsv($output, [
        'Date',
        'Employé',
        'Username', 
        'Heure entrée',
        'Heure sortie',
        'Heures totales',
        'Heures travail',
        'Heures pause',
        'Statut',
        'Notes'
    ], ';');

    // Données
    foreach ($export_data as $row) {
        fputcsv($output, [
            $row['date_pointage'],
            $row['full_name'] ?: $row['username'],
            $row['username'],
            $row['heure_entree'] ?: '',
            $row['heure_sortie'] ?: 'En cours',
            $row['heures_totales'] ?: '0.00',
            $row['heures_travail'] ?: '0.00', 
            $row['heures_pause'] ?: '0.00',
            $row['status'] ?: 'pending',
            $row['notes'] ?: ''
        ], ';');
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    // Log l'erreur (optionnel)
    error_log("Erreur export CSV: " . $e->getMessage());
    
    http_response_code(500);
    exit('Erreur lors de l\'export: ' . $e->getMessage());
}
?>
