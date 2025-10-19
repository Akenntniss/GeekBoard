<?php
session_start();
header('Content-Type: application/json');

// Initialiser la connexion à la base de données
require_once __DIR__ . '/../config/database.php';

// Debug simple
$debug = [
    'session_data' => $_SESSION ?? [],
    'post_data' => $_POST,
    'request_method' => $_SERVER['REQUEST_METHOD']
];

error_log("Simple Commande No User Debug: " . json_encode($debug));

// Vérifications de base
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée', 'debug' => $debug]);
    exit;
}

// La vérification du shop_id est gérée automatiquement par getShopDBConnection()

// Récupérer shop_id depuis l'URL si fourni
$shop_id_from_request = $_POST['shop_id'] ?? $_GET['shop_id'] ?? null;
if ($shop_id_from_request) {
    $_SESSION['shop_id'] = $shop_id_from_request;
}

try {
    // Utiliser la fonction système pour obtenir la connexion
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo json_encode(['success' => false, 'message' => 'Impossible de se connecter à la base du magasin', 'debug' => $debug]);
        exit;
    }
    
    // Vérifier quelle base nous utilisons
    try {
        $db_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
        $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
        $debug['current_database'] = $db_info['current_db'] ?? 'Inconnue';
    } catch (Exception $e) {
        $debug['db_check_error'] = $e->getMessage();
    }
    
    // Récupérer les données POST
    $client_id = intval($_POST['client_id'] ?? 0);
    $fournisseur_id = intval($_POST['fournisseur_id'] ?? 0);
    $nom_piece = trim($_POST['nom_piece'] ?? '');
    $quantite = intval($_POST['quantite'] ?? 1);
    $prix_estime = floatval($_POST['prix_estime'] ?? 0);
    $code_barre = trim($_POST['code_barre'] ?? '');
    $statut = $_POST['statut'] ?? 'en_attente';
    $reparation_id = intval($_POST['reparation_id'] ?? 0);
    
    // Vérifier si reparation_id existe dans la base si fourni
    $reparation_id_valid = null;
    if ($reparation_id > 0) {
        try {
            $check_stmt = $shop_pdo->prepare("SELECT id FROM reparations WHERE id = ?");
            $check_stmt->execute([$reparation_id]);
            if ($check_stmt->fetch()) {
                $reparation_id_valid = $reparation_id;
            }
        } catch (Exception $e) {
            $debug['reparation_check_error'] = $e->getMessage();
        }
    }
    
    $debug['parsed_data'] = compact('client_id', 'fournisseur_id', 'nom_piece', 'quantite', 'prix_estime', 'code_barre', 'statut', 'reparation_id', 'reparation_id_valid');
    
    // Vérifier quelle table existe (commandes ou commandes_pieces)
    $tables = [];
    try {
        $stmt = $shop_pdo->query("SHOW TABLES LIKE 'commandes%'");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        $debug['available_tables'] = $tables;
    } catch (Exception $e) {
        $debug['table_check_error'] = $e->getMessage();
    }
    
    // Déterminer la table à utiliser
    $table_name = 'commandes';
    if (in_array('commandes_pieces', $tables)) {
        $table_name = 'commandes_pieces';
    }
    
    $debug['table_used'] = $table_name;
    
    // Validation des données obligatoires
    if (!$client_id || !$fournisseur_id || !$nom_piece || !$quantite || !$prix_estime) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes ou invalides', 'debug' => $debug]);
        exit;
    }
    
    // PROTECTION CONTRE LES DOUBLONS - Vérifier s'il existe déjà une commande identique dans les 2 dernières minutes
    try {
        $duplicate_check_sql = "SELECT id, date_creation FROM {$table_name} 
                               WHERE client_id = ? AND fournisseur_id = ? AND nom_piece = ? 
                               AND quantite = ? AND prix_estime = ? 
                               AND date_creation >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
                               ORDER BY date_creation DESC LIMIT 1";
        
        $duplicate_stmt = $shop_pdo->prepare($duplicate_check_sql);
        $duplicate_stmt->execute([$client_id, $fournisseur_id, $nom_piece, $quantite, $prix_estime]);
        $existing_commande = $duplicate_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_commande) {
            $debug['duplicate_found'] = $existing_commande;
            echo json_encode([
                'success' => true, // Retourner success pour éviter les erreurs côté client
                'message' => 'Commande déjà enregistrée (doublon détecté)',
                'commande_id' => $existing_commande['id'],
                'is_duplicate' => true,
                'debug' => $debug
            ]);
            exit;
        }
        
        $debug['duplicate_check'] = 'Aucun doublon trouvé';
        
    } catch (Exception $e) {
        $debug['duplicate_check_error'] = $e->getMessage();
        // Continuer même en cas d'erreur de vérification de doublon
    }
    
    // Générer une référence automatique
    $reference = 'CMD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Insertion SANS user_id mais AVEC reference
    $sql = "INSERT INTO {$table_name} (
        client_id, fournisseur_id, nom_piece, quantite, prix_estime, 
        code_barre, statut, reparation_id, reference, date_creation
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $shop_pdo->prepare($sql);
    $result = $stmt->execute([
        $client_id, $fournisseur_id, $nom_piece, $quantite, $prix_estime,
        $code_barre, $statut, $reparation_id_valid, $reference
    ]);
    
    if ($result) {
        $commande_id = $shop_pdo->lastInsertId();
        echo json_encode([
            'success' => true, 
            'message' => 'Commande créée avec succès',
            'commande_id' => $commande_id,
            'debug' => $debug
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'insertion', 'debug' => $debug]);
    }
    
} catch (Exception $e) {
    $debug['exception'] = $e->getMessage();
    $debug['exception_trace'] = $e->getTraceAsString();
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage(), 'debug' => $debug]);
}
?>
