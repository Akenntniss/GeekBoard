<?php
// Page de checkout pour les abonnements SERVO - Redirection directe vers Stripe
session_start();

$plan_id = $_GET['plan'] ?? null;
$shop_id = $_GET['shop'] ?? $_SESSION['shop_id'] ?? null;

if (!$plan_id || !$shop_id) {
    header('Location: /subscription_required.php');
    exit;
}

// Charger la configuration Stripe
require_once('config/stripe_config.php');
global $stripe_config;

// Mapper plan_id vers price_id (même mapping que notre script qui fonctionne)
$price_mapping = [
    1 => 'price_1SA6HQKUpWbkHkw0yD1EABMt', // Starter mensuel 39.99€
    2 => 'price_1SA6JnKUpWbkHkw03eKJe9oz', // Professional mensuel 49.99€
    3 => 'price_1SA6KUKUpWbkHkw0G9YuEGf7'  // Enterprise mensuel 1€ (à corriger)
];

$price_id = $price_mapping[$plan_id] ?? null;

if (!$price_id) {
    header('Location: /subscription_required.php?error=invalid_plan');
    exit;
}

// Créer la session Stripe DIRECTEMENT (même méthode que create_session_direct.php)
try {
    $session_data = [
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $price_id,
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'success_url' => $stripe_config['success_url'],
        'cancel_url' => $stripe_config['cancel_url'] . "?plan=$plan_id&shop=$shop_id",
        'customer_email' => $_GET['email'] ?? null, // Optionnel
        'metadata' => [
            'shop_id' => (string)$shop_id,
            'plan_id' => (string)$plan_id,
            'source' => 'checkout_page'
        ]
    ];
    
    // Requête cURL pour créer la session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/checkout/sessions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($session_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $stripe_config['secret_key'],
        "Content-Type: application/x-www-form-urlencoded"
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $session = json_decode($response, true);
        
        // REDIRECTION DIRECTE VERS STRIPE !
        header('Location: ' . $session['url']);
        exit;
        
    } else {
        // En cas d'erreur, afficher les détails
        $error_data = json_decode($response, true);
        $error_message = $error_data['error']['message'] ?? 'Erreur inconnue';
        
        // Log l'erreur pour debug
        error_log("Erreur création session Stripe: HTTP $http_code - $error_message");
    }
    
} catch (Exception $e) {
    error_log("Exception checkout: " . $e->getMessage());
}

// Si on arrive ici, il y a eu une erreur - Afficher une page d'erreur simple
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur de Paiement - SERVO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="alert alert-danger text-center">
                    <i class="fa-solid fa-exclamation-triangle fa-3x mb-3"></i>
                    <h4>Erreur de Traitement</h4>
                    <p>Une erreur s'est produite lors de la création de votre session de paiement.</p>
                    <p class="mb-4">Veuillez réessayer dans quelques instants.</p>
                    
                    <div class="d-grid gap-2">
                        <a href="/subscription_required.php?shop_id=<?php echo htmlspecialchars($shop_id); ?>" class="btn btn-primary">
                            <i class="fa-solid fa-arrow-left me-2"></i>Retour aux Plans
                        </a>
                        <a href="mailto:support@servo.tools" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-envelope me-2"></i>Contacter le Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
