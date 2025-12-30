<?php
// Script de diagnostic pour vérifier l'authentification sur mes_missions
session_start();

// Forcer le contexte mkmkmk pour le test
$_SESSION['shop_id'] = 'mkmkmk';
$_SESSION['user_id'] = 6; // Utilisateur test
$_SESSION['user_role'] = 'admin';
$_SERVER['HTTP_HOST'] = 'mkmkmk.mdgeek.top';

echo "<h1>🔍 Diagnostic Authentification Missions</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Définir le chemin de base
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/public_html');
}

// Inclure les fichiers de configuration
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

echo "<h2>✅ Variables de session :</h2>";
echo "<ul>";
echo "<li><strong>shop_id:</strong> " . ($_SESSION['shop_id'] ?? 'NON DÉFINI') . "</li>";
echo "<li><strong>user_id:</strong> " . ($_SESSION['user_id'] ?? 'NON DÉFINI') . "</li>";
echo "<li><strong>HTTP_HOST:</strong> " . $_SERVER['HTTP_HOST'] . "</li>";
echo "</ul>";

echo "<h2>🔌 Test de connexion base de données :</h2>";
try {
    $shop_pdo = getShopDBConnection();
    if ($shop_pdo) {
        $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
        $db_info = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='success'>✅ Connexion réussie à la base : " . $db_info['db_name'] . "</p>";
        
        // Test des missions disponibles
        echo "<h2>📋 Test requête missions disponibles :</h2>";
        $user_id = $_SESSION['user_id'];
        $stmt = $shop_pdo->prepare("
            SELECT
                m.id,
                m.titre,
                m.description,
                mt.nom as type_nom,
                mt.couleur,
                m.recompense_euros,
                m.recompense_points,
                m.objectif_nombre as nombre_taches
            FROM missions m
            LEFT JOIN mission_types mt ON m.mission_type_id = mt.id
            WHERE m.statut = 'active'
              AND (m.date_fin IS NULL OR m.date_fin >= CURDATE())
              AND m.id NOT IN (
                SELECT mission_id FROM user_missions WHERE user_id = ?
              )
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $missions_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='info'>📊 Nombre de missions disponibles trouvées : <strong>" . count($missions_disponibles) . "</strong></p>";
        
        if (!empty($missions_disponibles)) {
            echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
            echo "<tr><th>ID</th><th>Titre</th><th>Récompense</th><th>Statut DB</th></tr>";
            foreach ($missions_disponibles as $mission) {
                echo "<tr>";
                echo "<td>" . $mission['id'] . "</td>";
                echo "<td>" . htmlspecialchars($mission['titre']) . "</td>";
                echo "<td>" . $mission['recompense_euros'] . "€</td>";
                echo "<td>Disponible</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ Aucune mission disponible trouvée</p>";
            
            // Debug : missions actives en général
            $stmt = $shop_pdo->query("SELECT id, titre, statut FROM missions WHERE statut = 'active'");
            $all_active = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p class='info'>🔍 Missions actives dans la DB : " . count($all_active) . "</p>";
            
            // Debug : missions déjà prises par l'utilisateur
            $stmt = $shop_pdo->prepare("SELECT mission_id FROM user_missions WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user_missions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<p class='info'>🔍 Missions déjà prises par l'utilisateur " . $user_id . " : " . implode(', ', $user_missions) . "</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Impossible de se connecter à la base de données</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur : " . $e->getMessage() . "</p>";
}

echo "<h2>🚀 Liens de test :</h2>";
echo "<ul>";
echo "<li><a href='https://mkmkmk.mdgeek.top/debug_missions_auth.php' target='_blank'>🔄 Relancer ce diagnostic</a></li>";
echo "<li><a href='https://mkmkmk.mdgeek.top/index.php?page=mes_missions' target='_blank'>📋 Accéder aux missions</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><em>Diagnostic terminé - " . date('Y-m-d H:i:s') . "</em></p>";
?>
