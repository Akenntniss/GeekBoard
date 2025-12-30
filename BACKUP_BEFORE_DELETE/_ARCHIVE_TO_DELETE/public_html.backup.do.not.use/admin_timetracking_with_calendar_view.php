<?php
/**
 * Interface administrateur avec calendrier pour le système de pointage
 * Inclut la vue calendrier avec filtres par employé
 */

// S'assurer que les fichiers de configuration sont chargés
if (!function_exists('getShopDBConnection')) {
    require_once BASE_PATH . '/config/database.php';
}

// Obtenir la connexion à la base de données
try {
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Connexion à la base de données échouée");
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">
        <h4><i class="fas fa-database"></i> Erreur de base de données</h4>
        <p>Impossible de se connecter à la base de données: ' . $e->getMessage() . '</p>
    </div>';
    return;
}

// Récupérer les données pour l'affichage
$all_users = [];
$global_slots = [];
$user_slots = [];
$pending_requests = [];
$stats = [
    'currently_working' => 0,
    'on_break' => 0,
    'total_work_hours' => 0,
    'pending_approvals' => 0
];

try {
    // Auto-créer table time_slots si nécessaire
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'time_slots'");
    $stmt->execute();
    $slots_table_exists = $stmt->fetch();
    
    if (!$slots_table_exists) {
        $shop_pdo->exec("
            CREATE TABLE IF NOT EXISTS time_slots (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                slot_type ENUM('morning', 'afternoon') NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_slot (user_id, slot_type)
            )
        ");
        
        $shop_pdo->exec("
            INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES
            (NULL, 'morning', '08:00:00', '12:30:00', TRUE),
            (NULL, 'afternoon', '14:00:00', '19:00:00', TRUE)
        ");
    }

    // Récupérer tous les utilisateurs
    $stmt = $shop_pdo->prepare("SELECT id, full_name, username FROM users WHERE role != 'admin' ORDER BY full_name");
    $stmt->execute();
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques de base
    $stmt = $shop_pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as currently_working,
            SUM(CASE WHEN status = 'break' THEN 1 ELSE 0 END) as on_break,
            COALESCE(SUM(work_duration), 0) as total_work_hours,
            COUNT(CASE WHEN admin_approved = 0 AND status = 'completed' THEN 1 END) as pending_approvals
        FROM time_tracking
        WHERE DATE(clock_in) = CURDATE()
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $stats = $result;
    }

    // Créneaux globaux
    $stmt = $shop_pdo->prepare("
        SELECT slot_type, start_time, end_time 
        FROM time_slots 
        WHERE user_id IS NULL AND is_active = TRUE
    ");
    $stmt->execute();
    $global_slots_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($global_slots_raw as $slot) {
        $global_slots[$slot['slot_type']] = [
            'start_time' => $slot['start_time'],
            'end_time' => $slot['end_time']
        ];
    }

    // Créneaux spécifiques
    $stmt = $shop_pdo->prepare("
        SELECT ts.user_id, ts.slot_type, ts.start_time, ts.end_time, u.full_name 
        FROM time_slots ts
        JOIN users u ON ts.user_id = u.id
        WHERE ts.user_id IS NOT NULL AND ts.is_active = TRUE
        ORDER BY u.full_name, ts.slot_type
    ");
    $stmt->execute();
    $user_slots_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($user_slots_raw as $slot) {
        if (!isset($user_slots[$slot['user_id']])) {
            $user_slots[$slot['user_id']] = [
                'full_name' => $slot['full_name'],
                'slots' => []
            ];
        }
        $user_slots[$slot['user_id']]['slots'][$slot['slot_type']] = [
            'start_time' => $slot['start_time'],
            'end_time' => $slot['end_time']
        ];
    }

    // Demandes à approuver
    $stmt = $shop_pdo->prepare("
        SELECT tt.*, u.full_name, u.username,
               DATE(tt.clock_in) as work_date,
               TIME(tt.clock_in) as start_time,
               TIME(tt.clock_out) as end_time,
               COALESCE(tt.approval_reason, '') as approval_reason
        FROM time_tracking tt
        JOIN users u ON tt.user_id = u.id
        WHERE tt.status = 'completed' AND tt.admin_approved = 0
        ORDER BY tt.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo '<div class="alert alert-danger">
        <h4><i class="fas fa-exclamation-triangle"></i> Erreur</h4>
        <p>Erreur lors de la récupération des données: ' . $e->getMessage() . '</p>
    </div>';
    return;
}
?>

<style>
.admin-timetracking-container {
    width: 100% !important;
    max-width: none !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
    float: none !important;
    clear: both !important;
}

:root {
    --primary-color: #0066cc;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --info-color: #17a2b8;
    --border-radius: 12px;
    --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

.admin-nav {
    background: linear-gradient(135deg, var(--primary-color), #004499);
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    padding: 1.5rem;
    box-shadow: var(--box-shadow);
    width: 100%;
}

.nav-tabs-custom .nav-link {
    border: none;
    color: rgba(255, 255, 255, 0.8);
    background: transparent;
    border-radius: 8px;
    margin-right: 0.5rem;
    transition: var(--transition);
    padding: 0.75rem 1.25rem;
    font-weight: 500;
}

.nav-tabs-custom .nav-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

.nav-tabs-custom .nav-link.active {
    color: white;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.stats-card {
    background: white;
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    transition: var(--transition);
    overflow: hidden;
    position: relative;
    margin-bottom: 1rem;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--success-color));
}

.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.settings-section {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    margin-bottom: 1.5rem;
}

.time-slot-config {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid #dee2e6;
}

.user-slot-item {
    background: white;
    border-radius: 8px;
    border-left: 4px solid var(--info-color);
    margin-bottom: 0.5rem;
    transition: var(--transition);
    padding: 1rem;
}

.approval-item.out-of-hours {
    border-left: 4px solid var(--warning-color);
    background: linear-gradient(45deg, #fff3cd, #ffeaa7);
}

/* Styles pour le calendrier */
.calendar-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #eee;
}

.calendar-nav {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.calendar-day-header {
    background: var(--primary-color);
    color: white;
    padding: 0.75rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
}

.calendar-day {
    background: white;
    min-height: 120px;
    padding: 0.5rem;
    position: relative;
    border: 1px solid #eee;
    transition: var(--transition);
}

.calendar-day:hover {
    background: #f8f9fa;
}

.calendar-day.other-month {
    background: #f8f9fa;
    color: #6c757d;
}

.calendar-day.today {
    background: #e3f2fd;
    border-color: var(--primary-color);
}

.day-number {
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.day-entries {
    font-size: 0.75rem;
    line-height: 1.2;
}

.entry-time {
    background: var(--success-color);
    color: white;
    padding: 0.1rem 0.3rem;
    border-radius: 3px;
    margin: 0.1rem 0;
    display: block;
    text-align: center;
}

.entry-time.pending {
    background: var(--warning-color);
    color: #333;
}

.entry-time.morning {
    background: #17a2b8;
}

.entry-time.afternoon {
    background: #6f42c1;
}

.filter-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border: 1px solid #dee2e6;
}
</style>

<div class="admin-timetracking-container">
    <div class="w-100">
        
        <!-- Header -->
        <div class="admin-nav">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="text-white mb-1">
                        <i class="fas fa-cog"></i> Administration Pointage
                    </h1>
                    <p class="text-white-50 mb-0">Gestion complète du système de pointage avec créneaux horaires</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-light btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Actualiser
                    </button>
                </div>
            </div>
            
            <!-- Navigation -->
            <ul class="nav nav-tabs nav-tabs-custom" id="adminTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar" type="button">
                        <i class="fas fa-calendar-alt"></i> Calendrier
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button">
                        <i class="fas fa-cog"></i> Paramètres
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="approvals-tab" data-bs-toggle="tab" data-bs-target="#approvals" type="button">
                        <i class="fas fa-check-double"></i> Demandes
                        <?php if (count($pending_requests) > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo count($pending_requests); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button">
                        <i class="fas fa-chart-pie"></i> Statistiques
                    </button>
                </li>
            </ul>
        </div>

        <!-- Contenu -->
        <div class="tab-content w-100">

            <!-- Calendrier (onglet principal) -->
            <div class="tab-pane fade show active" id="calendar">
                <div class="w-100">
                    
                    <!-- Filtres -->
                    <div class="filter-section">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-user"></i> Filtrer par employé
                                </label>
                                <select class="form-select" id="employeeFilter" onchange="loadCalendarData()">
                                    <option value="">Tous les employés</option>
                                    <?php foreach ($all_users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-calendar"></i> Mois/Année
                                </label>
                                <div class="d-flex gap-2">
                                    <select class="form-select" id="monthFilter" onchange="loadCalendarData()">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo $m; ?>" <?php echo ($m == date('n')) ? 'selected' : ''; ?>>
                                            <?php echo strftime('%B', mktime(0, 0, 0, $m, 1)); ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                    <select class="form-select" id="yearFilter" onchange="loadCalendarData()">
                                        <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo ($y == date('Y')) ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-filter"></i> Statut
                                </label>
                                <select class="form-select" id="statusFilter" onchange="loadCalendarData()">
                                    <option value="">Tous les statuts</option>
                                    <option value="approved">Approuvés</option>
                                    <option value="pending">En attente</option>
                                    <option value="auto">Auto-approuvés</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Calendrier -->
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <h3 id="calendarTitle">
                                <i class="fas fa-calendar-alt text-primary"></i> 
                                <span id="currentMonthYear"><?php echo strftime('%B %Y'); ?></span>
                            </h3>
                            <div class="calendar-nav">
                                <button class="btn btn-outline-primary btn-sm" onclick="navigateMonth(-1)">
                                    <i class="fas fa-chevron-left"></i> Précédent
                                </button>
                                <button class="btn btn-primary btn-sm" onclick="goToToday()">
                                    <i class="fas fa-calendar-day"></i> Aujourd'hui
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="navigateMonth(1)">
                                    Suivant <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>

                        <div id="calendarGrid" class="calendar-grid">
                            <!-- Les en-têtes des jours -->
                            <div class="calendar-day-header">Lun</div>
                            <div class="calendar-day-header">Mar</div>
                            <div class="calendar-day-header">Mer</div>
                            <div class="calendar-day-header">Jeu</div>
                            <div class="calendar-day-header">Ven</div>
                            <div class="calendar-day-header">Sam</div>
                            <div class="calendar-day-header">Dim</div>
                            
                            <!-- Les jours seront générés par JavaScript -->
                        </div>
                    </div>

                    <!-- Légende -->
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Légende du calendrier</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <span class="entry-time morning d-inline-block">Matin</span> Pointage matinal
                                        </div>
                                        <div class="col-md-3">
                                            <span class="entry-time afternoon d-inline-block">A-midi</span> Pointage après-midi
                                        </div>
                                        <div class="col-md-3">
                                            <span class="entry-time d-inline-block">Approuvé</span> Pointage validé
                                        </div>
                                        <div class="col-md-3">
                                            <span class="entry-time pending d-inline-block">En attente</span> À valider
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paramètres -->
            <div class="tab-pane fade" id="settings">
                <div class="w-100">
                    <!-- Créneaux globaux -->
                    <div class="settings-section">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-globe"></i> Créneaux globaux (par défaut)</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Fonctionnement :</strong> Les pointages dans ces créneaux sont approuvés automatiquement. 
                                Les pointages hors créneaux nécessitent une approbation manuelle.
                            </div>
                            <form id="globalSlotsForm" onsubmit="saveGlobalSlots(event)">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="time-slot-config">
                                            <h6><i class="fas fa-sun text-warning"></i> Matin</h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <label class="form-label">Début</label>
                                                    <input type="time" class="form-control" name="morning_start" 
                                                           value="<?php echo substr($global_slots['morning']['start_time'] ?? '08:00:00', 0, 5); ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Fin</label>
                                                    <input type="time" class="form-control" name="morning_end" 
                                                           value="<?php echo substr($global_slots['morning']['end_time'] ?? '12:30:00', 0, 5); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="time-slot-config">
                                            <h6><i class="fas fa-moon text-info"></i> Après-midi</h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <label class="form-label">Début</label>
                                                    <input type="time" class="form-control" name="afternoon_start" 
                                                           value="<?php echo substr($global_slots['afternoon']['start_time'] ?? '14:00:00', 0, 5); ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Fin</label>
                                                    <input type="time" class="form-control" name="afternoon_end" 
                                                           value="<?php echo substr($global_slots['afternoon']['end_time'] ?? '19:00:00', 0, 5); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Sauvegarder les créneaux globaux
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Créneaux spécifiques -->
                    <div class="settings-section">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Créneaux spécifiques par employé</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>Priorité :</strong> Les créneaux spécifiques remplacent les créneaux globaux pour l'employé concerné.
                            </div>
                            
                            <form id="userSlotsForm" onsubmit="saveUserSlots(event)">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Employé</label>
                                        <select class="form-select" name="user_id" required>
                                            <option value="">Sélectionner un employé</option>
                                            <?php foreach ($all_users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>">
                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Matin début</label>
                                        <input type="time" class="form-control" name="user_morning_start">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Matin fin</label>
                                        <input type="time" class="form-control" name="user_morning_end">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">A-midi début</label>
                                        <input type="time" class="form-control" name="user_afternoon_start">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">A-midi fin</label>
                                        <input type="time" class="form-control" name="user_afternoon_end">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-plus"></i> Ajouter
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <?php if (!empty($user_slots)): ?>
                            <h6 class="mt-4 mb-3">Créneaux spécifiques configurés</h6>
                            <?php foreach ($user_slots as $user_id => $user_data): ?>
                            <div class="user-slot-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user_data['full_name']); ?></h6>
                                        <small class="text-muted">
                                            <?php if (isset($user_data['slots']['morning'])): ?>
                                            <i class="fas fa-sun text-warning"></i> 
                                            Matin: <?php echo substr($user_data['slots']['morning']['start_time'], 0, 5); ?>-<?php echo substr($user_data['slots']['morning']['end_time'], 0, 5); ?>
                                            <?php endif; ?>
                                            <?php if (isset($user_data['slots']['afternoon'])): ?>
                                            <i class="fas fa-moon text-info"></i> 
                                            A-midi: <?php echo substr($user_data['slots']['afternoon']['start_time'], 0, 5); ?>-<?php echo substr($user_data['slots']['afternoon']['end_time'], 0, 5); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="removeUserSlots(<?php echo $user_id; ?>, '<?php echo htmlspecialchars($user_data['full_name']); ?>')">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demandes à approuver -->
            <div class="tab-pane fade" id="approvals">
                <div class="w-100">
                    <div class="mb-4">
                        <h3><i class="fas fa-check-double text-warning"></i> Demandes d'approbation</h3>
                        <p class="text-muted">Pointages hors créneaux horaires nécessitant une validation manuelle.</p>
                    </div>
                    
                    <?php if (empty($pending_requests)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4 class="text-success">Aucune demande en attente</h4>
                        <p class="text-muted">Tous les pointages sont dans les créneaux autorisés ou déjà approuvés.</p>
                    </div>
                    <?php else: ?>
                    <div class="row w-100">
                        <?php foreach ($pending_requests as $request): ?>
                        <div class="col-lg-6 mb-3">
                            <div class="card approval-item <?php echo $request['approval_reason'] ? 'out-of-hours' : ''; ?>">
                                <div class="card-body">
                                    <h6><i class="fas fa-user"></i> <?php echo htmlspecialchars($request['full_name']); ?></h6>
                                    <p class="small text-muted mb-2">
                                        <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($request['work_date'])); ?> |
                                        <i class="fas fa-clock"></i> <?php echo $request['start_time']; ?> - <?php echo $request['end_time'] ?? 'En cours'; ?>
                                    </p>
                                    
                                    <?php if ($request['approval_reason']): ?>
                                    <div class="bg-warning bg-opacity-25 p-2 rounded mb-3">
                                        <small><i class="fas fa-exclamation-triangle"></i> 
                                        <strong>Raison :</strong> <?php echo htmlspecialchars($request['approval_reason']); ?></small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-success btn-sm flex-fill" 
                                                onclick="approveEntry(<?php echo $request['id']; ?>)">
                                            <i class="fas fa-check"></i> Approuver
                                        </button>
                                        <button class="btn btn-danger btn-sm flex-fill" 
                                                onclick="rejectEntry(<?php echo $request['id']; ?>)">
                                            <i class="fas fa-times"></i> Rejeter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="tab-pane fade" id="dashboard">
                <div class="row w-100 mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center p-3">
                                <h3 class="text-success mb-1"><?php echo $stats['currently_working']; ?></h3>
                                <small class="text-muted">Actuellement au travail</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center p-3">
                                <h3 class="text-warning mb-1"><?php echo $stats['on_break']; ?></h3>
                                <small class="text-muted">En pause</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center p-3">
                                <h3 class="text-info mb-1"><?php echo number_format($stats['total_work_hours'], 1); ?>h</h3>
                                <small class="text-muted">Total heures aujourd'hui</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center p-3">
                                <h3 class="text-danger mb-1"><?php echo $stats['pending_approvals']; ?></h3>
                                <small class="text-muted">À approuver</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-success">
                    <h5><i class="fas fa-info-circle"></i> Système de créneaux horaires</h5>
                    <ul class="mb-0">
                        <li><strong>Créneaux globaux :</strong> Horaires par défaut pour tous les employés</li>
                        <li><strong>Créneaux spécifiques :</strong> Horaires personnalisés qui remplacent les globaux</li>
                        <li><strong>Approbation automatique :</strong> Pointages dans les créneaux validés automatiquement</li>
                        <li><strong>Demandes manuelles :</strong> Pointages hors créneaux nécessitent validation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript pour la gestion du calendrier et des actions admin
const AJAX_ENDPOINT = 'admin_timetracking_simple_ajax.php';

let currentDate = new Date();
let calendarData = {};

// Gestion des actions admin simplifiées
function callAjax(action, data, callback) {
    const formData = new FormData();
    formData.append('action', action);
    
    for (const key in data) {
        formData.append(key, data[key]);
    }
    
    fetch(AJAX_ENDPOINT, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            if (callback) callback(data);
            else location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur AJAX:', error);
        alert('❌ Erreur: ' + error.message);
    });
}

function saveGlobalSlots(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData);
    callAjax('save_global_slots', data);
}

function saveUserSlots(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData);
    callAjax('save_user_slots', data);
}

function removeUserSlots(userId, userName) {
    if (confirm(`Supprimer les créneaux spécifiques de ${userName} ?\nL'employé utilisera les créneaux globaux.`)) {
        callAjax('remove_user_slots', { user_id: userId });
    }
}

function approveEntry(entryId) {
    if (confirm('Approuver ce pointage ?')) {
        callAjax('approve_entry', { entry_id: entryId });
    }
}

function rejectEntry(entryId) {
    const reason = prompt('Raison du rejet du pointage :');
    if (reason !== null && reason.trim() !== '') {
        callAjax('reject_entry', { entry_id: entryId, reason: reason });
    }
}

// Fonctions du calendrier
function loadCalendarData() {
    const employeeId = document.getElementById('employeeFilter').value;
    const month = document.getElementById('monthFilter').value;
    const year = document.getElementById('yearFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    // Simuler les données pour le moment (à remplacer par un appel AJAX réel)
    generateCalendar(parseInt(year), parseInt(month) - 1);
}

function generateCalendar(year, month) {
    currentDate = new Date(year, month, 1);
    
    // Mettre à jour le titre
    const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                       'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    document.getElementById('currentMonthYear').textContent = `${monthNames[month]} ${year}`;
    
    const grid = document.getElementById('calendarGrid');
    
    // Garder les en-têtes des jours
    const dayHeaders = grid.querySelectorAll('.calendar-day-header');
    grid.innerHTML = '';
    dayHeaders.forEach(header => grid.appendChild(header));
    
    // Calculer le premier jour du mois (lundi = 0)
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    const mondayOffset = (firstDay.getDay() + 6) % 7; // Convertir dimanche=0 en lundi=0
    startDate.setDate(firstDay.getDate() - mondayOffset);
    
    // Générer 42 jours (6 semaines)
    for (let i = 0; i < 42; i++) {
        const currentDay = new Date(startDate);
        currentDay.setDate(startDate.getDate() + i);
        
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        
        if (currentDay.getMonth() !== month) {
            dayElement.classList.add('other-month');
        }
        
        if (isToday(currentDay)) {
            dayElement.classList.add('today');
        }
        
        dayElement.innerHTML = `
            <div class="day-number">${currentDay.getDate()}</div>
            <div class="day-entries">
                ${generateDayEntries(currentDay)}
            </div>
        `;
        
        grid.appendChild(dayElement);
    }
}

function generateDayEntries(date) {
    // Simuler des données de pointage (à remplacer par des vraies données)
    const entries = [];
    const dayOfMonth = date.getDate();
    
    // Exemples de pointages
    if (dayOfMonth % 3 === 0 && date.getMonth() === currentDate.getMonth()) {
        entries.push('<span class="entry-time morning">Matin: 08:30</span>');
        entries.push('<span class="entry-time afternoon">A-midi: 14:15</span>');
    } else if (dayOfMonth % 5 === 0 && date.getMonth() === currentDate.getMonth()) {
        entries.push('<span class="entry-time pending">Matin: 07:45</span>');
        entries.push('<span class="entry-time">A-midi: 14:00</span>');
    } else if (dayOfMonth % 4 === 0 && date.getMonth() === currentDate.getMonth()) {
        entries.push('<span class="entry-time">Matin: 08:15</span>');
    }
    
    return entries.join('');
}

function isToday(date) {
    const today = new Date();
    return date.getDate() === today.getDate() &&
           date.getMonth() === today.getMonth() &&
           date.getFullYear() === today.getFullYear();
}

function navigateMonth(delta) {
    currentDate.setMonth(currentDate.getMonth() + delta);
    document.getElementById('monthFilter').value = currentDate.getMonth() + 1;
    document.getElementById('yearFilter').value = currentDate.getFullYear();
    loadCalendarData();
}

function goToToday() {
    const today = new Date();
    document.getElementById('monthFilter').value = today.getMonth() + 1;
    document.getElementById('yearFilter').value = today.getFullYear();
    loadCalendarData();
}

// Initialiser le calendrier au chargement
document.addEventListener('DOMContentLoaded', function() {
    loadCalendarData();
});
</script>
