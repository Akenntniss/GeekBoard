<?php
include_once 'includes/night-mode-system.php';
// Détecter le mode PWA
$isPWA = false;
if (isset($_SESSION['pwa_mode']) && $_SESSION['pwa_mode'] === true) {
    $isPWA = true;
} elseif (isset($_COOKIE['pwa_mode']) && $_COOKIE['pwa_mode'] === 'true') {
    $isPWA = true;
}

// Récupération des filtres avec défaut "a_faire" pour le statut
$status = isset($_GET['status']) ? $_GET['status'] : 'a_faire';
$priorite = isset($_GET['priorite']) ? $_GET['priorite'] : null;
$employe_id = isset($_GET['employe_id']) ? $_GET['employe_id'] : null;

// Récupérer l'ID de l'utilisateur connecté
$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Obtenir la connexion à la base de données du magasin
try {
    $shop_pdo = getShopDBConnection();
} catch (Exception $e) {
    // En cas d'erreur de connexion, essayer une connexion alternative
    error_log("Erreur getShopDBConnection: " . $e->getMessage());
    $shop_pdo = null;
}

// Construction de la requête SQL
$sql = "SELECT t.*, 
        e.full_name as employe_nom,
        c.full_name as createur_nom
        FROM taches t
        LEFT JOIN users e ON t.employe_id = e.id
        LEFT JOIN users c ON t.created_by = c.id
        WHERE 1=1";

// Ajout des conditions de filtrage
if ($status) {
    $sql .= " AND t.statut = ?";
}
if ($priorite) {
    $sql .= " AND t.priorite = ?";
}
if ($employe_id) {
    $sql .= " AND t.employe_id = ?";
}

// Ajout du tri
$sql .= " ORDER BY t.date_creation DESC";

// Initialiser un tableau vide par défaut
$taches = [];

if ($shop_pdo) {
    try {
        $stmt = $shop_pdo->prepare($sql);
        $params = [];
        if ($status) {
            $params[] = $status;
        }
        if ($priorite) {
            $params[] = $priorite;
        }
        if ($employe_id) {
            $params[] = $employe_id;
        }
        $stmt->execute($params);
        $taches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des tâches: " . $e->getMessage());
        $taches = [];
    }
}

// Récupération des utilisateurs pour le filtre
$utilisateurs = [];
if ($shop_pdo) {
    try {
        $stmt = $shop_pdo->query("SELECT id, full_name FROM users ORDER BY full_name ASC");
        $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
        $utilisateurs = [];
    }
}

// Comptage des tâches par statut
$total_taches = $total_a_faire = $total_en_cours = $total_terminees = $total_haute_priorite = 0;

if ($shop_pdo) {
    try {
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM taches");
        $total_taches = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $shop_pdo->prepare("SELECT COUNT(*) as total FROM taches WHERE statut = ?");
        $stmt->execute(['a_faire']);
        $total_a_faire = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $shop_pdo->prepare("SELECT COUNT(*) as total FROM taches WHERE statut = ?");
        $stmt->execute(['en_cours']);
        $total_en_cours = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $shop_pdo->prepare("SELECT COUNT(*) as total FROM taches WHERE statut = ?");
        $stmt->execute(['termine']);
        $total_terminees = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $shop_pdo->prepare("SELECT COUNT(*) as total FROM taches WHERE priorite = ?");
        $stmt->execute(['haute']);
        $total_haute_priorite = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des tâches: " . $e->getMessage());
        $total_taches = $total_a_faire = $total_en_cours = $total_terminees = $total_haute_priorite = 0;
    }
}

// Suppression gérée via AJAX - plus de traitement direct ici

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
?>

<style>
/* ========================================
   CSS POUR LA PAGE TACHES MODERNE
======================================== */

/* Masquer la navbar desktop sur mobile */
@media (max-width: 991.98px) {
    #desktop-navbar, nav#desktop-navbar {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }
    
    /* Correction du padding body sur mobile */
    body {
        padding-top: 1rem !important;
    }
}

/* Variables CSS - Mode Jour par défaut */
:root, body:not(.night-mode) {
    /* Couleurs Mode Jour */
    --day-primary: #3b82f6;
    --day-secondary: #10b981;
    --day-accent: #f59e0b;
    --day-danger: #ef4444;
    --day-success: #22c55e;
    --day-warning: #f59e0b;
    --day-info: #06b6d4;
    
    /* Arrière-plans Mode Jour */
    --day-bg: #ffffff;
    --day-bg-secondary: #f8fafc;
    --day-bg-tertiary: #f1f5f9;
    --day-card-bg: #ffffff;
    --day-surface: #f8fafc;
    --day-hover: #f1f5f9;
    
    /* Texte Mode Jour */
    --day-text: #1e293b;
    --day-text-light: #64748b;
    --day-text-muted: #94a3b8;
    
    /* Bordures Mode Jour */
    --day-border: #e2e8f0;
    --day-border-light: #f1f5f9;
    --day-shadow: rgba(0, 0, 0, 0.1);
    
    /* Variables dégradés */
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-success: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-warning: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
}

/* Mode nuit - Variables CSS */
body.night-mode {
    /* Couleurs Mode Nuit */
    --day-primary: #60a5fa;
    --day-secondary: #34d399;
    --day-accent: #fbbf24;
    --day-danger: #f87171;
    --day-success: #4ade80;
    --day-warning: #fbbf24;
    --day-info: #38bdf8;
    
    /* Arrière-plans Mode Nuit */
    --day-bg: #0f172a;
    --day-bg-secondary: #1e293b;
    --day-bg-tertiary: #334155;
    --day-card-bg: rgba(30, 41, 59, 0.95);
    --day-surface: #1e293b;
    --day-hover: #334155;
    
    /* Texte Mode Nuit */
    --day-text: #f1f5f9;
    --day-text-light: #cbd5e1;
    --day-text-muted: #94a3b8;
    
    /* Bordures Mode Nuit */
    --day-border: #334155;
    --day-border-light: #475569;
    --day-shadow: rgba(0, 0, 0, 0.4);
}

/* Fix pour le modal ajouterTacheModal en mode nuit */
body.night-mode #ajouterTacheModal .modal-content {
    background-color: var(--day-card-bg) !important;
    color: var(--day-text) !important;
}

body.night-mode #ajouterTacheModal .modal-header {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)) !important;
    border-bottom: 1px solid var(--day-border) !important;
}

body.night-mode #ajouterTacheModal .modal-body {
    background-color: var(--day-card-bg) !important;
    color: var(--day-text) !important;
}

body.night-mode #ajouterTacheModal .modal-footer {
    background-color: var(--day-card-bg) !important;
    border-top: 1px solid var(--day-border) !important;
    color: var(--day-text) !important;
}

body.night-mode #ajouterTacheModal .form-control,
body.night-mode #ajouterTacheModal .form-select {
    background-color: var(--day-bg-secondary) !important;
    color: var(--day-text) !important;
    border-color: var(--day-border) !important;
}

body.night-mode #ajouterTacheModal .form-control:focus,
body.night-mode #ajouterTacheModal .form-select:focus {
    background-color: var(--day-bg-secondary) !important;
    color: var(--day-text) !important;
    border-color: var(--day-primary) !important;
}

body.night-mode #ajouterTacheModal .input-group-text {
    background-color: var(--day-bg-tertiary) !important;
    color: var(--day-text) !important;
    border-color: var(--day-border) !important;
}

body.night-mode #ajouterTacheModal .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

/* Styles généraux */
body {
    background: var(--day-bg) !important;
    color: var(--day-text) !important;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    transition: all 0.3s ease;
}

/* En mode nuit, on rend le body transparent pour voir #animated-bg */
body.night-mode {
    background: transparent !important;
}

/* Force l'application des styles du mode jour */
/* Forcer le mode jour */
body:not(.night-mode) {
    background: #ffffff !important;
    color: #1e293b !important;
}

body:not(.night-mode) .modern-dashboard {
    background: #ffffff !important;
    color: #1e293b !important;
}

body:not(.night-mode) .status-metric-card,
body:not(.night-mode) .modern-task-card,
body:not(.night-mode) .tasks-container,
body:not(.night-mode) .filters-section {
    background: #ffffff !important;
    color: #1e293b !important;
    border-color: #e2e8f0 !important;
}

/* Mode jour pour tous les éléments */
body:not(.night-mode) .status-metric-badge {
    background: var(--day-primary) !important;
    color: #ffffff !important;
}

body:not(.night-mode) .status-metric-number {
    color: #1e293b !important;
}

body:not(.night-mode) .status-metric-label {
    color: #64748b !important;
}

body:not(.night-mode) .btn-primary {
    background: var(--day-primary) !important;
    border-color: var(--day-primary) !important;
    color: #ffffff !important;
}

body:not(.night-mode) .navbar {
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
}

body:not(.night-mode) .navbar .nav-link {
    color: #1e293b !important;
}

/* Container principal moderne */
.modern-dashboard {
    min-height: 100vh;
    background: transparent; /* Laisser voir le body (et l'animation) */
    padding: 2rem 1rem;
}

/* Titre principal */
.page-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--day-text);
    margin-bottom: 0.5rem;
    text-align: center;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-subtitle {
    font-size: 1.125rem;
    color: var(--day-text-light);
    text-align: center;
    margin-bottom: 1rem;
}

/* ========================================
   MÉTRIQUES DE STATUT (Filtres modernes)
======================================== */
.status-overview-section {
    margin-bottom: 3rem;
}

.status-section-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--day-text);
    margin-bottom: 2rem;
    text-align: center;
    position: relative;
}

.status-section-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary));
    border-radius: 2px;
}

.separator-line {
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary));
    border-radius: 2px;
    margin: 0 auto 1rem auto;
}

.status-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.status-metric-card {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 20px;
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 25px var(--day-shadow);
    text-decoration: none;
    color: inherit;
}

.status-metric-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: 0 20px 60px var(--day-shadow);
    border-color: var(--day-primary);
    text-decoration: none;
    color: inherit;
}

.status-metric-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary));
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.status-metric-card:hover::after {
    transform: scaleX(1);
}

.status-metric-badge {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: white;
    flex-shrink: 0;
    transition: all 0.4s ease;
}

.status-metric-info {
    flex: 1;
}

.status-metric-number {
    font-size: 2.25rem;
    font-weight: 800;
    color: var(--day-text);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.status-metric-label {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--day-text-light);
    opacity: 0.9;
}

.status-metric-indicator {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--day-primary);
    color: white;
    font-size: 0.875rem;
    transition: all 0.4s ease;
    opacity: 0.8;
}

.status-metric-card:hover .status-metric-indicator {
    transform: translateX(6px) scale(1.1);
    opacity: 1;
}

.status-metric-card:hover .status-metric-badge {
    transform: scale(1.15) rotate(10deg);
}

/* Couleurs spécifiques pour chaque métrique */
.all-tasks-card .status-metric-badge {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}

.todo-tasks-card .status-metric-badge {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

.progress-tasks-card .status-metric-badge {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
}

.completed-tasks-card .status-metric-badge {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.high-priority-card .status-metric-badge {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

/* Effets de survol pour les badges */
.all-tasks-card:hover .status-metric-badge {
    box-shadow: 0 10px 30px rgba(99, 102, 241, 0.6);
}

.todo-tasks-card:hover .status-metric-badge {
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.6);
}

.progress-tasks-card:hover .status-metric-badge {
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.6);
}

.completed-tasks-card:hover .status-metric-badge {
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.6);
}

.high-priority-card:hover .status-metric-badge {
    box-shadow: 0 10px 30px rgba(239, 68, 68, 0.6);
}

/* ========================================
   SECTION DES FILTRES AVANCÉS
======================================== */
.filters-section {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 25px var(--day-shadow);
}

.filters-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.filters-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--day-text);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.toggle-filters-btn {
    background: var(--day-primary);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.toggle-filters-btn:hover {
    background: var(--day-secondary);
    transform: translateY(-2px);
}

/* ========================================
   SECTION DES TÂCHES
======================================== */
.tasks-container {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 25px var(--day-shadow);
}

.tasks-header {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    padding: 2rem;
    text-align: center;
}

.tasks-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.tasks-subtitle {
    opacity: 0.9;
    font-size: 0.95rem;
}

/* Vue sélecteur */
.view-selector {
    padding: 1.5rem;
    border-bottom: 1px solid var(--day-border);
    display: flex;
    justify-content: center;
}

.view-selector .btn-group .btn {
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.view-selector .btn-outline-primary {
    border-color: var(--day-primary);
    color: var(--day-primary);
}

.view-selector .btn-outline-primary.active,
.view-selector .btn-outline-primary:hover {
    background: var(--day-primary);
    border-color: var(--day-primary);
    color: white;
    transform: translateY(-2px);
}

/* ========================================
   CARTES DE TÂCHES MODERNES
======================================== */
.tasks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    padding: 2rem;
}

.modern-task-card {
    background: var(--day-card-bg);
    border: 1px solid var(--day-border);
    border-radius: 16px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px var(--day-shadow);
}

.modern-task-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 15px 40px var(--day-shadow);
    border-color: var(--day-primary);
}

.modern-task-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary));
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.modern-task-card:hover::before {
    transform: scaleX(1);
}

.task-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.task-card-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--day-text);
    margin: 0;
    line-height: 1.4;
}

.task-card-priority {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.priority-haute {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.priority-moyenne {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.priority-basse {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.task-card-description {
    color: var(--day-text-light);
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.task-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.task-card-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.8rem;
    color: var(--day-text-muted);
}

.task-card-status {
    padding: 0.35rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.status-a_faire {
    background: rgba(100, 116, 139, 0.1);
    color: #64748b;
    border: 1px solid rgba(100, 116, 139, 0.2);
}

.status-en_cours {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.status-termine {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

/* ========================================
   VUE TABLEAU MODERNE
======================================== */
.tasks-table-container {
    padding: 0;
}

.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.modern-table thead th {
    background: var(--day-bg-secondary);
    color: var(--day-text);
    font-weight: 600;
    padding: 1rem;
    border-bottom: 2px solid var(--day-border);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.modern-table tbody tr {
    transition: all 0.3s ease;
    cursor: pointer;
}

.modern-table tbody tr:hover {
    background: var(--day-hover);
    transform: scale(1.01);
}

.modern-table tbody td {
    padding: 1rem;
    border-bottom: 1px solid var(--day-border);
    color: var(--day-text);
}

/* État vide */
.tasks-empty {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--day-text-muted);
}

.tasks-empty i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.tasks-empty h5 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--day-text);
}

.tasks-empty p {
    margin-bottom: 2rem;
}

.btn-add-task {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    border: none;
    border-radius: 12px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-add-task:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
    color: white;
    text-decoration: none;
}

/* ========================================
   RESPONSIVE
======================================== */
@media (max-width: 768px) {
    .modern-dashboard {
        padding: 1rem 0.5rem;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .status-metrics-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .status-metric-card {
        padding: 1.5rem;
    }
    
    .tasks-grid {
        grid-template-columns: 1fr;
        padding: 1rem;
        gap: 1rem;
    }
    
    .filters-section {
        padding: 1.5rem;
    }
}

/* ========================================
   FIX NAVBAR & ANIMATION SERVO
   ======================================== */
@media (min-width: 992px) {
    /* Masquer le dock mobile sur desktop */
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    
    /* S'assurer que la navbar desktop est visible */
    #desktop-navbar, nav#desktop-navbar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 1030 !important;
        width: 100% !important;
    }
    
    /* Container fluid de la navbar */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.5rem 1rem !important;
        min-height: 60px !important;
    }
    
    /* Logo SERVO - CENTRÉ horizontalement ET verticalement */
    .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 1031 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    /* S'assurer que le loader SERVO est visible */
    .servo-logo-container .loader {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Animations SVG pour toutes les lettres SERVO */
    .servo-logo-container .dash {
        animation: dashArray 2s ease-in-out infinite, dashOffset 2s linear infinite !important;
    }
    
    .servo-logo-container .spin {
        animation: spinDashArray 2s ease-in-out infinite, spin 8s ease-in-out infinite, dashOffset 2s linear infinite !important;
        transform-origin: center;
    }
    
    /* Keyframes pour l'animation .dash (S, E, R, V) */
    @keyframes dashArray {
        0% { stroke-dasharray: 0 1 359 0; }
        50% { stroke-dasharray: 0 359 1 0; }
        100% { stroke-dasharray: 359 1 0 0; }
    }
    
    /* Keyframes pour l'animation .spin (O) */
    @keyframes spinDashArray {
        0% { stroke-dasharray: 270 90; }
        50% { stroke-dasharray: 0 360; }
        100% { stroke-dasharray: 250 90; }
    }
    
    /* Animation du trait qui se dessine */
    @keyframes dashOffset {
        0% { stroke-dashoffset: 385; }
        100% { stroke-dashoffset: 5; }
    }
    
    /* Animation de rotation pour le O */
    @keyframes spin {
        0% { rotate: 0deg; }
        12.5%, 25% { rotate: 270deg; }
        37.5%, 50% { rotate: 540deg; }
        62.5%, 75% { rotate: 810deg; }
        87.5%, 100% { rotate: 1080deg; }
    }
    
    /* S'assurer que tous les SVG sont visibles */
    .servo-logo-container svg,
    .servo-logo-container path {
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Padding pour le body */
    body {
        padding-top: 80px !important;
    }
}

/* Mode nuit pour les métriques de statut */
body.night-mode .status-metric-card {
    background: rgba(30, 30, 35, 0.95);
    border: 1px solid rgba(0, 255, 255, 0.2);
    color: #ffffff;
    box-shadow: 0 8px 32px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

body.night-mode .status-metric-card:hover {
    background: rgba(40, 40, 45, 0.98);
    border-color: rgba(0, 255, 255, 0.4);
    box-shadow: 0 15px 50px rgba(0, 255, 255, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

body.night-mode .modern-task-card {
    background: rgba(30, 30, 35, 0.95);
    border: 1px solid rgba(0, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 255, 255, 0.15);
}

body.night-mode .modern-task-card:hover {
    border-color: rgba(0, 255, 255, 0.4);
    box-shadow: 0 15px 50px rgba(0, 255, 255, 0.25);
}

body.night-mode .tasks-container {
    background: rgba(30, 30, 35, 0.95);
    border: 1px solid rgba(0, 255, 255, 0.2);
}

body.night-mode .filters-section {
    background: rgba(30, 30, 35, 0.95);
    border: 1px solid rgba(0, 255, 255, 0.2);
}
</style>

<!-- Container de particules (mode nuit) -->
<div class="particles-container" id="particles"></div>

<div class="modern-dashboard bg-animated" id="dashboard">
    
    <!-- Titre principal -->
    <div class="text-center mb-5 fade-in">
        <h1 class="page-title">
            <i class="fas fa-tasks me-3"></i>Gestion des Tâches
        </h1>
    </div>

    <!-- 📊 MÉTRIQUES DE STATUT (Filtres modernes) -->
    <div class="status-overview-section fade-in">
        <div class="separator-line"></div>
        <div class="status-metrics-grid">
            <a href="index.php?page=taches_moderne" class="status-metric-card all-tasks-card <?php echo empty($status) && empty($priorite) ? 'active' : ''; ?>">
                <div class="status-metric-badge">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="status-metric-info">
                    <div class="status-metric-number"><?php echo $total_taches; ?></div>
                    <div class="status-metric-label">Toutes les tâches</div>
                </div>
                <div class="status-metric-indicator">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>

            <a href="index.php?page=taches_moderne&status=a_faire" class="status-metric-card todo-tasks-card <?php echo $status == 'a_faire' ? 'active' : ''; ?>">
                <div class="status-metric-badge">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="status-metric-info">
                    <div class="status-metric-number"><?php echo $total_a_faire; ?></div>
                    <div class="status-metric-label">À faire</div>
                </div>
                <div class="status-metric-indicator">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>

            <a href="index.php?page=taches_moderne&status=en_cours" class="status-metric-card progress-tasks-card d-none d-md-flex <?php echo $status == 'en_cours' ? 'active' : ''; ?>">
                <div class="status-metric-badge">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="status-metric-info">
                    <div class="status-metric-number"><?php echo $total_en_cours; ?></div>
                    <div class="status-metric-label">En cours</div>
                </div>
                <div class="status-metric-indicator">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>

            <a href="index.php?page=taches_moderne&status=termine" class="status-metric-card completed-tasks-card d-none d-md-flex <?php echo $status == 'termine' ? 'active' : ''; ?>">
                <div class="status-metric-badge">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="status-metric-info">
                    <div class="status-metric-number"><?php echo $total_terminees; ?></div>
                    <div class="status-metric-label">Terminées</div>
                </div>
                <div class="status-metric-indicator">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>

            <a href="index.php?page=taches_moderne&priorite=haute" class="status-metric-card high-priority-card d-none d-md-flex <?php echo $priorite == 'haute' ? 'active' : ''; ?>">
                <div class="status-metric-badge">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="status-metric-info">
                    <div class="status-metric-number"><?php echo $total_haute_priorite; ?></div>
                    <div class="status-metric-label">Haute priorité</div>
                </div>
                <div class="status-metric-indicator">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Filtres avancés (collapsible) -->
    <div class="filters-section fade-in">
        <div class="filters-header">
            <div class="filters-title">
                <i class="fas fa-filter"></i>
                Filtres avancés
            </div>
            <button class="toggle-filters-btn" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false">
                <i class="fas fa-chevron-down me-1"></i>
                Afficher
            </button>
        </div>
        
        <div class="collapse" id="filterCollapse">
            <form method="GET" action="index.php" class="row g-3">
                <input type="hidden" name="page" value="taches_moderne">
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tous</option>
                        <option value="a_faire" <?php echo $status == 'a_faire' ? 'selected' : ''; ?>>À faire</option>
                        <option value="en_cours" <?php echo $status == 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="termine" <?php echo $status == 'termine' ? 'selected' : ''; ?>>Terminé</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="priorite" class="form-label">Priorité</label>
                    <select class="form-select" id="priorite" name="priorite">
                        <option value="">Toutes</option>
                        <option value="basse" <?php echo $priorite == 'basse' ? 'selected' : ''; ?>>Basse</option>
                        <option value="moyenne" <?php echo $priorite == 'moyenne' ? 'selected' : ''; ?>>Moyenne</option>
                        <option value="haute" <?php echo $priorite == 'haute' ? 'selected' : ''; ?>>Haute</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="employe_id" class="form-label">Employé</label>
                    <select class="form-select" id="employe_id" name="employe_id">
                        <option value="">Tous</option>
                        <?php foreach ($utilisateurs as $utilisateur): ?>
                        <option value="<?php echo $utilisateur['id']; ?>" <?php echo $employe_id == $utilisateur['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($utilisateur['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>Filtrer
                    </button>
                    <a href="index.php?page=taches_moderne" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Section des tâches -->
    <div class="tasks-container fade-in">
        <!-- Sélecteur de vue -->
        <div class="view-selector">
            <div class="btn-group" role="group" aria-label="Sélection de vue">
                <button type="button" class="btn btn-outline-primary" id="card-view-btn" onclick="switchView('cards')">
                    <i class="fas fa-th-large me-2"></i>Cartes
                </button>
                <button type="button" class="btn btn-outline-primary active" id="table-view-btn" onclick="switchView('table')">
                    <i class="fas fa-table me-2"></i>Tableau
                </button>
            </div>
        </div>
        
        <?php if (empty($taches)): ?>
            <div class="tasks-empty">
                <i class="fas fa-tasks"></i>
                <h5>Aucune tâche trouvée</h5>
                <p>Ajoutez une nouvelle tâche pour commencer</p>
                <button type="button" class="btn-add-task" data-bs-toggle="modal" data-bs-target="#ajouterTacheModal">
                    <i class="fas fa-plus"></i>
                    Nouvelle Tâche
                </button>
            </div>
        <?php else: ?>
            
            <!-- Vue en cartes -->
            <div id="cards-view" class="tasks-grid" style="display: none;">
                <?php foreach ($taches as $tache): ?>
                <div class="modern-task-card" data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                    <div class="task-card-header">
                        <h4 class="task-card-title"><?php echo htmlspecialchars($tache['titre'] ?? ''); ?></h4>
                        <span class="task-card-priority priority-<?php echo strtolower($tache['priorite'] ?? 'basse'); ?>">
                            <?php echo htmlspecialchars($tache['priorite'] ?? 'Basse'); ?>
                        </span>
                    </div>
                    
                    <div class="task-card-description">
                        <?php echo htmlspecialchars($tache['description'] ?? 'Aucune description disponible'); ?>
                    </div>
                    
                    <div class="task-card-footer">
                        <div class="task-card-meta">
                            <span><i class="fas fa-calendar-alt me-1"></i><?php echo date('d/m/Y', strtotime($tache['date_creation'] ?? 'now')); ?></span>
                            <?php if ($tache['employe_nom']): ?>
                            <span><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($tache['employe_nom']); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="task-card-status status-<?php echo $tache['statut'] ?? 'a_faire'; ?>">
                            <i class="fas <?php 
                                echo $tache['statut'] == 'termine' ? 'fa-check' : 
                                    ($tache['statut'] == 'en_cours' ? 'fa-spinner' : 'fa-clock'); 
                            ?>"></i>
                            <?php echo $tache['statut'] == 'termine' ? 'Terminé' : 
                                ($tache['statut'] == 'en_cours' ? 'En cours' : 'À faire'); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Vue en tableau -->
            <div id="table-view" class="tasks-table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Description</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th>Assigné à</th>
                            <th>Date création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($taches as $tache): ?>
                        <tr data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                            <td>
                                <strong><?php echo htmlspecialchars($tache['titre'] ?? ''); ?></strong>
                            </td>
                            <td class="description-cell">
                                <?php 
                                $description = $tache['description'] ?? 'Aucune description';
                                echo htmlspecialchars(strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description); 
                                ?>
                            </td>
                            <td>
                                <span class="task-card-priority priority-<?php echo strtolower($tache['priorite'] ?? 'basse'); ?>">
                                    <?php echo htmlspecialchars($tache['priorite'] ?? 'Basse'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="task-card-status status-<?php echo $tache['statut'] ?? 'a_faire'; ?>">
                                    <i class="fas <?php 
                                        echo $tache['statut'] == 'termine' ? 'fa-check' : 
                                            ($tache['statut'] == 'en_cours' ? 'fa-spinner' : 'fa-clock'); 
                                    ?>"></i>
                                    <?php echo $tache['statut'] == 'termine' ? 'Terminé' : 
                                        ($tache['statut'] == 'en_cours' ? 'En cours' : 'À faire'); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($tache['employe_nom'] ?? 'Non assigné'); ?>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($tache['date_creation'] ?? 'now')); ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); afficherModalEdition(<?php echo $tache['id']; ?>)" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); confirmerSuppression(<?php echo $tache['id']; ?>)" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        <?php endif; ?>
    </div>
</div>

<script>
// Fonction pour basculer entre les vues
function switchView(viewType) {
    const cardsView = document.getElementById('cards-view');
    const tableView = document.getElementById('table-view');
    const cardBtn = document.getElementById('card-view-btn');
    const tableBtn = document.getElementById('table-view-btn');
    
    if (viewType === 'cards') {
        cardsView.style.display = 'grid';
        tableView.style.display = 'none';
        cardBtn.classList.add('active');
        tableBtn.classList.remove('active');
        localStorage.setItem('tasks_view_preference', 'cards');
    } else {
        cardsView.style.display = 'none';
        tableView.style.display = 'block';
        tableBtn.classList.add('active');
        cardBtn.classList.remove('active');
        localStorage.setItem('tasks_view_preference', 'table');
    }
}

// Restaurer la préférence de vue au chargement
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('tasks_view_preference') || 'table';
    switchView(savedView);
    
    // Animation de fade-in pour les éléments
    const fadeElements = document.querySelectorAll('.fade-in');
    fadeElements.forEach((element, index) => {
        setTimeout(() => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'all 0.6s ease';
            
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, 100);
        }, index * 100);
    });
});

// Fonctions pour les actions sur les tâches (à implémenter selon vos besoins)
function afficherDetailsTache(event, taskId) {
    // Implémentation à ajouter pour afficher les détails de la tâche
    console.log('Afficher détails tâche:', taskId);
    // Vous pouvez réutiliser le code du modal existant de taches.php
}

function afficherModalEdition(taskId) {
    // Implémentation à ajouter pour modifier la tâche
    console.log('Modifier tâche:', taskId);
}

function confirmerSuppression(taskId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
        // Utiliser AJAX pour éviter les problèmes de headers
        fetch('ajax_handlers/delete_task.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${taskId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Tâche supprimée avec succès !');
                window.location.reload();
            } else {
                alert('Erreur lors de la suppression : ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la communication avec le serveur');
        });
    }
}

// Gestion du mode jour/nuit automatique basé sur les préférences système
function updateTheme() {
    // Détecter automatiquement les préférences système
    const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    console.log('🎨 Détection automatique du thème système:', prefersDarkMode ? 'Mode sombre' : 'Mode clair');
    
    if (prefersDarkMode) {
        document.body.classList.add('night-mode');
        console.log('✅ Mode nuit activé automatiquement');
    } else {
        document.body.classList.remove('night-mode');
        // S'assurer qu'aucun élément n'a la classe night-mode
        document.querySelectorAll('.night-mode').forEach(el => {
            if (el !== document.body) {
                el.classList.remove('night-mode');
            }
        });
        console.log('✅ Mode jour activé automatiquement');
    }
    
    // Forcer le re-calcul des styles CSS
    document.body.style.display = 'none';
    document.body.offsetHeight; // Trigger reflow
    document.body.style.display = '';
}

// Écouter les changements de préférences système
function createParticles() {
    const particlesContainer = document.getElementById('particles');
    if (!particlesContainer) return;
    
    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.cssText = `
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(0, 255, 255, 0.3);
            border-radius: 50%;
            animation: float ${Math.random() * 3 + 2}s ease-in-out infinite;
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            animation-delay: ${Math.random() * 2}s;
        `;
        particlesContainer.appendChild(particle);
    }
}

// Style pour les particules
const particleStyle = document.createElement('style');
particleStyle.textContent = `
    .particles-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.3; }
        50% { transform: translateY(-20px) rotate(180deg); opacity: 0.8; }
    }
    
    .bg-animated {
        position: relative;
        z-index: 2;
    }
`;
document.head.appendChild(particleStyle);

// Fonctions pour gérer les actions sur les tâches
function updateStatus(status) {
    const taskId = document.getElementById('statusTaskId').value;
    if (!taskId) return;
    
    // Envoyer la requête de mise à jour
    fetch('ajax_handlers/update_task_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `task_id=${taskId}&status=${status}&action=update_status`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('changeStatusModal'));
            modal.hide();
            
            // Recharger la page pour voir les changements
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            alert('Erreur lors de la mise à jour du statut');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la communication avec le serveur');
    });
}

function updatePriorite(priorite) {
    const taskId = document.getElementById('prioriteTaskId').value;
    if (!taskId) return;
    
    // Envoyer la requête de mise à jour
    fetch('ajax_handlers/update_task_priority.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `task_id=${taskId}&priority=${priorite}&action=update_priority`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('changePrioriteModal'));
            modal.hide();
            
            // Recharger la page pour voir les changements
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            alert('Erreur lors de la mise à jour de la priorité');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la communication avec le serveur');
    });
}

function updateEmploye(employeId) {
    const taskId = document.getElementById('employeTaskId').value;
    if (!taskId) return;
    
    // Envoyer la requête de mise à jour
    fetch('ajax_handlers/update_task_employee.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `task_id=${taskId}&employee_id=${employeId}&action=update_employee`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('changeEmployeModal'));
            modal.hide();
            
            // Recharger la page pour voir les changements
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            alert('Erreur lors de l\'assignation de la tâche');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la communication avec le serveur');
    });
}

// Fonction pour afficher les pièces jointes d'une tâche
function displayTaskAttachments(attachments) {
    const attachmentsSection = document.getElementById('task-attachments-section');
    const attachmentsList = document.getElementById('task-attachments-list');
    const attachmentsCount = document.getElementById('attachments-count');
    
    if (!attachments || attachments.length === 0) {
        attachmentsSection.style.display = 'none';
        return;
    }
    
    // Afficher la section et mettre à jour le compteur
    attachmentsSection.style.display = 'block';
    attachmentsCount.textContent = attachments.length;
    
    // Vider la liste existante
    attachmentsList.innerHTML = '';
    
    // Ajouter chaque pièce jointe
    attachments.forEach(attachment => {
        const attachmentItem = document.createElement('div');
        attachmentItem.className = 'attachment-item';
        
        const fileIcon = attachment.file_icon || {icon: 'fas fa-file', color: '#6c757d'};
        
        attachmentItem.innerHTML = `
            <div class="attachment-icon" style="color: ${fileIcon.color};">
                <i class="${fileIcon.icon}"></i>
            </div>
            <div class="attachment-info">
                <div class="attachment-name" title="${attachment.file_name}">
                    ${attachment.file_name}
                </div>
                <div class="attachment-meta">
                    <span class="attachment-size">${attachment.file_size_formatted}</span>
                    <span class="attachment-date">${attachment.date_upload_formatted}</span>
                    ${attachment.uploaded_by_name ? `<span class="attachment-uploader">par ${attachment.uploaded_by_name}</span>` : ''}
                </div>
            </div>
            <div class="attachment-actions">
                <a href="${attachment.file_url}" target="_blank" class="btn btn-sm btn-outline-primary" title="Ouvrir">
                    <i class="fas fa-external-link-alt"></i>
                </a>
                <a href="${attachment.file_url}" download="${attachment.file_name}" class="btn btn-sm btn-outline-success" title="Télécharger">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        `;
        
        attachmentsList.appendChild(attachmentItem);
    });
}

// Fonction pour afficher les détails d'une tâche
function afficherDetailsTache(event, taskId) {
    // Empêcher les clics sur les boutons d'action de déclencher cette fonction
    if (event.target.closest('.btn') || event.target.closest('button')) {
        return;
    }
    
    // Charger les détails de la tâche via AJAX
    fetch(`ajax_handlers/get_task_details.php?id=${taskId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const task = data.task;
            
            // Remplir le modal avec les détails
            document.getElementById('task-title').textContent = task.titre || '';
            document.getElementById('task-description').textContent = task.description || 'Aucune description disponible';
            document.getElementById('task-priority').textContent = task.priorite || '';
            document.getElementById('task-priority').className = `modern-priority-badge priority-${(task.priorite || 'basse').toLowerCase()}`;
            document.getElementById('task-status').textContent = task.statut_display || '';
            document.getElementById('task-status').className = `modern-status-badge status-${task.statut || 'a_faire'}`;
            document.getElementById('task-created-date').textContent = task.date_creation_formatted || '';
            document.getElementById('task-assignee').textContent = task.employe_nom || 'Non assigné';
            
            // Afficher les pièces jointes
            displayTaskAttachments(task.attachments || []);
            
            // Configurer les boutons d'action
            document.getElementById('start-task-btn').setAttribute('data-task-id', taskId);
            document.getElementById('complete-task-btn').setAttribute('data-task-id', taskId);
            document.getElementById('edit-task-btn').setAttribute('onclick', `afficherModalEdition(${taskId})`);
            
            // Masquer le loader et afficher le contenu
            document.getElementById('task-description-loader').style.display = 'none';
            document.getElementById('task-description').style.display = 'block';
            
            // Afficher le modal
            const modal = new bootstrap.Modal(document.getElementById('taskDetailsModal'));
            modal.show();
        } else {
            alert('Erreur lors du chargement des détails de la tâche');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la communication avec le serveur');
    });
}

// Fonction pour afficher le modal d'édition d'une tâche
function afficherModalEdition(taskId) {
    // Charger les détails de la tâche pour l'édition
    fetch(`ajax_handlers/get_task_details.php?id=${taskId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const task = data.task;
            
            // Remplir le formulaire d'édition
            document.getElementById('edit_task_id').value = taskId;
            document.getElementById('edit_titre').value = task.titre || '';
            document.getElementById('edit_description').value = task.description || '';
            document.getElementById('edit_priorite').value = task.priorite || '';
            document.getElementById('edit_statut').value = task.statut || '';
            document.getElementById('edit_employe_id').value = task.employe_id || '';
            document.getElementById('edit_date_limite').value = task.date_limite || '';
            
            // Afficher le modal d'édition
            const modal = new bootstrap.Modal(document.getElementById('editTaskModal'));
            modal.show();
        } else {
            alert('Erreur lors du chargement des détails de la tâche');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la communication avec le serveur');
    });
}

// Fonction pour sauvegarder les modifications
function sauvegarderModification() {
    const form = document.getElementById('editTaskForm');
    
    // Vérifier la validité du formulaire
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return false;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'modifier_tache');
    
    // Désactiver le bouton pendant le traitement
    const saveButton = document.querySelector('#editTaskModal .btn-save');
    const originalText = saveButton.innerHTML;
    saveButton.disabled = true;
    saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Traitement...';
    
    // Envoyer la requête AJAX
    fetch('ajax_handlers/update_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Succès
            saveButton.innerHTML = '<i class="fas fa-check me-2"></i>Succès!';
            
            // Délai avant de fermer le modal
            setTimeout(() => {
                const editModal = bootstrap.Modal.getInstance(document.getElementById('editTaskModal'));
                editModal.hide();
                
                // Recharger la page
                window.location.reload();
            }, 1000);
        } else {
            // Réactiver le bouton
            saveButton.disabled = false;
            saveButton.innerHTML = originalText;
            
            alert(data.message || "Erreur lors de la modification de la tâche");
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        
        // Réactiver le bouton
        saveButton.disabled = false;
        saveButton.innerHTML = originalText;
        
        alert('Erreur lors de la communication avec le serveur');
    });
}

// Fonction pour afficher le modal de changement de statut
function afficherModalStatut(event, element) {
    event.stopPropagation();
    const taskId = element.getAttribute('data-task-id');
    document.getElementById('statusTaskId').value = taskId;
    
    const modal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
    modal.show();
}

// Fonction pour afficher le modal de changement de priorité
function afficherModalPriorite(event, element) {
    event.stopPropagation();
    const taskId = element.getAttribute('data-task-id');
    document.getElementById('prioriteTaskId').value = taskId;
    
    const modal = new bootstrap.Modal(document.getElementById('changePrioriteModal'));
    modal.show();
}

// Fonction pour afficher le modal d'assignation d'employé
function afficherModalEmploye(event, element) {
    event.stopPropagation();
    const taskId = element.getAttribute('data-task-id');
    document.getElementById('employeTaskId').value = taskId;
    
    const modal = new bootstrap.Modal(document.getElementById('changeEmployeModal'));
    modal.show();
}

// Fonction pour mettre à jour le statut depuis les boutons du modal de détails
function updateTaskStatus(taskId, status) {
    if (!taskId) {
        console.error('ID de tâche manquant');
        alert('Erreur: Impossible d\'identifier la tâche');
        return;
    }
    
    // Trouver le bouton qui a été cliqué
    const button = event.target.closest('button');
    if (!button) return;
    
    // Afficher un spinner pendant le traitement
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
    button.disabled = true;
    
    // Envoyer la requête de mise à jour vers le bon endpoint (avec tracking d'activité)
    fetch('ajax/update_tache_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${taskId}&statut=${status}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Afficher une notification de succès
            alert('Statut de la tâche mis à jour avec succès.');
            
            // Fermer le modal de détails
            const modal = bootstrap.Modal.getInstance(document.getElementById('taskDetailsModal'));
            if (modal) modal.hide();
            
            // Recharger la page pour voir les changements
            setTimeout(() => {
                window.location.reload();
            }, 300);
        } else {
            alert(data.message || 'Erreur lors de la mise à jour du statut de la tâche');
            // Rétablir le contenu original du bouton
            button.innerHTML = originalContent;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la communication avec le serveur. Veuillez réessayer.');
        // Rétablir le contenu original du bouton
        button.innerHTML = originalContent;
        button.disabled = false;
    });
}

// ========================================
// GESTION DU MODAL AJOUTER TÂCHE
// ========================================

// Variables globales pour le modal
let modalFilesArray = [];
let modalFileInput = null;

// Initialisation du modal d'ajout de tâche
document.addEventListener('DOMContentLoaded', function() {
    initializeAddTaskModal();
});

function initializeAddTaskModal() {
    // Éléments du modal
    const modalPriorityButtons = document.querySelectorAll('.btn-modal-priority');
    const modalPriorityInput = document.getElementById('modal_priorite');
    
    const modalStatusButtons = document.querySelectorAll('.btn-modal-status');
    const modalStatusInput = document.getElementById('modal_statut');
    
    const modalUserButtons = document.querySelectorAll('.modal-user-btn');
    const modalEmployeInput = document.getElementById('modal_employe_id');
    const modalShowAllUsersBtn = document.getElementById('modalShowAllUsersBtn');
    const modalAllUsersList = document.getElementById('modalAllUsersList');
    
    const modalSaveTaskBtn = document.getElementById('modalSaveTaskBtn');
    const ajouterTacheForm = document.getElementById('ajouterTacheForm');
    
    // Gestion des fichiers supprimée
    
    // Activation des boutons de priorité
    modalPriorityButtons.forEach(button => {
        button.addEventListener('click', function() {
            modalPriorityButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            modalPriorityInput.value = this.dataset.value;
            
            // Styles actifs
            this.style.transform = 'translateY(-2px)';
            this.style.fontWeight = '500';
            
            // Couleurs spécifiques
            switch(this.dataset.value) {
                case 'basse':
                    this.style.backgroundColor = '#198754';
                    this.style.color = 'white';
                    this.style.borderColor = '#198754';
                    break;
                case 'moyenne':
                    this.style.backgroundColor = '#0d6efd';
                    this.style.color = 'white';
                    this.style.borderColor = '#0d6efd';
                    break;
                case 'haute':
                    this.style.backgroundColor = '#ffc107';
                    this.style.color = '#212529';
                    this.style.borderColor = '#ffc107';
                    break;
                case 'urgente':
                    this.style.backgroundColor = '#dc3545';
                    this.style.color = 'white';
                    this.style.borderColor = '#dc3545';
                    break;
            }
            
            setTimeout(() => {
                this.style.transform = '';
            }, 200);
        });
    });
    
    // Activation des boutons de statut
    modalStatusButtons.forEach(button => {
        button.addEventListener('click', function() {
            modalStatusButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            modalStatusInput.value = this.dataset.value;
            
            // Styles actifs
            this.style.transform = 'translateY(-2px)';
            this.style.fontWeight = '500';
            
            // Couleurs spécifiques
            switch(this.dataset.value) {
                case 'a_faire':
                    this.style.backgroundColor = '#6c757d';
                    this.style.color = 'white';
                    this.style.borderColor = '#6c757d';
                    break;
                case 'en_cours':
                    this.style.backgroundColor = '#0dcaf0';
                    this.style.color = 'white';
                    this.style.borderColor = '#0dcaf0';
                    break;
                case 'termine':
                    this.style.backgroundColor = '#198754';
                    this.style.color = 'white';
                    this.style.borderColor = '#198754';
                    break;
            }
            
            setTimeout(() => {
                this.style.transform = '';
            }, 200);
        });
    });
    
    // Activation des boutons d'utilisateurs
    modalUserButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Retirer la classe active et les styles de tous les boutons
            modalUserButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.style.backgroundColor = '';
                btn.style.color = '';
                btn.style.borderColor = '';
                btn.style.transform = '';
                btn.style.fontWeight = '';
            });
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            modalEmployeInput.value = this.dataset.value;
            
            // Appliquer les styles actifs permanents
            this.style.backgroundColor = '#0d6efd';
            this.style.color = 'white';
            this.style.borderColor = '#0d6efd';
            this.style.fontWeight = '500';
            this.style.transform = 'translateY(-2px)';
            
            // Animation temporaire
            setTimeout(() => {
                if (this.classList.contains('active')) {
                    this.style.transform = 'translateY(-1px)'; // Garder légèrement surélevé
                }
            }, 200);
        });
    });
    
    // Afficher/masquer la liste complète des utilisateurs
    if (modalShowAllUsersBtn) {
        modalShowAllUsersBtn.addEventListener('click', function() {
            if (modalAllUsersList.style.display === 'none') {
                modalAllUsersList.style.display = 'block';
                this.innerHTML = '<i class="fas fa-users-slash me-2"></i>Masquer';
            } else {
                modalAllUsersList.style.display = 'none';
                this.innerHTML = '<i class="fas fa-users me-2"></i>Voir tous';
            }
        });
    }
    
    // Définir des valeurs par défaut
    if (modalPriorityButtons.length > 0) {
        const defaultPriority = document.querySelector('.btn-modal-priority[data-value="moyenne"]');
        if (defaultPriority) {
            defaultPriority.click();
        }
    }
    
    if (modalStatusButtons.length > 0) {
        const defaultStatus = document.querySelector('.btn-modal-status[data-value="a_faire"]');
        if (defaultStatus) {
            defaultStatus.click();
        }
    }
    
    // Sélectionner "Non assigné" par défaut
    if (modalUserButtons.length > 0) {
        const defaultUser = document.querySelector('.modal-user-btn[data-value=""]');
        if (defaultUser) {
            defaultUser.click();
        }
    }
    
    // Gestion des pièces jointes
    // Gestion des fichiers supprimée
    
    // Gestion de la sauvegarde
    modalSaveTaskBtn.addEventListener('click', function() {
        saveNewTask();
    });
    
    // Reset du modal à la fermeture
    document.getElementById('ajouterTacheModal').addEventListener('hidden.bs.modal', function() {
        resetAddTaskModal();
    });
}

// Fonction de gestion des fichiers supprimée

function saveNewTask() {
    const form = document.getElementById('ajouterTacheForm');
    const saveBtn = document.getElementById('modalSaveTaskBtn');
    
    // Validation
    const titre = document.getElementById('modal_titre').value.trim();
    const description = document.getElementById('modal_description').value.trim();
    const priorite = document.getElementById('modal_priorite').value;
    const statut = document.getElementById('modal_statut').value;
    
    if (!titre) {
        alert('Le titre est obligatoire.');
        return;
    }
    
    if (!description) {
        alert('La description est obligatoire.');
        return;
    }
    
    if (!priorite) {
        alert('Veuillez sélectionner une priorité.');
        return;
    }
    
    if (!statut) {
        alert('Veuillez sélectionner un statut.');
        return;
    }
    
    // Désactiver le bouton pendant le traitement
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';
    
    // Préparer les données du formulaire
    const formData = new FormData(form);
    
    // Debug: vérifier les fichiers
    console.log('Fichiers dans modalFilesArray:', modalFilesArray.length);
    
    if (modalFileInput && modalFileInput.files) {
        console.log('Fichiers dans input:', modalFileInput.files.length);
    } else {
        console.log('Input fichier non trouvé ou pas de fichiers');
    }
    
    // S'assurer que les fichiers sont bien dans le FormData
    if (modalFilesArray.length > 0) {
        // Ajouter manuellement les fichiers au FormData
        modalFilesArray.forEach((file, index) => {
            formData.append(`attachments[${index}]`, file);
        });
        console.log('Fichiers ajoutés manuellement au FormData');
    }
    
    // Envoyer la requête AJAX
    fetch('ajax_handlers/add_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers.get('content-type'));
        
        // Vérifier si la réponse est OK
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Lire la réponse comme texte d'abord pour debug
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        
        // Essayer de parser comme JSON
        try {
            const data = JSON.parse(text);
            
            if (data.success) {
                // Succès
                saveBtn.innerHTML = '<i class="fas fa-check me-2"></i>Succès!';
                
                // Fermer le modal après un délai
                setTimeout(() => {
                    try {
                        const modalElement = document.getElementById('ajouterTacheModal');
                        if (modalElement) {
                            const modal = bootstrap.Modal.getInstance(modalElement);
                            if (modal) {
                                modal.hide();
                            } else {
                                // Créer une nouvelle instance si nécessaire
                                const newModal = new bootstrap.Modal(modalElement);
                                newModal.hide();
                            }
                        }
                    } catch (e) {
                        console.error('Erreur lors de la fermeture du modal:', e);
                    }
                    
                    // Recharger la page
                    window.location.reload();
                }, 1000);
            } else {
                // Réactiver le bouton
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
                
                alert(data.message || "Erreur lors de l'ajout de la tâche");
            }
        } catch (e) {
            console.error('Erreur parsing JSON:', e);
            console.error('Réponse reçue:', text);
            
            // Réactiver le bouton
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
            
            alert('Erreur: Réponse invalide du serveur. Vérifiez la console pour plus de détails.');
        }
    })
    .catch(error => {
        console.error('Erreur fetch:', error);
        
        // Réactiver le bouton
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
        
        alert('Erreur lors de la communication avec le serveur: ' + error.message);
    });
}

function resetAddTaskModal() {
    // Reset du formulaire
    document.getElementById('ajouterTacheForm').reset();
    
    // Reset des champs cachés
    document.getElementById('modal_priorite').value = '';
    document.getElementById('modal_statut').value = '';
    document.getElementById('modal_employe_id').value = '';
    
    // Reset des boutons
    document.querySelectorAll('.btn-modal-priority, .btn-modal-status, .modal-user-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.backgroundColor = '';
        btn.style.color = '';
        btn.style.borderColor = '';
        btn.style.transform = '';
        btn.style.fontWeight = '';
    });
    
    // Reset des fichiers supprimé
    
    // Reset de la liste des utilisateurs
    const modalAllUsersList = document.getElementById('modalAllUsersList');
    const modalShowAllUsersBtn = document.getElementById('modalShowAllUsersBtn');
    if (modalAllUsersList && modalShowAllUsersBtn) {
        modalAllUsersList.style.display = 'none';
        modalShowAllUsersBtn.innerHTML = '<i class="fas fa-users me-2"></i>Voir tous';
    }
    
    // Reset du bouton de sauvegarde
    const saveBtn = document.getElementById('modalSaveTaskBtn');
    saveBtn.disabled = false;
    saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Enregistrer la tâche';
    
    // Redéfinir les valeurs par défaut
    setTimeout(() => {
        const defaultPriority = document.querySelector('.btn-modal-priority[data-value="moyenne"]');
        if (defaultPriority) {
            defaultPriority.click();
        }
        
        const defaultStatus = document.querySelector('.btn-modal-status[data-value="a_faire"]');
        if (defaultStatus) {
            defaultStatus.click();
        }
        
        // Sélectionner "Non assigné" par défaut
        const defaultUser = document.querySelector('.modal-user-btn[data-value=""]');
        if (defaultUser) {
            defaultUser.click();
        }
    }, 100);
}
</script>

<!-- Modal moderne pour afficher les détails d'une tâche -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modern-task-modal">
            <!-- En-tête moderne avec dégradé -->
            <div class="modern-task-modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="modal-title-section">
                        <h5 class="modal-title" id="taskDetailsModalLabel">Détails de la tâche</h5>
                        <p class="modal-subtitle">Informations complètes</p>
                    </div>
                </div>
                <button type="button" class="modern-close-btn" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Corps du modal -->
            <div class="modal-body modern-task-modal-body">
                <div class="task-detail-container">
                    <!-- Section titre et priorité -->
                    <div class="task-header-section">
                        <div class="task-title-container">
                            <h4 id="task-title" class="modern-task-title"></h4>
                            <div class="task-meta">
                                <div class="priority-container">
                                    <span class="priority-label">Priorité</span>
                                    <span id="task-priority" class="modern-priority-badge"></span>
                                </div>
                                <div class="task-status-container">
                                    <span class="status-label">Statut</span>
                                    <span id="task-status" class="modern-status-badge">En attente</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section description -->
                    <div class="task-description-section">
                        <div class="section-header">
                            <i class="fas fa-file-alt section-icon"></i>
                            <h6 class="section-title">Description</h6>
                        </div>
                        <div class="description-content">
                            <div id="task-description-loader" class="description-loader">
                                <div class="loader-spinner"></div>
                                <span>Chargement des détails...</span>
                            </div>
                            <p id="task-description" class="modern-description" style="display: none;"></p>
                        </div>
                    </div>
                    
                    <!-- Section informations additionnelles -->
                    <div class="task-info-section">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="info-content">
                                    <span class="info-label">Date de création</span>
                                    <span id="task-created-date" class="info-value">-</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="info-content">
                                    <span class="info-label">Assigné à</span>
                                    <span id="task-assignee" class="info-value">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section pièces jointes -->
                    <div class="task-attachments-section" id="task-attachments-section" style="display: none;">
                        <div class="section-header">
                            <i class="fas fa-paperclip section-icon"></i>
                            <h6 class="section-title">Pièces jointes</h6>
                            <span class="attachments-count badge bg-primary ms-2" id="attachments-count">0</span>
                        </div>
                        <div class="attachments-grid" id="task-attachments-list">
                            <!-- Les pièces jointes seront ajoutées ici dynamiquement -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pied du modal avec actions -->
            <div class="modern-task-modal-footer">
                <div class="footer-actions">
                    <div class="primary-actions">
                        <button id="start-task-btn" class="modern-action-btn start-btn" data-task-id="" onclick="updateTaskStatus(this.getAttribute('data-task-id'), 'en_cours')">
                            <div class="btn-icon">
                                <i class="fas fa-play"></i>
                            </div>
                            <div class="btn-content">
                                <span class="btn-text">Démarrer</span>
                                <span class="btn-subtext">Commencer la tâche</span>
                            </div>
                        </button>
                        <button id="complete-task-btn" class="modern-action-btn complete-btn" data-task-id="" onclick="updateTaskStatus(this.getAttribute('data-task-id'), 'termine')">
                            <div class="btn-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="btn-content">
                                <span class="btn-text">Terminer</span>
                                <span class="btn-subtext">Marquer comme fini</span>
                            </div>
                        </button>
                        <button id="edit-task-btn" class="modern-action-btn edit-btn">
                            <div class="btn-icon">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div class="btn-content">
                                <span class="btn-text">Modifier</span>
                                <span class="btn-subtext">Éditer la tâche</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour changer le statut d'une tâche -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="changeStatusModalLabel">Changer le statut</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="statusTaskId" value="">
                <div class="d-grid gap-3">
                    <button type="button" class="btn btn-lg btn-outline-secondary w-100 d-flex align-items-center justify-content-center" 
                            onclick="updateStatus('a_faire')">
                        <i class="fas fa-clock me-2"></i>À faire
                    </button>
                    <button type="button" class="btn btn-lg btn-outline-primary w-100 d-flex align-items-center justify-content-center" 
                            onclick="updateStatus('en_cours')">
                        <i class="fas fa-spinner me-2"></i>En cours
                    </button>
                    <button type="button" class="btn btn-lg btn-outline-success w-100 d-flex align-items-center justify-content-center" 
                            onclick="updateStatus('termine')">
                        <i class="fas fa-check me-2"></i>Terminé
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour changer la priorité d'une tâche -->
<div class="modal fade" id="changePrioriteModal" tabindex="-1" aria-labelledby="changePrioriteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="changePrioriteModalLabel">Changer la priorité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="prioriteTaskId" value="">
                <div class="d-grid gap-3">
                    <button type="button" class="btn btn-lg btn-outline-success w-100 d-flex align-items-center justify-content-center" 
                            onclick="updatePriorite('basse')">
                        <i class="fas fa-arrow-down me-2"></i>Basse
                    </button>
                    <button type="button" class="btn btn-lg btn-outline-warning w-100 d-flex align-items-center justify-content-center" 
                            onclick="updatePriorite('moyenne')">
                        <i class="fas fa-minus me-2"></i>Moyenne
                    </button>
                    <button type="button" class="btn btn-lg btn-outline-danger w-100 d-flex align-items-center justify-content-center" 
                            onclick="updatePriorite('haute')">
                        <i class="fas fa-arrow-up me-2"></i>Haute
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour changer l'employé assigné à une tâche -->
<div class="modal fade" id="changeEmployeModal" tabindex="-1" aria-labelledby="changeEmployeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="changeEmployeModalLabel">Assigner la tâche</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="employeTaskId" value="">
                
                <!-- Boutons de sélection rapide des employés -->
                <div class="quick-assign-buttons d-grid gap-3 mb-3">
                    <button type="button" class="btn btn-outline-secondary btn-lg employee-option employee-unassign" onclick="updateEmploye('')">
                        <i class="fas fa-user-slash me-2"></i>Non assigné
                    </button>
                    
                    <?php 
                    // Afficher les 3 premiers employés (ou moins s'il y en a moins)
                    $top_employees = array_slice($utilisateurs, 0, min(3, count($utilisateurs)));
                    foreach ($top_employees as $index => $employe): 
                        $btn_classes = ['primary', 'success', 'warning'];
                        $btn_class = isset($btn_classes[$index]) ? $btn_classes[$index] : 'primary';
                    ?>
                        <button type="button" class="btn btn-outline-<?php echo $btn_class; ?> btn-lg employee-option" 
                                data-employee-id="<?php echo $employe['id']; ?>"
                                onclick="updateEmploye('<?php echo $employe['id']; ?>')">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($employe['full_name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour éditer une tâche -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="editTaskModalLabel">
                    <i class="fas fa-edit me-2"></i>Modifier la tâche
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="editTaskForm" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" id="edit_task_id" name="id" value="">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="edit_titre" class="form-label">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_titre" name="titre" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_employe_id" class="form-label">Assigner à</label>
                            <select class="form-select" id="edit_employe_id" name="employe_id">
                                <option value="">Non assigné</option>
                                <?php foreach ($utilisateurs as $utilisateur): ?>
                                    <option value="<?php echo $utilisateur['id']; ?>">
                                        <?php echo htmlspecialchars($utilisateur['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="edit_description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_description" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_priorite" class="form-label">Priorité <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_priorite" name="priorite" required>
                                <option value="">Sélectionner</option>
                                <option value="basse">Basse</option>
                                <option value="moyenne">Moyenne</option>
                                <option value="haute">Haute</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_statut" class="form-label">Statut <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_statut" name="statut" required>
                                <option value="">Sélectionner</option>
                                <option value="a_faire">À faire</option>
                                <option value="en_cours">En cours</option>
                                <option value="termine">Terminé</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_date_limite" class="form-label">Date d'échéance</label>
                            <input type="date" class="form-control" id="edit_date_limite" name="date_limite">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-primary btn-save" onclick="sauvegarderModification()">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour les modals */
.modern-task-modal .modal-content {
    border-radius: 20px;
    border: none;
    overflow: hidden;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
}

.modern-task-modal-header {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    padding: 2rem;
    position: relative;
}

.modal-header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.modal-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.modal-title-section .modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.modal-subtitle {
    opacity: 0.9;
    margin: 0;
    font-size: 0.9rem;
}

.modern-close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 10px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    transition: all 0.3s ease;
}

.modern-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.modern-task-modal-body {
    padding: 2rem;
}

.task-header-section {
    margin-bottom: 2rem;
}

.modern-task-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--day-text);
    margin-bottom: 1rem;
}

.task-meta {
    display: flex;
    gap: 2rem;
    align-items: center;
}

.priority-container,
.task-status-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.priority-label,
.status-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 600;
    color: var(--day-text-muted);
    letter-spacing: 0.5px;
}

.modern-priority-badge,
.modern-status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: capitalize;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.section-icon {
    width: 40px;
    height: 40px;
    background: var(--day-primary);
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--day-text);
    margin: 0;
}

.modern-description {
    background: var(--day-bg-secondary);
    border-radius: 12px;
    padding: 1.5rem;
    line-height: 1.6;
    color: var(--day-text);
}

.description-loader {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 2rem;
    justify-content: center;
}

.loader-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid var(--day-border);
    border-top: 2px solid var(--day-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--day-bg-secondary);
    border-radius: 12px;
}

.info-icon {
    width: 40px;
    height: 40px;
    background: var(--day-primary);
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.info-content {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 600;
    color: var(--day-text-muted);
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
}

.info-value {
    font-weight: 500;
    color: var(--day-text);
}

.modern-task-modal-footer {
    padding: 2rem;
    background: var(--day-bg-secondary);
}

.primary-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.modern-action-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    color: white;
    min-width: 140px;
}

.modern-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    color: white;
}

.start-btn {
    background: linear-gradient(135deg, #10b981, #059669);
}

.complete-btn {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.edit-btn {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.btn-icon {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.btn-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.btn-text {
    font-size: 0.95rem;
    font-weight: 600;
}

.btn-subtext {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* Mode nuit pour les modals */
body.night-mode .modern-task-modal .modal-content {
    background: rgba(30, 30, 35, 0.98);
    color: #ffffff;
}

body.night-mode .modern-description,
body.night-mode .info-item {
    background: rgba(40, 40, 45, 0.8);
    color: #ffffff;
}

body.night-mode .modern-task-modal-footer {
    background: rgba(40, 40, 45, 0.8);
}

/* Mode nuit pour le modal d'édition editTaskModal */
body.night-mode #editTaskModal .modal-content {
    background: rgba(30, 30, 35, 0.98) !important;
    color: #ffffff !important;
    border: 1px solid rgba(0, 255, 255, 0.2) !important;
}

body.night-mode #editTaskModal .modal-header {
    background: rgba(40, 40, 45, 0.9) !important;
    border-bottom: 1px solid rgba(0, 255, 255, 0.2) !important;
    color: #ffffff !important;
}

body.night-mode #editTaskModal .modal-title {
    color: #00ffff !important;
}

body.night-mode #editTaskModal .btn-close {
    filter: invert(1) !important;
}

body.night-mode #editTaskModal .modal-body {
    background: rgba(30, 30, 35, 0.98) !important;
    color: #ffffff !important;
}

body.night-mode #editTaskModal .form-label {
    color: #00ffff !important;
    font-weight: 600 !important;
}

body.night-mode #editTaskModal .form-control,
body.night-mode #editTaskModal .form-select {
    background: rgba(40, 40, 45, 0.8) !important;
    border: 1px solid rgba(0, 255, 255, 0.3) !important;
    color: #ffffff !important;
}

body.night-mode #editTaskModal .form-control:focus,
body.night-mode #editTaskModal .form-select:focus {
    background: rgba(40, 40, 45, 0.9) !important;
    border-color: rgba(0, 255, 255, 0.6) !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 255, 255, 0.25) !important;
    color: #ffffff !important;
}

body.night-mode #editTaskModal .form-control::placeholder {
    color: rgba(255, 255, 255, 0.6) !important;
}

body.night-mode #editTaskModal .modal-footer {
    background: rgba(40, 40, 45, 0.9) !important;
    border-top: 1px solid rgba(0, 255, 255, 0.2) !important;
}

body.night-mode #editTaskModal .btn-secondary {
    background: rgba(60, 60, 65, 0.8) !important;
    border-color: rgba(0, 255, 255, 0.3) !important;
    color: #ffffff !important;
}

body.night-mode #editTaskModal .btn-secondary:hover {
    background: rgba(70, 70, 75, 0.9) !important;
    border-color: rgba(0, 255, 255, 0.5) !important;
    transform: translateY(-2px) !important;
}

body.night-mode #editTaskModal .btn-primary {
    background: linear-gradient(135deg, #00ffff, #0080ff) !important;
    border: none !important;
    color: #000000 !important;
    font-weight: 600 !important;
}

body.night-mode #editTaskModal .btn-primary:hover {
    background: linear-gradient(135deg, #00e6e6, #0073e6) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 25px rgba(0, 255, 255, 0.3) !important;
}

/* Mode jour pour le modal d'édition editTaskModal - Forcer fond blanc */
body:not(.night-mode) #editTaskModal .modal-content {
    background: #ffffff !important;
    color: #1e293b !important;
    border: 1px solid #e2e8f0 !important;
}

body:not(.night-mode) #editTaskModal .modal-header {
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
    color: #1e293b !important;
}

body:not(.night-mode) #editTaskModal .modal-title {
    color: #1e293b !important;
}

body:not(.night-mode) #editTaskModal .modal-body {
    background: #ffffff !important;
    color: #1e293b !important;
}

body:not(.night-mode) #editTaskModal .form-label {
    color: #374151 !important;
    font-weight: 600 !important;
}

body:not(.night-mode) #editTaskModal .form-control,
body:not(.night-mode) #editTaskModal .form-select {
    background: #ffffff !important;
    border: 1px solid #d1d5db !important;
    color: #1e293b !important;
}

body:not(.night-mode) #editTaskModal .form-control:focus,
body:not(.night-mode) #editTaskModal .form-select:focus {
    background: #ffffff !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
    color: #1e293b !important;
}

body:not(.night-mode) #editTaskModal .form-control::placeholder {
    color: #9ca3af !important;
}

body:not(.night-mode) #editTaskModal .modal-footer {
    background: #ffffff !important;
    border-top: 1px solid #e2e8f0 !important;
}

body:not(.night-mode) #editTaskModal .btn-secondary {
    background: #f8fafc !important;
    border-color: #d1d5db !important;
    color: #374151 !important;
}

body:not(.night-mode) #editTaskModal .btn-secondary:hover {
    background: #f1f5f9 !important;
    border-color: #9ca3af !important;
    transform: translateY(-2px) !important;
}

body:not(.night-mode) #editTaskModal .btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
    border: none !important;
    color: #ffffff !important;
    font-weight: 600 !important;
}

body:not(.night-mode) #editTaskModal .btn-primary:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3) !important;
}

/* Styles pour les boutons utilisateurs actifs dans le modal */
.modal-user-btn.active {
    background-color: #0d6efd !important;
    color: white !important;
    border-color: #0d6efd !important;
    font-weight: 500 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3) !important;
}

.modal-user-btn:hover {
    transform: translateY(-2px) !important;
    transition: all 0.2s ease !important;
}

/* Styles pour les boutons de priorité et statut actifs */
.btn-modal-priority.active,
.btn-modal-status.active {
    font-weight: 500 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
}

/* Styles pour les pièces jointes dans le modal de détails */
.task-attachments-section {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--day-border);
}

.attachments-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1rem;
}

.attachment-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--day-bg-secondary);
    border: 1px solid var(--day-border);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.attachment-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--day-shadow);
    border-color: var(--day-primary);
}

.attachment-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(var(--day-primary-rgb, 59, 130, 246), 0.1);
    border-radius: 12px;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.attachment-info {
    flex: 1;
    min-width: 0;
}

.attachment-name {
    font-weight: 600;
    color: var(--day-text);
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.attachment-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--day-text-muted);
    flex-wrap: wrap;
}

.attachment-size {
    font-weight: 500;
}

.attachment-date {
    opacity: 0.8;
}

.attachment-uploader {
    opacity: 0.7;
    font-style: italic;
}

.attachment-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}

.attachment-actions .btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.attachment-actions .btn:hover {
    transform: translateY(-2px);
}

.attachments-count {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Mode nuit pour les pièces jointes */
body.night-mode .attachment-item {
    background: rgba(40, 40, 45, 0.8);
    border-color: rgba(0, 255, 255, 0.2);
}

body.night-mode .attachment-item:hover {
    border-color: rgba(0, 255, 255, 0.4);
    box-shadow: 0 8px 25px rgba(0, 255, 255, 0.15);
}

body.night-mode .attachment-icon {
    background: rgba(0, 255, 255, 0.1);
}

/* Responsive pour les pièces jointes */
@media (max-width: 768px) {
    .attachment-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .attachment-meta {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .attachment-actions {
        align-self: stretch;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .primary-actions {
        flex-direction: column;
    }
    
    .modern-action-btn {
        min-width: 100%;
    }
    
    .task-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
/* Animated Background for Night Mode */
#animated-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: -1; /* Derrière tout le contenu */
    pointer-events: none; /* Ne bloque pas les clics */
    opacity: 0;
    transition: opacity 0.5s ease;
    background-color: #0f172a; /* Couleur de fond de base */
}

body.night-mode #animated-bg {
    opacity: 1;
}

#animated-bg::before,
#animated-bg::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

#animated-bg::before {
    background: radial-gradient(circle at 20% 30%, rgba(76, 29, 149, 0.4), transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(59, 130, 246, 0.3), transparent 50%);
    animation: moveBackground1 25s ease-in-out infinite alternate;
}

#animated-bg::after {
    background: radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.3), transparent 45%),
                radial-gradient(circle at 10% 80%, rgba(236, 72, 153, 0.25), transparent 45%);
    animation: moveBackground2 30s ease-in-out infinite alternate-reverse;
}

@keyframes moveBackground1 {
    0% { transform: scale(1) translate(0, 0); }
    50% { transform: scale(1.1) translate(30px, -20px); }
    100% { transform: scale(1) translate(-20px, 20px); }
}

@keyframes moveBackground2 {
    0% { transform: scale(1) translate(0, 0); }
    50% { transform: scale(1.15) translate(-30px, 25px); }
    100% { transform: scale(1) translate(20px, -20px); }
}

</style>

<!-- Modal pour ajouter une nouvelle tâche -->
<div id="animated-bg"></div>
<div class="modal fade" id="ajouterTacheModal" tabindex="-1" aria-labelledby="ajouterTacheModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0" style="border-radius: 20px; overflow: hidden;">
            <!-- En-tête moderne avec dégradé -->
            <div class="modal-header" style="background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)); color: white; padding: 2rem; border: none;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 60px; height: 60px; background: rgba(255, 255, 255, 0.2); border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-1" id="ajouterTacheModalLabel" style="font-size: 1.5rem; font-weight: 700;">Nouvelle Tâche</h5>
                        <p class="mb-0" style="opacity: 0.9; font-size: 0.9rem;">Créer une nouvelle tâche</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer" style="background: rgba(255, 255, 255, 0.2); border-radius: 10px; width: 40px; height: 40px;"></button>
            </div>
            
            <!-- Corps du modal -->
            <div class="modal-body" style="padding: 2rem; background: var(--day-bg);">
                <form id="ajouterTacheForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="priorite" id="modal_priorite" value="">
                    <input type="hidden" name="statut" id="modal_statut" value="">
                    <input type="hidden" name="employe_id" id="modal_employe_id" value="">
                    
                    <!-- Titre de la tâche -->
                    <div class="mb-4">
                        <label for="modal_titre" class="form-label fw-bold">Titre de la tâche *</label>
                        <input type="text" class="form-control form-control-lg" id="modal_titre" name="titre" required
                            placeholder="Saisissez un titre clair et concis" style="border-radius: 10px;">
                    </div>
                    
                    <!-- Description de la tâche -->
                    <div class="mb-4">
                        <label for="modal_description" class="form-label fw-bold">Description *</label>
                        <textarea class="form-control" id="modal_description" name="description" rows="4" required
                            placeholder="Détaillez la tâche à accomplir..." style="border-radius: 10px;"></textarea>
                    </div>
                    
                    <!-- Priorité avec boutons -->
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold d-block">Priorité *</label>
                                <div class="modal-priority-buttons d-flex flex-nowrap">
                                    <button type="button" class="btn btn-modal-priority btn-outline-success flex-grow-1" data-value="basse" style="border-radius: 0; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                                        <i class="fas fa-angle-down me-1"></i><span class="d-none d-md-inline">Basse</span>
                                    </button>
                                    <button type="button" class="btn btn-modal-priority btn-outline-primary flex-grow-1" data-value="moyenne" style="border-radius: 0;">
                                        <i class="fas fa-equals me-1"></i><span class="d-none d-md-inline">Moyenne</span>
                                    </button>
                                    <button type="button" class="btn btn-modal-priority btn-outline-warning flex-grow-1" data-value="haute" style="border-radius: 0;">
                                        <i class="fas fa-angle-up me-1"></i><span class="d-none d-md-inline">Haute</span>
                                    </button>
                                    <button type="button" class="btn btn-modal-priority btn-outline-danger flex-grow-1" data-value="urgente" style="border-radius: 0; border-top-right-radius: 8px; border-bottom-right-radius: 8px;">
                                        <i class="fas fa-exclamation-triangle me-1"></i><span class="d-none d-md-inline">Urgente</span>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-12 col-md-6 mt-3 mt-md-0">
                                <!-- Statut avec boutons -->
                                <label class="form-label fw-bold d-block">Statut *</label>
                                <div class="modal-status-buttons d-flex flex-nowrap">
                                    <button type="button" class="btn btn-modal-status btn-outline-secondary flex-grow-1" data-value="a_faire" style="border-radius: 0; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                                        <i class="far fa-circle me-1"></i><span class="d-none d-md-inline">À faire</span>
                                    </button>
                                    <button type="button" class="btn btn-modal-status btn-outline-info flex-grow-1" data-value="en_cours" style="border-radius: 0;">
                                        <i class="fas fa-spinner me-1"></i><span class="d-none d-md-inline">En cours</span>
                                    </button>
                                    <button type="button" class="btn btn-modal-status btn-outline-success flex-grow-1" data-value="termine" style="border-radius: 0; border-top-right-radius: 8px; border-bottom-right-radius: 8px;">
                                        <i class="fas fa-check me-1"></i><span class="d-none d-md-inline">Terminé</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Date limite -->
                    <div class="mb-4">
                        <label for="modal_date_limite" class="form-label fw-bold">Date limite</label>
                        <div class="input-group">
                            <span class="input-group-text" style="border-radius: 10px 0 0 10px;"><i class="fas fa-calendar-alt"></i></span>
                            <input type="date" class="form-control form-control-lg" id="modal_date_limite" name="date_limite" style="border-radius: 0 10px 10px 0;">
                        </div>
                    </div>
                    
                    <!-- Assigner la tâche -->
                    <div class="mb-4">
                        <label class="form-label fw-bold d-block">Assigner à</label>
                        <div class="modal-user-selection">
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <button type="button" class="btn btn-outline-secondary btn-lg modal-user-btn" data-value="">
                                    <i class="fas fa-user-slash me-2"></i>Non assigné
                                </button>
                                
                                <?php foreach ($utilisateurs as $index => $utilisateur): ?>
                                    <?php if ($index < 3): ?>
                                        <button type="button" class="btn btn-outline-primary btn-lg modal-user-btn" 
                                                data-value="<?php echo $utilisateur['id']; ?>">
                                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($utilisateur['full_name']); ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <?php if (count($utilisateurs) > 3): ?>
                                    <button type="button" class="btn btn-outline-secondary btn-lg" id="modalShowAllUsersBtn">
                                        <i class="fas fa-users me-2"></i>Voir tous
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div id="modalAllUsersList" class="mt-3" style="display: none;">
                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                                    <?php foreach ($utilisateurs as $utilisateur): ?>
                                        <div class="col">
                                            <button type="button" class="btn btn-outline-primary w-100 text-start modal-user-btn py-2" 
                                                    data-value="<?php echo $utilisateur['id']; ?>">
                                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($utilisateur['full_name']); ?>
                                                <small class="d-block text-muted ms-4"><?php echo ucfirst($utilisateur['role']); ?></small>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </form>
            </div>
            
            <!-- Pied du modal -->
            <div class="modal-footer" style="padding: 2rem; background: var(--day-bg-secondary); border: none;">
                <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-primary btn-lg px-5" id="modalSaveTaskBtn">
                    <i class="fas fa-save me-2"></i>Enregistrer la tâche
                </button>
            </div>
        </div>
    </div>
</div>
