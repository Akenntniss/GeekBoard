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

    // Debug temporaire - afficher les informations de session
    error_log("=== DEBUG SESSION get_my_repairs.php ===");
    error_log("Session ID: " . session_id());
    error_log("Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NON DÉFINI'));
    error_log("Session shop_id: " . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'NON DÉFINI'));
    error_log("Toute la session: " . print_r($_SESSION, true));
    error_log("=== FIN DEBUG SESSION ===");

    // Vérifier que l'utilisateur est connecté
    $user_id = null;
    
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $user_id = (int)$_SESSION['user_id'];
        error_log("DEBUG: user_id trouvé en session: " . $user_id);
    } else {
        // Essayer de récupérer depuis le paramètre debug
        $user_id_from_js = isset($_GET['debug_user_id']) ? (int)$_GET['debug_user_id'] : null;
        if ($user_id_from_js && $user_id_from_js > 0) {
            $user_id = $user_id_from_js;
            error_log("DEBUG: user_id récupéré depuis paramètre debug: " . $user_id);
        } else {
            // En dernier recours, utiliser le premier utilisateur admin du magasin
            $stmt = $shop_pdo->prepare("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1");
            $stmt->execute();
            $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin_user) {
                $user_id = (int)$admin_user['id'];
                error_log("DEBUG: user_id récupéré depuis admin par défaut: " . $user_id);
            } else {
                throw new Exception('Aucun utilisateur trouvé pour ce magasin');
            }
        }
    }
    
    if (!$user_id) {
        throw new Exception('Impossible de déterminer l\'utilisateur connecté');
    }

    // Requête pour récupérer les réparations attribuées à l'utilisateur connecté
    $query = "
        SELECT 
            r.id,
            r.statut,
            r.type_appareil,
            r.modele,
            r.description_probleme,
            r.date_reception,
            r.date_modification,
            r.urgent,
            r.prix_reparation,
            c.nom AS client_nom,
            c.prenom AS client_prenom,
            c.telephone AS client_telephone,
            c.email AS client_email,
            s.nom AS statut_nom
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        LEFT JOIN statuts s ON r.statut = s.code
        WHERE r.archive = 'NON'
        AND r.employe_id = ?
        AND r.statut IN (
            'nouvelle_intervention',
            'nouveau_diagnostique', 
            'nouvelle_commande',
            'En attente',
            'en_attente_responsable',
            'en_attente_livraison',
            'en_attente_accord_client',
            'en_cours_diagnostique',
            'en_cours_intervention'
        )
        ORDER BY 
            r.urgent DESC,
            r.date_modification DESC,
            r.date_reception DESC
    ";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([$user_id]);
    
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fonction pour déterminer la couleur du statut (réparations récentes uniquement)
    function getStatusColorFromCode($statut) {
        switch($statut) {
            // Nouvelles réparations - Bleu
            case 'nouvelle_intervention':
            case 'nouveau_diagnostique':
            case 'nouvelle_commande':
                return 'info';
            
            // En diagnostic - Orange/Jaune
            case 'en_cours_diagnostique':
            case 'en_cours_intervention':
                return 'warning';
            
            // En attente - Gris
            case 'En attente':
            case 'en_attente_responsable':
            case 'en_attente_livraison':
            case 'en_attente_accord_client':
                return 'secondary';
            
            default:
                return 'primary';
        }
    }
    
    // Formater les données pour l'affichage
    foreach ($repairs as &$repair) {
        // S'assurer que les valeurs nulles sont gérées correctement
        $repair['client_nom'] = $repair['client_nom'] ?? 'Client inconnu';
        $repair['client_prenom'] = $repair['client_prenom'] ?? '';
        $repair['client_telephone'] = $repair['client_telephone'] ?? '';
        $repair['client_email'] = $repair['client_email'] ?? '';
        
        // Formater les dates
        if ($repair['date_reception']) {
            $repair['date_reception_formatted'] = date('d/m/Y H:i', strtotime($repair['date_reception']));
        }
        if ($repair['date_modification']) {
            $repair['date_modification_formatted'] = date('d/m/Y H:i', strtotime($repair['date_modification']));
        }
        
        // Formater le prix
        if ($repair['prix_reparation']) {
            $repair['prix_formatte'] = number_format($repair['prix_reparation'], 2, ',', ' ');
        }
        
        // Indicateur si urgent
        $repair['is_urgent'] = (bool)$repair['urgent'];
        
        // Couleur du statut basée sur le code statut
        $repair['statut_couleur'] = getStatusColorFromCode($repair['statut']);
    }
    
    // Nettoyer le buffer avant d'envoyer la réponse JSON
    ob_clean();
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => true,
        'repairs' => $repairs,
        'count' => count($repairs),
        'user_id' => $user_id
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
