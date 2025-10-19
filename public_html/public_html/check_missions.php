<?php
// Script de vérification des tables missions
session_start();

// Forcer les sessions pour les tests
$_SESSION["shop_id"] = "mkmkmk";
$_SESSION["user_id"] = 6;
$_SESSION["user_role"] = "admin";

// Définir le chemin de base
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/.');
}

// Inclure les fichiers de configuration
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Connexion à la base de données
$shop_pdo = getShopDBConnection();

echo "<h1>Vérification des tables missions</h1>";

// Vérifier si les tables existent
$tables = ['mission_types', 'missions', 'user_missions', 'mission_validations'];
foreach ($tables as $table) {
    try {
        $stmt = $shop_pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Table $table existe</p>";
            
            // Compter les enregistrements
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "<p>   → $count enregistrements</p>";
            
        } else {
            echo "<p style='color: red;'>❌ Table $table n'existe pas</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur avec table $table: " . $e->getMessage() . "</p>";
    }
}

// Si les tables n'existent pas, proposer de les créer
if (!empty($missing_tables)) {
    echo "<h2>Créer les tables manquantes</h2>";
    echo "<p>Certaines tables sont manquantes. Voulez-vous les créer ?</p>";
    echo "<a href='install_missions_simple.php' style='padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 5px;'>Installer les tables missions</a>";
}

// Test simple de données
echo "<h2>Test des données</h2>";
try {
    $stmt = $shop_pdo->query("SELECT * FROM mission_types LIMIT 3");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Types de missions :</h3>";
    foreach ($types as $type) {
        echo "<p>- " . htmlspecialchars($type['nom']) . " (couleur: " . htmlspecialchars($type['couleur']) . ")</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur types: " . $e->getMessage() . "</p>";
}

try {
    $stmt = $shop_pdo->query("SELECT * FROM missions LIMIT 3");
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Missions :</h3>";
    foreach ($missions as $mission) {
        echo "<p>- " . htmlspecialchars($mission['titre']) . " (" . htmlspecialchars($mission['recompense_euros']) . "€)</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur missions: " . $e->getMessage() . "</p>";
}

echo "<br><a href='mes_missions.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Retour aux missions</a>";
?> 