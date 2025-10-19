<?php
// Diagnostic des missions GeekBoard
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

echo "<h1>Diagnostic des tables missions</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2 { color: #333; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>";

// 1. Vérifier si les tables existent
echo "<h2>1. Vérification des tables missions</h2>";
$tables_missions = ['mission_types', 'missions', 'user_missions', 'mission_validations'];

foreach ($tables_missions as $table) {
    try {
        $stmt = $shop_pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Table $table existe</p>";
        } else {
            echo "<p class='error'>❌ Table $table n'existe pas</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erreur vérification table $table: " . $e->getMessage() . "</p>";
    }
}

// 2. Vérifier la structure des tables
echo "<h2>2. Structure des tables</h2>";

foreach ($tables_missions as $table) {
    try {
        $stmt = $shop_pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($columns)) {
            echo "<h3>Table: $table</h3>";
            echo "<table>";
            echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Default</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erreur structure table $table: " . $e->getMessage() . "</p>";
    }
}

// 3. Vérifier les données dans les tables
echo "<h2>3. Données dans les tables</h2>";

// Mission types
try {
    $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM mission_types");
    $count = $stmt->fetch()['count'];
    echo "<p>Mission types: <strong>$count</strong> enregistrements</p>";
    
    if ($count > 0) {
        $stmt = $shop_pdo->query("SELECT * FROM mission_types LIMIT 5");
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table>";
        echo "<tr><th>ID</th><th>Nom</th><th>Couleur</th><th>Icon</th></tr>";
        foreach ($types as $type) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($type['id']) . "</td>";
            echo "<td>" . htmlspecialchars($type['nom']) . "</td>";
            echo "<td>" . htmlspecialchars($type['couleur']) . "</td>";
            echo "<td>" . htmlspecialchars($type['icon']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur mission_types: " . $e->getMessage() . "</p>";
}

// Missions
try {
    $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM missions");
    $count = $stmt->fetch()['count'];
    echo "<p>Missions: <strong>$count</strong> enregistrements</p>";
    
    if ($count > 0) {
        $stmt = $shop_pdo->query("SELECT * FROM missions LIMIT 5");
        $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table>";
        echo "<tr><th>ID</th><th>Titre</th><th>Statut</th><th>Récompense €</th><th>Récompense Points</th></tr>";
        foreach ($missions as $mission) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($mission['id']) . "</td>";
            echo "<td>" . htmlspecialchars($mission['titre']) . "</td>";
            echo "<td>" . htmlspecialchars($mission['statut']) . "</td>";
            echo "<td>" . htmlspecialchars($mission['recompense_euros']) . "</td>";
            echo "<td>" . htmlspecialchars($mission['recompense_points']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur missions: " . $e->getMessage() . "</p>";
}

// User missions
try {
    $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM user_missions");
    $count = $stmt->fetch()['count'];
    echo "<p>User missions: <strong>$count</strong> enregistrements</p>";
    
    if ($count > 0) {
        $stmt = $shop_pdo->query("SELECT * FROM user_missions LIMIT 5");
        $user_missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table>";
        echo "<tr><th>ID</th><th>User ID</th><th>Mission ID</th><th>Statut</th><th>Progression</th></tr>";
        foreach ($user_missions as $um) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($um['id']) . "</td>";
            echo "<td>" . htmlspecialchars($um['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($um['mission_id']) . "</td>";
            echo "<td>" . htmlspecialchars($um['statut']) . "</td>";
            echo "<td>" . htmlspecialchars($um['progression_actuelle']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur user_missions: " . $e->getMessage() . "</p>";
}

// 4. Tester les requêtes de mes_missions.php
echo "<h2>4. Test des requêtes SQL</h2>";

$user_id = 6;

// Test missions en cours
echo "<h3>Missions en cours</h3>";
try {
    $stmt = $shop_pdo->prepare("
        SELECT um.id, m.titre, m.description, mt.nom as type_nom, mt.couleur, 
               m.recompense_euros, m.recompense_points, m.nombre_taches,
               COALESCE(mv.taches_validees, 0) as taches_validees,
               COALESCE(mv.taches_approuvees, 0) as taches_approuvees,
               um.date_acceptation
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.type_id = mt.id
        LEFT JOIN (
            SELECT user_mission_id, 
                   COUNT(*) as taches_validees,
                   SUM(CASE WHEN statut = 'approuve' THEN 1 ELSE 0 END) as taches_approuvees
            FROM mission_validations
            GROUP BY user_mission_id
        ) mv ON um.id = mv.user_mission_id
        WHERE um.user_id = ? AND um.statut = 'en_cours'
        ORDER BY um.date_acceptation DESC
    ");
    $stmt->execute([$user_id]);
    $missions_en_cours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✅ Requête missions en cours: " . count($missions_en_cours) . " résultats</p>";
    if (!empty($missions_en_cours)) {
        echo "<pre>" . print_r($missions_en_cours, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur requête missions en cours: " . $e->getMessage() . "</p>";
}

// Test missions disponibles
echo "<h3>Missions disponibles</h3>";
try {
    $stmt = $shop_pdo->prepare("
        SELECT m.id, m.titre, m.description, mt.nom as type_nom, mt.couleur,
               m.recompense_euros, m.recompense_points, m.nombre_taches
        FROM missions m
        JOIN mission_types mt ON m.type_id = mt.id
        WHERE m.actif = 1 AND m.statut = 'active'
        AND m.id NOT IN (
            SELECT mission_id FROM user_missions WHERE user_id = ?
        )
        ORDER BY m.priorite DESC, m.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $missions_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✅ Requête missions disponibles: " . count($missions_disponibles) . " résultats</p>";
    if (!empty($missions_disponibles)) {
        echo "<pre>" . print_r($missions_disponibles, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur requête missions disponibles: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Actions recommandées</h2>";
echo "<p>Consultez les erreurs ci-dessus pour identifier les problèmes avec les colonnes manquantes.</p>";
?> 