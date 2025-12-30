<?php
// Script de diagnostic pour get_tracked_products.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session_status' => session_status(),
    'session_data' => $_SESSION ?? [],
    'steps' => []
];

try {
    // Étape 1: Inclusion des fichiers
    $debug_info['steps']['1_includes_start'] = 'OK';
    
    if (file_exists('../config/database.php')) {
        require_once '../config/database.php';
        $debug_info['steps']['1a_database_included'] = 'OK';
    } else {
        $debug_info['steps']['1a_database_included'] = 'FAIL - File not found';
    }
    
    // Étape 2: Initialisation de la session
    $debug_info['steps']['2_session_init_start'] = 'OK';
    
    if (function_exists('initializeShopSession')) {
        $debug_info['steps']['2a_function_exists'] = 'OK';
        initializeShopSession();
        $debug_info['steps']['2b_function_called'] = 'OK';
        $debug_info['session_after_init'] = $_SESSION ?? [];
    } else {
        $debug_info['steps']['2a_function_exists'] = 'FAIL - Function not found';
    }
    
    // Étape 3: Vérification de l'authentification
    $debug_info['steps']['3_auth_check'] = isset($_SESSION['user_id']) ? 'OK' : 'FAIL - No user_id';
    $debug_info['user_id'] = $_SESSION['user_id'] ?? null;
    $debug_info['shop_id'] = $_SESSION['shop_id'] ?? null;
    
    // Étape 4: Connexion à la base de données
    $debug_info['steps']['4_db_connection_start'] = 'OK';
    
    if (function_exists('getShopDBConnection')) {
        $debug_info['steps']['4a_function_exists'] = 'OK';
        $shop_pdo = getShopDBConnection();
        
        if ($shop_pdo) {
            $debug_info['steps']['4b_connection_ok'] = 'OK';
            
            // Étape 5: Vérification de la structure de la table
            $debug_info['steps']['5_table_check_start'] = 'OK';
            
            try {
                $stmt = $shop_pdo->query("DESCRIBE produits");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $debug_info['table_structure'] = $columns;
                
                $has_suivre_stock = false;
                foreach ($columns as $column) {
                    if ($column['Field'] === 'suivre_stock') {
                        $has_suivre_stock = true;
                        break;
                    }
                }
                
                $debug_info['steps']['5a_suivre_stock_column'] = $has_suivre_stock ? 'OK' : 'FAIL - Column not found';
                
                if ($has_suivre_stock) {
                    // Étape 6: Test de la requête
                    $debug_info['steps']['6_query_test_start'] = 'OK';
                    
                    $stmt = $shop_pdo->prepare("
                        SELECT p.id, p.reference, p.nom, p.quantite, p.seuil_alerte, p.prix_achat, p.prix_vente, p.suivre_stock
                        FROM produits p 
                        WHERE p.suivre_stock = 1
                        ORDER BY p.nom ASC
                    ");
                    $stmt->execute();
                    $products = $stmt->fetchAll();
                    
                    $debug_info['steps']['6a_query_executed'] = 'OK';
                    $debug_info['products_count'] = count($products);
                    $debug_info['products'] = $products;
                    
                    // Test sans filtre sur suivre_stock
                    $stmt2 = $shop_pdo->prepare("
                        SELECT p.id, p.reference, p.nom, p.quantite, p.seuil_alerte, p.prix_achat, p.prix_vente
                        FROM produits p 
                        ORDER BY p.nom ASC
                    ");
                    $stmt2->execute();
                    $all_products = $stmt2->fetchAll();
                    $debug_info['all_products_count'] = count($all_products);
                    
                } else {
                    $debug_info['steps']['6_query_test_start'] = 'SKIPPED - Column missing';
                }
                
            } catch (PDOException $e) {
                $debug_info['steps']['5_table_check_error'] = $e->getMessage();
            }
            
        } else {
            $debug_info['steps']['4b_connection_ok'] = 'FAIL - PDO is null';
        }
    } else {
        $debug_info['steps']['4a_function_exists'] = 'FAIL - Function not found';
    }
    
} catch (Exception $e) {
    $debug_info['error'] = $e->getMessage();
    $debug_info['trace'] = $e->getTraceAsString();
}

header('Content-Type: application/json');
echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>
