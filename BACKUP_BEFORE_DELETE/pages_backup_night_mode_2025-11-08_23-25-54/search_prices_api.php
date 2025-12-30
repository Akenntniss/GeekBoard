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

$query = trim($data['query']);
$searchType = $data['type'] ?? 'piece';
$apiKey = $data['api_key'];
$searchEngineId = $data['search_engine_id'];

// Fonction de filtrage désactivée - on garde tous les prix
function filterOutlierPrices($prices) {
    return $prices; // Pas de filtrage
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
    
    // Exécution des 2 requêtes pour obtenir 20 résultats
    $allItems = [];
    $debugUrls = [];
    
    // Première requête (résultats 1-10)
    $response1 = @file_get_contents($url1, false, $context);
    if ($response1 !== false) {
        $data1 = json_decode($response1, true);
        if ($data1 && isset($data1['items']) && !isset($data1['error'])) {
            $allItems = array_merge($allItems, $data1['items']);
        }
        $debugUrls[] = $url1;
    }
    
    // Deuxième requête (résultats 11-20)
    $response2 = @file_get_contents($url2, false, $context);
    if ($response2 !== false) {
        $data2 = json_decode($response2, true);
        if ($data2 && isset($data2['items']) && !isset($data2['error'])) {
            $allItems = array_merge($allItems, $data2['items']);
        }
        $debugUrls[] = $url2;
    }
    
    // Vérifier si on a des erreurs
    if (empty($allItems)) {
        return [
            'success' => false, 
            'error' => 'Aucun résultat trouvé dans les 2 requêtes',
            'debug_url' => implode(' | ', $debugUrls),
            'debug_response' => 'Requête 1: ' . substr($response1, 0, 200) . ' | Requête 2: ' . substr($response2, 0, 200)
        ];
    }
    
    // Créer un objet data simulé avec tous les items
    $data = ['items' => $allItems];
    
    $prices = [];
    $priceSources = [];
    
    if (isset($data['items']) && is_array($data['items'])) {
        foreach ($data['items'] as $item) {
            $text = ($item['title'] ?? '') . ' ' . ($item['snippet'] ?? '');
            $link = $item['link'] ?? '';
            
            // Filtrage uniquement d'AliExpress
            $blockedSites = [
                'aliexpress.com'
            ];
            
            // Vérifier si le lien contient un site bloqué
            $isBlocked = false;
            foreach ($blockedSites as $blockedSite) {
                if (stripos($link, $blockedSite) !== false) {
                    $isBlocked = true;
                    break;
                }
            }
            
            if ($isBlocked) {
                continue; // Ignorer ce résultat
            }
            
            // Recherche de prix en euros avec patterns étendus
            $patterns = [
                '/(\d+(?:,\d+)?)\s*€/i',                    // 123€ (tous les prix en euros)
                '/€\s*(\d+(?:,\d+)?)/i',                    // €123
                '/(\d+(?:,\d+)?)\s*euros?/i',               // 123 euro
                '/(\d+(?:\.\d+)?)\s*EUR/i',                 // 123 EUR
                '/prix\s*:?\s*(\d+(?:,\d+)?)/i',           // prix: 123
                '/à\s*partir\s*de\s*(\d+(?:,\d+)?)/i',      // à partir de 123
                '/dès\s*(\d+(?:,\d+)?)/i',                 // dès 123
                '/(\d+(?:,\d+)?)\s*€\s*TTC/i',             // 123€ TTC
                '/(\d+(?:,\d+)?)\s*€\s*HT/i',              // 123€ HT
                '/€\s*(\d+(?:,\d+)?)\s*TTC/i',             // €123 TTC
                '/€\s*(\d+(?:,\d+)?)\s*HT/i',              // €123 HT
                '/(\d+(?:,\d+)?)\s*€\s*(?:neuf|occasion)/i', // 123€ neuf/occasion
                '/(\d+(?:,\d+)?)\s*€\s*(?:au\s*lieu|avant)/i', // 123€ au lieu de
                '/€\s*(\d+(?:,\d+)?)\s*(?:au\s*lieu|avant)/i' // €123 au lieu de
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $text, $matches)) {
                    foreach ($matches[1] as $priceStr) {
                        $price = floatval(str_replace(',', '.', $priceStr));
                        
                        // Filtrer les prix réalistes pour les pièces détachées
                        if ($price >= 20 && $price <= 500) { // Prix réaliste pour un écran
                            $prices[] = $price;
                            $priceSources[] = [
                                'price' => $price,
                                'site' => parse_url($item['link'], PHP_URL_HOST),
                                'url' => $item['link'],
                                'title' => $item['title'],
                                'snippet' => $item['snippet']
                            ];
                        }
                    }
                }
            }
        }
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

// Fonction de recherche alternative avec scraping léger
function searchAlternativePrices($query, $searchType) {
    $prices = [];
    
    // Sites de référence pour les prix (simulation)
    $sites = [
        'amazon.fr',
        'cdiscount.com',
        'fnac.com',
        'darty.com',
        'boulanger.com'
    ];
    
    // Simulation de prix basée sur des patterns réalistes
    // (En production, vous pourriez implémenter un vrai scraping ou utiliser des APIs partenaires)
    
    if ($searchType === 'piece') {
        // Simulation pour pièces détachées - prix d'achat plus réalistes
        $basePrice = 25; // Prix de base plus bas
        
        // Ajustement selon le type de pièce (prix de vente réalistes)
        if (stripos($query, 'écran') !== false || stripos($query, 'screen') !== false) {
            if (stripos($query, 'original') !== false) {
                // Écran original - prix de vente réalistes selon le modèle (basés sur Google Shopping)
                if (stripos($query, '15 pro max') !== false) {
                    $basePrice = 250; // iPhone 15 Pro Max écran original
                } elseif (stripos($query, '15 plus') !== false) {
                    $basePrice = 220; // iPhone 15 Plus écran original
                } elseif (stripos($query, '15 pro') !== false) {
                    $basePrice = 230; // iPhone 15 Pro écran original
                } elseif (stripos($query, '15') !== false) {
                    $basePrice = 200; // iPhone 15 standard écran original
                } elseif (stripos($query, '14 pro max') !== false) {
                    $basePrice = 210; // iPhone 14 Pro Max écran original
                } elseif (stripos($query, '14 pro') !== false) {
                    $basePrice = 190; // iPhone 14 Pro écran original
                } elseif (stripos($query, '14 plus') !== false) {
                    $basePrice = 180; // iPhone 14 Plus écran original
                } elseif (stripos($query, '14') !== false) {
                    $basePrice = 170; // iPhone 14 standard écran original
                } elseif (stripos($query, '13 pro max') !== false) {
                    $basePrice = 180; // iPhone 13 Pro Max écran original
                } elseif (stripos($query, '13 pro') !== false) {
                    $basePrice = 160; // iPhone 13 Pro écran original
                } elseif (stripos($query, '13') !== false) {
                    $basePrice = 140; // iPhone 13 standard écran original
                } elseif (stripos($query, '12') !== false) {
                    $basePrice = 120; // iPhone 12 écran original
                } elseif (stripos($query, '11') !== false) {
                    $basePrice = 100; // iPhone 11 écran original
                } else {
                    $basePrice = 80; // Autres modèles écran original
                }
            } elseif (stripos($query, 'oled') !== false) {
                $basePrice = 120; // Écran OLED
            } elseif (stripos($query, 'lcd') !== false) {
                $basePrice = 80; // Écran LCD
            } else {
                $basePrice = 100; // Écran générique
            }
        } elseif (stripos($query, 'batterie') !== false || stripos($query, 'battery') !== false) {
            $basePrice = 12; // Batterie
        } elseif (stripos($query, 'caméra') !== false || stripos($query, 'camera') !== false) {
            $basePrice = 20; // Caméra
        } elseif (stripos($query, 'connecteur') !== false || stripos($query, 'charging') !== false) {
            $basePrice = 8; // Connecteur
        } elseif (stripos($query, 'haut-parleur') !== false || stripos($query, 'speaker') !== false) {
            $basePrice = 15; // Haut-parleur
        } elseif (stripos($query, 'microphone') !== false || stripos($query, 'micro') !== false) {
            $basePrice = 10; // Microphone
        } elseif (stripos($query, 'carte mère') !== false || stripos($query, 'motherboard') !== false) {
            $basePrice = 40; // Carte mère
        }
        
        // Génération de prix simulés avec variations réalistes
        for ($i = 0; $i < 8; $i++) {
            $variation = mt_rand(-15, 25) / 100; // Variation de -15% à +25%
            $price = $basePrice * (1 + $variation);
            $prices[] = round($price, 2);
        }
        
    } else {
        // Simulation pour téléphones
        $basePrice = 300; // Prix de base
        
        // Ajustement selon la marque/modèle
        if (stripos($query, 'iphone') !== false) {
            if (stripos($query, '13') !== false || stripos($query, '14') !== false || stripos($query, '15') !== false) {
                $basePrice = 600;
            } elseif (stripos($query, '12') !== false || stripos($query, '11') !== false) {
                $basePrice = 400;
            } else {
                $basePrice = 200;
            }
        } elseif (stripos($query, 'samsung') !== false) {
            if (stripos($query, 's23') !== false || stripos($query, 's22') !== false || stripos($query, 's21') !== false) {
                $basePrice = 500;
            } else {
                $basePrice = 250;
            }
        }
        
        // Génération de prix simulés
        for ($i = 0; $i < 6; $i++) {
            $variation = mt_rand(-40, 30) / 100; // Variation de -40% à +30%
            $price = $basePrice * (1 + $variation);
            $prices[] = round($price, 2);
        }
    }
    
    return ['success' => true, 'prices' => $prices, 'total_results' => count($prices), 'source' => 'simulation'];
}

    // Exécution de la recherche
    try {
        $result = searchGooglePrices($query, $searchType, $apiKey, $searchEngineId);
        
        // Debug : afficher le nombre de résultats Google
        $result['debug'] = [
            'google_success' => $result['success'],
            'google_results_count' => count($result['prices'] ?? []),
            'api_key_provided' => !empty($apiKey),
            'search_engine_id_provided' => !empty($searchEngineId),
            'debug_url' => $result['debug_url'] ?? null,
            'debug_response' => $result['debug_response'] ?? null
        ];
        
        // Si la recherche Google ne donne pas de résultats, utiliser l'alternative
        if (!$result['success'] || empty($result['prices'])) {
            $result = searchAlternativePrices($query, $searchType);
            $result['fallback'] = true;
            $result['debug']['using_fallback'] = true;
        }
    
    // Ajout d'informations de debug
    $result['query'] = $query;
    $result['search_type'] = $searchType;
    $result['timestamp'] = date('Y-m-d H:i:s');
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur: ' . $e->getMessage(),
        'query' => $query,
        'search_type' => $searchType
    ]);
}
?>
