<?php
/**
 * Récupération des réparations attribuées à l'utilisateur connecté
 */

// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';

// Forcer l'initialisation de la session magasin
initializeShopSession();

// Headers JSON
header('Content-Type: application/json; charset=utf-8');

// Vérifier que c'est bien une requête GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier qu'on a une session magasin et utilisateur
if (!isset($_SESSION['shop_id'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'error' => 'Session magasin non valide',
        'message' => 'Session magasin manquante.',
        'debug' => [
            'session_keys' => array_keys($_SESSION ?? [])
        ]
    ]);
    exit;
}

// Debug : vérifier les clés de session disponibles
error_log("🔍 SESSION DEBUG: " . print_r(array_keys($_SESSION), true));

// Vérifier l'ID utilisateur (plusieurs possibilités)
$user_id = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
} elseif (isset($_SESSION['employe_id'])) {
    $user_id = $_SESSION['employe_id'];
} else {
    // Temporaire : utiliser l'utilisateur par défaut (ID 6 = Administrateur Mkmkmk)
    // En production, ceci devrait être remplacé par une vraie gestion de session
    $user_id = 6;
    error_log("⚠️ ATTENTION: Utilisation de l'utilisateur par défaut ID 6");
}

error_log("🔍 USER_ID final utilisé: " . $user_id);

// Récupérer la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();
if (!$shop_pdo) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Connexion base de données échouée'
    ]);
    exit;
}

try {
    // Utiliser l'ID utilisateur détecté
    error_log("🔍 USER_ID utilisé: " . $user_id);
    
    // Requête pour récupérer les réparations attribuées à l'utilisateur
    $sql = "
        SELECT r.*, 
               c.nom as client_nom, 
               c.prenom as client_prenom, 
               c.telephone as client_telephone, 
               c.email as client_email,
               s.nom as statut_nom,
               sc.couleur as statut_couleur,
               u.full_name as employe_nom
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        LEFT JOIN statuts s ON r.statut = s.code
        LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
        LEFT JOIN users u ON r.employe_id = u.id
        WHERE r.employe_id = ?
        ORDER BY r.date_reception DESC
    ";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour l'affichage
    foreach ($reparations as &$reparation) {
        // S'assurer que les noms de clients ne sont pas null
        $reparation['client_nom'] = $reparation['client_nom'] ?? 'N/A';
        $reparation['client_prenom'] = $reparation['client_prenom'] ?? '';
        
        // Formater la date
        if ($reparation['date_reception']) {
            $reparation['date_reception_formatted'] = date('d/m/Y H:i', strtotime($reparation['date_reception']));
        }
    }
    
    // Récupérer des statistiques
    $stats_sql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN r.statut IN ('nouvelle_intervention', 'nouveau_diagnostique', 'nouvelle_commande') THEN 1 ELSE 0 END) as nouvelles,
            SUM(CASE WHEN r.statut IN ('en_cours_intervention', 'en_cours_diagnostique') THEN 1 ELSE 0 END) as en_cours,
            SUM(CASE WHEN r.statut IN ('en_attente_accord_client', 'en_attente_livraison') THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN r.statut IN ('reparation_effectue', 'restitue') THEN 1 ELSE 0 END) as terminees
        FROM reparations r
        WHERE r.employe_id = ?
    ";
    
    $stmt_stats = $shop_pdo->prepare($stats_sql);
    $stmt_stats->execute([$user_id]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    // Retourner les résultats
    echo json_encode([
        'success' => true,
        'reparations' => $reparations,
        'count' => count($reparations),
        'stats' => $stats,
        'user_id' => $user_id,
        'message' => count($reparations) > 0 ? 
            count($reparations) . ' réparation(s) vous sont attribuées.' : 
            'Aucune réparation ne vous est attribuée pour le moment.'
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des réparations de l'utilisateur : " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des données : ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur générale dans get_my_reparations.php : " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur inattendue : ' . $e->getMessage()
    ]);
}
?>
