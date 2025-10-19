<?php
// Test simple pour vérifier la récupération des missions
session_start();

// Forcer session si pas définie
if (!isset($_SESSION['user_id'])) {
    $_SESSION['shop_id'] = 'mkmkmk';
    $_SESSION['user_id'] = 6;
    $_SESSION['full_name'] = 'Administrateur Mkmkmk';
    $_SESSION['role'] = 'admin';
}

$_SERVER['HTTP_HOST'] = 'mkmkmk.mdgeek.top';

echo "<h1>🧪 Test Simple Missions</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;}</style>";

try {
    require_once __DIR__ . '/config/database.php';
    
    $user_id = $_SESSION['user_id'];
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception("Connexion base de données impossible");
    }
    
    echo "<p class='success'>✅ Connecté en tant qu'utilisateur: $user_id</p>";
    
    // Test requête missions disponibles EXACTE du fichier mes_missions_harmonieux.php
    echo "<h2>📋 Test missions disponibles</h2>";
    
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
    
    $stmt->execute([$user_id]);
    $missions_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>🔢 Nombre de missions trouvées: " . count($missions_disponibles) . "</p>";
    
    if (!empty($missions_disponibles)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Titre</th><th>Description</th><th>Type</th><th>Récompense</th><th>Tâches</th></tr>";
        
        foreach ($missions_disponibles as $mission) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($mission['id']) . "</td>";
            echo "<td>" . htmlspecialchars($mission['titre']) . "</td>";
            echo "<td>" . htmlspecialchars($mission['description'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($mission['type_nom'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($mission['recompense_euros']) . "€</td>";
            echo "<td>" . htmlspecialchars($mission['nombre_taches']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<h3>✅ La requête SQL fonctionne parfaitement !</h3>";
        echo "<p class='success'>Le problème n'est PAS dans les données ou la requête SQL.</p>";
        echo "<p class='info'>Le problème est probablement dans l'affichage ou la logique du fichier mes_missions_harmonieux.php</p>";
        
    } else {
        echo "<p class='error'>❌ Aucune mission trouvée</p>";
        
        // Debug
        echo "<h3>🔍 Debug:</h3>";
        
        $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM missions WHERE statut = 'active'");
        $count = $stmt->fetch()['count'];
        echo "<p>Missions actives: $count</p>";
        
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) as count FROM user_missions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $count = $stmt->fetch()['count'];
        echo "<p>Missions de l'utilisateur $user_id: $count</p>";
    }
    
    // Test missions en cours
    echo "<h2>🔄 Test missions en cours</h2>";
    
    $stmt = $shop_pdo->prepare("
        SELECT
            um.id,
            m.titre,
            m.description,
            mt.nom AS type_nom,
            mt.couleur,
            m.recompense_euros,
            m.recompense_points,
            m.nombre_taches,
            um.date_rejointe as date_acceptation,
            um.progres as progression
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        LEFT JOIN mission_types mt ON m.type_id = mt.id
        WHERE um.user_id = ? AND um.statut = 'en_cours'
        ORDER BY um.date_rejointe DESC
    ");
    
    $stmt->execute([$user_id]);
    $missions_en_cours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>🔢 Missions en cours: " . count($missions_en_cours) . "</p>";
    
    if (!empty($missions_en_cours)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Titre</th><th>Progression</th><th>Statut</th></tr>";
        foreach ($missions_en_cours as $mission) {
            echo "<tr>";
            echo "<td>" . $mission['id'] . "</td>";
            echo "<td>" . htmlspecialchars($mission['titre']) . "</td>";
            echo "<td>" . ($mission['progression'] ?? 0) . "/" . $mission['nombre_taches'] . "</td>";
            echo "<td>en_cours</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php?page=mes_missions'>🔗 Retour à la page missions normale</a></p>";
echo "<p><em>Test terminé - " . date('Y-m-d H:i:s') . "</em></p>";
?>
