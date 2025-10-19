<?php
/**
 * Test de validation des Price IDs Stripe en production
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Test Validation Price IDs Stripe PRODUCTION</h1>";

// Charger la config
require_once 'config/stripe_config.php';
global $stripe_config;

echo "<h2>🔧 Configuration actuelle</h2>";
echo "<p><strong>Environnement:</strong> " . $stripe_config['environment'] . "</p>";
echo "<p><strong>Clé publique:</strong> " . substr($stripe_config['publishable_key'], 0, 20) . "...</p>";

// Test direct avec cURL vers l'API Stripe
echo "<h2>🧪 Test des Price IDs</h2>";

$secret_key = $stripe_config['secret_key'];

// Liste des price IDs à tester selon votre configuration
$price_ids_to_test = [
    // STARTER
    'price_1SA6HQKUpWbkHkw0yD1EABMt', // mensuelle 39.99€
    'price_1SA6IWKUpWbkHkw0f4xIAhD4', // annuel 383.88€
    
    // PROFESSIONAL  
    'price_1SA6JnKUpWbkHkw03eKJe9oz', // mensuelle 49.99€
    'price_1SA6JnKUpWbkHkw04DBOO37O', // annuel 479.88€
    
    // ENTERPRISE
    'price_1SA6KUKUpWbkHkw0G9YuEGf7', // mensuelle 59.99€
    'price_1SA6LpKUpWbkHkw0vOVrPhed'  // annuel 566.40€
];

foreach ($price_ids_to_test as $price_id) {
    echo "<h3>🏷️ Test Price ID: $price_id</h3>";
    
    // Requête cURL vers Stripe API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/prices/$price_id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $secret_key",
        "Content-Type: application/x-www-form-urlencoded"
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $price_data = json_decode($response, true);
        echo "✅ <strong>Prix valide</strong><br>";
        echo "- Montant: " . ($price_data['unit_amount'] / 100) . " " . strtoupper($price_data['currency']) . "<br>";
        echo "- Récurrence: " . $price_data['recurring']['interval'] . "<br>";
        echo "- Produit: " . $price_data['product'] . "<br>";
    } else {
        echo "❌ <strong>Erreur HTTP $http_code</strong><br>";
        if ($response) {
            $error_data = json_decode($response, true);
            echo "- Message: " . ($error_data['error']['message'] ?? 'Erreur inconnue') . "<br>";
        }
    }
    echo "<hr>";
}

// Test de création d'une session simple (sans base de données)
echo "<h2>🚀 Test Création Session Simple</h2>";

try {
    // Paramètres hardcodés pour test
    $test_price_id = 'price_1SA6JnKUpWbkHkw03eKJe9oz'; // Professional mensuel
    
    echo "<p>Tentative de création session avec Price ID: <code>$test_price_id</code></p>";
    
    $session_data = [
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $test_price_id,
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'success_url' => 'https://servo.tools/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://servo.tools/checkout.php?cancelled=1',
        'customer_email' => 'test@servo.tools',
        'metadata' => [
            'shop_id' => '94',
            'plan_id' => '2',
            'test' => 'true'
        ]
    ];
    
    // Requête cURL pour créer la session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/checkout/sessions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($session_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $secret_key",
        "Content-Type: application/x-www-form-urlencoded"
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $session = json_decode($response, true);
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
        echo "✅ <strong>Session créée avec succès !</strong><br>";
        echo "- Session ID: " . $session['id'] . "<br>";
        echo "- URL de paiement: <a href='" . $session['url'] . "' target='_blank'>" . $session['url'] . "</a><br>";
        echo "- Status: " . $session['status'] . "<br>";
        echo "</div>";
        
        echo "<p><strong>🧪 Test de paiement :</strong></p>";
        echo "<a href='" . $session['url'] . "' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>";
        echo "🚀 Aller au paiement TEST";
        echo "</a>";
        echo "<p><small>⚠️ Utilisez la carte de test: 4242 4242 4242 4242</small></p>";
        
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
        echo "❌ <strong>Erreur lors de la création de session</strong><br>";
        echo "- Code HTTP: $http_code<br>";
        if ($response) {
            $error_data = json_decode($response, true);
            echo "- Message: " . ($error_data['error']['message'] ?? 'Erreur inconnue') . "<br>";
            if (isset($error_data['error']['param'])) {
                echo "- Paramètre: " . $error_data['error']['param'] . "<br>";
            }
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Exception PHP</strong><br>";
    echo "- Message: " . $e->getMessage() . "<br>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>📋 Résumé</h2>";
echo "<p>Ce test valide :</p>";
echo "<ul>";
echo "<li>✅ Accès à l'API Stripe avec vos clés PRODUCTION</li>";
echo "<li>✅ Validité de tous vos Price IDs</li>";
echo "<li>✅ Capacité à créer des sessions de checkout</li>";
echo "</ul>";

echo "<p><strong>Si tout est vert, votre configuration Stripe est fonctionnelle !</strong></p>";
?>
