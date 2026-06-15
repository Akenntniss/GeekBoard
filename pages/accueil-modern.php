<?php
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'accueil-modern.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=accueil-modern');
    exit();
}

// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT
// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT DÉPLACÉE DANS INDEX.PHP
require_once __DIR__ . '/../includes/notification_functions.php';

// Fonction pour obtenir la couleur en fonction de la priorité
function get_priority_color($priority) {
    switch(strtolower($priority)) {
        case 'haute':
            return 'danger';
        case 'moyenne':
            return 'warning';
        case 'basse':
            return 'info';
        default:
            return 'secondary';
    }
}

// Récupérer les statistiques pour le tableau de bord (avec cache APCu léger)
$cache_key = 'dashboard_quick_' . ($_SESSION['shop_id'] ?? 'default');
$use_cache = function_exists('apcu_exists') && function_exists('apcu_fetch') && function_exists('apcu_store');

// Essayer le cache d'abord (1 minute seulement)
if ($use_cache && apcu_exists($cache_key)) {
    $cached_data = apcu_fetch($cache_key);
    if ($cached_data && is_array($cached_data)) {
        extract($cached_data);
    } else {
        $use_cache = false; // Cache corrompu, désactiver
    }
}

// Si pas de cache ou cache expiré, récupérer normalement
if (!$use_cache || !isset($reparations_stats_categorie)) {
    $reparations_stats_categorie = get_reparations_count_by_status_categorie();
    $reparations_en_attente = $reparations_stats_categorie['en_attente'];
    $reparations_en_cours = $reparations_stats_categorie['en_cours'];
    $reparations_nouvelles = $reparations_stats_categorie['nouvelles'];
    $reparations_actives = count_active_reparations();

    $total_clients = get_total_clients();
    $taches_recentes_count = get_taches_recentes_count();
    $reparations_recentes = get_recent_reparations(5);
    $reparations_recentes_count = count_recent_reparations();
    $taches = get_taches_en_cours(5);

    // Mettre en cache pour 1 minute seulement
    if ($use_cache) {
        try {
            apcu_store($cache_key, compact(
                'reparations_stats_categorie', 'reparations_en_attente', 'reparations_en_cours',
                'reparations_nouvelles', 'reparations_actives', 'total_clients', 'taches_recentes_count',
                'reparations_recentes', 'reparations_recentes_count', 'taches'
            ), 60);
        } catch (Exception $e) {
            // Ignorer les erreurs de cache
        }
    }
}

// Récupérer les commandes récentes et leur compteur
$commandes_recentes = [];
$commandes_en_attente_count = 0;
try {
    $shop_pdo = getShopDBConnection();

    // Compter les commandes en attente
    $stmt_count = $shop_pdo->query("
        SELECT COUNT(*) as count
        FROM commandes_pieces
        WHERE statut IN ('en_attente', 'urgent')
    ");
    $commandes_en_attente_count = $stmt_count->fetch()['count'];

    // Récupérer les commandes récentes
    $stmt = $shop_pdo->query("
        SELECT c.*, cl.nom as client_nom, cl.prenom as client_prenom, f.nom as fournisseur_nom
        FROM commandes_pieces c
        LEFT JOIN clients cl ON c.client_id = cl.id
        LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id
        WHERE c.statut IN ('en_attente', 'urgent')
        ORDER BY c.date_creation DESC
        LIMIT 5
    ");
    $commandes_recentes = $stmt->fetchAll();
} catch (PDOException $e) {
    // Gérer l'erreur silencieusement
}

// Récupérer les statistiques journalières
function get_daily_stats($date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }

    try {
        $shop_pdo = getShopDBConnection();

        // Nouvelles réparations du jour (toutes les réparations créées aujourd'hui, peu importe leur statut actuel)
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count
            FROM reparations
            WHERE DATE(date_reception) = ?
        ");
        $stmt->execute([$date]);
        $nouvelles_reparations = $stmt->fetchColumn();

        // Réparations effectuées du jour (réparations qui ont changé vers le statut "effectué" aujourd'hui)
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count
            FROM reparations
            WHERE DATE(date_modification) = ?
            AND (statut = 'reparation_effectue' OR statut_categorie = 4)
            AND DATE(date_reception) != ?
        ");
        $stmt->execute([$date, $date]);
        $reparations_effectuees_modifiees = $stmt->fetchColumn();

        // Ajouter les réparations créées ET terminées le même jour
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count
            FROM reparations
            WHERE DATE(date_reception) = ?
            AND (statut = 'reparation_effectue' OR statut_categorie = 4)
        ");
        $stmt->execute([$date]);
        $reparations_effectuees_nouvelles = $stmt->fetchColumn();

        $reparations_effectuees = $reparations_effectuees_modifiees + $reparations_effectuees_nouvelles;

        // Réparations restituées du jour (réparations qui ont changé vers le statut "restitué" aujourd'hui)
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count
            FROM reparations
            WHERE DATE(date_modification) = ?
            AND statut = 'restitue'
            AND DATE(date_reception) != ?
        ");
        $stmt->execute([$date, $date]);
        $reparations_restituees_modifiees = $stmt->fetchColumn();

        // Ajouter les réparations créées ET restituées le même jour
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count
            FROM reparations
            WHERE DATE(date_reception) = ?
            AND statut = 'restitue'
        ");
        $stmt->execute([$date]);
        $reparations_restituees_nouvelles = $stmt->fetchColumn();

        $reparations_restituees = $reparations_restituees_modifiees + $reparations_restituees_nouvelles;

        // Devis envoyés du jour
        $devis_envoyes = 0;
        try {
            $stmt = $shop_pdo->prepare("
                SELECT COUNT(*) as count
                FROM devis
                WHERE DATE(date_envoi) = ? AND statut = 'envoye'
            ");
            $stmt->execute([$date]);
            $devis_envoyes = $stmt->fetchColumn();
        } catch (PDOException $e) {
            // Table devis n'existe peut-être pas encore
            $devis_envoyes = 0;
        }

        return [
            'nouvelles_reparations' => $nouvelles_reparations ?: 0,
            'reparations_effectuees' => $reparations_effectuees ?: 0,
            'reparations_restituees' => $reparations_restituees ?: 0,
            'devis_envoyes' => $devis_envoyes ?: 0,
            'date' => $date
        ];

    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques journalières: " . $e->getMessage());
        return [
            'nouvelles_reparations' => 0,
            'reparations_effectuees' => 0,
            'reparations_restituees' => 0,
            'devis_envoyes' => 0,
            'date' => $date
        ];
    }
}

$stats_journalieres = get_daily_stats();

// Récupérer le statut des employés/techniciens
function get_employee_status() {
    try {
        $shop_pdo = getShopDBConnection();

        // Récupérer tous les utilisateurs EN LIGNE avec leur techbusy status et réparations actives
        $stmt = $shop_pdo->query("
            SELECT
                u.id as user_id,
                u.full_name as user_name,
                u.role,
                u.is_online,
                u.techbusy,
                u.active_repair_id,
                u.isActiveTask,
                u.activetaskid,
                r.id as reparation_id,
                r.modele as model,
                r.description_probleme as probleme,
                r.date_reception,
                r.statut,
                c.nom as client_nom,
                c.prenom as client_prenom,
                (SELECT rl.date_action
                 FROM reparation_logs rl
                 WHERE rl.reparation_id = r.id
                   AND rl.action_type IN ('demarrage', 'changement_statut')
                 ORDER BY rl.date_action DESC
                 LIMIT 1) as dernier_changement_statut,
                (SELECT tl.created_at
                 FROM Task_logs tl
                 WHERE tl.task_id = u.activetaskid
                   AND tl.user_id = u.id
                   AND tl.action_type = 'start'
                 ORDER BY tl.created_at DESC
                 LIMIT 1) as task_start_time
            FROM users u
            LEFT JOIN reparations r ON (
                (u.techbusy = 1 AND u.active_repair_id = r.id) OR
                (u.techbusy = 0 AND u.id = r.employe_id AND r.statut IN ('en_cours', 'diagnostic', 'attente_piece', 'reparation_en_cours'))
            )
            LEFT JOIN clients c ON r.client_id = c.id
            WHERE u.is_online = 1
            ORDER BY u.full_name, r.date_reception DESC
        ");

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organiser les données par utilisateur
        $employee_status = [];
        foreach ($users as $row) {
            $user_id = $row['user_id'];

            if (!isset($employee_status[$user_id])) {
                // Déterminer le statut basé sur techbusy et tâches actives
                $statut = 'disponible';
                $task_elapsed_time = '';
                $task_time_color = '';

                if ($row['isActiveTask'] == 1 && $row['activetaskid']) {
                    $statut = 'tache_active';

                    // Calculer le temps écoulé depuis le démarrage de la tâche
                    if ($row['task_start_time']) {
                        $date_start = new DateTime($row['task_start_time']);
                        $now = new DateTime();
                        $interval = $date_start->diff($now);

                        // Calculer le nombre total de minutes
                        $total_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

                        // Déterminer la couleur en fonction du temps
                        if ($total_minutes <= 30) {
                            $task_time_color = 'time-green';
                        } elseif ($total_minutes <= 45) {
                            $task_time_color = 'time-orange';
                        } else {
                            $task_time_color = 'time-red';
                        }

                        if ($interval->days > 0) {
                            $task_elapsed_time = $interval->days . 'j ';
                        }
                        $task_elapsed_time .= $interval->h . 'h ' . $interval->i . 'm';
                    }
                } elseif ($row['techbusy'] == 1 && $row['active_repair_id']) {
                    $statut = 'en_reparation';
                } elseif ($row['reparation_id']) {
                    $statut = 'en cours d\'intervention';
                }

                $employee_status[$user_id] = [
                    'nom' => $row['user_name'],
                    'poste' => ucfirst($row['role']),
                    'statut' => $statut,
                    'techbusy' => $row['techbusy'],
                    'active_repair_id' => $row['active_repair_id'],
                    'isActiveTask' => $row['isActiveTask'],
                    'activetaskid' => $row['activetaskid'],
                    'task_elapsed_time' => $task_elapsed_time,
                    'task_time_color' => $task_time_color,
                    'reparations' => []
                ];
            }

            if ($row['reparation_id']) {
                // Calculer le temps écoulé depuis le dernier changement de statut
                $temps_passe = '';
                if ($row['dernier_changement_statut']) {
                    $date_changement = new DateTime($row['dernier_changement_statut']);
                    $now = new DateTime();
                    $interval = $date_changement->diff($now);

                    if ($interval->days > 0) {
                        $temps_passe = $interval->days . 'j ';
                    }
                    $temps_passe .= $interval->h . 'h ' . $interval->i . 'm';
                } else {
                    // Fallback sur date_reception si pas de log
                    $date_reception = new DateTime($row['date_reception']);
                    $now = new DateTime();
                    $interval = $date_reception->diff($now);

                    if ($interval->days > 0) {
                        $temps_passe = $interval->days . 'j ';
                    }
                    $temps_passe .= $interval->h . 'h ' . $interval->i . 'm';
                }

                $employee_status[$user_id]['reparations'][] = [
                    'id' => $row['reparation_id'],
                    'model' => $row['model'] ?: 'N/A',
                    'probleme' => $row['probleme'] ?: 'N/A',
                    'temps_passe' => $temps_passe,
                    'client' => $row['client_nom'] . ' ' . $row['client_prenom']
                ];
            }
        }

        return $employee_status;

    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du statut des employés: " . $e->getMessage());
        return [];
    }
}

$employee_status = get_employee_status();
?>

<link rel="stylesheet" href="assets/css/variables.css">
<link rel="stylesheet" href="assets/css/pages/dashboard-modern.css?v=<?php echo time(); ?>">

<!-- Basculeur de thème -->
<!-- Toggle retiré - Mode automatique selon système -->

<!-- Container de particules (mode nuit) -->
<div class="particles-container" id="particles"></div>

<div class="modern-dashboard bg-animated" id="dashboard">

    <?php
    // Badge de notification flottant pour mobile
    $unread_count = count_unread_notifications($_SESSION['user_id']);
    ?>
    <a href="index.php?page=notifications" class="mobile-floating-notif">
        <div class="notif-icon">
            <i class="fas fa-bell"></i>
        </div>
        <?php if ($unread_count > 0): ?>
            <span class="unread-badge"><?php echo $unread_count; ?></span>
        <?php endif; ?>
    </a>

    <!-- 🚀 BOUTONS D'ACTIONS EN HAUT -->
    <!-- 🚀 NOUVEAUX BOUTONS D'ACTION MODERNES -->
    <div class="modern-action-grid fade-in">
        <a href="#" class="modern-action-card task-card" data-bs-toggle="modal" data-bs-target="#ajouterTacheModal" onclick="event.preventDefault();">
            <div class="modern-action-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="modern-action-content">
                <h3 class="modern-action-title">Nouvelle Tâche</h3>
                <p class="modern-action-desc">Créer une nouvelle tâche</p>
            </div>
            <div class="modern-action-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>

        <a href="index.php?page=ajouter_reparation" class="modern-action-card repair-card">
            <div class="modern-action-icon">
                <i class="fas fa-tools"></i>
            </div>
            <div class="modern-action-content">
                <h3 class="modern-action-title">Nouvelle Réparation</h3>
                <p class="modern-action-desc">Enregistrer une nouvelle réparation</p>
            </div>
            <div class="modern-action-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>

        <a href="#" class="modern-action-card order-card" data-bs-toggle="modal" data-bs-target="#ajouterCommandeModal">
            <div class="modern-action-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="modern-action-content">
                <h3 class="modern-action-title">Nouvelle Commande</h3>
                <p class="modern-action-desc">Commander une nouvelle pièce</p>
            </div>
            <div class="modern-action-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>
    </div>

    <!-- 📊 STATISTIQUES -->
    <!-- 📊 NOUVEAU DESIGN - ÉTAT DES RÉPARATIONS -->
    <div class="status-overview-section fade-in">
        <h3 class="status-section-title">État des Réparations</h3>
        <div class="quick-stats-bar">
            <!-- Rechercher -->
            <a href="#" class="quick-stat-btn" data-color="blue" onclick="ouvrirRechercheModerne(); return false;" data-bs-toggle="tooltip" title="Rechercher un client / une reparation / une commande">
                <div class="quick-stat-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="quick-stat-label">Rechercher</div>
            </a>

            <!-- Réparations -->
            <a href="index.php?page=reparations&statut_ids=1,2,3,19,20" class="quick-stat-btn" data-color="purple">
                <div class="quick-stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="quick-stat-count"><?php echo $reparations_actives; ?></div>
                <div class="quick-stat-label">Réparations</div>
            </a>

            <!-- Tâches -->
            <a href="index.php?page=taches" class="quick-stat-btn" data-color="green">
                <div class="quick-stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="quick-stat-count"><?php echo $taches_recentes_count; ?></div>
                <div class="quick-stat-label">Tâches</div>
            </a>

            <!-- Commandes -->
            <a href="index.php?page=commandes_pieces" class="quick-stat-btn" data-color="orange">
                <div class="quick-stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="quick-stat-count"><?php echo $commandes_en_attente_count; ?></div>
                <div class="quick-stat-label">Commandes</div>
            </a>
        </div>
    </div>

    <!-- 📋 TABLEAUX -->
    <div class="tables-container fade-in">
        <!-- Tableau 1: Tâches en cours -->
        <div class="table-section">
            <div class="table-header">
                <i class="fas fa-tasks"></i>
                <h4><a href="index.php?page=taches" style="text-decoration: none; color: inherit;">Tâches en cours</a></h4>
                <span class="badge"><?php echo $taches_recentes_count; ?></span>
            </div>

            <!-- Onglets pour les tâches -->
            <div class="modern-tabs" style="padding: 1rem; border-bottom: 1px solid var(--day-border);">
                <button class="modern-tab-button active" data-tab="toutes-taches" onclick="switchTab('toutes-taches')">Toutes</button>
                <button class="modern-tab-button" data-tab="mes-taches" onclick="switchTab('mes-taches')">Mes tâches</button>
            </div>

            <div class="table-content">
                <!-- Contenu onglet "Toutes les tâches" -->
                <div class="tab-content active" id="toutes-taches">
                    <?php
                    $toutes_taches = get_toutes_taches_en_cours(10);
                    if (!empty($toutes_taches)): ?>
                        <?php foreach ($toutes_taches as $tache):
                            $urgence_class = get_urgence_class($tache['urgence']);
                        ?>
                            <div class="table-row modern-table-row" data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                <div class="row-indicator taches"></div>
                                <div class="row-content">
                                    <div class="row-title modern-table-text"><?php echo htmlspecialchars($tache['titre']); ?></div>
                                    <div class="row-subtitle"><?php echo htmlspecialchars(substr($tache['description'] ?? '', 0, 50)) . '...'; ?></div>
                                </div>
                                <div class="row-meta">
                                    <div class="priority-badge modern-badge <?php echo strtolower($tache['urgence']); ?>">
                                        <?php echo htmlspecialchars($tache['urgence']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="table-empty">
                            <i class="fas fa-tasks"></i>
                            <div class="title">Aucune tâche en cours</div>
                            <div>Toutes les tâches ont été complétées</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Contenu onglet "Mes tâches" -->
                <div class="tab-content" id="mes-taches">
                    <?php if (!empty($taches)): ?>
                        <?php foreach ($taches as $tache):
                            $urgence_class = get_urgence_class($tache['urgence']);
                        ?>
                            <div class="table-row modern-table-row" data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                <div class="row-indicator taches"></div>
                                <div class="row-content">
                                    <div class="row-title modern-table-text"><?php echo htmlspecialchars($tache['titre']); ?></div>
                                    <div class="row-subtitle"><?php echo htmlspecialchars(substr($tache['description'] ?? '', 0, 50)) . '...'; ?></div>
                                </div>
                                <div class="row-meta">
                                    <div class="priority-badge modern-badge <?php echo strtolower($tache['urgence']); ?>">
                                        <?php echo htmlspecialchars($tache['urgence']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="table-empty">
                            <i class="fas fa-tasks"></i>
                            <div class="title">Aucune tâche</div>
                            <div>Toutes les tâches sont terminées</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tableau 2: Réparations récentes -->
        <div class="table-section">
            <div class="table-header">
                <i class="fas fa-wrench"></i>
                <h4><a href="index.php?page=reparations" style="text-decoration: none; color: inherit;">Réparations récentes</a></h4>
                <span class="badge"><?php echo $reparations_recentes_count; ?></span>
            </div>
            <!-- Onglets pour les réparations (même logique que Tâches) -->
            <div class="modern-tabs" style="padding: 1rem; border-bottom: 1px solid var(--day-border);">
                <button class="modern-tab-button active" data-tab="toutes-reparations" onclick="switchTab('toutes-reparations')">Toutes</button>
                <button class="modern-tab-button" data-tab="mes-reparations" onclick="switchTab('mes-reparations')">Mes réparations</button>
            </div>
            <div class="table-content">
                <!-- Contenu onglet "Toutes les réparations" -->
                <div class="tab-content active" id="toutes-reparations">
                    <?php
                    $toutes_repairs = !empty($reparations_recentes) ? $reparations_recentes : [];
                    if (!empty($toutes_repairs)): ?>
                        <?php foreach ($toutes_repairs as $reparation): ?>
                            <div class="table-row" onclick="window.location.href='index.php?page=reparations&open_modal=<?php echo $reparation['id']; ?>'">
                                <div class="row-indicator reparations"></div>
                                <div class="row-content">
                                    <div class="row-title"><?php echo htmlspecialchars($reparation['client_nom'] ?? 'N/A'); ?></div>
                                    <div class="row-subtitle"><?php echo htmlspecialchars($reparation['modele'] ?? ''); ?></div>
                                    <div class="row-problem">
                                        <?php
                                        $probleme = $reparation['description_probleme'] ?? '';
                                        echo htmlspecialchars(strlen($probleme) > 60 ? substr($probleme, 0, 60) . '...' : $probleme);
                                        ?>
                                    </div>
                                </div>
                                <div class="row-meta">
                                    <div class="date-badge"><?php echo date('d/m', strtotime($reparation['date_reception'] ?? 'now')); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="table-empty">
                            <i class="fas fa-wrench"></i>
                            <div class="title">Aucune réparation</div>
                            <div>Pas de réparations en cours</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Contenu onglet "Mes réparations" (filtré par utilisateur connecté) -->
                <div class="tab-content" id="mes-reparations">
                    <?php
                    $current_user_id = $_SESSION['user_id'] ?? null;
                    $mes_repairs = [];
                    if ($current_user_id && !empty($reparations_recentes)) {
                        foreach ($reparations_recentes as $rep) {
                            if ((int)($rep['employe_id'] ?? 0) === (int)$current_user_id) { $mes_repairs[] = $rep; }
                        }
                    }
                    if (!empty($mes_repairs)): ?>
                        <?php foreach ($mes_repairs as $reparation): ?>
                            <div class="table-row" onclick="window.location.href='index.php?page=reparations&open_modal=<?php echo $reparation['id']; ?>'">
                                <div class="row-indicator reparations"></div>
                                <div class="row-content">
                                    <div class="row-title"><?php echo htmlspecialchars($reparation['client_nom'] ?? 'N/A'); ?></div>
                                    <div class="row-subtitle"><?php echo htmlspecialchars($reparation['modele'] ?? ''); ?></div>
                                    <div class="row-problem">
                                        <?php
                                        $probleme = $reparation['description_probleme'] ?? '';
                                        echo htmlspecialchars(strlen($probleme) > 60 ? substr($probleme, 0, 60) . '...' : $probleme);
                                        ?>
                                    </div>
                                </div>
                                <div class="row-meta">
                                    <div class="date-badge"><?php echo date('d/m', strtotime($reparation['date_reception'] ?? 'now')); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="table-empty">
                            <i class="fas fa-user"></i>
                            <div class="title">Aucune de mes réparations</div>
                            <div>Vous n'avez pas de réparations récentes assignées</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tableau 3: Commandes récentes -->
        <div class="table-section">
            <div class="table-header">
                <i class="fas fa-shopping-cart"></i>
                <h4><a href="index.php?page=commandes_pieces" style="text-decoration: none; color: inherit;">Commandes récentes</a></h4>
                <span class="badge"><?php echo count($commandes_recentes); ?></span>
            </div>
            <div class="table-content">
                <?php if (!empty($commandes_recentes)): ?>
                    <?php foreach ($commandes_recentes as $commande): ?>
                        <div class="table-row" data-commande-id="<?php echo $commande['id']; ?>" onclick="ouvrirModalStatut(event, <?php echo $commande['id']; ?>, '<?php echo $commande['statut']; ?>', '<?php echo htmlspecialchars($commande['reference'] ?? 'REF-' . $commande['id']); ?>', '<?php echo htmlspecialchars($commande['nom_piece']); ?>')">
                            <div class="row-indicator commandes"></div>
                            <div class="row-content">
                                <div class="row-title"><?php echo htmlspecialchars($commande['nom_piece'] ?? 'Produit N/A'); ?></div>
                                <div class="row-subtitle"><?php echo htmlspecialchars($commande['fournisseur_nom'] ?? 'Fournisseur N/A'); ?></div>
                            </div>
                            <div class="row-meta">
                                <div class="date-badge">
                                    <?php echo date('d/m', strtotime($commande['date_creation'] ?? 'now')); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="table-empty">
                        <i class="fas fa-shopping-cart"></i>
                        <div class="title">Aucune commande</div>
                        <div>Pas de commandes en attente</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 📈 NOUVEAU DESIGN - STATISTIQUES DU JOUR (ADMIN UNIQUEMENT) -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <div class="daily-analytics-section mt-4 fade-in" id="dailyStatsSection" data-current-date="<?php echo date('Y-m-d'); ?>">
        <div class="daily-analytics-title-container">
            <button class="daily-stats-nav-btn" id="statsPrevDay" onclick="navigateDailyStats(-1)" title="Jour précédent">
                <i class="fas fa-chevron-left"></i>
            </button>
            <h3 class="daily-analytics-title">
                <span id="statsTitleText">Statistiques du jour</span>
                <span class="stats-date-label" id="statsDateLabel"></span>
            </h3>
            <button class="daily-stats-nav-btn" id="statsNextDay" onclick="navigateDailyStats(1)" title="Jour suivant" disabled>
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div class="daily-analytics-grid" id="dailyStatsGrid">
            <div class="daily-analytics-card new-repairs-card" onclick="openStatsModal('nouvelles_reparations')" style="cursor: pointer;">
                <div class="daily-analytics-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="daily-analytics-content">
                    <div class="daily-analytics-value"><?php echo $stats_journalieres['nouvelles_reparations']; ?></div>
                    <div class="daily-analytics-text">Nouvelles réparations</div>
                </div>
                <div class="daily-analytics-action">
                    <i class="fas fa-chart-bar"></i>
                </div>
            </div>

            <div class="daily-analytics-card completed-repairs-card" onclick="openStatsModal('reparations_effectuees')" style="cursor: pointer;">
                <div class="daily-analytics-icon">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="daily-analytics-content">
                    <div class="daily-analytics-value"><?php echo $stats_journalieres['reparations_effectuees']; ?></div>
                    <div class="daily-analytics-text">Réparations effectuées</div>
                </div>
                <div class="daily-analytics-action">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>

            <div class="daily-analytics-card returned-repairs-card" onclick="openStatsModal('reparations_restituees')" style="cursor: pointer;">
                <div class="daily-analytics-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="daily-analytics-content">
                    <div class="daily-analytics-value"><?php echo $stats_journalieres['reparations_restituees']; ?></div>
                    <div class="daily-analytics-text">Réparations restituées</div>
                </div>
                <div class="daily-analytics-action">
                    <i class="fas fa-chart-area"></i>
                </div>
            </div>

            <div class="daily-analytics-card quotes-sent-card" onclick="openStatsModal('devis_envoyes')" style="cursor: pointer;">
                <div class="daily-analytics-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="daily-analytics-content">
                    <div class="daily-analytics-value"><?php echo $stats_journalieres['devis_envoyes']; ?></div>
                    <div class="daily-analytics-text">Devis envoyés</div>
                </div>
                <div class="daily-analytics-action">
                    <i class="fas fa-chart-pie"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 👥 STATUT DES EMPLOYÉS (ADMIN UNIQUEMENT) -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <div class="employee-status-section mt-5 fade-in">
        <h3 class="employee-status-title">Statut des employés</h3>

        <div class="employee-status-table-container">
            <table class="employee-status-table">
                <thead>
                    <tr>
                        <th>Technicien</th>
                        <th>Statut</th>
                        <th>Temps</th>
                        <th>ID Réparation</th>
                        <th>Modèle</th>
                        <th>Problème</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($employee_status)): ?>
                        <?php foreach ($employee_status as $userId => $employee): ?>
                            <?php if (empty($employee['reparations'])): ?>
                                <!-- Employé disponible ou sur tâche -->
                                <tr class="employee-row <?php echo ($employee['isActiveTask'] == 1) ? 'busy' : 'available'; ?>">
                                    <td class="employee-name employee-name-clickable" onclick="openEmployeeActivityModal('<?php echo $userId; ?>', '<?php echo addslashes(htmlspecialchars($employee['nom'])); ?>')" style="cursor: pointer; color: #007bff; text-decoration: underline;">
                                        <?php echo htmlspecialchars($employee['nom']); ?>
                                    </td>
                                    <td class="employee-status <?php echo ($employee['isActiveTask'] == 1) ? 'busy' : 'available'; ?>">
                                        <span class="status-indicator <?php echo ($employee['isActiveTask'] == 1) ? 'busy' : 'available'; ?>"></span>
                                        <?php
                                        if ($employee['isActiveTask'] == 1 && $employee['activetaskid']) {
                                            echo '<span class="clickable-status" onclick="afficherDetailsTache(event, ' . htmlspecialchars($employee['activetaskid']) . ')" style="cursor: pointer; color: #007bff; text-decoration: underline;">📋 Tâche en cours : #' . htmlspecialchars($employee['activetaskid']) . '</span>';
                                        } else {
                                            echo 'Aucune activité pour le moment';
                                        }
                                        ?>
                                    </td>
                                    <td class="repair-time <?php echo ($employee['isActiveTask'] == 1 && !empty($employee['task_time_color'])) ? htmlspecialchars($employee['task_time_color']) : ''; ?>">
                                        <?php echo ($employee['isActiveTask'] == 1 && !empty($employee['task_elapsed_time'])) ? htmlspecialchars($employee['task_elapsed_time']) : '-'; ?>
                                    </td>
                                    <td class="repair-id">-</td>
                                    <td class="repair-model">-</td>
                                    <td class="repair-problem">-</td>
                                </tr>
                            <?php else: ?>
                                <!-- Employé avec réparations en cours -->
                                <?php foreach ($employee['reparations'] as $index => $reparation): ?>
                                    <tr class="employee-row busy">
                                        <?php if ($index === 0): ?>
                                            <td class="employee-name employee-name-clickable" rowspan="<?php echo count($employee['reparations']); ?>" onclick="openEmployeeActivityModal('<?php echo $userId; ?>', '<?php echo addslashes(htmlspecialchars($employee['nom'])); ?>')" style="cursor: pointer; color: #007bff; text-decoration: underline;">
                                                <?php echo htmlspecialchars($employee['nom']); ?>
                                            </td>
                                            <td class="employee-status <?php echo ($employee['statut'] == 'en_reparation') ? 'repairing' : 'busy'; ?>" rowspan="<?php echo count($employee['reparations']); ?>">
                                                <span class="status-indicator <?php echo ($employee['statut'] == 'en_reparation') ? 'repairing' : 'busy'; ?>"></span>
                                                <?php
                                                $firstRepairId = !empty($employee['reparations']) ? $employee['reparations'][0]['id'] : null;
                                                if ($employee['statut'] == 'en_reparation') {
                                                    echo '<span class="clickable-status" onclick="event.stopPropagation(); openRepairQuickInfo(' . htmlspecialchars($firstRepairId) . ');" style="cursor: pointer; color: #007bff; text-decoration: underline;">🔧 En réparation</span>';
                                                } else {
                                                    echo '<span class="clickable-status" onclick="event.stopPropagation(); openRepairQuickInfo(' . htmlspecialchars($firstRepairId) . ');" style="cursor: pointer; color: #007bff; text-decoration: underline;">Actif sur une réparation</span>';
                                                }
                                                ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="repair-time"><?php echo htmlspecialchars($reparation['temps_passe']); ?></td>
                                        <td class="repair-id">#<?php echo htmlspecialchars($reparation['id']); ?></td>
                                        <td class="repair-model"><?php echo htmlspecialchars($reparation['model']); ?></td>
                                        <td class="repair-problem"><?php echo htmlspecialchars(substr($reparation['probleme'], 0, 50)) . (strlen($reparation['probleme']) > 50 ? '...' : ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="no-data">
                            <td colspan="6">Aucun technicien trouvé</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Note: Le modal de statistiques est géré par le système existant via openStatsModal() -->

<script>
// ========================================
// NAVIGATION DES STATISTIQUES DU JOUR
// ========================================
let dailyStatsCurrentDate = new Date().toISOString().split('T')[0]; // Format YYYY-MM-DD

function navigateDailyStats(direction) {
    const section = document.getElementById('dailyStatsSection');
    if (!section) return;

    // Calculer la nouvelle date
    const currentDate = new Date(dailyStatsCurrentDate);
    currentDate.setDate(currentDate.getDate() + direction);
    const newDate = currentDate.toISOString().split('T')[0];

    // Vérifier qu'on ne dépasse pas aujourd'hui
    const today = new Date().toISOString().split('T')[0];
    if (newDate > today) return;

    // Mettre à jour la date courante
    dailyStatsCurrentDate = newDate;

    // Afficher un état de chargement
    const grid = document.getElementById('dailyStatsGrid');
    if (grid) {
        grid.style.opacity = '0.5';
        grid.style.pointerEvents = 'none';
    }

    // Faire la requête AJAX
    fetch(`ajax/get_daily_stats.php?date=${newDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDailyStatsUI(data, newDate, today);
            } else {
                console.error('Erreur lors de la récupération des statistiques:', data.error);
            }
        })
        .catch(error => {
            console.error('Erreur AJAX:', error);
        })
        .finally(() => {
            if (grid) {
                grid.style.opacity = '1';
                grid.style.pointerEvents = 'auto';
            }
        });
}

function updateDailyStatsUI(data, date, today) {
    // Mettre à jour les valeurs
    const cards = document.querySelectorAll('.daily-analytics-card');
    const values = [
        data.nouvelles_reparations,
        data.reparations_effectuees,
        data.reparations_restituees,
        data.devis_envoyes
    ];

    cards.forEach((card, index) => {
        const valueEl = card.querySelector('.daily-analytics-value');
        if (valueEl && values[index] !== undefined) {
            valueEl.textContent = values[index];
        }
    });

    // Mettre à jour le titre et le label de date
    const titleText = document.getElementById('statsTitleText');
    const dateLabel = document.getElementById('statsDateLabel');

    if (date === today) {
        if (titleText) titleText.textContent = "Statistiques du jour";
        if (dateLabel) dateLabel.textContent = '';
    } else {
        // Formater la date en français
        const dateObj = new Date(date);
        const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
        const formattedDate = dateObj.toLocaleDateString('fr-FR', options);

        if (titleText) titleText.textContent = "Statistiques";
        if (dateLabel) dateLabel.textContent = formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1);
    }

    // Mettre à jour l'état des boutons
    const prevBtn = document.getElementById('statsPrevDay');
    const nextBtn = document.getElementById('statsNextDay');

    if (nextBtn) {
        nextBtn.disabled = (date >= today);
    }
    // Le bouton précédent est toujours actif (on peut toujours remonter dans le passé)
    if (prevBtn) {
        prevBtn.disabled = false;
    }
}

// Initialiser la date courante au chargement
document.addEventListener('DOMContentLoaded', function() {
    const section = document.getElementById('dailyStatsSection');
    if (section) {
        dailyStatsCurrentDate = section.dataset.currentDate || new Date().toISOString().split('T')[0];
    }
});
</script>

<script>
// ========================================
// GESTION DU THÈME
// ========================================
// currentTheme handled later
// particlesCreated handled later

function setupModalListeners() {
    console.log('🎭 Configuration des écouteurs de modals');

    const modals = ['ajouterTacheModal', 'ajouterCommandeModal', 'taskDetailsModal'];

    modals.forEach(modalId => {
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            // Écouter l'ouverture du modal
            modalElement.addEventListener('shown.bs.modal', function() {
                console.log('🎭 Modal ouvert:', modalId);

                // Appliquer les styles selon le thème actuel
                setTimeout(() => {
                    if (currentTheme === 'night') {
                        forceModalsNightMode();
                    } else {
                        forceModalsDayMode();
                    }
                }, 50);
            });

            // Écouter quand le modal est sur le point de s'ouvrir
            modalElement.addEventListener('show.bs.modal', function() {
                console.log('🎭 Modal en cours d\'ouverture:', modalId);

                // Pré-appliquer les styles
                if (currentTheme === 'night') {
                    forceModalsNightMode();
                } else {
                    forceModalsDayMode();
                }
            });
        }
    });

    console.log('✅ Écouteurs de modals configurés');
}

// Fonction pour forcer les styles du mode jour sur les NOUVELLES cartes de statistiques
function forceStatCardsDayMode() {
    console.log('🌞 Forçage du mode jour pour les NOUVELLES cartes de statistiques');

    // Forcer les variables CSS du mode jour
    const root = document.documentElement;
    root.style.setProperty('--day-card-bg', '#ffffff');
    root.style.setProperty('--day-text', '#1e293b');
    root.style.setProperty('--day-text-light', '#64748b');
    root.style.setProperty('--day-shadow', 'rgba(0, 0, 0, 0.1)');
    root.style.setProperty('--day-border', 'rgba(148, 163, 184, 0.2)');
    root.style.setProperty('--day-primary', '#3b82f6');

    // Forcer les styles sur les NOUVELLES cartes de statistiques (status-metric-card)
    const statusCards = document.querySelectorAll('.status-metric-card');
    statusCards.forEach(card => {
        card.style.setProperty('background', 'var(--day-card-bg)', 'important');
        card.style.setProperty('border', '1px solid var(--day-border)', 'important');
        card.style.setProperty('color', 'var(--day-text)', 'important');
        card.style.setProperty('box-shadow', '0 6px 20px var(--day-shadow)', 'important');
        card.style.setProperty('border-radius', '18px', 'important');
        card.style.setProperty('padding', '1.75rem', 'important');

        // Forcer les styles sur le contenu
        const number = card.querySelector('.status-metric-number');
        const label = card.querySelector('.status-metric-label');
        if (number) {
            number.style.setProperty('color', 'var(--day-text)', 'important');
        }
        if (label) {
            label.style.setProperty('color', 'var(--day-text-light)', 'important');
        }
    });

    // Forcer les styles sur les NOUVELLES cartes analytiques (daily-analytics-card)
    const analyticsCards = document.querySelectorAll('.daily-analytics-card');
    analyticsCards.forEach(card => {
        card.style.setProperty('background', 'var(--day-card-bg)', 'important');
        card.style.setProperty('border', '1px solid var(--day-border)', 'important');
        card.style.setProperty('color', 'var(--day-text)', 'important');
        card.style.setProperty('box-shadow', '0 8px 25px var(--day-shadow)', 'important');
        card.style.setProperty('border-radius', '20px', 'important');
        card.style.setProperty('padding', '2rem', 'important');

        // Forcer les styles sur le contenu
        const value = card.querySelector('.daily-analytics-value');
        const text = card.querySelector('.daily-analytics-text');
        if (value) {
            value.style.setProperty('color', 'var(--day-text)', 'important');
        }
        if (text) {
            text.style.setProperty('color', 'var(--day-text-light)', 'important');
        }
    });

    // Forcer les modals en mode jour
    forceModalsDayMode();

    console.log('✅ Styles du mode jour forcés sur', statusCards.length, 'cartes de statut et', analyticsCards.length, 'cartes analytiques');
}

// Fonction pour forcer les styles du mode nuit sur les NOUVELLES cartes de statistiques
function forceStatCardsNightMode() {
    console.log('🌙 Forçage du mode nuit pour les NOUVELLES cartes de statistiques');

    // Forcer les variables CSS du mode nuit
    const root = document.documentElement;
    root.style.setProperty('--day-card-bg', 'rgba(30, 30, 35, 0.95)');
    root.style.setProperty('--day-text', '#ffffff');
    root.style.setProperty('--day-text-light', '#b0b0b0');
    root.style.setProperty('--day-shadow', 'rgba(0, 255, 255, 0.15)');
    root.style.setProperty('--day-border', 'rgba(0, 255, 255, 0.2)');
    root.style.setProperty('--day-primary', '#00d4ff');

    // Forcer les styles sur les NOUVELLES cartes de statistiques (status-metric-card)
    const statusCards = document.querySelectorAll('.status-metric-card');
    statusCards.forEach(card => {
        card.style.setProperty('background', 'rgba(30, 30, 35, 0.95)', 'important');
        card.style.setProperty('border', '1px solid rgba(0, 255, 255, 0.2)', 'important');
        card.style.setProperty('color', '#ffffff', 'important');
        card.style.setProperty('box-shadow', '0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1)', 'important');
        card.style.setProperty('border-radius', '18px', 'important');
        card.style.setProperty('padding', '1.75rem', 'important');

        // Forcer les styles sur le contenu
        const number = card.querySelector('.status-metric-number');
        const label = card.querySelector('.status-metric-label');
        if (number) {
            number.style.setProperty('color', '#ffffff', 'important');
        }
        if (label) {
            label.style.setProperty('color', '#b0b0b0', 'important');
        }
    });

    // Forcer les styles sur les NOUVELLES cartes analytiques (daily-analytics-card)
    const analyticsCards = document.querySelectorAll('.daily-analytics-card');
    analyticsCards.forEach(card => {
        card.style.setProperty('background', 'rgba(30, 30, 35, 0.95)', 'important');
        card.style.setProperty('border', '1px solid rgba(0, 255, 255, 0.2)', 'important');
        card.style.setProperty('color', '#ffffff', 'important');
        card.style.setProperty('box-shadow', '0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1)', 'important');
        card.style.setProperty('border-radius', '20px', 'important');
        card.style.setProperty('padding', '2rem', 'important');

        // Forcer les styles sur le contenu
        const value = card.querySelector('.daily-analytics-value');
        const text = card.querySelector('.daily-analytics-text');
        if (value) {
            value.style.setProperty('color', '#ffffff', 'important');
        }
        if (text) {
            text.style.setProperty('color', '#b0b0b0', 'important');
        }
    });

    // Forcer les modals en mode nuit
    forceModalsNightMode();

    console.log('✅ Styles du mode nuit forcés sur', statusCards.length, 'cartes de statut et', analyticsCards.length, 'cartes analytiques');
}

// Fonction pour forcer les modals en mode jour
function forceModalsDayMode() {
    console.log('🌞 Forçage des modals en mode jour - Design spécialisé');

    // Design premium pour ajouterCommandeModal
    const commandeModal = document.querySelector('#ajouterCommandeModal');
    if (commandeModal) {
        forceCommandeModalPremiumDayMode(commandeModal);
    }

    // Design standard pour ajouterTacheModal
    const tacheModal = document.querySelector('#ajouterTacheModal');
    if (tacheModal) {
        forceStandardModalDayMode(tacheModal);
    }

    // Forcer le backdrop global
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        backdrop.style.setProperty('backdrop-filter', 'blur(12px)', 'important');
        backdrop.style.setProperty('background', 'rgba(0, 0, 0, 0.3)', 'important');
    });

    console.log('✅ Modals forcés en mode jour avec designs spécialisés');
}

// Design premium ultra-moderne pour ajouterCommandeModal
function forceCommandeModalPremiumDayMode(modal) {
    console.log('🛒 Application du design premium pour ajouterCommandeModal');

    const modalDialog = modal.querySelector('.modal-dialog');
    const modalContent = modal.querySelector('.modal-content');
    const modalHeader = modal.querySelector('.modal-header');
    const modalBody = modal.querySelector('.modal-body');
    const modalFooter = modal.querySelector('.modal-footer');

    // Modal principal avec effet glassmorphism avancé
    modal.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
    modal.style.setProperty('background', 'rgba(0, 0, 0, 0.2)', 'important');

    // Dialog avec taille optimisée
    if (modalDialog) {
        modalDialog.style.setProperty('backdrop-filter', 'blur(25px)', 'important');
        modalDialog.style.setProperty('transform', 'none', 'important');
        modalDialog.style.setProperty('transition', 'none', 'important');
        modalDialog.style.setProperty('max-width', '1000px', 'important');
        modalDialog.style.setProperty('margin', '2rem auto', 'important');
    }

    // Contenu avec design glassmorphism premium
    if (modalContent) {
        modalContent.style.setProperty('background', 'linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 50%, rgba(241, 245, 249, 0.92) 100%)', 'important');
        modalContent.style.setProperty('color', '#0f172a', 'important');
        modalContent.style.setProperty('border', '2px solid rgba(255, 255, 255, 0.4)', 'important');
        modalContent.style.setProperty('border-radius', '28px', 'important');
        modalContent.style.setProperty('box-shadow', '0 40px 80px rgba(0, 0, 0, 0.15), 0 20px 40px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.3), inset 0 2px 0 rgba(255, 255, 255, 0.9)', 'important');
        modalContent.style.setProperty('backdrop-filter', 'blur(30px)', 'important');
        modalContent.style.setProperty('overflow', 'hidden', 'important');
        modalContent.style.setProperty('position', 'relative', 'important');

        // Pas d'animation - affichage instantané
        modalContent.style.setProperty('background-image', 'none', 'important');
        modalContent.style.setProperty('animation', 'none', 'important');
    }

    // Header avec design ultra-moderne
    if (modalHeader) {
        modalHeader.style.setProperty('background', 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 25%, #6366f1 50%, #8b5cf6 75%, #a855f7 100%)', 'important');
        modalHeader.style.setProperty('color', '#ffffff', 'important');
        modalHeader.style.setProperty('border', 'none', 'important');
        modalHeader.style.setProperty('border-radius', '28px 28px 0 0', 'important');
        modalHeader.style.setProperty('backdrop-filter', 'blur(20px)', 'important');
        modalHeader.style.setProperty('padding', '2rem 2.5rem', 'important');
        modalHeader.style.setProperty('position', 'relative', 'important');
        modalHeader.style.setProperty('box-shadow', 'inset 0 1px 0 rgba(255, 255, 255, 0.2)', 'important');

        // Pas d'effet de brillance - affichage statique
        modalHeader.style.setProperty('background-image', 'none', 'important');

        // Styliser le titre avec icône
        const title = modalHeader.querySelector('.modal-title');
        if (title) {
            title.style.setProperty('font-size', '1.75rem', 'important');
            title.style.setProperty('font-weight', '800', 'important');
            title.style.setProperty('text-shadow', '0 2px 8px rgba(0,0,0,0.2)', 'important');
            title.style.setProperty('display', 'flex', 'important');
            title.style.setProperty('align-items', 'center', 'important');
            title.style.setProperty('gap', '1rem', 'important');
            title.style.setProperty('letter-spacing', '-0.025em', 'important');

            // Ajouter une icône si elle n'existe pas
            if (!title.querySelector('.fas')) {
                const icon = document.createElement('i');
                icon.className = 'fas fa-shopping-cart';
                icon.style.setProperty('font-size', '1.5rem', 'important');
                icon.style.setProperty('padding', '0.5rem', 'important');
                icon.style.setProperty('background', 'rgba(255, 255, 255, 0.2)', 'important');
                icon.style.setProperty('border-radius', '12px', 'important');
                icon.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
                title.insertBefore(icon, title.firstChild);
            }
        }

        // Styliser le bouton de fermeture
        const closeBtn = modalHeader.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.style.setProperty('background', 'rgba(255, 255, 255, 0.25)', 'important');
            closeBtn.style.setProperty('border-radius', '16px', 'important');
            closeBtn.style.setProperty('padding', '0.75rem', 'important');
            closeBtn.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
            closeBtn.style.setProperty('transition', 'none', 'important');
            closeBtn.style.setProperty('border', '1px solid rgba(255, 255, 255, 0.3)', 'important');
            closeBtn.style.setProperty('box-shadow', '0 4px 12px rgba(0, 0, 0, 0.1)', 'important');
        }
    }

    // Body avec design premium
    if (modalBody) {
        modalBody.style.setProperty('background', 'rgba(255, 255, 255, 0.6)', 'important');
        modalBody.style.setProperty('color', '#0f172a', 'important');
        modalBody.style.setProperty('backdrop-filter', 'blur(20px)', 'important');
        modalBody.style.setProperty('padding', '2.5rem', 'important');
        modalBody.style.setProperty('position', 'relative', 'important');
    }

    // Footer avec design cohérent
    if (modalFooter) {
        modalFooter.style.setProperty('background', 'linear-gradient(145deg, rgba(248, 250, 252, 0.95) 0%, rgba(241, 245, 249, 0.9) 100%)', 'important');
        modalFooter.style.setProperty('color', '#0f172a', 'important');
        modalFooter.style.setProperty('border', 'none', 'important');
        modalFooter.style.setProperty('border-radius', '0 0 28px 28px', 'important');
        modalFooter.style.setProperty('backdrop-filter', 'blur(20px)', 'important');
        modalFooter.style.setProperty('padding', '2rem 2.5rem', 'important');
        modalFooter.style.setProperty('border-top', '1px solid rgba(226, 232, 240, 0.6)', 'important');
        modalFooter.style.setProperty('box-shadow', 'inset 0 1px 0 rgba(255, 255, 255, 0.8)', 'important');
    }

    // Champs de formulaire avec design ultra-moderne
    const formControls = modal.querySelectorAll('.form-control, .form-select, input, select, textarea');
    formControls.forEach(control => {
        control.style.setProperty('background', 'rgba(255, 255, 255, 0.85)', 'important');
        control.style.setProperty('border', '2px solid rgba(59, 130, 246, 0.25)', 'important');
        control.style.setProperty('border-radius', '16px', 'important');
        control.style.setProperty('color', '#0f172a', 'important');
        control.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
        control.style.setProperty('padding', '1rem 1.25rem', 'important');
        control.style.setProperty('font-size', '1rem', 'important');
        control.style.setProperty('font-weight', '500', 'important');
        control.style.setProperty('transition', 'none', 'important');
        control.style.setProperty('box-shadow', '0 6px 16px rgba(0, 0, 0, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.9)', 'important');

        // États focus et hover sans animation
        control.addEventListener('focus', function() {
            this.style.setProperty('border-color', '#3b82f6', 'important');
            this.style.setProperty('box-shadow', '0 0 0 4px rgba(59, 130, 246, 0.15), 0 8px 20px rgba(0, 0, 0, 0.12)', 'important');
            this.style.setProperty('background', 'rgba(255, 255, 255, 0.95)', 'important');
        });

        control.addEventListener('blur', function() {
            this.style.setProperty('border-color', 'rgba(59, 130, 246, 0.25)', 'important');
            this.style.setProperty('box-shadow', '0 6px 16px rgba(0, 0, 0, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.9)', 'important');
            this.style.setProperty('background', 'rgba(255, 255, 255, 0.85)', 'important');
        });
    });

    // Labels avec style premium
    const labels = modal.querySelectorAll('label, .form-label');
    labels.forEach(label => {
        label.style.setProperty('color', '#1e293b', 'important');
        label.style.setProperty('font-weight', '700', 'important');
        label.style.setProperty('font-size', '0.95rem', 'important');
        label.style.setProperty('margin-bottom', '0.75rem', 'important');
        label.style.setProperty('text-transform', 'uppercase', 'important');
        label.style.setProperty('letter-spacing', '0.05em', 'important');
        label.style.setProperty('text-shadow', '0 1px 2px rgba(255, 255, 255, 0.8)', 'important');
    });

    // Boutons avec design premium
    const buttons = modal.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.style.setProperty('border-radius', '16px', 'important');
        button.style.setProperty('padding', '1rem 2rem', 'important');
        button.style.setProperty('font-weight', '700', 'important');
        button.style.setProperty('font-size', '1rem', 'important');
        button.style.setProperty('transition', 'none', 'important');
        button.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
        button.style.setProperty('text-transform', 'uppercase', 'important');
        button.style.setProperty('letter-spacing', '0.025em', 'important');

        if (button.classList.contains('btn-primary')) {
            button.style.setProperty('background', 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 50%, #6366f1 100%)', 'important');
            button.style.setProperty('border', 'none', 'important');
            button.style.setProperty('color', '#ffffff', 'important');
            button.style.setProperty('box-shadow', '0 10px 25px rgba(59, 130, 246, 0.4), 0 4px 12px rgba(59, 130, 246, 0.3)', 'important');
            button.style.setProperty('text-shadow', '0 1px 2px rgba(0, 0, 0, 0.2)', 'important');

            // Pas d'animation hover pour le bouton principal

        } else if (button.classList.contains('btn-secondary')) {
            button.style.setProperty('background', 'rgba(255, 255, 255, 0.9)', 'important');
            button.style.setProperty('border', '2px solid rgba(156, 163, 175, 0.4)', 'important');
            button.style.setProperty('color', '#374151', 'important');
            button.style.setProperty('box-shadow', '0 6px 16px rgba(0, 0, 0, 0.12)', 'important');

            // Pas d'animation hover pour le bouton secondaire
        }
    });

    // Textes muted avec style premium
    const mutedTexts = modal.querySelectorAll('.text-muted, .small');
    mutedTexts.forEach(text => {
        text.style.setProperty('color', '#64748b', 'important');
        text.style.setProperty('font-size', '0.9rem', 'important');
        text.style.setProperty('font-weight', '500', 'important');
    });
}

// Design standard pour ajouterTacheModal
function forceStandardModalDayMode(modal) {
    const modalDialog = modal.querySelector('.modal-dialog');
    const modalContent = modal.querySelector('.modal-content');
    const modalHeader = modal.querySelector('.modal-header');
    const modalBody = modal.querySelector('.modal-body');
    const modalFooter = modal.querySelector('.modal-footer');

    // Modal standard
    modal.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
    modal.style.setProperty('background', 'rgba(0, 0, 0, 0.5)', 'important');

    if (modalDialog) {
        modalDialog.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
        modalDialog.style.setProperty('transform', 'none', 'important');
        modalDialog.style.setProperty('transition', 'all 0.3s ease', 'important');
    }

    if (modalContent) {
        modalContent.style.setProperty('background', 'rgba(255, 255, 255, 0.95)', 'important');
        modalContent.style.setProperty('color', '#1f2937', 'important');
        modalContent.style.setProperty('border', 'none', 'important');
        modalContent.style.setProperty('border-radius', '20px', 'important');
        modalContent.style.setProperty('box-shadow', '0 25px 50px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1)', 'important');
        modalContent.style.setProperty('backdrop-filter', 'blur(20px)', 'important');
    }

    if (modalHeader) {
        modalHeader.style.setProperty('background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', 'important');
        modalHeader.style.setProperty('color', '#ffffff', 'important');
        modalHeader.style.setProperty('border', 'none', 'important');
        modalHeader.style.setProperty('border-radius', '20px 20px 0 0', 'important');
        modalHeader.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
    }

    if (modalBody) {
        modalBody.style.setProperty('background', 'rgba(255, 255, 255, 0.9)', 'important');
        modalBody.style.setProperty('color', '#1f2937', 'important');
        modalBody.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
    }

    if (modalFooter) {
        modalFooter.style.setProperty('background', 'rgba(248, 249, 250, 0.9)', 'important');
        modalFooter.style.setProperty('color', '#1f2937', 'important');
        modalFooter.style.setProperty('border', 'none', 'important');
        modalFooter.style.setProperty('border-radius', '0 0 20px 20px', 'important');
        modalFooter.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
    }

    // Champs de formulaire standard
    const formControls = modal.querySelectorAll('.form-control, .form-select');
    formControls.forEach(control => {
        control.style.setProperty('background', 'rgba(255, 255, 255, 0.9)', 'important');
        control.style.setProperty('border', '1px solid rgba(209, 213, 219, 0.8)', 'important');
        control.style.setProperty('color', '#1f2937', 'important');
        control.style.setProperty('backdrop-filter', 'blur(5px)', 'important');
    });

    // Boutons standard
    const buttons = modal.querySelectorAll('.btn');
    buttons.forEach(button => {
        if (button.classList.contains('btn-primary')) {
            button.style.setProperty('background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', 'important');
            button.style.setProperty('border', 'none', 'important');
            button.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
        }
    });
}

// Fonction pour forcer les modals en mode nuit
function forceModalsNightMode() {
    console.log('🌙 Forçage des modals en mode nuit avec backdrop');

    // Cibler les modals spécifiques
    const modals = ['#ajouterTacheModal', '#ajouterCommandeModal', '#taskDetailsModal'];

    modals.forEach(modalId => {
        const modal = document.querySelector(modalId);
        if (modal) {
            const modalDialog = modal.querySelector('.modal-dialog');
            const modalContent = modal.querySelector('.modal-content');
            const modalHeader = modal.querySelector('.modal-header');
            const modalBody = modal.querySelector('.modal-body');
            const modalFooter = modal.querySelector('.modal-footer');

            // Forcer le modal lui-même
            if (modal) {
                modal.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
                modal.style.setProperty('background', 'rgba(0, 0, 0, 0.7)', 'important');
            }

            // Forcer le dialog
            if (modalDialog) {
                modalDialog.style.setProperty('backdrop-filter', 'blur(20px)', 'important');
                modalDialog.style.setProperty('transform', 'none', 'important');
                modalDialog.style.setProperty('transition', 'all 0.3s ease', 'important');
            }

            if (modalContent) {
                modalContent.style.setProperty('background', 'rgba(30, 30, 35, 0.9)', 'important');
                modalContent.style.setProperty('color', '#ffffff', 'important');
                modalContent.style.setProperty('border', '1px solid rgba(0, 255, 255, 0.3)', 'important');
                modalContent.style.setProperty('border-radius', '20px', 'important');
                modalContent.style.setProperty('box-shadow', '0 25px 50px rgba(0, 255, 255, 0.4), 0 0 0 1px rgba(0, 255, 255, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1)', 'important');
                modalContent.style.setProperty('backdrop-filter', 'blur(25px)', 'important');
            }

            if (modalHeader) {
                modalHeader.style.setProperty('background', 'linear-gradient(135deg, #00d4ff 0%, #ff00aa 100%)', 'important');
                modalHeader.style.setProperty('color', '#000000', 'important');
                modalHeader.style.setProperty('border', 'none', 'important');
                modalHeader.style.setProperty('border-radius', '20px 20px 0 0', 'important');
                modalHeader.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
                modalHeader.style.setProperty('font-weight', '700', 'important');
            }

            if (modalBody) {
                modalBody.style.setProperty('background', 'rgba(30, 30, 35, 0.8)', 'important');
                modalBody.style.setProperty('color', '#ffffff', 'important');
                modalBody.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
            }

            if (modalFooter) {
                modalFooter.style.setProperty('background', 'rgba(40, 40, 45, 0.8)', 'important');
                modalFooter.style.setProperty('color', '#ffffff', 'important');
                modalFooter.style.setProperty('border', 'none', 'important');
                modalFooter.style.setProperty('border-radius', '0 0 20px 20px', 'important');
                modalFooter.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
            }

            // Forcer les champs de formulaire
            const formControls = modal.querySelectorAll('.form-control, .form-select');
            formControls.forEach(control => {
                control.style.setProperty('background', 'rgba(40, 40, 45, 0.8)', 'important');
                control.style.setProperty('border', '1px solid rgba(0, 255, 255, 0.4)', 'important');
                control.style.setProperty('color', '#ffffff', 'important');
                control.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
                control.style.setProperty('box-shadow', '0 0 10px rgba(0, 255, 255, 0.2)', 'important');
            });

            // Forcer les textes muted
            const mutedTexts = modal.querySelectorAll('.text-muted');
            mutedTexts.forEach(text => {
                text.style.setProperty('color', '#b0b0b0', 'important');
            });

            // Forcer les boutons
            const buttons = modal.querySelectorAll('.btn');
            buttons.forEach(button => {
                if (button.classList.contains('btn-primary')) {
                    button.style.setProperty('background', 'linear-gradient(135deg, #00d4ff 0%, #ff00aa 100%)', 'important');
                    button.style.setProperty('border', 'none', 'important');
                    button.style.setProperty('color', '#000000', 'important');
                    button.style.setProperty('font-weight', '700', 'important');
                    button.style.setProperty('backdrop-filter', 'blur(15px)', 'important');
                    button.style.setProperty('box-shadow', '0 0 20px rgba(0, 255, 255, 0.5)', 'important');
                }
            });
        }
    });

    // Forcer le backdrop global
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        backdrop.style.setProperty('backdrop-filter', 'blur(12px)', 'important');
        backdrop.style.setProperty('background', 'rgba(0, 0, 0, 0.6)', 'important');
    });

    console.log('✅ Modals forcés en mode nuit avec backdrop');
}

// Fonction pour forcer les boutons d'action en mode nuit avec le même fond que les statistiques
function forceActionButtonsNightMode() {
    console.log('🌙 Forçage AGRESSIF des boutons d\'action en mode nuit');

    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach((btn, index) => {
        // Supprimer toutes les classes qui pourraient interférer
        btn.classList.remove('geek-action-btn', 'futuristic-action-btn', 'action-card');

        // Styles JS désactivés pour laisser le CSS gérer le Glassmorphism
        // btn.style.setProperty('background', 'rgba(30, 30, 35, 0.95)', 'important');
        // btn.style.setProperty('border', '1px solid rgba(0, 255, 255, 0.2)', 'important');
        // btn.style.setProperty('color', '#ffffff', 'important');
        // btn.style.setProperty('box-shadow', '0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1)', 'important');
        // btn.style.setProperty('backdrop-filter', 'blur(20px)', 'important');
        // btn.style.setProperty('border-radius', '20px', 'important');
        // btn.style.setProperty('padding', '2rem', 'important');
        // btn.style.setProperty('display', 'flex', 'important');
        // btn.style.setProperty('align-items', 'center', 'important');
        // btn.style.setProperty('gap', '1.5rem', 'important');
        // btn.style.setProperty('text-decoration', 'none', 'important');
        // btn.style.setProperty('transition', 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)', 'important');

        // Ajouter un attribut pour identifier les boutons forcés
        btn.setAttribute('data-night-forced', 'true');
    });

    console.log('✅ Boutons d\'action ULTRA-FORCÉS en mode nuit:', actionButtons.length, 'boutons');
}

// ========================================
// EFFETS VISUELS FUTURISTES - MODE NUIT
// ========================================

function injectNightModeEffects() {
    // Éviter les doublons
    if (document.querySelector('.night-mode-bg-effects')) return;

    console.log('✨ Injection des effets visuels futuristes mode nuit');

    // Créer le conteneur principal
    const container = document.createElement('div');
    container.className = 'night-mode-bg-effects';

    // Ajouter les lueurs de coins
    const glowTopLeft = document.createElement('div');
    glowTopLeft.className = 'night-corner-glow top-left';
    container.appendChild(glowTopLeft);

    const glowBottomRight = document.createElement('div');
    glowBottomRight.className = 'night-corner-glow bottom-right';
    container.appendChild(glowBottomRight);

    // Ajouter des particules flottantes
    for (let i = 0; i < 15; i++) {
        const particle = document.createElement('div');
        particle.className = 'night-particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 15 + 's';
        particle.style.animationDuration = (10 + Math.random() * 10) + 's';
        particle.style.width = (2 + Math.random() * 4) + 'px';
        particle.style.height = particle.style.width;
        particle.style.opacity = 0.3 + Math.random() * 0.5;
        container.appendChild(particle);
    }

    // Ajouter quelques lignes de données
    for (let i = 0; i < 3; i++) {
        const dataLine = document.createElement('div');
        dataLine.className = 'night-data-line';
        dataLine.style.top = (20 + i * 30) + '%';
        dataLine.style.width = (100 + Math.random() * 200) + 'px';
        dataLine.style.animationDelay = (i * 2) + 's';
        container.appendChild(dataLine);
    }

    // Insérer au début du body
    document.body.insertBefore(container, document.body.firstChild);

    console.log('✅ Effets visuels futuristes injectés');
}

function removeNightModeEffects() {
    const container = document.querySelector('.night-mode-bg-effects');
    if (container) {
        container.remove();
        console.log('🧹 Effets visuels futuristes supprimés');
    }
}

// Surveillance continue des boutons d'action en mode nuit
let nightModeWatcher = null;

function startNightModeWatcher() {
    if (nightModeWatcher) {
        clearInterval(nightModeWatcher);
    }

    console.log('🔄 Démarrage de la surveillance continue du mode nuit');

    nightModeWatcher = setInterval(() => {
        if (currentTheme === 'night' && document.body.classList.contains('night-mode')) {
            const actionButtons = document.querySelectorAll('.action-btn');
            let needsForcing = false;

            actionButtons.forEach(btn => {
                const currentBg = window.getComputedStyle(btn).backgroundColor;
                // Vérifier si le fond n'est pas celui attendu
                if (!currentBg.includes('30, 30, 35') && !currentBg.includes('rgba(30, 30, 35')) {
                    needsForcing = true;
                }
            });

            if (needsForcing) {
                console.log('⚠️ Styles écrasés détectés - Re-forçage immédiat');
                forceActionButtonsNightMode();
            }
        }
    }, 500); // Vérification toutes les 500ms
}

function stopNightModeWatcher() {
    if (nightModeWatcher) {
        clearInterval(nightModeWatcher);
        nightModeWatcher = null;
        console.log('⏹️ Arrêt de la surveillance du mode nuit');
    }
}

// MutationObserver pour détecter les changements de style en temps réel
let styleObserver = null;

function startStyleObserver() {
    if (styleObserver) {
        styleObserver.disconnect();
    }

    console.log('👁️ Démarrage de l\'observateur de styles');

    styleObserver = new MutationObserver((mutations) => {
        if (currentTheme === 'night' && document.body.classList.contains('night-mode')) {
            let needsForcing = false;

            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' &&
                    (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                    const target = mutation.target;
                    if (target.classList.contains('action-btn')) {
                        needsForcing = true;
                    }
                }
            });

            if (needsForcing) {
                console.log('🔄 Changement de style détecté - Re-forçage');
                setTimeout(() => forceActionButtonsNightMode(), 10);
            }
        }
    });

    // Observer tous les boutons d'action
    document.querySelectorAll('.action-btn').forEach(btn => {
        styleObserver.observe(btn, {
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    });
}

function stopStyleObserver() {
    if (styleObserver) {
        styleObserver.disconnect();
        styleObserver = null;
        console.log('⏹️ Arrêt de l\'observateur de styles');
    }
}

// ========================================
// PARTICULES FLOTTANTES (MODE NUIT)
// ========================================
// createParticles/removeParticles handled later

// ========================================
// MODALS DE STATISTIQUES
// ========================================
</script>
<!-- Système de statistiques avancé -->

<script>

// ========================================
// GESTION DES ONGLETS
// ========================================
function switchTab(tabId) {
    console.log('Basculement vers onglet:', tabId);

    // Masquer tous les contenus d'onglets
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });

    // Désactiver tous les boutons d'onglets
    document.querySelectorAll('.modern-tab-button').forEach(button => {
        button.classList.remove('active');
    });

    // Activer le contenu de l'onglet sélectionné
    const selectedContent = document.getElementById(tabId);
    if (selectedContent) {
        selectedContent.classList.add('active');
    }

    // Activer le bouton de l'onglet sélectionné
    const selectedButton = document.querySelector(`[data-tab="${tabId}"]`);
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
}

// ========================================
// GESTION DU THÈME
// ========================================
// Détection automatique du thème (système ou localStorage)
let dashboardActiveTheme = localStorage.getItem('dashboard-theme');
if (!dashboardActiveTheme) {
    // Si aucun choix utilisateur, utiliser la préférence système
    dashboardActiveTheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'night' : 'day';
}
let particlesCreated = false;

function initTheme() {
    const dashboard = document.getElementById('dashboard');
    const icon = document.getElementById('theme-icon');
    const text = document.getElementById('theme-text');
    const body = document.body;

    if (dashboardActiveTheme === 'night') {
        if(dashboard) dashboard.classList.add('night-mode');
        if(body) body.classList.add('night-mode');
        if(icon) icon.className = 'fas fa-sun';
        if(text) text.textContent = 'Mode Jour';
        if (!particlesCreated) {
            createParticles();
        }
    } else {
        if(dashboard) dashboard.classList.remove('night-mode');
        if(body) body.classList.remove('night-mode');
        if(icon) icon.className = 'fas fa-moon';
        if(text) text.textContent = 'Mode Nuit';
        removeParticles();
    }
}

function toggleTheme() {
    dashboardActiveTheme = dashboardActiveTheme === 'day' ? 'night' : 'day';
    localStorage.setItem('dashboard-theme', dashboardActiveTheme);
    initTheme();
}

function setupThemeListener() {
    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    darkModeMediaQuery.addListener((e) => {
        // Uniquement si l'utilisateur n'a pas forcé le thème
        if (!localStorage.getItem('dashboard-theme')) {
            dashboardActiveTheme = e.matches ? 'night' : 'day';
            initTheme();
        }
    });
}

// ========================================
// PARTICULES FLOTTANTES (MODE NUIT)
// ========================================
function createParticles() {
    const container = document.getElementById('particles');
    if(!container) return;
    const particleCount = 50;

    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 6 + 's';
        particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
        container.appendChild(particle);
    }
    particlesCreated = true;
}

function removeParticles() {
    const container = document.getElementById('particles');
    if(container) container.innerHTML = '';
    particlesCreated = false;
}

// ========================================
// DÉTECTION TACTILE & PROTECTION STYLES
// ========================================
// isTouchDevice handled later

// ========================================
// INITIALISATION
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le thème automatique
    initTheme();

    // Configurer l'écoute des changements de préférences système
    setupThemeListener();

    // Configurer les écouteurs pour les modals
    setupModalListeners();

    // Forcer les bons styles au chargement selon le thème
    setTimeout(() => {
        if (dashboardActiveTheme === 'night') {
            forceStatCardsNightMode();
            forceActionButtonsNightMode();
            injectNightModeEffects(); // Injecter les animations futuristes
        } else {
            forceStatCardsDayMode();
            removeNightModeEffects(); // Nettoyer les animations
        }
    }, 100);

    // Animation au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                // Vérifier et corriger les styles après l'animation
                setTimeout(() => {
                    if (dashboardActiveTheme === 'night') {
                        forceStatCardsNightMode();
                        forceActionButtonsNightMode();
                    } else {
                        forceStatCardsDayMode();
                    }
                }, 50);
            }
        });
    }, observerOptions);

    // Observer tous les éléments avec fade-in
    document.querySelectorAll('.fade-in').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    console.log('✅ Page accueil-modern initialisée');
});

// Charger les scripts et styles du système de statistiques avancé
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔄 Chargement du système de statistiques avancé...');

    // Charger les styles du système de statistiques en premier
    const statsCSS = document.createElement('link');
    statsCSS.rel = 'stylesheet';
    statsCSS.href = 'assets/css/advanced-stats-system.css';
    document.head.appendChild(statsCSS);

    // Fonction pour charger Chart.js puis le système de stats
    function loadStatsSystem() {
        if (typeof Chart === 'undefined') {
            console.log('📊 Chargement de Chart.js...');
            const chartScript = document.createElement('script');
            chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            chartScript.onload = function() {
                console.log('✅ Chart.js chargé, chargement du système de stats...');
                loadAdvancedStatsScript();
            };
            chartScript.onerror = function() {
                console.error('❌ Erreur lors du chargement de Chart.js');
                loadAdvancedStatsScript(); // Charger quand même le système
            };
            document.head.appendChild(chartScript);
        } else {
            console.log('📊 Chart.js déjà disponible');
            loadAdvancedStatsScript();
        }
    }

    // Fonction pour charger le script du système de statistiques
    function loadAdvancedStatsScript() {
        const statsScript = document.createElement('script');
        statsScript.src = 'assets/js/advanced-stats-system.js';
        statsScript.onload = function() {
            console.log('✅ Système de statistiques avancé chargé avec succès');
        };
        statsScript.onerror = function() {
            console.error('❌ Erreur lors du chargement du système de statistiques');
        };
        document.head.appendChild(statsScript);
    }

    // Démarrer le chargement
    loadStatsSystem();
});

// ========================================
// DÉTECTION TACTILE
// ========================================
function isTouchDevice() {
    return (('ontouchstart' in window) ||
           (navigator.maxTouchPoints > 0) ||
           (navigator.msMaxTouchPoints > 0));
}

// Ajuster les interactions pour les appareils tactiles
if (isTouchDevice()) {
    document.body.classList.add('touch-device');

    // Gestion des touches pour les NOUVELLES cartes
    document.querySelectorAll('.modern-action-card, .status-metric-card, .daily-analytics-card, .table-row').forEach(element => {
        element.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.98)';
        });

        element.addEventListener('touchend', function() {
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // 🛡️ PROTECTION ULTRA-AGRESSIVE DES BOUTONS D'ACTION
    function forceActionButtonStyles() {
        const actionButtons = document.querySelectorAll('.action-btn');
        const isNightMode = document.body.classList.contains('night-mode');

        actionButtons.forEach((btn, index) => {
            // Supprimer toutes les classes qui pourraient interférer
            btn.classList.remove('geek-action-btn', 'futuristic-action-btn', 'action-card');

            // Forcer les styles avec setProperty pour bypasser !important
            const style = btn.style;

            if (isNightMode) {
                // Styles mode nuit - EXACTEMENT le même fond que les boutons de statistiques
                style.setProperty('background', 'rgba(30, 30, 35, 0.95)', 'important');
                style.setProperty('border', '1px solid rgba(0, 255, 255, 0.2)', 'important');
                style.setProperty('color', '#ffffff', 'important');
                style.setProperty('box-shadow', '0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1)', 'important');
            } else {
                // Styles mode jour
                style.setProperty('background', 'linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%)', 'important');
                style.setProperty('border', '3px solid rgba(59, 130, 246, 0.3)', 'important');
                style.setProperty('color', '#1e293b', 'important');
                style.setProperty('box-shadow', '0 15px 50px rgba(0, 0, 0, 0.15), 0 8px 25px rgba(0, 0, 0, 0.1), inset 0 2px 0 rgba(255, 255, 255, 0.9), 0 0 0 1px rgba(255, 255, 255, 0.5)', 'important');
            }

            style.setProperty('border-radius', '20px', 'important');
            style.setProperty('padding', '2rem', 'important');
            style.setProperty('display', 'flex', 'important');
            style.setProperty('align-items', 'center', 'important');
            style.setProperty('gap', '1.5rem', 'important');
            style.setProperty('text-decoration', 'none', 'important');
            style.setProperty('transition', 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)', 'important');
            style.setProperty('backdrop-filter', 'blur(20px)', 'important');
            style.setProperty('position', 'relative', 'important');
            style.setProperty('overflow', 'hidden', 'important');
            style.setProperty('animation', 'slideInUp 0.6s ease-out', 'important');
            style.setProperty('width', 'auto', 'important');
            style.setProperty('height', 'auto', 'important');
            style.setProperty('min-width', 'auto', 'important');
            style.setProperty('min-height', 'auto', 'important');
            style.setProperty('max-width', 'none', 'important');
            style.setProperty('max-height', 'none', 'important');
            style.setProperty('flex', 'none', 'important');

            // Forcer les styles des icônes
            const icon = btn.querySelector('.icon');
            if (icon) {
                const iconStyle = icon.style;
                iconStyle.setProperty('width', '60px', 'important');
                iconStyle.setProperty('height', '60px', 'important');
                iconStyle.setProperty('border-radius', '16px', 'important');
                iconStyle.setProperty('display', 'flex', 'important');
                iconStyle.setProperty('align-items', 'center', 'important');
                iconStyle.setProperty('justify-content', 'center', 'important');
                iconStyle.setProperty('font-size', '1.75rem', 'important');
                iconStyle.setProperty('flex-shrink', '0', 'important');
                iconStyle.setProperty('transition', 'all 0.3s ease', 'important');

                // Couleurs spécifiques par bouton selon le mode
                let colors, shadows;

                if (isNightMode) {
                    // Mode nuit - Couleurs néon
                    iconStyle.setProperty('color', '#000000', 'important');
                    colors = [
                        'linear-gradient(135deg, #00d4ff 0%, #0099cc 100%)', // Cyan
                        'linear-gradient(135deg, #00ff41 0%, #00cc33 100%)', // Vert néon
                        'linear-gradient(135deg, #ff8c00 0%, #ff6600 100%)', // Orange néon
                        'linear-gradient(135deg, #ff00aa 0%, #cc0088 100%)'  // Rose néon
                    ];

                    shadows = [
                        '0 4px 16px rgba(0, 212, 255, 0.5), 0 0 20px rgba(0, 212, 255, 0.3)',
                        '0 4px 16px rgba(0, 255, 65, 0.5), 0 0 20px rgba(0, 255, 65, 0.3)',
                        '0 4px 16px rgba(255, 140, 0, 0.5), 0 0 20px rgba(255, 140, 0, 0.3)',
                        '0 4px 16px rgba(255, 0, 170, 0.5), 0 0 20px rgba(255, 0, 170, 0.3)'
                    ];
                } else {
                    // Mode jour - Couleurs classiques
                    iconStyle.setProperty('color', 'white', 'important');
                    colors = [
                        'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)', // Bleu
                        'linear-gradient(135deg, #10b981 0%, #059669 100%)', // Vert
                        'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)', // Orange
                        'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)'  // Violet
                    ];

                    shadows = [
                        '0 4px 16px rgba(59, 130, 246, 0.3)',
                        '0 4px 16px rgba(16, 185, 129, 0.3)',
                        '0 4px 16px rgba(245, 158, 11, 0.3)',
                        '0 4px 16px rgba(139, 92, 246, 0.3)'
                    ];
                }

                if (colors[index]) {
                    iconStyle.setProperty('background', colors[index], 'important');
                    iconStyle.setProperty('box-shadow', shadows[index], 'important');
                }
            }

            // Forcer les styles du contenu
            const content = btn.querySelector('.content');
            if (content) {
                const h3 = content.querySelector('h3');
                const p = content.querySelector('p');

                if (h3) {
                    const h3Style = h3.style;
                    h3Style.setProperty('margin', '0 0 0.5rem 0', 'important');
                    h3Style.setProperty('font-size', '1.4rem', 'important');
                    h3Style.setProperty('font-weight', '900', 'important');
                    h3Style.setProperty('letter-spacing', '-0.025em', 'important');

                    if (isNightMode) {
                        h3Style.setProperty('color', '#f8fafc', 'important');
                        h3Style.setProperty('text-shadow', '0 0 20px rgba(0, 212, 255, 1), 0 3px 6px rgba(0, 0, 0, 0.8), 0 0 40px rgba(0, 212, 255, 0.6), 0 1px 0 rgba(0, 212, 255, 0.8)', 'important');
                    } else {
                        h3Style.setProperty('color', '#020617', 'important');
                        h3Style.setProperty('text-shadow', '0 2px 4px rgba(255, 255, 255, 1), 0 1px 0 rgba(255, 255, 255, 0.8), 0 0 10px rgba(255, 255, 255, 0.5)', 'important');
                    }
                }

                if (p) {
                    const pStyle = p.style;
                    pStyle.setProperty('margin', '0', 'important');
                    pStyle.setProperty('font-size', '0.95rem', 'important');
                    pStyle.setProperty('font-weight', '600', 'important');

                    if (isNightMode) {
                        pStyle.setProperty('color', '#e2e8f0', 'important');
                        pStyle.setProperty('text-shadow', '0 0 15px rgba(0, 212, 255, 0.8), 0 2px 4px rgba(0, 0, 0, 0.5), 0 0 25px rgba(0, 212, 255, 0.4)', 'important');
                    } else {
                        pStyle.setProperty('color', '#334155', 'important');
                        pStyle.setProperty('text-shadow', '0 1px 2px rgba(255, 255, 255, 1), 0 0 5px rgba(255, 255, 255, 0.7)', 'important');
                    }
                }
            }
        });
    }

    // Appliquer immédiatement
    forceActionButtonStyles();

    // Réappliquer toutes les 100ms pendant les 5 premières secondes
    let protectionInterval = setInterval(forceActionButtonStyles, 100);
    setTimeout(() => {
        clearInterval(protectionInterval);
        // Puis toutes les secondes pendant 10 secondes
        protectionInterval = setInterval(forceActionButtonStyles, 1000);
        setTimeout(() => {
            clearInterval(protectionInterval);
            console.log('🛡️ Protection des boutons d\'action terminée');
        }, 10000);
    }, 5000);

    // Observer les changements de style
    const styleObserver = new MutationObserver(function(mutations) {
        let needsForcing = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' &&
                (mutation.attributeName === 'style' || mutation.attributeName === 'class') &&
                mutation.target.classList.contains('action-btn')) {
                needsForcing = true;
            }
        });
        if (needsForcing) {
            setTimeout(forceActionButtonStyles, 10);
        }
    });

    // Observer tous les boutons d'action
    document.querySelectorAll('.action-btn').forEach(btn => {
        styleObserver.observe(btn, {
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    });

    console.log('🛡️ Protection ultra-agressive des boutons d\'action activée');
}
</script>

<!-- 🛡️ CSS DE PROTECTION ABSOLUE - CHARGÉ EN DERNIER -->


<!-- Modal pour afficher les détails d'une tâche -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <!-- En-tête du modal -->
            <div class="modal-header">
                <div class="modal-header-content" style="display:flex;align-items:center;gap:14px;">
                    <div class="action-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="modal-title-section" style="display:flex;flex-direction:column;gap:4px;">
                        <h5 class="modal-title" id="taskDetailsModalLabel" style="margin:0;">Détails de la tâche</h5>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span class="modern-priority-badge" id="task-priority"></span>
                            <span class="modern-status-badge" id="task-status">En attente</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Corps du modal -->
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row g-3">
                        <!-- Colonne gauche: titre + description -->
                        <div class="col-12 col-lg-8">
                            <div class="task-details-card" style="margin-bottom:1rem;">
                                <h3 class="section-title" style="margin-bottom:1rem;">
                                    <i class="fas fa-heading me-2"></i>Titre
                                </h3>
                                <h4 id="task-title" class="modern-task-title" style="margin:0;"></h4>
                            </div>

                            <div class="task-details-card" style="margin-bottom:1rem;">
                                <h3 class="section-title" style="display:flex;align-items:center;gap:8px;margin-bottom:1rem;">
                                    <i class="fas fa-file-alt"></i>
                                    Description
                                </h3>
                                <div class="description-content">
                                    <div id="task-description-loader" class="description-loader" style="display:none;">
                                        <div class="loader-spinner"></div>
                                        <span>Chargement de la description...</span>
                                    </div>
                                    <p id="task-description" class="modern-description" style="margin:0;"></p>
                                </div>
                            </div>

                            <!-- Pièces jointes -->
                            <div id="task-attachments" class="task-details-card" style="display:none;">
                                <h3 class="section-title" style="display:flex;align-items:center;gap:8px;margin-bottom:1rem;">
                                    <i class="fas fa-paperclip"></i>
                                    Pièces jointes
                                </h3>
                                <div id="task-attachments-list"></div>
                            </div>
                        </div>

                        <!-- Colonne droite: informations complémentaires -->
                        <div class="col-12 col-lg-4">
                            <div class="task-details-card">
                                <h3 class="section-title" style="margin-bottom:1rem;">
                                    <i class="fas fa-info-circle me-2"></i>Informations
                                </h3>
                                <div class="task-info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Créée le</span>
                                        <span id="task-created-date" class="info-value">-</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Assignée à</span>
                                        <span id="task-assignee" class="info-value">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conteneur d'erreur -->
                <div id="task-error-container" class="error-container" style="display:none;">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="error-message">Une erreur est survenue</div>
                </div>
            </div>

            <!-- Pied du modal avec boutons d'action -->
            <div class="modal-footer">
                <button id="start-task-btn" class="btn btn-primary" data-task-id="" data-status="en_cours">
                    <i class="fas fa-play me-2"></i> Démarrer
                </button>
                <button id="complete-task-btn" class="btn btn-success" data-task-id="" data-status="termine">
                    <i class="fas fa-check me-2"></i> Terminer
                </button>
                <a href="index.php?page=taches" id="voir-toutes-taches" class="btn btn-secondary">
                    <i class="fas fa-external-link-alt me-2"></i> Voir toutes les tâches
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Styles pour le modal des tâches -->

<!-- Modal pour changer le statut des commandes -->

<!-- Modal moderne pour changer le statut d'une commande -->
<div class="modal fade" id="commandeStatutModal" tabindex="-1" aria-labelledby="commandeStatutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <!-- En-tête du modal -->
            <div class="modal-header">
                <div class="modal-header-content" style="display:flex;align-items:center;gap:14px;">
                    <div class="action-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="modal-title-section" style="display:flex;flex-direction:column;gap:4px;">
                        <h5 class="modal-title" id="commandeStatutModalLabel" style="margin:0;">Changer le statut</h5>
                        <p class="modal-subtitle">Mettre à jour le statut de la commande</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Corps du modal -->
            <div class="modal-body">
                <div class="statut-update-container">
                    <!-- Section titre et statut actuel -->
                    <div class="task-header-section">
                        <div class="task-title-container">
                            <h4 id="statut-commande-reference" class="modern-task-title"></h4>
                            <p id="statut-piece-nom" class="task-subtitle"></p>
                            <div class="task-meta">
                                <div class="priority-container">
                                    <span class="priority-label">Statut actuel</span>
                                    <span id="statut-actuel" class="modern-priority-badge"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Options de statut -->
                    <div class="status-options-grid">
                        <div class="status-option" data-status="en_attente">
                            <div class="status-option-card">
                                <div class="status-icon" style="background: linear-gradient(135deg, #ffa502, #ff6348);">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="status-info">
                                    <div class="status-title">En attente</div>
                                    <div class="status-description">Commande en attente de traitement</div>
                                </div>
                            </div>
                        </div>

                        <div class="status-option" data-status="commande">
                            <div class="status-option-card">
                                <div class="status-icon" style="background: linear-gradient(135deg, #3742fa, #2f3542);">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="status-info">
                                    <div class="status-title">Commandé</div>
                                    <div class="status-description">Commande passée chez le fournisseur</div>
                                </div>
                            </div>
                        </div>

                        <div class="status-option" data-status="recue">
                            <div class="status-option-card">
                                <div class="status-icon" style="background: linear-gradient(135deg, #2ed573, #1e90ff);">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="status-info">
                                    <div class="status-title">Reçu</div>
                                    <div class="status-description">Pièce reçue en magasin</div>
                                </div>
                            </div>
                        </div>

                        <div class="status-option" data-status="utilise">
                            <div class="status-option-card">
                                <div class="status-icon" style="background: linear-gradient(135deg, #70a1ff, #5352ed);">
                                    <i class="fas fa-tools"></i>
                                </div>
                                <div class="status-info">
                                    <div class="status-title">Utilisé</div>
                                    <div class="status-description">Pièce utilisée pour la réparation</div>
                                </div>
                            </div>
                        </div>

                        <div class="status-option" data-status="annulee">
                            <div class="status-option-card">
                                <div class="status-icon" style="background: linear-gradient(135deg, #ff4757, #c44569);">
                                    <i class="fas fa-times"></i>
                                </div>
                                <div class="status-info">
                                    <div class="status-title">Annulé</div>
                                    <div class="status-description">Commande annulée</div>
                                </div>
                            </div>
                        </div>

                        <div class="status-option" data-status="a_retourner">
                            <div class="status-option-card">
                                <div class="status-icon" style="background: linear-gradient(135deg, #57606f, #3d4454);">
                                    <i class="fas fa-undo"></i>
                                </div>
                                <div class="status-info">
                                    <div class="status-title">À retourner</div>
                                    <div class="status-description">Pièce à retourner au fournisseur</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loader et erreur -->
                    <div id="statut-update-loader" class="description-loader" style="display:none;">
                        <div class="loader-spinner"></div>
                        <span>Mise à jour en cours...</span>
                    </div>

                    <div id="statut-error-container" class="error-container" style="display:none;">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="error-message">Une erreur est survenue</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles pour les modals des commandes -->

<!-- Modal pour ajouter un nouveau client (identique à ajouter_reparation) -->
<div class="modal fade" id="nouveauClientModal_commande" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Ajouter un nouveau client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNouveauClient_commande">
                    <?php if (isset($_SESSION['shop_id'])): ?>
                    <input type="hidden" id="nouveau_shop_id_commande" name="shop_id" value="<?php echo $_SESSION['shop_id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="nouveau_nom_commande" class="form-label">Nom *</label>
                        <input type="text" class="form-control form-control-lg" id="nouveau_nom_commande" required>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_prenom_commande" class="form-label">Prénom *</label>
                        <input type="text" class="form-control form-control-lg" id="nouveau_prenom_commande" required>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_telephone_commande" class="form-label">Téléphone * <small class="text-muted">Format international : 331234567890</small></label>
                        <input type="tel" inputmode="tel" class="form-control form-control-lg" id="nouveau_telephone_commande" placeholder="331234567890" pattern="[0-9]{11}" maxlength="11" required>
                        <div class="form-text">Format : 11 chiffres (ex: 331234567890)</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <div class="d-flex w-100">
                    <button type="button" class="btn btn-secondary flex-grow-1 me-2" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary flex-grow-1" id="btn_sauvegarder_client_commande">Sauvegarder</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour le changement de statut des commandes -->
<script src="assets/js/commande-statut.js"></script>

<!-- Inclusion du script des tâches -->
<script src="assets/js/taches.js"></script>

<!-- Script pour le modal de création de client -->
<script>
// Fonction pour ouvrir le modal de création de client (identique à ajouter_reparation)
window.createNewClientModal = function() {
    console.log('👤 Ouverture du modal nouveau client (style ajouter_reparation)');

    // Fermer d'abord le modal de commande s'il est ouvert
    const modalCommande = document.getElementById('ajouterCommandeModal');
    if (modalCommande) {
        const modalCommandeInstance = bootstrap.Modal.getInstance(modalCommande);
        if (modalCommandeInstance) {
            console.log('🔄 Fermeture du modal de commande...');
            modalCommandeInstance.hide();
        }
    }

    // Attendre un peu que le modal de commande se ferme avant d'ouvrir le nouveau
    setTimeout(() => {
        // Nettoyer les champs du formulaire
        document.getElementById('nouveau_nom_commande').value = '';
        document.getElementById('nouveau_prenom_commande').value = '';
        document.getElementById('nouveau_telephone_commande').value = '';

        // Ouvrir le modal nouveau client
        const modal = new bootstrap.Modal(document.getElementById('nouveauClientModal_commande'));
        modal.show();

        console.log('✅ Modal nouveau client ouvert');
    }, 300); // Délai pour laisser le temps au modal de commande de se fermer
};

// Gestionnaire pour sauvegarder le nouveau client
document.addEventListener('DOMContentLoaded', function() {
    const btnSauvegarder = document.getElementById('btn_sauvegarder_client_commande');
    if (btnSauvegarder) {
        btnSauvegarder.addEventListener('click', function() {
            const nom = document.getElementById('nouveau_nom_commande').value.trim();
            const prenom = document.getElementById('nouveau_prenom_commande').value.trim();
            const telephone = document.getElementById('nouveau_telephone_commande').value.trim();

            // Validation
            if (!nom || !prenom || !telephone) {
                alert('Veuillez remplir tous les champs obligatoires.');
                return;
            }

            // Validation du téléphone (11 chiffres)
            if (!/^[0-9]{11}$/.test(telephone)) {
                alert('Le numéro de téléphone doit contenir exactement 11 chiffres (format international).');
                return;
            }

            // Préparer les données
            const formData = new FormData();
            formData.append('action', 'ajouter_client');
            formData.append('nom', nom);
            formData.append('prenom', prenom);
            formData.append('telephone', telephone);
            if (document.getElementById('nouveau_shop_id_commande')) {
                formData.append('shop_id', document.getElementById('nouveau_shop_id_commande').value);
            }

            // Désactiver le bouton pendant l'envoi
            btnSauvegarder.disabled = true;
            btnSauvegarder.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sauvegarde...';

            // Envoyer la requête AJAX vers la version nettoyée
            fetch('ajax/ajouter_client_clean.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Succès - mettre à jour l'interface
                    const clientSearchInput = document.getElementById('nom_client_selectionne');
                    const clientIdInput = document.getElementById('client_id');
                    const clientSelectionne = document.getElementById('client_selectionne');

                    if (clientSearchInput && clientIdInput && clientSelectionne) {
                        clientIdInput.value = data.client_id;
                        clientSearchInput.value = nom + ' ' + prenom;
                        clientSelectionne.classList.remove('d-none');

                        // Mettre à jour le texte affiché
                        const clientNomSpan = clientSelectionne.querySelector('.client-nom');
                        if (clientNomSpan) {
                            clientNomSpan.textContent = nom + ' ' + prenom;
                        }
                    }

                    // Fermer le modal nouveau client
                    const modal = bootstrap.Modal.getInstance(document.getElementById('nouveauClientModal_commande'));
                    modal.hide();

                    // Rouvrir le modal de commande après un court délai
                    setTimeout(() => {
                        const modalCommande = document.getElementById('ajouterCommandeModal');
                        if (modalCommande) {
                            const modalCommandeInstance = new bootstrap.Modal(modalCommande);
                            modalCommandeInstance.show();
                            console.log('🔄 Modal de commande rouvert après création du client');
                        }
                    }, 300);

                    // Message de succès
                    console.log('✅ Client créé avec succès:', data);
                } else {
                    alert('Erreur lors de la création du client: ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la communication avec le serveur.');
            })
            .finally(() => {
                // Réactiver le bouton
                btnSauvegarder.disabled = false;
                btnSauvegarder.innerHTML = 'Sauvegarder';
            });
        });
    }
});

// ========================================
// CORRECTION FORCÉE DU MODE NUIT
// ========================================
(function() {
    'use strict';

    function forceApplyDarkMode() {
        // Vérifier si le système préfère le mode sombre
        const prefersDarkScheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (prefersDarkScheme) {
            console.log('🌙 Mode sombre détecté par le système - Application forcée');

            // Appliquer les classes de mode nuit
            document.documentElement.classList.add('night-mode');
            document.body.classList.add('night-mode');
            document.body.classList.add('dark-mode'); // Fallback

            // Sauvegarder la préférence
            try {
                localStorage.setItem('geekboard_theme', 'dark');
            } catch (e) {
                console.warn('Impossible de sauvegarder la préférence de thème');
            }

            console.log('✅ Mode nuit appliqué avec succès');
        } else {
            console.log('☀️ Mode jour détecté par le système');
        }
    }

    // Appliquer immédiatement
    forceApplyDarkMode();

    // Écouter les changements de préférence système
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            console.log('🔄 Changement de préférence système détecté:', e.matches ? 'sombre' : 'clair');
            forceApplyDarkMode();
        });
    }
})();
</script>

<script>
// Définir la fonction globalement dès le chargement pour éviter les erreurs
window.openEmployeeActivityModal = window.openEmployeeActivityModal || function(userId, employeeName) {
    console.log('openEmployeeActivityModal called with:', userId, employeeName);
};
</script>

<?php
// Modal d'activité employé - Accessible à tous les utilisateurs connectés
?>

<!-- Modal d'activité employé -->
<div class="modal fade" id="employeeActivityModal" tabindex="-1" aria-labelledby="employeeActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="avatar-circle me-3 bg-primary bg-gradient text-white d-flex align-items-center justify-content-center rounded-circle shadow-sm" style="width: 52px; height: 52px; font-size: 1.3rem;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-dark" id="employeeActivityModalLabel">
                            <span id="employeeName">...</span>
                        </h5>
                        <small class="text-muted">Suivi d'activité journalier</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0 bg-light">
                <!-- Contrôles de date -->
                <div class="date-navigation bg-white border-bottom px-4 py-3 d-flex align-items-center justify-content-between sticky-top shadow-sm" style="z-index: 1020;">
                    <button class="btn btn-outline-secondary btn-sm rounded-circle shadow-sm" onclick="changeActivityDate(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>

                    <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1 border">
                        <i class="far fa-calendar-alt text-primary me-2"></i>
                        <input type="date" id="activityDateInput" class="form-control form-control-sm border-0 bg-transparent fw-bold text-center p-0 text-dark" style="width: 130px; outline: none; box-shadow: none;" onchange="loadActivityForDate(this.value)">
                    </div>

                    <button class="btn btn-outline-secondary btn-sm rounded-circle shadow-sm" onclick="changeActivityDate(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <div id="activityLoadingSpinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-3 text-muted fw-medium">Chargement de l'activité...</p>
                </div>

                <div id="activityContent" style="display: none; height: 65vh; overflow-y: auto;" class="px-4 py-4 custom-scrollbar">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="text-uppercase text-muted fw-bold fs-7 mb-0 ls-1">Timeline des Activités</h6>
                        <span class="badge bg-white text-primary border shadow-sm rounded-pill px-3 py-2">
                            <i class="fas fa-history me-1"></i>
                            <span id="activityCount">0</span> réparations
                        </span>
                    </div>

                    <div id="activityTimeline" class="modern-timeline">
                        <!-- Les logs seront insérés ici -->
                    </div>

                    <div id="noActivityMessage" class="text-center py-5" style="display: none;">
                        <div class="mb-3 text-muted opacity-25">
                            <i class="fas fa-calendar-day fa-4x"></i>
                        </div>
                        <h5 class="text-muted fw-bold">Aucune activité</h5>
                        <p class="text-muted">Aucun log n'a été trouvé pour cette date.</p>
                    </div>
                </div>

                <div id="activityError" class="alert alert-danger m-4 shadow-sm border-0" style="display: none;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                        <div>
                            <h6 class="alert-heading fw-bold mb-1">Erreur de chargement</h6>
                            <span id="activityErrorMessage"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Infos Rapides Réparation -->
<div class="modal fade" id="repairQuickInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg night-mode" style="border-radius: 16px;">
            <div class="modal-header night-mode border-bottom">
                <h5 class="modal-title fw-bold night-mode"><i class="fas fa-tools me-2"></i>Informations Réparation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 night-mode">
                <div id="repairQuickInfoLoading" class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3 text-muted">Chargement...</p></div>
                <div id="repairQuickInfoContent" style="display: none;">
                    <div class="mb-3"><h6 class="text-muted small"><i class="far fa-user me-1"></i> CLIENT</h6><p class="h5 night-mode mb-0" id="repairClientName">-</p></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-mobile-alt me-1"></i> APPAREIL</h6><p class="h6 night-mode mb-0" id="repairModel">-</p></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-exclamation-circle me-1"></i> PROBLÈME</h6><p class="night-mode mb-0" id="repairProblem">-</p></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-info-circle me-1"></i> STATUT</h6><span id="repairStatus" class="badge bg-primary">-</span></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-sticky-note me-1"></i> NOTE INTERNE</h6><div class="p-3 rounded night-mode" style="background: rgba(0,0,0,0.05);"><p class="mb-0 small night-mode" id="repairNote">Aucune note</p></div></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-camera me-1"></i> PHOTO</h6><div id="repairPhotoContainer" class="text-center"><img id="repairPhoto" class="img-fluid rounded shadow-sm" style="max-height: 300px; display: none;"><p id="repairNoPhoto" class="text-muted mb-0">Aucune photo disponible</p></div></div>
                </div>
                <div id="repairQuickInfoError" class="alert alert-danger" style="display: none;"><i class="fas fa-exclamation-triangle me-2"></i><span id="repairQuickInfoErrorMessage">Erreur</span></div>
            </div>
            <div class="modal-footer border-0 night-mode">
                <button type="button" class="btn btn-secondary night-mode" data-bs-dismiss="modal">Fermer</button>
                <a id="repairDetailsLink" href="#" target="_blank" class="btn btn-primary night-mode"><i class="fas fa-external-link-alt me-1"></i> Voir détails</a>
            </div>
        </div>
    </div>
</div>


<!-- Modal Infos Rapides Tâche -->
<div class="modal fade" id="taskQuickInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg night-mode" style="border-radius: 16px;">
            <div class="modal-header night-mode border-bottom">
                <h5 class="modal-title fw-bold night-mode"><i class="fas fa-tasks me-2"></i>Informations Tâche</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 night-mode">
                <div id="taskQuickInfoLoading" class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3 text-muted">Chargement...</p></div>
                <div id="taskQuickInfoContent" style="display: none;">
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-heading me-1"></i> TITRE</h6><p class="h5 night-mode mb-0" id="taskTitle">-</p></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-align-left me-1"></i> DESCRIPTION</h6><p class="night-mode mb-0" id="taskDescription">-</p></div>
                    <div class="mb-3 d-flex gap-3"><div class="flex-fill"><h6 class="text-muted small"><i class="fas fa-info-circle me-1"></i> STATUT</h6><span id="taskStatus" class="badge bg-primary">-</span></div><div class="flex-fill"><h6 class="text-muted small"><i class="fas fa-flag me-1"></i> PRIORITÉ</h6><span id="taskPriority" class="badge bg-secondary">-</span></div></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-user me-1"></i> ASSIGNÉ À</h6><p class="night-mode mb-0" id="taskAssignedTo">-</p></div>
                    <div class="mb-3"><h6 class="text-muted small"><i class="fas fa-user-plus me-1"></i> CRÉÉ PAR</h6><p class="night-mode mb-0" id="taskCreatedBy">-</p></div>
                    <div class="mb-3 d-flex gap-3"><div class="flex-fill"><h6 class="text-muted small"><i class="fas fa-calendar-alt me-1"></i> ÉCHÉANCE</h6><p class="night-mode mb-0" id="taskDueDate">-</p></div><div class="flex-fill"><h6 class="text-muted small"><i class="fas fa-clock me-1"></i> CRÉÉE LE</h6><p class="night-mode mb-0" id="taskCreatedAt">-</p></div></div>
                </div>
                <div id="taskQuickInfoError" class="alert alert-danger" style="display: none;"><i class="fas fa-exclamation-triangle me-2"></i><span id="taskQuickInfoErrorMessage">Erreur</span></div>
            </div>
            <div class="modal-footer border-0 night-mode">
                <button type="button" class="btn btn-secondary night-mode" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>


<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/fr.js"></script>


<!-- Employee Activity Modal Script -->
<script src="assets/js/employee-activity-modal.js?v=<?php echo time(); ?>"></script>
<script src="assets/js/repair-quick-info.js?v=<?php echo time(); ?>"></script>
<!-- Formation Overlay System -->
<script src="assets/js/formation-reparation.js?v=<?php echo time(); ?>"></script>
<script src="assets/js/formation-taches.js?v=<?php echo time(); ?>"></script>
<script src="assets/js/formation-commandes.js?v=<?php echo time(); ?>"></script>


<!-- Script autonome pour les animations futuristes mode nuit -->
<script>
(function() {
    'use strict';

    function injectNightEffects() {
        // Éviter les doublons
        if (document.querySelector('.night-mode-bg-effects')) return;
        if (!document.body.classList.contains('night-mode')) return;

        console.log('✨ Injection des effets visuels futuristes mode nuit (autonome)');

        // Créer le conteneur principal
        const container = document.createElement('div');
        container.className = 'night-mode-bg-effects';

        // Créer la couche de fond de base
        const baseBg = document.createElement('div');
        baseBg.className = 'night-mode-base-bg';
        document.body.insertBefore(baseBg, document.body.firstChild);

        // Ajouter les lueurs de coins
        const glowTopLeft = document.createElement('div');
        glowTopLeft.className = 'night-corner-glow top-left';
        container.appendChild(glowTopLeft);

        const glowBottomRight = document.createElement('div');
        glowBottomRight.className = 'night-corner-glow bottom-right';
        container.appendChild(glowBottomRight);

        // Ajouter des particules flottantes
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'night-particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (8 + Math.random() * 12) + 's';
            particle.style.width = (2 + Math.random() * 4) + 'px';
            particle.style.height = particle.style.width;
            particle.style.opacity = 0.4 + Math.random() * 0.4;
            container.appendChild(particle);
        }

        // Ajouter quelques lignes de données
        for (let i = 0; i < 5; i++) {
            const dataLine = document.createElement('div');
            dataLine.className = 'night-data-line';
            dataLine.style.top = (15 + i * 18) + '%';
            dataLine.style.width = (80 + Math.random() * 150) + 'px';
            dataLine.style.animationDelay = (i * 1.5) + 's';
            container.appendChild(dataLine);
        }

        // Insérer au début du body
        document.body.insertBefore(container, document.body.firstChild);

        console.log('✅ Effets visuels futuristes injectés avec succès');
    }

    function removeNightEffects() {
        const container = document.querySelector('.night-mode-bg-effects');
        if (container) {
            container.remove();
        }
        const baseBg = document.querySelector('.night-mode-base-bg');
        if (baseBg) {
            baseBg.remove();
        }
        console.log('🧹 Effets visuels futuristes supprimés');
    }

    // Injecter immédiatement si le mode nuit est déjà actif
    if (document.body.classList.contains('night-mode')) {
        injectNightEffects();
    }

    // Écouter les changements de classe sur le body
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                if (document.body.classList.contains('night-mode')) {
                    injectNightEffects();
                } else {
                    removeNightEffects();
                }
            }
        });
    });

    observer.observe(document.body, { attributes: true });

    // Backup: vérifier après un court délai
    setTimeout(function() {
        if (document.body.classList.contains('night-mode')) {
            injectNightEffects();
        }
    }, 500);

    // Backup supplémentaire au DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            if (document.body.classList.contains('night-mode')) {
                injectNightEffects();
            }
        }, 100);
    });
})();
</script>
