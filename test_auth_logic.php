<?php
// Test de la logique d'authentification
session_start();

echo "=== TEST LOGIQUE AUTH ===\n";

// Simuler l'état de la requête
$_GET['page'] = 'template_sms';
$page = $_GET['page'];

echo "Page demandée: $page\n";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'non défini') . "\n";
echo "Session shop_id avant: " . ($_SESSION['shop_id'] ?? 'non défini') . "\n";

// Cas spéciaux qui ne nécessitent pas d'authentification
$no_auth_pages = ['imprimer_etiquette', 'diagnostic_session', 'debug_fournisseurs', 'template_sms'];

echo "Page dans no_auth_pages: " . (in_array($page, $no_auth_pages) ? 'OUI' : 'NON') . "\n";

if (!isset($_SESSION['user_id'])) {
    echo "Utilisateur non connecté\n";
    
    if (in_array($page, $no_auth_pages)) {
        echo "Page autorisée sans authentification\n";
        
        // Pour les pages sans authentification, s'assurer que la session magasin est initialisée
        if (!isset($_SESSION['shop_id'])) {
            echo "Shop_id non défini, tentative d'inclusion subdomain_config.php\n";
            try {
                require_once __DIR__ . '/config/subdomain_config.php';
                echo "subdomain_config.php inclus avec succès\n";
                echo "Session shop_id après: " . ($_SESSION['shop_id'] ?? 'non défini') . "\n";
            } catch (Exception $e) {
                echo "Erreur lors de l'inclusion: " . $e->getMessage() . "\n";
            }
        } else {
            echo "Shop_id déjà défini: " . $_SESSION['shop_id'] . "\n";
        }
        
        echo "Accès autorisé à la page $page\n";
    } else {
        echo "Page NON autorisée sans authentification - redirection nécessaire\n";
    }
} else {
    echo "Utilisateur connecté - accès autorisé\n";
}

echo "=== FIN TEST ===\n";
?>
