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

// Validation des clés API
if (empty($data['api_key']) || empty($data['search_engine_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'API Google non configurée',
        'debug' => [
            'api_key_provided' => !empty($data['api_key']),
            'search_engine_id_provided' => !empty($data['search_engine_id']),
            'message' => 'Veuillez configurer l\'API Google dans les paramètres'
        ]
    ]);
    exit;
}

// Fonction de scraping direct des sites pour prix réels
function scrapeRealPrices($query, $searchType, $apiKey, $searchEngineId) {
    // D'abord, utiliser Google pour trouver les sites
    $sites = findRelevantSites($query, $apiKey, $searchEngineId);
    
    if (empty($sites)) {
        return ['success' => false, 'error' => 'Aucun site trouvé'];
    }
    
    $prices = [];
    $priceSources = [];
    
    // Scraper chaque site trouvé
    foreach ($sites as $site) {
        $sitePrices = scrapeSitePrices($site['url'], $query);
        if (!empty($sitePrices)) {
            $prices = array_merge($prices, $sitePrices['prices']);
            $priceSources = array_merge($priceSources, $sitePrices['sources']);
        }
    }
    
    if (empty($prices)) {
        return ['success' => false, 'error' => 'Aucun prix trouvé sur les sites'];
    }
    
    // Suppression des doublons et tri
    $prices = array_values(array_unique($prices));
    sort($prices);
    
    return [
        'success' => true,
        'prices' => $prices,
        'price_sources' => $priceSources,
        'total_results' => count($prices),
        'original_count' => count($prices),
        'average_price' => count($prices) > 0 ? round(array_sum($prices) / count($prices), 2) : 0,
        'min_price' => count($prices) > 0 ? min($prices) : 0,
        'max_price' => count($prices) > 0 ? max($prices) : 0
    ];
}

// Fonction pour trouver les sites pertinents via Google
function findRelevantSites($query, $apiKey, $searchEngineId) {
    // Nettoyer la requête
    $cleanQuery = str_replace(['é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'ç', 'ù', 'û', 'ü', 'ô', 'ö', 'î', 'ï'], 
                              ['e', 'e', 'e', 'e', 'a', 'a', 'a', 'c', 'u', 'u', 'u', 'o', 'o', 'i', 'i'], 
                              $query);
    
    // Première requête : résultats 1-10
    $url1 = "https://www.googleapis.com/customsearch/v1?" . http_build_query([
        'key' => $apiKey,
        'cx' => $searchEngineId,
        'q' => $cleanQuery,
        'num' => 10,
        'start' => 1,
        'safe' => 'active'
    ], '', '&', PHP_QUERY_RFC3986);
    
    // Deuxième requête : résultats 11-20
    $url2 = "https://www.googleapis.com/customsearch/v1?" . http_build_query([
        'key' => $apiKey,
        'cx' => $searchEngineId,
        'q' => $cleanQuery,
        'num' => 10,
        'start' => 11,
        'safe' => 'active'
    ], '', '&', PHP_QUERY_RFC3986);
    
    // Configuration du contexte HTTP
    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'method' => 'GET'
        ]
    ]);
    
    $allItems = [];
    
    // Première requête (résultats 1-10)
    $response1 = @file_get_contents($url1, false, $context);
    if ($response1 !== false) {
        $data1 = json_decode($response1, true);
        if ($data1 && isset($data1['items']) && !isset($data1['error'])) {
            $allItems = array_merge($allItems, $data1['items']);
        }
    }
    
    // Deuxième requête (résultats 11-20)
    $response2 = @file_get_contents($url2, false, $context);
    if ($response2 !== false) {
        $data2 = json_decode($response2, true);
        if ($data2 && isset($data2['items']) && !isset($data2['error'])) {
            $allItems = array_merge($allItems, $data2['items']);
        }
    }
    
    return $allItems;
}

// Fonction de scraping d'un site avec filtres intelligents
function scrapeSitePrices($url, $query) {
    // Configuration du contexte HTTP
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'method' => 'GET'
        ]
    ]);
    
    // Récupérer le HTML de la page
    $html = @file_get_contents($url, false, $context);
    if ($html === false) {
        return ['prices' => [], 'sources' => []];
    }
    
    $prices = [];
    $sources = [];
    
    // Patterns de prix intelligents (vrais prix, pas réductions)
    $pricePatterns = [
        '/prix\s*:?\s*(\d+(?:,\d+)?)\s*€/i',           // "Prix: 89€"
        '/(\d+(?:,\d+)?)\s*€\s*TTC/i',                     // "89€ TTC"
        '/à\s*partir\s*de\s*(\d+(?:,\d+)?)\s*€/i',         // "à partir de 89€"
        '/dès\s*(\d+(?:,\d+)?)\s*€/i',                     // "dès 89€"
        '/(\d+(?:,\d+)?)\s*€\s*(?:neuf|occasion)/i',       // "89€ neuf"
        '/€\s*(\d+(?:,\d+)?)\s*TTC/i',                     // "€89 TTC"
        '/€\s*(\d+(?:,\d+)?)\s*HT/i'                       // "€89 HT"
    ];
    
    // Patterns à éviter (réductions, économies)
    $avoidPatterns = [
        '/réduction\s*:?\s*(\d+(?:,\d+)?)\s*€/i',          // "Réduction: 30€"
        '/remise\s*:?\s*(\d+(?:,\d+)?)\s*€/i',            // "Remise: 30€"
        '/économie\s*:?\s*(\d+(?:,\d+)?)\s*€/i',          // "Économie: 30€"
        '/promo\s*:?\s*(\d+(?:,\d+)?)\s*€/i',              // "Promo: 30€"
        '/offre\s*:?\s*(\d+(?:,\d+)?)\s*€/i',              // "Offre: 30€"
        '/rabais\s*:?\s*(\d+(?:,\d+)?)\s*€/i',             // "Rabais: 30€"
        '/-\s*(\d+(?:,\d+)?)\s*€/i',                       // "-30€" (réduction)
        '/moins\s*(\d+(?:,\d+)?)\s*€/i'                    // "moins 30€"
    ];
    
    foreach ($pricePatterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $priceStr) {
                $price = floatval(str_replace(',', '.', $priceStr));
                
                // Vérifier le contexte autour du prix
                $pricePosition = strpos($html, $priceStr);
                if ($pricePosition !== false) {
                    $context = substr($html, max(0, $pricePosition - 100), 200);
                    
                    // Vérifier si c'est une réduction
                    $isReduction = false;
                    foreach ($avoidPatterns as $avoidPattern) {
                        if (preg_match($avoidPattern, $context)) {
                            $isReduction = true;
                            break;
                        }
                    }
                    
                    // Filtrage par prix réaliste (paramètres configurables)
                    $minPrice = 20;  // À configurer dans les paramètres
                    $maxPrice = 500; // À configurer dans les paramètres
                    
                    if (!$isReduction && $price >= $minPrice && $price <= $maxPrice) {
                        $prices[] = $price;
                        $sources[] = [
                            'price' => $price,
                            'site' => parse_url($url, PHP_URL_HOST),
                            'url' => $url,
                            'title' => extractPageTitle($html),
                            'snippet' => extractPageDescription($html)
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
try {
    $result = scrapeRealPrices($data['query'], $data['type'] ?? 'piece', $data['api_key'], $data['search_engine_id']);
    
    // Ajouter les informations de debug
    $result['debug'] = [
        'google_success' => $result['success'] ?? false,
        'google_results_count' => $result['total_results'] ?? 0,
        'api_key_provided' => !empty($data['api_key']),
        'search_engine_id_provided' => !empty($data['search_engine_id']),
        'debug_url' => null,
        'debug_response' => null
    ];
    
    $result['query'] = $data['query'];
    $result['search_type'] = $data['type'] ?? 'piece';
    $result['timestamp'] = date('Y-m-d H:i:s');
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur interne: ' . $e->getMessage(),
        'debug' => [
            'google_success' => false,
            'google_results_count' => 0,
            'api_key_provided' => !empty($data['api_key']),
            'search_engine_id_provided' => !empty($data['search_engine_id']),
            'debug_url' => null,
            'debug_response' => $e->getMessage()
        ],
        'query' => $data['query'],
        'search_type' => $data['type'] ?? 'piece',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
