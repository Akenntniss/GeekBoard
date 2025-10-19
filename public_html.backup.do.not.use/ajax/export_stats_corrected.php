<?php
/**
 * API pour exporter les données statistiques en CSV
 */

// Vérifier si on accède directement à cette page
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Initialiser la session du magasin si nécessaire
if (!isset($_SESSION['shop_id'])) {
    initializeShopSession();
}

try {
    // Récupérer les paramètres
    $type = $_GET['type'] ?? '';
    $period = $_GET['period'] ?? 'day';
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Valider les paramètres
    $validTypes = ['nouvelles_reparations', 'reparations_effectuees', 'reparations_restituees', 'devis_envoyes'];
    if (!in_array($type, $validTypes)) {
        throw new Exception('Type de statistique invalide');
    }
    
    $validPeriods = ['day', 'week', 'month'];
    if (!in_array($period, $validPeriods)) {
        throw new Exception('Période invalide');
    }
    
    if (!DateTime::createFromFormat('Y-m-d', $date)) {
        throw new Exception('Format de date invalide');
    }
    
    // Récupérer les données
    $data = getExportData($type, $period, $date);
    
    // Configuration des noms
    $typeNames = [
        'nouvelles_reparations' => 'Nouvelles réparations',
        'reparations_effectuees' => 'Réparations effectuées', 
        'reparations_restituees' => 'Réparations restituées',
        'devis_envoyes' => 'Devis envoyés'
    ];
    
    $periodNames = [
        'day' => 'journalier',
        'week' => 'hebdomadaire',
        'month' => 'mensuel'
    ];
    
    // Nom du fichier
    $filename = sprintf(
        'statistiques_%s_%s_%s.csv',
        str_replace(' ', '_', strtolower($typeNames[$type])),
        $periodNames[$period],
        $date
    );
    
    // Headers pour le téléchargement CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Créer le contenu CSV
    $output = fopen('php://output', 'w');
    
    // BOM UTF-8 pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // En-têtes du CSV
    fputcsv($output, [
        'Type de statistique',
        'Période',
        'Date de référence',
        'Date générée'
    ], ';');
    
    fputcsv($output, [
        $typeNames[$type],
        $periodNames[$period],
        $date,
        date('Y-m-d H:i:s')
    ], ';');
    
    // Ligne vide
    fputcsv($output, [], ';');
    
    // En-têtes des données
    fputcsv($output, ['Date', 'Nombre', 'Évolution (%)'], ';');
    
    // Données
    foreach ($data as $row) {
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    error_log("Erreur dans export_stats.php: " . $e->getMessage());
    
    // Retourner une erreur HTTP
    http_response_code(400);
    echo "Erreur: " . $e->getMessage();
}

/**
 * Récupérer les données pour l'export
 */
function getExportData($type, $period, $date) {
    try {
        $shop_pdo = getShopDBConnection();
        
        // Récupérer la plage de dates
        $dates = getDateRangeForExport($period, $date);
        $data = [];
        $previousValue = 0;
        
        foreach ($dates as $currentDate) {
            $value = getStatValueForExport($type, $currentDate, $shop_pdo);
            $evolution = $previousValue > 0 ? round((($value - $previousValue) / $previousValue) * 100, 1) : 0;
            
            $data[] = [
                formatDateForExport($currentDate, $period),
                $value,
                $evolution
            ];
            
            $previousValue = $value;
        }
        
        return $data;
        
    } catch (Exception $e) {
        error_log("Erreur dans getExportData: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtenir la plage de dates pour l'export
 */
function getDateRangeForExport($period, $date) {
    $dates = [];
    $baseDate = new DateTime($date);
    
    switch ($period) {
        case 'day':
            // 30 derniers jours pour l'export
            for ($i = 29; $i >= 0; $i--) {
                $currentDate = clone $baseDate;
                $currentDate->modify("-{$i} days");
                $dates[] = $currentDate->format('Y-m-d');
            }
            break;
            
        case 'week':
            // 12 dernières semaines
            for ($i = 11; $i >= 0; $i--) {
                $currentDate = clone $baseDate;
                $currentDate->modify("-{$i} weeks");
                $currentDate->modify('monday this week');
                $dates[] = $currentDate->format('Y-m-d');
            }
            break;
            
        case 'month':
            // 12 derniers mois
            for ($i = 11; $i >= 0; $i--) {
                $currentDate = clone $baseDate;
                $currentDate->modify("-{$i} months");
                $currentDate->modify('first day of this month');
                $dates[] = $currentDate->format('Y-m-d');
            }
            break;
    }
    
    return $dates;
}

/**
 * Formater la date pour l'export
 */
function formatDateForExport($date, $period) {
    $dateObj = new DateTime($date);
    
    switch ($period) {
        case 'day':
            return $dateObj->format('d/m/Y');
            
        case 'week':
            $endDate = clone $dateObj;
            $endDate->modify('+6 days');
            return 'Semaine du ' . $dateObj->format('d/m/Y') . ' au ' . $endDate->format('d/m/Y');
            
        case 'month':
            return $dateObj->format('F Y');
            
        default:
            return $dateObj->format('d/m/Y');
    }
}

/**
 * Obtenir la valeur statistique pour l'export
 */
function getStatValueForExport($type, $date, $pdo) {
    try {
        switch ($type) {
            case 'nouvelles_reparations':
                return getNouvellesReparationsForExport($date, $pdo);
                
            case 'reparations_effectuees':
                return getReparationsEffectueesForExport($date, $pdo);
                
            case 'reparations_restituees':
                return getReparationsRestituees ForExport($date, $pdo);
                
            case 'devis_envoyes':
                return getDevisEnvoyesForExport($date, $pdo);
                
            default:
                return 0;
        }
        
    } catch (Exception $e) {
        error_log("Erreur dans getStatValueForExport: " . $e->getMessage());
        return 0;
    }
}

/**
 * Nouvelles réparations pour export
 */
function getNouvellesReparationsForExport($date, $pdo) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reparations 
        WHERE DATE(date_reception) = ? AND statut_categorie = 1
    ");
    $stmt->execute([$date]);
    return (int) $stmt->fetchColumn();
}

/**
 * Réparations effectuées pour export
 */
function getReparationsEffectueesForExport($date, $pdo) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reparations 
        WHERE DATE(date_modification) = ? AND (statut = 'reparation_effectue' OR statut_categorie = 4)
    ");
    $stmt->execute([$date]);
    return (int) $stmt->fetchColumn();
}

/**
 * Réparations restituées pour export
 */
function getReparationsRestituees ForExport($date, $pdo) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reparations 
        WHERE DATE(date_modification) = ? AND statut = 'restitue'
    ");
    $stmt->execute([$date]);
    return (int) $stmt->fetchColumn();
}

/**
 * Devis envoyés pour export
 */
function getDevisEnvoyesForExport($date, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM devis 
            WHERE DATE(date_envoi) = ? AND statut = 'envoye'
        ");
        $stmt->execute([$date]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
?>
