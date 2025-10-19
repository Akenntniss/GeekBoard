<?php
/**
 * API POUR LES STATISTIQUES AVANCÉES
 * Système complet de récupération des données statistiques
 */

// Vérifier l'accès
require_once '../config/database.php';
require_once '../includes/functions.php';

// Initialiser la session shop
initializeShopSession();

// Vérifier que l'utilisateur est connecté (temporairement désactivé pour debug)
/*
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}
*/

// Debug temporaire - forcer un user_id si pas défini
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // ID temporaire pour les tests
    error_log("DEBUG: Session user_id forcé à 1 pour les statistiques");
}

// Debug des données reçues
error_log("DEBUG STATS: Début de l'API get_advanced_stats.php");
error_log("DEBUG STATS: Session user_id = " . ($_SESSION['user_id'] ?? 'non défini'));
error_log("DEBUG STATS: Session shop_id = " . ($_SESSION['shop_id'] ?? 'non défini'));

// Récupérer les données POST
$input_raw = file_get_contents('php://input');
error_log("DEBUG STATS: Données brutes reçues = " . $input_raw);

$input = json_decode($input_raw, true);
error_log("DEBUG STATS: Données décodées = " . print_r($input, true));

if (!$input) {
    error_log("DEBUG STATS: ERREUR - Données invalides");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$type = $input['type'] ?? '';
$period = $input['period'] ?? 'today';
$start_date = $input['start_date'] ?? date('Y-m-d');
$end_date = $input['end_date'] ?? date('Y-m-d');

try {
    error_log("DEBUG STATS: Tentative de connexion à la base de données");
    $shop_pdo = getShopDBConnection();
    error_log("DEBUG STATS: Connexion réussie");
    
    error_log("DEBUG STATS: Type demandé = " . $type);
    error_log("DEBUG STATS: Période = " . $period);
    error_log("DEBUG STATS: Date début = " . $start_date);
    error_log("DEBUG STATS: Date fin = " . $end_date);
    
    // Générer les données selon le type
    switch ($type) {
        case 'nouvelles_reparations':
            error_log("DEBUG STATS: Génération des stats nouvelles réparations");
            $data = getNewRepairsStats($shop_pdo, $start_date, $end_date, $period);
            break;
        case 'reparations_effectuees':
            error_log("DEBUG STATS: Génération des stats réparations effectuées");
            $data = getCompletedRepairsStats($shop_pdo, $start_date, $end_date, $period);
            break;
        case 'reparations_restituees':
            error_log("DEBUG STATS: Génération des stats réparations restituées");
            $data = getReturnedRepairsStats($shop_pdo, $start_date, $end_date, $period);
            break;
        case 'devis_envoyes':
            error_log("DEBUG STATS: Génération des stats devis envoyés");
            $data = getSentQuotesStats($shop_pdo, $start_date, $end_date, $period);
            break;
        default:
            error_log("DEBUG STATS: ERREUR - Type non reconnu: " . $type);
            throw new Exception('Type de statistique non reconnu: ' . $type);
    }
    
    error_log("DEBUG STATS: Données générées avec succès");
    echo json_encode(['success' => true, 'data' => $data]);
    
} catch (Exception $e) {
    error_log("DEBUG STATS: ERREUR EXCEPTION - " . $e->getMessage());
    error_log("DEBUG STATS: Stack trace - " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Statistiques des nouvelles réparations
 */
function getNewRepairsStats($pdo, $start_date, $end_date, $period) {
    // Indicateurs principaux
    $indicators = getRepairIndicators($pdo, $start_date, $end_date, 'date_reception');
    
    // Données pour les graphiques
    $charts = [
        'overview' => getRepairOverviewChart($pdo, $start_date, $end_date, 'date_reception'),
        'timeline' => getRepairTimelineChart($pdo, $start_date, $end_date, 'date_reception'),
        'breakdown' => getRepairBreakdownChart($pdo, $start_date, $end_date, 'date_reception'),
        'performance' => getRepairPerformanceChart($pdo, $start_date, $end_date, 'date_reception')
    ];
    
    // Métriques détaillées
    $metrics = getRepairMetrics($pdo, $start_date, $end_date, 'date_reception');
    
    // Détails
    $details = getRepairDetails($pdo, $start_date, $end_date, 'date_reception');
    
    return [
        'indicators' => $indicators,
        'charts' => $charts,
        'metrics' => $metrics,
        'details' => $details
    ];
}

/**
 * Statistiques des réparations effectuées
 */
function getCompletedRepairsStats($pdo, $start_date, $end_date, $period) {
    // Indicateurs principaux
    $indicators = getRepairIndicators($pdo, $start_date, $end_date, 'date_modification', "AND (statut = 'reparation_effectue' OR statut_categorie = 4)");
    
    // Données pour les graphiques
    $charts = [
        'overview' => getRepairOverviewChart($pdo, $start_date, $end_date, 'date_modification', "AND (statut = 'reparation_effectue' OR statut_categorie = 4)"),
        'timeline' => getRepairTimelineChart($pdo, $start_date, $end_date, 'date_modification', "AND (statut = 'reparation_effectue' OR statut_categorie = 4)"),
        'breakdown' => getRepairBreakdownChart($pdo, $start_date, $end_date, 'date_modification', "AND (statut = 'reparation_effectue' OR statut_categorie = 4)"),
        'performance' => getRepairPerformanceChart($pdo, $start_date, $end_date, 'date_modification', "AND (statut = 'reparation_effectue' OR statut_categorie = 4)")
    ];
    
    // Métriques détaillées
    $metrics = getRepairMetrics($pdo, $start_date, $end_date, 'date_modification', "AND (statut = 'reparation_effectue' OR statut_categorie = 4)");
    
    // Détails
    $details = getRepairDetails($pdo, $start_date, $end_date, 'date_modification', "AND (statut = 'reparation_effectue' OR statut_categorie = 4)");
    
    return [
        'indicators' => $indicators,
        'charts' => $charts,
        'metrics' => $metrics,
        'details' => $details
    ];
}

/**
 * Statistiques des réparations restituées
 */
function getReturnedRepairsStats($pdo, $start_date, $end_date, $period) {
    // Indicateurs principaux
    $indicators = getRepairIndicators($pdo, $start_date, $end_date, 'date_modification', "AND statut = 'restitue'");
    
    // Données pour les graphiques
    $charts = [
        'overview' => getRepairOverviewChart($pdo, $start_date, $end_date, 'date_modification', "AND statut = 'restitue'"),
        'timeline' => getRepairTimelineChart($pdo, $start_date, $end_date, 'date_modification', "AND statut = 'restitue'"),
        'breakdown' => getRepairBreakdownChart($pdo, $start_date, $end_date, 'date_modification', "AND statut = 'restitue'"),
        'performance' => getRepairPerformanceChart($pdo, $start_date, $end_date, 'date_modification', "AND statut = 'restitue'")
    ];
    
    // Métriques détaillées avec revenus
    $metrics = getRepairMetrics($pdo, $start_date, $end_date, 'date_modification', "AND statut = 'restitue'", true);
    
    // Détails
    $details = getRepairDetails($pdo, $start_date, $end_date, 'date_modification', "AND statut = 'restitue'");
    
    return [
        'indicators' => $indicators,
        'charts' => $charts,
        'metrics' => $metrics,
        'details' => $details
    ];
}

/**
 * Statistiques des devis envoyés
 */
function getSentQuotesStats($pdo, $start_date, $end_date, $period) {
    // Vérifier si la table devis existe
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'devis'");
        if ($stmt->rowCount() == 0) {
            // Table n'existe pas, retourner des données vides
            return [
                'indicators' => [
                    'main' => ['value' => 0, 'label' => 'Devis envoyés', 'change' => 0],
                    'period' => ['value' => 0, 'label' => 'Période précédente'],
                    'best' => ['value' => 0, 'label' => 'Meilleur jour', 'date' => '-']
                ],
                'charts' => [
                    'overview' => ['labels' => [], 'values' => [], 'label' => 'Devis'],
                    'timeline' => ['labels' => [], 'values' => [], 'label' => 'Devis par jour'],
                    'breakdown' => ['labels' => ['Aucune donnée'], 'values' => [1]],
                    'performance' => ['labels' => [], 'values' => [], 'label' => 'Devis par employé']
                ],
                'metrics' => [],
                'details' => []
            ];
        }
        
        // Table existe, récupérer les vraies données
        $indicators = getQuoteIndicators($pdo, $start_date, $end_date);
        
        $charts = [
            'overview' => getQuoteOverviewChart($pdo, $start_date, $end_date),
            'timeline' => getQuoteTimelineChart($pdo, $start_date, $end_date),
            'breakdown' => getQuoteBreakdownChart($pdo, $start_date, $end_date),
            'performance' => getQuotePerformanceChart($pdo, $start_date, $end_date)
        ];
        
        $metrics = getQuoteMetrics($pdo, $start_date, $end_date);
        $details = getQuoteDetails($pdo, $start_date, $end_date);
        
        return [
            'indicators' => $indicators,
            'charts' => $charts,
            'metrics' => $metrics,
            'details' => $details
        ];
        
    } catch (Exception $e) {
        // En cas d'erreur, retourner des données vides
        return [
            'indicators' => [
                'main' => ['value' => 0, 'label' => 'Devis envoyés', 'change' => 0],
                'period' => ['value' => 0, 'label' => 'Période précédente'],
                'best' => ['value' => 0, 'label' => 'Meilleur jour', 'date' => '-']
            ],
            'charts' => [
                'overview' => ['labels' => [], 'values' => [], 'label' => 'Devis'],
                'timeline' => ['labels' => [], 'values' => [], 'label' => 'Devis par jour'],
                'breakdown' => ['labels' => ['Aucune donnée'], 'values' => [1]],
                'performance' => ['labels' => [], 'values' => [], 'label' => 'Devis par employé']
            ],
            'metrics' => [],
            'details' => []
        ];
    }
}

/**
 * Indicateurs principaux pour les réparations
 */
function getRepairIndicators($pdo, $start_date, $end_date, $date_field, $extra_condition = '') {
    // Valeur principale (période actuelle)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count, 
               COALESCE(SUM(prix_reparation), 0) as revenue
        FROM reparations 
        WHERE DATE($date_field) BETWEEN ? AND ? 
        $extra_condition
    ");
    $stmt->execute([$start_date, $end_date]);
    $current = $stmt->fetch();
    
    // Période précédente (même durée)
    $days_diff = (strtotime($end_date) - strtotime($start_date)) / (24 * 3600) + 1;
    $prev_start = date('Y-m-d', strtotime($start_date . " -$days_diff days"));
    $prev_end = date('Y-m-d', strtotime($start_date . " -1 day"));
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count, 
               COALESCE(SUM(prix_reparation), 0) as revenue
        FROM reparations 
        WHERE DATE($date_field) BETWEEN ? AND ? 
        $extra_condition
    ");
    $stmt->execute([$prev_start, $prev_end]);
    $previous = $stmt->fetch();
    
    // Calcul du pourcentage de changement
    $change = 0;
    if ($previous['count'] > 0) {
        $change = round((($current['count'] - $previous['count']) / $previous['count']) * 100, 1);
    } elseif ($current['count'] > 0) {
        $change = 100;
    }
    
    // Meilleur jour
    $stmt = $pdo->prepare("
        SELECT DATE($date_field) as date, COUNT(*) as count
        FROM reparations 
        WHERE DATE($date_field) BETWEEN ? AND ? 
        $extra_condition
        GROUP BY DATE($date_field)
        ORDER BY count DESC
        LIMIT 1
    ");
    $stmt->execute([$start_date, $end_date]);
    $best_day = $stmt->fetch();
    
    return [
        'main' => [
            'value' => number_format($current['count']),
            'label' => 'Total période',
            'change' => $change
        ],
        'period' => [
            'value' => number_format($previous['count']),
            'label' => 'Période précédente'
        ],
        'best' => [
            'value' => $best_day ? number_format($best_day['count']) : '0',
            'label' => 'Meilleur jour',
            'date' => $best_day ? date('d/m/Y', strtotime($best_day['date'])) : '-'
        ]
    ];
}

/**
 * Graphique vue d'ensemble
 */
function getRepairOverviewChart($pdo, $start_date, $end_date, $date_field, $extra_condition = '') {
    $stmt = $pdo->prepare("
        SELECT DATE($date_field) as date, COUNT(*) as count
        FROM reparations 
        WHERE DATE($date_field) BETWEEN ? AND ? 
        $extra_condition
        GROUP BY DATE($date_field)
        ORDER BY date ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    $results = $stmt->fetchAll();
    
    $labels = [];
    $values = [];
    
    foreach ($results as $row) {
        $labels[] = date('d/m', strtotime($row['date']));
        $values[] = (int)$row['count'];
    }
    
    return [
        'labels' => $labels,
        'values' => $values,
        'label' => 'Réparations'
    ];
}

/**
 * Graphique timeline
 */
function getRepairTimelineChart($pdo, $start_date, $end_date, $date_field, $extra_condition = '') {
    // Même logique que overview mais avec plus de détails
    return getRepairOverviewChart($pdo, $start_date, $end_date, $date_field, $extra_condition);
}

/**
 * Graphique répartition
 */
function getRepairBreakdownChart($pdo, $start_date, $end_date, $date_field, $extra_condition = '') {
    $stmt = $pdo->prepare("
        SELECT type_appareil, COUNT(*) as count
        FROM reparations 
        WHERE DATE($date_field) BETWEEN ? AND ? 
        $extra_condition
        GROUP BY type_appareil
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->execute([$start_date, $end_date]);
    $results = $stmt->fetchAll();
    
    $labels = [];
    $values = [];
    
    foreach ($results as $row) {
        $labels[] = ucfirst($row['type_appareil']);
        $values[] = (int)$row['count'];
    }
    
    if (empty($labels)) {
        $labels = ['Aucune donnée'];
        $values = [1];
    }
    
    return [
        'labels' => $labels,
        'values' => $values
    ];
}

/**
 * Graphique performance
 */
function getRepairPerformanceChart($pdo, $start_date, $end_date, $date_field, $extra_condition = '') {
    $stmt = $pdo->prepare("
        SELECT u.full_name, COUNT(r.id) as count
        FROM reparations r
        LEFT JOIN users u ON r.employe_id = u.id
        WHERE DATE(r.$date_field) BETWEEN ? AND ? 
        $extra_condition
        GROUP BY r.employe_id, u.full_name
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $results = $stmt->fetchAll();
    
    $labels = [];
    $values = [];
    
    foreach ($results as $row) {
        $labels[] = $row['full_name'] ?: 'Non assigné';
        $values[] = (int)$row['count'];
    }
    
    return [
        'labels' => $labels,
        'values' => $values,
        'label' => 'Réparations par employé'
    ];
}

/**
 * Métriques détaillées
 */
function getRepairMetrics($pdo, $start_date, $end_date, $date_field, $extra_condition = '', $include_revenue = false) {
    $metrics = [];
    
    // Nombre total
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM reparations 
        WHERE DATE($date_field) BETWEEN ? AND ? 
        $extra_condition
    ");
    $stmt->execute([$start_date, $end_date]);
    $total = $stmt->fetchColumn();
    
    $metrics[] = [
        'label' => 'Total',
        'value' => number_format($total),
        'type' => 'number'
    ];
    
    // Moyenne par jour
    $days = (strtotime($end_date) - strtotime($start_date)) / (24 * 3600) + 1;
    $avg_per_day = $total / $days;
    
    $metrics[] = [
        'label' => 'Moyenne par jour',
        'value' => number_format($avg_per_day, 1),
        'type' => 'number'
    ];
    
    // Revenus si demandé
    if ($include_revenue) {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(prix_reparation), 0) as revenue
            FROM reparations 
            WHERE DATE($date_field) BETWEEN ? AND ? 
            $extra_condition
        ");
        $stmt->execute([$start_date, $end_date]);
        $revenue = $stmt->fetchColumn();
        
        $metrics[] = [
            'label' => 'Chiffre d\'affaires',
            'value' => number_format($revenue, 2) . ' €',
            'type' => 'currency'
        ];
        
        if ($total > 0) {
            $avg_revenue = $revenue / $total;
            $metrics[] = [
                'label' => 'Panier moyen',
                'value' => number_format($avg_revenue, 2) . ' €',
                'type' => 'currency'
            ];
        }
    }
    
    // Types d'appareils les plus fréquents
    $stmt = $pdo->prepare("
        SELECT type_appareil, COUNT(*) as count
        FROM reparations 
        WHERE DATE($date_field) BETWEEN ? AND ? 
        $extra_condition
        GROUP BY type_appareil
        ORDER BY count DESC
        LIMIT 3
    ");
    $stmt->execute([$start_date, $end_date]);
    $top_types = $stmt->fetchAll();
    
    foreach ($top_types as $i => $type) {
        $metrics[] = [
            'label' => 'Top ' . ($i + 1) . ' - ' . ucfirst($type['type_appareil']),
            'value' => number_format($type['count']),
            'type' => 'number'
        ];
    }
    
    return $metrics;
}

/**
 * Détails des réparations
 */
function getRepairDetails($pdo, $start_date, $end_date, $date_field, $extra_condition = '') {
    $stmt = $pdo->prepare("
        SELECT r.id, r.type_appareil, r.modele, 
               r.prix_reparation, r.$date_field as date,
               c.nom as client_nom, c.prenom as client_prenom,
               u.full_name as employe_nom
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        LEFT JOIN users u ON r.employe_id = u.id
        WHERE DATE(r.$date_field) BETWEEN ? AND ? 
        $extra_condition
        ORDER BY r.$date_field DESC
        LIMIT 100
    ");
    $stmt->execute([$start_date, $end_date]);
    $results = $stmt->fetchAll();
    
    $details = [];
    foreach ($results as $row) {
        $details[] = [
            'id' => $row['id'],
            'client' => trim($row['client_prenom'] . ' ' . $row['client_nom']),
            'appareil' => $row['type_appareil'] . ' ' . $row['modele'],
            'prix' => number_format($row['prix_reparation'] ?: 0, 2) . ' €',
            'employe' => $row['employe_nom'] ?: 'Non assigné',
            'date' => date('d/m/Y H:i', strtotime($row['date']))
        ];
    }
    
    return $details;
}

/**
 * Fonctions pour les devis (si la table existe)
 */
function getQuoteIndicators($pdo, $start_date, $end_date) {
    // Implémentation similaire pour les devis
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM devis 
        WHERE DATE(date_envoi) BETWEEN ? AND ? 
        AND statut = 'envoye'
    ");
    $stmt->execute([$start_date, $end_date]);
    $current = $stmt->fetchColumn();
    
    return [
        'main' => [
            'value' => number_format($current),
            'label' => 'Devis envoyés',
            'change' => 0
        ],
        'period' => [
            'value' => '0',
            'label' => 'Période précédente'
        ],
        'best' => [
            'value' => '0',
            'label' => 'Meilleur jour',
            'date' => '-'
        ]
    ];
}

function getQuoteOverviewChart($pdo, $start_date, $end_date) {
    return [
        'labels' => [],
        'values' => [],
        'label' => 'Devis'
    ];
}

function getQuoteTimelineChart($pdo, $start_date, $end_date) {
    return getQuoteOverviewChart($pdo, $start_date, $end_date);
}

function getQuoteBreakdownChart($pdo, $start_date, $end_date) {
    return [
        'labels' => ['Aucune donnée'],
        'values' => [1]
    ];
}

function getQuotePerformanceChart($pdo, $start_date, $end_date) {
    return [
        'labels' => [],
        'values' => [],
        'label' => 'Devis par employé'
    ];
}

function getQuoteMetrics($pdo, $start_date, $end_date) {
    return [];
}

function getQuoteDetails($pdo, $start_date, $end_date) {
    return [];
}
?>
