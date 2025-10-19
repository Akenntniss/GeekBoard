<?php
// Test pour simuler le processus d'inscription et identifier où ça bloque

echo "=== TEST PROCESSUS INSCRIPTION ===\n";

// Simuler l'appel à inscription.php avec les mêmes données que jouikl
$test_data = [
    'subdomain' => 'testprocess',
    'shop_name' => 'Test Process',
    'owner_email' => 'test@servo.tools'
];

echo "Test avec sous-domaine: {$test_data['subdomain']}\n\n";

// Créer une requête POST simulée vers inscription.php
$post_data = http_build_query([
    'subdomain' => $test_data['subdomain'],
    'shop_name' => $test_data['shop_name'],
    'owner_name' => 'Test Owner',
    'owner_email' => $test_data['owner_email'],
    'owner_phone' => '0123456789',
    'password' => 'testpass123',
    'confirm_password' => 'testpass123',
    'terms_accepted' => '1'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $post_data,
        'timeout' => 60
    ]
]);

echo "Envoi de la requête POST à inscription.php...\n";
$start_time = microtime(true);

try {
    $response = file_get_contents('http://servo.tools/inscription.php', false, $context);
    $execution_time = microtime(true) - $start_time;
    
    echo "✅ Requête terminée en " . round($execution_time, 2) . " secondes\n";
    echo "Taille de la réponse: " . strlen($response) . " caractères\n";
    
    // Vérifier si la boutique a été créée en base
    echo "\n=== VÉRIFICATION BASE DE DONNÉES ===\n";
    // Cette partie sera exécutée sur le serveur
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la requête: " . $e->getMessage() . "\n";
}

echo "\n=== INSTRUCTIONS ===\n";
echo "1. Vérifiez les logs avec: grep 'INSCRIPTION DEBUG\\|SERVO SSL DEBUG' /var/log/nginx/error.log | tail -20\n";
echo "2. Vérifiez si testprocess existe en base: mysql -u root -p'Mamanmaman01#' geekboard_general -e \"SELECT * FROM shops WHERE subdomain = 'testprocess';\"\n";
echo "3. Corrigez manuellement si nécessaire: bash /root/fix_servo_ssl_smart.sh testprocess\n";
?>
