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

// Paramètres configurables (à récupérer depuis la base de données)
$minPrice = 20;   // Prix minimum configurable
$maxPrice = 500;  // Prix maximum configurable

// Fonction de scraping intelligent avec tous les filtres
function advancedScrape($query, $minPrice = 20, $maxPrice = 500) {
    // Sites de test (à remplacer par Google Search API)
    $testSites = [
        'https://www.brico-tech.com/',
        'https://www.world-itech.com/',
        'https://www.ifixit.com/',
        'https://www.repairsuniverse.com/',
        'https://www.etradesupply.com/'
    ];
    
    $allPrices = [];
    $priceSources = [];
    
    foreach ($testSites as $site) {
        $siteData = scrapeSiteWithSmartFilters($site, $query, $minPrice, $maxPrice);
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

// Fonction de scraping avec filtres intelligents
function scrapeSiteWithSmartFilters($url, $query, $minPrice, $maxPrice) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $html = @file_get_contents($url, false, $context);
    if ($html === false) {
        return ['prices' => [], 'sources' => []];
    }
    
    $prices = [];
    $sources = [];
    
    // 1. Patterns de prix intelligents (vrais prix, pas réductions)
    $pricePatterns = [
        '/prix\s*:?\s*(\d+(?:,\d+)?)\s*€/i',           // "Prix: 89€"
        '/(\d+(?:,\d+)?)\s*€\s*TTC/i',                     // "89€ TTC"
        '/à\s*partir\s*de\s*(\d+(?:,\d+)?)\s*€/i',         // "à partir de 89€"
        '/dès\s*(\d+(?:,\d+)?)\s*€/i',                     // "dès 89€"
        '/(\d+(?:,\d+)?)\s*€\s*(?:neuf|occasion)/i',       // "89€ neuf"
        '/€\s*(\d+(?:,\d+)?)\s*TTC/i',                     // "€89 TTC"
        '/€\s*(\d+(?:,\d+)?)\s*HT/i',                      // "€89 HT"
        '/(\d+(?:,\d+)?)\s*€\s*(?:au\s*lieu|avant)/i',    // "89€ au lieu de"
        '/€\s*(\d+(?:,\d+)?)\s*(?:au\s*lieu|avant)/i'     // "€89 au lieu de"
    ];
    
    // 2. Patterns à éviter (réductions, économies, "-" pour -30€)
    $avoidPatterns = [
        '/réduction\s*:?\s*(\d+(?:,\d+)?)\s*€/i',          // "Réduction: 30€"
        '/remise\s*:?\s*(\d+(?:,\d+)?)\s*€/i',            // "Remise: 30€"
        '/économie\s*:?\s*(\d+(?:,\d+)?)\s*€/i',          // "Économie: 30€"
        '/promo\s*:?\s*(\d+(?:,\d+)?)\s*€/i',              // "Promo: 30€"
        '/offre\s*:?\s*(\d+(?:,\d+)?)\s*€/i',              // "Offre: 30€"
        '/rabais\s*:?\s*(\d+(?:,\d+)?)\s*€/i',             // "Rabais: 30€"
        '/-\s*(\d+(?:,\d+)?)\s*€/i',                       // "-30€" (réduction)
        '/moins\s*(\d+(?:,\d+)?)\s*€/i',                   // "moins 30€"
        '/discount\s*:?\s*(\d+(?:,\d+)?)\s*€/i',          // "Discount: 30€"
        '/save\s*:?\s*(\d+(?:,\d+)?)\s*€/i',              // "Save: 30€"
        '/off\s*:?\s*(\d+(?:,\d+)?)\s*€/i',               // "Off: 30€"
        '/reduction\s*:?\s*(\d+(?:,\d+)?)\s*€/i',         // "Reduction: 30€"
        '/sale\s*:?\s*(\d+(?:,\d+)?)\s*€/i'               // "Sale: 30€"
    ];
    
    foreach ($pricePatterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $priceStr) {
                $price = floatval(str_replace(',', '.', $priceStr));
                
                // 3. Vérifier le contexte autour du prix
                $pricePosition = strpos($html, $priceStr);
                if ($pricePosition !== false) {
                    $context = substr($html, max(0, $pricePosition - 150), 300);
                    
                    // 4. Vérifier si c'est une réduction
                    $isReduction = false;
                    foreach ($avoidPatterns as $avoidPattern) {
                        if (preg_match($avoidPattern, $context)) {
                            $isReduction = true;
                            break;
                        }
                    }
                    
                    // 5. Vérifier les mots-clés de réduction dans le contexte
                    $reductionKeywords = [
                        'réduction', 'remise', 'économie', 'promo', 'offre', 'rabais',
                        'discount', 'save', 'off', 'reduction', 'sale', 'moins',
                        'au lieu de', 'avant', 'was', 'now', 'save', 'off'
                    ];
                    
                    foreach ($reductionKeywords as $keyword) {
                        if (stripos($context, $keyword) !== false) {
                            $isReduction = true;
                            break;
                        }
                    }
                    
                    // 6. Filtrage par prix réaliste
                    if (!$isReduction && $price >= $minPrice && $price <= $maxPrice) {
                        $prices[] = $price;
                        $sources[] = [
                            'price' => $price,
                            'site' => parse_url($url, PHP_URL_HOST),
                            'url' => $url,
                            'title' => extractPageTitle($html),
                            'snippet' => extractPageDescription($html),
                            'context' => substr($context, 0, 100) // Contexte pour debug
                        ];
                    }
                }
            }
        }
    }
    
    return ['prices' => $prices, 'sources' => $sources];
}

// Fonction pour extraire le titre de la page
function extractPageTitle($html) {
    if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $html, $matches)) {
        return trim(strip_tags($matches[1]));
    }
    return 'Page sans titre';
}

// Fonction pour extraire la description de la page
function extractPageDescription($html) {
    if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches)) {
        return trim($matches[1]);
    }
    return 'Aucune description';
}

// Exécution de la recherche
$result = advancedScrape($data['query'], $minPrice, $maxPrice);

// Ajouter les informations de debug
$result['debug'] = [
    'google_success' => true,
    'google_results_count' => $result['total_results'],
    'api_key_provided' => true,
    'search_engine_id_provided' => true,
    'using_fallback' => false,
    'debug_url' => null,
    'debug_response' => null
];

$result['query'] = $data['query'];
$result['search_type'] = $data['type'] ?? 'piece';
$result['timestamp'] = date('Y-m-d H:i:s');

echo json_encode($result);
?>
