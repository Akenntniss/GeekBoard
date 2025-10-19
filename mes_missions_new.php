<?php
// Inclure la configuration de session avant de démarrer la session
require_once __DIR__ . '/config/session_config.php';
// La session est déjà démarrée dans session_config.php, pas besoin de session_start() ici

// Inclure la configuration pour la gestion des sous-domaines
require_once __DIR__ . '/config/subdomain_config.php';
// Le sous-domaine est détecté et la session est configurée avec le magasin correspondant

// Définir le chemin de base seulement s'il n'est pas déjà défini (éviter les conflits avec index.php)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/.');
}

// Inclure les fichiers de configuration et de connexion à la base de données
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Vérification de l'authentification GeekBoard
if (!isset($_SESSION['shop_id'])) {
    header('Location: /pages/login_auto.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login_auto.php');
    exit();
}

// Récupération des informations utilisateur
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? $_SESSION['name'] ?? 'Utilisateur';
$shop_id = $_SESSION['shop_id'];

// Connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Récupération de la cagnotte de l'utilisateur
$cagnotte_utilisateur = 0.00;
try {
    $stmt = $shop_pdo->prepare("SELECT cagnotte FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $cagnotte_utilisateur = $result['cagnotte'];
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
                INSERT INTO user_missions (user_id, mission_id, date_acceptation, statut, progression)
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
                $upload_dir = __DIR__ . '/uploads/missions/';
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
                    // Définir les permissions appropriées
                    chmod($photo_path, 0644);
                    chown($photo_path, 'www-data');
                    chgrp($photo_path, 'www-data');
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload de la photo']);
                    exit;
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
                SET um.progression = (
                    SELECT COUNT(*) FROM mission_validations mv 
                    WHERE mv.user_mission_id = um.id AND mv.statut = 'approuve'
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
    // Missions en cours
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
    
    // Missions disponibles
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
    
    // Missions complétées
    $stmt = $shop_pdo->prepare("
        SELECT um.id, m.titre, m.description, mt.nom as type_nom, mt.couleur,
               m.recompense_euros, m.recompense_points, m.nombre_taches,
               um.date_completion
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.type_id = mt.id
        WHERE um.user_id = ? AND um.statut = 'complete'
        ORDER BY um.date_completion DESC
    ");
    $stmt->execute([$user_id]);
    $missions_completees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des missions: " . $e->getMessage());
    $missions_en_cours = [];
    $missions_disponibles = [];
    $missions_completees = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Missions - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3b82f6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Header moderne */
        .modern-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .modern-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-brand {
            display: flex;
            align-items: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
        }

        .header-brand i {
            margin-right: 0.5rem;
            font-size: 1.8rem;
        }

        .header-user {
            color: white;
            text-align: right;
        }

        .header-user h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .header-user p {
            margin: 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }

        /* Carte de cagnotte */
        .cagnotte-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 20px;
            padding: 2rem;
            color: white;
            text-align: center;
            margin: 2rem 0;
            box-shadow: var(--shadow-lg);
        }

        .cagnotte-amount {
            font-size: 3rem;
            font-weight: 800;
            margin: 0.5rem 0;
        }

        .cagnotte-label {
            font-size: 1.2rem;
            opacity: 0.9;
            margin: 0;
        }

        .cagnotte-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        /* Filtres modernes */
        .filters-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1rem;
            margin: 2rem 0;
        }

        .filters-tabs {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .filter-tab {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            min-width: 140px;
            justify-content: center;
        }

        .filter-tab.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .filter-tab:hover:not(.active) {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .filter-tab i {
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }

        .filter-badge {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }

        /* Grille de missions */
        .missions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }

        .mission-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .mission-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .mission-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .mission-type {
            display: inline-block;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }

        .mission-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0 0 0.5rem 0;
        }

        .mission-description {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 0;
        }

        .mission-card-body {
            padding: 1.5rem;
        }

        .mission-progress {
            margin-bottom: 1.5rem;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .progress-text {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--dark-color);
        }

        .progress-badge {
            background: var(--light-color);
            color: var(--dark-color);
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .progress-bar-container {
            height: 8px;
            background: var(--light-color);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--success-color), #059669);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .mission-rewards {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .reward-item {
            flex: 1;
            text-align: center;
            padding: 1rem;
            background: var(--light-color);
            border-radius: 15px;
        }

        .reward-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .reward-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.2rem;
        }

        .reward-label {
            font-size: 0.8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Boutons modernes */
        .btn-modern {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 0.9rem;
            min-width: 120px;
        }

        .btn-modern i {
            margin-right: 0.5rem;
        }

        .btn-modern.btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-modern.btn-primary:hover {
            background: #3730a3;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-modern.btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-modern.btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-modern.btn-outline {
            background: transparent;
            color: var(--dark-color);
            border: 2px solid var(--border-color);
        }

        .btn-modern.btn-outline:hover {
            background: var(--light-color);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        /* État vide */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: white;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.7;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .empty-state p {
            opacity: 0.8;
            font-size: 1rem;
        }

        /* Contenu des onglets */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .missions-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .filter-tab {
                min-width: 100px;
                padding: 0.75rem 1rem;
            }
            
            .cagnotte-amount {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header moderne -->
    <div class="modern-header">
        <div class="container">
            <a href="index.php" class="header-brand">
                <i class="fas fa-bullseye"></i>
                Mes Missions
            </a>
            <div class="header-user">
                <h3>Bonjour, <?php echo htmlspecialchars($user_name); ?> !</h3>
                <p>Voici vos missions et défis du moment</p>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Carte de cagnotte -->
        <div class="cagnotte-card">
            <div class="cagnotte-icon">💰</div>
            <div class="cagnotte-amount"><?php echo number_format($cagnotte_utilisateur, 2, ',', ' '); ?> €</div>
            <p class="cagnotte-label">Votre cagnotte</p>
        </div>

        <!-- Filtres modernes -->
        <div class="filters-container">
            <div class="filters-tabs">
                <a href="#en-cours" class="filter-tab active" data-tab="en-cours">
                    <i class="fas fa-play-circle"></i>
                    En cours
                    <span class="filter-badge"><?php echo count($missions_en_cours); ?></span>
                </a>
                <a href="#disponibles" class="filter-tab" data-tab="disponibles">
                    <i class="fas fa-star"></i>
                    Disponibles
                    <span class="filter-badge"><?php echo count($missions_disponibles); ?></span>
                </a>
                <a href="#completees" class="filter-tab" data-tab="completees">
                    <i class="fas fa-trophy"></i>
                    Complétées
                    <span class="filter-badge"><?php echo count($missions_completees); ?></span>
                </a>
            </div>
        </div>

        <!-- Missions en cours -->
        <div id="en-cours" class="tab-content active">
            <?php if (empty($missions_en_cours)): ?>
            <div class="empty-state">
                <i class="fas fa-tasks"></i>
                <h3>Aucune mission en cours</h3>
                <p>Consultez les missions disponibles pour commencer à gagner des récompenses !</p>
            </div>
            <?php else: ?>
            <div class="missions-grid">
                <?php foreach ($missions_en_cours as $mission): ?>
                <div class="mission-card">
                    <div class="mission-card-header">
                        <div class="mission-type" style="background-color: <?php echo htmlspecialchars($mission['couleur']); ?>">
                            <?php echo htmlspecialchars($mission['type_nom']); ?>
                        </div>
                        <h3 class="mission-title"><?php echo htmlspecialchars($mission['titre']); ?></h3>
                        <p class="mission-description"><?php echo htmlspecialchars($mission['description']); ?></p>
                    </div>
                    <div class="mission-card-body">
                        <div class="mission-progress">
                            <div class="progress-header">
                                <span class="progress-text">Progression</span>
                                <span class="progress-badge"><?php echo $mission['taches_approuvees']; ?>/<?php echo $mission['nombre_taches']; ?></span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: <?php echo ($mission['nombre_taches'] > 0) ? ($mission['taches_approuvees'] / $mission['nombre_taches'] * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="mission-rewards">
                            <div class="reward-item">
                                <div class="reward-icon">💰</div>
                                <div class="reward-value"><?php echo number_format($mission['recompense_euros'], 2); ?> €</div>
                                <div class="reward-label">Euros</div>
                            </div>
                            <div class="reward-item">
                                <div class="reward-icon">⭐</div>
                                <div class="reward-value"><?php echo $mission['recompense_points']; ?></div>
                                <div class="reward-label">Points XP</div>
                            </div>
                        </div>
                        
                        <div class="mission-actions">
                            <?php if ($mission['taches_approuvees'] < $mission['nombre_taches']): ?>
                            <button type="button" class="btn-modern btn-success" onclick="validerTache(<?php echo $mission['id']; ?>)">
                                <i class="fas fa-check"></i>
                                Valider une tâche
                            </button>
                            <?php else: ?>
                            <span class="btn-modern btn-outline" style="cursor: default;">
                                <i class="fas fa-trophy"></i>
                                Mission complète
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Missions disponibles -->
        <div id="disponibles" class="tab-content">
            <?php if (empty($missions_disponibles)): ?>
            <div class="empty-state">
                <i class="fas fa-star"></i>
                <h3>Aucune mission disponible</h3>
                <p>Toutes les missions sont déjà en cours ou complétées. Revenez plus tard !</p>
            </div>
            <?php else: ?>
            <div class="missions-grid">
                <?php foreach ($missions_disponibles as $mission): ?>
                <div class="mission-card">
                    <div class="mission-card-header">
                        <div class="mission-type" style="background-color: <?php echo htmlspecialchars($mission['couleur']); ?>">
                            <?php echo htmlspecialchars($mission['type_nom']); ?>
                        </div>
                        <h3 class="mission-title"><?php echo htmlspecialchars($mission['titre']); ?></h3>
                        <p class="mission-description"><?php echo htmlspecialchars($mission['description']); ?></p>
                    </div>
                    <div class="mission-card-body">
                        <div class="mission-progress">
                            <div class="progress-header">
                                <span class="progress-text">Tâches requises</span>
                                <span class="progress-badge"><?php echo $mission['nombre_taches']; ?> tâches</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: 0%"></div>
                            </div>
                        </div>
                        
                        <div class="mission-rewards">
                            <div class="reward-item">
                                <div class="reward-icon">💰</div>
                                <div class="reward-value"><?php echo number_format($mission['recompense_euros'], 2); ?> €</div>
                                <div class="reward-label">Récompense</div>
                            </div>
                            <div class="reward-item">
                                <div class="reward-icon">⭐</div>
                                <div class="reward-value"><?php echo $mission['recompense_points']; ?></div>
                                <div class="reward-label">Points XP</div>
                            </div>
                        </div>
                        
                        <div class="mission-actions">
                            <button type="button" class="btn-modern btn-primary" onclick="accepterMission(<?php echo $mission['id']; ?>)">
                                <i class="fas fa-plus"></i>
                                Accepter la mission
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Missions complétées -->
        <div id="completees" class="tab-content">
            <?php if (empty($missions_completees)): ?>
            <div class="empty-state">
                <i class="fas fa-trophy"></i>
                <h3>Aucune mission complétée</h3>
                <p>Complétez vos premières missions pour les voir apparaître ici !</p>
            </div>
            <?php else: ?>
            <div class="missions-grid">
                <?php foreach ($missions_completees as $mission): ?>
                <div class="mission-card">
                    <div class="mission-card-header">
                        <div class="mission-type" style="background-color: <?php echo htmlspecialchars($mission['couleur']); ?>">
                            <?php echo htmlspecialchars($mission['type_nom']); ?>
                        </div>
                        <h3 class="mission-title"><?php echo htmlspecialchars($mission['titre']); ?></h3>
                        <p class="mission-description"><?php echo htmlspecialchars($mission['description']); ?></p>
                    </div>
                    <div class="mission-card-body">
                        <div class="mission-progress">
                            <div class="progress-header">
                                <span class="progress-text">Complétée le</span>
                                <span class="progress-badge"><?php echo date('d/m/Y', strtotime($mission['date_completion'])); ?></span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: 100%;"></div>
                            </div>
                        </div>
                        
                        <div class="mission-rewards">
                            <div class="reward-item">
                                <div class="reward-icon">💰</div>
                                <div class="reward-value"><?php echo number_format($mission['recompense_euros'], 2); ?> €</div>
                                <div class="reward-label">Gagnés</div>
                            </div>
                            <div class="reward-item">
                                <div class="reward-icon">⭐</div>
                                <div class="reward-value"><?php echo $mission['recompense_points']; ?></div>
                                <div class="reward-label">Points XP</div>
                            </div>
                        </div>
                        
                        <div class="mission-actions">
                            <span class="btn-modern btn-success" style="cursor: default;">
                                <i class="fas fa-trophy"></i>
                                Mission réussie !
                            </span>
                        </div>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion des onglets
        document.addEventListener('DOMContentLoaded', function() {
            const filterTabs = document.querySelectorAll('.filter-tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            filterTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Retirer la classe active
                    filterTabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Ajouter la classe active
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });

        // Fonction pour accepter une mission
        function accepterMission(missionId) {
            fetch('mes_missions_new.php', {
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
            
            fetch('mes_missions_new.php', {
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
            // Créer l'élément de notification
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
</body>
</html> 