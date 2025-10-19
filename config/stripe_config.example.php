<?php
/**
 * Configuration Stripe pour GeekBoard - TEMPLATE
 * Copiez ce fichier vers stripe_config.php et remplissez vos vraies clés
 */

$stripe_config = [
    // Clés API Stripe - REMPLACEZ PAR VOS VRAIES CLÉS
    'publishable_key' => 'pk_live_VOTRE_CLE_PUBLIQUE_ICI',
    'secret_key' => 'sk_live_VOTRE_CLE_SECRETE_ICI',
    
    // Webhook - REMPLACEZ PAR VOTRE VRAIE CLÉ
    'webhook_secret' => 'whsec_VOTRE_WEBHOOK_SECRET_ICI',
    
    // Configuration environnement
    'environment' => 'production', // 'test' ou 'production'
    
    // IDs des produits Stripe - REMPLACEZ PAR VOS VRAIS IDs
    'products' => [
        'starter' => 'prod_VOTRE_PRODUIT_STARTER',
        'professional' => 'prod_VOTRE_PRODUIT_PRO', 
        'enterprise' => 'prod_VOTRE_PRODUIT_ENTERPRISE'
    ],
    
    // URLs de callback - ADAPTEZ À VOTRE DOMAINE
    'success_url' => 'https://VOTRE-DOMAINE.com/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => 'https://VOTRE-DOMAINE.com/checkout.php?cancelled=1',
    'webhook_url' => 'https://VOTRE-DOMAINE.com/api/stripe/webhook.php',
    
    // Configuration par défaut
    'currency' => 'eur',
    'locale' => 'fr',
    
    // Logging
    'log_enabled' => true,
    'log_file' => __DIR__ . '/../logs/stripe.log'
];

return $stripe_config;
