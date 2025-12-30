<?php
/* ====================================================================
   🔧 TEST DEBUG - AJOUTER CLIENT
   Script de diagnostic pour identifier le problème JSON
==================================================================== */

// Capturer toute sortie non désirée
ob_start();

// Démarrer la session et inclure la configuration
session_start();
require_once '../config/database.php';

// Initialiser la session shop pour la détection automatique de la base
initializeShopSession();

// Nettoyer le buffer de sortie
$unwanted_output = ob_get_clean();

// Headers pour JSON
header('Content-Type: application/json');

try {
    // Informations de diagnostic
    $diagnostic = [
        'success' => true,
        'message' => 'Test de diagnostic réussi',
        'session_info' => [
            'shop_id' => $_SESSION['shop_id'] ?? 'non défini',
            'shop_name' => $_SESSION['shop_name'] ?? 'non défini',
            'user_id' => $_SESSION['user_id'] ?? 'non défini'
        ],
        'unwanted_output' => $unwanted_output,
        'unwanted_output_length' => strlen($unwanted_output),
        'post_data' => $_POST,
        'server_info' => [
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'non défini'
        ]
    ];
    
    // Test de connexion à la base de données
    if (isset($_SESSION['shop_id'])) {
        $shop_pdo = getShopDBConnection();
        if ($shop_pdo) {
            $db_stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
            $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
            $diagnostic['database_info'] = [
                'connected' => true,
                'database_name' => $db_info['db_name'] ?? 'Inconnue'
            ];
            
            // Test de la table clients
            try {
                $table_exists = $shop_pdo->query("SHOW TABLES LIKE 'clients'");
                $diagnostic['table_clients'] = [
                    'exists' => $table_exists->rowCount() > 0
                ];
                
                if ($table_exists->rowCount() > 0) {
                    $table_check = $shop_pdo->query("DESCRIBE clients");
                    $columns = $table_check->fetchAll(PDO::FETCH_COLUMN);
                    $diagnostic['table_clients']['columns'] = $columns;
                }
            } catch (Exception $e) {
                $diagnostic['table_clients'] = [
                    'error' => $e->getMessage()
                ];
            }
        } else {
            $diagnostic['database_info'] = [
                'connected' => false,
                'error' => 'Impossible de se connecter à la base de données'
            ];
        }
    } else {
        $diagnostic['database_info'] = [
            'connected' => false,
            'error' => 'Aucun shop_id en session'
        ];
    }
    
    echo json_encode($diagnostic, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de diagnostic: ' . $e->getMessage(),
        'unwanted_output' => $unwanted_output ?? '',
        'unwanted_output_length' => strlen($unwanted_output ?? '')
    ], JSON_PRETTY_PRINT);
}
?>

