<?php
/**
 * Endpoint AJAX pour importer un catalogue fournisseur (CSV/JSON)
 */

header('Content-Type: application/json');

require_once '../config/session_config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    // Vérifier si c'est un import de fichier ou de données JSON directes
    if (isset($_FILES['file'])) {
        // Import de fichier
        $file = $_FILES['file'];
        $fournisseur_id = isset($_POST['fournisseur_id']) ? intval($_POST['fournisseur_id']) : 0;
        
        if ($fournisseur_id <= 0) {
            throw new Exception('Fournisseur non spécifié');
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $content = file_get_contents($file['tmp_name']);
        
        if ($extension === 'json') {
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Format JSON invalide');
            }
        } elseif ($extension === 'csv') {
            $data = [];
            $lines = explode("\n", $content);
            $headers = str_getcsv(array_shift($lines));
            
            foreach ($lines as $line) {
                if (trim($line) === '') continue;
                $values = str_getcsv($line);
                if (count($values) === count($headers)) {
                    $data[] = array_combine($headers, $values);
                }
            }
        } else {
            throw new Exception('Format non supporté. Utilisez CSV ou JSON.');
        }
        
    } elseif (isset($_POST['data'])) {
        // Import de données JSON directes
        $data = json_decode($_POST['data'], true);
        $fournisseur_id = isset($_POST['fournisseur_id']) ? intval($_POST['fournisseur_id']) : 0;
        
        if ($fournisseur_id <= 0) {
            throw new Exception('Fournisseur non spécifié');
        }
    } else {
        throw new Exception('Aucune donnée fournie');
    }
    
    if (empty($data)) {
        throw new Exception('Données vides');
    }
    
    // Préparer l'insertion
    $stmt = $shop_pdo->prepare("
        INSERT INTO catalogue_fournisseur 
        (fournisseur_id, name, url, price, reference, stock, type, device_type, brand, series, model)
        VALUES 
        (:fournisseur_id, :name, :url, :price, :reference, :stock, :type, :device_type, :brand, :series, :model)
    ");
    
    $imported = 0;
    $errors = 0;
    
    // Démarrer une transaction pour de meilleures performances
    $shop_pdo->beginTransaction();
    
    foreach ($data as $item) {
        try {
            // Nettoyer le prix (enlever € et espaces)
            $price = null;
            if (isset($item['price']) && !empty($item['price'])) {
                $price = floatval(preg_replace('/[^0-9.,]/', '', str_replace(',', '.', $item['price'])));
            }
            
            $stmt->execute([
                'fournisseur_id' => $fournisseur_id,
                'name' => $item['name'] ?? '',
                'url' => $item['url'] ?? null,
                'price' => $price,
                'reference' => $item['reference'] ?? null,
                'stock' => $item['stock'] ?? null,
                'type' => $item['type'] ?? null,
                'device_type' => $item['device_type'] ?? null,
                'brand' => $item['brand'] ?? null,
                'series' => $item['series'] ?? null,
                'model' => $item['model'] ?? null
            ]);
            $imported++;
        } catch (Exception $e) {
            $errors++;
            error_log("Erreur import catalogue: " . $e->getMessage());
        }
    }
    
    $shop_pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "$imported produits importés" . ($errors > 0 ? " ($errors erreurs)" : ""),
        'imported' => $imported,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
