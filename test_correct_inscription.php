<?php
// Test avec les bons paramètres pour inscription.php

echo "=== TEST INSCRIPTION AVEC BONS PARAMÈTRES ===\n";

$post_data = http_build_query([
    'nom' => 'Test',
    'prenom' => 'User', 
    'nom_commercial' => 'Test Final SSL',
    'subdomain' => 'testfinalssl',
    'email' => 'test@servo.tools',
    'telephone' => '0123456789',
    'adresse' => '123 rue test',
    'code_postal' => '75000',
    'ville' => 'Paris',
    'cgu_acceptees' => '1'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/x-www-form-urlencoded',
            'X-Requested-With: XMLHttpRequest'
        ],
        'content' => $post_data,
        'timeout' => 60
    ]
]);

echo "Envoi requête POST avec bons paramètres...\n";
$start_time = microtime(true);

try {
    $response = file_get_contents('http://servo.tools/inscription.php', false, $context);
    $execution_time = microtime(true) - $start_time;
    
    echo "✅ Requête terminée en " . round($execution_time, 2) . " secondes\n";
    echo "Taille réponse: " . strlen($response) . " caractères\n\n";
    
    // Essayer de parser la réponse JSON
    $json_data = json_decode($response, true);
    if ($json_data) {
        echo "=== RÉPONSE JSON ===\n";
        if ($json_data['success']) {
            echo "✅ SUCCÈS: " . ($json_data['message'] ?? 'Boutique créée') . "\n";
            if (isset($json_data['data'])) {
                echo "URL: " . ($json_data['data']['url'] ?? 'N/A') . "\n";
                echo "Username: " . ($json_data['data']['admin_username'] ?? 'N/A') . "\n";
            }
        } else {
            echo "❌ ÉCHEC: " . ($json_data['message'] ?? 'Erreur inconnue') . "\n";
            if (isset($json_data['errors'])) {
                foreach ($json_data['errors'] as $error) {
                    echo "- $error\n";
                }
            }
        }
    } else {
        echo "⚠️ Réponse non-JSON (probablement HTML)\n";
        // Extraire les premiers caractères pour diagnostic
        echo "Début réponse: " . substr($response, 0, 200) . "...\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== VÉRIFICATION ===\n";
echo "1. Vérifiez si testfinalssl a été créé en base\n";
echo "2. Surveillez les logs: tail -f /var/log/nginx/error.log | grep 'INSCRIPTION DEBUG\\|SERVO SSL DEBUG'\n";
echo "3. Si créé, vérifiez le SSL: curl -I https://testfinalssl.servo.tools/\n";
?>
