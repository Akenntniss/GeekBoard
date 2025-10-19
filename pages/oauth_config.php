<?php
// Configuration OAuth 2.0 pour Google Shopping Content API

// Configuration OAuth 2.0 avec vos identifiants
$oauth_config = [
    'client_id' => '285093751565-eo72np55a5d2ksbn9abb4e7sf14ifn2e.apps.googleusercontent.com',
    'client_secret' => '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',
    'redirect_uri' => 'https://mkmkmk.servo.tools/pages/oauth_callback.php',
    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'token_uri' => 'https://oauth2.googleapis.com/token',
    'scope' => 'https://www.googleapis.com/auth/content'
];

// Fonction pour obtenir l'URL d'autorisation
function getAuthUrl($config) {
    $params = [
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'scope' => $config['scope'],
        'response_type' => 'code',
        'access_type' => 'offline',
        'prompt' => 'consent'
    ];
    
    return $config['auth_uri'] . '?' . http_build_query($params);
}

// Fonction pour échanger le code contre un token
function getAccessToken($code, $config) {
    $data = [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['redirect_uri'],
        'grant_type' => 'authorization_code',
        'code' => $code
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($config['token_uri'], false, $context);
    
    return json_decode($response, true);
}

// Fonction pour rafraîchir le token
function refreshAccessToken($refresh_token, $config) {
    $data = [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'refresh_token' => $refresh_token,
        'grant_type' => 'refresh_token'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($config['token_uri'], false, $context);
    
    return json_decode($response, true);
}
?>
