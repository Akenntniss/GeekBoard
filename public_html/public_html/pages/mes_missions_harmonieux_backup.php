<?php
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'mes_missions_harmonieux.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=mes_missions');
    exit();
}

// Inclure la configuration de session avant de démarrer la session
require_once __DIR__ . '/../config/session_config.php';

// Inclure la configuration pour la gestion des sous-domaines
require_once __DIR__ . '/../config/subdomain_config.php';

// Inclure les fichiers de configuration et de connexion à la base de données
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification de l'authentification GeekBoard
if (!isset($_SESSION['shop_id'])) {
    header('Location: /pages/login_auto.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login_auto.php');
    exit();
}

// TEST VISIBLE - Message de debug très visible
echo '<div style="position: fixed; top: 0; left: 0; width: 100%; background: #ff0000; color: white; padding: 10px; z-index: 9999; text-align: center; font-size: 18px; font-weight: bold;">🐛 FICHIER HARMONIEUX CHARGÉ - VERSION DEBUG</div>';

// DEBUG IMMÉDIAT - Affichage des informations de session
echo '<div style="position: fixed; top: 50px; left: 10px; background: #2196F3; color: white; padding: 15px; z-index: 9998; border-radius: 8px; max-width: 400px;">';
echo '<h4>🔍 DEBUG SESSION</h4>';
echo '<p><strong>User ID:</strong> ' . ($_SESSION['user_id'] ?? 'NULL') . '</p>';
echo '<p><strong>Shop ID:</strong> ' . ($_SESSION['shop_id'] ?? 'NULL') . '</p>';
echo '<p><strong>Username:</strong> ' . ($_SESSION['username'] ?? 'NULL') . '</p>';
echo '</div>';

// Récupération des informations utilisateur
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? $_SESSION['name'] ?? 'Utilisateur';
$shop_id = $_SESSION['shop_id'];

// DEBUG - Supprimer après correction
error_log("DEBUG MISSIONS: user_id = " . ($user_id ?? 'NULL') . ", shop_id = " . ($shop_id ?? 'NULL'));

// Connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// DEBUG - Vérification connexion et base
if ($shop_pdo) {
    $stmt_db = $shop_pdo->query("SELECT DATABASE() as db_name");
    $db_info = $stmt_db->fetch(PDO::FETCH_ASSOC);
    error_log("DEBUG DB: Connecté à " . $db_info['db_name'] . " pour user_id=" . $user_id . ", shop_id=" . $shop_id);
    
    // DEBUG VISIBLE DATABASE
    echo '<div style="position: fixed; top: 180px; left: 10px; background: #4CAF50; color: white; padding: 15px; z-index: 9997; border-radius: 8px; max-width: 400px;">';
    echo '<h4>✅ DEBUG DATABASE</h4>';
    echo '<p><strong>Base connectée:</strong> ' . htmlspecialchars($db_info['db_name']) . '</p>';
    echo '<p><strong>PDO Status:</strong> Connecté</p>';
    echo '</div>';
} else {
    error_log("DEBUG DB: ERREUR - Impossible de se connecter à la base de données");
    
    // DEBUG VISIBLE ERROR
    echo '<div style="position: fixed; top: 180px; left: 10px; background: #f44336; color: white; padding: 15px; z-index: 9997; border-radius: 8px; max-width: 400px;">';
    echo '<h4>❌ DEBUG DATABASE</h4>';
    echo '<p><strong>ERREUR:</strong> Impossible de se connecter à la base</p>';
    echo '</div>';
}

// Récupération de la cagnotte et des XP de l'utilisateur
$cagnotte_utilisateur = 0.00;
$xp_utilisateur = 0;
// Variables par défaut pour la cagnotte et l'historique
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

// Traitement des actions AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'accepter_mission') {
        $mission_id = (int) $_POST['mission_id'];
        
        try {
            // Vérifier si la mission n'est pas déjà prise
            $stmt = $shop_pdo->prepare("SELECT id FROM user_missions WHERE user_id = ? AND mission_id = ?");
            $stmt->execute([$user_id, $mission_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Mission déjà acceptée']);
                exit;
            }
            
            // Accepter la mission
            $stmt = $shop_pdo->prepare("
                INSERT INTO user_missions (user_id, mission_id, date_rejointe, statut, progres)
                VALUES (?, ?, NOW(), 'en_cours', 0)
            ");
            $stmt->execute([$user_id, $mission_id]);
            
            echo json_encode(['success' => true, 'message' => 'Mission acceptée avec succès !']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'valider_tache') {
        $user_mission_id = (int) $_POST['user_mission_id'];
        $description = trim($_POST['description']);
        
        if (empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Veuillez décrire la tâche accomplie']);
            exit;
        }
        
        try {
            // Gérer l'upload de la photo
            $photo_url = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../uploads/missions/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo_filename = 'mission_' . $user_mission_id . '_' . time() . '.' . $file_extension;
                $photo_path = $upload_dir . $photo_filename;
                
                // Vérifier le type de fichier
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array(strtolower($file_extension), $allowed_types)) {
                    echo json_encode(['success' => false, 'message' => 'Format de fichier non autorisé']);
                    exit;
                }
                
                // Déplacer le fichier uploadé
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                    $photo_url = '/uploads/missions/' . $photo_filename;
                }
            }
            
            // Insérer la validation avec photo
            $stmt = $shop_pdo->prepare("
                INSERT INTO mission_validations (user_mission_id, description, photo_url, date_validation, statut)
                VALUES (?, ?, ?, NOW(), 'en_attente')
            ");
            $stmt->execute([$user_mission_id, $description, $photo_url]);
            
            // Mettre à jour la progression
            $stmt = $shop_pdo->prepare("
                UPDATE user_missions um
                SET um.progres = (
                    SELECT COUNT(*) FROM mission_validations mv 
                    WHERE mv.user_mission_id = um.id AND mv.statut = 'validee'
                )
                WHERE um.id = ?
            ");
            $stmt->execute([$user_mission_id]);
            
            echo json_encode(['success' => true, 'message' => 'Tâche soumise pour validation !']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Récupération des missions par catégorie
try {
    // DEBUG AVANT MISSIONS EN COURS
    echo '<div style="position: fixed; top: 320px; left: 10px; background: #ff9800; color: white; padding: 15px; z-index: 9996; border-radius: 8px; max-width: 400px;">';
    echo '<h4>🔍 DEBUG AVANT MISSIONS EN COURS</h4>';
    echo '<p>Avant la requête missions en cours...</p>';
    echo '</div>';

    try {
        // Missions en cours
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
                COALESCE(mv.taches_validees, 0) AS taches_validees,
                COALESCE(mv.taches_approuvees, 0) AS taches_approuvees,
                um.date_rejointe as date_acceptation,
                um.progres as progression
            FROM user_missions um
            JOIN missions m ON um.mission_id = m.id
            LEFT JOIN mission_types mt ON m.type_id = mt.id
            LEFT JOIN (
                SELECT
                    user_mission_id,
                    COUNT(*) AS taches_validees,
                    SUM(CASE WHEN statut = 'validee' THEN 1 ELSE 0 END) AS taches_approuvees
                FROM mission_validations
                GROUP BY user_mission_id
            ) mv ON um.id = mv.user_mission_id
            WHERE um.user_id = ? AND um.statut = 'en_cours'
            ORDER BY um.date_rejointe DESC
        ");
        
        echo '<div style="position: fixed; top: 450px; left: 10px; background: #3f51b5; color: white; padding: 15px; z-index: 9995; border-radius: 8px; max-width: 400px;">';
        echo '<h4>🔍 DEBUG REQUÊTE PRÉPARÉE</h4>';
        echo '<p>Requête missions en cours préparée</p>';
        echo '</div>';
        
        $stmt->execute([$user_id]);
        
        echo '<div style="position: fixed; top: 580px; left: 10px; background: #009688; color: white; padding: 15px; z-index: 9994; border-radius: 8px; max-width: 400px;">';
        echo '<h4>🔍 DEBUG APRÈS EXECUTE</h4>';
        echo '<p>Requête exécutée avec user_id: ' . $user_id . '</p>';
        echo '</div>';
        
        $missions_en_cours = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<div style="position: fixed; top: 710px; left: 10px; background: #4caf50; color: white; padding: 15px; z-index: 9993; border-radius: 8px; max-width: 400px;">';
        echo '<h4>🔍 DEBUG MISSIONS EN COURS OK</h4>';
        echo '<p>Trouvé: ' . count($missions_en_cours) . ' missions en cours</p>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div style="position: fixed; top: 450px; left: 10px; background: #f44336; color: white; padding: 15px; z-index: 9995; border-radius: 8px; max-width: 400px;">';
        echo '<h4>❌ ERREUR MISSIONS EN COURS</h4>';
        echo '<p>Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
        $missions_en_cours = [];
    }
    

    // Missions disponibles
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
    
    // DEBUG - Supprimer après correction
    error_log("DEBUG MISSIONS DISPONIBLES: count = " . count($missions_disponibles) . " pour user_id = " . $user_id);
    
    // DEBUG VISIBLE - Affichage temporaire pour diagnostic - FORCER L'AFFICHAGE
    echo "<div style='background: #ffebee; border: 2px solid #f44336; padding: 20px; margin: 20px 20px 20px 420px; border-radius: 8px; font-family: monospace;'>";
    echo "<h3 style='color: #d32f2f; margin-top: 0;'>🐛 DEBUG: Analyse des missions (trouvées: " . count($missions_disponibles) . ")</h3>";
    echo "<p><strong>User ID:</strong> " . htmlspecialchars($user_id) . "</p>";
    echo "<p><strong>Shop ID:</strong> " . htmlspecialchars($shop_id) . "</p>";
    echo "<p><strong>Tests SQL:</strong></p>";
    
    // Tester étape par étape
    try {
        // Test 1: Toutes les missions actives
        $stmt_debug = $shop_pdo->query("SELECT COUNT(*) as count FROM missions WHERE statut = 'active'");
        $count_active = $stmt_debug->fetch()['count'];
        echo "<p>✅ Missions actives: <strong>$count_active</strong></p>";
        
        // Test 2: Missions avec date OK
        $stmt_debug = $shop_pdo->query("SELECT COUNT(*) as count FROM missions WHERE statut = 'active' AND (date_fin IS NULL OR date_fin >= CURDATE())");
        $count_date_ok = $stmt_debug->fetch()['count'];
        echo "<p>✅ Missions avec date valide: <strong>$count_date_ok</strong></p>";
        
        // Test 3: Missions de l'utilisateur
        $stmt_debug = $shop_pdo->prepare("SELECT COUNT(*) as count FROM user_missions WHERE user_id = ?");
        $stmt_debug->execute([$user_id]);
        $count_user = $stmt_debug->fetch()['count'];
        echo "<p>✅ Missions de l'utilisateur: <strong>$count_user</strong></p>";
        
        // Test 4: Missions exclues
        $stmt_debug = $shop_pdo->prepare("SELECT GROUP_CONCAT(mission_id) as ids FROM user_missions WHERE user_id = ?");
        $stmt_debug->execute([$user_id]);
        $excluded_ids = $stmt_debug->fetch()['ids'] ?? 'aucune';
        echo "<p>✅ Missions exclues (IDs): <strong>$excluded_ids</strong></p>";
        
        // Test 5: Requête finale avec détails
        $stmt_debug = $shop_pdo->prepare("
            SELECT m.id, m.titre, m.recompense_euros, m.date_fin, m.statut
            FROM missions m
            WHERE m.statut = 'active'
              AND (m.date_fin IS NULL OR m.date_fin >= CURDATE())
              AND m.id NOT IN (
                SELECT COALESCE(mission_id, 0) FROM user_missions WHERE user_id = ?
              )
            ORDER BY m.created_at DESC
        ");
        $stmt_debug->execute([$user_id]);
        $debug_missions = $stmt_debug->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>✅ Missions qui devraient être disponibles: <strong>" . count($debug_missions) . "</strong></p>";
        
        if (!empty($debug_missions)) {
            echo "<table style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
            echo "<tr style='background: #f5f5f5;'><th style='border: 1px solid #ddd; padding: 8px;'>ID</th><th style='border: 1px solid #ddd; padding: 8px;'>Titre</th><th style='border: 1px solid #ddd; padding: 8px;'>Récompense</th><th style='border: 1px solid #ddd; padding: 8px;'>Date fin</th><th style='border: 1px solid #ddd; padding: 8px;'>Statut</th></tr>";
            foreach ($debug_missions as $dm) {
                echo "<tr>";
                echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $dm['id'] . "</td>";
                echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($dm['titre']) . "</td>";
                echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $dm['recompense_euros'] . "€</td>";
                echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . ($dm['date_fin'] ?? 'NULL') . "</td>";
                echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $dm['statut'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            // Si pas de missions, testons sans l'exclusion
            echo "<p><strong>🔍 Test sans exclusion des user_missions:</strong></p>";
            $stmt_debug2 = $shop_pdo->query("
                SELECT m.id, m.titre, m.recompense_euros, m.date_fin, m.statut
                FROM missions m
                WHERE m.statut = 'active'
                  AND (m.date_fin IS NULL OR m.date_fin >= CURDATE())
                ORDER BY m.created_at DESC
            ");
            $debug_missions2 = $stmt_debug2->fetchAll(PDO::FETCH_ASSOC);
            echo "<p>✅ Missions sans exclusion: <strong>" . count($debug_missions2) . "</strong></p>";
            
            if (!empty($debug_missions2)) {
                echo "<table style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
                echo "<tr style='background: #f5f5f5;'><th style='border: 1px solid #ddd; padding: 8px;'>ID</th><th style='border: 1px solid #ddd; padding: 8px;'>Titre</th><th style='border: 1px solid #ddd; padding: 8px;'>Récompense</th><th style='border: 1px solid #ddd; padding: 8px;'>Date fin</th><th style='border: 1px solid #ddd; padding: 8px;'>Statut</th></tr>";
                foreach ($debug_missions2 as $dm) {
                    echo "<tr>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $dm['id'] . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($dm['titre']) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $dm['recompense_euros'] . "€</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . ($dm['date_fin'] ?? 'NULL') . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $dm['statut'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: #d32f2f;'>❌ Erreur debug: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "</div>";
    
    // Missions complétées
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
            um.date_completion
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        LEFT JOIN mission_types mt ON m.type_id = mt.id
        WHERE um.user_id = ? AND um.statut = 'complete'
        ORDER BY um.date_completion DESC
    ");
    $stmt->execute([$user_id]);
    $missions_completees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques
    $total_missions_actives = count($missions_en_cours);
    $total_missions_disponibles = count($missions_disponibles);
    $total_missions_completees = count($missions_completees);
    $validations_en_attente = 0;
    
    // Compter les validations en attente
    $stmt = $shop_pdo->prepare("
        SELECT COUNT(*)
        FROM mission_validations mv
        JOIN user_missions um ON mv.user_mission_id = um.id
        WHERE um.user_id = ? AND mv.statut = 'en_attente'
    ");
    $stmt->execute([$user_id]);
    $validations_en_attente = $stmt->fetchColumn();

    // Historique des gains (cagnotte)
    $gains_historiques = [];
    $total_gains_euros = 0.0;
    $total_gains_points = 0;
    try {
        $stmt = $shop_pdo->prepare("
            SELECT 
                m.titre as mission_titre,
                COALESCE(mr.montant_euros, m.recompense_euros) as euros,
                COALESCE(mr.points_attribues, m.recompense_points) as points,
                COALESCE(mr.date_attribution, um.date_completion) as date_gain
            FROM user_missions um
            JOIN missions m ON um.mission_id = m.id
            LEFT JOIN mission_recompenses mr ON um.id = mr.user_mission_id
            WHERE um.user_id = ? AND um.statut IN ('complete', 'terminee')
            ORDER BY COALESCE(mr.date_attribution, um.date_completion) DESC
            LIMIT 50
        ");
        $stmt->execute([$user_id]);
        $gains_historiques = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($gains_historiques as $row) {
            $total_gains_euros += (float)($row['euros'] ?? 0);
            $total_gains_points += (int)($row['points'] ?? 0);
        }
    } catch (Exception $e) {
        error_log('Erreur récupération historique cagnotte: ' . $e->getMessage());
        $gains_historiques = [];
        $total_gains_euros = 0.0;
        $total_gains_points = 0;
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des missions: " . $e->getMessage());
    $missions_en_cours = [];
    $missions_disponibles = [];
    $missions_completees = [];
    $total_missions_actives = 0;
    $total_missions_disponibles = 0;
    $total_missions_completees = 0;
    $validations_en_attente = 0;
}
?>

<!-- Reprise exacte du design tableau de bord -->
<link href="assets/css/dashboard-new.css" rel="stylesheet">

<style>
/* Ajustements légers spécifiques Missions pour compléter dashboard-new.css */
.mission-type { border-radius: 10px; padding: 6px 10px; font-size: 0.75rem; font-weight: 600; }
.badge-status { display: inline-block; padding: 4px 8px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; }
.badge-status.success { background: rgba(34,197,94,.1); color: #16a34a; }
.badge-status.info { background: rgba(59,130,246,.1); color: #2563eb; }
.badge-status.warning { background: rgba(245,158,11,.1); color: #f59e0b; }
.badge-status.gray { background: #f3f4f6; color: #4b5563; }
.action-cell { text-align: right; }
</style>

<div class="statistics-container">
    <h2 class="section-title"><i class="fas fa-bullseye"></i> Mes Missions</h2>
    <div class="statistics-grid">
        <a href="#" class="stat-card js-stat-card" data-tab="en-cours" style="text-decoration: none; color: inherit;">
            <div class="stat-icon"><i class="fas fa-play-circle"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_missions_actives; ?></div>
                <div class="stat-label">Missions en cours</div>
            </div>
            <div class="stat-link"><i class="fas fa-arrow-right"></i></div>
        </a>
        <a href="#" class="stat-card progress-card js-stat-card" data-tab="disponibles" style="text-decoration: none; color: inherit;">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_missions_disponibles; ?></div>
                <div class="stat-label">Disponibles</div>
            </div>
            <div class="stat-link"><i class="fas fa-arrow-right"></i></div>
        </a>
        <a href="#" class="stat-card waiting-card js-stat-card" data-tab="en-cours" style="text-decoration: none; color: inherit;">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $validations_en_attente; ?></div>
                <div class="stat-label">Validations en attente</div>
            </div>
            <div class="stat-link"><i class="fas fa-arrow-right"></i></div>
        </a>
        <a href="#" class="stat-card clients-card js-open-cagnotte" style="text-decoration: none; color: inherit;">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($cagnotte_utilisateur, 2); ?> €</div>
                <div class="stat-label">Ma cagnotte</div>
            </div>
            <div class="stat-link"><i class="fas fa-arrow-right"></i></div>
        </a>
    </div>
</div>

<!-- Onglets identiques au dashboard -->
<div class="tabs-container">
    <div class="tabs-header">
        <button class="tab-button active" data-tab="en-cours"><i class="fas fa-play-circle"></i> En cours <span class="badge bg-primary ms-2"><?php echo $total_missions_actives; ?></span></button>
        <button class="tab-button" data-tab="disponibles"><i class="fas fa-star"></i> Disponibles <span class="badge bg-primary ms-2"><?php echo $total_missions_disponibles; ?></span></button>
        <button class="tab-button" data-tab="completees"><i class="fas fa-trophy"></i> Complétées <span class="badge bg-primary ms-2"><?php echo $total_missions_completees; ?></span></button>
    </div>
</div>

    <!-- Missions en cours -->
    <div id="en-cours" class="tab-content active">
        <?php if (empty($missions_en_cours)): ?>
        <div class="modern-table">
            <div class="modern-table-empty">
                <i class="fas fa-tasks"></i>
                <div class="title">Aucune mission en cours</div>
                <p class="subtitle">Consultez les missions disponibles pour commencer</p>
            </div>
        </div>
        <?php else: ?>
        <div class="modern-table">
            <div class="modern-table-columns">
                <span style="flex:1;">Titre</span>
                <span style="width:25%; text-align:center;">Progression</span>
                <span style="width:25%; text-align:center;">Récompense</span>
                <span class="hide-sm" style="width:20%; text-align:right;">Action</span>
            </div>
            <?php foreach ($missions_en_cours as $mission): ?>
            <div class="modern-table-row">
                <div class="modern-table-indicator taches"></div>
                <div class="modern-table-cell primary">
                    <span class="modern-table-text"><?php echo htmlspecialchars($mission['titre']); ?></span>
                    <span class="modern-table-subtext"><?php echo htmlspecialchars($mission['type_nom'] ?? ''); ?></span>
                </div>
                <div class="modern-table-cell" style="width:25%; text-align:center;">
                    <span class="badge-status info"><?php echo $mission['taches_approuvees']; ?>/<?php echo $mission['nombre_taches']; ?></span>
                </div>
                <div class="modern-table-cell" style="width:25%; text-align:center;">
                    <span class="modern-table-text"><?php echo number_format($mission['recompense_euros'], 2); ?> €</span>
                    <span class="modern-table-subtext">+ <?php echo (int)$mission['recompense_points']; ?> XP</span>
                </div>
                <div class="modern-table-cell action-cell hide-sm" style="width:20%;">
                    <?php if ($mission['taches_approuvees'] < $mission['nombre_taches']): ?>
                        <a href="javascript:void(0)" class="task-edit-btn" onclick="validerTache(<?php echo $mission['id']; ?>)"><i class="fas fa-check"></i> Valider</a>
                    <?php else: ?>
                        <span class="badge-status success">Complète</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Missions disponibles -->
    <div id="disponibles" class="tab-content">
        <?php if (empty($missions_disponibles)): ?>
        <div class="modern-table">
            <div class="modern-table-empty">
                <i class="fas fa-star"></i>
                <div class="title">Aucune mission disponible</div>
                <p class="subtitle">Revenez plus tard</p>
            </div>
        </div>
        <?php else: ?>
        <div class="modern-table">
            <div class="modern-table-columns">
                <span style="flex:1;">Titre</span>
                <span style="width:25%; text-align:center;">Tâches</span>
                <span style="width:25%; text-align:center;">Récompense</span>
                <span class="hide-sm" style="width:20%; text-align:right;">Action</span>
            </div>
            <?php foreach ($missions_disponibles as $mission): ?>
            <div class="modern-table-row">
                <div class="modern-table-indicator reparations"></div>
                <div class="modern-table-cell primary">
                    <span class="modern-table-text"><?php echo htmlspecialchars($mission['titre']); ?></span>
                    <span class="modern-table-subtext"><?php echo htmlspecialchars($mission['type_nom'] ?? ''); ?></span>
                </div>
                <div class="modern-table-cell" style="width:25%; text-align:center;">
                    <span class="badge-status gray"><?php echo (int)$mission['nombre_taches']; ?> tâches</span>
                </div>
                <div class="modern-table-cell" style="width:25%; text-align:center;">
                    <span class="modern-table-text"><?php echo number_format($mission['recompense_euros'], 2); ?> €</span>
                    <span class="modern-table-subtext">+ <?php echo (int)$mission['recompense_points']; ?> XP</span>
                </div>
                <div class="modern-table-cell action-cell hide-sm" style="width:20%;">
                    <a href="javascript:void(0)" class="task-edit-btn" onclick="accepterMission(<?php echo $mission['id']; ?>)"><i class="fas fa-plus"></i> Accepter</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Missions complétées -->
    <div id="completees" class="tab-content">
        <?php if (empty($missions_completees)): ?>
        <div class="modern-table">
            <div class="modern-table-empty">
                <i class="fas fa-trophy"></i>
                <div class="title">Aucune mission complétée</div>
                <p class="subtitle">Complétez vos premières missions</p>
            </div>
        </div>
        <?php else: ?>
        <div class="modern-table">
            <div class="modern-table-columns">
                <span style="flex:1;">Titre</span>
                <span style="width:25%; text-align:center;">Date</span>
                <span style="width:25%; text-align:center;">Récompense</span>
                <span class="hide-sm" style="width:20%; text-align:center;">Statut</span>
            </div>
            <?php foreach ($missions_completees as $mission): ?>
            <div class="modern-table-row">
                <div class="modern-table-indicator commandes"></div>
                <div class="modern-table-cell primary">
                    <span class="modern-table-text"><?php echo htmlspecialchars($mission['titre']); ?></span>
                    <span class="modern-table-subtext"><?php echo htmlspecialchars($mission['type_nom'] ?? ''); ?></span>
                </div>
                <div class="modern-table-cell" style="width:25%; text-align:center;">
                    <div class="modern-date-badge"><span><?php echo date('d/m/Y', strtotime($mission['date_completion'])); ?></span></div>
                </div>
                <div class="modern-table-cell" style="width:25%; text-align:center;">
                    <span class="modern-table-text"><?php echo number_format($mission['recompense_euros'], 2); ?> €</span>
                    <span class="modern-table-subtext">+ <?php echo (int)$mission['recompense_points']; ?> XP</span>
                </div>
                <div class="modern-table-cell hide-sm" style="width:20%; text-align:center;">
                    <span class="badge-status success">Validée</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de validation de tâche -->
<div class="modal fade" id="validationModal" tabindex="-1" aria-labelledby="validationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="validationModalLabel">
                    <i class="fas fa-check-circle me-2"></i>
                    Valider une tâche
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="validationForm" enctype="multipart/form-data">
                    <input type="hidden" id="userMissionId" name="user_mission_id">
                    <div class="mb-3">
                        <label for="description" class="form-label">Description de la tâche accomplie *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  placeholder="Décrivez ce que vous avez fait pour accomplir cette tâche..."
                                  required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="photo" class="form-label">Photo (optionnelle)</label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                        <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" onclick="soumettreValidation()">
                    <i class="fas fa-check"></i>
                    Valider la tâche
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cagnotte & Historique des gains -->
<div class="modal fade" id="cagnotteModal" tabindex="-1" aria-labelledby="cagnotteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cagnotteModalLabel">
                    <i class="fas fa-coins me-2"></i>
                    Ma cagnotte
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <div class="repair-card" style="border-left-color:#10b981;">
                            <div class="repair-header">
                                <div class="repair-device"><i class="fas fa-wallet"></i> Solde actuel</div>
                            </div>
                            <div class="repair-footer">
                                <div class="order-price" style="font-size:1.75rem;">
                                    <?php echo number_format($cagnotte_utilisateur, 2, ',', ' '); ?> €
                                </div>
                                <div class="order-date">
                                    <i class="fas fa-chart-line"></i>
                                    Total: <?php echo number_format($total_gains_euros, 2, ',', ' '); ?> € / <?php echo (int)$total_gains_points; ?> XP
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="repair-card">
                            <div class="repair-header">
                                <div class="repair-device"><i class="fas fa-info-circle"></i> Détails</div>
                            </div>
                            <div class="repair-footer">
                                <div class="order-quantity">Dernières 50 entrées max</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modern-table">
                    <div class="modern-table-columns">
                        <span style="flex:1;">Mission</span>
                        <span style="width:20%; text-align:center;">Date</span>
                        <span style="width:20%; text-align:center;">Euros</span>
                        <span style="width:20%; text-align:center;">Points</span>
                    </div>
                    <?php if (!empty($gains_historiques)): ?>
                        <?php foreach ($gains_historiques as $gain): ?>
                            <div class="modern-table-row">
                                <div class="modern-table-indicator commandes"></div>
                                <div class="modern-table-cell primary">
                                    <span class="modern-table-text"><?php echo htmlspecialchars($gain['mission_titre'] ?? ''); ?></span>
                                </div>
                                <div class="modern-table-cell" style="width:20%; text-align:center;">
                                    <div class="modern-date-badge"><span><?php echo date('d/m/Y', strtotime($gain['date_gain'] ?? 'now')); ?></span></div>
                                </div>
                                <div class="modern-table-cell" style="width:20%; text-align:center;">
                                    <span class="modern-table-text"><?php echo number_format((float)($gain['euros'] ?? 0), 2, ',', ' '); ?> €</span>
                                </div>
                                <div class="modern-table-cell" style="width:20%; text-align:center;">
                                    <span class="modern-table-text"><?php echo (int)($gain['points'] ?? 0); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="modern-table-empty">
                            <i class="fas fa-piggy-bank"></i>
                            <div class="title">Aucun gain enregistré</div>
                            <p class="subtitle">Les gains apparaîtront ici après vos missions validées</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    // Supporte les deux variantes: .tab-button (dashboard) et .tab-btn (anciens styles)
    const tabBtns = document.querySelectorAll('.tab-button, .tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const statCards = document.querySelectorAll('.js-stat-card');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            if (!tabId) return;

            // Retirer la classe active de tous les boutons et contenus
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            // Activer le bouton et l'onglet correspondant
            this.classList.add('active');
            const target = document.getElementById(tabId);
            if (target) target.classList.add('active');
        });
    });

    // Click sur cartes de stats -> bascule l'onglet correspondant
    statCards.forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.dataset.tab;
            if (!tabId) return;
            // activer le bon bouton s'il existe
            const btn = document.querySelector(`.tab-button[data-tab="${tabId}"]`) || document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
            if (btn) btn.click();
            else {
                // fallback: basculer manuellement
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                const target = document.getElementById(tabId);
                if (target) target.classList.add('active');
            }
        });
    });

    // Ouvrir modal cagnotte
    const cagnotteCard = document.querySelector('.js-open-cagnotte');
    if (cagnotteCard) {
        cagnotteCard.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = new bootstrap.Modal(document.getElementById('cagnotteModal'));
            modal.show();
        });
    }
});

// Fonction pour accepter une mission
function accepterMission(missionId) {
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=accepter_mission&mission_id=${missionId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de connexion', 'error');
    });
}

// Fonction pour valider une tâche
function validerTache(userMissionId) {
    document.getElementById('userMissionId').value = userMissionId;
    new bootstrap.Modal(document.getElementById('validationModal')).show();
}

// Fonction pour soumettre la validation
function soumettreValidation() {
    const form = document.getElementById('validationForm');
    const formData = new FormData(form);
    formData.append('action', 'valider_tache');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('validationModal')).hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de connexion', 'error');
    });
}

// Fonction pour afficher les notifications
function showNotification(message, type) {
    // Supprimer les notifications existantes
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        ${message}
        <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: white; margin-left: 1rem; cursor: pointer;">×</button>
    `;
    
    document.body.appendChild(notification);
    
    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}
</script>
