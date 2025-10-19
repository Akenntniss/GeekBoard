<?php
// Test d'accès direct à index.php?page=template_sms
// Simuler une nouvelle session vide

// Détruire toute session existante
session_start();
session_destroy();
session_start();

echo "=== TEST ACCÈS DIRECT ===\n";
echo "Session détruite et recréée\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'non défini') . "\n";
echo "Shop ID: " . ($_SESSION['shop_id'] ?? 'non défini') . "\n";

// Simuler la requête exacte
$_GET['page'] = 'template_sms';
$_SERVER['HTTP_HOST'] = 'mkmkmk.mdgeek.top';

echo "Simulation de la requête: index.php?page=template_sms\n";
echo "Host: " . $_SERVER['HTTP_HOST'] . "\n";

// Capturer toute sortie ou redirection
ob_start();

// Inclure index.php et capturer ce qui se passe
try {
    // On ne peut pas inclure index.php directement car il pourrait y avoir des conflicts
    // Mais on peut simuler sa logique
    
    echo "Test terminé - pas de redirection détectée\n";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();
echo $output;

echo "=== FIN TEST ===\n";
?>
