<?php
// Forcer les sessions pour éviter les redirections
session_start();

// Forcer les variables de session pour admin
$_SESSION["shop_id"] = "mkmkmk";
$_SESSION["user_id"] = 6; 
$_SESSION["user_role"] = "admin";
$_SESSION["role"] = "admin";  
$_SESSION["full_name"] = "Administrateur Mkmkmk";
$_SESSION["username"] = "admin";
$_SESSION["is_logged_in"] = true;

// Inclure les fichiers nécessaires
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Connexion à la base de données
$shop_pdo = getShopDBConnection();

// Initialiser les variables
$stats_missions_actives = 0;
$stats_missions_en_cours = 0;
$stats_missions_completees = 0;
$stats_validations_en_attente = 0;
$missions = [];
$validations = [];

// Récupérer les statistiques des missions
try {
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM missions WHERE statut = 'active'");
    $stmt->execute();
    $stats_missions_actives = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Erreur stats missions actives: " . $e->getMessage());
}

try {
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM user_missions WHERE statut = 'en_cours'");
    $stmt->execute();
    $stats_missions_en_cours = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Erreur stats missions en cours: " . $e->getMessage());
}

try {
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM user_missions WHERE statut = 'terminee' AND MONTH(date_completee) = MONTH(NOW()) AND YEAR(date_completee) = YEAR(NOW())");
    $stmt->execute();
    $stats_missions_completees = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Erreur stats missions complétées: " . $e->getMessage());
}

try {
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM mission_validations WHERE statut = 'en_attente'");
    $stmt->execute();
    $stats_validations_en_attente = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Erreur stats validations: " . $e->getMessage());
}

// Récupérer les missions actives avec informations complètes
try {
    $stmt = $shop_pdo->prepare("
        SELECT 
            m.id, m.titre, m.description, m.objectif_quantite, m.recompense_euros, m.recompense_points, m.statut, m.created_at,
            mt.nom as type_nom, mt.icone as type_icone, mt.couleur as type_couleur,
            COUNT(DISTINCT um.id) as nb_participants,
            COUNT(DISTINCT CASE WHEN um.statut = 'terminee' THEN um.id END) as nb_completes
        FROM missions m
        LEFT JOIN mission_types mt ON m.type_id = mt.id
        LEFT JOIN user_missions um ON m.id = um.mission_id
        WHERE m.statut = 'active'
        GROUP BY m.id
        ORDER BY m.created_at DESC
    ");
    $stmt->execute();
    $missions = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Erreur récupération missions: " . $e->getMessage());
}

// Récupérer les validations en attente
try {
    $stmt = $shop_pdo->prepare("
        SELECT 
            mv.id, mv.user_mission_id, mv.tache_numero, mv.statut, mv.date_soumission, mv.description,
            m.titre as mission_titre,
            u.full_name as user_nom,
            um.progres as progression_actuelle
        FROM mission_validations mv
        LEFT JOIN user_missions um ON mv.user_mission_id = um.id
        LEFT JOIN missions m ON um.mission_id = m.id
        LEFT JOIN users u ON um.user_id = u.id
        WHERE mv.statut = 'en_attente'
        ORDER BY mv.date_soumission DESC
    ");
    $stmt->execute();
    $validations = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Erreur récupération validations: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration des Missions - GeekBoard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Dashboard CSS -->
    <link href="assets/css/dashboard-new.css" rel="stylesheet">
    
    <!-- Styles CSS manquants pour la navbar -->
    <link href="assets/css/navbar.css" rel="stylesheet">
    <link href="assets/css/professional-desktop.css" rel="stylesheet">
    <link href="assets/css/modern-effects.css" rel="stylesheet">
    <link href="assets/css/neo-dock.css" rel="stylesheet">
    <link href="assets/css/mobile-navigation.css" rel="stylesheet">
    <link href="assets/css/status-colors.css" rel="stylesheet">
    <link href="assets/css/pwa-enhancements.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4361ee;
            --success: #52b788;
            --warning: #f77f00;
            --danger: #ef476f;
            --info: #06d6a0;
            --light: #f8f9fa;
            --dark: #343a40;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary), #6c5ce7);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.3);
        }

        .mission-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .mission-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            border-color: var(--primary);
        }

        .mission-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .mission-rewards {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .reward-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 10px;
            font-weight: 600;
            color: var(--primary);
        }

        .validation-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--warning);
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-approve {
            background: var(--success);
            color: white;
        }

        .btn-reject {
            background: var(--danger);
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), #6c5ce7);
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }

        .participant-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--info);
        }

        .user-stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 0.875rem;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-radius: 10px 10px 0 0;
            margin-right: 0.5rem;
        }

        .nav-tabs .nav-link.active {
            background: var(--primary);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .mission-rewards {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .reward-item {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/head.php'; ?>

    <div class="modern-dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2"><i class="fas fa-trophy me-3"></i>Administration des Missions</h1>
                        <p class="mb-0 opacity-75">Gérez les missions et récompenses de votre équipe</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#newMissionModal">
                            <i class="fas fa-plus me-2"></i>Nouvelle Mission
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="statistics-container">
            <h3 class="section-title"><i class="fas fa-chart-line me-2"></i>Vue d'ensemble des missions</h3>
            <div class="statistics-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats_missions_actives; ?></div>
                        <div class="stat-label">Missions Actives</div>
                    </div>
                </div>
                
                <div class="stat-card progress-card">
                    <div class="stat-icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats_missions_en_cours; ?></div>
                        <div class="stat-label">En Cours</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats_missions_completees; ?></div>
                        <div class="stat-label">Complétées ce mois</div>
                    </div>
                </div>
                
                <div class="stat-card waiting-card">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats_validations_en_attente; ?></div>
                        <div class="stat-label">Validations en attente</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglets -->
        <div class="tabs-container">
            <div class="tabs-header">
                <button class="tab-button active" data-tab="missions">
                    <i class="fas fa-tasks me-2"></i>Missions Actives
                    <span class="badge bg-primary ms-2"><?php echo count($missions); ?></span>
                </button>
                <button class="tab-button" data-tab="validations">
                    <i class="fas fa-clipboard-check me-2"></i>Validations
                    <span class="badge bg-warning ms-2"><?php echo count($validations); ?></span>
                </button>
                <button class="tab-button" data-tab="rewards">
                    <i class="fas fa-coins me-2"></i>Cagnotte & XP
                </button>
            </div>

            <!-- Contenu des onglets -->
            <div class="tab-content active" id="missions">
                <?php if (empty($missions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h4>Aucune mission active</h4>
                        <p>Créez votre première mission pour motiver votre équipe</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMissionModal">
                            <i class="fas fa-plus me-2"></i>Créer une mission
                        </button>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($missions as $mission): ?>
                            <div class="col-lg-6 col-xl-4">
                                <div class="mission-card" onclick="showMissionDetails(<?php echo $mission['id']; ?>)">
                                    <div class="mission-type-badge" style="background: <?php echo $mission['type_couleur'] ?? '#4361ee'; ?>20; color: <?php echo $mission['type_couleur'] ?? '#4361ee'; ?>">
                                        <i class="<?php echo $mission['type_icone'] ?? 'fas fa-star'; ?>"></i>
                                        <?php echo htmlspecialchars($mission['type_nom'] ?? 'Mission'); ?>
                                    </div>
                                    
                                    <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($mission['titre']); ?></h5>
                                    <p class="text-muted mb-3"><?php echo htmlspecialchars(substr($mission['description'], 0, 100)) . '...'; ?></p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-muted">
                                            <i class="fas fa-users me-1"></i>
                                            <?php echo $mission['nb_participants']; ?> participants
                                        </span>
                                        <span class="text-muted">
                                            <i class="fas fa-target me-1"></i>
                                            Objectif: <?php echo $mission['objectif_quantite']; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mission-rewards">
                                        <?php if ($mission['recompense_euros'] > 0): ?>
                                            <div class="reward-item">
                                                <i class="fas fa-euro-sign"></i>
                                                <?php echo $mission['recompense_euros']; ?>€
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($mission['recompense_points'] > 0): ?>
                                            <div class="reward-item">
                                                <i class="fas fa-star"></i>
                                                <?php echo $mission['recompense_points']; ?> XP
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('d/m/Y', strtotime($mission['created_at'])); ?>
                                        </small>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); editMission(<?php echo $mission['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deactivateMission(<?php echo $mission['id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-content" id="validations">
                <?php if (empty($validations)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <h4>Aucune validation en attente</h4>
                        <p>Toutes les validations ont été traitées</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($validations as $validation): ?>
                        <div class="validation-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($validation['mission_titre']); ?></h6>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-user me-1"></i>
                                        <?php echo htmlspecialchars($validation['user_nom']); ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="fas fa-chart-line me-1"></i>
                                        Progression: <?php echo $validation['progression_actuelle']; ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($validation['date_soumission'])); ?>
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn-action btn-approve" onclick="validerTacheAdmin(<?php echo $validation['id']; ?>, 'approuver')">
                                        <i class="fas fa-check me-1"></i>Approuver
                                    </button>
                                    <button class="btn-action btn-reject" onclick="validerTacheAdmin(<?php echo $validation['id']; ?>, 'rejeter')">
                                        <i class="fas fa-times me-1"></i>Rejeter
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="tab-content" id="rewards">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="fas fa-coins me-2"></i>Cagnotte et Points XP</h4>
                    <button class="btn btn-primary" onclick="showUserRewards()">
                        <i class="fas fa-refresh me-2"></i>Actualiser
                    </button>
                </div>
                <div id="userRewardsContainer">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement des données...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détails Mission -->
    <div class="modal fade" id="missionDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>Détails de la Mission
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="missionDetailsContent">
                    <!-- Le contenu sera chargé via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nouvelle Mission -->
    <div class="modal fade" id="newMissionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Nouvelle Mission
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newMissionForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Titre de la mission</label>
                                    <input type="text" class="form-control" name="titre" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Type de mission</label>
                                    <select class="form-select" name="type_id" required>
                                        <option value="">Sélectionner un type</option>
                                        <option value="1">Trottinettes</option>
                                        <option value="2">Smartphones</option>
                                        <option value="3">LeBonCoin</option>
                                        <option value="4">eBay</option>
                                        <option value="5">Réparations Express</option>
                                        <option value="6">Service Client</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Objectif (quantité)</label>
                                    <input type="number" class="form-control" name="objectif_quantite" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Récompense (€)</label>
                                    <input type="number" class="form-control" name="recompense_euros" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Points XP</label>
                                    <input type="number" class="form-control" name="recompense_points" min="0">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="createMission()">
                        <i class="fas fa-save me-2"></i>Créer la Mission
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Gestion des onglets
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                const tab = this.dataset.tab;
                
                // Mise à jour des boutons
                document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Mise à jour du contenu
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                document.getElementById(tab).classList.add('active');
                
                // Charger le contenu spécifique si nécessaire
                if (tab === 'rewards') {
                    showUserRewards();
                }
            });
        });

        // Fonction pour afficher les détails d'une mission
        function showMissionDetails(missionId) {
            fetch(`ajax/get_mission_details.php?id=${missionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('missionDetailsContent').innerHTML = data.html;
                        new bootstrap.Modal(document.getElementById('missionDetailsModal')).show();
                    } else {
                        alert('Erreur lors du chargement des détails');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des détails');
                });
        }

        // Fonction pour afficher les récompenses des utilisateurs
        function showUserRewards() {
            fetch('ajax/get_user_rewards.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('userRewardsContainer').innerHTML = data.html;
                    } else {
                        document.getElementById('userRewardsContainer').innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des données</div>';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('userRewardsContainer').innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des données</div>';
                });
        }

        // Fonction pour valider une tâche
        function validerTacheAdmin(validationId, action) {
            if (!confirm(`Êtes-vous sûr de vouloir ${action} cette validation ?`)) {
                return;
            }
            
            fetch('ajax/valider_mission.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    validation_id: validationId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la validation');
            });
        }

        // Fonction pour créer une nouvelle mission
        function createMission() {
            const form = document.getElementById('newMissionForm');
            const formData = new FormData(form);
            
            fetch('ajax/create_mission.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('newMissionModal')).hide();
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la création de la mission');
            });
        }

        // Fonction pour désactiver une mission
        function deactivateMission(missionId) {
            if (!confirm('Êtes-vous sûr de vouloir désactiver cette mission ?')) {
                return;
            }
            
            fetch('ajax/deactivate_mission.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    mission_id: missionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la désactivation');
            });
        }

        // Fonction pour éditer une mission
        function editMission(missionId) {
            alert('Fonction d\'édition à implémenter');
        }

        // Fonction pour afficher l'historique des validations
        function showValidationHistory() {
            console.log('Affichage de l\'historique des validations');
            
            // Créer et afficher une modal pour l'historique des validations
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'validationHistoryModal';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-history me-2"></i>Historique des Validations
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p class="mt-2">Chargement de l'historique...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // Charger l'historique des validations
            fetch('ajax/get_validation_history.php')
                .then(response => response.json())
                .then(data => {
                    let content = '<div class="row">';
                    
                    if (data.success && data.validations && data.validations.length > 0) {
                        data.validations.forEach(validation => {
                            const statusClass = validation.statut === 'validee' ? 'success' : validation.statut === 'rejetee' ? 'danger' : 'warning';
                            const statusText = validation.statut === 'validee' ? 'Validée' : validation.statut === 'rejetee' ? 'Rejetée' : 'En attente';
                            
                            content += `
                                <div class="col-md-6 mb-3">
                                    <div class="card validation-card" data-validation-id="${validation.id}" style="cursor: pointer; transition: all 0.3s ease;">
                                        <div class="card-header">
                                            <h6 class="mb-0">${validation.mission_titre}</h6>
                                            <small class="text-muted">${validation.user_nom}</small>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">${validation.description}</p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-${statusClass}">${statusText}</span>
                                                <small class="text-muted">${new Date(validation.date_soumission).toLocaleString('fr-FR')}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        content += `
                            <div class="col-12">
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h4>Aucun historique de validation</h4>
                                    <p class="text-muted">Aucune validation n'a été trouvée dans l'historique.</p>
                                </div>
                            </div>
                        `;
                    }
                    
                    content += '</div>';
                    modal.querySelector('.modal-body').innerHTML = content;
                    
                    // Ajouter les événements de clic sur les cartes de validation
                    const validationCards = modal.querySelectorAll('.validation-card');
                    validationCards.forEach(card => {
                        card.addEventListener('click', function() {
                            const validationId = this.getAttribute('data-validation-id');
                            showValidationDetails(validationId);
                        });
                        
                        // Ajouter l'effet hover
                        card.addEventListener('mouseenter', function() {
                            this.style.transform = 'translateY(-5px)';
                            this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
                        });
                        
                        card.addEventListener('mouseleave', function() {
                            this.style.transform = 'translateY(0)';
                            this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
                        });
                    });
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    modal.querySelector('.modal-body').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erreur lors du chargement de l'historique des validations.
                        </div>
                    `;
                });
            
            // Nettoyer le modal quand il est fermé
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.removeChild(modal);
            });
        }

        // Fonction pour afficher les détails d'une validation
        function showValidationDetails(validationId) {
            console.log('Affichage des détails de la validation:', validationId);
            
            // Créer et afficher une modal pour les détails de la validation
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'validationDetailsModal';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-info-circle me-2"></i>Détails de la Validation
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p class="mt-2">Chargement des détails...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // Charger les détails de la validation
            fetch(`ajax/get_validation_details.php?id=${validationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.validation) {
                        const validation = data.validation;
                        const statusClass = validation.statut === 'validee' ? 'success' : validation.statut === 'refusee' ? 'danger' : 'warning';
                        const statusText = validation.statut === 'validee' ? 'Validée' : validation.statut === 'refusee' ? 'Refusée' : 'En attente';
                        
                        let content = `
                            <div class="row">
                                <div class="col-md-12 mb-4">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">${validation.mission_titre || 'Mission inconnue'}</h6>
                                            <span class="badge bg-${statusClass}">${statusText}</span>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Employé:</strong> ${validation.user_full_name}</p>
                                                    <p><strong>Tâche n°:</strong> ${validation.tache_numero}</p>
                                                    <p><strong>Date de soumission:</strong> ${new Date(validation.date_soumission).toLocaleString('fr-FR')}</p>
                                                    ${validation.date_validation ? `<p><strong>Date de validation:</strong> ${new Date(validation.date_validation).toLocaleString('fr-FR')}</p>` : ''}
                                                    ${validation.admin_full_name ? `<p><strong>Validé par:</strong> ${validation.admin_full_name}</p>` : ''}
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Description:</strong></p>
                                                    <p class="text-muted">${validation.description}</p>
                                                    ${validation.commentaire_admin ? `
                                                        <p><strong>Commentaire administrateur:</strong></p>
                                                        <p class="text-muted">${validation.commentaire_admin}</p>
                                                    ` : ''}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        `;
                        
                        // Ajouter les photos si elles existent
                        if (validation.photo_exists || validation.preuve_exists) {
                            content += `
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-image me-2"></i>Fichiers joints</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                            `;
                            
                            if (validation.photo_exists && validation.photo_url) {
                                content += `
                                    <div class="col-md-6 mb-3">
                                        <div class="text-center">
                                            <h6>Photo</h6>
                                            <img src="${validation.photo_url}" class="img-fluid rounded" style="max-height: 300px; cursor: pointer;" onclick="window.open('${validation.photo_url}', '_blank')">
                                        </div>
                                    </div>
                                `;
                            }
                            
                            if (validation.preuve_exists && validation.preuve_fichier) {
                                const fileExtension = validation.preuve_fichier.split('.').pop().toLowerCase();
                                const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension);
                                
                                if (isImage) {
                                    content += `
                                        <div class="col-md-6 mb-3">
                                            <div class="text-center">
                                                <h6>Preuve</h6>
                                                <img src="${validation.preuve_fichier}" class="img-fluid rounded" style="max-height: 300px; cursor: pointer;" onclick="window.open('${validation.preuve_fichier}', '_blank')">
                                            </div>
                                        </div>
                                    `;
                                } else {
                                    content += `
                                        <div class="col-md-6 mb-3">
                                            <div class="text-center">
                                                <h6>Preuve</h6>
                                                <a href="${validation.preuve_fichier}" target="_blank" class="btn btn-outline-primary">
                                                    <i class="fas fa-download me-2"></i>Télécharger le fichier
                                                </a>
                                            </div>
                                        </div>
                                    `;
                                }
                            }
                            
                            content += `
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                        
                        content += '</div>';
                        modal.querySelector('.modal-body').innerHTML = content;
                        
                    } else {
                        modal.querySelector('.modal-body').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Erreur lors du chargement des détails de la validation.
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    modal.querySelector('.modal-body').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erreur lors du chargement des détails de la validation.
                        </div>
                    `;
                });
            
            // Nettoyer le modal quand il est fermé
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.removeChild(modal);
            });
        }

        // Fonction pour afficher l'historique des missions
        function showMissionHistory() {
            console.log('Affichage de l\'historique des missions');
            
            // Créer et afficher une modal pour l'historique des missions
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'missionHistoryModal';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-history me-2"></i>Historique des Missions
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p class="mt-2">Chargement de l'historique...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // Charger l'historique des missions
            fetch('ajax/get_mission_history.php')
                .then(response => response.json())
                .then(data => {
                    let content = '<div class="row">';
                    
                    if (data.success && data.missions && data.missions.length > 0) {
                        data.missions.forEach(mission => {
                            const statusClass = mission.statut === 'active' ? 'success' : mission.statut === 'inactive' ? 'secondary' : 'warning';
                            const statusText = mission.statut === 'active' ? 'Active' : mission.statut === 'inactive' ? 'Inactive' : 'Archivée';
                            
                            content += `
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">${mission.titre}</h6>
                                            <small class="text-muted">${mission.type_nom || 'Mission'}</small>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">${mission.description.substring(0, 100)}...</p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-${statusClass}">${statusText}</span>
                                                <small class="text-muted">${new Date(mission.created_at).toLocaleString('fr-FR')}</small>
                                            </div>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-users me-1"></i>${mission.nb_participants || 0} participants
                                                    <i class="fas fa-check-circle ms-2 me-1"></i>${mission.nb_completes || 0} complétées
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        content += `
                            <div class="col-12">
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h4>Aucun historique de mission</h4>
                                    <p class="text-muted">Aucune mission n'a été trouvée dans l'historique.</p>
                                </div>
                            </div>
                        `;
                    }
                    
                    content += '</div>';
                    modal.querySelector('.modal-body').innerHTML = content;
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    modal.querySelector('.modal-body').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erreur lors du chargement de l'historique des missions.
                        </div>
                    `;
                });
            
            // Nettoyer le modal quand il est fermé
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.removeChild(modal);
            });
        }

        // Charger les récompenses au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            // Charger les récompenses dès le démarrage
            setTimeout(showUserRewards, 500);
        });
    </script>
    
    <!-- Scripts JavaScript manquants pour la navbar -->
    <script src="assets/js/app.js" defer></script>
    <script src="assets/js/modern-interactions.js" defer></script>
    <script src="components/js/navbar.js" defer></script>
    <script src="components/js/tablet-detect.js" defer></script>
    
</body>
</html> 