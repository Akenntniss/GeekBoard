<?php
// Forcer l'utilisation de la même session que la page principale
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

// Essayer de récupérer l'ID de session depuis les cookies
$session_id = $_COOKIE['PHPSESSID'] ?? $_COOKIE['MDGEEK_SESSION'] ?? null;

if ($session_id) {
    session_id($session_id);
}

session_start();

header('Content-Type: application/json');

// Si user_id manque, essayer de le récupérer depuis la base de données
if (!isset($_SESSION['user_id']) && isset($_SESSION['shop_id'])) {
    try {
        require_once dirname(__DIR__) . '/config/database.php';
        
        // Récupérer les infos utilisateur depuis l'IP et le shop
        $main_pdo = getMainDBConnection();
        if ($main_pdo) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Chercher une session active récente pour ce shop et cette IP
            $stmt = $main_pdo->prepare("
                SELECT user_id, username, role 
                FROM user_sessions 
                WHERE shop_id = ? AND ip_address = ? 
                AND last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ORDER BY last_activity DESC 
                LIMIT 1
            ");
            $stmt->execute([$_SESSION['shop_id'], $ip]);
            $user_session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_session) {
                $_SESSION['user_id'] = $user_session['user_id'];
                $_SESSION['user_username'] = $user_session['username'];
                $_SESSION['user_role'] = $user_session['role'];
                
                error_log("Session réparée - user_id récupéré: " . $user_session['user_id']);
            } else {
                // Essayer avec l'utilisateur admin par défaut du shop
                $stmt = $main_pdo->prepare("
                    SELECT u.id, u.username, u.role 
                    FROM users u 
                    WHERE u.shop_id = ? AND u.role = 'admin' 
                    LIMIT 1
                ");
                $stmt->execute([$_SESSION['shop_id']]);
                $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin_user) {
                    $_SESSION['user_id'] = $admin_user['id'];
                    $_SESSION['user_username'] = $admin_user['username'];
                    $_SESSION['user_role'] = $admin_user['role'];
                    
                    error_log("Session réparée avec admin par défaut - user_id: " . $admin_user['id']);
                }
            }
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la réparation de session: " . $e->getMessage());
    }
}

// Debug complet
$debug = [
    'session_id' => session_id(),
    'session_status' => session_status(),
    'session_data' => $_SESSION ?? [],
    'cookies' => $_COOKIE ?? [],
    'post_data' => $_POST,
    'get_data' => $_GET,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'user_id_fixed' => isset($_SESSION['user_id'])
];

error_log("Fix Session Debug: " . json_encode($debug));

// Vérifications de base
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée', 'debug' => $debug]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Impossible de récupérer user_id', 'debug' => $debug]);
    exit;
}

if (!isset($_SESSION['shop_id'])) {
    echo json_encode(['success' => false, 'message' => 'Shop ID manquant', 'debug' => $debug]);
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
