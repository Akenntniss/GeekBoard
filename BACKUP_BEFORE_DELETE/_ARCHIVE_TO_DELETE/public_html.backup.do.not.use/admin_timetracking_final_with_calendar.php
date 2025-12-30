<?php
/**
 * Interface administrateur avec calendrier pour le système de pointage
 * Version finale avec JavaScript intégré
 */

// Ajouter le CSS spécifique pour le mode sombre
echo '<link rel="stylesheet" href="assets/css/admin-timetracking-dark.css">';

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

    // Plus de créneaux globaux - Système supprimé

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
    color: white !important;
    background: transparent;
    border-radius: 8px;
    margin-right: 0.5rem;
    transition: var(--transition);
    padding: 0.75rem 1.25rem;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.nav-tabs-custom .nav-link:hover {
    color: white !important;
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

.nav-tabs-custom .nav-link.active {
    color: white !important;
    background: rgba(255, 255, 255, 0.25);
    border: none;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

.nav-tabs-custom .nav-link .badge {
    color: white !important;
    background-color: #dc3545 !important;
    text-shadow: none;
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

/* Mode sombre pour les cartes de demandes */
.dark-mode .approval-item {
    background: #1f2937 !important;
    border: 1px solid #4b5563 !important;
    color: #f9fafb !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3) !important;
}

.dark-mode .approval-item:hover {
    background: #374151 !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4) !important;
}

.dark-mode .approval-item.out-of-hours {
    background: #1f2937 !important;
    border-left: 4px solid #f59e0b !important;
    border: 1px solid #f59e0b !important;
}

.dark-mode .approval-item h6 {
    color: #f9fafb !important;
}

.dark-mode .approval-item .text-muted {
    color: #d1d5db !important;
}

.dark-mode .approval-item .small {
    color: #d1d5db !important;
}

/* Corrections pour les éléments warning en mode sombre */
.dark-mode .bg-warning {
    background-color: #d97706 !important;
    color: white !important;
}

.dark-mode .bg-warning.text-dark {
    background-color: #f59e0b !important;
    color: #1f2937 !important;
    font-weight: 600 !important;
}

.dark-mode .modal-header.bg-warning {
    background-color: #d97706 !important;
    color: white !important;
}

.dark-mode .modal-header.bg-warning .modal-title {
    color: white !important;
}

/* Amélioration des zones d'information warning */
.dark-mode .bg-warning.bg-opacity-25 {
    background-color: rgba(245, 158, 11, 0.2) !important;
    color: #fef3c7 !important;
    border: 1px solid #f59e0b !important;
}

/* Section demandes d'approbation en mode sombre */
.dark-mode #approvals h3 {
    color: #f9fafb !important;
}

.dark-mode #approvals .text-muted {
    color: #d1d5db !important;
}

/* Messages d'état en mode sombre */
.dark-mode .text-success {
    color: #34d399 !important;
}

.dark-mode .text-center.py-5 h4 {
    color: #34d399 !important;
}

.dark-mode .text-center.py-5 p {
    color: #d1d5db !important;
}

/* Boutons dans les cartes d'approbation */
.dark-mode .approval-item .btn {
    border-width: 1px !important;
}

.dark-mode .approval-item .btn-success {
    background-color: #059669 !important;
    border-color: #059669 !important;
    color: white !important;
}

.dark-mode .approval-item .btn-warning {
    background-color: #d97706 !important;
    border-color: #d97706 !important;
    color: white !important;
}

.dark-mode .approval-item .btn-danger {
    background-color: #dc2626 !important;
    border-color: #dc2626 !important;
    color: white !important;
}

/* Section paramètres - créneaux spécifiques */
.dark-mode .settings-section {
    background: #1f2937 !important;
    border: 1px solid #4b5563 !important;
}

.dark-mode .settings-section .card-header {
    background-color: #1e40af !important;
    border-bottom: 1px solid #4b5563 !important;
    color: white !important;
}

.dark-mode .settings-section .card-header.bg-info {
    background-color: #1e40af !important;
    color: white !important;
}

.dark-mode .settings-section .card-body {
    background: #1f2937 !important;
    color: #f9fafb !important;
}

/* Amélioration de l'alerte système individuel */
.dark-mode .alert-success {
    background-color: #064e3b !important;
    border-color: #059669 !important;
    color: #d1fae5 !important;
}

.dark-mode .alert-success strong {
    color: #d1fae5 !important;
}

/* Autres éléments de la section paramètres */
.dark-mode .settings-section h5 {
    color: white !important;
}

.dark-mode .settings-section h6 {
    color: #f9fafb !important;
}

.dark-mode .user-slot-item {
    background: #374151 !important;
    border: 1px solid #4b5563 !important;
    color: #f9fafb !important;
}

.dark-mode .user-slot-item h6 {
    color: #f9fafb !important;
}

.dark-mode .user-slot-item .text-muted {
    color: #d1d5db !important;
}

/* Headers bg-info en mode sombre */
.dark-mode .modal-header.bg-info {
    background-color: #1e40af !important;
    color: white !important;
}

.dark-mode .modal-header.bg-info .modal-title {
    color: white !important;
}

/* Styles pour le calendrier */
.calendar-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
}

/* Mode sombre pour le calendrier */
.dark-mode .calendar-container {
    background: #1f2937 !important;
    border: 1px solid #4b5563 !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3) !important;
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

.dark-mode .calendar-grid {
    background: #4b5563 !important;
}

.calendar-day-header {
    background: var(--primary-color);
    color: white;
    padding: 0.75rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
}

.dark-mode .calendar-day-header {
    background: #1e40af !important;
    color: white !important;
    border: 1px solid #4b5563 !important;
}

.calendar-day {
    background: white;
    min-height: 140px;
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

/* Mode sombre pour les cellules du calendrier */
.dark-mode .calendar-day {
    background: #1f2937 !important;
    color: #f9fafb !important;
    border: 1px solid #4b5563 !important;
}

.dark-mode .calendar-day:hover {
    background: #374151 !important;
}

.dark-mode .calendar-day.other-month {
    background: #1a1e2c !important;
    color: #9ca3af !important;
}

.dark-mode .calendar-day.today {
    background: rgba(59, 130, 246, 0.2) !important;
    border-color: #3b82f6 !important;
    box-shadow: inset 0 0 0 2px #3b82f6;
}

.day-number {
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.dark-mode .day-number {
    color: #f9fafb !important;
}

.dark-mode .calendar-day.other-month .day-number {
    color: #9ca3af !important;
}

.day-entries {
    font-size: 0.7rem;
    line-height: 1.1;
}

.entry-time {
    background: var(--success-color);
    color: white;
    padding: 0.1rem 0.3rem;
    border-radius: 3px;
    margin: 0.1rem 0;
    display: block;
    text-align: center;
    cursor: pointer;
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

.dark-mode .filter-section {
    background: #1f2937 !important;
    border: 1px solid #4b5563 !important;
    color: #f9fafb !important;
}

.dark-mode .filter-section .form-label {
    color: #f9fafb !important;
}

.dark-mode .filter-section .fw-bold {
    color: #f9fafb !important;
}

#loadingIndicator {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: var(--box-shadow);
    z-index: 1000;
}

/* Styles pour l'impression */
@media print {
    body * {
        visibility: hidden;
    }
    
    #reportContent, #reportContent * {
        visibility: visible;
    }
    
    #reportContent {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 20px;
        box-shadow: none;
        border: none;
    }
    
    .no-print {
        display: none !important;
    }
    
    .print-page-break {
        page-break-before: always;
    }
    
    table {
        border-collapse: collapse;
        width: 100%;
    }
    
    table, th, td {
        border: 1px solid #333;
    }
    
    th, td {
        padding: 8px;
        text-align: left;
    }
    
    th {
        background-color: #f5f5f5 !important;
        font-weight: bold;
    }
}

.report-header {
    text-align: center;
    margin-bottom: 2rem;
    border-bottom: 2px solid #333;
    padding-bottom: 1rem;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
}

.report-table th,
.report-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

.report-table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.report-table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.report-summary {
    background: #e3f2fd;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.late-highlight {
    background-color: #ffebee !important;
    color: #c62828;
    font-weight: bold;
}
</style>

<div class="admin-timetracking-container">
    <div class="w-100">
        
        <!-- Header -->
        <div class="admin-nav">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="text-white mb-1">
                        <i class="fas fa-calendar-alt"></i> Administration Pointage
                    </h1>
                    <p class="text-white-50 mb-0">Gestion complète du système de pointage avec vue calendrier</p>
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
                    <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button">
                        <i class="fas fa-chart-pie"></i> Statistiques
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar" type="button">
                        <i class="fas fa-calendar-alt"></i> Calendrier
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
                    <button class="nav-link" id="export-tab" data-bs-toggle="tab" data-bs-target="#export" type="button">
                        <i class="fas fa-print"></i> Exporter
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button">
                        <i class="fas fa-cog"></i> Paramètres
                    </button>
                </li>
            </ul>
        </div>

        <!-- Contenu -->
        <div class="tab-content w-100">

            <!-- Statistiques (onglet principal) -->
            <div class="tab-pane fade show active" id="dashboard">
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
                
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Système de créneaux horaires</h5>
                    <ul class="mb-0">
                        <li><strong>Créneaux spécifiques uniquement :</strong> Chaque employé doit avoir ses propres horaires configurés</li>
                        <li><strong>Approbation automatique :</strong> Pointages dans les créneaux autorisés validés automatiquement</li>
                        <li><strong>Demandes manuelles :</strong> Pointages hors créneaux ou sans créneaux définis nécessitent validation</li>
                        <li><strong>Obligation de configuration :</strong> Configurez des créneaux pour chaque employé pour un fonctionnement optimal</li>
                    </ul>
                </div>
            </div>

            <!-- Calendrier -->
            <div class="tab-pane fade" id="calendar">
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
                                            <?php
                                            $monthName = '';
                                            switch($m) {
                                                case 1: $monthName = 'Janvier'; break;
                                                case 2: $monthName = 'Février'; break;
                                                case 3: $monthName = 'Mars'; break;
                                                case 4: $monthName = 'Avril'; break;
                                                case 5: $monthName = 'Mai'; break;
                                                case 6: $monthName = 'Juin'; break;
                                                case 7: $monthName = 'Juillet'; break;
                                                case 8: $monthName = 'Août'; break;
                                                case 9: $monthName = 'Septembre'; break;
                                                case 10: $monthName = 'Octobre'; break;
                                                case 11: $monthName = 'Novembre'; break;
                                                case 12: $monthName = 'Décembre'; break;
                                            }
                                            echo $monthName;
                                            ?>
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
                    <div class="calendar-container" style="position: relative;">
                        <div class="calendar-header">
                            <h3 id="calendarTitle">
                                <i class="fas fa-calendar-alt text-primary"></i> 
                                <span id="currentMonthYear"><?php 
                                    $monthNames = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                                                   'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                                    echo $monthNames[date('n')] . ' ' . date('Y');
                                ?></span>
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
                        
                        <div id="loadingIndicator" style="display: none;">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2 mb-0">Chargement du calendrier...</p>
                        </div>
                    </div>

                    <!-- Légende -->
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Légende du calendrier</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <span class="entry-time morning d-inline-block">Matin: 08:30 - 12:00</span> Format pointage matinal (IN - OUT)
                                        </div>
                                        <div class="col-md-6">
                                            <span class="entry-time afternoon d-inline-block">A-Midi: 14:00 - 18:30</span> Format pointage après-midi (IN - OUT)
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <span class="entry-time d-inline-block">Vert</span> = Pointage approuvé automatiquement ou manuellement
                                        </div>
                                        <div class="col-md-6">
                                            <span class="entry-time pending d-inline-block">Jaune</span> = En attente d'approbation administrative
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exporter -->
            <div class="tab-pane fade" id="export">
                <div class="w-100">
                    
                    <!-- Filtres pour l'export -->
                    <div class="filter-section">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-user"></i> Employé
                                </label>
                                <select class="form-select" id="exportEmployeeFilter">
                                    <option value="">Tous les employés</option>
                                    <?php foreach ($all_users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-calendar-week"></i> Période
                                </label>
                                <select class="form-select" id="exportPeriod">
                                    <option value="this_week">Cette semaine</option>
                                    <option value="last_week">Semaine dernière</option>
                                    <option value="this_month">Ce mois</option>
                                    <option value="last_month">Mois dernier</option>
                                    <option value="custom">Période personnalisée</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="customPeriodSection" style="display: none;">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-calendar"></i> Dates
                                </label>
                                <div class="d-flex gap-1">
                                    <input type="date" class="form-control" id="exportStartDate">
                                    <input type="date" class="form-control" id="exportEndDate">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-file-alt"></i> Type de rapport
                                </label>
                                <select class="form-select" id="exportType">
                                    <option value="timesheet">Feuille de temps</option>
                                    <option value="late_report">Rapport des retards</option>
                                    <option value="overtime_report">Heures supplémentaires</option>
                                    <option value="summary">Résumé mensuel</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12 text-center">
                                <button class="btn btn-primary btn-lg me-2" onclick="generateReport()">
                                    <i class="fas fa-file-pdf"></i> Générer le rapport
                                </button>
                                <button class="btn btn-success btn-lg" onclick="printReport()" id="printBtn" style="display: none;">
                                    <i class="fas fa-print"></i> Imprimer
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Zone d'aperçu du rapport -->
                    <div id="reportPreview" class="mt-4" style="display: none;">
                        <div class="d-flex justify-content-between mb-3">
                            <h4><i class="fas fa-eye"></i> Aperçu du rapport</h4>
                            <div>
                                <button class="btn btn-outline-secondary btn-sm" onclick="hideReport()">
                                    <i class="fas fa-times"></i> Fermer
                                </button>
                            </div>
                        </div>
                        
                        <!-- Le contenu du rapport sera inséré ici -->
                        <div id="reportContent" class="border rounded p-3 bg-white">
                            <!-- Contenu généré dynamiquement -->
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="alert alert-info mt-4">
                        <h6><i class="fas fa-info-circle"></i> Instructions d'utilisation</h6>
                        <ul class="mb-0">
                            <li><strong>Feuille de temps :</strong> Rapport détaillé des pointages avec heures d'arrivée et départ</li>
                            <li><strong>Rapport des retards :</strong> Liste des retards calculés selon les créneaux horaires définis</li>
                            <li><strong>Heures supplémentaires :</strong> Dépassements d'horaires selon les créneaux spécifiques ou globaux</li>
                            <li><strong>Résumé mensuel :</strong> Vue d'ensemble avec totaux et statistiques</li>
                            <li><strong>Impression :</strong> Optimisé pour impression A4 en portrait</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Paramètres -->
            <div class="tab-pane fade" id="settings">
                <div class="w-100">
                    <!-- Section QR Code -->
                    <div class="qr-section mb-4" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 15px; padding: 2rem; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><defs><pattern id=\"qrgrid\" width=\"10\" height=\"10\" patternUnits=\"userSpaceOnUse\"><path d=\"M 10 0 L 0 0 0 10\" fill=\"none\" stroke=\"white\" stroke-width=\"0.5\" opacity=\"0.1\"/></pattern></defs><rect width=\"100\" height=\"100\" fill=\"url(%23qrgrid)\"/></svg>'); animation: rotate 20s linear infinite;"></div>
                        <div style="position: relative; z-index: 1;">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h3 class="mb-3">
                                        <i class="fas fa-qrcode fa-2x me-3"></i>
                                        Pointage QR Code
                                    </h3>
                                    <p class="lead mb-3">
                                        🎯 Générez un QR Code pour permettre aux employés de pointer facilement avec leur smartphone
                                    </p>
                                    <div style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-radius: 10px; padding: 1rem;">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <i class="fas fa-mobile-alt fa-2x mb-2"></i>
                                                <div><small>Pointage Mobile</small></div>
                                            </div>
                                            <div class="col-4">
                                                <i class="fas fa-clock fa-2x mb-2"></i>
                                                <div><small>Temps Réel</small></div>
                                            </div>
                                            <div class="col-4">
                                                <i class="fas fa-shield-alt fa-2x mb-2"></i>
                                                <div><small>Sécurisé</small></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <button class="btn btn-lg" style="background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border: 2px solid rgba(255, 255, 255, 0.3); color: white; padding: 1rem 2rem; border-radius: 12px; font-weight: 600;" onclick="showQRCodeModal()">
                                        <i class="fas fa-qrcode fa-2x d-block mb-2"></i>
                                        <strong>AFFICHER QR CODE</strong>
                                        <div><small>Pour le pointage mobile</small></div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section créneaux globaux supprimée - Utilisation créneaux spécifiques uniquement -->

                    <!-- Créneaux spécifiques -->
                    <div class="settings-section">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Créneaux spécifiques par employé</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <i class="fas fa-user-clock"></i> 
                                <strong>Système individuel :</strong> Chaque employé doit avoir ses propres créneaux configurés pour bénéficier de l'approbation automatique.
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
                                    
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-success btn-sm" style="flex: 1;" 
                                                onclick="approveEntry(<?php echo $request['id']; ?>)">
                                            <i class="fas fa-check"></i> Approuver
                                        </button>
                                        <button class="btn btn-warning btn-sm" style="flex: 1;" 
                                                onclick="openRectifyModal(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['full_name']); ?>', '<?php echo $request['start_time']; ?>', '<?php echo $request['end_time']; ?>', '<?php echo $request['work_date']; ?>')">
                                            <i class="fas fa-edit"></i> Rectifier
                                        </button>
                                        <button class="btn btn-danger btn-sm" style="flex: 1;" 
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

        </div>
    </div>
</div>

<!-- Modal de rectification des pointages -->
<div class="modal fade" id="rectifyModal" tabindex="-1" aria-labelledby="rectifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="rectifyModalLabel">
                    <i class="fas fa-edit"></i> Rectifier le pointage
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rectifyForm">
                    <input type="hidden" id="rectifyEntryId" name="entry_id">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Attention :</strong> Vous allez modifier un pointage existant. Cette action sera enregistrée dans l'historique.
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6><i class="fas fa-user"></i> Employé</h6>
                            <p class="form-control-plaintext" id="rectifyEmployeeName"></p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar"></i> Date</h6>
                            <p class="form-control-plaintext" id="rectifyWorkDate"></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="rectifyStartTime" class="form-label">
                                <i class="fas fa-sign-in-alt text-success"></i> Heure d'entrée
                            </label>
                            <input type="time" class="form-control" id="rectifyStartTime" name="start_time" required>
                        </div>
                        <div class="col-md-6">
                            <label for="rectifyEndTime" class="form-label">
                                <i class="fas fa-sign-out-alt text-danger"></i> Heure de sortie
                            </label>
                            <input type="time" class="form-control" id="rectifyEndTime" name="end_time">
                            <div class="form-text">Laisser vide si l'employé n'est pas encore sorti</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rectifyReason" class="form-label">
                            <i class="fas fa-comment-alt"></i> Motif de la rectification <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="rectifyReason" name="reason" rows="3" 
                                  placeholder="Expliquez pourquoi ce pointage doit être rectifié..." required></textarea>
                        <div class="form-text">Ce motif sera enregistré dans l'historique des modifications</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rectifyAutoApprove" name="auto_approve" checked>
                                <label class="form-check-label" for="rectifyAutoApprove">
                                    <i class="fas fa-check-circle text-success"></i> 
                                    Approuver automatiquement après rectification
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Information :</strong> La rectification remplacera les heures actuelles et créera un historique de modification.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="button" class="btn btn-warning" onclick="submitRectification()">
                    <i class="fas fa-save"></i> Enregistrer la rectification
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal des détails de pointage -->
<div class="modal fade" id="entryDetailsModal" tabindex="-1" aria-labelledby="entryDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="entryDetailsModalLabel">
                    <i class="fas fa-info-circle"></i> Détails du pointage
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-user text-primary"></i> Informations employé</h6>
                        <div class="card mb-3">
                            <div class="card-body">
                                <p><strong>Nom :</strong> <span id="detailEmployeeName"></span></p>
                                <p><strong>Date :</strong> <span id="detailWorkDate"></span></p>
                                <p class="mb-0"><strong>Statut :</strong> <span id="detailStatus"></span></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-clock text-success"></i> Horaires</h6>
                        <div class="card mb-3">
                            <div class="card-body">
                                <p><strong>Arrivée :</strong> <span id="detailClockIn"></span></p>
                                <p><strong>Départ :</strong> <span id="detailClockOut"></span></p>
                                <p class="mb-0"><strong>Durée :</strong> <span id="detailDuration"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-network-wired text-warning"></i> Informations techniques</h6>
                        <div class="card mb-3">
                            <div class="card-body">
                                <p><strong>Adresse IP :</strong> <span id="detailIpAddress"></span></p>
                                <p><strong>Appareil :</strong> <span id="detailUserAgent"></span></p>
                                <p class="mb-0"><strong>Navigateur :</strong> <span id="detailBrowser"></span></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-map-marker-alt text-danger"></i> Géolocalisation</h6>
                        <div class="card mb-3">
                            <div class="card-body">
                                <p><strong>Arrivée :</strong> <span id="detailLocationIn"></span></p>
                                <p><strong>Départ :</strong> <span id="detailLocationOut"></span></p>
                                <div id="detailMapLink" style="display: none;">
                                    <a href="#" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-map"></i> Voir sur la carte
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row" id="detailNotesSection" style="display: none;">
                    <div class="col-md-12">
                        <h6><i class="fas fa-sticky-note text-info"></i> Notes et historique</h6>
                        <div class="card">
                            <div class="card-body">
                                <div id="detailNotes" class="small text-muted"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row" id="detailApprovalsSection" style="display: none;">
                    <div class="col-md-12">
                        <h6><i class="fas fa-check-circle text-success"></i> Informations d'approbation</h6>
                        <div class="card">
                            <div class="card-body">
                                <p id="detailAutoApproved" style="display: none;">
                                    <i class="fas fa-robot text-success"></i> 
                                    <strong>Approuvé automatiquement</strong> selon les créneaux horaires
                                </p>
                                <p id="detailManualApproved" style="display: none;">
                                    <i class="fas fa-user-check text-primary"></i> 
                                    <strong>Approuvé manuellement</strong> par un administrateur
                                </p>
                                <p id="detailPendingApproval" style="display: none;">
                                    <i class="fas fa-clock text-warning"></i> 
                                    <strong>En attente d'approbation</strong>
                                </p>
                                <div id="detailApprovalReason" style="display: none;">
                                    <small class="text-muted">
                                        <strong>Raison :</strong> <span id="detailApprovalReasonText"></span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Fermer
                </button>
                <button type="button" class="btn btn-warning" id="detailEditBtn" onclick="editEntryFromDetails()">
                    <i class="fas fa-edit"></i> Modifier
                </button>
                <button type="button" class="btn btn-danger" id="detailDeleteBtn" onclick="deleteEntryFromDetails()">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal des pointages du jour -->
<div class="modal fade" id="dayEntriesModal" tabindex="-1" aria-labelledby="dayEntriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="dayEntriesModalLabel">
                    <i class="fas fa-calendar-day"></i> Pointages du <span id="dayEntriesDate"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Instructions :</strong> Cliquez sur un pointage pour voir ses détails complets, ou utilisez les boutons d'action rapide.
                </div>
                
                <div id="dayEntriesContent">
                    <!-- Le contenu sera généré dynamiquement -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Fermer
                </button>
                <button type="button" class="btn btn-primary" onclick="refreshDayEntries()">
                    <i class="fas fa-sync-alt"></i> Actualiser
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript intégré pour la gestion du calendrier et des actions admin
const AJAX_ENDPOINT = 'admin_timetracking_simple_ajax.php';

let currentDate = new Date();
let calendarData = {};
let currentEntryDetails = null;
let currentDayEntries = null;

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

// Fonction saveGlobalSlots supprimée - Plus de créneaux globaux

function saveUserSlots(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData);
    callAjax('save_user_slots', data);
}

function removeUserSlots(userId, userName) {
    if (confirm(`Supprimer les créneaux spécifiques de ${userName} ?\nL'employé n'aura plus de créneaux définis et tous ses pointages nécessiteront une approbation manuelle.`)) {
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

function openRectifyModal(entryId, employeeName, startTime, endTime, workDate) {
    // Remplir les données du modal
    document.getElementById('rectifyEntryId').value = entryId;
    document.getElementById('rectifyEmployeeName').textContent = employeeName;
    document.getElementById('rectifyWorkDate').textContent = formatDateForDisplay(workDate);
    document.getElementById('rectifyStartTime').value = startTime || '';
    document.getElementById('rectifyEndTime').value = endTime || '';
    document.getElementById('rectifyReason').value = '';
    document.getElementById('rectifyAutoApprove').checked = true;
    
    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('rectifyModal'));
    modal.show();
}

function submitRectification() {
    const form = document.getElementById('rectifyForm');
    const formData = new FormData(form);
    
    // Validation
    const startTime = formData.get('start_time');
    const reason = formData.get('reason');
    
    if (!startTime) {
        alert('❌ L\'heure d\'entrée est obligatoire');
        return;
    }
    
    if (!reason || reason.trim() === '') {
        alert('❌ Le motif de rectification est obligatoire');
        return;
    }
    
    // Confirmation
    if (!confirm('Confirmer la rectification de ce pointage ?\nCette action sera enregistrée dans l\'historique.')) {
        return;
    }
    
    // Préparer les données
    const data = {
        entry_id: formData.get('entry_id'),
        start_time: formData.get('start_time'),
        end_time: formData.get('end_time') || null,
        reason: formData.get('reason'),
        auto_approve: formData.get('auto_approve') ? 1 : 0
    };
    
    // Envoyer la requête
    callAjax('rectify_entry', data, function(response) {
        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('rectifyModal'));
        modal.hide();
        
        // Recharger la page pour voir les changements
        setTimeout(() => {
            location.reload();
        }, 1000);
    });
}

function formatDateForDisplay(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Fonctions pour les détails de pointage
function showEntryDetails(entryId) {
    // Récupérer les détails du pointage
    const params = new URLSearchParams({
        action: 'get_entry_details',
        entry_id: entryId
    });
    
    fetch(`calendar_api.php?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayEntryDetails(data.data);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des détails:', error);
            alert('❌ Erreur lors du chargement des détails: ' + error.message);
        });
}

function displayEntryDetails(entry) {
    currentEntryDetails = entry;
    
    // Remplir les informations de base
    document.getElementById('detailEmployeeName').textContent = entry.employee_name || 'N/A';
    document.getElementById('detailWorkDate').textContent = formatDateForDisplay(entry.work_date);
    document.getElementById('detailClockIn').textContent = entry.clock_in_time || 'N/A';
    document.getElementById('detailClockOut').textContent = entry.clock_out_time || 'En cours';
    
    // Calculer et afficher la durée
    if (entry.work_duration) {
        document.getElementById('detailDuration').textContent = parseFloat(entry.work_duration).toFixed(1) + 'h';
    } else {
        document.getElementById('detailDuration').textContent = 'En cours';
    }
    
    // Statut
    let statusText = 'Actif';
    let statusClass = 'text-primary';
    
    if (entry.status === 'completed') {
        statusText = 'Terminé';
        statusClass = 'text-success';
    } else if (entry.status === 'break') {
        statusText = 'En pause';
        statusClass = 'text-warning';
    }
    
    const statusElement = document.getElementById('detailStatus');
    statusElement.textContent = statusText;
    statusElement.className = statusClass;
    
    // Informations techniques
    document.getElementById('detailIpAddress').textContent = entry.ip_address || 'N/A';
    
    // Analyser le User-Agent pour extraire des infos
    const userAgent = entry.user_agent || 'N/A';
    document.getElementById('detailUserAgent').textContent = userAgent;
    document.getElementById('detailBrowser').textContent = getBrowserName(userAgent);
    
    // Géolocalisation
    const locationIn = entry.location_in;
    const locationOut = entry.location_out;
    
    if (locationIn && locationIn !== 'null' && locationIn !== '') {
        document.getElementById('detailLocationIn').textContent = locationIn;
        // Afficher le lien vers la carte si c'est des coordonnées
        if (locationIn.includes(',')) {
            const mapLink = document.getElementById('detailMapLink');
            const link = mapLink.querySelector('a');
            link.href = `https://www.google.com/maps?q=${locationIn}`;
            mapLink.style.display = 'block';
        }
    } else {
        document.getElementById('detailLocationIn').textContent = 'Non disponible';
    }
    
    if (locationOut && locationOut !== 'null' && locationOut !== '') {
        document.getElementById('detailLocationOut').textContent = locationOut;
    } else {
        document.getElementById('detailLocationOut').textContent = 'Non disponible';
    }
    
    // Notes et historique
    if (entry.admin_notes || entry.notes) {
        const notesSection = document.getElementById('detailNotesSection');
        const notesContent = document.getElementById('detailNotes');
        
        let allNotes = [];
        if (entry.notes) allNotes.push('Notes: ' + entry.notes);
        if (entry.admin_notes) allNotes.push('Historique admin: ' + entry.admin_notes);
        
        notesContent.innerHTML = allNotes.join('<br><br>').replace(/\n/g, '<br>');
        notesSection.style.display = 'block';
    } else {
        document.getElementById('detailNotesSection').style.display = 'none';
    }
    
    // Informations d'approbation
    const approvalsSection = document.getElementById('detailApprovalsSection');
    const autoApproved = document.getElementById('detailAutoApproved');
    const manualApproved = document.getElementById('detailManualApproved');
    const pendingApproval = document.getElementById('detailPendingApproval');
    const approvalReason = document.getElementById('detailApprovalReason');
    
    // Réinitialiser l'affichage
    autoApproved.style.display = 'none';
    manualApproved.style.display = 'none';
    pendingApproval.style.display = 'none';
    approvalReason.style.display = 'none';
    
    if (entry.auto_approved == 1) {
        autoApproved.style.display = 'block';
        approvalsSection.style.display = 'block';
    } else if (entry.admin_approved == 1) {
        manualApproved.style.display = 'block';
        approvalsSection.style.display = 'block';
    } else {
        pendingApproval.style.display = 'block';
        approvalsSection.style.display = 'block';
    }
    
    if (entry.approval_reason) {
        document.getElementById('detailApprovalReasonText').textContent = entry.approval_reason;
        approvalReason.style.display = 'block';
    }
    
    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('entryDetailsModal'));
    modal.show();
}

function getBrowserName(userAgent) {
    if (!userAgent || userAgent === 'N/A') return 'N/A';
    
    if (userAgent.includes('Chrome') && !userAgent.includes('Edge')) return 'Chrome';
    if (userAgent.includes('Firefox')) return 'Firefox';
    if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) return 'Safari';
    if (userAgent.includes('Edge')) return 'Edge';
    if (userAgent.includes('Opera')) return 'Opera';
    
    return 'Autre';
}

function editEntryFromDetails() {
    if (!currentEntryDetails) return;
    
    // Fermer le modal de détails
    const detailsModal = bootstrap.Modal.getInstance(document.getElementById('entryDetailsModal'));
    detailsModal.hide();
    
    // Ouvrir le modal de rectification avec les données
    setTimeout(() => {
        openRectifyModal(
            currentEntryDetails.id,
            currentEntryDetails.employee_name,
            currentEntryDetails.clock_in_time,
            currentEntryDetails.clock_out_time,
            currentEntryDetails.work_date
        );
    }, 300);
}

function deleteEntryFromDetails() {
    if (!currentEntryDetails) return;
    
    const confirmMsg = `Êtes-vous sûr de vouloir supprimer ce pointage ?\n\n` +
                      `Employé: ${currentEntryDetails.employee_name}\n` +
                      `Date: ${formatDateForDisplay(currentEntryDetails.work_date)}\n` +
                      `Heures: ${currentEntryDetails.clock_in_time} - ${currentEntryDetails.clock_out_time || 'En cours'}\n\n` +
                      `Cette action est irréversible !`;
    
    if (confirm(confirmMsg)) {
        callAjax('delete_entry', { entry_id: currentEntryDetails.id }, function(response) {
            // Fermer le modal
            const detailsModal = bootstrap.Modal.getInstance(document.getElementById('entryDetailsModal'));
            detailsModal.hide();
            
            // Recharger le calendrier
            setTimeout(() => {
                loadCalendarData();
            }, 500);
        });
    }
}

// Fonctions pour afficher tous les pointages d'un jour
function showDayEntries(dateString) {
    const entries = calendarData[dateString] || [];
    
    if (entries.length === 0) {
        alert('Aucun pointage trouvé pour ce jour');
        return;
    }
    
    currentDayEntries = entries;
    
    // Mettre à jour le titre du modal
    document.getElementById('dayEntriesDate').textContent = formatDateForDisplay(dateString);
    
    // Générer le contenu
    generateDayEntriesContent(entries);
    
    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('dayEntriesModal'));
    modal.show();
}

function generateDayEntriesContent(entries) {
    const content = document.getElementById('dayEntriesContent');
    
    if (entries.length === 0) {
        content.innerHTML = '<p class="text-center text-muted">Aucun pointage pour cette journée.</p>';
        return;
    }
    
    // Trier les entrées par heure d'arrivée
    const sortedEntries = entries.sort((a, b) => {
        const timeA = a.start_time || '00:00';
        const timeB = b.start_time || '00:00';
        return timeA.localeCompare(timeB);
    });
    
    let html = `
        <div class="row">
            <div class="col-md-12">
                <h6><i class="fas fa-users"></i> ${entries.length} pointage(s) ce jour-là</h6>
            </div>
        </div>
        <div class="row">
    `;
    
    sortedEntries.forEach((entry, index) => {
        const statusClass = getStatusClass(entry);
        const statusText = getStatusText(entry);
        const period = entry.period === 'morning' ? 'Matin' : 'Après-midi';
        const periodIcon = entry.period === 'morning' ? 'fa-sun' : 'fa-moon';
        const periodColor = entry.period === 'morning' ? 'text-warning' : 'text-info';
        
        html += `
            <div class="col-lg-6 col-md-12 mb-3">
                <div class="card h-100 ${entry.approval_status === 'pending' ? 'border-warning' : ''}">
                    <div class="card-header d-flex justify-content-between align-items-center ${statusClass}">
                        <div>
                            <i class="fas ${periodIcon} ${periodColor}"></i>
                            <strong>${entry.employee}</strong> - ${period}
                        </div>
                        <small class="badge ${getStatusBadgeClass(entry)}">${statusText}</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p class="mb-1">
                                    <i class="fas fa-sign-in-alt text-success"></i>
                                    <strong>Arrivée :</strong><br>
                                    <span class="h6">${entry.start_time || '--:--'}</span>
                                </p>
                            </div>
                            <div class="col-6">
                                <p class="mb-1">
                                    <i class="fas fa-sign-out-alt text-danger"></i>
                                    <strong>Départ :</strong><br>
                                    <span class="h6">${entry.end_time || 'En cours'}</span>
                                </p>
                            </div>
                        </div>
                        
                        ${entry.work_duration ? `
                        <p class="mb-2">
                            <i class="fas fa-clock text-primary"></i>
                            <strong>Durée :</strong> ${parseFloat(entry.work_duration).toFixed(1)}h
                        </p>
                        ` : ''}
                        
                        ${entry.approval_reason ? `
                        <div class="alert alert-warning alert-sm p-2 mb-2">
                            <small><i class="fas fa-exclamation-triangle"></i> ${entry.approval_reason}</small>
                        </div>
                        ` : ''}
                        
                        <div class="d-flex gap-1 mt-2">
                            <button class="btn btn-info btn-sm flex-fill" onclick="showEntryDetails(${entry.id})" title="Voir les détails">
                                <i class="fas fa-eye"></i> Détails
                            </button>
                            <button class="btn btn-warning btn-sm flex-fill" onclick="quickEditEntry(${entry.id})" title="Modifier rapidement">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            <button class="btn btn-danger btn-sm flex-fill" onclick="quickDeleteEntry(${entry.id})" title="Supprimer le pointage">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                            ${entry.approval_status === 'pending' ? `
                            <button class="btn btn-success btn-sm flex-fill" onclick="quickApproveEntry(${entry.id})" title="Approuver">
                                <i class="fas fa-check"></i> Approuver
                            </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    
    content.innerHTML = html;
}

function getStatusClass(entry) {
    if (entry.approval_status === 'pending') return 'bg-warning';
    if (entry.approval_status === 'auto') return 'bg-success';
    if (entry.approval_status === 'approved') return 'bg-primary';
    return 'bg-secondary';
}

function getStatusText(entry) {
    switch (entry.approval_status) {
        case 'auto': return 'Auto-approuvé';
        case 'approved': return 'Approuvé';
        case 'pending': return 'En attente';
        default: return 'Inconnu';
    }
}

function getStatusBadgeClass(entry) {
    switch (entry.approval_status) {
        case 'auto': return 'bg-success';
        case 'approved': return 'bg-primary';
        case 'pending': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
}

function quickEditEntry(entryId) {
    // Fermer le modal des pointages du jour
    const dayModal = bootstrap.Modal.getInstance(document.getElementById('dayEntriesModal'));
    dayModal.hide();
    
    // Trouver l'entrée dans les données actuelles
    const entry = currentDayEntries.find(e => e.id === entryId);
    if (!entry) {
        alert('Entrée non trouvée');
        return;
    }
    
    // Ouvrir le modal de rectification
    setTimeout(() => {
        openRectifyModal(
            entry.id,
            entry.employee,
            entry.start_time,
            entry.end_time,
            entry.date
        );
    }, 300);
}

function quickApproveEntry(entryId) {
    if (confirm('Approuver ce pointage ?')) {
        callAjax('approve_entry', { entry_id: entryId }, function(response) {
            // Actualiser les données du jour
            refreshDayEntries();
            // Recharger le calendrier
            loadCalendarData();
        });
    }
}

function quickDeleteEntry(entryId) {
    // Trouver l'entrée pour afficher des informations dans la confirmation
    const entry = currentDayEntries.find(e => e.id === entryId);
    const employeeName = entry ? entry.employee : 'cet employé';
    const timeInfo = entry ? `(${entry.start_time || '--:--'} - ${entry.end_time || 'En cours'})` : '';
    
    if (confirm(`⚠️ ATTENTION ⚠️\n\nÊtes-vous sûr de vouloir supprimer définitivement ce pointage ?\n\n👤 Employé : ${employeeName}\n⏰ Horaires : ${timeInfo}\n\n❌ Cette action est irréversible !`)) {
        callAjax('delete_entry', { entry_id: entryId }, function(response) {
            if (response.success) {
                // Afficher un message de succès
                alert('✅ Pointage supprimé avec succès');
                
                // Actualiser les données du jour
                refreshDayEntries();
                // Recharger le calendrier
                loadCalendarData();
                
                // Fermer le modal si c'était le dernier pointage
                setTimeout(() => {
                    const dateString = currentDayEntries[0].date;
                    const remainingEntries = calendarData[dateString] || [];
                    if (remainingEntries.length === 0) {
                        const dayModal = bootstrap.Modal.getInstance(document.getElementById('dayEntriesModal'));
                        if (dayModal) {
                            dayModal.hide();
                        }
                    }
                }, 1000);
            }
        });
    }
}

function refreshDayEntries() {
    if (currentDayEntries && currentDayEntries.length > 0) {
        const dateString = currentDayEntries[0].date;
        // Recharger les données du calendrier d'abord
        loadCalendarData();
        // Puis actualiser l'affichage du modal
        setTimeout(() => {
            showDayEntries(dateString);
        }, 500);
    }
}

// Charger les données du calendrier depuis l'API
function loadCalendarData() {
    const employeeId = document.getElementById('employeeFilter').value;
    const month = document.getElementById('monthFilter').value;
    const year = document.getElementById('yearFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    // Construire l'URL de l'API
    const params = new URLSearchParams({
        action: 'get_calendar_data',
        month: month,
        year: year
    });
    
    if (employeeId) {
        params.append('employee_id', employeeId);
    }
    
    if (status) {
        params.append('status', status);
    }
    
    // Afficher un indicateur de chargement
    showLoadingIndicator(true);
    
    fetch(`calendar_api.php?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                calendarData = data.data.calendar_data;
                generateCalendar(parseInt(year), parseInt(month) - 1);
                updateCalendarInfo(data.data);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des données:', error);
            showError('Erreur lors du chargement des données: ' + error.message);
        })
        .finally(() => {
            showLoadingIndicator(false);
        });
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
        
        const dateString = formatDateForAPI(currentDay);
        
        dayElement.innerHTML = `
            <div class="day-number">${currentDay.getDate()}</div>
            <div class="day-entries">
                ${generateDayEntries(dateString)}
            </div>
        `;
        
        grid.appendChild(dayElement);
    }
}

function generateDayEntries(dateString) {
    const entries = calendarData[dateString] || [];
    
    if (entries.length === 0) {
        return '';
    }
    
    const entryHtml = [];
    
    // Grouper par période (matin/après-midi)
    const morningEntries = entries.filter(e => e.period === 'morning');
    const afternoonEntries = entries.filter(e => e.period === 'afternoon');
    
    // Afficher les entrées du matin
    if (morningEntries.length > 0) {
        const morning = morningEntries[0];
        const cssClass = getEntryClass(morning);
        const startTime = morning.start_time ? morning.start_time.substring(0, 5) : '--:--';
        const endTime = morning.end_time ? morning.end_time.substring(0, 5) : '--:--';
        entryHtml.push(`<span class="entry-time ${cssClass}" title="${getEntryTooltip(morning)}" onclick="showEntryDetails(${morning.id})">Matin: ${startTime} - ${endTime}</span>`);
    }
    
    // Afficher les entrées de l'après-midi
    if (afternoonEntries.length > 0) {
        const afternoon = afternoonEntries[0];
        const cssClass = getEntryClass(afternoon);
        const startTime = afternoon.start_time ? afternoon.start_time.substring(0, 5) : '--:--';
        const endTime = afternoon.end_time ? afternoon.end_time.substring(0, 5) : '--:--';
        entryHtml.push(`<span class="entry-time ${cssClass}" title="${getEntryTooltip(afternoon)}" onclick="showEntryDetails(${afternoon.id})">A-Midi: ${startTime} - ${endTime}</span>`);
    }
    
    // Si plusieurs employés le même jour
    if (entries.length > 2) {
        entryHtml.push(`<small class="text-muted" style="cursor: pointer;" onclick="showDayEntries('${dateString}')" title="Cliquer pour voir tous les pointages">+${entries.length - 2} autres</small>`);
    }
    
    return entryHtml.join('');
}

function getEntryClass(entry) {
    let cssClass = entry.period; // 'morning' ou 'afternoon'
    
    if (entry.approval_status === 'pending') {
        cssClass += ' pending';
    }
    
    return cssClass;
}

function getEntryTooltip(entry) {
    let tooltip = `${entry.employee}\n`;
    tooltip += `Heure: ${entry.start_time}`;
    
    if (entry.end_time) {
        tooltip += ` - ${entry.end_time}`;
    }
    
    tooltip += `\nStatut: `;
    
    switch (entry.approval_status) {
        case 'auto':
            tooltip += 'Approuvé automatiquement';
            break;
        case 'approved':
            tooltip += 'Approuvé manuellement';
            break;
        case 'pending':
            tooltip += 'En attente d\'approbation';
            break;
    }
    
    if (entry.approval_reason) {
        tooltip += `\nRaison: ${entry.approval_reason}`;
    }
    
    if (entry.work_duration) {
        tooltip += `\nDurée: ${parseFloat(entry.work_duration).toFixed(1)}h`;
    }
    
    return tooltip;
}

function formatDateForAPI(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
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

function updateCalendarInfo(data) {
    console.log(`Calendrier chargé: ${data.total_entries} entrées pour ${data.month}/${data.year}`);
}

function showLoadingIndicator(show) {
    const indicator = document.getElementById('loadingIndicator');
    if (indicator) {
        indicator.style.display = show ? 'block' : 'none';
    }
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger';
    errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
    
    const calendarContainer = document.querySelector('.calendar-container');
    if (calendarContainer) {
        calendarContainer.insertBefore(errorDiv, calendarContainer.firstChild);
        
        // Supprimer l'erreur après 5 secondes
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
}

// Fonctions pour l'export/impression
function toggleCustomPeriod() {
    const period = document.getElementById('exportPeriod').value;
    const customSection = document.getElementById('customPeriodSection');
    
    if (period === 'custom') {
        customSection.style.display = 'block';
        // Définir dates par défaut
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        document.getElementById('exportStartDate').value = firstDay.toISOString().split('T')[0];
        document.getElementById('exportEndDate').value = today.toISOString().split('T')[0];
    } else {
        customSection.style.display = 'none';
    }
}

function generateReport() {
    const employeeId = document.getElementById('exportEmployeeFilter').value;
    const period = document.getElementById('exportPeriod').value;
    const reportType = document.getElementById('exportType').value;
    
    let startDate, endDate;
    
    // Calculer les dates selon la période
    const today = new Date();
    switch (period) {
        case 'this_week':
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay() + 1); // Lundi
            startDate = startOfWeek.toISOString().split('T')[0];
            endDate = today.toISOString().split('T')[0];
            break;
        case 'last_week':
            const lastWeekEnd = new Date(today);
            lastWeekEnd.setDate(today.getDate() - today.getDay());
            const lastWeekStart = new Date(lastWeekEnd);
            lastWeekStart.setDate(lastWeekEnd.getDate() - 6);
            startDate = lastWeekStart.toISOString().split('T')[0];
            endDate = lastWeekEnd.toISOString().split('T')[0];
            break;
        case 'this_month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            endDate = today.toISOString().split('T')[0];
            break;
        case 'last_month':
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
            startDate = lastMonth.toISOString().split('T')[0];
            endDate = lastMonthEnd.toISOString().split('T')[0];
            break;
        case 'custom':
            startDate = document.getElementById('exportStartDate').value;
            endDate = document.getElementById('exportEndDate').value;
            break;
    }
    
    if (!startDate || !endDate) {
        alert('❌ Veuillez sélectionner une période valide');
        return;
    }
    
    // Préparer les données pour l'API
    const params = new URLSearchParams({
        action: 'generate_report',
        employee_id: employeeId || '',
        start_date: startDate,
        end_date: endDate,
        report_type: reportType
    });
    
    // Afficher un indicateur de chargement
    showLoadingIndicator(true);
    
    fetch(`export_api.php?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayReport(data.data);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la génération du rapport:', error);
            alert('❌ Erreur lors de la génération du rapport: ' + error.message);
        })
        .finally(() => {
            showLoadingIndicator(false);
        });
}

function displayReport(reportData) {
    const reportContent = document.getElementById('reportContent');
    const reportPreview = document.getElementById('reportPreview');
    const printBtn = document.getElementById('printBtn');
    
    // Utiliser directement le HTML généré par l'API
    let html = '';
    
    // L'API retourne directement le HTML dans reportData.html
    if (reportData.html) {
        html = reportData.html;
    } else {
        // Fallback pour l'ancien format si nécessaire
        switch (reportData.type) {
            case 'timesheet':
                html = generateTimesheetHTML(reportData);
                break;
            case 'late_report':
                html = generateLateReportHTML(reportData);
                break;
            case 'overtime_report':
                html = generateOvertimeReportHTML(reportData);
                break;
            case 'summary':
                html = generateSummaryHTML(reportData);
                break;
            default:
                html = '<div class="alert alert-warning">Format de rapport non reconnu</div>';
        }
    }
    
    reportContent.innerHTML = html;
    reportPreview.style.display = 'block';
    printBtn.style.display = 'inline-block';
    
    // Scroll vers le rapport
    reportPreview.scrollIntoView({ behavior: 'smooth' });
}

function generateTimesheetHTML(data) {
    let html = `
        <div class="report-header">
            <h2>Feuille de Temps</h2>
            <p><strong>Période:</strong> ${formatDateForDisplay(data.start_date)} - ${formatDateForDisplay(data.end_date)}</p>
            ${data.employee_name ? `<p><strong>Employé:</strong> ${data.employee_name}</p>` : '<p><strong>Tous les employés</strong></p>'}
            <p><strong>Généré le:</strong> ${new Date().toLocaleString('fr-FR')}</p>
        </div>
    `;
    
    if (data.employees && data.employees.length > 0) {
        data.employees.forEach(employee => {
            html += `
                <h3>${employee.name}</h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Arrivée</th>
                            <th>Départ</th>
                            <th>Heures travaillées</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            employee.entries.forEach(entry => {
                const isLate = entry.late_minutes > 0;
                const rowClass = isLate ? 'late-highlight' : '';
                html += `
                    <tr class="${rowClass}">
                        <td>${formatDateForDisplay(entry.date)}</td>
                        <td>${entry.clock_in || '--:--'}</td>
                        <td>${entry.clock_out || '--:--'}</td>
                        <td>${entry.hours_worked || '0.0'}h</td>
                        <td>${entry.status_text}${isLate ? ` (Retard: ${entry.late_minutes}min)` : ''}</td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #e3f2fd; font-weight: bold;">
                            <td colspan="3"><strong>Total</strong></td>
                            <td><strong>${employee.total_hours || '0.0'}h</strong></td>
                            <td><strong>${employee.total_days || 0} jours</strong></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="print-page-break"></div>
            `;
        });
    } else {
        html += '<p class="text-center">Aucun pointage trouvé pour cette période.</p>';
    }
    
    return html;
}

function generateLateReportHTML(data) {
    let html = `
        <div class="report-header">
            <h2>Rapport des Retards</h2>
            <p><strong>Période:</strong> ${formatDateForDisplay(data.start_date)} - ${formatDateForDisplay(data.end_date)}</p>
            ${data.employee_name ? `<p><strong>Employé:</strong> ${data.employee_name}</p>` : '<p><strong>Tous les employés</strong></p>'}
            <p><strong>Généré le:</strong> ${new Date().toLocaleString('fr-FR')}</p>
        </div>
    `;
    
    if (data.late_entries && data.late_entries.length > 0) {
        html += `
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Employé</th>
                        <th>Date</th>
                        <th>Heure prévue</th>
                        <th>Heure réelle</th>
                        <th>Retard</th>
                        <th>Créneau appliqué</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.late_entries.forEach(entry => {
            html += `
                <tr class="late-highlight">
                    <td>${entry.employee_name}</td>
                    <td>${formatDateForDisplay(entry.date)}</td>
                    <td>${entry.expected_time}</td>
                    <td>${entry.actual_time}</td>
                    <td><strong>${entry.late_minutes} minutes</strong></td>
                    <td>${entry.slot_type}</td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
            
            <div class="report-summary">
                <h4>Résumé des Retards</h4>
                <p><strong>Total des retards:</strong> ${data.summary.total_late_entries || 0}</p>
                <p><strong>Total minutes de retard:</strong> ${data.summary.total_late_minutes || 0} minutes</p>
                <p><strong>Moyenne par retard:</strong> ${data.summary.average_late_minutes || 0} minutes</p>
            </div>
        `;
    } else {
        html += '<p class="text-center">Aucun retard détecté pour cette période. Félicitations ! 🎉</p>';
    }
    
    return html;
}

function generateOvertimeReportHTML(data) {
    let html = `
        <div class="report-header">
            <h2>Rapport des Heures Supplémentaires</h2>
            <p><strong>Période:</strong> ${formatDateForDisplay(data.start_date)} - ${formatDateForDisplay(data.end_date)}</p>
            ${data.employee_name ? `<p><strong>Employé:</strong> ${data.employee_name}</p>` : '<p><strong>Tous les employés</strong></p>'}
            <p><strong>Généré le:</strong> ${new Date().toLocaleString('fr-FR')}</p>
        </div>
    `;
    
    if (data.overtime_entries && data.overtime_entries.length > 0) {
        html += `
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Employé</th>
                        <th>Date</th>
                        <th>Heure fin prévue</th>
                        <th>Heure fin réelle</th>
                        <th>Heures sup.</th>
                        <th>Créneau appliqué</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.overtime_entries.forEach(entry => {
            html += `
                <tr style="background-color: #e8f5e8; color: #2e7d32;">
                    <td>${entry.employee_name}</td>
                    <td>${formatDateForDisplay(entry.date)}</td>
                    <td>${entry.expected_end_time}</td>
                    <td>${entry.actual_end_time}</td>
                    <td><strong>${entry.overtime_minutes} minutes</strong></td>
                    <td>${entry.slot_type}</td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
            
            <div class="report-summary">
                <h4>Résumé des Heures Supplémentaires</h4>
                <p><strong>Total des heures sup.:</strong> ${data.summary.total_overtime_entries || 0}</p>
                <p><strong>Total minutes supplémentaires:</strong> ${data.summary.total_overtime_minutes || 0} minutes</p>
                <p><strong>Total heures supplémentaires:</strong> ${((data.summary.total_overtime_minutes || 0) / 60).toFixed(1)}h</p>
                <p><strong>Moyenne par dépassement:</strong> ${data.summary.average_overtime_minutes || 0} minutes</p>
            </div>
        `;
    } else {
        html += '<p class="text-center">Aucune heure supplémentaire détectée pour cette période. 📋</p>';
    }
    
    return html;
}

function generateSummaryHTML(data) {
    let html = `
        <div class="report-header">
            <h2>Résumé Mensuel</h2>
            <p><strong>Période:</strong> ${formatDateForDisplay(data.start_date)} - ${formatDateForDisplay(data.end_date)}</p>
            <p><strong>Généré le:</strong> ${new Date().toLocaleString('fr-FR')}</p>
        </div>
    `;
    
    if (data.summary) {
        html += `
            <div class="report-summary">
                <h4>Statistiques Générales</h4>
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>Total employés:</strong> ${data.summary.total_employees || 0}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Total heures:</strong> ${data.summary.total_hours || 0}h</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Total retards:</strong> ${data.summary.total_late_entries || 0}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Taux de ponctualité:</strong> ${data.summary.punctuality_rate || 0}%</p>
                    </div>
                </div>
            </div>
        `;
        
        if (data.employees && data.employees.length > 0) {
            html += `
                <h4>Résumé par Employé</h4>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Employé</th>
                            <th>Jours travaillés</th>
                            <th>Total heures</th>
                            <th>Retards</th>
                            <th>Taux présence</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.employees.forEach(employee => {
                html += `
                    <tr>
                        <td>${employee.name}</td>
                        <td>${employee.days_worked || 0}</td>
                        <td>${employee.total_hours || 0}h</td>
                        <td>${employee.late_count || 0}</td>
                        <td>${employee.attendance_rate || 0}%</td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
        }
    }
    
    return html;
}

function printReport() {
    window.print();
}

function hideReport() {
    document.getElementById('reportPreview').style.display = 'none';
    document.getElementById('printBtn').style.display = 'none';
}

// Initialiser le calendrier au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Charger les données initiales
    loadCalendarData();
    
    // Gérer le changement de période pour l'export
    document.getElementById('exportPeriod').addEventListener('change', toggleCustomPeriod);
});
</script>

<!-- Modal QR Code -->
<div class="modal fade" id="qrCodeModal" tabindex="-1" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="qrCodeModalLabel">
                    <i class="fas fa-qrcode"></i> QR Code de Pointage Mobile
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); padding: 2rem; text-align: center; border-radius: 15px;">
                            <h6 class="mb-3">📱 Scanner avec votre smartphone</h6>
                            <div id="qrcode-container">
                                <!-- QR Code sera généré ici -->
                            </div>
                            <p class="mt-3 text-muted">
                                <small>
                                    <i class="fas fa-info-circle"></i>
                                    Scanner ce QR Code pour accéder à la page de pointage mobile
                                </small>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-mobile-alt"></i> Instructions d'utilisation</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item">
                                <strong>1. Scanner le QR Code</strong>
                                <br><small class="text-muted">Utilisez l'appareil photo de votre smartphone</small>
                            </div>
                            <div class="list-group-item">
                                <strong>2. Ouvrir la page</strong>
                                <br><small class="text-muted">Cliquez sur le lien qui apparaît</small>
                            </div>
                            <div class="list-group-item">
                                <strong>3. Pointer</strong>
                                <br><small class="text-muted">Utilisez les boutons Arrivée/Départ</small>
                            </div>
                            <div class="list-group-item">
                                <strong>4. Validation automatique</strong>
                                <br><small class="text-muted">Selon les créneaux horaires configurés</small>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h6><i class="fas fa-link"></i> Lien direct</h6>
                            <div class="input-group">
                                <input type="text" class="form-control" id="qr-link" readonly>
                                <button class="btn btn-outline-secondary" onclick="copyQRLink()">
                                    <i class="fas fa-copy"></i> Copier
                                </button>
                            </div>
                            <small class="text-muted">
                                Vous pouvez aussi partager ce lien directement
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Fermer
                </button>
                <button type="button" class="btn btn-primary" onclick="printQRCode()">
                    <i class="fas fa-print"></i> Imprimer QR Code
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts QR Code -->
<script src="qrcode.min.js"></script>
<script src="qrcode-wrapper.js"></script>
<script src="qrcode-inline.js"></script>
<script>
// URL de la page de pointage QR
const QR_POINTAGE_URL = `${window.location.origin}/pointage_qr.php`;

function showQRCodeModal() {
    // Générer le QR Code
    generateQRCode();
    
    // Remplir le lien
    document.getElementById('qr-link').value = QR_POINTAGE_URL;
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
    modal.show();
}

async function generateQRCode() {
    const container = document.getElementById('qrcode-container');
    container.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Génération...</span></div>';
    
    try {
        console.log('🔄 Génération QR Code pour:', QR_POINTAGE_URL);
        
        // Vérifier si QRCode est disponible
        if (typeof QRCode === 'undefined') {
            throw new Error('QRCode library not available');
        }
        
        // Créer le QR Code
        const canvas = document.createElement('canvas');
        
        // Essayer d'abord avec la méthode toCanvas
        if (QRCode.toCanvas) {
            await QRCode.toCanvas(canvas, QR_POINTAGE_URL, {
                width: 256,
                height: 256,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                },
                errorCorrectionLevel: 'M',
                margin: 2
            });
        } else if (QRCode.create) {
            // Fallback vers la méthode create
            const qrCanvas = QRCode.create(QR_POINTAGE_URL, { width: 256 });
            canvas.width = 256;
            canvas.height = 256;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(qrCanvas, 0, 0);
        } else {
            throw new Error('No suitable QR generation method found');
        }
        
        // Remplacer le spinner par le QR Code
        container.innerHTML = '';
        container.appendChild(canvas);
        
        console.log('✅ QR Code généré avec succès !');
        
    } catch (error) {
        console.error('❌ Erreur génération QR Code:', error);
        
        // Fallback : afficher le lien en tant que QR "visuel"
        container.innerHTML = `
            <div class="text-center p-4" style="border: 2px dashed #007bff; border-radius: 10px; background: #f8f9fa;">
                <i class="fas fa-qrcode fa-3x text-primary mb-3"></i>
                <h6 class="text-primary">QR Code de Pointage</h6>
                <p class="small text-muted mb-3">Scanner ce code ou utiliser le lien ci-dessous</p>
                <div class="qr-visual" style="width: 200px; height: 200px; margin: 0 auto; background: repeating-linear-gradient(45deg, #000 0px, #000 10px, #fff 10px, #fff 20px); position: relative;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 10px; border-radius: 5px;">
                        <i class="fas fa-mobile-alt fa-2x text-primary"></i>
                    </div>
                </div>
                <p class="mt-3 small text-primary">
                    <strong>Lien direct :</strong><br>
                    <a href="${QR_POINTAGE_URL}" target="_blank" class="text-decoration-none">
                        ${QR_POINTAGE_URL}
                    </a>
                </p>
            </div>
        `;
        
        console.log('🔄 Fallback QR visuel affiché');
    }
}

function copyQRLink() {
    const linkInput = document.getElementById('qr-link');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // Pour mobile
    
    try {
        document.execCommand('copy');
        
        // Feedback visuel
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copié !';
        btn.classList.replace('btn-outline-secondary', 'btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.replace('btn-success', 'btn-outline-secondary');
        }, 2000);
        
    } catch (err) {
        console.error('Erreur copie:', err);
        alert('Impossible de copier. Sélectionnez et copiez manuellement.');
    }
}

function printQRCode() {
    const qrCanvas = document.querySelector('#qrcode-container canvas');
    if (!qrCanvas) {
        alert('QR Code non généré');
        return;
    }
    
    // Créer une nouvelle fenêtre pour l'impression
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>QR Code Pointage - GeekBoard</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    text-align: center;
                    padding: 2rem;
                }
                .qr-container {
                    background: white;
                    padding: 2rem;
                    border: 2px solid #333;
                    border-radius: 10px;
                    display: inline-block;
                    margin: 2rem 0;
                }
                canvas {
                    border: 1px solid #ddd;
                }
                .instructions {
                    margin-top: 2rem;
                    font-size: 0.9rem;
                    color: #666;
                }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h1>📱 Pointage Mobile - GeekBoard</h1>
            <div class="qr-container">
                <h2>Scanner ce QR Code</h2>
                ${qrCanvas.outerHTML}
                <p><strong>Pointage par smartphone</strong></p>
            </div>
            <div class="instructions">
                <h3>Instructions :</h3>
                <ol style="text-align: left; display: inline-block;">
                    <li>Ouvrez l'appareil photo de votre smartphone</li>
                    <li>Pointez vers le QR Code</li>
                    <li>Cliquez sur le lien qui apparaît</li>
                    <li>Utilisez les boutons pour pointer</li>
                </ol>
                <p><strong>URL directe :</strong> ${QR_POINTAGE_URL}</p>
            </div>
            <button class="no-print" onclick="window.print()" style="padding: 1rem 2rem; font-size: 1.1rem; margin: 1rem;">
                🖨️ Imprimer
            </button>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Lancer l'impression automatiquement
    printWindow.onload = function() {
        setTimeout(() => {
            printWindow.print();
        }, 500);
    };
}

// Animation CSS pour le background QR
const style = document.createElement('style');
style.textContent = `
    @keyframes rotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Générer le QR Code au chargement de la page (pré-cache)
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔗 URL de pointage QR:', QR_POINTAGE_URL);
});
</script>
