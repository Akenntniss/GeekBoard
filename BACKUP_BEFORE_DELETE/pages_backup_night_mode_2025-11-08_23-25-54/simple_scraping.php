<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Vérification de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Lecture des données JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données JSON invalides']);
    exit;
}

// Validation des paramètres requis
if (empty($data['query'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Requête manquante']);
    exit;
}

// Test simple de scraping
function simpleScrape($url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $html = @file_get_contents($url, false, $context);
    if ($html === false) {
        return false;
    }
    
    // Recherche simple de prix
    if (preg_match('/(\d+(?:,\d+)?)\s*€/i', $html, $matches)) {
        return floatval(str_replace(',', '.', $matches[1]));
    }
    
    return false;
}

// Test avec un site simple
$testUrl = 'https://www.brico-tech.com/';
$price = simpleScrape($testUrl);

echo json_encode([
    'success' => true,
    'query' => $data['query'],
    'test_url' => $testUrl,
    'price_found' => $price !== false,
    'price' => $price,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
