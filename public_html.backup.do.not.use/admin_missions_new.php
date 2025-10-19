<?php
// Inclure la configuration de session
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/.');
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Vérifications d'authentification
if (!isset($_SESSION['shop_id']) || !isset($_SESSION['user_id'])) {
    header('Location: /pages/login_auto.php');
    exit();
}

// Vérification du rôle admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /pages/accueil.php?error=admin_required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'Administrateur';
$shop_id = $_SESSION['shop_id'];
$shop_pdo = getShopDBConnection();

// Traitement des actions AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'creer_mission':
            $titre = trim($_POST['titre']);
            $description = trim($_POST['description']);
            $type_id = (int) $_POST['type_id'];
            $nombre_taches = (int) $_POST['nombre_taches'];
            $recompense_euros = (float) $_POST['recompense_euros'];
            $recompense_points = (int) $_POST['recompense_points'];
            $priorite = (int) $_POST['priorite'];
            $date_fin = $_POST['date_fin'] ?: null;
            
            if (empty($titre) || empty($description) || $type_id <= 0 || $nombre_taches <= 0) {
                echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']);
                exit;
            }
            
            try {
                $stmt = $shop_pdo->prepare("
                    INSERT INTO missions (titre, description, type_id, nombre_taches, recompense_euros, 
                                        recompense_points, priorite, date_fin, statut, actif, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 1, NOW())
                ");
                $stmt->execute([$titre, $description, $type_id, $nombre_taches, $recompense_euros, $recompense_points, $priorite, $date_fin]);
                
                echo json_encode(['success' => true, 'message' => 'Mission créée avec succès !']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
            }
            break;
            
        case 'valider_tache':
            $validation_id = (int) $_POST['validation_id'];
            $action = $_POST['validation_action'];
            
            try {
                if ($action === 'approuver') {
                    $stmt = $shop_pdo->prepare("UPDATE mission_validations SET statut = 'approuve', date_validation_admin = NOW(), admin_id = ? WHERE id = ?");
                    $stmt->execute([$user_id, $validation_id]);
                    
                    // Vérifier si mission complète et créditer la cagnotte
                    $stmt = $shop_pdo->prepare("
                        SELECT um.id, um.user_id, m.nombre_taches, m.recompense_euros, m.recompense_points, m.id as mission_id,
                               (SELECT COUNT(*) FROM mission_validations mv WHERE mv.user_mission_id = um.id AND mv.statut = 'approuve') as taches_approuvees
                        FROM mission_validations mv
                        JOIN user_missions um ON mv.user_mission_id = um.id
                        JOIN missions m ON um.mission_id = m.id
                        WHERE mv.id = ?
                    ");
                    $stmt->execute([$validation_id]);
                    $mission_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($mission_data && $mission_data['taches_approuvees'] >= $mission_data['nombre_taches']) {
                        // Marquer la mission comme complète
                        $stmt = $shop_pdo->prepare("UPDATE user_missions SET statut = 'complete', date_completion = NOW() WHERE id = ?");
                        $stmt->execute([$mission_data['id']]);
                        
                        // Créditer la cagnotte
                        $stmt = $shop_pdo->prepare("UPDATE users SET cagnotte = cagnotte + ? WHERE id = ?");
                        $stmt->execute([$mission_data['recompense_euros'], $mission_data['user_id']]);
                        
                        // Enregistrer l'historique
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO cagnotte_historique (user_id, montant, type, description, mission_id, admin_id)
                            VALUES (?, ?, 'credit', ?, ?, ?)
                        ");
                        $stmt->execute([
                            $mission_data['user_id'], 
                            $mission_data['recompense_euros'], 
                            'Mission terminée: ' . $mission_data['mission_id'],
                            $mission_data['mission_id'],
                            $user_id
                        ]);
                        
                        echo json_encode(['success' => true, 'message' => 'Tâche approuvée ! Mission complétée et cagnotte créditée !']);
                    } else {
                        echo json_encode(['success' => true, 'message' => 'Tâche approuvée avec succès !']);
                    }
                } else {
                    $stmt = $shop_pdo->prepare("UPDATE mission_validations SET statut = 'rejete', date_validation_admin = NOW(), admin_id = ? WHERE id = ?");
                    $stmt->execute([$user_id, $validation_id]);
                    echo json_encode(['success' => true, 'message' => 'Tâche rejetée']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
            }
            break;
            
        case 'modifier_cagnotte':
            $user_id_target = (int) $_POST['user_id'];
            $montant = (float) $_POST['montant'];
            $type = $_POST['type']; // 'credit' ou 'debit'
            $description = trim($_POST['description']);
            
            if (empty($description)) {
                echo json_encode(['success' => false, 'message' => 'La description est obligatoire']);
                exit;
            }
            
            try {
                $shop_pdo->beginTransaction();
                
                // Modifier la cagnotte
                if ($type === 'credit') {
                    $stmt = $shop_pdo->prepare("UPDATE users SET cagnotte = cagnotte + ? WHERE id = ?");
                } else {
                    $stmt = $shop_pdo->prepare("UPDATE users SET cagnotte = GREATEST(0, cagnotte - ?) WHERE id = ?");
                }
                $stmt->execute([$montant, $user_id_target]);
                
                // Enregistrer l'historique
                $stmt = $shop_pdo->prepare("
                    INSERT INTO cagnotte_historique (user_id, montant, type, description, admin_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id_target, $montant, $type, $description, $user_id]);
                
                $shop_pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Cagnotte mise à jour avec succès !']);
            } catch (Exception $e) {
                $shop_pdo->rollback();
                echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
            }
            break;
    }
    exit;
}

// Récupération des données
try {
    // Statistiques
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM missions WHERE actif = 1");
    $stmt->execute();
    $total_missions = $stmt->fetchColumn();
    
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM user_missions WHERE statut = 'en_cours'");
    $stmt->execute();
    $missions_en_cours = $stmt->fetchColumn();
    
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM user_missions WHERE statut = 'complete' AND MONTH(date_completion) = MONTH(NOW())");
    $stmt->execute();
    $missions_completees_mois = $stmt->fetchColumn();
    
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM mission_validations WHERE statut = 'en_attente'");
    $stmt->execute();
    $validations_en_attente = $stmt->fetchColumn();
    
    // Toutes les missions
    $stmt = $shop_pdo->prepare("
        SELECT m.*, mt.nom as type_nom, mt.couleur,
               (SELECT COUNT(*) FROM user_missions um WHERE um.mission_id = m.id) as participants
        FROM missions m
        JOIN mission_types mt ON m.type_id = mt.id
        WHERE m.actif = 1
        ORDER BY m.created_at DESC
    ");
    $stmt->execute();
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Validations en attente
    $stmt = $shop_pdo->prepare("
        SELECT mv.*, m.titre as mission_titre, u.full_name as user_nom
        FROM mission_validations mv
        JOIN user_missions um ON mv.user_mission_id = um.id
        JOIN missions m ON um.mission_id = m.id
        JOIN users u ON um.user_id = u.id
        WHERE mv.statut = 'en_attente'
        ORDER BY mv.date_validation DESC
    ");
    $stmt->execute();
    $validations_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Types de missions
    $stmt = $shop_pdo->prepare("SELECT * FROM mission_types ORDER BY nom");
    $stmt->execute();
    $types_missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Employés avec cagnottes
    $stmt = $shop_pdo->prepare("SELECT id, full_name, cagnotte FROM users WHERE role IN ('admin', 'technicien') ORDER BY full_name");
    $stmt->execute();
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des données: " . $e->getMessage());
    $total_missions = $missions_en_cours = $missions_completees_mois = $validations_en_attente = 0;
    $missions = $validations_attente = $types_missions = $employes = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration des Missions - GeekBoard</title>
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

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-content h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        .stat-content p {
            margin: 0;
            color: #6b7280;
            font-size: 0.9rem;
        }

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
        }

        .filter-tab.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .filter-tab i {
            margin-right: 0.5rem;
        }

        .filter-badge {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }

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
            margin-bottom: 1rem;
        }

        .mission-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0 0 0.5rem 0;
        }

        .mission-card-body {
            padding: 1.5rem;
        }

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
            margin: 0.25rem;
        }

        .btn-modern i {
            margin-right: 0.5rem;
        }

        .btn-modern.btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-modern.btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-modern.btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .validation-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
        }

        .validation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .validation-photo {
            max-width: 200px;
            border-radius: 10px;
            margin: 1rem 0;
        }

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

        .cagnotte-card {
            background: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cagnotte-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cagnotte-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .cagnotte-details h5 {
            margin: 0;
            font-size: 1rem;
            color: var(--dark-color);
        }

        .cagnotte-amount {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--success-color);
        }

        .cagnotte-actions {
            display: flex;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .missions-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="modern-header">
        <div class="container">
            <a href="index.php" class="header-brand">
                <i class="fas fa-shield-alt"></i>
                Administration des Missions
            </a>
            <div class="header-user">
                <h3>Bonjour, <?php echo htmlspecialchars($user_name); ?> !</h3>
                <p>Gérez et supervisez les missions de votre équipe</p>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--primary-color);">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_missions; ?></h3>
                    <p>Missions actives</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--warning-color);">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $missions_en_cours; ?></h3>
                    <p>En cours</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--success-color);">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $missions_completees_mois; ?></h3>
                    <p>Complétées ce mois</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--danger-color);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $validations_en_attente; ?></h3>
                    <p>En attente validation</p>
                </div>
            </div>
        </div>

        <div class="filters-container">
            <div class="filters-tabs">
                <a href="#gestion" class="filter-tab active" data-tab="gestion">
                    <i class="fas fa-cogs"></i>
                    Gestion
                    <span class="filter-badge"><?php echo count($missions); ?></span>
                </a>
                <a href="#validations" class="filter-tab" data-tab="validations">
                    <i class="fas fa-check-circle"></i>
                    Validations
                    <span class="filter-badge"><?php echo count($validations_attente); ?></span>
                </a>
                <a href="#cagnottes" class="filter-tab" data-tab="cagnottes">
                    <i class="fas fa-wallet"></i>
                    Cagnottes
                    <span class="filter-badge"><?php echo count($employes); ?></span>
                </a>
            </div>
        </div>

        <!-- Onglet Gestion -->
        <div id="gestion" class="tab-content active">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="color: white; margin: 0;">Gestion des Missions</h2>
                <button class="btn-modern btn-primary" data-bs-toggle="modal" data-bs-target="#createMissionModal">
                    <i class="fas fa-plus"></i>
                    Créer une mission
                </button>
            </div>
            
            <?php if (empty($missions)): ?>
            <div class="empty-state">
                <i class="fas fa-tasks"></i>
                <h3>Aucune mission créée</h3>
                <p>Créez votre première mission pour commencer !</p>
            </div>
            <?php else: ?>
            <div class="missions-grid">
                <?php foreach ($missions as $mission): ?>
                <div class="mission-card">
                    <div class="mission-card-header">
                        <div class="mission-type" style="background-color: <?php echo htmlspecialchars($mission['couleur']); ?>">
                            <?php echo htmlspecialchars($mission['type_nom']); ?>
                        </div>
                        <h3 class="mission-title"><?php echo htmlspecialchars($mission['titre']); ?></h3>
                        <p style="color: #6b7280; margin: 0;"><?php echo htmlspecialchars($mission['description']); ?></p>
                    </div>
                    <div class="mission-card-body">
                        <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                            <div style="flex: 1; text-align: center; padding: 0.5rem; background: var(--light-color); border-radius: 10px;">
                                <div style="font-size: 1.2rem; font-weight: 700; color: var(--dark-color);"><?php echo $mission['participants']; ?></div>
                                <div style="font-size: 0.8rem; color: #6b7280;">Participants</div>
                            </div>
                            <div style="flex: 1; text-align: center; padding: 0.5rem; background: var(--light-color); border-radius: 10px;">
                                <div style="font-size: 1.2rem; font-weight: 700; color: var(--dark-color);"><?php echo $mission['nombre_taches']; ?></div>
                                <div style="font-size: 0.8rem; color: #6b7280;">Tâches</div>
                            </div>
                            <div style="flex: 1; text-align: center; padding: 0.5rem; background: var(--light-color); border-radius: 10px;">
                                <div style="font-size: 1.2rem; font-weight: 700; color: var(--success-color);"><?php echo number_format($mission['recompense_euros'], 2); ?> €</div>
                                <div style="font-size: 0.8rem; color: #6b7280;">Récompense</div>
                            </div>
                        </div>
                        <div style="text-align: center;">
                            <button class="btn-modern btn-danger" onclick="desactiverMission(<?php echo $mission['id']; ?>)">
                                <i class="fas fa-ban"></i>
                                Désactiver
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Onglet Validations -->
        <div id="validations" class="tab-content">
            <h2 style="color: white; margin-bottom: 2rem;">Validations en Attente</h2>
            
            <?php if (empty($validations_attente)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>Aucune validation en attente</h3>
                <p>Toutes les tâches ont été validées !</p>
            </div>
            <?php else: ?>
            <?php foreach ($validations_attente as $validation): ?>
            <div class="validation-card">
                <div class="validation-header">
                    <div>
                        <h4 style="margin: 0; color: var(--dark-color);"><?php echo htmlspecialchars($validation['mission_titre']); ?></h4>
                        <small style="color: #6b7280;">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($validation['user_nom']); ?>
                            <i class="fas fa-clock ms-2"></i> <?php echo date('d/m/Y H:i', strtotime($validation['date_validation'])); ?>
                        </small>
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>Description:</strong>
                    <p style="margin: 0.5rem 0;"><?php echo htmlspecialchars($validation['description']); ?></p>
                </div>
                <?php if ($validation['photo_url']): ?>
                <div style="margin-bottom: 1rem;">
                    <strong>Photo:</strong><br>
                    <img src="<?php echo htmlspecialchars($validation['photo_url']); ?>" class="validation-photo" alt="Photo de validation">
                </div>
                <?php endif; ?>
                <div>
                    <button class="btn-modern btn-success" onclick="validerTache(<?php echo $validation['id']; ?>, 'approuver')">
                        <i class="fas fa-check"></i>
                        Approuver
                    </button>
                    <button class="btn-modern btn-danger" onclick="validerTache(<?php echo $validation['id']; ?>, 'rejeter')">
                        <i class="fas fa-times"></i>
                        Rejeter
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Onglet Cagnottes -->
        <div id="cagnottes" class="tab-content">
            <h2 style="color: white; margin-bottom: 2rem;">Gestion des Cagnottes</h2>
            
            <?php if (empty($employes)): ?>
            <div class="empty-state">
                <i class="fas fa-wallet"></i>
                <h3>Aucun employé trouvé</h3>
                <p>Aucun employé dans la base de données.</p>
            </div>
            <?php else: ?>
            <?php foreach ($employes as $employe): ?>
            <div class="cagnotte-card">
                <div class="cagnotte-info">
                    <div class="cagnotte-avatar">
                        <?php echo strtoupper(substr($employe['full_name'], 0, 2)); ?>
                    </div>
                    <div class="cagnotte-details">
                        <h5><?php echo htmlspecialchars($employe['full_name']); ?></h5>
                        <div class="cagnotte-amount"><?php echo number_format($employe['cagnotte'], 2, ',', ' '); ?> €</div>
                    </div>
                </div>
                <div class="cagnotte-actions">
                    <button class="btn-modern btn-success" onclick="modifierCagnotte(<?php echo $employe['id']; ?>, 'credit')">
                        <i class="fas fa-plus"></i>
                        Créditer
                    </button>
                    <button class="btn-modern btn-danger" onclick="modifierCagnotte(<?php echo $employe['id']; ?>, 'debit')">
                        <i class="fas fa-minus"></i>
                        Débiter
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal création mission -->
    <div class="modal fade" id="createMissionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        Créer une nouvelle mission
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createMissionForm">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="titre" class="form-label">Titre de la mission *</label>
                                    <input type="text" class="form-control" id="titre" name="titre" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description *</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="type_id" class="form-label">Type *</label>
                                    <select class="form-select" id="type_id" name="type_id" required>
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($types_missions as $type): ?>
                                        <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['nom']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="priorite" class="form-label">Priorité *</label>
                                    <select class="form-select" id="priorite" name="priorite" required>
                                        <option value="1">Basse</option>
                                        <option value="2">Normale</option>
                                        <option value="3" selected>Élevée</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="nombre_taches" class="form-label">Nb tâches *</label>
                                    <input type="number" class="form-control" id="nombre_taches" name="nombre_taches" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="recompense_euros" class="form-label">Récompense (€) *</label>
                                    <input type="number" class="form-control" id="recompense_euros" name="recompense_euros" min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="recompense_points" class="form-label">Points XP *</label>
                                    <input type="number" class="form-control" id="recompense_points" name="recompense_points" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="date_fin" class="form-label">Date limite</label>
                                    <input type="date" class="form-control" id="date_fin" name="date_fin">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="creerMission()">
                        <i class="fas fa-plus"></i>
                        Créer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal modification cagnotte -->
    <div class="modal fade" id="cagnotteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cagnotteModalLabel">
                        <i class="fas fa-wallet me-2"></i>
                        Modifier la cagnotte
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="cagnotteForm">
                        <input type="hidden" id="cagnotteUserId" name="user_id">
                        <input type="hidden" id="cagnotteType" name="type">
                        <div class="mb-3">
                            <label for="cagnotteMontant" class="form-label">Montant (€) *</label>
                            <input type="number" class="form-control" id="cagnotteMontant" name="montant" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="cagnotteDescription" class="form-label">Description *</label>
                            <textarea class="form-control" id="cagnotteDescription" name="description" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="soumettreModificationCagnotte()">
                        <i class="fas fa-check"></i>
                        Confirmer
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
                    
                    filterTabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });

        function creerMission() {
            const form = document.getElementById('createMissionForm');
            const formData = new FormData(form);
            formData.append('action', 'creer_mission');
            
            fetch('admin_missions_new.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('createMissionModal')).hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message, 'error');
                }
            });
        }

        function validerTache(validationId, action) {
            fetch('admin_missions_new.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=valider_tache&validation_id=${validationId}&validation_action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message, 'error');
                }
            });
        }

        function modifierCagnotte(userId, type) {
            document.getElementById('cagnotteUserId').value = userId;
            document.getElementById('cagnotteType').value = type;
            document.getElementById('cagnotteModalLabel').innerHTML = 
                `<i class="fas fa-wallet me-2"></i>${type === 'credit' ? 'Créditer' : 'Débiter'} la cagnotte`;
            
            new bootstrap.Modal(document.getElementById('cagnotteModal')).show();
        }

        function soumettreModificationCagnotte() {
            const form = document.getElementById('cagnotteForm');
            const formData = new FormData(form);
            formData.append('action', 'modifier_cagnotte');
            
            fetch('admin_missions_new.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('cagnotteModal')).hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message, 'error');
                }
            });
        }

        function desactiverMission(missionId) {
            if (confirm('Êtes-vous sûr de vouloir désactiver cette mission ?')) {
                fetch('admin_missions_new.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=desactiver_mission&mission_id=${missionId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
            }
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html> 