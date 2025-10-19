<?php
// Test de génération de numéros de devis
require_once __DIR__ . '/config/database.php';

session_start();
initializeShopSession();

echo "<h1>Test Génération Numéros de Devis</h1>\n";
echo "<pre>\n";

try {
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception("Impossible de se connecter à la base de données du magasin");
    }
    
    // Récupérer la base de données actuelle
    $db_name = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "Base de données active: $db_name\n\n";
    
    echo "=== Numéros de devis existants ===\n";
    $stmt = $pdo->query("SELECT numero_devis, titre FROM devis ORDER BY numero_devis");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['numero_devis'] . " : " . $row['titre'] . "\n";
    }
    
    echo "\n=== Test de génération ===\n";
    
    // Test de la logique de génération (même code que dans creer_devis_clean.php)
    $year = date('Y');
    $max_attempts = 10;
    $attempt = 0;
    $numero_devis = '';
    
    do {
        $attempt++;
        $random_suffix = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $numero_devis = "DV-$year-$random_suffix";
        
        // Vérifier si ce numéro existe déjà
        $check_query = "SELECT COUNT(*) FROM devis WHERE numero_devis = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$numero_devis]);
        $exists = $check_stmt->fetchColumn();
        
        echo "Tentative $attempt: $numero_devis - " . ($exists ? "EXISTE DÉJÀ" : "DISPONIBLE") . "\n";
        
        if (!$exists) {
            break;
        }
        
    } while ($attempt < $max_attempts);
    
    if ($attempt >= $max_attempts) {
        echo "❌ ERREUR: Impossible de générer un numéro unique après $max_attempts tentatives\n";
    } else {
        echo "✅ Numéro unique généré: $numero_devis (en $attempt tentative(s))\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
echo "<p><a href='javascript:history.back()'>← Retour</a></p>\n";
?>
