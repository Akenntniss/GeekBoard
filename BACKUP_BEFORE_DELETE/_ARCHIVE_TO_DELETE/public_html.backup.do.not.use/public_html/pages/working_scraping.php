<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Lecture des données JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['query'])) {
    echo json_encode(['success' => false, 'error' => 'Requête manquante']);
    exit;
}

// Test de scraping simple
function testScrape($url) {
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
    
    // Recherche de prix avec patterns intelligents
    $patterns = [
        '/(\d+(?:,\d+)?)\s*€/i',                    // 123€
        '/prix\s*:?\s*(\d+(?:,\d+)?)\s*€/i',       // Prix: 123€
        '/(\d+(?:,\d+)?)\s*€\s*TTC/i'              // 123€ TTC
    ];
    
    $prices = [];
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $priceStr) {
                $price = floatval(str_replace(',', '.', $priceStr));
                if ($price >= 20 && $price <= 500) { // Filtre prix réaliste
                    $prices[] = $price;
                }
            }
        }
    }
    
    return $prices;
}

// Test avec quelques sites connus
$testSites = [
    'https://www.brico-tech.com/',
    'https://www.world-itech.com/',
    'https://www.ifixit.com/'
];

$allPrices = [];
$sources = [];

foreach ($testSites as $site) {
    $prices = testScrape($site);
    if ($prices) {
        $allPrices = array_merge($allPrices, $prices);
        $sources[] = [
            'site' => parse_url($site, PHP_URL_HOST),
            'url' => $site,
            'prices' => $prices
        ];
    }
}

// Suppression des doublons et tri
$allPrices = array_values(array_unique($allPrices));
sort($allPrices);

echo json_encode([
    'success' => true,
    'query' => $data['query'],
    'prices' => $allPrices,
    'sources' => $sources,
    'total_results' => count($allPrices),
    'average_price' => count($allPrices) > 0 ? round(array_sum($allPrices) / count($allPrices), 2) : 0,
    'min_price' => count($allPrices) > 0 ? min($allPrices) : 0,
    'max_price' => count($allPrices) > 0 ? max($allPrices) : 0,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
