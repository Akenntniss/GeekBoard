<?php
// Test simple pour template_sms
session_start();

echo "=== TEST SIMPLE ===\n";
echo "Tentative d'inclusion de sms_templates.php\n";

// Simuler l'environnement minimal nécessaire
$_SESSION['shop_id'] = 1; // Forcer un shop_id pour le test
$_SESSION['shop_name'] = 'mkmkmk';

// Inclure les fichiers de base nécessaires
define('BASE_PATH', '/var/www/mdgeek.top');

// Tenter d'inclure la page directement
$sms_templates_path = BASE_PATH . '/pages/sms_templates.php';
if (file_exists($sms_templates_path)) {
    echo "Fichier sms_templates.php trouvé, tentative d'inclusion...\n";
    
    // Inclure les dépendances minimales
    try {
        require_once BASE_PATH . '/config/database.php';
        require_once BASE_PATH . '/includes/functions.php';
        
        echo "Dépendances chargées avec succès\n";
        echo "Shop ID: " . ($_SESSION['shop_id'] ?? 'non défini') . "\n";
        
        // Test de connexion à la base
        $shop_pdo = getShopDBConnection();
        if ($shop_pdo) {
            echo "Connexion à la base de données réussie\n";
        } else {
            echo "Erreur de connexion à la base de données\n";
        }
        
    } catch (Exception $e) {
        echo "Erreur lors du chargement des dépendances: " . $e->getMessage() . "\n";
    }
} else {
    echo "Fichier sms_templates.php non trouvé à: $sms_templates_path\n";
}

echo "=== FIN TEST ===\n";
?>
