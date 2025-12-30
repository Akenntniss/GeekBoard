<?php
/**
 * API REST v2 - Détails d'une Réparation
 * GeekBoard Desktop Application
 * 
 * GET /api/v2/reparations/get?id=123
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
    error_response('ID de réparation requis');
}

try {
    // Connexion à la base du magasin
    $shop_pdo = get_shop_connection($payload['subdomain']);
    
    if (!$shop_pdo) {
        error_response('Impossible de se connecter à la base du magasin', 500);
    }
    
    // Récupérer la réparation
    $sql = "SELECT 
                r.*,
                c.id as client_id,
                c.nom as client_nom,
                c.prenom as client_prenom,
                c.telephone as client_telephone,
                c.email as client_email,
                c.adresse as client_adresse
            FROM reparations r
            LEFT JOIN clients c ON r.client_id = c.id
            WHERE r.id = ?
            LIMIT 1";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([$id]);
    $reparation = $stmt->fetch();
    
    if (!$reparation) {
        error_response('Réparation non trouvée', 404);
    }
    
    // Récupérer l'historique des statuts si la table existe
    $historique = [];
    try {
        $stmt = $shop_pdo->prepare("SELECT * FROM reparation_historique WHERE reparation_id = ? ORDER BY date_changement DESC");
        $stmt->execute([$id]);
        $historique = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Table n'existe peut-être pas, ignorer
    }
    
    // Récupérer les pièces associées si la table existe
    $pieces = [];
    try {
        $stmt = $shop_pdo->prepare("
            SELECT rp.*, p.nom as piece_nom, p.reference 
            FROM reparation_pieces rp 
            LEFT JOIN pieces p ON rp.piece_id = p.id 
            WHERE rp.reparation_id = ?
        ");
        $stmt->execute([$id]);
        $pieces = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Table n'existe peut-être pas, ignorer
    }
    
    success_response([
        'data' => $reparation,
        'historique' => $historique,
        'pieces' => $pieces
    ]);
    
} catch (PDOException $e) {
    error_log("API v2 Reparation Get Error: " . $e->getMessage());
    error_response('Erreur lors de la récupération de la réparation', 500);
} catch (Exception $e) {
    error_log("API v2 Reparation Get Error: " . $e->getMessage());
    error_response('Erreur interne du serveur', 500);
}
?>
