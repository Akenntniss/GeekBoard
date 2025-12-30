<?php
/**
 * Script pour intégrer le middleware de vérification d'abonnement
 * dans les pages principales des boutiques
 */

// Pages qui nécessitent une vérification d'abonnement
$pages_to_protect = [
    'index.php',
    'accueil.php', 
    'reparations.php',
    'clients.php',
    'stock.php',
    'devis.php',
    'admin_missions.php',
    'presence.php',
    'rapports.php'
];

$middleware_code = '
// Vérification automatique de l\'abonnement
require_once __DIR__ . \'/includes/subscription_redirect_middleware.php\';

// Vérifier l\'accès - redirection automatique si expiré
if (!checkSubscriptionAccess()) {
    // La fonction checkSubscriptionAccess() gère la redirection automatique
    exit;
}
';

$warning_banner_code = '
// Afficher le bandeau d\'avertissement si l\'essai va expirer
displayTrialWarning();
';

echo "Script d'intégration du middleware d'abonnement\n";
echo "================================================\n\n";

foreach ($pages_to_protect as $page) {
    if (file_exists($page)) {
        echo "✓ Page trouvée: $page\n";
        
        $content = file_get_contents($page);
        
        // Vérifier si le middleware est déjà intégré
        if (strpos($content, 'subscription_redirect_middleware.php') !== false) {
            echo "  → Middleware déjà intégré\n";
            continue;
        }
        
        // Trouver la position après session_start() ou au début du PHP
        if (preg_match('/(<\?php.*?session_start\(\);)/s', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insert_position = $matches[1][1] + strlen($matches[1][0]);
            
            // Insérer le middleware après session_start()
            $new_content = substr($content, 0, $insert_position) . 
                          $middleware_code . 
                          substr($content, $insert_position);
            
            // Insérer le bandeau d'avertissement après l'inclusion du header/navbar
            if (strpos($new_content, 'include') !== false && strpos($new_content, 'header') !== false) {
                $new_content = preg_replace(
                    '/(include.*?[\'"].*?header.*?[\'"].*?;)/i',
                    '$1' . $warning_banner_code,
                    $new_content,
                    1
                );
            }
            
            // Sauvegarder une copie de sauvegarde
            copy($page, $page . '.backup');
            
            // Écrire le nouveau contenu
            file_put_contents($page, $new_content);
            
            echo "  → Middleware intégré avec succès\n";
            echo "  → Sauvegarde créée: $page.backup\n";
            
        } else {
            echo "  → Impossible de trouver session_start() dans $page\n";
        }
        
    } else {
        echo "✗ Page non trouvée: $page\n";
    }
    
    echo "\n";
}

echo "Intégration terminée!\n";
echo "\nPour tester:\n";
echo "1. Créez un shop avec essai expiré\n";
echo "2. Essayez d'accéder à une page protégée\n";
echo "3. Vérifiez la redirection vers subscription_required.php\n";

// Créer un exemple de page de test
$test_page_content = '<?php
session_start();

// Simulation d\'un shop avec essai expiré pour test
$_SESSION[\'shop_id\'] = 94; // Shop ID de test

// Vérification automatique de l\'abonnement
require_once __DIR__ . \'/includes/subscription_redirect_middleware.php\';

if (!checkSubscriptionAccess()) {
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Middleware Abonnement</title>
</head>
<body>
    <?php displayTrialWarning(); ?>
    
    <h1>Page protégée</h1>
    <p>Si vous voyez cette page, c\'est que l\'abonnement est valide!</p>
    <p>Shop ID: <?php echo $_SESSION[\'shop_id\']; ?></p>
</body>
</html>';

file_put_contents('test_subscription_middleware.php', $test_page_content);
echo "\nPage de test créée: test_subscription_middleware.php\n";
echo "Testez avec: https://82.29.168.205/test_subscription_middleware.php\n";
?>
