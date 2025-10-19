<?php
// Test spécifique pour les missions en cours
session_start();

// Forcer les sessions
$_SESSION["shop_id"] = "mkmkmk";
$_SESSION["user_id"] = 6;
$_SESSION["user_role"] = "admin";

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/.');
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

$shop_pdo = getShopDBConnection();
$user_id = 6;

echo "<h1>Debug missions en cours</h1>";

// D'abord, vérifions les user_missions
echo "<h2>1. User missions pour user_id = $user_id</h2>";
try {
    $stmt = $shop_pdo->prepare("SELECT * FROM user_missions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($user_missions as $um) {
        echo "<p>ID: " . $um['id'] . ", Mission ID: " . $um['mission_id'] . ", Statut: " . $um['statut'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}

// Ensuite, testons la requête complète étape par étape
echo "<h2>2. Test JOIN user_missions avec missions</h2>";
try {
    $stmt = $shop_pdo->prepare("
        SELECT um.id, um.statut, m.titre, m.id as mission_id
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        WHERE um.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Nombre de résultats: " . count($results) . "</p>";
    foreach ($results as $result) {
        echo "<p>Mission: " . htmlspecialchars($result['titre']) . ", Statut: " . $result['statut'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}

// Testons avec mission_types
echo "<h2>3. Test avec mission_types</h2>";
try {
    $stmt = $shop_pdo->prepare("
        SELECT um.id, um.statut, m.titre, mt.nom as type_nom
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.type_id = mt.id
        WHERE um.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Nombre de résultats avec types: " . count($results) . "</p>";
    foreach ($results as $result) {
        echo "<p>Mission: " . htmlspecialchars($result['titre']) . ", Type: " . htmlspecialchars($result['type_nom']) . ", Statut: " . $result['statut'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}

// Testons la requête complète avec filtre en_cours
echo "<h2>4. Test avec filtre en_cours</h2>";
try {
    $stmt = $shop_pdo->prepare("
        SELECT um.id, m.titre, mt.nom as type_nom, um.statut
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.type_id = mt.id
        WHERE um.user_id = ? AND um.statut = 'en_cours'
    ");
    $stmt->execute([$user_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Nombre de missions en cours: " . count($results) . "</p>";
    foreach ($results as $result) {
        echo "<p>Mission: " . htmlspecialchars($result['titre']) . ", Type: " . htmlspecialchars($result['type_nom']) . ", Statut: " . $result['statut'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}

// Testons exactement la requête du fichier mes_missions.php
echo "<h2>5. Test requête exacte de mes_missions.php</h2>";
try {
    $stmt = $shop_pdo->prepare("
        SELECT um.id, m.titre, m.description, mt.nom as type_nom, mt.couleur, 
               m.recompense_euros, m.recompense_points, m.objectif_quantite as nombre_taches,
               COALESCE(mv.taches_validees, 0) as taches_validees,
               COALESCE(mv.taches_approuvees, 0) as taches_approuvees,
               um.date_rejointe as date_acceptation
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.type_id = mt.id
        LEFT JOIN (
            SELECT user_mission_id, 
                   COUNT(*) as taches_validees,
                   SUM(CASE WHEN validee = 1 THEN 1 ELSE 0 END) as taches_approuvees
            FROM mission_validations
            GROUP BY user_mission_id
        ) mv ON um.id = mv.user_mission_id
        WHERE um.user_id = ? AND um.statut = 'en_cours'
        ORDER BY um.date_rejointe DESC
    ");
    $stmt->execute([$user_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Requête complète - Nombre de missions en cours: " . count($results) . "</p>";
    foreach ($results as $result) {
        echo "<p>Mission: " . htmlspecialchars($result['titre']) . ", Type: " . htmlspecialchars($result['type_nom']) . ", Récompense: " . $result['recompense_euros'] . "€</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur requête complète: " . $e->getMessage() . "</p>";
}

echo "<br><a href='mes_missions.php'>Retour aux missions</a>";
?> 