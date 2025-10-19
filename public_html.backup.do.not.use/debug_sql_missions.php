<?php
// Diagnostic SQL direct pour les missions
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnostic SQL Missions</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;}</style>";

// Simuler session utilisateur
$_SESSION['shop_id'] = 'mkmkmk';
$_SESSION['user_id'] = 6;
$_SERVER['HTTP_HOST'] = 'mkmkmk.mdgeek.top';

// Inclure les fichiers de config
require_once __DIR__ . '/config/database.php';

try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception("Impossible de se connecter à la base de données");
    }
    
    $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
    $db_info = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p class='success'>✅ Connecté à : " . $db_info['db_name'] . "</p>";
    
    $user_id = 6; // Utilisateur test
    
    echo "<h2>📊 Données de base</h2>";
    
    // 1. Toutes les missions actives
    echo "<h3>1. Missions actives dans la DB</h3>";
    $stmt = $shop_pdo->query("SELECT id, titre, statut, recompense_euros, nombre_taches FROM missions WHERE statut = 'active'");
    $missions_actives = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($missions_actives)) {
        echo "<table><tr><th>ID</th><th>Titre</th><th>Statut</th><th>Récompense</th><th>Nombre Tâches</th></tr>";
        foreach ($missions_actives as $mission) {
            echo "<tr><td>{$mission['id']}</td><td>{$mission['titre']}</td><td>{$mission['statut']}</td><td>{$mission['recompense_euros']}€</td><td>{$mission['nombre_taches']}</td></tr>";
        }
        echo "</table>";
        echo "<p class='info'>📈 Total : " . count($missions_actives) . " missions actives</p>";
    } else {
        echo "<p class='error'>❌ Aucune mission active trouvée</p>";
    }
    
    // 2. Missions de l'utilisateur
    echo "<h3>2. Missions de l'utilisateur 6</h3>";
    $stmt = $shop_pdo->prepare("SELECT um.id, um.mission_id, um.statut, m.titre FROM user_missions um JOIN missions m ON um.mission_id = m.id WHERE um.user_id = ?");
    $stmt->execute([$user_id]);
    $user_missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($user_missions)) {
        echo "<table><tr><th>ID User Mission</th><th>Mission ID</th><th>Titre</th><th>Statut</th></tr>";
        foreach ($user_missions as $um) {
            echo "<tr><td>{$um['id']}</td><td>{$um['mission_id']}</td><td>{$um['titre']}</td><td>{$um['statut']}</td></tr>";
        }
        echo "</table>";
        echo "<p class='info'>📈 Total : " . count($user_missions) . " missions utilisateur</p>";
    } else {
        echo "<p class='info'>ℹ️ Aucune mission pour l'utilisateur 6</p>";
    }
    
    // 3. Test requête missions disponibles EXACTE
    echo "<h3>3. Test requête missions disponibles (exacte du code)</h3>";
    $stmt = $shop_pdo->prepare("
        SELECT
            m.id,
            m.titre,
            m.description,
            mt.nom AS type_nom,
            mt.couleur,
            m.recompense_euros,
            m.recompense_points,
            m.nombre_taches
        FROM missions m
        LEFT JOIN mission_types mt ON m.type_id = mt.id
        WHERE m.statut = 'active'
          AND (m.date_fin IS NULL OR m.date_fin >= CURDATE())
          AND m.id NOT IN (
            SELECT mission_id FROM user_missions WHERE user_id = ?
          )
        ORDER BY m.created_at DESC
    ");
    
    try {
        $stmt->execute([$user_id]);
        $missions_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='success'>✅ Requête exécutée avec succès</p>";
        echo "<p class='info'>📊 Nombre de résultats : " . count($missions_disponibles) . "</p>";
        
        if (!empty($missions_disponibles)) {
            echo "<table><tr><th>ID</th><th>Titre</th><th>Type</th><th>Récompense</th><th>Tâches</th></tr>";
            foreach ($missions_disponibles as $mission) {
                echo "<tr>";
                echo "<td>{$mission['id']}</td>";
                echo "<td>{$mission['titre']}</td>";
                echo "<td>" . ($mission['type_nom'] ?? 'NULL') . "</td>";
                echo "<td>{$mission['recompense_euros']}€</td>";
                echo "<td>{$mission['nombre_taches']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ Aucune mission disponible retournée par la requête</p>";
            
            // Debug step by step
            echo "<h4>🔍 Debug étape par étape :</h4>";
            
            // Missions actives
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM missions WHERE statut = 'active'");
            $count = $stmt->fetch()['count'];
            echo "<p>- Missions avec statut='active' : $count</p>";
            
            // Missions avec date_fin OK
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM missions WHERE statut = 'active' AND (date_fin IS NULL OR date_fin >= CURDATE())");
            $count = $stmt->fetch()['count'];
            echo "<p>- Missions actives avec date_fin OK : $count</p>";
            
            // Missions exclues par NOT IN
            $stmt = $shop_pdo->prepare("SELECT mission_id FROM user_missions WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $excluded = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>- Missions exclues (déjà prises par user $user_id) : " . implode(', ', $excluded) . "</p>";
            
            // Missions restantes
            $excluded_list = empty($excluded) ? '0' : implode(',', $excluded);
            $stmt = $shop_pdo->query("SELECT id, titre FROM missions WHERE statut = 'active' AND (date_fin IS NULL OR date_fin >= CURDATE()) AND id NOT IN ($excluded_list)");
            $remaining = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p>- Missions qui devraient être disponibles :</p>";
            if (!empty($remaining)) {
                echo "<ul>";
                foreach ($remaining as $m) {
                    echo "<li>ID {$m['id']}: {$m['titre']}</li>";
                }
                echo "</ul>";
            } else {
                echo "<p class='error'>Aucune !</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erreur SQL : " . $e->getMessage() . "</p>";
    }
    
    // 4. Vérifier structure table mission_types
    echo "<h3>4. Structure table mission_types</h3>";
    try {
        $stmt = $shop_pdo->query("DESCRIBE mission_types");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table>";
        
        $stmt = $shop_pdo->query("SELECT * FROM mission_types LIMIT 3");
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Exemples de types :</p>";
        echo "<pre>" . print_r($types, true) . "</pre>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erreur mission_types : " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur générale : " . $e->getMessage() . "</p>";
}

echo "<hr><p><em>Diagnostic terminé - " . date('Y-m-d H:i:s') . "</em></p>";
?>
