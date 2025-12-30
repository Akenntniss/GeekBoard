<?php
// Configuration de session identique au système
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // HTTP pour localhost

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Debug complet
$debug = [
    'session_exists' => isset($_SESSION),
    'user_id_exists' => isset($_SESSION['user_id']),
    'user_id_value' => $_SESSION['user_id'] ?? null,
    'shop_id_exists' => isset($_SESSION['shop_id']),
    'shop_id_value' => $_SESSION['shop_id'] ?? null,
    'post_data' => $_POST,
    'get_data' => $_GET,
    'request_method' => $_SERVER['REQUEST_METHOD']
];

// Log du debug
error_log("Smart Add Commande Debug: " . json_encode($debug));

// Vérifications de base
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée', 'debug' => $debug]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée - user_id manquant', 'debug' => $debug]);
    exit;
}

// Récupérer shop_id depuis l'URL si fourni
$shop_id_from_request = $_POST['shop_id'] ?? $_GET['shop_id'] ?? null;
if ($shop_id_from_request) {
    $_SESSION['shop_id'] = $shop_id_from_request;
    $debug['shop_id_from_request'] = $shop_id_from_request;
    error_log("Shop ID récupéré depuis la requête: $shop_id_from_request");
}

if (!isset($_SESSION['shop_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée - shop_id manquant', 'debug' => $debug]);
    exit;
}

try {
    // Utiliser le système de configuration existant
    require_once dirname(__DIR__) . '/config/database.php';
    
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
        error_log("Base de données connectée: " . $debug['current_database']);
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
    
    $debug['parsed_data'] = compact('client_id', 'fournisseur_id', 'nom_piece', 'quantite', 'prix_estime', 'code_barre', 'statut', 'reparation_id');
    
    // Validation
    if (!$client_id || !$fournisseur_id || !$nom_piece || !$quantite || !$prix_estime) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes ou invalides', 'debug' => $debug]);
        exit;
    }
    
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
    
    // Insertion
    $sql = "INSERT INTO {$table_name} (
        client_id, fournisseur_id, nom_piece, quantite, prix_estime, 
        code_barre, statut, reparation_id, user_id, date_creation
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $shop_pdo->prepare($sql);
    $result = $stmt->execute([
        $client_id, $fournisseur_id, $nom_piece, $quantite, $prix_estime,
        $code_barre, $statut, $reparation_id, $_SESSION['user_id']
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
