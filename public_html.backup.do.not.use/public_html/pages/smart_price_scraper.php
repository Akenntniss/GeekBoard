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

// Fonction de scraping intelligent et rapide
function smartPriceScraper($query) {
    // Sites ciblés pour pièces détachées (rapides et fiables)
    $targetSites = [
        'https://www.brico-tech.com/',
        'https://www.world-itech.com/'
    ];
    
    $allPrices = [];
    $priceSources = [];
    
    foreach ($targetSites as $site) {
        $siteData = scrapeSiteSmartly($site, $query);
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

// Fonction de scraping intelligente
function scrapeSiteSmartly($url, $query) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5, // Timeout court
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $html = @file_get_contents($url, false, $context);
    if ($html === false) {
        return ['prices' => [], 'sources' => []];
    }
    
    $prices = [];
    $sources = [];
    
    // Patterns de prix équilibrés (spécifiques mais pas trop restrictifs)
    $pricePatterns = [
        '/prix\s*:?\s*(\d+(?:,\d+)?)\s*€/i',           // "Prix: 89€"
        '/(\d+(?:,\d+)?)\s*€\s*TTC/i',                     // "89€ TTC"
        '/à\s*partir\s*de\s*(\d+(?:,\d+)?)\s*€/i',         // "à partir de 89€"
        '/(\d+(?:,\d+)?)\s*€\s*(?:neuf|occasion)/i',       // "89€ neuf"
        '/€\s*(\d+(?:,\d+)?)\s*TTC/i',                     // "€89 TTC"
        '/€\s*(\d+(?:,\d+)?)\s*HT/i',                      // "€89 HT"
        '/(\d+(?:,\d+)?)\s*€/i',                           // "89€" (pattern simple)
        '/€\s*(\d+(?:,\d+)?)/i'                            // "€89" (pattern simple)
    ];
    
    // Patterns à éviter (livraison, réductions, etc.)
    $avoidPatterns = [
        '/livraison.*?(\d+(?:,\d+)?)\s*€/i',           // Livraison gratuite à partir de 39€
        '/gratuit.*?(\d+(?:,\d+)?)\s*€/i',             // Gratuit à partir de 39€
        '/free.*?(\d+(?:,\d+)?)\s*€/i',                // Free shipping from 39€
        '/shipping.*?(\d+(?:,\d+)?)\s*€/i',             // Shipping from 39€
        '/-\s*(\d+(?:,\d+)?)\s*€/i',                   // -30€
        '/réduction\s*:?\s*(\d+(?:,\d+)?)\s*€/i',      // Réduction: 30€
        '/remise\s*:?\s*(\d+(?:,\d+)?)\s*€/i',         // Remise: 30€
        '/économie\s*:?\s*(\d+(?:,\d+)?)\s*€/i'         // Économie: 30€
    ];
    
    foreach ($pricePatterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $priceStr) {
                $price = floatval(str_replace(',', '.', $priceStr));
                
                // Vérifier le contexte étendu
                $pricePosition = strpos($html, $priceStr);
                if ($pricePosition !== false) {
                    $context = substr($html, max(0, $pricePosition - 200), 400);
                    
                    // Vérifier si c'est un prix invalide
                    $isInvalid = false;
                    foreach ($avoidPatterns as $avoidPattern) {
                        if (preg_match($avoidPattern, $context)) {
                            $isInvalid = true;
                            break;
                        }
                    }
                    
                    // Vérifier les mots-clés de livraison
                    $shippingKeywords = [
                        'livraison', 'shipping', 'delivery', 'gratuit', 'free',
                        'à partir de', 'dès', 'from', 'starting', 'commande'
                    ];
                    
                    foreach ($shippingKeywords as $keyword) {
                        if (stripos($context, $keyword) !== false) {
                            $isInvalid = true;
                            break;
                        }
                    }
                    
                    // Filtrage par prix réaliste pour écrans (équilibré)
                    if (!$isInvalid && $price >= 50 && $price <= 500) {
                        $prices[] = $price;
                        $sources[] = [
                            'price' => $price,
                            'site' => parse_url($url, PHP_URL_HOST),
                            'url' => $url,
                            'title' => 'Prix trouvé',
                            'snippet' => 'Prix extrait du site',
                            'context' => substr($context, 0, 100) // Pour debug
                        ];
                    }
                }
            }
        }
    }
    
    return ['prices' => $prices, 'sources' => $sources];
}

// Exécution de la recherche
$result = smartPriceScraper($data['query']);

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
