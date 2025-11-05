<?php
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

// Traitement de la suppression
if (isset($_GET['action']) && $_GET['action'] == 'supprimer' && isset($_GET['id']) && $shop_pdo) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $shop_pdo->prepare("DELETE FROM taches WHERE id = ?");
        $stmt->execute([$id]);
        // Utiliser une redirection simple sans set_message si elle n'existe pas
        header("Location: index.php?page=taches_moderne");
        exit;
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression de la tâche: " . $e->getMessage());
    }
}

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

/* Styles généraux */
body {
    background: var(--day-bg) !important;
    color: var(--day-text) !important;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    transition: all 0.3s ease;
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
    background: var(--day-bg);
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
    margin-bottom: 3rem;
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

/* FIX NAVBAR - Obligatoire pour affichage correct */
/* Masquer dock mobile sur desktop */
@media (min-width: 992px) {
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    /* Forcer navbar desktop visible */
    #desktop-navbar, nav#desktop-navbar, .navbar, nav.navbar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 10000 !important;
        height: 60px !important;
        width: 100% !important;
    }
    /* Surcharger navbar-servo-fix.css */
    body #desktop-navbar, html body #desktop-navbar {
        height: 60px !important;
        min-height: 60px !important;
        max-height: 60px !important;
    }
    /* Éléments navbar visibles */
    #desktop-navbar * {
        visibility: visible !important;
        opacity: 1 !important;
    }
    /* Container navbar avec centrage vertical parfait */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.75rem 1rem !important; /* Augmenté à 0.75rem pour plus de centrage */
        min-height: 60px !important;
    }
    /* Logo avec centrage vertical parfait */
    #desktop-navbar .navbar-brand {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        line-height: 1 !important;
    }
    #desktop-navbar .navbar-brand img {
        height: 32px !important; /* Encore réduit pour plus d'espace vertical */
        width: auto !important;
        vertical-align: middle !important;
    }
    /* Boutons avec centrage vertical parfait */
    #desktop-navbar .btn,
    #desktop-navbar .navbar-nav .nav-link,
    #desktop-navbar .dropdown-toggle {
        display: flex !important;
        align-items: center !important;
        height: auto !important;
        padding: 0.375rem 0.75rem !important; /* Padding encore plus réduit */
        margin: 0.125rem 0.25rem !important; /* Marges ajustées */
        line-height: 1.2 !important;
        vertical-align: middle !important;
    }
    /* Correction spécifique pour les icônes dans les boutons */
    #desktop-navbar .btn i,
    #desktop-navbar .navbar-nav .nav-link i,
    #desktop-navbar .dropdown-toggle i {
        vertical-align: middle !important;
        line-height: 1 !important;
    }
    /* Messages de bienvenue centrés */
    #desktop-navbar .d-none.d-md-flex {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
        line-height: 1.2 !important;
    }
    /* Correction pour tous les textes dans la navbar */
    #desktop-navbar .navbar-text,
    #desktop-navbar .text-muted,
    #desktop-navbar span,
    #desktop-navbar small {
        line-height: 1.2 !important;
        vertical-align: middle !important;
    }
    /* Forcer l'alignement vertical pour tous les éléments flex */
    #desktop-navbar .d-flex {
        align-items: center !important;
    }
    /* Animation SERVO centrée parfaitement */
    body .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 10001 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        height: auto !important;
        width: auto !important;
    }
    
    /* Correction spécifique pour l'animation SERVO dans la navbar */
    #desktop-navbar .servo-logo-container {
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 10001 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        height: auto !important;
        width: auto !important;
        line-height: 1 !important;
    }
    
    /* Animation SERVO - ajustement de la taille pour navbar */
    #desktop-navbar .servo-logo-container .servo-text,
    #desktop-navbar .servo-logo-container .animated-text {
        font-size: 1.5rem !important;
        line-height: 1 !important;
        vertical-align: middle !important;
    }
    /* Réserver espace navbar */
    body {
        padding-top: 80px !important;
    }
}

/* Styles généraux navbar (mobile + desktop) */
#desktop-navbar, nav#desktop-navbar {
    display: block !important;
    visibility: visible !important;
    position: fixed !important;
    top: 0 !important;
    z-index: 10000 !important;
}

/* Masquer navbar sur mobile */
@media (max-width: 767px) {
    #desktop-navbar, nav#desktop-navbar {
        display: none !important;
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
        <h1 class="page-title">Gestion des Tâches</h1>
        <p class="page-subtitle">Organisez et suivez vos tâches efficacement</p>
    </div>

    <!-- 📊 MÉTRIQUES DE STATUT (Filtres modernes) -->
    <div class="status-overview-section fade-in">
        <h3 class="status-section-title">Vue d'ensemble des Tâches</h3>
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

            <a href="index.php?page=taches_moderne&status=en_cours" class="status-metric-card progress-tasks-card <?php echo $status == 'en_cours' ? 'active' : ''; ?>">
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

            <a href="index.php?page=taches_moderne&status=termine" class="status-metric-card completed-tasks-card <?php echo $status == 'termine' ? 'active' : ''; ?>">
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

            <a href="index.php?page=taches_moderne&priorite=haute" class="status-metric-card high-priority-card <?php echo $priorite == 'haute' ? 'active' : ''; ?>">
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
        <div class="tasks-header">
            <h3 class="tasks-title">
                <?php 
                if ($status) {
                    echo $status == 'a_faire' ? 'Tâches à faire' : 
                        ($status == 'en_cours' ? 'Tâches en cours' : 'Tâches terminées');
                } elseif ($priorite) {
                    echo 'Tâches priorité ' . $priorite;
                } else {
                    echo 'Tâches à faire'; // Par défaut "Tâches à faire"
                }
                ?>
            </h3>
            <p class="tasks-subtitle"><?php echo count($taches); ?> tâche(s) trouvée(s)</p>
        </div>
        
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
                <a href="index.php?page=ajouter_tache" class="btn-add-task">
                    <i class="fas fa-plus"></i>
                    Nouvelle Tâche
                </a>
            </div>
        <?php else: ?>
            
            <!-- Vue en cartes -->
            <div id="cards-view" class="tasks-grid" style="display: none;">
                <?php foreach ($taches as $tache): ?>
                <div class="modern-task-card" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
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
                        <tr onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
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
        window.location.href = `index.php?page=taches_moderne&action=supprimer&id=${taskId}`;
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
function setupThemeListener() {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    
    // Écouter les changements
    mediaQuery.addListener((e) => {
        console.log('🔄 Changement détecté des préférences système:', e.matches ? 'Mode sombre' : 'Mode clair');
        updateTheme();
    });
}

// Appliquer le thème au chargement
document.addEventListener('DOMContentLoaded', function() {
    updateTheme();
    setupThemeListener(); // Écouter les changements système
});

// Animation des particules pour le mode nuit
if (document.body.classList.contains('night-mode')) {
    createParticles();
}

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
            // Fermer le modal de détails
            const modal = bootstrap.Modal.getInstance(document.getElementById('taskDetailsModal'));
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
}

body.night-mode .modern-task-modal-footer {
    background: rgba(40, 40, 45, 0.8);
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
</style>
