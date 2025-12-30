<?php
/**
 * API REST v2 - Détails d'un Client
 * GeekBoard Desktop Application
 * 
 * GET /api/v2/clients/get?id=123
 * Header: Authorization: Bearer <token>
 */

require_once __DIR__ . '/../config.php';

// Vérifier l'authentification
$payload = require_auth();

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error_response('Méthode non autorisée', 405);
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    error_response('ID de client requis');
}

try {
    // Connexion à la base du magasin
    $shop_pdo = get_shop_connection($payload['subdomain']);
    
    if (!$shop_pdo) {
        error_response('Impossible de se connecter à la base du magasin', 500);
    }
    
    // Récupérer le client
    $stmt = $shop_pdo->prepare("SELECT * FROM clients WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $client = $stmt->fetch();
    
    if (!$client) {
        error_response('Client non trouvé', 404);
    }
    
    // Récupérer les réparations du client
    $stmt = $shop_pdo->prepare("
        SELECT id, numero, appareil, marque, modele, probleme, status, prix, date_creation
        FROM reparations 
        WHERE client_id = ? 
        ORDER BY date_creation DESC 
        LIMIT 20
    ");
    $stmt->execute([$id]);
    $reparations = $stmt->fetchAll();
    
    // Statistiques du client
    $stmt = $shop_pdo->prepare("
        SELECT 
            COUNT(*) as total_reparations,
            SUM(CASE WHEN status = 'terminee' OR status = 'livre' THEN prix ELSE 0 END) as total_depense,
            MIN(date_creation) as premiere_visite,
            MAX(date_creation) as derniere_visite
        FROM reparations 
        WHERE client_id = ?
    ");
    $stmt->execute([$id]);
    $stats = $stmt->fetch();
    
    success_response([
        'data' => $client,
        'reparations' => $reparations,
        'statistiques' => [
            'total_reparations' => (int)($stats['total_reparations'] ?? 0),
            'total_depense' => (float)($stats['total_depense'] ?? 0),
            'premiere_visite' => $stats['premiere_visite'],
            'derniere_visite' => $stats['derniere_visite']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("API v2 Client Get Error: " . $e->getMessage());
    error_response('Erreur lors de la récupération du client', 500);
} catch (Exception $e) {
    error_log("API v2 Client Get Error: " . $e->getMessage());
    error_response('Erreur interne du serveur', 500);
}
?>
