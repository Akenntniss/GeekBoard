<?php
/**
 * Configuration SumUp API pour GeekBoard
 * Clé API Sandbox fournie par l'utilisateur
 */

return [
    // Clés API
    'api_key_sandbox' => 'sup_sk_uAoS3pKa2YDMvjJaFPFNOk6O6Foz80y8i',
    'api_key_production' => '', // À remplir plus tard
    
    // URLs API
    'base_url_sandbox' => 'https://api.sumup.com',
    'base_url_production' => 'https://api.sumup.com',
    
    // Configuration environnement
    'environment' => 'sandbox', // 'sandbox' ou 'production'
    
    // Configuration par défaut
    'currency' => 'EUR',
    'return_url' => 'https://82.29.168.205/MDGEEK/api/sumup/callback.php',
    'webhook_url' => 'https://82.29.168.205/MDGEEK/api/sumup/webhook.php',
    
    // Configuration checkout
    'checkout_timeout' => 3600, // 1 heure
    'description_prefix' => 'GeekBoard - ',
    
    // Logging
    'log_enabled' => true,
    'log_file' => __DIR__ . '/../logs/sumup.log'
]; 