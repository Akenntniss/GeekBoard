<?php
/**
 * API KPI pour Dashboard GeekBoard
 * Fournit toutes les données d'analyse de performance
 * 
 * Actions disponibles :
 * - chiffre_affaires_global
 * - chiffre_affaires_employe
 * - kpi_reparations
 * - analyse_comportement 
 * - analyse_temps
 * - analyse_autonomie
 * - analyse_gardiennage
 * - panier_moyen
 */

// Inclure les configurations nécessaires
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';
require_once __DIR__ . '/includes/functions.php';

// Headers JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// Vérification de l'authentification
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

// Récupération des paramètres
$action = $_GET['action'] ?? '';
$date_debut = $_GET['date_start'] ?? date('Y-m-d', strtotime('-30 days'));
$date_fin = $_GET['date_end'] ?? date('Y-m-d');
$employe_id = $_GET['user_id'] ?? '';

// Si non admin et employe_id non spécifié, filtrer sur l'utilisateur courant
if ($user_role !== 'admin' && empty($employe_id)) {
    $employe_id = $user_id;
}

try {
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }
    
    // Router vers la bonne fonction selon l'action
    switch ($action) {
        case 'chiffre_affaires_global':
            $result = getChiffreAffairesGlobal($pdo, $date_debut, $date_fin);
            break;
            
        case 'chiffre_affaires_employe':
            $result = getChiffreAffairesEmploye($pdo, $date_debut, $date_fin, $employe_id);
            break;
            
        case 'kpi_reparations':
            $result = getKPIReparations($pdo, $date_debut, $date_fin, $employe_id);
            break;
            
        case 'analyse_comportement':
            $result = getAnalyseComportement($pdo, $date_debut, $date_fin, $employe_id);
            break;
            
        case 'analyse_temps':
            $result = getAnalyseTemps($pdo, $date_debut, $date_fin, $employe_id);
            break;
            
        case 'analyse_autonomie':
            $result = getAnalyseAutonomie($pdo, $date_debut, $date_fin, $employe_id);
            break;
            
        case 'analyse_gardiennage':
            $result = getAnalyseGardiennage($pdo, $date_debut, $date_fin);
            break;
            
        case 'panier_moyen':
            $result = getPanierMoyen($pdo, $date_debut, $date_fin);
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
 * CHIFFRE D'AFFAIRES GLOBAL
 * 
 * Calcule le CA encaissé (réparations restituées) et le CA total (encaissé + à encaisser)
 */
function getChiffreAffairesGlobal($pdo, $date_debut, $date_fin) {
    $sql = "
        SELECT 
            COUNT(CASE WHEN statut = 'restitue' THEN 1 END) as nb_restituees,
            COALESCE(SUM(CASE WHEN statut = 'restitue' THEN prix_reparation END), 0) as ca_encaisse,
            COUNT(CASE WHEN statut IN ('restitue', 'reparation_effectue') THEN 1 END) as nb_total,
            COALESCE(SUM(CASE WHEN statut IN ('restitue', 'reparation_effectue') THEN prix_reparation END), 0) as ca_total
        FROM reparations
        WHERE date_reception BETWEEN ? AND ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date_debut, $date_fin]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calcul panier moyen
    $result['panier_moyen_encaisse'] = $result['nb_restituees'] > 0 
        ? round($result['ca_encaisse'] / $result['nb_restituees'], 2) 
        : 0;
    $result['panier_moyen_total'] = $result['nb_total'] > 0 
        ? round($result['ca_total'] / $result['nb_total'], 2) 
        : 0;
    $result['ca_a_encaisser'] = $result['ca_total'] - $result['ca_encaisse'];
    
    return $result;
}

/**
 * CHIFFRE D'AFFAIRES PAR EMPLOYÉ
 * 
 * Calcule le CA pour chaque employé (basé sur qui a démarré ET terminé la réparation)
 */
function getChiffreAffairesEmploye($pdo, $date_debut, $date_fin, $employe_id = '') {
    $sql = "
        SELECT 
            u.id as employe_id,
            u.full_name as employe_nom,
            COUNT(DISTINCT CASE WHEN r.statut = 'restitue' THEN r.id END) as nb_restituees,
            COALESCE(SUM(CASE WHEN r.statut = 'restitue' THEN r.prix_reparation END), 0) as ca_encaisse,
            COUNT(DISTINCT CASE WHEN r.statut IN ('restitue', 'reparation_effectue') THEN r.id END) as nb_total,
            COALESCE(SUM(CASE WHEN r.statut IN ('restitue', 'reparation_effectue') THEN r.prix_reparation END), 0) as ca_total
        FROM users u
        INNER JOIN reparations r ON r.id IN (
            SELECT DISTINCT rl1.reparation_id
            FROM reparation_logs rl1
            INNER JOIN reparation_logs rl2 ON rl1.reparation_id = rl2.reparation_id
            WHERE rl1.employe_id = u.id
            AND rl1.action_type = 'demarrage'
            AND rl2.employe_id = u.id
            AND rl2.action_type = 'changement_statut'
            AND rl2.statut_apres = 'reparation_effectue'
        )
        WHERE u.role IN ('admin', 'technicien')
        AND r.date_reception BETWEEN ? AND ?
        " . (!empty($employe_id) ? "AND u.id = ?" : "") . "
        GROUP BY u.id, u.full_name
        ORDER BY ca_total DESC
    ";
    
    $params = [$date_debut, $date_fin];
    if (!empty($employe_id)) {
        $params[] = $employe_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcul panier moyen pour chaque employé
    foreach ($results as &$row) {
        $row['panier_moyen_encaisse'] = $row['nb_restituees'] > 0 
            ? round($row['ca_encaisse'] / $row['nb_restituees'], 2) 
            : 0;
        $row['panier_moyen_total'] = $row['nb_total'] > 0 
            ? round($row['ca_total'] / $row['nb_total'], 2) 
            : 0;
        $row['ca_a_encaisser'] = $row['ca_total'] - $row['ca_encaisse'];
    }
    
    return $results;
}

/**
 * KPI RÉPARATIONS
 * 
 * Statistiques sur les réparations : nouvelles, effectuées, restituées
 */
function getKPIReparations($pdo, $date_debut, $date_fin, $employe_id = '') {
    // Requête globale
    $sql_global = "
        SELECT 
            COUNT(CASE WHEN statut = 'nouvelle_intervention' THEN 1 END) as nb_nouvelles,
            COUNT(CASE WHEN statut = 'reparation_effectue' THEN 1 END) as nb_effectuees,
            COUNT(CASE WHEN statut = 'restitue' THEN 1 END) as nb_restituees,
            COUNT(CASE WHEN statut = 'en_cours_intervention' THEN 1 END) as nb_en_cours
        FROM reparations
        WHERE date_reception BETWEEN ? AND ?
    ";
    
    $stmt = $pdo->prepare($sql_global);
    $stmt->execute([$date_debut, $date_fin]);
    $global = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Par employé si demandé
    $par_employe = [];
    if (!empty($employe_id)) {
        $sql_employe = "
            SELECT 
                u.id as employe_id,
                u.full_name as employe_nom,
                COUNT(DISTINCT r.id) as nb_effectuees
            FROM users u
            INNER JOIN reparations r ON r.id IN (
                SELECT DISTINCT rl1.reparation_id
                FROM reparation_logs rl1
                INNER JOIN reparation_logs rl2 ON rl1.reparation_id = rl2.reparation_id
                WHERE rl1.employe_id = u.id
                AND rl1.action_type = 'demarrage'
                AND rl2.employe_id = u.id
                AND rl2.action_type = 'changement_statut'
                AND rl2.statut_apres = 'reparation_effectue'
            )
            WHERE u.id = ?
            AND r.date_reception BETWEEN ? AND ?
            GROUP BY u.id, u.full_name
        ";
        
        $stmt = $pdo->prepare($sql_employe);
        $stmt->execute([$employe_id, $date_debut, $date_fin]);
        $par_employe = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return [
        'global' => $global,
        'par_employe' => $par_employe
    ];
}

/**
 * ANALYSE DU COMPORTEMENT DES EMPLOYÉS
 * 
 * Présence, retards, absences (basé sur presence_events)
 */
function getAnalyseComportement($pdo, $date_debut, $date_fin, $employe_id = '') {
    // Note : Cette fonction nécessiterait plus de détails sur la structure de presence_events
    // Pour l'instant, retourne un placeholder
    
    $sql = "
        SELECT 
            u.id as employe_id,
            u.full_name as employe_nom,
            COUNT(DISTINCT pe.id) as nb_evenements,
            COUNT(DISTINCT CASE WHEN pe.status = 'approved' THEN pe.id END) as nb_presences
        FROM users u
        LEFT JOIN presence_events pe ON pe.employee_id = u.id
            AND pe.date_start BETWEEN ? AND ?
        WHERE u.role IN ('admin', 'technicien')
        " . (!empty($employe_id) ? "AND u.id = ?" : "") . "
        GROUP BY u.id, u.full_name
    ";
    
    $params = [$date_debut, $date_fin];
    if (!empty($employe_id)) {
        $params[] = $employe_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ANALYSE DES TEMPS DE RÉPARATION
 * 
 * Double analyse : temps technique (création → réparation effectuée) et temps total (création → restitution)
 */
function getAnalyseTemps($pdo, $date_debut, $date_fin, $employe_id = '') {
    $sql = "
        SELECT 
            r.id,
            r.type_appareil,
            r.marque,
            r.modele,
            r.date_reception,
            u.full_name as employe_nom,
            TIMESTAMPDIFF(HOUR, 
                r.date_reception,
                (SELECT date_action FROM reparation_logs 
                 WHERE reparation_id = r.id 
                 AND action_type = 'changement_statut' 
                 AND statut_apres = 'reparation_effectue' 
                 ORDER BY date_action DESC LIMIT 1)
            ) as temps_technique_heures,
            TIMESTAMPDIFF(HOUR, 
                r.date_reception,
                r.date_modification
            ) as temps_total_heures
        FROM reparations r
        LEFT JOIN reparation_logs rl ON r.id = rl.reparation_id 
            AND rl.action_type = 'demarrage'
        LEFT JOIN users u ON rl.employe_id = u.id
        WHERE r.date_reception BETWEEN ? AND ?
        AND r.statut IN ('reparation_effectue', 'restitue')
        " . (!empty($employe_id) ? "AND rl.employe_id = ?" : "") . "
        GROUP BY r.id
        ORDER BY r.date_reception DESC
    ";
    
    $params = [$date_debut, $date_fin];
    if (!empty($employe_id)) {
        $params[] = $employe_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcul des moyennes
    $total_technique = 0;
    $total_global = 0;
    $count = 0;
    
    foreach ($reparations as $rep) {
        if ($rep['temps_technique_heures'] !== null) {
            $total_technique += $rep['temps_technique_heures'];
            $total_global += $rep['temps_total_heures'];
            $count++;
        }
    }
    
    $moyenne_technique = $count > 0 ? round($total_technique / $count, 2) : 0;
    $moyenne_totale = $count > 0 ? round($total_global / $count, 2) : 0;
    
    return [
        'reparations' => $reparations,
        'moyenne_technique_heures' => $moyenne_technique,
        'moyenne_totale_heures' => $moyenne_totale,
        'total_reparations' => $count
    ];
}

/**
 * ANALYSE D'AUTONOMIE
 * 
 * Réparations effectuées en totale autonomie (un seul employé)
 */
function getAnalyseAutonomie($pdo, $date_debut, $date_fin, $employe_id = '') {
    $sql = "
        SELECT 
            u.id as employe_id,
            u.full_name as employe_nom,
            COUNT(DISTINCT r.id) as total_reparations,
            COUNT(DISTINCT CASE 
                WHEN (SELECT COUNT(DISTINCT employe_id) 
                      FROM reparation_logs 
                      WHERE reparation_id = r.id) = 1 
                THEN r.id 
            END) as reparations_autonomes,
            ROUND(
                (COUNT(DISTINCT CASE 
                    WHEN (SELECT COUNT(DISTINCT employe_id) 
                          FROM reparation_logs 
                          WHERE reparation_id = r.id) = 1 
                    THEN r.id 
                END) * 100.0) / COUNT(DISTINCT r.id), 
                2
            ) as taux_autonomie
        FROM users u
        INNER JOIN reparation_logs rl ON rl.employe_id = u.id
        INNER JOIN reparations r ON r.id = rl.reparation_id
        WHERE r.date_reception BETWEEN ? AND ?
        AND u.role IN ('admin', 'technicien')
        " . (!empty($employe_id) ? "AND u.id = ?" : "") . "
        GROUP BY u.id, u.full_name
        ORDER BY taux_autonomie DESC
    ";
    
    $params = [$date_debut, $date_fin];
    if (!empty($employe_id)) {
        $params[] = $employe_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ANALYSE DU GARDIENNAGE
 * 
 * Appareils en gardiennage et durées
 */
function getAnalyseGardiennage($pdo, $date_debut, $date_fin) {
    // Appareils actuellement en gardiennage (actifs)
    $sql_actifs = "
        SELECT 
            COUNT(*) as nb_appareils_actifs,
            COALESCE(SUM(montant_total), 0) as cout_total_actif,
            AVG(DATEDIFF(CURDATE(), date_debut)) as duree_moyenne_jours
        FROM gardiennage
        WHERE est_actif = 1
    ";
    
    $stmt = $pdo->prepare($sql_actifs);
    $stmt->execute();
    $actifs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Historique sur la période
    $sql_periode = "
        SELECT 
            COUNT(*) as nb_total_periode,
            COALESCE(SUM(montant_total), 0) as cout_total_periode,
            AVG(CASE 
                WHEN date_fin IS NOT NULL 
                THEN DATEDIFF(date_fin, date_debut) 
                ELSE DATEDIFF(CURDATE(), date_debut) 
            END) as duree_moyenne_periode
        FROM gardiennage
        WHERE date_debut BETWEEN ? AND ?
    ";
    
    $stmt = $pdo->prepare($sql_periode);
    $stmt->execute([$date_debut, $date_fin]);
    $periode = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'actifs' => $actifs,
        'periode' => $periode
    ];
}

/**
 * ÉVOLUTION DU PANIER MOYEN
 * 
 * Analyse de l'évolution du panier moyen par semaine/mois
 */
function getPanierMoyen($pdo, $date_debut, $date_fin) {
    $sql = "
        SELECT 
            DATE_FORMAT(date_reception, '%Y-%m') as mois,
            COUNT(*) as nb_reparations,
            COALESCE(AVG(prix_reparation), 0) as panier_moyen
        FROM reparations
        WHERE date_reception BETWEEN ? AND ?
        AND statut IN ('restitue', 'reparation_effectue')
        GROUP BY DATE_FORMAT(date_reception, '%Y-%m')
        ORDER BY mois ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date_debut, $date_fin]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
