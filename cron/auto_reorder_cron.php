#!/usr/bin/env php
<?php
/**
 * Script CRON pour le réapprovisionnement automatique
 * À exécuter toutes les 5 minutes via crontab
 * Commande: php auto_reorder_cron.php
 */

// Définir le chemin absolu du projet
define('PROJECT_ROOT', dirname(__DIR__));

// Inclure les configurations
require_once PROJECT_ROOT . '/config/database.php';
require_once PROJECT_ROOT . '/includes/functions.php';

// Logger le début
$timestamp = date('Y-m-d H:i:s');
echo "\n=== CRON Auto-Reorder - Début: {$timestamp} ===\n";

try {
    // Récupérer toutes les boutiques actives
    $pdo_main = getMainDBConnection();
    $stmt = $pdo_main->query("SELECT id, subdomain, db_name FROM shops WHERE active = 1");
    $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Boutiques actives trouvées: " . count($shops) . "\n";
    
    foreach ($shops as $shop) {
        echo "\n--- Traitement boutique: {$shop['subdomain']} (ID: {$shop['id']}) ---\n";
        
        // Simuler une session pour cette boutique
        $_SESSION['shop_id'] = $shop['id'];
        $_SESSION['user_id'] = 1; // Utilisateur système
        
        // Appeler le script de réapprovisionnement
        $auto_reorder_path = PROJECT_ROOT . '/ajax/auto_reorder.php';
        
        if (!file_exists($auto_reorder_path)) {
            echo "ERREUR: Fichier auto_reorder.php introuvable\n";
            continue;
        }
        
        // Capturer la sortie
        ob_start();
        include $auto_reorder_path;
        $output = ob_get_clean();
        
        // Parser le JSON retourné
        $result = json_decode($output, true);
        
        if ($result && isset($result['success']) && $result['success']) {
            echo "✅ Succès: {$result['message']}\n";
            echo "   Créées: {$result['created']}, Mises à jour: {$result['updated']}\n";
            
            if (!empty($result['processed_products'])) {
                echo "   Produits traités:\n";
                foreach ($result['processed_products'] as $product) {
                    echo "   - {$product['nom']} ({$product['action']}): {$product['quantite']} unités\n";
                }
            }
        } else {
            $error_msg = $result['message'] ?? 'Erreur inconnue';
            echo "❌ Échec: {$error_msg}\n";
        }
    }
    
    echo "\n=== CRON Auto-Reorder - Fin: " . date('Y-m-d H:i:s') . " ===\n";
    
} catch (Exception $e) {
    echo "ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
