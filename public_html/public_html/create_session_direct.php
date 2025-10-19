<?php
/**
 * Script simplifié pour créer une session Stripe - COPIE EXACTE du test qui fonctionne
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🚀 Création Session Stripe PRODUCTION</h1>";

// Charger la config EXACTEMENT comme dans test_stripe_price_validation.php
require_once 'config/stripe_config.php';
global $stripe_config;

echo "<h2>🔧 Debug Configuration</h2>";
echo "<p><strong>Config chargée:</strong> " . (isset($stripe_config) ? "OUI" : "NON") . "</p>";

if (isset($stripe_config)) {
    echo "<p><strong>Environnement:</strong> " . $stripe_config['environment'] . "</p>";
    echo "<p><strong>Clé secrète:</strong> " . substr($stripe_config['secret_key'], 0, 20) . "...</p>";
} else {
    echo "<p>❌ $stripe_config non définie</p>";
    die();
}

// Test de création de session - COPIE EXACTE du test qui fonctionne
echo "<h2>🧪 Test Création Session</h2>";

try {
    $test_price_id = 'price_1SA6JnKUpWbkHkw03eKJe9oz'; // Professional mensuel
    $secret_key = $stripe_config['secret_key'];
    
    echo "<p>Price ID: <code>$test_price_id</code></p>";
    echo "<p>Secret Key: " . substr($secret_key, 0, 20) . "...</p>";
    
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
    
    echo "<h3>Données de la session:</h3>";
    echo "<pre>" . print_r($session_data, true) . "</pre>";
    
    // Requête cURL pour créer la session - COPIE EXACTE
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
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "<h3>Réponse cURL:</h3>";
    echo "<p><strong>Code HTTP:</strong> $http_code</p>";
    if ($curl_error) {
        echo "<p><strong>Erreur cURL:</strong> $curl_error</p>";
    }
    
    if ($http_code === 200) {
        $session = json_decode($response, true);
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
        echo "✅ <strong>Session créée avec succès !</strong><br>";
        echo "- Session ID: " . $session['id'] . "<br>";
        echo "- URL: <a href='" . $session['url'] . "' target='_blank'>" . $session['url'] . "</a><br>";
        echo "- Status: " . $session['status'] . "<br>";
        echo "</div>";
        
        echo "<a href='" . $session['url'] . "' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>";
        echo "🚀 Aller au paiement TEST";
        echo "</a>";
        
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
        echo "❌ <strong>Erreur HTTP $http_code</strong><br>";
        if ($response) {
            $error_data = json_decode($response, true);
            echo "- Message: " . ($error_data['error']['message'] ?? 'Erreur inconnue') . "<br>";
            if (isset($error_data['error']['param'])) {
                echo "- Paramètre: " . $error_data['error']['param'] . "<br>";
            }
            echo "<pre>" . print_r($error_data, true) . "</pre>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Exception PHP</strong><br>";
    echo "- Message: " . $e->getMessage() . "<br>";
    echo "</div>";
}
?>
