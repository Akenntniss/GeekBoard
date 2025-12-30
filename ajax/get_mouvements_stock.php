<?php
/**
 * Récupérer l'historique des mouvements de stock
 */

require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

try {
    // Vérifier authentification
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Non authentifié']);
        exit;
    }
    
    // Initialiser la session shop si nécessaire
    if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
        initializeShopSession();
    }
    
    // Connexion à la base de données du shop
    $pdo = getShopDBConnection();
    
    // Paramètres optionnels
    $produit_id = filter_input(INPUT_GET, 'produit_id', FILTER_VALIDATE_INT);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 100;
    $date_start = filter_input(INPUT_GET, 'date_start', FILTER_SANITIZE_STRING);
    $date_end = filter_input(INPUT_GET, 'date_end', FILTER_SANITIZE_STRING);
    
    // Construction de la requête
    $sql = "SELECT 
                m.id,
                m.produit_id,
                m.type_mouvement,
                m.quantite,
                m.motif,
                m.date_mouvement,
                m.user_id,
                p.nom as produit_nom,
                p.reference as produit_ref,
                u.full_name as user_nom
            FROM mouvements_stock m
            LEFT JOIN produits p ON m.produit_id = p.id
            LEFT JOIN users u ON m.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    if ($produit_id) {
        $sql .= " AND m.produit_id = ?";
        $params[] = $produit_id;
    }
    
    // Filtrage par date
    if ($date_start) {
        $sql .= " AND DATE(m.date_mouvement) >= ?";
        $params[] = $date_start;
    }
    
    if ($date_end) {
        $sql .= " AND DATE(m.date_mouvement) <= ?";
        $params[] = $date_end;
    }
    
    $sql .= " ORDER BY m.date_mouvement DESC, m.id DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Enrichir les données avec des informations supplémentaires
    foreach ($mouvements as &$mvt) {
        // Déterminer le type de mouvement pour l'affichage
        $motif = strtolower($mvt['motif'] ?? '');
        
        if (strpos($motif, 'réparation') !== false || strpos($motif, 'reparation') !== false) {
            $mvt['type_affichage'] = 'Utilisé dans réparation';
            $mvt['icon'] = 'fa-tools';
            $mvt['color'] = '#3b82f6';
            
            // Extraire l'ID de réparation si présent
            if (preg_match('/#(\d+)/', $motif, $matches)) {
                $mvt['reparation_id'] = $matches[1];
            }
        } elseif (strpos($motif, 'prêt') !== false || strpos($motif, 'pret') !== false || 
                  strpos($motif, 'avance') !== false || strpos($motif, 'partenaire') !== false) {
            $mvt['type_affichage'] = 'Prêt';
            $mvt['icon'] = 'fa-handshake';
            $mvt['color'] = '#10b981';
        } else {
            $mvt['type_affichage'] = 'Autre';
            $mvt['icon'] = 'fa-edit';
            $mvt['color'] = '#6b7280';
        }
        
        // Formater la date
        $mvt['date_formattee'] = date('d/m/Y H:i', strtotime($mvt['date_mouvement']));
        
        // Informations utilisateur
        $mvt['user_display'] = $mvt['user_nom'] ?? 'Système';
        if (empty(trim($mvt['user_display']))) {
            $mvt['user_display'] = 'Système';
        }
        
        // Badge de quantité
        if ($mvt['type_mouvement'] === 'entree') {
            $mvt['quantite_display'] = '+' . $mvt['quantite'];
            $mvt['quantite_color'] = '#10b981';
        } else {
            $mvt['quantite_display'] = '-' . $mvt['quantite'];
            $mvt['quantite_color'] = '#ef4444';
        }
    }
    
    echo json_encode([
        'success' => true,
        'mouvements' => $mouvements,
        'total' => count($mouvements)
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_mouvements_stock: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
