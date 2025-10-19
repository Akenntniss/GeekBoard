<?php
session_start();
require_once '../config/database.php';

// Initialisation de la session magasin (sans vérification d'authentification utilisateur)
try {
    initializeShopSession();
    $pdo = getShopDBConnection();
} catch (Exception $e) {
    // Si on ne peut pas se connecter à la base, afficher une erreur
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Récupération des paramètres de configuration
function getCalculatorSettings($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM calculator_settings WHERE id = 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings) {
            // Valeurs par défaut
            $settings = [
                'margin_min' => 30,
                'margin_max' => 60,
                'difficulty_easy' => 1.0,
                'difficulty_medium' => 1.5,
                'difficulty_hard' => 2.0,
                'time_rate' => 25,
                'google_api_key' => '',
                'google_search_engine_id' => ''
            ];
        }
        return $settings;
    } catch (PDOException $e) {
        return [
            'margin_min' => 30,
            'margin_max' => 60,
            'difficulty_easy' => 1.0,
            'difficulty_medium' => 1.5,
            'difficulty_hard' => 2.0,
            'time_rate' => 25,
            'google_api_key' => '',
            'google_search_engine_id' => ''
        ];
    }
}

$settings = getCalculatorSettings($pdo);

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_settings':
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO calculator_settings (id, margin_min, margin_max, difficulty_easy, difficulty_medium, difficulty_hard, time_rate, google_api_key, google_search_engine_id, updated_at) 
                        VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, NOW()) 
                        ON DUPLICATE KEY UPDATE 
                        margin_min = VALUES(margin_min),
                        margin_max = VALUES(margin_max),
                        difficulty_easy = VALUES(difficulty_easy),
                        difficulty_medium = VALUES(difficulty_medium),
                        difficulty_hard = VALUES(difficulty_hard),
                        time_rate = VALUES(time_rate),
                        google_api_key = VALUES(google_api_key),
                        google_search_engine_id = VALUES(google_search_engine_id),
                        updated_at = NOW()
                    ");
                    $stmt->execute([
                        $_POST['margin_min'],
                        $_POST['margin_max'],
                        $_POST['difficulty_easy'],
                        $_POST['difficulty_medium'],
                        $_POST['difficulty_hard'],
                        $_POST['time_rate'],
                        $_POST['google_api_key'],
                        $_POST['google_search_engine_id']
                    ]);
                    
                    $settings = getCalculatorSettings($pdo);
                    $success_message = "Paramètres sauvegardés avec succès !";
                } catch (PDOException $e) {
                    $error_message = "Erreur lors de la sauvegarde : " . $e->getMessage();
                }
                break;
        }
    }
}

// Fonction de recherche Google
function searchGooglePrices($query, $apiKey, $searchEngineId) {
    if (empty($apiKey) || empty($searchEngineId)) {
        return [];
    }
    
    $url = "https://www.googleapis.com/customsearch/v1?" . http_build_query([
        'key' => $apiKey,
        'cx' => $searchEngineId,
        'q' => $query . " prix achat",
        'num' => 10
    ]);
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return [];
    }
    
    $data = json_decode($response, true);
    $prices = [];
    
    if (isset($data['items'])) {
        foreach ($data['items'] as $item) {
            $text = $item['title'] . ' ' . $item['snippet'];
            
            // Recherche de prix en euros
            if (preg_match_all('/(\d+(?:,\d+)?)\s*€/', $text, $matches)) {
                foreach ($matches[1] as $price) {
                    $price = floatval(str_replace(',', '.', $price));
                    if ($price > 5 && $price < 2000) { // Prix raisonnables
                        $prices[] = $price;
                    }
                }
            }
        }
    }
    
    return array_unique($prices);
}

// Fonction de calcul du prix de vente
function calculateSellPrice($costPrice, $repairTime, $difficulty, $settings) {
    $laborCost = ($repairTime / 60) * $settings['time_rate']; // Temps en heures * tarif horaire
    
    $difficultyMultiplier = 1;
    switch ($difficulty) {
        case 'facile':
            $difficultyMultiplier = $settings['difficulty_easy'] ?? 1.0;
            break;
        case 'moyen':
            $difficultyMultiplier = $settings['difficulty_medium'] ?? 1.5;
            break;
        case 'difficile':
            $difficultyMultiplier = $settings['difficulty_hard'] ?? 2.0;
            break;
    }
    
    $adjustedLaborCost = $laborCost * $difficultyMultiplier;
    $totalCost = $costPrice + $adjustedLaborCost;
    
    $minPrice = $totalCost * (1 + $settings['margin_min'] / 100);
    $maxPrice = $totalCost * (1 + $settings['margin_max'] / 100);
    
    return [
        'cost_price' => $costPrice,
        'labor_cost' => $laborCost,
        'adjusted_labor_cost' => $adjustedLaborCost,
        'total_cost' => $totalCost,
        'min_sell_price' => $minPrice,
        'max_sell_price' => $maxPrice,
        'recommended_price' => ($minPrice + $maxPrice) / 2
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculateur de Prix - GeekBoard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .content-section {
            padding: 30px;
        }
        
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        
        .card-custom:hover {
            transform: translateY(-5px);
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            border: none;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .price-result {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .price-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .nav-pills .nav-link {
            border-radius: 10px;
            margin: 0 5px;
            transition: all 0.3s ease;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .search-results {
            max-height: 300px;
            overflow-y: auto;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .price-tag {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9em;
            margin: 2px;
            display: inline-block;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .alert-custom {
            border-radius: 15px;
            border: none;
            padding: 15px 20px;
        }
        
        .piece-btn {
            transition: all 0.3s ease;
            border-radius: 10px;
        }
        
        .piece-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .piece-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1><i class="fas fa-calculator me-3"></i>Calculateur de Prix de Réparation</h1>
            <p class="mb-0">Outil intelligent pour calculer vos prix de vente avec recherche automatique</p>
        </div>
        
        <div class="content-section">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-custom">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-custom">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Navigation par onglets -->
            <ul class="nav nav-pills mb-4" id="mainTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="calculator-tab" data-bs-toggle="pill" data-bs-target="#calculator" type="button" role="tab">
                        <i class="fas fa-calculator me-2"></i>Calculateur
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="search-tab" data-bs-toggle="pill" data-bs-target="#search" type="button" role="tab">
                        <i class="fas fa-search me-2"></i>Recherche Prix
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="settings-tab" data-bs-toggle="pill" data-bs-target="#settings" type="button" role="tab">
                        <i class="fas fa-cog me-2"></i>Paramètres
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="mainTabContent">
                <!-- Onglet Calculateur -->
                <div class="tab-pane fade show active" id="calculator" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card card-custom">
                                <div class="card-header card-header-custom">
                                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Informations de Réparation</h5>
                                </div>
                                <div class="card-body">
                                    <form id="calculatorForm">
                                        <div class="mb-3">
                                            <label for="repairType" class="form-label">Type de Réparation</label>
                                            <select class="form-select" id="repairType" required>
                                                <option value="">Sélectionnez le type</option>
                                                <option value="ecran-original">Réparation Écran Original</option>
                                                <option value="ecran-oled">Réparation Écran OLED</option>
                                                <option value="ecran-lcd">Réparation Écran LCD</option>
                                                <option value="batterie">Remplacement Batterie</option>
                                                <option value="camera">Réparation Caméra</option>
                                                <option value="connecteur">Réparation Connecteur</option>
                                                <option value="haut-parleur">Réparation Haut-parleur</option>
                                                <option value="micro">Réparation Microphone</option>
                                                <option value="carte-mere">Réparation Carte Mère</option>
                                                <option value="autre">Autre</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="deviceModel" class="form-label">Modèle de l'Appareil</label>
                                            <input type="text" class="form-control" id="deviceModel" placeholder="ex: iPhone 13 Pro, Samsung Galaxy S21" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="costPrice" class="form-label">Prix de Revient (€)</label>
                                            <input type="number" class="form-control" id="costPrice" step="0.01" min="0" placeholder="Prix d'achat de la pièce" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="repairTime" class="form-label">Temps de Réparation (minutes)</label>
                                            <input type="number" class="form-control" id="repairTime" min="1" placeholder="Temps estimé en minutes" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="difficulty" class="form-label">Difficulté de la Réparation</label>
                                            <select class="form-select" id="difficulty" required>
                                                <option value="">Sélectionnez la difficulté</option>
                                                <option value="facile">Facile (x<?php echo $settings['difficulty_easy'] ?? 1.0; ?>)</option>
                                                <option value="moyen">Moyenne (x<?php echo $settings['difficulty_medium'] ?? 1.5; ?>)</option>
                                                <option value="difficile">Difficile (x<?php echo $settings['difficulty_hard'] ?? 2.0; ?>)</option>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-custom w-100">
                                            <i class="fas fa-calculator me-2"></i>Calculer le Prix
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="card card-custom">
                                <div class="card-header card-header-custom">
                                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Résultat du Calcul</h5>
                                </div>
                                <div class="card-body">
                                    <div id="calculationResult" style="display: none;">
                                        <div class="price-result">
                                            <div class="price-item">
                                                <span>Prix de revient :</span>
                                                <span id="resultCostPrice">0.00 €</span>
                                            </div>
                                            <div class="price-item">
                                                <span>Coût main d'œuvre :</span>
                                                <span id="resultLaborCost">0.00 €</span>
                                            </div>
                                            <div class="price-item">
                                                <span>Coût ajusté (difficulté) :</span>
                                                <span id="resultAdjustedLaborCost">0.00 €</span>
                                            </div>
                                            <div class="price-item">
                                                <span>Coût total :</span>
                                                <span id="resultTotalCost">0.00 €</span>
                                            </div>
                                            <div class="price-item">
                                                <span>Prix minimum :</span>
                                                <span id="resultMinPrice">0.00 €</span>
                                            </div>
                                            <div class="price-item">
                                                <span>Prix maximum :</span>
                                                <span id="resultMaxPrice">0.00 €</span>
                                            </div>
                                            <div class="price-item">
                                                <span><strong>Prix recommandé :</strong></span>
                                                <span id="resultRecommendedPrice"><strong>0.00 €</strong></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="noCalculation" class="text-center text-muted py-5">
                                        <i class="fas fa-calculator fa-3x mb-3"></i>
                                        <p>Remplissez le formulaire pour calculer le prix de vente</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Onglet Recherche Prix -->
                <div class="tab-pane fade" id="search" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8 mx-auto">
                            <div class="card card-custom">
                                <div class="card-header card-header-custom">
                                    <h5 class="mb-0"><i class="fas fa-search me-2"></i>Recherche de Prix de Pièces Détachées</h5>
                                </div>
                                <div class="card-body">
                                    <form id="searchForm">
                                        <div class="mb-3">
                                            <label for="deviceModel" class="form-label">Modèle du Téléphone</label>
                                            <input type="text" class="form-control" id="deviceModel" placeholder="ex: iPhone 13 Pro, Samsung Galaxy S21, Xiaomi Redmi Note 10" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Pièce Détachée</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <button type="button" class="btn btn-outline-primary w-100 mb-2 piece-btn" data-piece="écran original">
                                                        <i class="fas fa-mobile-alt me-2"></i>Écran Original
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary w-100 mb-2 piece-btn" data-piece="écran oled">
                                                        <i class="fas fa-mobile-alt me-2"></i>Écran OLED
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary w-100 mb-2 piece-btn" data-piece="écran lcd">
                                                        <i class="fas fa-mobile-alt me-2"></i>Écran LCD
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary w-100 mb-2 piece-btn" data-piece="batterie">
                                                        <i class="fas fa-battery-half me-2"></i>Batterie
                                                    </button>
                                                </div>
                                                <div class="col-md-6">
                                                    <button type="button" class="btn btn-outline-primary w-100 mb-2 piece-btn" data-piece="caméra">
                                                        <i class="fas fa-camera me-2"></i>Caméra
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary w-100 mb-2 piece-btn" data-piece="connecteur">
                                                        <i class="fas fa-plug me-2"></i>Connecteur
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary w-100 mb-2 piece-btn" data-piece="haut-parleur">
                                                        <i class="fas fa-volume-up me-2"></i>Haut-parleur
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary w-100 mb-2 piece-btn" data-piece="microphone">
                                                        <i class="fas fa-microphone me-2"></i>Microphone
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-md-6">
                                                    <button type="button" class="btn btn-outline-primary w-100 mb-2 piece-btn" data-piece="carte mère">
                                                        <i class="fas fa-microchip me-2"></i>Carte Mère
                                                    </button>
                                                </div>
                                                <div class="col-md-6">
                                                    <button type="button" class="btn btn-outline-primary w-100 mb-2 piece-btn" data-piece="autre">
                                                        <i class="fas fa-ellipsis-h me-2"></i>Autre
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3" id="customPieceDiv" style="display: none;">
                                            <label for="customPiece" class="form-label">Autre Pièce (précisez)</label>
                                            <input type="text" class="form-control" id="customPiece" placeholder="ex: caméra arrière, bouton power, flex antenne">
                                        </div>
                                        
                                        <input type="hidden" id="selectedPiece" value="">
                                        
                                        <button type="submit" class="btn btn-custom w-100">
                                            <i class="fas fa-search me-2"></i>Rechercher les Prix
                                        </button>
                                    </form>
                                    
                                    <div class="loading" id="searchLoading">
                                        <div class="spinner"></div>
                                        <p class="mt-3">Recherche en cours...</p>
                                    </div>
                                    
                                    <div id="searchResults"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Onglet Paramètres -->
                <div class="tab-pane fade" id="settings" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8 mx-auto">
                            <div class="card card-custom">
                                <div class="card-header card-header-custom">
                                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Paramètres du Calculateur</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="save_settings">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="margin_min" class="form-label">Marge Minimum (%)</label>
                                                    <input type="number" class="form-control" name="margin_min" id="margin_min" value="<?php echo $settings['margin_min']; ?>" min="0" max="100" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="margin_max" class="form-label">Marge Maximum (%)</label>
                                                    <input type="number" class="form-control" name="margin_max" id="margin_max" value="<?php echo $settings['margin_max']; ?>" min="0" max="1000" required>
                                                    <small class="text-muted">Permet des marges jusqu'à 1000% (x10)</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="difficulty_easy" class="form-label">Multiplicateur Facile</label>
                                                    <input type="number" class="form-control" name="difficulty_easy" id="difficulty_easy" value="<?php echo $settings['difficulty_easy'] ?? 1.0; ?>" step="0.1" min="0.5" max="5" required>
                                                    <small class="text-muted">Facile (pas de surcharge)</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="difficulty_medium" class="form-label">Multiplicateur Moyenne</label>
                                                    <input type="number" class="form-control" name="difficulty_medium" id="difficulty_medium" value="<?php echo $settings['difficulty_medium'] ?? 1.5; ?>" step="0.1" min="0.5" max="5" required>
                                                    <small class="text-muted">Moyenne (surcharge standard)</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="difficulty_hard" class="form-label">Multiplicateur Difficile</label>
                                                    <input type="number" class="form-control" name="difficulty_hard" id="difficulty_hard" value="<?php echo $settings['difficulty_hard'] ?? 2.0; ?>" step="0.1" min="0.5" max="5" required>
                                                    <small class="text-muted">Difficile (surcharge élevée)</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="time_rate" class="form-label">Tarif Horaire (€)</label>
                                                    <input type="number" class="form-control" name="time_rate" id="time_rate" value="<?php echo $settings['time_rate']; ?>" min="1" max="200" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        <h6><i class="fas fa-key me-2"></i>Configuration API Google</h6>
                                        
                                        <div class="mb-3">
                                            <label for="google_api_key" class="form-label">Clé API Google</label>
                                            <input type="password" class="form-control" name="google_api_key" id="google_api_key" value="<?php echo $settings['google_api_key']; ?>" placeholder="Votre clé API Google Custom Search">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="google_search_engine_id" class="form-label">ID Moteur de Recherche</label>
                                            <input type="text" class="form-control" name="google_search_engine_id" id="google_search_engine_id" value="<?php echo $settings['google_search_engine_id']; ?>" placeholder="ID de votre moteur de recherche personnalisé">
                                        </div>
                                        
                                        <div class="alert alert-info alert-custom">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Configuration API Google :</strong><br>
                                            1. Créez un projet sur Google Cloud Console<br>
                                            2. Activez l'API Custom Search<br>
                                            3. Créez une clé API<br>
                                            4. Configurez un moteur de recherche personnalisé sur programmablesearchengine.google.com<br>
                                            <strong>Note :</strong> Chaque boutique peut configurer sa propre API pour plus de flexibilité
                                        </div>
                                        
                                        <div class="alert alert-success alert-custom">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <strong>API Actuellement Configurée :</strong><br>
                                            <strong>Clé API :</strong> <?php echo !empty($settings['google_api_key']) ? substr($settings['google_api_key'], 0, 10) . '...' : 'Non configurée'; ?><br>
                                            <strong>ID Moteur :</strong> <?php echo !empty($settings['google_search_engine_id']) ? $settings['google_search_engine_id'] : 'Non configuré'; ?>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-custom w-100">
                                            <i class="fas fa-save me-2"></i>Sauvegarder les Paramètres
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configuration globale
        const config = {
            marginMin: <?php echo $settings['margin_min']; ?>,
            marginMax: <?php echo $settings['margin_max']; ?>,
            difficultyEasy: <?php echo $settings['difficulty_easy'] ?? 1.0; ?>,
            difficultyMedium: <?php echo $settings['difficulty_medium'] ?? 1.5; ?>,
            difficultyHard: <?php echo $settings['difficulty_hard'] ?? 2.0; ?>,
            timeRate: <?php echo $settings['time_rate']; ?>,
            googleApiKey: '<?php echo $settings['google_api_key']; ?>',
            googleSearchEngineId: '<?php echo $settings['google_search_engine_id']; ?>'
        };
        
        // Fonction de calcul du prix
        function calculatePrice(costPrice, repairTime, difficulty) {
            const laborCost = (repairTime / 60) * config.timeRate;
            
            let difficultyMultiplier = 1;
            switch (difficulty) {
                case 'facile':
                    difficultyMultiplier = config.difficultyEasy;
                    break;
                case 'moyen':
                    difficultyMultiplier = config.difficultyMedium;
                    break;
                case 'difficile':
                    difficultyMultiplier = config.difficultyHard;
                    break;
            }
            
            const adjustedLaborCost = laborCost * difficultyMultiplier;
            const totalCost = costPrice + adjustedLaborCost;
            const minPrice = totalCost * (1 + config.marginMin / 100);
            const maxPrice = totalCost * (1 + config.marginMax / 100);
            const recommendedPrice = (minPrice + maxPrice) / 2;
            
            return {
                costPrice: costPrice,
                laborCost: laborCost,
                adjustedLaborCost: adjustedLaborCost,
                totalCost: totalCost,
                minPrice: minPrice,
                maxPrice: maxPrice,
                recommendedPrice: recommendedPrice
            };
        }
        
        // Gestionnaire du formulaire calculateur
        document.getElementById('calculatorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const costPrice = parseFloat(document.getElementById('costPrice').value);
            const repairTime = parseInt(document.getElementById('repairTime').value);
            const difficulty = document.getElementById('difficulty').value;
            
            if (costPrice && repairTime && difficulty) {
                const result = calculatePrice(costPrice, repairTime, difficulty);
                
                // Affichage des résultats
                document.getElementById('resultCostPrice').textContent = result.costPrice.toFixed(2) + ' €';
                document.getElementById('resultLaborCost').textContent = result.laborCost.toFixed(2) + ' €';
                document.getElementById('resultAdjustedLaborCost').textContent = result.adjustedLaborCost.toFixed(2) + ' €';
                document.getElementById('resultTotalCost').textContent = result.totalCost.toFixed(2) + ' €';
                document.getElementById('resultMinPrice').textContent = result.minPrice.toFixed(2) + ' €';
                document.getElementById('resultMaxPrice').textContent = result.maxPrice.toFixed(2) + ' €';
                document.getElementById('resultRecommendedPrice').textContent = result.recommendedPrice.toFixed(2) + ' €';
                
                document.getElementById('noCalculation').style.display = 'none';
                document.getElementById('calculationResult').style.display = 'block';
            }
        });
        
        // Gestion des boutons de pièces
        document.querySelectorAll('.piece-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Retirer la classe active de tous les boutons
                document.querySelectorAll('.piece-btn').forEach(b => b.classList.remove('active'));
                
                // Ajouter la classe active au bouton cliqué
                this.classList.add('active');
                
                const piece = this.getAttribute('data-piece');
                document.getElementById('selectedPiece').value = piece;
                
                // Afficher le champ personnalisé si "autre" est sélectionné
                if (piece === 'autre') {
                    document.getElementById('customPieceDiv').style.display = 'block';
                    document.getElementById('customPiece').required = true;
                } else {
                    document.getElementById('customPieceDiv').style.display = 'none';
                    document.getElementById('customPiece').required = false;
                }
            });
        });
        
        // Gestionnaire du formulaire de recherche
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const deviceModel = document.getElementById('deviceModel').value.trim();
            const selectedPiece = document.getElementById('selectedPiece').value;
            const customPiece = document.getElementById('customPiece').value;
            
            if (!deviceModel) {
                alert('Veuillez entrer le modèle du téléphone');
                return;
            }
            
            if (!selectedPiece) {
                alert('Veuillez sélectionner une pièce détachée');
                return;
            }
            
            // Construire la requête de recherche
            let pieceName = selectedPiece;
            if (selectedPiece === 'autre' && customPiece) {
                pieceName = customPiece;
            }
            
            const query = `${pieceName} ${deviceModel}`;
            
            // Debug : afficher la requête construite
            console.log('Requête de recherche:', query);
            console.log('Modèle:', deviceModel);
            console.log('Pièce:', pieceName);
            
            if (!config.googleApiKey || !config.googleSearchEngineId) {
                document.getElementById('searchResults').innerHTML = `
                    <div class="alert alert-warning alert-custom">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>API Google non configurée !</strong><br>
                        Veuillez configurer les paramètres API Google dans l'onglet Paramètres pour utiliser la recherche automatique.<br>
                        <small>Actuellement: API Key = ${config.googleApiKey ? '✅ Configurée' : '❌ Manquante'} | Search Engine ID = ${config.googleSearchEngineId ? '✅ Configurée' : '❌ Manquante'}</small>
                    </div>
                `;
                return;
            }
            
            // Affichage du loading
            document.getElementById('searchLoading').style.display = 'block';
            document.getElementById('searchResults').innerHTML = '';
            
            // Appel à l'API de recherche (toujours en mode pièce)
            searchPrices(query, 'piece');
        });
        
        // Fonction de recherche de prix
        async function searchPrices(query, searchType) {
            try {
                const response = await fetch('smart_price_scraper.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        query: query,
                        type: searchType
                    })
                });
                
                const data = await response.json();
                document.getElementById('searchLoading').style.display = 'none';
                
                if (data.success && data.prices && data.prices.length > 0) {
                    displaySearchResults(data, query);
                } else if (data.error === 'API Google non configurée') {
                    document.getElementById('searchResults').innerHTML = `
                        <div class="search-results">
                            <div class="alert alert-danger alert-custom">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>API Google non configurée !</strong><br>
                                Veuillez configurer l'API Google dans l'onglet Paramètres pour utiliser la recherche automatique.<br>
                                <small>API Key: ${data.debug?.api_key_provided ? '✅ Configurée' : '❌ Manquante'} | Search Engine ID: ${data.debug?.search_engine_id_provided ? '✅ Configurée' : '❌ Manquante'}</small>
                            </div>
                        </div>
                    `;
                } else {
                    document.getElementById('searchResults').innerHTML = `
                        <div class="search-results">
                            <div class="alert alert-info alert-custom">
                                <i class="fas fa-info-circle me-2"></i>
                                Aucun prix trouvé pour "${query}". Essayez avec des termes différents.
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('searchLoading').style.display = 'none';
                document.getElementById('searchResults').innerHTML = `
                    <div class="search-results">
                        <div class="alert alert-danger alert-custom">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Erreur lors de la recherche : ${error.message}
                        </div>
                    </div>
                `;
            }
        }
        
        // Fonction d'affichage des résultats de recherche
        // Fonction pour afficher les détails de recherche dans un modal
        function showSearchDetails(data) {
            const modal = document.getElementById('searchDetailsModal');
            const modalBody = document.getElementById('searchDetailsBody');
            
            let html = `
                <div class="search-details">
                    <h6><i class="fas fa-info-circle me-2"></i>Détails de la recherche</h6>
                    
                    <div class="mb-3">
                        <strong>Requête :</strong> ${data.query || 'N/A'}<br>
                        <strong>Type :</strong> ${data.search_type || 'N/A'}<br>
                        <strong>Timestamp :</strong> ${data.timestamp || 'N/A'}
                    </div>
                    
                    <div class="mb-3">
                        <strong>Statut API :</strong>
                        <span class="badge ${data.debug?.google_success ? 'bg-success' : 'bg-danger'}">
                            ${data.debug?.google_success ? '✅ Fonctionne' : '❌ Échec'}
                        </span><br>
                        <strong>Résultats Google :</strong> ${data.debug?.google_results_count || 0}<br>
                        <strong>Source :</strong> ${data.source || 'N/A'}<br>
                        <strong>Fallback :</strong> ${data.fallback ? 'Oui' : 'Non'}
                    </div>
                    
                    <div class="mb-3">
                        <strong>Prix trouvés (${data.prices?.length || 0}) :</strong>
                        <div class="mt-2">
                            ${data.prices ? data.prices.map(price => `<span class="badge bg-primary me-1">${price.toFixed(2)}€</span>`).join('') : 'Aucun prix'}
                        </div>
                    </div>
                    
                    ${data.price_sources && data.price_sources.length > 0 ? `
                        <div class="mb-3">
                            <strong>Détail des prix par site :</strong>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Prix</th>
                                            <th>Site Web</th>
                                            <th>Titre</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.price_sources.map(source => `
                                            <tr>
                                                <td><strong>${source.price.toFixed(2)}€</strong></td>
                                                <td>
                                                    <a href="${source.url}" target="_blank" class="text-decoration-none">
                                                        ${source.site}
                                                    </a>
                                                </td>
                                                <td>
                                                    <small class="text-muted" title="${source.snippet}">
                                                        ${source.title.length > 50 ? source.title.substring(0, 50) + '...' : source.title}
                                                    </small>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="mb-3">
                        <strong>Statistiques :</strong><br>
                        • Prix minimum : ${data.min_price || 'N/A'}€<br>
                        • Prix maximum : ${data.max_price || 'N/A'}€<br>
                        • Prix moyen : ${data.average_price || 'N/A'}€<br>
                        • Total résultats : ${data.total_results || 'N/A'}
                    </div>
                    
                    ${data.debug?.debug_url ? `
                        <div class="mb-3">
                            <strong>URL API :</strong><br>
                            <small class="text-muted">${data.debug.debug_url}</small>
                        </div>
                    ` : ''}
                    
                    ${data.debug?.debug_response ? `
                        <div class="mb-3">
                            <strong>Réponse API :</strong><br>
                            <small class="text-muted">${data.debug.debug_response}</small>
                        </div>
                    ` : ''}
                    
                    <div class="mb-3">
                        <strong>Données complètes :</strong>
                        <pre class="bg-light p-2 mt-2" style="font-size: 0.8em; max-height: 200px; overflow-y: auto;">${JSON.stringify(data, null, 2)}</pre>
                    </div>
                </div>
            `;
            
            modalBody.innerHTML = html;
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }

        function displaySearchResults(data, query) {
            const prices = data.prices || data;
            const uniquePrices = [...new Set(prices)].sort((a, b) => a - b);
            const avgPrice = data.average_price || (uniquePrices.reduce((a, b) => a + b, 0) / uniquePrices.length);
            const minPrice = data.min_price || Math.min(...uniquePrices);
            const maxPrice = data.max_price || Math.max(...uniquePrices);
            const originalCount = data.original_count || uniquePrices.length;
            const filteredCount = data.total_results || uniquePrices.length;
            
            let html = `
                <div class="search-results">
                    <h6><i class="fas fa-chart-bar me-2"></i>Résultats pour "${query}"</h6>
                    <small class="text-muted"><i class="fas fa-search me-1"></i>Requête exacte: "${query}"</small>
                    ${data.debug ? `
                        <br><small class="text-info">
                            <i class="fas fa-info-circle me-1"></i>
                            Google API: ${data.debug.google_success ? '✅ Fonctionne' : '❌ Échec'} | 
                            Résultats: ${data.debug.google_results_count || 0} | 
                            ${data.debug.using_fallback ? '🔄 Prix simulés utilisés' : '🌐 Prix Google réels'}
                        </small>
                        ${data.debug.debug_url ? `<br><small class="text-muted">URL: ${data.debug.debug_url}</small>` : ''}
                        ${data.debug.debug_response ? `<br><small class="text-muted">Erreur: ${data.debug.debug_response}</small>` : ''}
                    ` : ''}
                    ${originalCount !== filteredCount ? `<br><small class="text-info"><i class="fas fa-filter me-1"></i>${filteredCount} prix réalistes (filtrés sur ${originalCount} trouvés)</small>` : ''}
                    
                    <div class="mt-3 mb-3">
                        <button type="button" class="btn btn-info btn-sm" onclick="showSearchDetails(${JSON.stringify(data).replace(/"/g, '&quot;')})">
                            <i class="fas fa-eye me-1"></i>Voir les résultats
                        </button>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="text-center">
                                <small class="text-muted">Prix Minimum</small>
                                <div class="h5 text-success">${minPrice.toFixed(2)} €</div>
                                <small class="text-muted">Meilleur prix</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <small class="text-muted">Prix Moyen</small>
                                <div class="h5 text-primary">${avgPrice.toFixed(2)} €</div>
                                <small class="text-muted">Prix filtré</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <small class="text-muted">Prix Maximum</small>
                                <div class="h5 text-danger">${maxPrice.toFixed(2)} €</div>
                                <small class="text-muted">Prix le plus élevé</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Prix réalistes trouvés :</small><br>
            `;
            
            uniquePrices.forEach(price => {
                html += `<span class="price-tag">${price.toFixed(2)} €</span>`;
            });
            
            html += `
                    </div>
                    ${data.fallback ? '<div class="alert alert-warning alert-sm"><i class="fas fa-info-circle me-1"></i>Prix simulés (API Google non disponible)</div>' : ''}
                    
                    <div class="d-grid">
                        <button class="btn btn-outline-primary" onclick="usePriceInCalculator(${avgPrice.toFixed(2)})">
                            <i class="fas fa-arrow-right me-2"></i>Utiliser le prix moyen (${avgPrice.toFixed(2)} €) dans le calculateur
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('searchResults').innerHTML = html;
        }
        
        // Fonction pour utiliser un prix dans le calculateur
        function usePriceInCalculator(price) {
            document.getElementById('costPrice').value = price;
            
            // Basculer vers l'onglet calculateur
            const calculatorTab = new bootstrap.Tab(document.getElementById('calculator-tab'));
            calculatorTab.show();
            
            // Faire défiler vers le formulaire
            document.getElementById('costPrice').scrollIntoView({ behavior: 'smooth' });
            document.getElementById('costPrice').focus();
        }
        
        // Auto-save des paramètres (optionnel)
        const settingsInputs = document.querySelectorAll('#settings input');
        settingsInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Mettre à jour la configuration locale
                switch(this.name) {
                    case 'margin_min':
                        config.marginMin = parseFloat(this.value);
                        break;
                    case 'margin_max':
                        config.marginMax = parseFloat(this.value);
                        break;
                    case 'difficulty_easy':
                        config.difficultyEasy = parseFloat(this.value);
                        break;
                    case 'difficulty_medium':
                        config.difficultyMedium = parseFloat(this.value);
                        break;
                    case 'difficulty_hard':
                        config.difficultyHard = parseFloat(this.value);
                        break;
                    case 'time_rate':
                        config.timeRate = parseFloat(this.value);
                        break;
                }
            });
        });
    </script>

    <!-- Modal pour afficher les détails de recherche -->
    <div class="modal fade" id="searchDetailsModal" tabindex="-1" aria-labelledby="searchDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchDetailsModalLabel">
                        <i class="fas fa-search me-2"></i>Détails de la recherche
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="searchDetailsBody">
                    <!-- Le contenu sera rempli dynamiquement -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
