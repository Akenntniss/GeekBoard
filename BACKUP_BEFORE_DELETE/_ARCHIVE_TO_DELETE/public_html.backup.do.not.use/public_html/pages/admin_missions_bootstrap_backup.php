<?php
// Vérification des droits d'accès admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    set_message("Accès refusé. Vous devez être administrateur pour accéder à cette page.", "error");
    redirect('accueil');
}

$shop_pdo = getShopDBConnection();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Créer une nouvelle mission
    if ($_POST['action'] === 'create_mission') {
        $titre = cleanInput($_POST['titre']);
        $description = cleanInput($_POST['description']);
        $mission_type_id = (int)$_POST['mission_type_id'];
        $objectif_nombre = (int)$_POST['objectif_nombre'];
        $periode_jours = (int)$_POST['periode_jours'];
        $recompense_euros = (float)$_POST['recompense_euros'];
        $recompense_points = (int)$_POST['recompense_points'];
        $date_debut = cleanInput($_POST['date_debut']);
        $date_fin = cleanInput($_POST['date_fin']);
        
        $errors = [];
        if (empty($titre)) $errors[] = "Le titre est obligatoire.";
        if (empty($description)) $errors[] = "La description est obligatoire.";
        if ($objectif_nombre <= 0) $errors[] = "L'objectif doit être supérieur à 0.";
        if ($periode_jours <= 0) $errors[] = "La période doit être supérieure à 0.";
        
        if (empty($errors)) {
            try {
                $stmt = $shop_pdo->prepare("
                    INSERT INTO missions (titre, description, mission_type_id, objectif_nombre, periode_jours, 
                                        recompense_euros, recompense_points, date_debut, date_fin, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $titre, $description, $mission_type_id, $objectif_nombre, $periode_jours,
                    $recompense_euros, $recompense_points, $date_debut ?: null, $date_fin ?: null, $_SESSION['user_id']
                ]);
                set_message("Mission créée avec succès !", "success");
            } catch (PDOException $e) {
                set_message("Erreur lors de la création de la mission: " . $e->getMessage(), "error");
            }
        } else {
            set_message(implode('<br>', $errors), "error");
        }
    }
    
    // Valider une tâche d'employé
    if ($_POST['action'] === 'validate_task') {
        $validation_id = (int)$_POST['validation_id'];
        $approve = isset($_POST['approve']);
        $commentaire = cleanInput($_POST['commentaire']);
        
        try {
            if ($approve) {
                // Approuver la validation
                $stmt = $shop_pdo->prepare("
                    UPDATE mission_validations 
                    SET validee = 1, validee_par = ?, commentaire_validation = ?, validated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $commentaire, $validation_id]);
                
                // Vérifier si la mission est maintenant complète et verser la récompense
                $stmt = $shop_pdo->prepare("
                    SELECT mv.user_mission_id, um.user_id, um.mission_id, m.objectif_nombre, 
                           COUNT(mv2.id) as validations_approuvees, m.recompense_euros, m.recompense_points
                    FROM mission_validations mv
                    JOIN user_missions um ON mv.user_mission_id = um.id
                    JOIN missions m ON um.mission_id = m.id
                    LEFT JOIN mission_validations mv2 ON um.id = mv2.user_mission_id AND mv2.validee = 1
                    WHERE mv.id = ?
                    GROUP BY mv.user_mission_id
                ");
                $stmt->execute([$validation_id]);
                $mission_info = $stmt->fetch();
                
                if ($mission_info && $mission_info['validations_approuvees'] >= $mission_info['objectif_nombre']) {
                    // Mission complète - verser la récompense
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO mission_recompenses (user_id, mission_id, user_mission_id, montant_euros, points_attribues, attribuee_par, commentaire)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $mission_info['user_id'], $mission_info['mission_id'], $mission_info['user_mission_id'],
                        $mission_info['recompense_euros'], $mission_info['recompense_points'], 
                        $_SESSION['user_id'], "Récompense automatique pour mission complétée"
                    ]);
                    
                    // Marquer la récompense comme attribuée
                    $stmt = $shop_pdo->prepare("UPDATE user_missions SET recompense_attribuee = 1 WHERE id = ?");
                    $stmt->execute([$mission_info['user_mission_id']]);
                }
                
                set_message("Tâche validée avec succès !", "success");
            } else {
                // Rejeter la validation
                $stmt = $shop_pdo->prepare("
                    UPDATE mission_validations 
                    SET validee = 0, validee_par = ?, commentaire_validation = ?, validated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $commentaire, $validation_id]);
                set_message("Tâche rejetée.", "warning");
            }
        } catch (PDOException $e) {
            set_message("Erreur lors de la validation: " . $e->getMessage(), "error");
        }
    }
    
    // Modifier le statut d'une mission
    if ($_POST['action'] === 'toggle_mission_status') {
        $mission_id = (int)$_POST['mission_id'];
        $new_status = cleanInput($_POST['new_status']);
        
        try {
            $stmt = $shop_pdo->prepare("UPDATE missions SET statut = ? WHERE id = ?");
            $stmt->execute([$new_status, $mission_id]);
            set_message("Statut de la mission mis à jour !", "success");
        } catch (PDOException $e) {
            set_message("Erreur lors de la mise à jour: " . $e->getMessage(), "error");
        }
    }
    
    redirect('admin_missions');
}

// Récupération des types de missions
try {
    $stmt = $shop_pdo->query("SELECT * FROM mission_types ORDER BY nom");
    $mission_types = $stmt->fetchAll();
} catch (PDOException $e) {
    $mission_types = [];
}

// Récupération des missions avec statistiques
try {
    $stmt = $shop_pdo->query("
        SELECT m.*, mt.nom as type_nom, mt.icon, mt.couleur,
               COUNT(DISTINCT um.user_id) as participants,
               COUNT(CASE WHEN um.statut = 'complete' THEN 1 END) as completions,
               SUM(CASE WHEN um.statut = 'complete' THEN m.recompense_euros ELSE 0 END) as total_recompenses,
               COUNT(CASE WHEN mv.validee IS NULL THEN 1 END) as validations_en_attente
        FROM missions m
        LEFT JOIN mission_types mt ON m.mission_type_id = mt.id
        LEFT JOIN user_missions um ON m.id = um.mission_id
        LEFT JOIN mission_validations mv ON um.id = mv.user_mission_id
        GROUP BY m.id
        ORDER BY m.created_at DESC
    ");
    $missions = $stmt->fetchAll();
} catch (PDOException $e) {
    $missions = [];
    set_message("Erreur lors de la récupération des missions.", "error");
}

// Récupération des validations en attente
try {
    $stmt = $shop_pdo->query("
        SELECT mv.*, u.full_name, m.titre as mission_titre, mt.nom as type_nom,
               um.progression_actuelle, m.objectif_nombre
        FROM mission_validations mv
        JOIN users u ON mv.user_id = u.id
        JOIN missions m ON mv.mission_id = m.id
        JOIN mission_types mt ON m.mission_type_id = mt.id
        JOIN user_missions um ON mv.user_mission_id = um.id
        WHERE mv.validee IS NULL
        ORDER BY mv.created_at ASC
    ");
    $validations_en_attente = $stmt->fetchAll();
} catch (PDOException $e) {
    $validations_en_attente = [];
}

// Statistiques globales
try {
    $stmt = $shop_pdo->query("
        SELECT 
            COUNT(DISTINCT m.id) as total_missions,
            COUNT(DISTINCT um.user_id) as total_participants,
            COUNT(CASE WHEN um.statut = 'complete' THEN 1 END) as missions_completees,
            SUM(CASE WHEN mr.montant_euros IS NOT NULL THEN mr.montant_euros ELSE 0 END) as total_primes_versees,
            COUNT(CASE WHEN mv.validee IS NULL THEN 1 END) as validations_en_attente
        FROM missions m
        LEFT JOIN user_missions um ON m.id = um.mission_id
        LEFT JOIN mission_recompenses mr ON um.id = mr.user_mission_id
        LEFT JOIN mission_validations mv ON um.id = mv.user_mission_id
    ");
    $stats = $stmt->fetch() ?: [
        'total_missions' => 0, 'total_participants' => 0, 'missions_completees' => 0,
        'total_primes_versees' => 0, 'validations_en_attente' => 0
    ];
} catch (PDOException $e) {
    $stats = ['total_missions' => 0, 'total_participants' => 0, 'missions_completees' => 0, 'total_primes_versees' => 0, 'validations_en_attente' => 0];
}
?>

<div class="container-fluid">
    <!-- En-tête avec statistiques -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h1 class="mb-2">
                                <i class="fas fa-cogs me-2"></i>
                                Administration des Missions
                            </h1>
                            <p class="mb-0 opacity-75">Gérez les missions, validez les tâches et suivez les performances</p>
                        </div>
                        <div class="col-md-6">
                            <div class="row g-2 text-center">
                                <div class="col-4">
                                    <div class="h4 mb-0"><?= $stats['total_missions'] ?></div>
                                    <small>Missions</small>
                                </div>
                                <div class="col-4">
                                    <div class="h4 mb-0"><?= $stats['total_participants'] ?></div>
                                    <small>Participants</small>
                                </div>
                                <div class="col-4">
                                    <div class="h4 mb-0"><?= $stats['missions_completees'] ?></div>
                                    <small>Complétées</small>
                                </div>
                                <div class="col-6">
                                    <div class="h4 mb-0"><?= number_format($stats['total_primes_versees'], 2) ?>€</div>
                                    <small>Primes versées</small>
                                </div>
                                <div class="col-6">
                                    <div class="h4 mb-0">
                                        <?= $stats['validations_en_attente'] ?>
                                        <?php if ($stats['validations_en_attente'] > 0): ?>
                                            <i class="fas fa-exclamation-circle text-warning ms-1"></i>
                                        <?php endif; ?>
                                    </div>
                                    <small>En attente</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation par onglets -->
    <ul class="nav nav-pills nav-fill mb-4" id="adminTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="missions-tab" data-bs-toggle="pill" data-bs-target="#missions" type="button" role="tab">
                <i class="fas fa-list me-2"></i>Missions <span class="badge bg-primary ms-1"><?= count($missions) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="validations-tab" data-bs-toggle="pill" data-bs-target="#validations" type="button" role="tab">
                <i class="fas fa-check-circle me-2"></i>Validations 
                <span class="badge bg-warning ms-1"><?= count($validations_en_attente) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="create-tab" data-bs-toggle="pill" data-bs-target="#create" type="button" role="tab">
                <i class="fas fa-plus me-2"></i>Nouvelle Mission
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stats-tab" data-bs-toggle="pill" data-bs-target="#stats" type="button" role="tab">
                <i class="fas fa-chart-bar me-2"></i>Statistiques
            </button>
        </li>
    </ul>

    <!-- Contenu des onglets -->
    <div class="tab-content" id="adminTabContent">
        
        <!-- Onglet Missions -->
        <div class="tab-pane fade show active" id="missions" role="tabpanel">
            <?php if (empty($missions)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                    <h4>Aucune mission créée</h4>
                    <p class="text-muted">Créez votre première mission dans l'onglet "Nouvelle Mission"</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($missions as $mission): ?>
                        <div class="col-lg-6 mb-4">
                            <?php include 'components/admin_mission_card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Onglet Validations -->
        <div class="tab-pane fade" id="validations" role="tabpanel">
            <?php if (empty($validations_en_attente)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h4>Aucune validation en attente</h4>
                    <p class="text-muted">Toutes les tâches ont été traitées !</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($validations_en_attente as $validation): ?>
                        <div class="col-lg-6 mb-4">
                            <?php include 'components/admin_validation_card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Onglet Création -->
        <div class="tab-pane fade" id="create" role="tabpanel">
            <?php include 'components/admin_create_mission_form.php'; ?>
        </div>

        <!-- Onglet Statistiques -->
        <div class="tab-pane fade" id="stats" role="tabpanel">
            <?php include 'components/admin_mission_stats.php'; ?>
        </div>
    </div>
</div>

<style>
.nav-pills .nav-link {
    border-radius: 8px;
    padding: 12px 20px;
    margin: 0 5px;
    transition: all 0.3s ease;
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.badge {
    font-size: 0.7rem;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}
</style> 