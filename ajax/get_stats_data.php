<?php
/**
 * API pour récupérer les données statistiques
 * Supporte différents types de statistiques et périodes
 */

// Désactiver l'affichage des erreurs pour les réponses JSON propres
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers pour JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Inclure les fichiers nécessaires
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

try {
    // Obtenir la connexion à la base de données de la boutique
    $shop_pdo = getShopDBConnection();
    
    // Vérifier que la connexion PDO existe
    if (!isset($shop_pdo) || $shop_pdo === null) {
        throw new Exception('Erreur de connexion à la base de données');
    }
    
    // Récupérer les données POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Données JSON invalides');
    }
    
    $period = $input['period'] ?? 'day';
    $date = $input['date'] ?? date('Y-m-d');
    $comparison = $input['comparison'] ?? false;
    
    // Valider la période
    $validPeriods = ['day', 'week', 'month'];
    if (!in_array($period, $validPeriods)) {
        throw new Exception('Période invalide');
    }
    
    // Valider la date
    if (!DateTime::createFromFormat('Y-m-d', $date)) {
        throw new Exception('Format de date invalide');
    }
    
    $validTypes = ['nouvelles_reparations', 'reparations_effectuees', 'reparations_restituees', 'devis_envoyes'];
    
    if ($comparison && isset($input['types']) && is_array($input['types'])) {
        // Mode comparaison
        $types = $input['types'];
        
        // Valider tous les types
        foreach ($types as $type) {
            if (!in_array($type, $validTypes)) {
                throw new Exception('Type de statistique invalide: ' . $type);
            }
        }
        
        // Récupérer les données de comparaison
        $chartData = getComparisonChartData($types, $period, $date, $shop_pdo);
        $tableData = getComparisonTableData($types, $period, $date, $shop_pdo);
        
        // Retourner la réponse
        echo json_encode([
            'success' => true,
            'chartData' => $chartData,
            'tableData' => $tableData,
            'types' => $types,
            'period' => $period,
            'date' => $date,
            'comparison' => true
        ]);
        
    } else {
        // Mode simple
        $type = $input['type'] ?? '';
        
        // Valider le type
        if (!in_array($type, $validTypes)) {
            throw new Exception('Type de statistique invalide');
        }
        
        // Récupérer les données
        $chartData = getChartData($type, $period, $date, $shop_pdo);
        $tableData = getTableData($type, $period, $date, $shop_pdo);
        
        // Retourner la réponse
        echo json_encode([
            'success' => true,
            'chartData' => $chartData,
            'tableData' => $tableData,
            'type' => $type,
            'period' => $period,
            'date' => $date,
            'comparison' => false
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erreur dans get_stats_data.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Récupérer les données pour le graphique
 */
function getChartData($type, $period, $date, $pdo) {
    try {
        // Déterminer les dates et labels selon la période
        $dates = getDateRange($period, $date);
        $labels = [];
        $values = [];
        
        foreach ($dates as $currentDate) {
            $labels[] = formatDateLabel($currentDate, $period);
            $values[] = getStatValue($type, $currentDate, $pdo);
        }
        
        return [
            'labels' => $labels,
            'values' => $values
        ];
        
    } catch (Exception $e) {
        error_log("Erreur dans getChartData: " . $e->getMessage());
        return [
            'labels' => [],
            'values' => []
        ];
    }
}

/**
 * Récupérer les données pour le tableau
 */
function getTableData($type, $period, $date, $pdo) {
    try {
        // En-têtes selon la période
        $headers = ['Date', 'Nombre', 'Évolution'];
        
        // Récupérer les données
        $dates = getDateRange($period, $date);
        $rows = [];
        $previousValue = 0;
        
        foreach ($dates as $currentDate) {
            $value = getStatValue($type, $currentDate, $pdo);
            $evolution = $previousValue > 0 ? round((($value - $previousValue) / $previousValue) * 100, 1) : 0;
            
            $rows[] = [
                formatDateLabel($currentDate, $period),
                $value,
                $evolution >= 0 ? "+{$evolution}%" : "{$evolution}%"
            ];
            
            $previousValue = $value;
        }
        
        return [
            'headers' => $headers,
            'rows' => $rows
        ];
        
    } catch (Exception $e) {
        error_log("Erreur dans getTableData: " . $e->getMessage());
        return [
            'headers' => ['Date', 'Nombre', 'Évolution'],
            'rows' => []
        ];
    }
}

/**
 * Obtenir la plage de dates selon la période
 */
function getDateRange($period, $date) {
    $dates = [];
    $baseDate = new DateTime($date);
    
    switch ($period) {
        case 'day':
            // 7 derniers jours
            for ($i = 6; $i >= 0; $i--) {
                $currentDate = clone $baseDate;
                $currentDate->modify("-{$i} days");
                $dates[] = $currentDate->format('Y-m-d');
            }
            break;
            
        case 'week':
            // 4 dernières semaines
            for ($i = 3; $i >= 0; $i--) {
                $currentDate = clone $baseDate;
                $currentDate->modify("-{$i} weeks");
                // Commencer au lundi de la semaine
                $currentDate->modify('monday this week');
                $dates[] = $currentDate->format('Y-m-d');
            }
            break;
            
        case 'month':
            // 6 derniers mois
            for ($i = 5; $i >= 0; $i--) {
                $currentDate = clone $baseDate;
                $currentDate->modify("-{$i} months");
                // Premier jour du mois
                $currentDate->modify('first day of this month');
                $dates[] = $currentDate->format('Y-m-d');
            }
            break;
    }
    
    return $dates;
}

/**
 * Formater le label de date
 */
function formatDateLabel($date, $period) {
    $dateObj = new DateTime($date);
    
    switch ($period) {
        case 'day':
            return $dateObj->format('d/m');
            
        case 'week':
            $endDate = clone $dateObj;
            $endDate->modify('+6 days');
            return $dateObj->format('d/m') . ' - ' . $endDate->format('d/m');
            
        case 'month':
            return $dateObj->format('M Y');
            
        default:
            return $dateObj->format('d/m/Y');
    }
}

/**
 * Obtenir la valeur statistique pour une date donnée
 */
function getStatValue($type, $date, $pdo) {
    try {
        switch ($type) {
            case 'nouvelles_reparations':
                return getNouvellesReparations($date, $pdo);
                
            case 'reparations_effectuees':
                return getReparationsEffectuees($date, $pdo);
                
            case 'reparations_restituees':
                return getReparationsRestituees($date, $pdo);
                
            case 'devis_envoyes':
                return getDevisEnvoyes($date, $pdo);
                
            default:
                return 0;
        }
        
    } catch (Exception $e) {
        error_log("Erreur dans getStatValue: " . $e->getMessage());
        return 0;
    }
}

/**
 * Nouvelles réparations
 */
function getNouvellesReparations($date, $pdo) {
    // Toutes les réparations créées à cette date, peu importe leur statut actuel
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reparations 
        WHERE DATE(date_reception) = ?
    ");
    $stmt->execute([$date]);
    return (int) $stmt->fetchColumn();
}

/**
 * Réparations effectuées
 */
function getReparationsEffectuees($date, $pdo) {
    // Réparations qui ont changé vers le statut "effectué" à cette date
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reparations 
        WHERE DATE(date_modification) = ? 
        AND (statut = 'reparation_effectue' OR statut_categorie = 4)
        AND DATE(date_reception) != ?
    ");
    $stmt->execute([$date, $date]);
    $reparations_effectuees_modifiees = (int) $stmt->fetchColumn();
    
    // Ajouter les réparations créées ET terminées le même jour
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reparations 
        WHERE DATE(date_reception) = ? 
        AND (statut = 'reparation_effectue' OR statut_categorie = 4)
    ");
    $stmt->execute([$date]);
    $reparations_effectuees_nouvelles = (int) $stmt->fetchColumn();
    
    return $reparations_effectuees_modifiees + $reparations_effectuees_nouvelles;
}

/**
 * Réparations restituées
 */
function getReparationsRestituees($date, $pdo) {
    // Réparations qui ont changé vers le statut "restitué" à cette date
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reparations 
        WHERE DATE(date_modification) = ? 
        AND statut = 'restitue'
        AND DATE(date_reception) != ?
    ");
    $stmt->execute([$date, $date]);
    $reparations_restituees_modifiees = (int) $stmt->fetchColumn();
    
    // Ajouter les réparations créées ET restituées le même jour
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reparations 
        WHERE DATE(date_reception) = ? 
        AND statut = 'restitue'
    ");
    $stmt->execute([$date]);
    $reparations_restituees_nouvelles = (int) $stmt->fetchColumn();
    
    return $reparations_restituees_modifiees + $reparations_restituees_nouvelles;
}

/**
 * Devis envoyés
 */
function getDevisEnvoyes($date, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM devis 
            WHERE DATE(date_envoi) = ? AND statut = 'envoye'
        ");
        $stmt->execute([$date]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Table devis n'existe peut-être pas
        return 0;
    }
}

/**
 * Récupérer les données de graphique pour comparaison
 */
function getComparisonChartData($types, $period, $date, $pdo) {
    try {
        // Déterminer les dates selon la période
        $dates = getDateRange($period, $date);
        $labels = [];
        $chartData = [];
        
        // Préparer les labels
        foreach ($dates as $currentDate) {
            $labels[] = formatDateLabel($currentDate, $period);
        }
        
        // Préparer les données pour chaque type
        foreach ($types as $type) {
            $values = [];
            foreach ($dates as $currentDate) {
                $values[] = getStatValue($type, $currentDate, $pdo);
            }
            $chartData[$type] = [
                'labels' => $labels,
                'values' => $values
            ];
        }
        
        // Retourner avec les labels communs
        $chartData['labels'] = $labels;
        
        return $chartData;
        
    } catch (Exception $e) {
        error_log("Erreur dans getComparisonChartData: " . $e->getMessage());
        return [
            'labels' => [],
            'types' => []
        ];
    }
}

/**
 * Récupérer les données de tableau pour comparaison
 */
function getComparisonTableData($types, $period, $date, $pdo) {
    try {
        // En-têtes : Date + chaque type
        $headers = ['Date'];
        $typeNames = [
            'nouvelles_reparations' => 'Nouvelles réparations',
            'reparations_effectuees' => 'Réparations effectuées',
            'reparations_restituees' => 'Réparations restituées',
            'devis_envoyes' => 'Devis envoyés'
        ];
        
        foreach ($types as $type) {
            $headers[] = $typeNames[$type] ?? $type;
        }
        
        // Récupérer les données
        $dates = getDateRange($period, $date);
        $rows = [];
        
        foreach ($dates as $currentDate) {
            $row = [formatDateLabel($currentDate, $period)];
            
            foreach ($types as $type) {
                $row[] = getStatValue($type, $currentDate, $pdo);
            }
            
            $rows[] = $row;
        }
        
        return [
            'headers' => $headers,
            'rows' => $rows
        ];
        
    } catch (Exception $e) {
        error_log("Erreur dans getComparisonTableData: " . $e->getMessage());
        return [
            'headers' => ['Date'],
            'rows' => []
        ];
    }
}
?>