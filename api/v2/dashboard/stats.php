<?php
/**
 * API REST v2 - Statistiques Dashboard
 * GeekBoard Desktop Application
 * 
 * GET /api/v2/dashboard/stats
 * Header: Authorization: Bearer <token>
 */

require_once __DIR__ . '/../config.php';

// Vérifier l'authentification
$payload = require_auth();

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error_response('Méthode non autorisée', 405);
}

try {
    // Connexion à la base du magasin
    $shop_pdo = get_shop_connection($payload['subdomain']);
    
    if (!$shop_pdo) {
        error_response('Impossible de se connecter à la base du magasin', 500);
    }
    
    $stats = [];
    
    // Réparations par statut
    $stmt = $shop_pdo->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM reparations 
        GROUP BY status
    ");
    $status_counts = $stmt->fetchAll();
    
    $reparations_par_statut = [];
    $total_reparations = 0;
    foreach ($status_counts as $row) {
        $reparations_par_statut[$row['status']] = (int)$row['count'];
        $total_reparations += $row['count'];
    }
    
    $stats['reparations'] = [
        'total' => $total_reparations,
        'par_statut' => $reparations_par_statut,
        'en_cours' => (int)($reparations_par_statut['en_cours'] ?? 0),
        'en_attente' => (int)($reparations_par_statut['en_attente'] ?? 0),
        'terminees' => (int)($reparations_par_statut['terminee'] ?? 0),
        'livrees' => (int)($reparations_par_statut['livre'] ?? 0)
    ];
    
    // Chiffre d'affaires du mois
    $stmt = $shop_pdo->query("
        SELECT 
            COALESCE(SUM(prix), 0) as ca_mois
        FROM reparations 
        WHERE (status = 'terminee' OR status = 'livre')
        AND DATE_FORMAT(date_creation, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
    ");
    $ca = $stmt->fetch();
    $stats['ca_mois'] = (float)($ca['ca_mois'] ?? 0);
    
    // Chiffre d'affaires d'aujourd'hui
    $stmt = $shop_pdo->query("
        SELECT 
            COALESCE(SUM(prix), 0) as ca_jour
        FROM reparations 
        WHERE (status = 'terminee' OR status = 'livre')
        AND DATE(date_creation) = CURDATE()
    ");
    $ca_jour = $stmt->fetch();
    $stats['ca_jour'] = (float)($ca_jour['ca_jour'] ?? 0);
    
    // Nombre de clients
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM clients");
    $clients = $stmt->fetch();
    $stats['clients_total'] = (int)($clients['total'] ?? 0);
    
    // Nouveaux clients ce mois
    $stmt = $shop_pdo->query("
        SELECT COUNT(*) as nouveaux 
        FROM clients 
        WHERE DATE_FORMAT(date_creation, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
    ");
    $nouveaux = $stmt->fetch();
    $stats['clients_nouveaux_mois'] = (int)($nouveaux['nouveaux'] ?? 0);
    
    // Réparations récentes (5 dernières)
    $stmt = $shop_pdo->query("
        SELECT 
            r.id, r.numero, r.appareil, r.marque, r.status, r.date_creation,
            c.nom as client_nom, c.prenom as client_prenom
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        ORDER BY r.date_creation DESC
        LIMIT 5
    ");
    $stats['reparations_recentes'] = $stmt->fetchAll();
    
    // Informations du magasin
    $stats['shop'] = [
        'id' => $payload['shop_id'],
        'name' => $payload['shop_name'],
        'subdomain' => $payload['subdomain']
    ];
    
    success_response([
        'data' => $stats
    ]);
    
} catch (PDOException $e) {
    error_log("API v2 Dashboard Stats Error: " . $e->getMessage());
    error_response('Erreur lors de la récupération des statistiques', 500);
} catch (Exception $e) {
    error_log("API v2 Dashboard Stats Error: " . $e->getMessage());
    error_response('Erreur interne du serveur', 500);
}
?>
