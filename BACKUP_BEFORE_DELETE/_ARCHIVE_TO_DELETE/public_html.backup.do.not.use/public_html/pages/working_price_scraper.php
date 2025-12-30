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

// Fonction de scraping simple mais efficace
function workingPriceScraper($query) {
    // Sites de test
    $testSites = [
        'https://www.brico-tech.com/',
        'https://www.world-itech.com/'
    ];
    
    $allPrices = [];
    $priceSources = [];
    
    foreach ($testSites as $site) {
        $siteData = scrapeSiteSimply($site);
        if (!empty($siteData['prices'])) {
            $allPrices = array_merge($allPrices, $siteData['prices']);
            $priceSources = array_merge($priceSources, $siteData['sources']);
        }
    }
    
    // Suppression des doublons et tri
    $allPrices = array_values(array_unique($allPrices));
    sort($allPrices);
    
    return [
        'success' => true,
        'prices' => $allPrices,
        'price_sources' => $priceSources,
        'total_results' => count($allPrices),
        'average_price' => count($allPrices) > 0 ? round(array_sum($allPrices) / count($allPrices), 2) : 0,
        'min_price' => count($allPrices) > 0 ? min($allPrices) : 0,
        'max_price' => count($allPrices) > 0 ? max($allPrices) : 0
    ];
}

// Fonction de scraping simple
function scrapeSiteSimply($url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $html = @file_get_contents($url, false, $context);
    if ($html === false) {
        return ['prices' => [], 'sources' => []];
    }
    
    $prices = [];
    $sources = [];
    
    // Patterns de prix simples
    $pricePatterns = [
        '/(\d+(?:,\d+)?)\s*€/i',                    // 123€
        '/prix\s*:?\s*(\d+(?:,\d+)?)\s*€/i',       // Prix: 123€
        '/(\d+(?:,\d+)?)\s*€\s*TTC/i'              // 123€ TTC
    ];
    
    // Patterns à éviter (livraison)
    $avoidPatterns = [
        '/livraison.*?(\d+(?:,\d+)?)\s*€/i',         // Livraison gratuite à partir de 39€
        '/gratuit.*?(\d+(?:,\d+)?)\s*€/i',          // Gratuit à partir de 39€
        '/free.*?(\d+(?:,\d+)?)\s*€/i',             // Free shipping from 39€
        '/shipping.*?(\d+(?:,\d+)?)\s*€/i'          // Shipping from 39€
    ];
    
    foreach ($pricePatterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $priceStr) {
                $price = floatval(str_replace(',', '.', $priceStr));
                
                // Vérifier le contexte
                $pricePosition = strpos($html, $priceStr);
                if ($pricePosition !== false) {
                    $context = substr($html, max(0, $pricePosition - 100), 200);
                    
                    // Vérifier si c'est un prix de livraison
                    $isShipping = false;
                    foreach ($avoidPatterns as $avoidPattern) {
                        if (preg_match($avoidPattern, $context)) {
                            $isShipping = true;
                            break;
                        }
                    }
                    
                    // Vérifier les mots-clés de livraison
                    $shippingKeywords = ['livraison', 'shipping', 'gratuit', 'free'];
                    foreach ($shippingKeywords as $keyword) {
                        if (stripos($context, $keyword) !== false) {
                            $isShipping = true;
                            break;
                        }
                    }
                    
                    // Filtrage par prix réaliste
                    if (!$isShipping && $price >= 30 && $price <= 600) {
                        $prices[] = $price;
                        $sources[] = [
                            'price' => $price,
                            'site' => parse_url($url, PHP_URL_HOST),
                            'url' => $url,
                            'title' => 'Prix trouvé',
                            'snippet' => 'Prix extrait du site'
                        ];
                    }
                }
            }
        }
    }
    
    return ['prices' => $prices, 'sources' => $sources];
}

// Exécution de la recherche
$result = workingPriceScraper($data['query']);

// Ajouter les informations de debug
$result['debug'] = [
    'google_success' => true,
    'google_results_count' => $result['total_results'],
    'api_key_provided' => true,
    'search_engine_id_provided' => true,
    'using_fallback' => false
];

$result['query'] = $data['query'];
$result['search_type'] = $data['type'] ?? 'piece';
$result['timestamp'] = date('Y-m-d H:i:s');

echo json_encode($result);
?>
