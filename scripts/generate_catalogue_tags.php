<?php
/**
 * Script de génération des tags de recherche via IA Groq
 * Génère des mots-clés optimisés pour chaque produit du catalogue
 * 
 * Usage: php scripts/generate_catalogue_tags.php [--limit=100] [--offset=0]
 */

// Configuration
require_once __DIR__ . '/../config/database.php';

// Paramètres Groq
$GROQ_API_KEY = "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";
$GROQ_ENDPOINT = "https://api.groq.com/openai/v1/chat/completions";
$GROQ_MODEL = "llama-3.1-8b-instant"; // Plus rapide et moins cher pour cette tâche

// Parse arguments
$options = getopt('', ['limit::', 'offset::', 'batch::']);
$limit = isset($options['limit']) ? intval($options['limit']) : 100;
$offset = isset($options['offset']) ? intval($options['offset']) : 0;
$batchSize = isset($options['batch']) ? intval($options['batch']) : 20; // Produits par appel API

echo "=== Génération des tags de recherche IA ===\n";
echo "Limit: $limit | Offset: $offset | Batch: $batchSize\n\n";

try {
    // Connexion directe à la base de données
    $pdo = new PDO(
        "mysql:host=82.29.168.205;dbname=geekboard_mdg;charset=utf8mb4",
        "gb_mdg",
        "Admin123!",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Compter les produits sans tags
    $stmt = $pdo->query("SELECT COUNT(*) FROM catalogue_fournisseur WHERE search_tags IS NULL OR search_tags = ''");
    $totalWithoutTags = $stmt->fetchColumn();
    echo "Produits sans tags: $totalWithoutTags\n\n";
    
    // Récupérer les produits
    $stmt = $pdo->prepare("
        SELECT id, name, brand, device_type, model, type, reference 
        FROM catalogue_fournisseur 
        WHERE search_tags IS NULL OR search_tags = ''
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Produits récupérés: " . count($products) . "\n\n";
    
    // Traiter par batch
    $batches = array_chunk($products, $batchSize);
    $updated = 0;
    
    foreach ($batches as $batchIndex => $batch) {
        echo "Batch " . ($batchIndex + 1) . "/" . count($batches) . "...\n";
        
        // Construire le prompt pour le batch
        $productList = "";
        foreach ($batch as $i => $p) {
            $productList .= "ID:{$p['id']}|{$p['name']}|{$p['brand']}|{$p['model']}|{$p['type']}|{$p['reference']}\n";
        }
        
        $prompt = "Tu es un expert en pièces détachées de téléphones et tablettes.
Pour chaque produit ci-dessous, génère des TAGS DE RECHERCHE optimisés.

RÈGLES:
1. Inclure les mots du nom, marque, modèle, type
2. Ajouter les SYNONYMES courants (écran=lcd=display=afficheur, batterie=accu)
3. Ajouter les VARIANTES de noms de modèles (Redmi Note 11 → redmi note11 note 11)
4. Corriger les fautes courantes dans les tags (samsng=samsung)
5. Tout en MINUSCULES sans accents
6. Pas de ponctuation, juste des espaces

PRODUITS:
$productList

RÉPONSE (une ligne par produit, format: ID:tags):
";

        $response = callGroqAPI($prompt, $GROQ_API_KEY, $GROQ_ENDPOINT, $GROQ_MODEL);
        
        if ($response['success']) {
            // Parser la réponse
            $lines = explode("\n", trim($response['content']));
            foreach ($lines as $line) {
                if (preg_match('/^(\d+):(.+)$/', trim($line), $matches)) {
                    $productId = intval($matches[1]);
                    $tags = trim($matches[2]);
                    
                    // Mettre à jour en BDD
                    $updateStmt = $pdo->prepare("UPDATE catalogue_fournisseur SET search_tags = ? WHERE id = ?");
                    $updateStmt->execute([$tags, $productId]);
                    $updated++;
                }
            }
            echo "  → $updated produits mis à jour\n";
        } else {
            echo "  ⚠ Erreur API: " . $response['error'] . "\n";
        }
        
        // Pause pour éviter le rate limiting
        usleep(500000); // 0.5s
    }
    
    echo "\n=== Terminé ===\n";
    echo "Total mis à jour: $updated\n";
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Appelle l'API Groq
 */
function callGroqAPI($prompt, $apiKey, $endpoint, $model) {
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.2,
        'max_tokens' => 2000
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return ['success' => false, 'error' => $curlError];
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => "HTTP $httpCode: $response"];
    }
    
    $result = json_decode($response, true);
    if (!isset($result['choices'][0]['message']['content'])) {
        return ['success' => false, 'error' => 'Réponse invalide'];
    }
    
    return ['success' => true, 'content' => $result['choices'][0]['message']['content']];
}
