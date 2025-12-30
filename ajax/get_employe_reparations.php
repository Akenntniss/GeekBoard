<?php
/**
 * AJAX - Récupérer les réparations d'un employé
 */

header('Content-Type: application/json');

// Capture errors to JSON instead of blank 500
error_reporting(E_ALL);
ini_set('display_errors', 0);
set_error_handler(function($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo json_encode(['success' => false, 'message' => 'Erreur fatale: ' . $e['message']]);
    }
});

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/subdomain_config.php';

function json_fail($msg) {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

try {
    if (function_exists('initializeShopSession')) {
        initializeShopSession();
    }
    $pdo = getShopDBConnection();
    if (!$pdo) {
        json_fail('Connexion base de données introuvable.');
    }
    
    // Récupérer les paramètres
    $employe_id = isset($_GET['employe_id']) ? (int)$_GET['employe_id'] : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    
    if ($employe_id <= 0) {
        json_fail('ID employé invalide');
    }
    
    // Condition de date pour les 30 derniers jours
    $date_condition = '';
    if ($type === '30days') {
        $date_condition = ' AND rl.date_action >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
    }
    
    // Requête pour récupérer les réparations de l'employé
    $sql = "
        SELECT DISTINCT 
            r.id,
            r.date_reception,
            r.modele,
            r.description_probleme,
            r.statut,
            s.nom as statut_nom
        FROM reparation_logs rl
        INNER JOIN reparations r ON rl.reparation_id = r.id
        LEFT JOIN statuts s ON r.statut_id = s.id
        WHERE rl.employe_id = :employe_id
        AND rl.action_type = 'changement_statut'
        AND (
            rl.statut_apres LIKE '%effectue%' 
            OR rl.statut_apres LIKE '%annule%' 
            OR rl.statut_apres LIKE '%termine%'
            OR rl.statut_apres LIKE '%fini%'
        )
        $date_condition
        ORDER BY r.date_reception DESC
        LIMIT 100
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['employe_id' => $employe_id]);
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données
    $formatted = [];
    foreach ($reparations as $rep) {
        // Déterminer la couleur du statut
        $statut_nom = $rep['statut_nom'] ?? $rep['statut'] ?? 'Inconnu';
        $statut_lower = strtolower($statut_nom);
        $statut_couleur = '#6b7280'; // Gris par défaut
        
        // Vert - Statuts terminés/positifs
        if (preg_match('/(effectue|termine|fini|répare|repare|livr|récupér|recuper|remis|clotur|ok|complete|rendu)/i', $statut_lower)) {
            $statut_couleur = '#10b981'; // Vert
        }
        // Rouge - Statuts négatifs
        elseif (preg_match('/(annul|refus|probleme|problème|abandon|rejet|echec|échoué)/i', $statut_lower)) {
            $statut_couleur = '#ef4444'; // Rouge
        }
        // Orange - En attente/Devis
        elseif (preg_match('/(attente|devis|commande|pièce|piece|stock)/i', $statut_lower)) {
            $statut_couleur = '#f59e0b'; // Orange
        }
        // Bleu - En cours
        elseif (preg_match('/(cours|diagnostic|réparation|reparation|travail|progress|encours)/i', $statut_lower)) {
            $statut_couleur = '#3b82f6'; // Bleu
        }
        // Violet - Nouveau/Réception
        elseif (preg_match('/(nouveau|nouvelle|reception|réception|entree|entrée)/i', $statut_lower)) {
            $statut_couleur = '#8b5cf6'; // Violet
        }
        
        $formatted[] = [
            'id' => $rep['id'],
            'date' => $rep['date_reception'] ? date('d/m/Y', strtotime($rep['date_reception'])) : 'N/A',
            'modele' => $rep['modele'] ?? 'N/A',
            'probleme' => $rep['description_probleme'] ? (mb_strlen($rep['description_probleme']) > 80 ? mb_substr($rep['description_probleme'], 0, 80) . '...' : $rep['description_probleme']) : 'N/A',
            'statut' => $statut_nom,
            'statut_couleur' => $statut_couleur
        ];
    }
    
    echo json_encode([
        'success' => true,
        'reparations' => $formatted,
        'count' => count($formatted)
    ]);
    
} catch (Throwable $e) {
    json_fail('Erreur: ' . $e->getMessage());
}
