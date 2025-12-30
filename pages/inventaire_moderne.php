<?php
include_once 'includes/night-mode-system.php';
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Récupérer les données
try {
    $shop_pdo = getShopDBConnection();
    $colCheck = $shop_pdo->query("SHOW COLUMNS FROM produits LIKE 'suivre_stock'");
    $gb_has_suivre_stock = $colCheck && $colCheck->rowCount() > 0;
    $sql = "SELECT p.*" . ($gb_has_suivre_stock ? ", p.suivre_stock" : "") . ", f.nom as fournisseur_nom FROM produits p LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id ORDER BY p.nom ASC";
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute();
    $gb_products = $stmt->fetchAll();
} catch (Throwable $e) {
    set_message("Erreur chargement inventaire: " . $e->getMessage(), 'danger');
    $gb_has_suivre_stock = false;
    $gb_products = [];
}

// Calculer les statistiques
$gb_total = count($gb_products);
$gb_alert = array_filter($gb_products, function($p){ return (int)$p['quantite'] > 0 && (int)$p['quantite'] <= (int)$p['seuil_alerte'];});
$gb_out = array_filter($gb_products, function($p){ return (int)$p['quantite'] === 0; });
$gb_stock = $gb_total - count($gb_out);
$gb_tracked = $gb_has_suivre_stock ? array_filter($gb_products, function($p){ return isset($p['suivre_stock']) && (int)$p['suivre_stock'] === 1; }) : [];
?>

<style>
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
    }
    /* Réserver espace navbar */
    body {
        padding-top: 80px !important;
        margin: 0 !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        overflow-x: hidden !important;
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

/* ========================================
   VARIABLES CSS POUR LES THÈMES
======================================== */
:root {
    /* Mode Jour - Moderne Dynamique */
    --day-primary: #3b82f6;
    --day-secondary: #8b5cf6;
    --day-accent: #06b6d4;
    --day-bg: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    --day-bg-animated: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff);
    --day-card-bg: rgba(255, 255, 255, 0.95);
    --day-text: #1e293b;
    --day-text-light: #64748b;
    --day-shadow: rgba(59, 130, 246, 0.15);
    --day-border: rgba(148, 163, 184, 0.2);

    /* Mode Nuit - Futuriste */
    --night-primary: #00d4ff;
    --night-secondary: #7c3aed;
    --night-accent: #ff00aa;
    --night-bg: #0a0a0a;
    --night-bg-animated: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483);
    --night-card-bg: rgba(15, 15, 25, 0.95);
    --night-text: #ffffff;
    --night-text-light: #a0aec0;
    --night-shadow: rgba(0, 212, 255, 0.25);
    --night-border: rgba(0, 212, 255, 0.3);
    --night-glow: 0 0 20px rgba(0, 212, 255, 0.5);
}

/* ========================================
   STRUCTURE DE BASE
======================================== */
body {
    margin: 0;
    padding: 0;
    padding-top: 80px; /* Espace pour la navbar fixe */
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    overflow-x: hidden;
}

.modern-dashboard {
    position: relative;
    min-height: 100vh;
    padding: 1rem;
    transition: all 0.3s ease;
    margin-top: -80px; /* Remonter sous la navbar */
    padding-top: calc(80px + 1rem); /* Compenser avec padding */
}

/* ========================================
   ANIMATIONS DE FOND
======================================== */
.bg-animated {
    background: var(--day-bg-animated);
    background-size: 300% 300%;
    animation: gradientFlow 20s ease infinite;
}

.bg-animated.night-mode {
    background: var(--night-bg-animated);
    background-size: 400% 400%;
}

@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* ========================================
   ANIMATIONS MODERNES
======================================== */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.fade-in {
    animation: fadeIn 0.6s ease-out;
}

/* ========================================
   EN-TÊTE MODERNE
======================================== */
.modern-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--day-card-bg);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    animation: slideInUp 0.6s ease-out;
}

.modern-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--day-text);
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0;
}

.modern-title i {
    color: var(--day-primary);
    font-size: 2rem;
}

/* ========================================
   BOUTONS D'ACTION MODERNES
======================================== */
.modern-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%);
    color: white;
    text-decoration: none;
    border-radius: 15px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
    position: relative;
    overflow: hidden;
}

.modern-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.modern-btn:hover::before {
    left: 100%;
}

.modern-btn:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
}

.modern-btn--success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.modern-btn--success:hover {
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
}

.modern-btn--info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
}

.modern-btn--info:hover {
    box-shadow: 0 10px 30px rgba(6, 182, 212, 0.4);
}

.modern-btn--warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.modern-btn--warning:hover {
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
}

/* ========================================
   STATISTIQUES MODERNES
======================================== */
.modern-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.modern-stat-card {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid var(--day-border);
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px var(--day-shadow);
    transition: all 0.3s ease;
    animation: slideInUp 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

.modern-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary), var(--day-accent));
    background-size: 200% 100%;
    animation: gradientFlow 3s ease infinite;
}

.modern-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px var(--day-shadow);
}

.stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1e293b !important; /* Noir en mode jour - Priorité forte */
    margin: 0;
    line-height: 1;
}

.stat-label {
    color: var(--day-text-light);
    font-size: 0.95rem;
    font-weight: 500;
    margin: 0.5rem 0 0;
}

/* ========================================
   CONTRÔLES MODERNES
======================================== */
.modern-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    padding: 1.5rem;
    background: var(--day-card-bg);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
}

.modern-search {
    position: relative;
    flex: 1;
    min-width: 300px;
}

.modern-search input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid var(--day-border);
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.8);
    color: var(--day-text);
    font-size: 1rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.modern-search input:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    background: rgba(255, 255, 255, 1);
}

.modern-search i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--day-text-light);
    font-size: 1.1rem;
}

.modern-select {
    padding: 1rem;
    border: 2px solid var(--day-border);
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.8);
    color: var(--day-text);
    font-size: 1rem;
    min-width: 150px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.modern-select:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* ========================================
   TABLEAU MODERNE
======================================== */
.modern-table-container {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    overflow: hidden;
    animation: slideInUp 0.6s ease-out;
}

.modern-table-wrapper {
    overflow-x: auto;
    border-radius: 15px;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.modern-table th {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    color: var(--day-text);
    font-weight: 700;
    padding: 1.25rem;
    text-align: left;
    border-bottom: 2px solid var(--day-border);
    position: sticky;
    top: 0;
    z-index: 10;
}

.modern-table td {
    padding: 1.25rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    color: var(--day-text);
    vertical-align: middle;
}

.modern-table tr {
    transition: all 0.2s ease;
}

.modern-table tr:hover {
    background: rgba(59, 130, 246, 0.05);
    transform: scale(1.002);
}

/* ========================================
   BADGES MODERNES
======================================== */
.modern-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.875rem;
    font-weight: 600;
    letter-spacing: 0.025em;
}

.modern-badge--success {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.modern-badge--warning {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    color: #92400e;
    border: 1px solid #fcd34d;
}

.modern-badge--danger {
    background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
    color: #991b1b;
    border: 1px solid #fca5a5;
}

/* ========================================
   BOUTONS D'ACTION TABLE
======================================== */
.modern-actions-cell {
    display: flex;
    gap: 0.5rem;
}

.modern-action-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid var(--day-border);
    background: white;
    color: var(--day-text-light);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.modern-action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    color: var(--day-primary);
    border-color: var(--day-primary);
}

/* ========================================
   MODALS MODERNES
======================================== */
.modern-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    animation: fadeIn 0.3s ease;
}

.modern-modal.show {
    display: flex;
}

.modern-modal-dialog {
    background: white;
    border-radius: 20px;
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    animation: slideInUp 0.3s ease;
    position: relative;
}

.modern-modal-header {
    padding: 2rem 2rem 0;
    border-bottom: 1px solid var(--day-border);
    margin-bottom: 1.5rem;
}

.modern-modal-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--day-text);
    margin: 0 0 1.5rem;
}

.modern-modal-body {
    padding: 0 2rem 2rem;
}

.modern-form-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.modern-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.modern-form-label {
    font-weight: 600;
    color: var(--day-text);
    font-size: 0.95rem;
}

.modern-form-input {
    padding: 0.875rem;
    border: 2px solid var(--day-border);
    border-radius: 10px;
    background: white;
    color: var(--day-text);
    font-size: 1rem;
    transition: all 0.2s ease;
}

.modern-form-input:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.modern-form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--day-border);
}

/* ========================================
   RESPONSIVE
======================================== */
@media (max-width: 768px) {
    .modern-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .modern-actions {
        width: 100%;
        justify-content: center;
    }
    
    .modern-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .modern-search {
        min-width: unset;
    }
    
    .modern-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .modern-title {
        font-size: 2rem;
    }
}

/* ========================================
   MODE NUIT
======================================== */
body.night-mode {
    --day-primary: var(--night-primary);
    --day-secondary: var(--night-secondary);
    --day-accent: var(--night-accent);
    --day-card-bg: var(--night-card-bg);
    --day-text: var(--night-text);
    --day-text-light: var(--night-text-light);
    --day-shadow: var(--night-shadow);
    --day-border: var(--night-border);
    
    /* Rendre le body transparent pour voir #animated-bg */
    background: transparent !important;
}

body.night-mode .bg-animated {
    background: var(--night-bg-animated);
}

body.night-mode .modern-header,
body.night-mode .modern-stat-card,
body.night-mode .modern-controls,
body.night-mode .modern-table-container {
    background: var(--night-card-bg);
    color: var(--night-text);
    border: 1px solid var(--night-border);
    box-shadow: 0 8px 32px var(--night-shadow);
}

body.night-mode .modern-title {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

body.night-mode .modern-table {
    background: #0f172a;
}

body.night-mode .modern-table th {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: var(--night-text);
}

body.night-mode .modern-table tr:hover {
    background-color: rgba(0, 212, 255, 0.1);
}

body.night-mode .modern-search input,
body.night-mode .modern-select,
body.night-mode .modern-form-input {
    background: rgba(15, 23, 42, 0.8);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode .modern-search input:focus,
body.night-mode .modern-select:focus,
body.night-mode .modern-form-input:focus {
    background: rgba(15, 23, 42, 0.9);
    border-color: var(--night-primary);
    box-shadow: var(--night-glow);
}

body.night-mode .modern-modal-dialog {
    background: #0f172a;
    border-color: var(--night-border);
}

body.night-mode .modern-btn {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    color: var(--night-text);
}

/* Règle spécifique pour mode jour */
body:not(.night-mode) .stat-value {
    color: #1e293b !important; /* Noir en mode jour */
}

body.night-mode .stat-value {
    color: var(--night-text) !important; /* Blanc en mode nuit - Priorité forte */
}

/* ========================================
   MODE NUIT - BADGES STATUT
======================================== */
body.night-mode .modern-badge--success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.2) 100%);
    color: #6ee7b7;
    border: 1px solid rgba(110, 231, 183, 0.3);
}

body.night-mode .modern-badge--warning {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(217, 119, 6, 0.2) 100%);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

body.night-mode .modern-badge--danger {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(220, 38, 38, 0.2) 100%);
    color: #fca5a5;
    border: 1px solid rgba(252, 165, 165, 0.3);
}

/* ========================================
   MODE NUIT - BOUTONS ACTIONS TABLE
======================================== */
body.night-mode .modern-action-btn {
    background: rgba(30, 41, 59, 0.8);
    border-color: var(--night-border);
    color: var(--night-text-light);
}

body.night-mode .modern-action-btn:hover {
    background: rgba(59, 130, 246, 0.2);
    border-color: var(--night-primary);
    color: var(--night-primary);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* ========================================
   MODE NUIT - MODALS
======================================== */
body.night-mode .modern-modal-dialog {
    background: #0f172a;
    border: 1px solid var(--night-border);
}

body.night-mode .modern-modal-header {
    border-bottom-color: var(--night-border);
}

body.night-mode .modern-modal-title {
    color: var(--night-text);
}

body.night-mode .modern-modal-body {
    background: #0f172a;
    color: var(--night-text);
}

body.night-mode .modern-form-label {
    color: var(--night-text);
}

body.night-mode .modern-form-actions {
    border-top-color: var(--night-border);
}

body.night-mode .adjust-display {
    background: rgba(30, 41, 59, 0.8);
}

body.night-mode .adjust-value {
    color: var(--night-text);
}

body.night-mode .adjust-unit {
    color: var(--night-text-light);
}

body.night-mode .adjust-controls {
    background: rgba(30, 41, 59, 0.5);
    border-color: var(--night-border);
}

body.night-mode .adjust-product-ref {
    background: rgba(30, 41, 59, 0.8);
    color: var(--night-text-light);
}

/* ========================================
   MODE NUIT - MODAL ALERTES STOCK
======================================== */
body.night-mode #gb_stock_stats {
    background: #1e293b !important;
    border-bottom-color: #334155 !important;
}

body.night-mode #gb_stock_stats > div {
    background: #0f172a !important;
    border-color: #334155 !important;
}

body.night-mode #gb_stock_stats > div > div:first-child {
    color: inherit !important;
}

body.night-mode #gb_stock_stats > div > div:last-child {
    color: #94a3b8 !important;
}

body.night-mode .gb-stock-filter-btn {
    background: #0f172a !important;
    border-color: #475569 !important;
    color: #cbd5e1 !important;
}

body.night-mode .gb-stock-filter-btn[style*="linear-gradient"] {
    /* Le gradient actif prime */
}

body.night-mode #gbStockSearch {
    background: #0f172a !important;
    color: #f1f5f9 !important;
    border-color:475569 !important;
}

body.night-mode .gb-stock-card {
    background: #0f172a !important;
    border-color: #334155 !important;
}

body.night-mode .gb-stock-card code {
    background: #1e293b !important;
    color: #cbd5e1 !important;
}

body.night-mode .gb-stock-card > div:last-child {
    border-top-color: #334155 !important;
}


/* Night mode pour modal alertes (tableau) */
body.night-mode .gb-alert-modal-content {
    background: #0f172a !important;
}

body.night-mode .gb-alert-modal-content > div:nth-child(2) {
    /* Stats */
    background: #1e293b !important;
    border-bottom-color: #334155 !important;
}

body.night-mode .gb-alert-modal-content > div:nth-child(2) > div:nth-child(2) {
    /* Séparateur */
    background: #334155 !important;
}

body.night-mode .gb-alert-modal-content table thead tr {
    background: linear-gradient(135deg, #1e293b, #334155) !important;
    color: #f1f5f9 !important;
}

body.night-mode .gb-alert-row {
    background: #1e293b !important;
    border-bottom-color: #334155 !important;
}

body.night-mode .gb-alert-row:hover {
    background: #0f172a !important;
}

body.night-mode .gb-alert-row code {
    background: #0f172a !important;
    color: #cbd5e1 !important;
}

body.night-mode .gb-alert-modal-content > div:last-child {
    /* Footer */
    background: #1e293b !important;
    border-top-color: #334155 !important;
}

body.night-mode .gb-alert-modal-content > div:last-child > div:first-child {
    color: #94a3b8 !important;
}


body.night-mode .gb-alert-table-row {
    border-bottom-color: var(--night-border) !important;
}

body.night-mode .gb-alert-code {
    background: rgba(30, 41, 59, 0.8);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: var(--night-text-light);
}

/* ========================================
   AJUSTEMENT STOCK MODAL
======================================== */
.adjust-modal {
    max-width: 400px;
}

.adjust-product-info {
    text-align: center;
    margin-bottom: 2rem;
}

.adjust-product-ref {
    background: var(--day-bg);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    color: var(--day-text-light);
    display: inline-block;
    margin-bottom: 0.5rem;
}

.adjust-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin: 2rem 0;
    background: var(--day-bg);
    border-radius: 15px;
    padding: 0.5rem;
    border: 2px solid var(--day-border);
}

.adjust-btn {
    width: 50px;
    height: 50px;
    border: none;
    background: var(--day-primary);
    color: white;
    border-radius: 10px;
    font-size: 1.25rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.adjust-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.adjust-btn--minus {
    background: #ef4444;
    border-top-right-radius: 4px;
    border-bottom-right-radius: 4px;
}

.adjust-btn--plus {
    background: #10b981;
    border-top-left-radius: 4px;
    border-bottom-left-radius: 4px;
}

.adjust-display {
    flex: 1;
    text-align: center;
    padding: 0 1.5rem;
    background: white;
    border-radius: 8px;
    margin: 0 0.25rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 50px;
}

.adjust-value {
    font-size: 2rem;
    font-weight: 800;
    color: var(--day-text);
    line-height: 1;
}

.adjust-unit {
    font-size: 0.75rem;
    color: var(--day-text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 0.25rem;
}

/* ========================================
   TOAST NOTIFICATIONS
======================================== */
.modern-toast {
    position: fixed;
    top: 2rem;
    right: 2rem;
    background: white;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    border-left: 4px solid var(--day-primary);
    z-index: 100000;
    animation: slideInUp 0.3s ease;
    min-width: 300px;
}

.modern-toast--success {
    border-left-color: #10b981;
}

.modern-toast--error {
    border-left-color: #ef4444;
}

.modern-toast--warning {
    border-left-color: #f59e0b;
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

/* ====================================================================
   ANIMATED BACKGROUND FOR NIGHT MODE (copié de taches_moderne.php)
==================================================================== */
#animated-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: -1;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.5s ease;
    background-color: #0f172a;
}

body.night-mode #animated-bg,
body.dark-mode #animated-bg {
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

<script>
// Données d'alerte côté serveur
const GB_ALERTS = <?php echo json_encode(array_values(array_map(function($p){

    return [
        'id'=>(int)$p['id'],
        'nom'=>$p['nom'],
        'reference'=>$p['reference'],
        'fournisseur_nom'=>isset($p['fournisseur_nom']) ? $p['fournisseur_nom'] : null,
        'quantite'=>(int)$p['quantite'],
        'seuil_alerte'=>(int)$p['seuil_alerte']
    ];
}, $gb_alert))); ?>;
</script>

<!-- Animated Background for Night Mode -->
<div id="animated-bg"></div>

<!-- Particules d'arrière-plan -->
<div class="particles-container" id="particles"></div>

<div class="modern-dashboard bg-animated" id="dashboard">
    
    <!-- En-tête moderne -->
    <div class="modern-header fade-in">
        <h1 class="modern-title">
            <i class="fas fa-boxes"></i>
            Inventaire
        </h1>
        <div class="modern-actions">
            <button class="modern-btn modern-btn--success" onclick="gbOpenScanner()">
                <i class="fas fa-barcode"></i>
                Scanner
            </button>
            <button class="modern-btn modern-btn--info" onclick="gbOpenStockCheck()">
                <i class="fas fa-eye"></i>
                Vérifier Stock
            </button>
            <button class="modern-btn" onclick="gbAutoReorder()" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                <i class="fas fa-sync-alt"></i>
                Réapprovisionner
            </button>
            <button class="modern-btn" onclick="gbOpen('gbAddModal')">
                <i class="fas fa-plus"></i>
                Nouveau produit
            </button>
            <button class="modern-btn modern-btn--warning" onclick="gbOpenAlerts()">
                <i class="fas fa-triangle-exclamation"></i>
                Alertes stock
            </button>
            <button class="modern-btn modern-btn--secondary" onclick="gbOpenStockHistory()" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <i class="fas fa-history"></i>
                Historique
            </button>
        </div>
    </div>

    <!-- Statistiques modernes -->
    <div class="modern-stats-grid fade-in">
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $gb_total; ?></div>

            <div class="stat-label">Total Produits</div>
        </div>
        
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-circle-check"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $gb_stock; ?></div>

            <div class="stat-label">En Stock</div>
        </div>
        
        <div class="modern-stat-card" style="cursor: pointer;" onclick="gbOpenAlerts()">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo count($gb_alert); ?></div>

            <div class="stat-label">Alertes</div>
        </div>
        
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <i class="fas fa-xmark"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo count($gb_out); ?></div>

            <div class="stat-label">Épuisés</div>
        </div>
        
        <?php if ($gb_has_suivre_stock): ?>

        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                    <i class="fas fa-eye"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo count($gb_tracked); ?></div>

            <div class="stat-label">Suivis</div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Contrôles modernes -->
    <div class="modern-controls fade-in">
        <div class="modern-search">
            <i class="fas fa-search"></i>
            <input id="gbSearch" placeholder="Rechercher par nom ou référence..." />
        </div>
        <select id="gbFilter" class="modern-select">
            <option value="all">Tous</option>
            <option value="stock">En stock</option>
            <option value="alert">Alerte</option>
            <option value="out">Épuisés</option>
            <?php if ($gb_has_suivre_stock): ?><option value="tracked">Suivis</option><?php endif; ?>

        </select>
        <button class="modern-btn" onclick="gbExport()">
            <i class="fas fa-download"></i>
            Exporter
        </button>
    </div>

    <!-- Tableau moderne -->
    <div class="modern-table-container fade-in">
        <div class="modern-table-wrapper">
            <table class="modern-table" id="gbTable">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Nom</th>
                        <th>Fournisseur</th>
                        <th>Prix Achat</th>
                        <th>Prix Vente</th>
                        <th>Stock</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($gb_products as $p): ?>

                    <tr data-ref="<?php echo strtolower($p['reference']); ?>" data-name="<?php echo strtolower($p['nom']); ?>" data-qty="<?php echo (int)$p['quantite']; ?>" data-th="<?php echo (int)$p['seuil_alerte']; ?>" data-tracked="<?php echo ($gb_has_suivre_stock && isset($p['suivre_stock']) && (int)$p['suivre_stock']===1) ? '1' : '0'; ?>" data-id="<?php echo (int)$p['id']; ?>" data-price="<?php echo number_format((float)($p['prix_achat'] ?? 0), 2, '.', ''); ?>">

                        <td><code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.875rem;"><?php echo htmlspecialchars($p['reference']); ?></code></td>

                        <td>
                            <div style="font-weight: 700; color: var(--day-text);"><?php echo htmlspecialchars($p['nom']); ?></div>

                            <?php if (!empty($p['description'])): ?>

                                <div style="color: var(--day-text-light); font-size: 0.875rem; margin-top: 0.25rem;"><?php echo htmlspecialchars(substr($p['description'],0,60)); ?>...</div>

                            <?php endif; ?>

                        </td>
                        <td>
                            <?php echo $p['fournisseur_nom'] ? htmlspecialchars($p['fournisseur_nom']) : '<em style="color: var(--day-text-light);">Non défini</em>'; ?>

                        </td>
                        <td><strong><?php echo number_format((float)$p['prix_achat'],2); ?>€</strong></td>

                        <td><strong><?php echo number_format((float)$p['prix_vente'],2); ?>€</strong></td>

                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <strong style="font-size: 1.1rem;"><?php echo (int)$p['quantite']; ?></strong>

                                <?php if ($gb_has_suivre_stock && !empty($p['suivre_stock'])): ?>

                                    <i class="fas fa-eye" title="Suivi" style="color: #06b6d4;"></i>
                                <?php endif; ?>

                            </div>
                        </td>
                        <td>
                            <?php if ((int)$p['quantite'] === 0): ?>

                                <span class="modern-badge modern-badge--danger"><i class="fas fa-times-circle"></i> Épuisé</span>
                            <?php elseif ((int)$p['quantite'] <= (int)$p['seuil_alerte']): ?>

                                <span class="modern-badge modern-badge--warning"><i class="fas fa-exclamation-triangle"></i> Alerte</span>
                            <?php else: ?>

                                <span class="modern-badge modern-badge--success"><i class="fas fa-check-circle"></i> En stock</span>
                            <?php endif; ?>

                        </td>
                        <td class="modern-actions-cell">
                            <button class="modern-action-btn" title="Ajuster" onclick="gbOpenAdjust(<?php echo (int)$p['id']; ?>)">

                                <i class="fas fa-boxes"></i>
                            </button>
                            <button class="modern-action-btn" title="Modifier" onclick="gbEdit(<?php echo (int)$p['id']; ?>)">

                                <i class="fas fa-pen"></i>
                            </button>
                            <button class="modern-action-btn" title="Supprimer" onclick="gbDelete(<?php echo (int)$p['id']; ?>)">

                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
            </table>
            <?php if (empty($gb_products)): ?>

                <div style="text-align: center; padding: 3rem; color: var(--day-text-light);">
                    <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <div style="font-size: 1.1rem; font-weight: 600;">Aucun produit trouvé</div>
                    <div style="margin-top: 0.5rem;">Ajoutez votre premier produit pour commencer</div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Modal Ajout Produit -->
<!-- Modal Motif Sortie (Choix) -->
<div class="modern-modal" id="gbReasonModal" style="z-index: 1060;">
    <div class="modern-modal-dialog">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
            <h3 class="modern-modal-title">📉 Motif de Sortie</h3>
        </div>
        <div class="modern-modal-body text-center">
            <p class="mb-4">Vous réduisez le stock. Quelle est la raison ?</p>
            
            <div class="d-grid gap-3">
                <button class="btn btn-lg btn-outline-primary" onclick="gbOpenPartnerSelect()" style="border-radius: 15px; padding: 15px;">
                    <i class="fas fa-handshake fa-2x mb-2 d-block"></i>
                    Prêt / Transaction Partenaire
                </button>
                
                <button class="btn btn-lg btn-outline-secondary" onclick="gbOpenOtherReason()" style="border-radius: 15px; padding: 15px;">
                    <i class="fas fa-pen fa-2x mb-2 d-block"></i>
                    Autre (Casse, Perte...)
                </button>
            </div>
        </div>
        <div class="modern-modal-footer">
            <button class="modern-btn" onclick="gbClose('gbReasonModal')">Annuler</button>
        </div>
    </div>
</div>

<!-- Modal Sélection Partenaire -->
<div class="modern-modal" id="gbPartnerSelectModal" style="z-index: 1070;">
    <div class="modern-modal-dialog">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
            <h3 class="modern-modal-title">🤝 Sélection Partenaire</h3>
        </div>
        <div class="modern-modal-body">
            <div class="mb-3">
                <label class="form-label">Partenaire</label>
                <select id="gb_partner_select" class="form-select form-select-lg">
                    <option value="">Chargement...</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Type de Transaction</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="gb_partner_type" id="gb_type_avance" value="AVANCE" checked>
                    <label class="btn btn-outline-success" for="gb_type_avance">Prêt de pièce (Avance)</label>

                    <input type="radio" class="btn-check" name="gb_partner_type" id="gb_type_remboursement" value="REMBOURSEMENT">
                    <label class="btn btn-outline-danger" for="gb_type_remboursement">Retour de pièce (Remboursement)</label>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Valeur de la pièce (€)</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text">€</span>
                    <input type="number" id="gb_partner_amount" class="form-control" placeholder="0.00" step="0.01">
                </div>
            </div>
        </div>
        <div class="modern-modal-footer">
            <button class="modern-btn" onclick="gbClose('gbPartnerSelectModal')">Retour</button>
            <button class="modern-btn modern-btn--success" onclick="gbConfirmPartnerTransaction()">
                Valider Transaction
            </button>
        </div>
    </div>
</div>

<!-- Modal Raison Autre -->
<div class="modern-modal" id="gbOtherReasonModal" style="z-index: 1070;">
    <div class="modern-modal-dialog">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white;">
            <h3 class="modern-modal-title">📝 Préciser le motif</h3>
        </div>
        <div class="modern-modal-body">
            <div class="mb-3">
                <label class="form-label">Raison de la sortie</label>
                <textarea id="gb_other_reason_text" class="form-control" rows="3" placeholder="Ex: Casse lors du montage, Perdu..."></textarea>
            </div>
        </div>
        <div class="modern-modal-footer">
            <button class="modern-btn" onclick="gbClose('gbOtherReasonModal')">Retour</button>
            <button class="modern-btn modern-btn--primary" onclick="gbConfirmOtherReason()">
                Valider
            </button>
        </div>
    </div>
</div>

<div class="modern-modal" id="gbAddModal">
    <div class="modern-modal-dialog">
        <div class="modern-modal-header">
            <h3 class="modern-modal-title">
                <i class="fas fa-plus"></i>
                Nouveau Produit
            </h3>
        </div>
        <div class="modern-modal-body">
            <form id="gbAddForm" method="POST" action="?page=inventaire_actions">
                <input type="hidden" name="action" value="ajouter_produit" />
                <div class="modern-form-grid">
                    <div class="modern-form-group">
                        <label class="modern-form-label">Référence *</label>
                        <input class="modern-form-input" name="reference" required />
                    </div>
                    <div class="modern-form-group">
                        <label class="modern-form-label">Nom *</label>
                        <input class="modern-form-input" name="nom" required />
                    </div>
                </div>
                
                <div class="modern-form-group">
                    <label class="modern-form-label">Description</label>
                    <textarea class="modern-form-input" name="description" rows="3" style="resize: vertical;"></textarea>
                </div>
                
                <div class="modern-form-group">
                    <label class="modern-form-label">Fournisseur</label>
                    <select class="modern-form-input" name="fournisseur_id">
                        <option value="">-- Sélectionner un fournisseur --</option>
                        <?php

                        try {
                            $stmt_fournisseurs = $shop_pdo->prepare("SELECT id, nom FROM fournisseurs ORDER BY nom");
                            $stmt_fournisseurs->execute();
                            while ($fournisseur = $stmt_fournisseurs->fetch()) {
                                echo '<option value="' . (int)$fournisseur['id'] . '">' . htmlspecialchars($fournisseur['nom']) . '</option>';
                            }
                        } catch (Exception $e) {
                            // En cas d'erreur, continuer sans fournisseurs
                        }
                        ?>
                    </select>
                </div>
                
                <div class="modern-form-grid">
                    <div class="modern-form-group">
                        <label class="modern-form-label">Prix d'achat *</label>
                        <input class="modern-form-input" type="number" step="0.01" name="prix_achat" required />
                    </div>
                    <div class="modern-form-group">
                        <label class="modern-form-label">Prix de vente *</label>
                        <input class="modern-form-input" type="number" step="0.01" name="prix_vente" required />
                    </div>
                </div>
                
                <div class="modern-form-grid">
                    <div class="modern-form-group">
                        <label class="modern-form-label">Quantité *</label>
                        <input class="modern-form-input" type="number" name="quantite" value="0" required />
                    </div>
                    <div class="modern-form-group">
                        <label class="modern-form-label">Seuil d'alerte *</label>
                        <input class="modern-form-input" type="number" name="seuil_alerte" value="5" required />
                    </div>
                </div>
                
                <?php if ($gb_has_suivre_stock): ?>

                <div class="modern-form-group">
                    <label class="modern-form-label">
                        <input type="checkbox" name="suivre_stock" value="1" style="margin-right: 0.5rem;" />
                        Suivre ce produit
                    </label>
                </div>
                <?php endif; ?>

                
                <div class="modern-form-actions">
                    <button type="button" class="modern-btn" style="background: #6b7280; color: white;" onclick="gbClose('gbAddModal')">
                        Annuler
                    </button>
                    <button type="submit" class="modern-btn">
                        <i class="fas fa-plus"></i>
                        Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ajustement Stock -->
<div class="modern-modal" id="gbAdjustModal">
    <div class="modern-modal-dialog adjust-modal">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)); color: white; border-radius: 20px 20px 0 0;">
            <h3 class="modern-modal-title" style="color: white; margin-bottom: 0;" id="gb_adjust_name">Produit</h3>
        </div>
        <div class="modern-modal-body">
            <div class="adjust-product-info">
                <div class="adjust-product-ref" id="gb_adjust_ref">REF-000</div>
                <div style="color: var(--day-text-light); font-size: 0.9rem;">Stock actuel</div>
            </div>
            
            <div class="adjust-controls">
                <button class="adjust-btn adjust-btn--minus" onclick="gbDecreaseQuantity()" type="button">
                    <i class="fas fa-minus"></i>
                </button>
                
                <div class="adjust-display">
                    <div class="adjust-value" id="gb_adjust_current">0</div>
                    <div class="adjust-unit">unités</div>
                </div>
                
                <button class="adjust-btn adjust-btn--plus" onclick="gbIncreaseQuantity()" type="button">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            
            <!-- Champs cachés -->
            <input type="hidden" id="gb_adjust_id" />
            <input type="hidden" id="gb_adjust_original" />
            <input type="hidden" id="gb_adjust_new" />
            
            <div class="modern-form-actions">
                <button class="modern-btn" style="background: #6b7280; color: white;" onclick="gbClose('gbAdjustModal')" type="button">
                    <i class="fas fa-times"></i>
                    Annuler
                </button>
                <button class="modern-btn modern-btn--success" onclick="gbUpdateStock()" type="button">
                    <i class="fas fa-check"></i>
                    Confirmer
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Modal Scanner QR pour Réparations -->
<div class="modern-modal" id="gbQRRepairModal">
    <div class="modern-modal-dialog">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #8b5cf6, #6366f1); color: white;">
            <h3 class="modern-modal-title" style="color: white;">
                <i class="fas fa-qrcode"></i>
                Scanner QR Code Réparation
            </h3>
            <button class="modern-modal-close" onclick="gbCloseQRScanner()" type="button">×</button>
        </div>
        <div class="modern-modal-body">
            <!-- Zone scanner QR -->
            <div id="gb_qr_scan_area" style="height:320px; background:#000; border-radius:15px; overflow:hidden; position:relative; margin-bottom: 1rem;">
                <video id="gb_qr_video" style="width:100%; height:100%; object-fit:cover;"></video>
                <canvas id="gb_qr_canvas" style="display:none;"></canvas>
                <div style="position:absolute; left:50%; top:50%; width:200px; height:200px; transform:translate(-50%,-50%); border:3px solid #10b981; box-shadow:0 0 0 4px rgba(16,185,129,0.2); border-radius:15px;"></div>
            </div>
            
            <div id="gb_qr_status" style="text-align:center; padding:1rem; background:rgba(139,92,246,0.1); border-radius:10px; margin-bottom:1rem; color:#8b5cf6; font-weight:600;">
                📱 Scannez le QR code de la réparation...
            </div>
            
            <!-- Bouton manuel -->
            <div class="modern-form-actions">
                <button class="modern-btn modern-btn--secondary" onclick="gbSkipQRScan()" type="button">
                    <i class="fas fa-edit"></i>
                    AJUSTER LE STOCK (Sans Réparation)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Scanner -->
<div class="modern-modal" id="gbScanModal">
    <div class="modern-modal-dialog">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-radius: 20px 20px 0 0;">
            <h3 class="modern-modal-title" style="color: white; margin-bottom: 0;">
                <i class="fas fa-barcode"></i>
                Scanner Code-Barres
            </h3>
        </div>
        <div class="modern-modal-body">
            <div id="gb_scan_area" style="height:300px; background:#000; border-radius:15px; overflow:hidden; position:relative; margin-bottom: 1rem;">
                <video id="gb_scan_video" style="width:100%; height:100%; object-fit:cover;"></video>
                <div style="position:absolute; left:50%; top:50%; width:200px; height:2px; transform:translate(-50%,-50%); background:#10b981; box-shadow:0 0 10px #10b981;"></div>
                <button id="gbFlashBtn" onclick="gbToggleFlash()" style="position:absolute; bottom:20px; right:20px; background:rgba(0,0,0,0.5); border:2px solid white; color:white; width:40px; height:40px; border-radius:50%; display:none; align-items:center; justify-content:center; cursor:pointer; z-index:10;">
                    <i class="fas fa-bolt"></i>
                </button>
            </div>
            <div id="gb_scan_status" style="text-align:center; padding: 1rem; background: #f8fafc; border-radius: 10px; color: var(--day-text);">
                Initialisation du scanner...
            </div>
            <div class="modern-form-actions">
                <button class="modern-btn" style="background: #6b7280; color: white;" onclick="gbCloseScanner()">
                    <i class="fas fa-times"></i>
                    Fermer Scanner
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Vérification Stock -->
<!-- Modal Alertes Stock (redesign complet) -->
<div class="modern-modal" id="gbStockModal">
    <div class="modern-modal-dialog" style="max-width: 1000px;">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; display: flex; justify-content: space-between; align-items: center;">
            <h3 class="modern-modal-title" style="color: white; margin: 0;">
                <i class="fas fa-triangle-exclamation"></i>
                Alertes Stock
            </h3>
            <button class="modern-modal-close" onclick="gbClose('gbStockModal')" type="button" style="background: rgba(255,255,255,0.1); border: none; color: white; width: 36px; height: 36px; border-radius: 8px; font-size: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; flex-shrink: 0;">×</button>
        </div>
        <div class="modern-modal-body" style="padding: 0;">
            <!-- Stats Summary -->
            <div id="gb_stock_stats" style="padding: 1.5rem; background: #fffbeb; border-bottom: 1px solid #fde68a; display: flex; gap: 1rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 150px; text-align: center; padding: 1rem; background: white; border-radius: 12px; border: 2px solid #fef3c7;">
                    <div style="font-size: 2rem; font-weight: 700; color: #dc2626;" id="gb_stat_rupture">0</div>
                    <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">En rupture</div>
                </div>
                <div style="flex: 1; min-width: 150px; text-align: center; padding: 1rem; background: white; border-radius: 12px; border: 2px solid #fef3c7;">
                    <div style="font-size: 2rem; font-weight: 700; color: #f59e0b;" id="gb_stat_critique">0</div>
                    <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">Stock critique</div>
                </div>
                <div style="flex: 1; min-width: 150px; text-align: center; padding: 1rem; background: white; border-radius: 12px; border: 2px solid #fef3c7;">
                    <div style="font-size: 2rem; font-weight: 700; color: #10b981;" id="gb_stat_normal">0</div>
                    <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">Stock normal</div>
                </div>
            </div>
            
            <!-- Filters -->
            <div style="padding: 1rem 1.5rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <button class="gb-stock-filter-btn" data-filter="all" style="padding: 0.5rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.875rem;">
                        <i class="fas fa-list"></i> Tous
                    </button>
                    <button class="gb-stock-filter-btn" data-filter="rupture" style="padding: 0.5rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; background: white; color: #475569; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.875rem;">
                        <i class="fas fa-times-circle"></i> En rupture
                    </button>
                    <button class="gb-stock-filter-btn" data-filter="critique" style="padding: 0.5rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; background: white; color: #475569; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.875rem;">
                        <i class="fas fa-exclamation-triangle"></i> Critique
                    </button>
                    <button class="gb-stock-filter-btn" data-filter="normal" style="padding: 0.5rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; background: white; color: #475569; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.875rem;">
                        <i class="fas fa-check-circle"></i> Normal
                    </button>
                    <div style="flex: 1; min-width: 200px;">
                        <input id="gbStockSearch" placeholder="🔍 Rechercher un produit..." style="width: 100%; padding: 0.5rem 1rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.875rem;">
                    </div>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div style="padding: 1.5rem;">
                <div id="gbStockCards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; max-height: 500px; overflow-y: auto;">
                    <div style="text-align: center; padding: 3rem; color: #64748b; grid-column: 1 / -1;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 3rem; margin-bottom: 1rem; color: #f59e0b;"></i>
                        <div style="font-size: 1.1rem;">Chargement des alertes...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal Historique des Mouvements -->
<div class="modern-modal" id="gbHistoryModal">
    <div class="modern-modal-dialog" style="max-width: 1200px;">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; display: flex; justify-content: space-between; align-items: center;">
            <h3 class="modern-modal-title" style="color: white; margin: 0;">
                <i class="fas fa-history"></i>
                Historique des Mouvements de Stock
            </h3>
            <button class="modern-modal-close" onclick="gbClose('gbHistoryModal')" type="button" style="background: rgba(255,255,255,0.1); border: none; color: white; width: 36px; height: 36px; border-radius: 8px; font-size: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; flex-shrink: 0;">×</button>
        </div>
        <div class="modern-modal-body" style="padding: 0;">
            <!-- Loading -->
            <div id="gb_history_loading" style="text-align: center; padding: 3rem;">
                <i class="fas fa-spinner fa-spin" style="font-size: 3rem; color: #8b5cf6; margin-bottom: 1rem;"></i>
                <div style="color: #64748b;">Chargement de l'historique...</div>
            </div>
            
            <!-- Filtres de période -->
            <div id="gb_history_filters" style="padding: 1.5rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: none;">
                <!-- Boutons de période -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #475569; font-size: 0.875rem;">
                        <i class="fas fa-calendar-alt"></i> Sélectionner une période
                    </label>
                    <div class="gb-period-buttons" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button class="gb-period-btn" data-period="current_month" style="padding: 0.5rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; background: white; color: #475569; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.875rem;">
                            <i class="fas fa-calendar-day"></i> Ce mois
                        </button>
                        <button class="gb-period-btn" data-period="last_month" style="padding: 0.5rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; background: white; color: #475569; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.875rem;">
                            <i class="fas fa-calendar-minus"></i> Mois précédent
                        </button>
                        <button class="gb-period-btn" data-period="quarter" style="padding: 0.5rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; background: white; color: #475569; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.875rem;">
                            <i class="fas fa-calendar-week"></i> Trimestre
                        </button>
                        <button class="gb-period-btn" data-period="all" style="padding: 0.5rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; background: white; color: #475569; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.875rem;">
                            <i class="fas fa-infinity"></i> Tout
                        </button>
                        <button class="gb-period-btn" data-period="custom" style="padding: 0.5rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; background: white; color: #475569; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.875rem;">
                            <i class="fas fa-calendar-alt"></i> Personnalisé
                        </button>
                    </div>
                </div>
                
                <!-- Dates personnalisées -->
                <div id="gb_history_custom_dates" style="display: none; margin-top: 1rem;">
                    <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Date début</label>
                            <input type="date" id="gb_history_date_start" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
                        </div>
                        
                        <div style="flex: 1; min-width: 200px;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem;">Date fin</label>
                            <input type="date" id="gb_history_date_end" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
                        </div>
                        
                        <button id="gb_history_apply_custom" style="padding: 0.5rem 1.5rem; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                            <i class="fas fa-check"></i> Appliquer
                        </button>
                    </div>
                </div>
                
                <input type="hidden" id="gb_history_period" value="current_month">
            </div>
            
            <!-- Table Container -->
            <div id="gb_history_content" style="display: none;">
                <div style="overflow-x: auto;">
                    <table class="gb-history-table">
                        <thead>
                            <tr>
                                <th style="width: 140px;">Date</th>
                                <th>Produit</th>
                                <th style="width: 100px; text-align: center;">Qté</th>
                                <th style="width: 200px;">Type</th>
                                <th>Motif</th>
                                <th style="width: 150px;">Utilisateur</th>
                            </tr>
                        </thead>
                        <tbody id="gb_history_tbody">
                        </tbody>
                    </table>
                </div>
                
                <div id="gb_history_empty" style="display: none; text-align: center; padding: 3rem; color: #64748b;">
                    <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.5; margin-bottom: 1rem; display: block;"></i>
                    <div style="font-size: 1.1rem; font-weight: 600;">Aucun mouvement trouvé</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.gb-history-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.95rem;
}
.gb-history-table thead {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    position: sticky;
    top: 0;
    z-index: 10;
}
.gb-history-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 700;
    color: #1e293b;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}
.gb-history-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s ease;
}
.gb-history-table tbody tr:hover {
    background: #f8fafc;
}
.gb-history-table td {
    padding: 0.75rem 1rem;
    color: #475569;
}
.gb-history-date {
    font-size: 0.85rem;
    color: #64748b;
    font-family: 'Courier New', monospace;
}
.gb-history-product {
    font-weight: 600;
    color: #1e293b;
}
.gb-history-ref {
    font-size: 0.85rem;
    color: #94a3b8;
    font-family: 'Courier New', monospace;
    display: block;
    margin-top: 0.25rem;
}
.gb-history-qty {
    font-weight: 700;
    font-size: 1.1rem;
    text-align: center;
    font-family: 'Courier New', monospace;
}
.gb-history-type {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
}
/* Night mode pour les filtres */
body.night-mode #gb_history_filters {
    background: #1e293b;
    border-bottom-color: #334155;
}
body.night-mode #gb_history_filters label {
    color: #cbd5e1;
}
body.night-mode #gb_history_date_start,
body.night-mode #gb_history_date_end {
    background: #0f172a;
    color: #f1f5f9;
    border-color: #475569;
}

/* Styles pour les boutons de période */
.gb-period-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.gb-period-btn:active {
    transform: translateY(0);
}

/* Night mode pour les boutons de période */
body.night-mode .gb-period-btn {
    background: #0f172a !important;
    border-color: #475569 !important;
    color: #cbd5e1 !important;
}
body.night-mode .gb-period-btn:hover {
    background: #1e293b !important;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

/* Bouton actif (appliqué via JS) conserve son gradient */
body.night-mode .gb-period-btn[style*="linear-gradient"] {
    /* Le style inline du bouton actif prime */
}

/* Bouton appliquer custom */
#gb_history_apply_custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(139, 92, 246, 0.4);
}

/* Night mode pour le bouton close */
body.night-mode .modern-modal-close {
    background: rgba(255,255,255,0.05) !important;
}
body.night-mode .modern-modal-close:hover {
    background: rgba(255,255,255,0.15) !important;
}

body.night-mode .gb-history-table thead { background: linear-gradient(135deg, #1e293b, #334155); }
body.night-mode .gb-history-table th { color: #f1f5f9; border-bottom-color: #334155; }
body.night-mode .gb-history-table tbody tr { border-bottom-color: #334155; }
body.night-mode .gb-history-table tbody tr:hover { background: #1e293b; }
body.night-mode .gb-history-table td,
body.night-mode .gb-history-product { color: #cbd5e1; }
</style>

<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
// Détection IMMÉDIATE du mode nuit (avant DOMContentLoaded)
(function() {
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const storedTheme = localStorage.getItem('theme');
    
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.documentElement.classList.add('night-mode');
        document.body.classList.add('night-mode');
        console.log('🌙 Mode nuit détecté et appliqué immédiatement');
    } else {
        document.documentElement.classList.remove('night-mode');
        document.body.classList.remove('night-mode');
        console.log('☀️ Mode jour détecté et appliqué immédiatement');
    }
})();

// Variables globales pour l'ajustement
let gbCurrentQuantity = 0;
let gbOriginalQuantity = 0;
let gbProductPrice = 0; // Prix du produit pour transaction partenaire
let gbCurrentProductId = null; // ID du produit pour scanner QR

// Utils Modals
function gbOpen(id){ 
    document.getElementById(id).classList.add('show'); 
    document.body.style.overflow = 'hidden';
}

function gbClose(id){ 
    document.getElementById(id).classList.remove('show'); 
    document.body.style.overflow = 'auto';
    
    // Si on ferme le modal d'ajout, réinitialiser le champ référence
    if (id === 'gbAddModal') {
        const refInput = document.querySelector('#gbAddModal input[name="reference"]');
        if (refInput) {
            refInput.readOnly = false; // Débloquer le champ
        }
    }
}

// Ajustement de stock
function gbOpenAdjust(id){
    const tr = document.querySelector(`#gbTable tbody tr[data-id="${id}"]`);
    if(!tr) return;
    
    const productName = tr.querySelector('td:nth-child(2) div').textContent;
    const productRef = tr.querySelector('code').textContent;
    const currentQty = parseInt(tr.dataset.qty) || 0;
    const productPrice = parseFloat(tr.dataset.price) || 0; // Récupérer le prix
    
    gbCurrentQuantity = currentQty;
    gbOriginalQuantity = currentQty;
    gbProductPrice = productPrice; // Stocker le prix
    gbCurrentProductId = id; // Stocker l'ID pour le scanner QR
    
    document.getElementById('gb_adjust_id').value = id;
    document.getElementById('gb_adjust_name').textContent = productName;
    document.getElementById('gb_adjust_ref').textContent = productRef;
    document.getElementById('gb_adjust_current').textContent = currentQty;
    document.getElementById('gb_adjust_original').value = currentQty;
    document.getElementById('gb_adjust_new').value = currentQty;
    
    // Ouvrir le scanner QR au lieu du modal d'ajustement direct
    gbOpen('gbQRRepairModal');
    gbInitQRScanner();
}

function gbDecreaseQuantity() {
    if (gbCurrentQuantity > 0) {
        gbCurrentQuantity--;
        gbUpdateQuantityDisplay();
    }
}

function gbIncreaseQuantity() {
    gbCurrentQuantity++;
    gbUpdateQuantityDisplay();
}

function gbUpdateQuantityDisplay() {
    const display = document.getElementById('gb_adjust_current');
    display.textContent = gbCurrentQuantity;
    document.getElementById('gb_adjust_new').value = gbCurrentQuantity;
    
    // Changer la couleur selon la variation
    if (gbCurrentQuantity > gbOriginalQuantity) {
        display.style.color = '#10b981'; // Vert
    } else if (gbCurrentQuantity < gbOriginalQuantity) {
        display.style.color = '#ef4444'; // Rouge
    } else {
        display.style.color = 'var(--day-text)'; // Normal
    }
}

function gbUpdateStock() {
    const produitId = document.getElementById('gb_adjust_id').value;
    const nouvelleQuantite = gbCurrentQuantity;
    const originalQuantite = gbOriginalQuantity;
    
    if (isNaN(nouvelleQuantite) || nouvelleQuantite < 0) {
        gbShowToast('❌ Quantité invalide', 'error');
        return;
    }
    
    if (nouvelleQuantite === originalQuantite) {
        gbClose('gbAdjustModal');
        return;
    }

    // INTERCEPTION: Si on baisse le stock, on demande pourquoi
    if (nouvelleQuantite < originalQuantite) {
        gbOpen('gbReasonModal');
        return; 
    }
    
    // Si on augmente, ou si on a passé l'étape de raison, on continue standard
    gbExecuteStockUpdate(produitId, nouvelleQuantite);
}

// Fonction interne pour exécuter la mise à jour (avec motif optionnel)
function gbExecuteStockUpdate(produitId, nouvelleQuantite, motif = '', callback = null) {
    const formData = new FormData();
    formData.append('produit_id', produitId);
    formData.append('nouvelle_quantite', nouvelleQuantite);
    if(motif) formData.append('motif', motif);
    
    fetch('ajax/ajuster_stock.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour l'affichage
            const row = document.querySelector(`#gbTable tbody tr[data-id="${produitId}"]`);
            if (row) {
                const quantiteCell = row.querySelector('td:nth-child(6) strong');
                if (quantiteCell) {
                    quantiteCell.textContent = data.nouvelle_quantite;
                }
                row.dataset.qty = data.nouvelle_quantite;
            }
            
            gbClose('gbAdjustModal');
            gbClose('gbReasonModal');     // Fermer tous les potentiels modals
            gbClose('gbPartnerSelectModal');
            gbClose('gbOtherReasonModal');

            gbShowToast('✅ ' + (data.message || 'Stock mis à jour'), 'success');
            
            if (callback) callback();
        } else {
            gbShowToast('❌ Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        gbShowToast('❌ Erreur de connexion', 'error');
    });
}

// Search/Filter
document.getElementById('gbSearch').addEventListener('input', function(){
    const q = this.value.toLowerCase();
    document.querySelectorAll('#gbTable tbody tr').forEach(tr=>{
        const ok = (tr.dataset.name||'').includes(q) || (tr.dataset.ref||'').includes(q);
        tr.style.display = ok ? '' : 'none';
    });
});

document.getElementById('gbFilter').addEventListener('change', function(){
    const f = this.value;
    document.querySelectorAll('#gbTable tbody tr').forEach(tr=>{
        const qty = parseInt(tr.dataset.qty||'0',10);
        const th  = parseInt(tr.dataset.th||'0',10);
        const tk  = tr.dataset.tracked === '1';
        let vis = true;
        switch(f){
            case 'stock': vis = qty>0; break;
            case 'alert': vis = qty>0 && qty<=th; break;
            case 'out': vis = qty===0; break;
            case 'tracked': vis = tk; break;
            default: vis = true;
        }
        tr.style.display = vis ? '' : 'none';
    });
});

// === Gestion du flux Partenaire ===

// 1. Ouvrir le selecteur de partenaire
function gbOpenPartnerSelect() {
    gbClose('gbReasonModal');
    gbOpen('gbPartnerSelectModal');
    
    // Pré-remplir le montant avec le prix du produit
    const amountInput = document.getElementById('gb_partner_amount');
    if (gbProductPrice > 0) {
        amountInput.value = gbProductPrice.toFixed(2);
    }
    
    // Charger les partenaires via AJAX si vide
    const select = document.getElementById('gb_partner_select');
    if (select.options.length <= 1) { // 1 = "Chargement..."
        fetch('ajax/get_partenaires_simple.php')
        .then(res => res.json())
        .then(data => {
            select.innerHTML = '<option value="">Choisir un partenaire...</option>';
            if(data.success && data.partenaires) {
                data.partenaires.forEach(p => {
                    select.innerHTML += `<option value="${p.id}">${p.nom}</option>`;
                });
            }
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = '<option value="">Erreur chargement</option>';
        });
    }
}

// 2. Valider la transaction partenaire
function gbConfirmPartnerTransaction() {
    const partenaireId = document.getElementById('gb_partner_select').value;
    const type = document.querySelector('input[name="gb_partner_type"]:checked').value;
    const montant = document.getElementById('gb_partner_amount').value;
    const produitRef = document.getElementById('gb_adjust_ref').textContent;
    const produitNom = document.getElementById('gb_adjust_name').textContent;
    
    if (!partenaireId || !montant) {
        gbShowToast('⚠️ Veuillez choisir un partenaire et un montant', 'warning');
        return;
    }
    
    const btn = document.querySelector('#gbPartnerSelectModal .modern-btn--success');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
    btn.disabled = true;

    // 1. Créer la transaction financière
    const formData = new FormData();
    formData.append('partenaire_id', partenaireId);
    formData.append('type', type);
    formData.append('montant', montant);
    formData.append('description', `Stock ${type === 'AVANCE' ? 'Sortie (Prêt)' : 'Sortie (Retour)'} : ${produitRef} - ${produitNom}`);

    fetch('ajax/add_transaction_partenaire.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'  // Force l'envoi des cookies
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            gbShowToast('✅ Transaction enregistrée', 'success');
            
            // 2. Mettre à jour le stock
            const produitId = document.getElementById('gb_adjust_id').value;
            const nouvelleQuantite = gbCurrentQuantity;
            const nomPartenaire = document.getElementById('gb_partner_select').selectedOptions[0].text;
            
            gbExecuteStockUpdate(
                produitId, 
                nouvelleQuantite, 
                `Sortie vers Partenaire: ${nomPartenaire} (${type})`,
                () => { // Callback
                     btn.innerHTML = originalText;
                     btn.disabled = false;
                }
            );
        } else {
            throw new Error(data.message);
        }
    })
    .catch(err => {
        gbShowToast('❌ Erreur: ' + err.message, 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// === Gestion Flux Autre Raison ===

function gbOpenOtherReason() {
    gbClose('gbReasonModal');
    gbOpen('gbOtherReasonModal');
    document.getElementById('gb_other_reason_text').focus();
}

function gbConfirmOtherReason() {
    const reason = document.getElementById('gb_other_reason_text').value;
    if (!reason.trim()) {
        gbShowToast('⚠️ Veuillez indiquer une raison', 'warning');
        return;
    }
    
    const produitId = document.getElementById('gb_adjust_id').value;
    const nouvelleQuantite = gbCurrentQuantity;
    
    gbExecuteStockUpdate(produitId, nouvelleQuantite, reason);
}

// === Gestion du Scanner QR pour Réparations ===

let gbQRScannerActive = false;
let gbQRAnimationFrame = null;

function gbInitQRScanner() {
    const video = document.getElementById('gb_qr_video');
    const canvas = document.getElementById('gb_qr_canvas');
    const statusDiv = document.getElementById('gb_qr_status');
    
    if (!video || !canvas) {
        console.error('Éléments QR scanner introuvables');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    gbQRScannerActive = true;
    
    // Démarrer la caméra
    navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' }
    })
    .then(stream => {
        video.srcObject = stream;
        video.play();
        
        // Quand la vidéo est prête, démarrer le scan
        video.addEventListener('loadedmetadata', () => {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            gbScanQRFrame(video, canvas, ctx, statusDiv);
        });
    })
    .catch(err => {
        console.error('Erreur caméra:', err);
        statusDiv.innerHTML = '❌ Impossible d\'accéder à la caméra';
        statusDiv.style.background = 'rgba(239,68,68,0.1)';
        statusDiv.style.color = '#ef4444';
    });
}

function gbScanQRFrame(video, canvas, ctx, statusDiv) {
    if (!gbQRScannerActive) return;
    
    // Dessiner l'image vidéo sur le canvas
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Obtenir les données d'image
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    
    // Utiliser jsQR pour détecter le QR code
    if (typeof jsQR !== 'undefined') {
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        
        if (code && code.data) {
            // QR code détecté !
            gbOnQRDetected(code.data);
            return; // Arrêter le scan
        }
    }
    
    // Continuer le scan
    gbQRAnimationFrame = requestAnimationFrame(() => gbScanQRFrame(video, canvas, ctx, statusDiv));
}

function gbOnQRDetected(url) {
    console.log('QR détecté:', url);
    
    const statusDiv = document.getElementById('gb_qr_status');
    
    // Extraire l'ID de réparation depuis l'URL (format: page=statut_rapide&id=1741)
    const match = url.match(/[?&]id=(\d+)/i);
    
    if (!match) {
        statusDiv.innerHTML = '❌ QR code invalide (pas une URL de suivi)';
        statusDiv.style.background = 'rgba(239,68,68,0.1)';
        statusDiv.style.color = '#ef4444';
        
        // Réessayer après 2 secondes
        setTimeout(() => {
            statusDiv.innerHTML = '📱 Scannez le QR code de la réparation...';
            statusDiv.style.background = 'rgba(139,92,246,0.1)';
            statusDiv.style.color = '#8b5cf6';
        }, 2000);
        return;
    }
    
    const reparationId = match[1];
    
    // Afficher un indicateur de traitement
    statusDiv.innerHTML = `✅ Réparation #${reparationId} détectée! Association en cours...`;
    statusDiv.style.background = 'rgba(16,185,129,0.1)';
    statusDiv.style.color = '#10b981';
    
    // Arrêter le scanner
    gbStopQRScanner();
    
    // Associer la pièce à la réparation
    gbAssociatePieceToRepair(gbCurrentProductId, reparationId);
}

function gbAssociatePieceToRepair(produitId, reparationId) {
    const formData = new FormData();
    formData.append('produit_id', produitId);
    formData.append('reparation_id', reparationId);
    formData.append('quantite', 1);
    
    fetch('ajax/associer_piece_reparation.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            gbShowToast(`✅ ${data.produit_nom || 'Pièce'} associée à la réparation #${reparationId}`, 'success');
            gbClose('gbQRRepairModal');
            
            // Rafraîchir la page pour afficher le nouveau stock
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            throw new Error(data.message);
        }
    })
    .catch(err => {
        gbShowToast('❌ Erreur: ' + err.message, 'error');
        
        // Réouvrir le scanner après erreur
        setTimeout(() => {
            gbInitQRScanner();
        }, 2000);
    });
}

function gbStopQRScanner() {
    gbQRScannerActive = false;
    
    if (gbQRAnimationFrame) {
        cancelAnimationFrame(gbQRAnimationFrame);
        gbQRAnimationFrame = null;
    }
    
    const video = document.getElementById('gb_qr_video');
    if (video && video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
        video.srcObject = null;
    }
}

function gbCloseQRScanner() {
    gbStopQRScanner();
    gbClose('gbQRRepairModal');
}

function gbSkipQRScan() {
    gbStopQRScanner();
    gbClose('gbQRRepairModal');
    
    // Ouvrir directement le modal d'ajustement
    gbOpen('gbAdjustModal');
}

// Toast notifications
function gbShowToast(message, type = 'info') {
    // Supprimer les anciens toasts
    const existingToasts = document.querySelectorAll('.modern-toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `modern-toast modern-toast--${type}`;
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i>
            <span style="font-weight: 500;">${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Supprimer après 4 secondes
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Export
function gbExport(){ 
    const f = document.getElementById('gbFilter').value; 
    window.open(`ajax/export_print.php?filter=${encodeURIComponent(f)}`, '_blank'); 
}

// Scanner
let gbStream=null;
function gbOpenScanner(){ gbOpen('gbScanModal'); gbStartCam(); }
function gbCloseScanner(){ gbStopCam(); gbClose('gbScanModal'); }

// Variables pour la stabilisation du scanner
let gbDetectedCodes = [];
let gbIsProcessing = false;
let gbLastProcessTime = 0;

function gbStartCam(){
    gbShowToast('📷 Ouverture de la caméra...', 'info');
    
    const constraints = {
        video: {
            facingMode: 'environment'
        }
    };
    
    navigator.mediaDevices.getUserMedia(constraints).then(stream=>{
        gbStream = stream;
        gbShowToast('✅ Caméra active - Scannez un code', 'success');
        
        // Gestion du Flash
        const track = stream.getVideoTracks()[0];
        const capabilities = track.getCapabilities();
        const flashBtn = document.getElementById('gbFlashBtn');
        
        if (capabilities.torch && flashBtn) {
            flashBtn.style.display = 'flex';
        } else if (flashBtn) {
            flashBtn.style.display = 'none';
        }
        
        // Vérifier que l'élément target existe
        const scanArea = document.getElementById('gb_scan_area');
        if (!scanArea) {
            gbShowToast('❌ Erreur: Zone de scan non trouvée', 'error');
            return;
        }
        
        // Configuration Quagga plus standard pour assurer la compatibilité
        // Configuration Quagga optimisée pour la vitesse de détection
        const config = {
            inputStream: {
                name: "Live",
                type: 'LiveStream',
                target: scanArea,
                constraints: {
                    width: 640,
                    height: 480,
                    facingMode: 'environment'
                },
                area: { // Zone de détection plus large (10% marges)
                    top: "10%",
                    right: "10%",
                    bottom: "10%",
                    left: "10%"
                }
            },
            locator: {
                patchSize: "medium",
                halfSample: false // Désactivé pour meilleure netteté en 640x480
            },
            numOfWorkers: navigator.hardwareConcurrency ? Math.min(4, navigator.hardwareConcurrency) : 2,
            frequency: 20,
            decoder: {
                readers: [
                    "ean_reader", 
                    "code_128_reader",
                    "code_39_reader",
                    "ean_8_reader" 
                ],
                multiple: false
            },
            locate: true
        };
        
        if(typeof Quagga !== 'undefined') {
            document.getElementById('gb_scan_status').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Démarrage de la détection...';
            
            Quagga.init(config, err => {
                if(err){ 
                    document.getElementById('gb_scan_status').innerHTML = '<span style="color:red"><i class="fas fa-exclamation-circle"></i> Erreur initialisation</span>';
                    gbShowToast('❌ Erreur scanner: ' + (err.message || err), 'error');
                    console.error('Erreur Quagga init:', err);
                    return;
                } 
                Quagga.start(); 
                document.getElementById('gb_scan_status').innerHTML = '<span style="color:green"><i class="fas fa-check"></i> Scanner actif - Visez le code-barres</span>';
            });
            
            // Feedback visuel en temps réel (dessine les boîtes vertes)
            Quagga.onProcessed(function(result) {
                const drawingCtx = Quagga.canvas.ctx.overlay;
                const drawingCanvas = Quagga.canvas.dom.overlay;
                
                if (result) {
                    if (result.boxes) {
                        drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute("width")), parseInt(drawingCanvas.getAttribute("height")));
                        result.boxes.filter(function (box) {
                            return box !== result.box;
                        }).forEach(function (box) {
                            Quagga.ImageDebug.drawPath(box, {x: 0, y: 1}, drawingCtx, {color: "green", lineWidth: 2});
                        });
                    }
                    if (result.box) {
                        Quagga.ImageDebug.drawPath(result.box, {x: 0, y: 1}, drawingCtx, {color: "#00F", lineWidth: 2});
                    }
                    if (result.codeResult && result.codeResult.code) {
                        Quagga.ImageDebug.drawPath(result.line, {x: 'x', y: 'y'}, drawingCtx, {color: 'red', lineWidth: 3});
                        // AFFICHER LE CODE DÉTECTÉ POUR DEBUG
                        document.getElementById('gb_scan_status').innerHTML = `Scanning: <b>${result.codeResult.code}</b>`;
                    }
                }
            });
            
            let gbScanBuffer = {}; // Stocke les comptes pour chaque code
            let gbBestCandidate = null;

            Quagga.onDetected(res => {
                if(!res || !res.codeResult || !res.codeResult.code) return;
                
                const rawCode = res.codeResult.code.trim();
                const now = Date.now();
                
                // Nettoyage périodique du buffer (toutes les 2s)
                if (Math.random() < 0.05) gbScanBuffer = {}; 

                if(gbIsProcessing) return;

                // Algorithme de stabilisation
                if (!gbScanBuffer[rawCode]) gbScanBuffer[rawCode] = 0;
                gbScanBuffer[rawCode]++;

                // Feedback visuel de progression
                if (gbScanBuffer[rawCode] > 1) {
                    document.getElementById('gb_scan_status').innerHTML = `<span style="color:orange"><i class="fas fa-crosshairs"></i> Stabilisation... ${rawCode} (${gbScanBuffer[rawCode]}/3)</span>`;
                }

                // Seuil de validation : 3 détections identiques nécessaires
                if (gbScanBuffer[rawCode] < 3) return;

                // Validation réussie !
                const code = rawCode;
                
                if(code.length < 3 || (now - gbLastProcessTime) < 1500) {
                    return; // Anti-rebond après succès
                }
                
                gbIsProcessing = true;
                gbLastProcessTime = now;
                gbScanBuffer = {}; // Reset buffer
                
                gbShowToast(`📦 Code détecté: ${code}`, 'success');
                gbCheckCode(code);
                
                setTimeout(() => {
                    gbIsProcessing = false;
                }, 3000);
            });
        } else {
            gbShowToast('❌ Librairie Quagga non chargée', 'error');
        }
    }).catch(err => { 
        console.error('Erreur getUserMedia:', err);
        gbShowToast('❌ Impossible d\'accéder à la caméra: ' + err.message, 'error'); 
    });
}

function gbToggleFlash() {
    if (gbStream) {
        const track = gbStream.getVideoTracks()[0];
        const capabilities = track.getCapabilities();
        if (capabilities.torch) {
            const current = track.getSettings().torch;
            track.applyConstraints({
                advanced: [{torch: !current}]
            }).then(() => {
                const btn = document.getElementById('gbFlashBtn');
                if(btn) {
                    btn.style.background = !current ? 'rgba(255, 255, 255, 0.8)' : 'rgba(0,0,0,0.5)';
                    btn.style.color = !current ? '#000' : '#fff';
                }
            }).catch(e => console.error(e));
        }
    }
}

function gbStopCam(){ 
    if(gbStream){ 
        // Éteindre le flash avant de couper
        try {
            const track = gbStream.getVideoTracks()[0];
            track.applyConstraints({advanced: [{torch: false}]});
        } catch(e){}
        
        gbStream.getTracks().forEach(t=>t.stop()); 
        gbStream=null; 
    } 
    
    if(typeof Quagga!=='undefined'){ 
        try{ 
            Quagga.stop(); 
        }catch(e){} 
    }
    
    gbDetectedCodes = [];
    gbIsProcessing = false;
    gbLastProcessTime = 0;
}

function gbCheckCode(code){ 
    fetch(`ajax/verifier_produit.php?code=${encodeURIComponent(code)}`)
    .then(r=>r.json())
    .then(d=>{
        if(d.existe && d.id){ 
            // Produit trouvé : fermer le scanner et ouvrir l'ajustement
            gbCloseScanner(); 
            gbOpenAdjust(d.id);
        } else { 
            // Produit non trouvé : fermer le scanner et ouvrir le modal d'ajout
            gbCloseScanner();
            gbShowToast('📦 Produit non trouvé - Ajout rapide', 'info');
            
            // Ouvrir le modal d'ajout
            setTimeout(() => {
                gbOpen('gbAddModal');
                
                // Pré-remplir le champ référence avec le code scanné
                const refInput = document.querySelector('#gbAddModal input[name="reference"]');
                if (refInput) {
                    refInput.value = code;
                    refInput.readOnly = true; // Verrouiller le champ référence
                    
                    // Focus sur le champ nom pour une saisie rapide
                    const nameInput = document.querySelector('#gbAddModal input[name="nom"]');
                    if (nameInput) {
                        setTimeout(() => nameInput.focus(), 300);
                    }
                }
            }, 300);
        } 
    })
    .catch(()=> { 
        gbShowToast('❌ Erreur de vérification', 'error'); 
    }); 
}

// Stock check
function gbOpenStockCheck(){ 
    gbOpen('gbStockModal'); 
    gbLoadTracked(); 
}

function gbLoadTracked(){
    const wrap = document.getElementById('gbStockCards');
    wrap.innerHTML = '<div style="text-align: center; padding: 3rem; color: #64748b; grid-column: 1 / -1;"><i class="fas fa-spinner fa-spin" style="font-size: 3rem; margin-bottom: 1rem; color: #f59e0b;"></i><div style="font-size: 1.1rem;">Chargement des alertes...</div></div>';
    
    fetch('ajax/get_tracked_products.php').then(r=>r.json()).then(d=>{
        if(!d.success){ 
            wrap.innerHTML = `<div style="text-align: center; padding: 3rem; color: #64748b; grid-column: 1 / -1;"><i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; color: #ef4444;"></i><div style="font-size: 1.1rem;">${d.error||'Erreur de chargement'}</div></div>`;
            return; 
        }
        
        if(!d.products || d.products.length===0){ 
            wrap.innerHTML = '<div style="text-align: center; padding: 3rem; color: #64748b; grid-column: 1 / -1;"><i class="fas fa-info-circle" style="font-size: 3rem; margin-bottom: 1rem; color: #06b6d4;"></i><div style="font-size: 1.1rem;">Aucun produit suivi.</div><div style="margin-top: 0.5rem; font-size: 0.9rem;">Cochez "Suivre ce produit" lors de l\'ajout.</div></div>';
            return; 
        }
        
        // Calculer les statistiques
        let statRupture = 0, statCritique = 0, statNormal = 0;
        d.products.forEach(p => {
            if (p.quantite <= 0) statRupture++;
            else if (p.quantite <= p.seuil_alerte) statCritique++;
            else statNormal++;
        });
        
        // Mettre à jour les stats
        document.getElementById('gb_stat_rupture').textContent = statRupture;
        document.getElementById('gb_stat_critique').textContent = statCritique;
        document.getElementById('gb_stat_normal').textContent = statNormal;
        
        // Stocker les produits globalement pour le filtrage
        window.gbAllProducts = d.products;
        
        // Afficher tous les produits
        gbRenderStockCards(d.products);
        
        // Initialiser les event listeners pour les filtres (une seule fois)
        if (!window.gbStockFiltersInitialized) {
            window.gbStockFiltersInitialized = true;
            
            // Filtres par statut
            document.querySelectorAll('.gb-stock-filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const filter = this.dataset.filter;
                    
                    // Mettre à jour l'état actif des boutons
                    document.querySelectorAll('.gb-stock-filter-btn').forEach(b => {
                        b.style.background = 'white';
                        b.style.color = '#475569';
                    });
                    this.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
                    this.style.color = 'white';
                    
                    // Filtrer les produits
                    let filtered = window.gbAllProducts;
                    if (filter === 'rupture') {
                        filtered = window.gbAllProducts.filter(p => p.quantite <= 0);
                    } else if (filter === 'critique') {
                        filtered = window.gbAllProducts.filter(p => p.quantite > 0 && p.quantite <= p.seuil_alerte);
                    } else if (filter === 'normal') {
                        filtered = window.gbAllProducts.filter(p => p.quantite > p.seuil_alerte);
                    }
                    
                    // Appliquer aussi la recherche si présente
                    const search = document.getElementById('gbStockSearch').value.toLowerCase().trim();
                    if (search) {
                        filtered = filtered.filter(p => 
                            p.nom.toLowerCase().includes(search) || 
                            p.reference.toLowerCase().includes(search)
                        );
                    }
                    
                    gbRenderStockCards(filtered);
                });
            });
            
            // Recherche
            document.getElementById('gbStockSearch').addEventListener('input', function() {
                const search = this.value.toLowerCase().trim();
                const activeFilter = document.querySelector('.gb-stock-filter-btn[style*="linear-gradient"]')?.dataset.filter || 'all';
                
                let filtered = window.gbAllProducts;
                
                // Appliquer filtre de statut
                if (activeFilter === 'rupture') {
                    filtered = filtered.filter(p => p.quantite <= 0);
                } else if (activeFilter === 'critique') {
                    filtered = filtered.filter(p => p.quantite > 0 && p.quantite <= p.seuil_alerte);
                } else if (activeFilter === 'normal') {
                    filtered = filtered.filter(p => p.quantite > p.seuil_alerte);
                }
                
                // Appliquer recherche textuelle
                if (search) {
                    filtered = filtered.filter(p => 
                        p.nom.toLowerCase().includes(search) || 
                        p.reference.toLowerCase().includes(search)
                    );
                }
                
                gbRenderStockCards(filtered);
            });
        }
    }).catch(err => {
        console.error('Erreur chargement alertes:', err);
        wrap.innerHTML = `<div style="text-align: center; padding: 3rem; color: #64748b; grid-column: 1 / -1;"><i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; color: #ef4444;"></i><div style="font-size: 1.1rem;">Erreur de connexion</div></div>`;
    });
}

// Fonction pour afficher les cartes de produits
function gbRenderStockCards(products) {
    const wrap = document.getElementById('gbStockCards');
    
    if (!products || products.length === 0) {
        wrap.innerHTML = '<div style="text-align: center; padding: 3rem; color: #64748b; grid-column: 1 / -1;"><i class="fas fa-filter" style="font-size: 3rem; margin-bottom: 1rem; color: #94a3b8;"></i><div style="font-size: 1.1rem;">Aucun produit trouvé</div></div>';
        return;
    }
    
    let html = '';
    products.forEach(p => {
        let statusColor, statusBg, statusIcon, statusText, statusBorder;
        
        if (p.quantite <= 0) {
            statusColor = '#dc2626';
            statusBg = '#fef2f2';
            statusIcon = 'fa-times-circle';
            statusText = 'RUPTURE';
            statusBorder = '#fecaca';
        } else if (p.quantite <= p.seuil_alerte) {
            statusColor = '#f59e0b';
            statusBg = '#fffbeb';
            statusIcon = 'fa-exclamation-triangle';
            statusText = 'CRITIQUE';
            statusBorder = '#fde68a';
        } else {
            statusColor = '#10b981';
            statusBg = '#f0fdf4';
            statusIcon = 'fa-check-circle';
            statusText = 'NORMAL';
            statusBorder = '#bbf7d0';
        }
        
        html += `
            <div class="gb-stock-card" data-id="${p.id}" onclick="gbOpenAdjust(${p.id}); gbClose('gbStockModal');" style="
                background: white;
                border: 2px solid ${statusBorder};
                border-radius: 12px;
                padding: 1.25rem;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                position: relative;
                overflow: hidden;
            ">
                <!-- Status Badge -->
                <div style="
                    position: absolute;
                    top: 0;
                    right: 0;
                    background: ${statusBg};
                    color: ${statusColor};
                    padding: 0.375rem 0.75rem;
                    border-bottom-left-radius: 12px;
                    font-size: 0.75rem;
                    font-weight: 700;
                    letter-spacing: 0.5px;
                    display: flex;
                    align-items: center;
                    gap: 0.375rem;
                ">
                    <i class="fas ${statusIcon}"></i>
                    ${statusText}
                </div>
                
                <!-- Product Info -->
                <div style="margin-top: 0.5rem;">
                    <div style="font-weight: 700; font-size: 1.rem; color: #1e293b; margin-bottom: 0.5rem; padding-right: 80px;">
                        ${p.nom}
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                        <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; color: #64748b;">
                            ${p.reference}
                        </code>
                    </div>
                </div>
                
                <!-- Stock Info -->
                <div style="
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 0.75rem;
                    padding-top: 1rem;
                    border-top: 1px solid #e2e8f0;
                ">
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: ${statusColor};">
                            ${p.quantite}
                        </div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">Stock</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #6b7280;">
                            ${p.seuil_alerte}
                        </div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">Seuil</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #10b981;">
                            ${Number(p.prix_vente || 0).toFixed(2)}€
                        </div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">Prix</div>
                    </div>
                </div>
            </div>
        `;
    });
    
    wrap.innerHTML = html;
    
    // Ajouter les effets hover
    document.querySelectorAll('.gb-stock-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
            this.style.boxShadow = '0 8px 20px rgba(0,0,0,0.15)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.08)';
        });
    });
}

// Alerts modal (tableau moderne)
function gbOpenAlerts(){ 
    if(!GB_ALERTS || GB_ALERTS.length === 0) {
        gbShowToast('✅ Aucune alerte stock actuellement', 'success');
        return;
    }
    
    // Calculer statistiques
    let criticalCount = 0, emptyCount = 0;
    GB_ALERTS.forEach(p => {
        if (p.quantite <= 0) emptyCount++;
        else criticalCount++;
    });
    
    let alertsHtml = `
        <div class="gb-alert-modal-content" style="
            background: white;
            border-radius: 16px;
            max-width: 1000px;
            width: 100%;
            max-height: 85vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        ">
            <!-- Header -->
            <div style="
                background: linear-gradient(135deg, #ef4444, #dc2626);
                color: white;
                padding: 1.5rem 2rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            ">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="
                        width: 48px;
                        height: 48px;
                        background: rgba(255,255,255,0.2);
                        border-radius: 12px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 24px;
                    ">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Alertes Stock</h3>
                        <div style="font-size: 0.875rem; opacity: 0.9; margin-top: 0.25rem;">
                            ${GB_ALERTS.length} produit${GB_ALERTS.length > 1 ? 's' : ''} nécessite${GB_ALERTS.length > 1 ? 'nt' : ''} votre attention
                        </div>
                    </div>
                </div>
                <button onclick="gbCloseAlert()" style="
                    background: rgba(255,255,255,0.1);
                    border: none;
                    color: white;
                    width: 36px;
                    height: 36px;
                    border-radius: 8px;
                    font-size: 24px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s;
                    flex-shrink: 0;
                ">×</button>
            </div>
            
            <!-- Stats Summary -->
            <div style="
                padding: 1.5rem 2rem;
                background: #fef2f2;
                border-bottom: 2px solid #fecaca;
                display: flex;
                gap: 1.5rem;
                justify-content: center;
            ">
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; font-weight: 700; color: #dc2626;">${emptyCount}</div>
                    <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; font-weight: 600;">En rupture</div>
                </div>
                <div style="width: 1px; background: #fecaca;"></div>
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; font-weight: 700; color: #f59e0b;">${criticalCount}</div>
                    <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; font-weight: 600;">Stock critique</div>
                </div>
            </div>
            
            <!-- Table Container -->
            <div style="
                overflow-y: auto;
                flex: 1;
                padding: 1.5rem 2rem;
            ">
                <table style="
                    width: 100%;
                    border-collapse: separate;
                    border-spacing: 0;
                ">
                    <thead>
                        <tr style="
                            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
                            color: #1e293b;
                        ">
                            <th style="
                                padding: 1rem 1.25rem;
                                text-align: left;
                                font-weight: 700;
                                font-size: 0.875rem;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                                border-top-left-radius: 8px;
                            ">Produit</th>
                            <th style="
                                padding: 1rem 1.25rem;
                                text-align: left;
                                font-weight: 700;
                                font-size: 0.875rem;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            ">Référence</th>
                            <th style="
                                padding: 1rem 1.25rem;
                                text-align: center;
                                font-weight: 700;
                                font-size: 0.875rem;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            ">Stock</th>
                            <th style="
                                padding: 1rem 1.25rem;
                                text-align: center;
                                font-weight: 700;
                                font-size: 0.875rem;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            ">Seuil</th>
                            <th style="
                                padding: 1rem 1.25rem;
                                text-align: center;
                                font-weight: 700;
                                font-size: 0.875rem;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            ">Statut</th>
                            <th style="
                                padding: 1rem 1.25rem;
                                text-align: center;
                                font-weight: 700;
                                font-size: 0.875rem;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                                border-top-right-radius: 8px;
                            ">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    GB_ALERTS.forEach((p, index) => {
        const isEmpty = p.quantite <= 0;
        const statusColor = isEmpty ? '#dc2626' : '#f59e0b';
        const statusBg = isEmpty ? '#fef2f2' : '#fffbeb';
        const statusIcon = isEmpty ? 'fa-times-circle' : 'fa-exclamation-triangle';
        const statusText = isEmpty ? 'RUPTURE' : 'CRITIQUE';
        const statusBorder = isEmpty ? '#fecaca' : '#fde68a';
        
        alertsHtml += `
            <tr class="gb-alert-row" style="
                background: white;
                border-bottom: 1px solid #e2e8f0;
                transition: all 0.2s;
            ">
                <td style="
                    padding: 1.25rem;
                    font-weight: 600;
                    color: #1e293b;
                    font-size: 0.95rem;
                ">${p.nom}</td>
                <td style="padding: 1.25rem;">
                    <code style="
                        background: #f1f5f9;
                        padding: 0.375rem 0.625rem;
                        border-radius: 6px;
                        font-size: 0.8rem;
                        color: #64748b;
                        font-family: 'Courier New', monospace;
                    ">${p.reference}</code>
                </td>
                <td style="
                    padding: 1.25rem;
                    text-align: center;
                ">
                    <span style="
                        display: inline-block;
                        font-size: 1.25rem;
                        font-weight: 700;
                        color: ${statusColor};
                        min-width: 40px;
                    ">${p.quantite}</span>
                </td>
                <td style="
                    padding: 1.25rem;
                    text-align: center;
                    color: #6b7280;
                    font-weight: 600;
                    font-size: 0.95rem;
                ">${p.seuil_alerte}</td>
                <td style="padding: 1.25rem; text-align: center;">
                    <span style="
                        display: inline-flex;
                        align-items: center;
                        gap: 0.5rem;
                        background: ${statusBg};
                        color: ${statusColor};
                        padding: 0.5rem 1rem;
                        border-radius: 8px;
                        font-size: 0.75rem;
                        font-weight: 700;
                        letter-spacing: 0.5px;
                        border: 2px solid ${statusBorder};
                    ">
                        <i class="fas ${statusIcon}"></i>
                        ${statusText}
                    </span>
                </td>
                <td style="padding: 1.25rem; text-align: center;">
                    <button onclick="gbOpenAdjust(${p.id}); gbCloseAlert();" style="
                        background: linear-gradient(135deg, #3b82f6, #2563eb);
                        color: white;
                        border: none;
                        padding: 0.625rem 1.25rem;
                        border-radius: 8px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s;
                        font-size: 0.875rem;
                    ">
                        <i class="fas fa-edit"></i> Ajuster
                    </button>
                </td>
            </tr>
        `;
    });
    
    alertsHtml += `
                    </tbody>
                </table>
            </div>
            
            <!-- Footer -->
            <div style="
                padding: 1.5rem 2rem;
                background: #f8fafc;
                border-top: 2px solid #e2e8f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            ">
                <div style="color: #64748b; font-size: 0.875rem;">
                    <i class="fas fa-info-circle"></i>
                    Cliquez sur "Ajuster" pour modifier le stock d'un produit
                </div>
                <button onclick="gbCloseAlert()" style="
                    background: #6b7280;
                    color: white;
                    border: none;
                    padding: 0.75rem 1.5rem;
                    border-radius: 8px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s;
                ">
                    <i class="fas fa-times"></i>
                    Fermer
                </button>
            </div>
        </div>
    `;
    
    const alertDiv = document.createElement('div');
    alertDiv.id = 'gbAlertOverlay';
    alertDiv.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        animation: fadeIn 0.3s ease;
    `;
    alertDiv.innerHTML = alertsHtml;
    
    document.body.appendChild(alertDiv);
    document.body.style.overflow = 'hidden';
    
    // Ajouter hover effects sur les lignes et boutons
    setTimeout(() => {
        document.querySelectorAll('.gb-alert-row').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.background = '#f8fafc';
                this.style.transform = 'scale(1.01)';
                this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
            });
            row.addEventListener('mouseleave', function() {
                this.style.background = 'white';
                this.style.transform = 'scale(1)';
                this.style.boxShadow = 'none';
            });
        });
        
        document.querySelectorAll('.gb-alert-row button').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 6px 16px rgba(59, 130, 246, 0.4)';
            });
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    }, 100);
}

function gbCloseAlert() {
    const overlay = document.getElementById('gbAlertOverlay');
    if(overlay) {
        overlay.remove();
        document.body.style.overflow = 'auto';
    }
}

// === RÉAPPROVISIONNEMENT AUTOMATIQUE ===
function gbAutoReorder() {
    // Confirmation
    if (!confirm('🔄 Générer automatiquement les bons de commande pour les produits en stock faible?\n\nCette action vérifiera tous les produits suivis et créera/mettra à jour les commandes nécessaires.')) {
        return;
    }
    
    // Afficher loading
    gbShowToast('⏳ Analyse du stock et génération des commandes...', 'info');
    
    fetch('ajax/auto_reorder.php', {
        method: 'POST',
        credentials: 'include'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Créer modal de rapport détaillé
            let reportHtml = `
                <div style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.6);
                    backdrop-filter: blur(8px);
                    z-index: 99999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 1rem;
                    animation: fadeIn 0.3s ease;
                " id="gbReorderReport">
                    <div style="
                        background: white;
                        border-radius: 16px;
                        max-width: 700px;
                        max-height: 85vh;
                        overflow: hidden;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    ">
                        <!-- Header -->
                        <div style="
                            background: linear-gradient(135deg, #10b981, #059669);
                            color: white;
                            padding: 1.5rem 2rem;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                        ">
                            <div>
                                <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">
                                    ✅ Réapprovisionnement Terminé
                                </h3>
                                <div style="font-size: 0.875rem; opacity: 0.9; margin-top: 0.25rem;">
                                    ${data.total_products} produit(s) analysé(s)
                                </div>
                            </div>
                            <button onclick="document.getElementById('gbReorderReport').remove(); document.body.style.overflow = 'auto';" style="
                                background: rgba(255,255,255,0.1);
                                border: none;
                                color: white;
                                width: 36px;
                                height: 36px;
                                border-radius: 8px;
                                font-size: 24px;
                                cursor: pointer;
                            ">×</button>
                        </div>
                        
                        <!-- Stats -->
                        <div style="padding: 1.5rem 2rem; background: #f0fdf4; border-bottom: 2px solid #bbf7d0; display: flex; gap: 1rem; justify-content: center;">
                            <div style="text-align: center;">
                                <div style="font-size: 2.5rem; font-weight: 700; color: #16a34a;">${data.created}</div>
                                <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; font-weight: 600;">Créées</div>
                            </div>
                            <div style="width: 1px; background: #bbf7d0;"></div>
                            <div style="text-align: center;">
                                <div style="font-size: 2.5rem; font-weight: 700; color: #0891b2;">${data.updated}</div>
                                <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; font-weight: 600;">Mises à jour</div>
                            </div>
            `;
            
            if (data.skipped > 0) {
                reportHtml += `
                    <div style="width: 1px; background: #bbf7d0;"></div>
                    <div style="text-align: center;">
                        <div style="font-size: 2.5rem; font-weight: 700; color: #f59e0b;">${data.skipped}</div>
                        <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; font-weight: 600;">Ignorés</div>
                    </div>
                `;
            }
            
            reportHtml += `
                        </div>
                        
                        <!-- Details -->
                        <div style="padding: 1.5rem 2rem; overflow-y: auto; max-height: 400px;">
            `;
            
            if (data.processed_products && data.processed_products.length > 0) {
                reportHtml += '<h4 style="margin: 0 0 1rem; color: #1e293b;">Produits traités:</h4>';
                data.processed_products.forEach(product => {
                    const actionColor = product.action === 'created' ? '#16a34a' : '#0891b2';
                    const actionText = product.action === 'created' ? 'CRÉÉE' : 'MISE À JOUR';
                    const actionIcon = product.action === 'created' ? 'fa-plus-circle' : 'fa-edit';
                    
                    reportHtml += `
                        <div style="
                            padding: 1rem;
                            margin-bottom: 0.75rem;
                            background: #f8fafc;
                            border-left: 4px solid ${actionColor};
                            border-radius: 8px;
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                <div style="font-weight: 600; color: #1e293b;">${product.nom}</div>
                                <span style="
                                    background: ${actionColor};
                                    color: white;
                                    padding: 0.25rem 0.5rem;
                                    border-radius: 6px;
                                    font-size: 0.75rem;
                                    font-weight: 700;
                                ">
                                    <i class="fas ${actionIcon}"></i> ${actionText}
                                </span>
                            </div>
                            <div style="font-size: 0.875rem; color: #64748b;">
                                <div><strong>Référence:</strong> ${product.reference}</div>
                                <div><strong>Quantité:</strong> ${product.quantite} unité(s)</div>
                                <div><strong>Fournisseur:</strong> ${product.fournisseur}</div>
                    `;
                    
                    if (product.ancienne_quantite !== undefined) {
                        reportHtml += `<div><strong>Ajustement:</strong> ${product.ancienne_quantite} → ${product.nouvelle_quantite} unités</div>`;
                    }
                    
                    reportHtml += `
                            </div>
                        </div>
                    `;
                });
            } else {
                reportHtml += '<p style="text-align: center; color: #64748b; padding: 2rem;">Aucune commande nécessaire.</p>';
            }
            
            reportHtml += `
                        </div>
                        
                        <!-- Footer -->
                        <div style="padding: 1.5rem 2rem; background: #f8fafc; border-top: 2px solid #e2e8f0; text-align: right;">
                            <button onclick="window.location.href='index.php?page=commandes_pieces'" style="
                                background: linear-gradient(135deg, #3b82f6, #2563eb);
                                color: white;
                                border: none;
                                padding: 0.75rem 1.5rem;
                                border-radius: 8px;
                                font-weight: 600;
                                cursor: pointer;
                                margin-right: 0.5rem;
                            ">
                                <i class="fas fa-list"></i> Voir les commandes
                            </button>
                            <button onclick="document.getElementById('gbReorderReport').remove(); document.body.style.overflow = 'auto';" style="
                                background: #6b7280;
                                color: white;
                                border: none;
                                padding: 0.75rem 1.5rem;
                                border-radius: 8px;
                                font-weight: 600;
                                cursor: pointer;
                            ">
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Insérer le modal
            document.body.insertAdjacentHTML('beforeend', reportHtml);
            document.body.style.overflow = 'hidden';
            
            gbShowToast('✅ ' + data.message, 'success');
            
            // Rafraîchir les alertes si la fonction existe
            if (typeof gbLoadAlerts === 'function') {
                setTimeout(() => gbLoadAlerts(), 2000);
            }
            
        } else {
            gbShowToast('❌ ' + data.message, 'error');
        }
    })
    .catch(err => {
        console.error('Erreur auto-reorder:', err);
        gbShowToast('❌ Erreur de connexion', 'error');
    });
}


// === HISTORIQUE DES MOUVEMENTS ===
function gbOpenStockHistory(produitId = null) {
    console.log('📊 Ouverture historique mouvements', produitId);
    
    // Ouvrir le modal
    gbOpen('gbHistoryModal');
    
    // Afficher les filtres et loading
    document.getElementById('gb_history_filters').style.display = 'block';
    document.getElementById('gb_history_loading').style.display = 'block';
    document.getElementById('gb_history_content').style.display = 'none';
    
    // Initialiser les dates par défaut (ce mois)
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    document.getElementById('gb_history_date_start').value = firstDay.toISOString().split('T')[0];
    document.getElementById('gb_history_date_end').value = lastDay.toISOString().split('T')[0];
    
    // Activer le bouton "Ce mois" par défaut
    setTimeout(() => {
        document.querySelectorAll('.gb-period-btn').forEach(btn => {
            if (btn.dataset.period === 'current_month') {
                btn.style.background = 'linear-gradient(135deg, #8b5cf6, #7c3aed)';
                btn.style.borderColor = '#8b5cf6';
                btn.style.color = 'white';
            } else {
                btn.style.background = 'white';
                btn.style.borderColor = '#e2e8f0';
                btn.style.color = '#475569';
            }
        });
    }, 100);
    
    // Charger avec le filtre par défaut
    gbLoadStockHistory(produitId);
    
    // Event listeners pour les filtres (les ajouter une seule fois)
    if (!window.gbHistoryFiltersInitialized) {
        window.gbHistoryFiltersInitialized = true;
        
        // Gestion des boutons de période
        document.querySelectorAll('.gb-period-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const period = this.dataset.period;
                const customDates = document.getElementById('gb_history_custom_dates');
                const hiddenPeriod = document.getElementById('gb_history_period');
                
                // Mettre à jour le champ caché
                hiddenPeriod.value = period;
                
                // Retirer l'état actif de tous les boutons
                document.querySelectorAll('.gb-period-btn').forEach(b => {
                    b.style.background = 'white';
                    b.style.borderColor = '#e2e8f0';
                    b.style.color = '#475569';
                });
                
                // Activer le bouton sélectionné
                this.style.background = 'linear-gradient(135deg, #8b5cf6, #7c3aed)';
                this.style.borderColor = '#8b5cf6';
                this.style.color = 'white';
                
                if (period === 'custom') {
                    customDates.style.display = 'block';
                } else {
                    customDates.style.display = 'none';
                    
                    // Calculer les dates selon la période
                    const today = new Date();
                    let startDate, endDate;
                    
                    switch(period) {
                        case 'current_month':
                            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                            break;
                        case 'last_month':
                            startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                            break;
                        case 'quarter':
                            const quarter = Math.floor(today.getMonth() / 3);
                            startDate = new Date(today.getFullYear(), quarter * 3, 1);
                            endDate = new Date(today.getFullYear(), quarter * 3 + 3, 0);
                            break;
                        case 'all':
                        default:
                            startDate = null;
                            endDate = null;
                    }
                    
                    if (startDate && endDate) {
                        document.getElementById('gb_history_date_start').value = startDate.toISOString().split('T')[0];
                        document.getElementById('gb_history_date_end').value = endDate.toISOString().split('T')[0];
                    }
                    
                    // Charger automatiquement les données
                    gbLoadStockHistory(window.gbCurrentHistoryProductId);
                }
            });
        });
        
        // Bouton Appliquer (pour période personnalisée)
        document.getElementById('gb_history_apply_custom').addEventListener('click', function() {
            gbLoadStockHistory(window.gbCurrentHistoryProductId);
        });
    }
    
    // Stocker le produitId pour les filtres
    window.gbCurrentHistoryProductId = produitId;
}

// Fonction pour charger les données avec filtres
function gbLoadStockHistory(produitId = null) {
    // Afficher loading
    document.getElementById('gb_history_loading').style.display = 'block';
    document.getElementById('gb_history_content').style.display = 'none';
    
    // Construire URL avec filtres
    let url = 'ajax/get_mouvements_stock.php?limit=100';
    if (produitId) {
        url += `&produit_id=${produitId}`;
    }
    
    // Ajouter filtres de date si ce n'est pas "all"
    const period = document.getElementById('gb_history_period').value;
    if (period !== 'all') {
        const dateStart = document.getElementById('gb_history_date_start').value;
        const dateEnd = document.getElementById('gb_history_date_end').value;
        
        if (dateStart) {
            url += `&date_start=${dateStart}`;
        }
        if (dateEnd) {
            url += `&date_end=${dateEnd}`;
        }
    }
    
    // Charger les données
    fetch(url, { credentials: 'include' })
        .then(res => res.json())
        .then(data => {
            console.log('📦 Données reçues:', data);
            
            document.getElementById('gb_history_loading').style.display = 'none';
            document.getElementById('gb_history_content').style.display = 'block';
            
            if (!data.success) {
                throw new Error(data.message || 'Erreur inconnue');
            }
            
            const tbody = document.getElementById('gb_history_tbody');
            const emptyDiv = document.getElementById('gb_history_empty');
            
            if (!data.mouvements || data.mouvements.length === 0) {
                tbody.innerHTML = '';
                emptyDiv.style.display = 'block';
                return;
            }
            
            emptyDiv.style.display = 'none';
            
            // Construire le tableau
            let html = '';
            data.mouvements.forEach(mvt => {
                html += '<tr>';
                
                // Date
                html += `<td><div class="gb-history-date">${mvt.date_formattee}</div></td>`;
                
                // Produit
                html += `<td>
                    <div class="gb-history-product">${mvt.produit_nom || 'Produit supprimé'}</div>
                    <div class="gb-history-ref">${mvt.produit_ref || 'N/A'}</div>
                </td>`;
                
                // Quantité
                html += `<td>
                    <div class="gb-history-qty" style="color: ${mvt.quantite_color};">
                        ${mvt.quantite_display}
                    </div>
                </td>`;
                
                // Type
                html += `<td>
                    <div class="gb-history-type" style="background: rgba(${hexToRgb(mvt.color)}, 0.1); color: ${mvt.color};">
                        <i class="fas ${mvt.icon}"></i>
                        ${mvt.type_affichage}
                    </div>
                </td>`;
                
                // Motif
                let motifHtml = mvt.motif || 'Aucun motif';
                if (mvt.reparation_id) {
                    motifHtml = motifHtml.replace(`#${mvt.reparation_id}`, 
                        `<a href="index.php?page=reparations&showRepId=${mvt.reparation_id}" target="_blank" class="gb-history-motif-link">#${mvt.reparation_id}</a>`
                    );
                }
                html += `<td><div style="color: #64748b; font-size: 0.9rem;">${motifHtml}</div></td>`;
                
                // Utilisateur
                html += `<td>
                    <div class="gb-history-user">
                        <i class="fas fa-user"></i>
                        ${mvt.user_display}
                    </div>
                </td>`;
                
                html += '</tr>';
            });
            
            tbody.innerHTML = html;
        })
        .catch(err => {
            console.error('❌ Erreur chargement historique:', err);
            document.getElementById('gb_history_loading').style.display = 'none';
            document.getElementById('gb_history_content').style.display = 'block';
            document.getElementById('gb_history_empty').innerHTML = `
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem; display: block;"></i>
                <div style="font-size: 1.1rem; font-weight: 600; color: #ef4444;">Erreur</div>
                <div style="margin-top: 0.5rem; color: #64748b;">${err.message}</div>
            `;
            document.getElementById('gb_history_empty').style.display = 'block';
        });
}

// Helper function
function hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? 
        `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : 
        '0, 0, 0';
}


// Édition produit
function gbEdit(id) {
    const row = document.querySelector(`#gbTable tbody tr[data-id="${id}"]`);
    if (!row) {
        gbShowToast('❌ Produit non trouvé', 'error');
        return;
    }
    
    const cells = row.querySelectorAll('td');
    const reference = cells[0].querySelector('code').textContent.trim();
    const nom = cells[1].querySelector('div').textContent.trim();
    const prixVente = cells[4].querySelector('strong').textContent.replace('€', '').trim();
    const quantite = cells[5].querySelector('strong').textContent.trim();
    
    // Créer un modal d'édition simple
    let editHtml = '<div style="background: white; padding: 2rem; border-radius: 15px; max-width: 500px; width: 100%;">';
    editHtml += '<h3 style="margin: 0 0 1.5rem; color: var(--day-text);">Modifier le Produit</h3>';
    editHtml += '<form id="editForm">';
    editHtml += `<div style="margin-bottom: 1rem;"><label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Référence</label><input type="text" id="edit_ref" value="${reference}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px;"></div>`;
    editHtml += `<div style="margin-bottom: 1rem;"><label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Nom</label><input type="text" id="edit_nom" value="${nom}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px;"></div>`;
    editHtml += `<div style="margin-bottom: 1rem;"><label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Prix de vente</label><input type="number" step="0.01" id="edit_prix" value="${prixVente}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px;"></div>`;
    editHtml += `<div style="margin-bottom: 1rem;"><label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Quantité</label><input type="number" id="edit_qty" value="${quantite}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px;"></div>`;
    editHtml += '<div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">';
    editHtml += '<button type="button" onclick="gbCloseEdit()" style="background: #6b7280; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; cursor: pointer;">Annuler</button>';
    editHtml += `<button type="button" onclick="gbSaveEdit(${id})" style="background: #3b82f6; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; cursor: pointer;">Enregistrer</button>`;
    editHtml += '</div></form></div>';
    
    const editDiv = document.createElement('div');
    editDiv.id = 'gbEditOverlay';
    editDiv.style.cssText = `
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);
        z-index: 99999; display: flex; align-items: center; justify-content: center;
        padding: 1rem; animation: fadeIn 0.3s ease;
    `;
    editDiv.innerHTML = editHtml;
    
    document.body.appendChild(editDiv);
    document.body.style.overflow = 'hidden';
}

function gbCloseEdit() {
    const overlay = document.getElementById('gbEditOverlay');
    if(overlay) {
        overlay.remove();
        document.body.style.overflow = 'auto';
    }
}

function gbSaveEdit(id) {
    const reference = document.getElementById('edit_ref').value;
    const nom = document.getElementById('edit_nom').value;
    const prix = document.getElementById('edit_prix').value;
    const qty = document.getElementById('edit_qty').value;
    
    if(!reference || !nom || !prix || !qty) {
        gbShowToast('❌ Tous les champs sont obligatoires', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('reference', reference);
    formData.append('nom', nom);
    formData.append('prix_vente', prix);
    formData.append('quantite', qty);
    
    fetch('ajax/modifier_produit_simple.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            gbShowToast('✅ Produit modifié avec succès', 'success');
            gbCloseEdit();
            setTimeout(() => location.reload(), 1000);
        } else {
            gbShowToast('❌ Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        gbShowToast('❌ Erreur de connexion', 'error');
    });
}

// Suppression produit
function gbDelete(id){
    if(!confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('ajax/supprimer_produit.php', { 
        method:'POST', 
        body: formData 
    })
    .then(r=>r.json())
    .then(d=>{
        if(d.success){
            const tr = document.querySelector(`#gbTable tbody tr[data-id="${id}"]`);
            if(tr){ 
                tr.style.transform = 'scale(0.8)';
                tr.style.opacity = '0';
                setTimeout(() => tr.remove(), 300);
            }
            gbShowToast('✅ Produit supprimé', 'success');
        }else{
            gbShowToast('❌ Erreur: '+(d.message||'Suppression impossible'), 'error');
        }
    })
    .catch(()=>gbShowToast('❌ Erreur de connexion', 'error'));
}

// Fermeture des modals en cliquant en dehors
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modern-modal')) {
        const modal = e.target;
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
});

// Animations des particules (optionnel)
function createParticles() {
    const container = document.getElementById('particles');
    if (!container) return;
    
    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.style.cssText = `
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(59, 130, 246, 0.3);
            border-radius: 50%;
            pointer-events: none;
            animation: float ${Math.random() * 3 + 2}s ease-in-out infinite;
        `;
        
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 2 + 's';
        
        container.appendChild(particle);
    }
}

// Style pour l'animation des particules
const style = document.createElement('style');
style.textContent = `
    .particles-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
        overflow: hidden;
    }
    
    @keyframes float {
        0%, 100% { 
            transform: translateY(0px) rotate(0deg);
            opacity: 0.3;
        }
        50% { 
            transform: translateY(-20px) rotate(180deg);
            opacity: 0.7;
        }
    }
`;
document.head.appendChild(style);


// Auto-open modal from URL params
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    const code = urlParams.get('code');
    
    if (action === 'add' && code) {
        // Open the modal
        if (typeof gbOpen === 'function') {
            gbOpen('gbAddModal');
        } else {
            const modal = document.getElementById('gbAddModal');
            if (modal) modal.classList.add('show');
        }
        
        // Fill the reference
        const refInput = document.querySelector('#gbAddModal input[name="reference"]');
        if (refInput) {
            refInput.value = code;
            // Optional: Focus on name field
            const nameInput = document.querySelector('#gbAddModal input[name="nom"]');
            if (nameInput) setTimeout(() => nameInput.focus(), 500);
        }
        
        if (typeof gbShowToast === 'function') {
            gbShowToast('📦 Code scanné: ' + code, 'success');
        }
    }
});

</script>
