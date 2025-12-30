<?php
// Analyser la réponse d'inscription.php pour comprendre pourquoi ça échoue

echo "=== ANALYSE RÉPONSE INSCRIPTION.PHP ===\n";

$post_data = http_build_query([
    'subdomain' => 'testanalyse',
    'shop_name' => 'Test Analyse',
    'owner_name' => 'Test Owner',
    'owner_email' => 'test@servo.tools',
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

echo "Envoi requête POST...\n";
$response = file_get_contents('http://servo.tools/inscription.php', false, $context);

echo "Taille réponse: " . strlen($response) . " caractères\n\n";

// Extraire les messages d'erreur ou de succès
if (preg_match('/<div[^>]*class="[^"]*alert[^"]*"[^>]*>(.*?)<\/div>/s', $response, $matches)) {
    echo "=== MESSAGE ALERT TROUVÉ ===\n";
    echo strip_tags($matches[1]) . "\n\n";
}

// Chercher des messages d'erreur spécifiques
$error_patterns = [
    '/Erreur.*?:.*?([^<]+)/i',
    '/Error.*?:.*?([^<]+)/i', 
    '/Invalid.*?([^<]+)/i',
    '/déjà.*?utilisé/i',
    '/already.*?exists/i'
];

foreach ($error_patterns as $pattern) {
    if (preg_match($pattern, $response, $matches)) {
        echo "=== ERREUR DÉTECTÉE ===\n";
        echo trim($matches[0]) . "\n\n";
    }
}

// Vérifier si on a un formulaire (échec) ou une page de succès
if (strpos($response, '<form') !== false) {
    echo "❌ ÉCHEC: Le formulaire est encore présent (pas de redirection)\n";
    
    // Extraire les messages d'erreur du formulaire
    if (preg_match_all('/<span[^>]*class="[^"]*error[^"]*"[^>]*>(.*?)<\/span>/s', $response, $matches)) {
        echo "=== ERREURS FORMULAIRE ===\n";
        foreach ($matches[1] as $error) {
            echo "- " . strip_tags($error) . "\n";
        }
    }
} else {
    echo "✅ SUCCÈS: Pas de formulaire (redirection probable)\n";
}

// Vérifier si on a une redirection
if (preg_match('/Location:\s*([^\r\n]+)/i', implode("\n", $http_response_header ?? []), $matches)) {
    echo "✅ REDIRECTION VERS: " . $matches[1] . "\n";
}

echo "\n=== VÉRIFICATION EN BASE ===\n";
// Cette vérification sera faite sur le serveur
?>
