<?php
session_start();
// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Démarrer le buffer de sortie pour capturer les sorties indésirables
ob_start();

try {
    // Utiliser le système de configuration multi-magasin
    $config_path = realpath(__DIR__ . '/../config/database.php');
    
    if (!file_exists($config_path)) {
        throw new Exception('Fichier de configuration introuvable');
    }
    
    require_once $config_path;

    // Initialiser la session du magasin
    initializeShopSession();

    // Obtenir la connexion à la base de données du magasin de l'utilisateur
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }

    // Requête pour récupérer les réparations disponibles pour attribution
    $query = "
        SELECT 
            r.id,
            r.statut,
            r.type_appareil,
            r.modele,
            r.description_probleme,
            r.date_reception,
            r.employe_id,
            r.urgent,
            c.nom AS client_nom,
            c.prenom AS client_prenom,
            c.telephone AS client_telephone,
            u.username AS employe_nom,
            u.full_name AS employe_prenom
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        LEFT JOIN users u ON r.employe_id = u.id
        WHERE r.archive = 'NON'
        AND r.statut IN (
            'nouvelle_intervention',
            'nouveau_diagnostique', 
            'nouvelle_commande',
            'En attente',
            'en_attente_responsable',
            'en_attente_livraison',
            'en_attente_accord_client',
            'en_cours_diagnostique',
            'en_cours_intervention',
            'devis_accepte',
            'devis_refuse'
        )
        ORDER BY 
            r.urgent DESC,
            r.date_reception DESC
    ";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute();
    
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour l'affichage
    foreach ($repairs as &$repair) {
        // S'assurer que les valeurs nulles sont gérées correctement
        $repair['client_nom'] = $repair['client_nom'] ?? 'Client inconnu';
        $repair['client_prenom'] = $repair['client_prenom'] ?? '';
        $repair['client_telephone'] = $repair['client_telephone'] ?? '';
        $repair['employe_nom'] = $repair['employe_nom'] ?? null;
        $repair['employe_prenom'] = $repair['employe_prenom'] ?? null;
        
        // Formater la date de réception
        if ($repair['date_reception']) {
            $repair['date_reception_formatted'] = date('d/m/Y H:i', strtotime($repair['date_reception']));
        }
        
        // Indicateur si urgent
        $repair['is_urgent'] = (bool)$repair['urgent'];
        
        // Indicateur si déjà attribué
        $repair['is_assigned'] = !empty($repair['employe_id']);
    }
    
    // Nettoyer le buffer avant d'envoyer la réponse JSON
    ob_clean();
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => true,
        'repairs' => $repairs,
        'count' => count($repairs)
    ]);

} catch (Exception $e) {
    // Nettoyer le buffer en cas d'erreur
    ob_clean();
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des réparations : ' . $e->getMessage()
    ]);
}
?>
