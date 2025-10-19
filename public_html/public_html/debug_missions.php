<?php
// Script de débogage des requêtes missions
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

$user_id = 6;

echo "<h1>Débogage des requêtes missions</h1>";
echo "<p>User ID: $user_id</p>";

// 1. Vérifier les données brutes
echo "<h2>1. Données brutes</h2>";

echo "<h3>Missions :</h3>";
try {
    $stmt = $shop_pdo->query("SELECT * FROM missions");
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($missions as $mission) {
        echo "<p>Mission ID: " . $mission['id'] . " - Titre: " . htmlspecialchars($mission['titre']) . " - Statut: " . $mission['statut'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur missions: " . $e->getMessage() . "</p>";
}

echo "<h3>User missions :</h3>";
try {
    $stmt = $shop_pdo->query("SELECT * FROM user_missions WHERE user_id = $user_id");
    $user_missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($user_missions as $um) {
        echo "<p>User Mission ID: " . $um['id'] . " - Mission ID: " . $um['mission_id'] . " - Statut: " . $um['statut'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur user_missions: " . $e->getMessage() . "</p>";
}

// 2. Tester les requêtes individuellement
echo "<h2>2. Test des requêtes</h2>";

// Requête missions disponibles simplifiée
echo "<h3>Missions disponibles (requête simplifiée) :</h3>";
try {
    $stmt = $shop_pdo->prepare("SELECT * FROM missions WHERE statut = 'active'");
    $stmt->execute();
    $missions_simple = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Nombre de missions actives: " . count($missions_simple) . "</p>";
    foreach ($missions_simple as $mission) {
        echo "<p>- " . htmlspecialchars($mission['titre']) . " (statut: " . $mission['statut'] . ")</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur requête simple: " . $e->getMessage() . "</p>";
}

// Requête avec JOIN
echo "<h3>Missions avec JOIN :</h3>";
try {
    $stmt = $shop_pdo->prepare("
        SELECT m.id, m.titre, m.statut, mt.nom as type_nom 
        FROM missions m 
        JOIN mission_types mt ON m.mission_type_id = mt.id 
        WHERE m.statut = 'active'
    ");
    $stmt->execute();
    $missions_join = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Nombre de missions avec JOIN: " . count($missions_join) . "</p>";
    foreach ($missions_join as $mission) {
        echo "<p>- " . htmlspecialchars($mission['titre']) . " (" . htmlspecialchars($mission['type_nom']) . ")</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur requête JOIN: " . $e->getMessage() . "</p>";
}

// Requête complète missions disponibles
echo "<h3>Requête complète missions disponibles :</h3>";
try {
    $stmt = $shop_pdo->prepare("
        SELECT m.id, m.titre, m.description, mt.nom as type_nom, mt.couleur,
               m.recompense_euros, m.recompense_points, m.objectif_nombre as nombre_taches
        FROM missions m
        JOIN mission_types mt ON m.mission_type_id = mt.id
        WHERE m.statut = 'active'
        AND m.id NOT IN (
            SELECT mission_id FROM user_missions WHERE user_id = ?
        )
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $missions_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Nombre de missions disponibles: " . count($missions_disponibles) . "</p>";
    foreach ($missions_disponibles as $mission) {
        echo "<p>- " . htmlspecialchars($mission['titre']) . " (" . $mission['recompense_euros'] . "€)</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur requête disponibles: " . $e->getMessage() . "</p>";
}

// 3. Vérifier les colonnes qui posent problème
echo "<h2>3. Vérification des colonnes</h2>";

echo "<h3>Structure table missions :</h3>";
try {
    $stmt = $shop_pdo->query("DESCRIBE missions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "<p>- " . $column['Field'] . " (" . $column['Type'] . ")</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur structure missions: " . $e->getMessage() . "</p>";
}

echo "<h3>Structure table user_missions :</h3>";
try {
    $stmt = $shop_pdo->query("DESCRIBE user_missions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "<p>- " . $column['Field'] . " (" . $column['Type'] . ")</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur structure user_missions: " . $e->getMessage() . "</p>";
}

echo "<br><a href='mes_missions.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Retour aux missions</a>";
?> 