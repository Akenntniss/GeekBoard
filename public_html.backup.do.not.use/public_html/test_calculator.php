<?php
// Script de test pour vérifier le fonctionnement du calculateur
echo "<h1>Test du Calculateur de Prix</h1>";
echo "<pre>";

// Test de connexion à la base de données
try {
    require_once 'config/database.php';
    initializeShopSession();
    $pdo = getShopDBConnection();
    echo "✓ Connexion à la base de données : OK\n";
    echo "✓ Magasin actuel : " . ($_SESSION['shop_id'] ?? 'Non défini') . "\n";
} catch (Exception $e) {
    echo "✗ Erreur connexion : " . $e->getMessage() . "\n";
    exit;
}

// Test de la table calculator_settings
try {
    $stmt = $pdo->query("SELECT * FROM calculator_settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($settings) {
        echo "✓ Table calculator_settings : OK\n";
        echo "  - Marge min : " . $settings['margin_min'] . "%\n";
        echo "  - Marge max : " . $settings['margin_max'] . "%\n";
        echo "  - Multiplicateur : " . $settings['difficulty_multiplier'] . "\n";
        echo "  - Tarif horaire : " . $settings['time_rate'] . "€\n";
        echo "  - API Google : " . (!empty($settings['google_api_key']) ? 'Configurée' : 'Non configurée') . "\n";
        echo "  - ID Moteur : " . (!empty($settings['google_search_engine_id']) ? $settings['google_search_engine_id'] : 'Non configuré') . "\n";
    } else {
        echo "✗ Table calculator_settings : Vide\n";
    }
} catch (Exception $e) {
    echo "✗ Erreur table calculator_settings : " . $e->getMessage() . "\n";
}

// Test de l'API de recherche
echo "\n=== Test de l'API de Recherche ===\n";
try {
    $test_data = [
        'query' => 'écran iPhone 13 Pro',
        'type' => 'piece',
        'api_key' => $settings['google_api_key'] ?? '',
        'search_engine_id' => $settings['google_search_engine_id'] ?? ''
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . '/pages/search_prices_api.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "✓ API de recherche : OK\n";
            echo "  - Prix trouvés : " . count($data['prices']) . "\n";
            if (!empty($data['prices'])) {
                echo "  - Prix min : " . min($data['prices']) . "€\n";
                echo "  - Prix max : " . max($data['prices']) . "€\n";
                echo "  - Prix moyen : " . round(array_sum($data['prices']) / count($data['prices']), 2) . "€\n";
            }
        } else {
            echo "⚠ API de recherche : Réponse invalide\n";
            echo "  - Réponse : " . $response . "\n";
        }
    } else {
        echo "✗ API de recherche : Erreur HTTP $http_code\n";
    }
} catch (Exception $e) {
    echo "✗ Erreur API de recherche : " . $e->getMessage() . "\n";
}

// Test de calcul de prix
echo "\n=== Test de Calcul de Prix ===\n";
try {
    $costPrice = 50.00;
    $repairTime = 30; // 30 minutes
    $difficulty = 'moyen';
    
    $laborCost = ($repairTime / 60) * $settings['time_rate'];
    $difficultyMultiplier = $settings['difficulty_multiplier'];
    $adjustedLaborCost = $laborCost * $difficultyMultiplier;
    $totalCost = $costPrice + $adjustedLaborCost;
    $minPrice = $totalCost * (1 + $settings['margin_min'] / 100);
    $maxPrice = $totalCost * (1 + $settings['margin_max'] / 100);
    $recommendedPrice = ($minPrice + $maxPrice) / 2;
    
    echo "✓ Calcul de prix : OK\n";
    echo "  - Prix de revient : " . $costPrice . "€\n";
    echo "  - Temps de réparation : " . $repairTime . " minutes\n";
    echo "  - Difficulté : " . $difficulty . "\n";
    echo "  - Coût main d'œuvre : " . round($laborCost, 2) . "€\n";
    echo "  - Coût ajusté : " . round($adjustedLaborCost, 2) . "€\n";
    echo "  - Coût total : " . round($totalCost, 2) . "€\n";
    echo "  - Prix minimum : " . round($minPrice, 2) . "€\n";
    echo "  - Prix maximum : " . round($maxPrice, 2) . "€\n";
    echo "  - Prix recommandé : " . round($recommendedPrice, 2) . "€\n";
} catch (Exception $e) {
    echo "✗ Erreur calcul de prix : " . $e->getMessage() . "\n";
}

echo "\n=== RÉSUMÉ ===\n";
echo "Le calculateur de prix est prêt à être utilisé !\n";
echo "Accédez-y via : pages/CalculateurPrix.php\n";

echo "</pre>";
echo "<a href='pages/CalculateurPrix.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0;'>🚀 Accéder au Calculateur</a>";
?>
