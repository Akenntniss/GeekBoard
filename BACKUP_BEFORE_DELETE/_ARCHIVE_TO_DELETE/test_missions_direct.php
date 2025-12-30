<?php
// Test direct des missions sans authentification pour diagnostic
session_start();

// Simuler une session utilisateur pour le test
$_SESSION['shop_id'] = 'mkmkmk';
$_SESSION['user_id'] = 6;
$_SESSION['full_name'] = 'Test User';
$_SERVER['HTTP_HOST'] = 'mkmkmk.mdgeek.top';

// Définir le chemin de base
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/public_html');
}

// Inclure les fichiers de configuration
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Récupération des informations utilisateur
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? $_SESSION['name'] ?? 'Utilisateur';
$shop_id = $_SESSION['shop_id'];

// Connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Variables par défaut
$cagnotte_utilisateur = 0.00;
$xp_utilisateur = 0;
$gains_historiques = [];
$total_gains_euros = 0.0;
$total_gains_points = 0;

try {
    $stmt = $shop_pdo->prepare("SELECT cagnotte, COALESCE(xp_total, 0) as xp FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $cagnotte_utilisateur = $result['cagnotte'];
        $xp_utilisateur = $result['xp'];
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération de la cagnotte: " . $e->getMessage());
}

// Récupération des missions par catégorie
try {
    // Missions disponibles
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
    
    // Missions en cours
    $stmt = $shop_pdo->prepare("
        SELECT
            um.id,
            m.titre,
            m.description,
            mt.nom as type_nom,
            mt.couleur,
            m.recompense_euros,
            m.recompense_points,
            m.objectif_nombre as nombre_taches,
            COALESCE(mv.taches_validees, 0) as taches_validees,
            COALESCE(mv.taches_approuvees, 0) as taches_approuvees,
            um.date_inscription as date_acceptation,
            um.progression_actuelle as progression
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        LEFT JOIN mission_types mt ON m.mission_type_id = mt.id
        LEFT JOIN (
            SELECT
                user_mission_id,
                COUNT(*) as taches_validees,
                SUM(CASE WHEN validee = 1 THEN 1 ELSE 0 END) as taches_approuvees
            FROM mission_validations
            GROUP BY user_mission_id
        ) mv ON um.id = mv.user_mission_id
        WHERE um.user_id = ? AND um.statut = 'en_cours'
        ORDER BY um.date_inscription DESC
    ");
    $stmt->execute([$user_id]);
    $missions_en_cours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Missions complétées
    $stmt = $shop_pdo->prepare("
        SELECT
            um.id,
            m.titre,
            m.description,
            mt.nom as type_nom,
            mt.couleur,
            m.recompense_euros,
            m.recompense_points,
            m.objectif_nombre as nombre_taches,
            um.date_completion
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        LEFT JOIN mission_types mt ON m.mission_type_id = mt.id
        WHERE um.user_id = ? AND um.statut = 'complete'
        ORDER BY um.date_completion DESC
    ");
    $stmt->execute([$user_id]);
    $missions_completees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques
    $total_missions_actives = count($missions_en_cours);
    $total_missions_disponibles = count($missions_disponibles);
    $total_missions_completees = count($missions_completees);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des missions: " . $e->getMessage());
    $missions_en_cours = [];
    $missions_disponibles = [];
    $missions_completees = [];
    $total_missions_actives = 0;
    $total_missions_disponibles = 0;
    $total_missions_completees = 0;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Missions Direct</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; } .error { color: red; } .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .mission-card { border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🧪 Test Direct des Missions</h1>
    
    <div class="info">
        <h2>📊 Statistiques :</h2>
        <ul>
            <li><strong>Missions disponibles :</strong> <?= $total_missions_disponibles ?></li>
            <li><strong>Missions en cours :</strong> <?= $total_missions_actives ?></li>
            <li><strong>Missions complétées :</strong> <?= $total_missions_completees ?></li>
            <li><strong>Utilisateur :</strong> <?= $user_name ?> (ID: <?= $user_id ?>)</li>
            <li><strong>Magasin :</strong> <?= $shop_id ?></li>
        </ul>
    </div>

    <h2>🌟 Missions Disponibles (<?= count($missions_disponibles) ?>)</h2>
    <?php if (empty($missions_disponibles)): ?>
        <p class="error">❌ Aucune mission disponible trouvée</p>
        
        <!-- Debug : vérifier toutes les missions actives -->
        <?php
        try {
            $stmt = $shop_pdo->query("SELECT id, titre, statut, mission_type_id FROM missions WHERE statut = 'active'");
            $all_active = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>🔍 Debug - Toutes les missions actives dans la DB :</h3>";
            if (!empty($all_active)) {
                echo "<table><tr><th>ID</th><th>Titre</th><th>Statut</th><th>Type ID</th></tr>";
                foreach ($all_active as $mission) {
                    echo "<tr><td>{$mission['id']}</td><td>{$mission['titre']}</td><td>{$mission['statut']}</td><td>{$mission['mission_type_id']}</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p>Aucune mission active trouvée</p>";
            }
            
            // Vérifier les missions déjà prises
            $stmt = $shop_pdo->prepare("SELECT mission_id FROM user_missions WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user_missions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<h3>🔍 Missions déjà prises par l'utilisateur {$user_id} :</h3>";
            echo "<p>" . (empty($user_missions) ? "Aucune" : implode(', ', $user_missions)) . "</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>Erreur debug : " . $e->getMessage() . "</p>";
        }
        ?>
        
    <?php else: ?>
        <?php foreach ($missions_disponibles as $mission): ?>
            <div class="mission-card">
                <h3><?= htmlspecialchars($mission['titre']) ?></h3>
                <p><strong>Description :</strong> <?= htmlspecialchars($mission['description']) ?></p>
                <p><strong>Type :</strong> <?= htmlspecialchars($mission['type_nom'] ?? 'Non défini') ?></p>
                <p><strong>Récompense :</strong> <?= $mission['recompense_euros'] ?>€ + <?= $mission['recompense_points'] ?> points</p>
                <p><strong>Tâches :</strong> <?= $mission['nombre_taches'] ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <hr>
    <p><em>Test généré le <?= date('Y-m-d H:i:s') ?></em></p>
    <p><a href="https://mkmkmk.mdgeek.top/index.php?page=mes_missions">🔗 Retour à la page normale</a></p>
</body>
</html>
