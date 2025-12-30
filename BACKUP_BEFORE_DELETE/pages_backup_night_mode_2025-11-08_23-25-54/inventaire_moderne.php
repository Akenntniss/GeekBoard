<?php
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
            <button class="modern-btn" onclick="gbOpen('gbAddModal')">
                <i class="fas fa-plus"></i>
                Nouveau produit
            </button>
            <button class="modern-btn modern-btn--warning" onclick="gbOpenAlerts()">
                <i class="fas fa-triangle-exclamation"></i>
                Alertes stock
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
                    <tr data-ref="<?php echo strtolower($p['reference']); ?>" data-name="<?php echo strtolower($p['nom']); ?>" data-qty="<?php echo (int)$p['quantite']; ?>" data-th="<?php echo (int)$p['seuil_alerte']; ?>" data-tracked="<?php echo ($gb_has_suivre_stock && isset($p['suivre_stock']) && (int)$p['suivre_stock']===1) ? '1' : '0'; ?>" data-id="<?php echo (int)$p['id']; ?>">
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
<div class="modern-modal" id="gbStockModal">
    <div class="modern-modal-dialog" style="max-width: 800px;">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #06b6d4, #0891b2); color: white; border-radius: 20px 20px 0 0;">
            <h3 class="modern-modal-title" style="color: white; margin-bottom: 0;">
                <i class="fas fa-eye"></i>
                Produits Suivis
            </h3>
        </div>
        <div class="modern-modal-body">
            <div class="modern-search" style="margin-bottom: 1.5rem;">
                <i class="fas fa-search"></i>
                <input id="gbStockSearch" placeholder="Rechercher dans les produits suivis..." />
            </div>
            <div id="gbStockCards" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; max-height: 400px; overflow-y: auto;">
                <div style="text-align:center; padding: 2rem; color: var(--day-text-light); grid-column: 1 / -1;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <div>Chargement des produits suivis...</div>
                </div>
            </div>
            <div class="modern-form-actions">
                <button class="modern-btn" style="background: #6b7280; color: white;" onclick="gbClose('gbStockModal')">
                    <i class="fas fa-times"></i>
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

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

// Utils Modals
function gbOpen(id){ 
    document.getElementById(id).classList.add('show'); 
    document.body.style.overflow = 'hidden';
}

function gbClose(id){ 
    document.getElementById(id).classList.remove('show'); 
    document.body.style.overflow = 'auto';
}

// Ajustement de stock
function gbOpenAdjust(id){
    const tr = document.querySelector(`#gbTable tbody tr[data-id="${id}"]`);
    if(!tr) return;
    
    const productName = tr.querySelector('td:nth-child(2) div').textContent;
    const productRef = tr.querySelector('code').textContent;
    const currentQty = parseInt(tr.dataset.qty) || 0;
    
    gbCurrentQuantity = currentQty;
    gbOriginalQuantity = currentQty;
    
    document.getElementById('gb_adjust_id').value = id;
    document.getElementById('gb_adjust_name').textContent = productName;
    document.getElementById('gb_adjust_ref').textContent = productRef;
    document.getElementById('gb_adjust_current').textContent = currentQty;
    document.getElementById('gb_adjust_original').value = currentQty;
    document.getElementById('gb_adjust_new').value = currentQty;
    
    gbOpen('gbAdjustModal');
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
    
    const formData = new FormData();
    formData.append('produit_id', produitId);
    formData.append('nouvelle_quantite', nouvelleQuantite);
    
    fetch('ajax/ajuster_stock_minimal.php', {
        method: 'POST',
        body: formData
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
            gbShowToast('✅ Stock mis à jour: ' + data.nouvelle_quantite, 'success');
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
        
        // Configuration Quagga simplifiée
        const config = {
            inputStream: {
                type: 'LiveStream',
                constraints: {
                    width: 640,
                    height: 480,
                    facingMode: 'environment'
                }
            },
            locator: {
                patchSize: "large",
                halfSample: false
            },
            numOfWorkers: 2,
            frequency: 10,
            decoder: {
                readers: [
                    "code_128_reader",
                    "ean_reader", 
                    "ean_8_reader",
                    "code_39_reader",
                    "codabar_reader"
                ]
            },
            locate: true,
            debug: false
        };
        
        if(typeof Quagga !== 'undefined') {
            Quagga.init(config, err => {
                if(err){ 
                    gbShowToast('❌ Erreur scanner: ' + (err.message || err), 'error');
                    return;
                } 
                Quagga.start(); 
            });
            
            Quagga.onDetected(res => {
                if(!res || !res.codeResult || !res.codeResult.code) return;
                
                const code = res.codeResult.code.trim();
                const currentTime = Date.now();
                
                if(code.length < 3 || gbIsProcessing || (currentTime - gbLastProcessTime) < 1500) {
                    return;
                }
                
                gbIsProcessing = true;
                gbLastProcessTime = currentTime;
                
                gbShowToast(`📦 Code détecté: ${code}`, 'success');
                gbCheckCode(code);
                
                setTimeout(() => {
                    gbIsProcessing = false;
                }, 3000);
            });
        }
    }).catch(err => { 
        gbShowToast('❌ Impossible d\'accéder à la caméra: ' + err.message, 'error'); 
    });
}

function gbStopCam(){ 
    if(gbStream){ 
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
            gbCloseScanner(); 
            gbOpenAdjust(d.id);
        } else { 
            gbShowToast('❌ Produit non trouvé: '+code, 'warning'); 
        } 
    })
    .catch(()=>{ 
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
    wrap.innerHTML = '<div style="text-align:center; padding: 2rem; color: var(--day-text-light); grid-column: 1 / -1;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i><div>Chargement des produits suivis...</div></div>';
    
    fetch('ajax/get_tracked_products.php').then(r=>r.json()).then(d=>{
        if(!d.success){ 
            wrap.innerHTML = `<div style="text-align:center; padding: 2rem; color: var(--day-text-light); grid-column: 1 / -1;"><i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; color: #ef4444;"></i><div>${d.error||'Erreur de chargement'}</div></div>`;
            return; 
        }
        
        if(!d.products || d.products.length===0){ 
            wrap.innerHTML = '<div style="text-align:center; padding: 2rem; color: var(--day-text-light); grid-column: 1 / -1;"><i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 1rem; color: #06b6d4;"></i><div>Aucun produit suivi.<br/>Cochez "Suivre ce produit" lors de l\'ajout.</div></div>';
            return; 
        }
        
        // Afficher les produits suivis
        let html = '';
        d.products.forEach(p=>{
            const badgeClass = (p.quantite<=0) ? 'modern-badge--danger' : (p.quantite<=p.seuil_alerte) ? 'modern-badge--warning' : 'modern-badge--success';
            const badgeIcon = (p.quantite<=0) ? 'fas fa-times-circle' : (p.quantite<=p.seuil_alerte) ? 'fas fa-exclamation-triangle' : 'fas fa-check-circle';
            
            html += `<div class="stock-card-modern" data-id="${p.id}" onclick="gbOpenAdjust(${p.id}); gbClose('gbStockModal');" style="
                background: white; 
                border: 2px solid var(--day-border); 
                border-radius: 15px; 
                padding: 1.5rem; 
                cursor: pointer; 
                transition: all 0.3s ease;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            ">
                <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: var(--day-text); margin-bottom: 0.5rem;">${p.nom}</div>
                        <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; color: var(--day-text-light);">${p.reference}</code>
                    </div>
                    <span class="modern-badge ${badgeClass}">
                        <i class="${badgeIcon}"></i> ${p.quantite}
                    </span>
                </div>
                <div style="color: var(--day-text-light); font-size: 0.875rem; display: flex; justify-content: space-between;">
                    <span>Seuil: ${p.seuil_alerte}</span>
                    <span>Prix: ${Number(p.prix_vente||0).toFixed(2)}€</span>
                </div>
            </div>`;
        });
        
        wrap.innerHTML = html;
        
        // Ajouter l'effet hover via JavaScript
        document.querySelectorAll('.stock-card-modern').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
                this.style.borderColor = 'var(--day-primary)';
                this.style.boxShadow = '0 8px 25px rgba(59, 130, 246, 0.15)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.borderColor = 'var(--day-border)';
                this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
            });
        });
        
        gbShowToast('✅ Produits suivis chargés', 'success');
    }).catch(()=>{ 
        wrap.innerHTML = '<div style="text-align:center; padding: 2rem; color: var(--day-text-light); grid-column: 1 / -1;"><i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; color: #ef4444;"></i><div>Erreur de connexion</div></div>';
    });
}

// Alerts modal
function gbOpenAlerts(){ 
    if(!GB_ALERTS || GB_ALERTS.length === 0) {
        gbShowToast('✅ Aucune alerte stock actuellement', 'success');
        return;
    }
    
    let alertsHtml = '<div style="background: white; padding: 2rem; border-radius: 15px; max-width: 800px; max-height: 80vh; overflow-y: auto;">';
    alertsHtml += '<h3 style="margin: 0 0 1.5rem; color: var(--day-text); display: flex; align-items: center; gap: 0.75rem;"><i class="fas fa-triangle-exclamation" style="color: #f59e0b;"></i>Alertes Stock</h3>';
    
    alertsHtml += '<div style="overflow-x: auto;">';
    alertsHtml += '<table style="width: 100%; border-collapse: collapse;">';
    alertsHtml += '<thead><tr style="background: #f8fafc;">';
    alertsHtml += '<th style="padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0;">Produit</th>';
    alertsHtml += '<th style="padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0;">Référence</th>';
    alertsHtml += '<th style="padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0;">Stock</th>';
    alertsHtml += '<th style="padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0;">Seuil</th>';
    alertsHtml += '<th style="padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0;">Actions</th>';
    alertsHtml += '</tr></thead><tbody>';
    
    GB_ALERTS.forEach(p => {
        alertsHtml += '<tr style="border-bottom: 1px solid #f1f5f9;">';
        alertsHtml += `<td style="padding: 1rem;">${p.nom}</td>`;
        alertsHtml += `<td style="padding: 1rem;"><code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px;">${p.reference}</code></td>`;
        alertsHtml += `<td style="padding: 1rem;"><span style="color: #ef4444; font-weight: bold;">${p.quantite}</span></td>`;
        alertsHtml += `<td style="padding: 1rem;">${p.seuil_alerte}</td>`;
        alertsHtml += `<td style="padding: 1rem;"><button onclick="gbOpenAdjust(${p.id}); gbCloseAlert();" style="background: #3b82f6; color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer;">Ajuster</button></td>`;
        alertsHtml += '</tr>';
    });
    
    alertsHtml += '</tbody></table></div>';
    alertsHtml += '<div style="margin-top: 1.5rem; text-align: right;"><button onclick="gbCloseAlert()" style="background: #6b7280; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; cursor: pointer;">Fermer</button></div>';
    alertsHtml += '</div>';
    
    const alertDiv = document.createElement('div');
    alertDiv.id = 'gbAlertOverlay';
    alertDiv.style.cssText = `
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);
        z-index: 99999; display: flex; align-items: center; justify-content: center;
        padding: 1rem; animation: fadeIn 0.3s ease;
    `;
    alertDiv.innerHTML = alertsHtml;
    
    document.body.appendChild(alertDiv);
    document.body.style.overflow = 'hidden';
}

function gbCloseAlert() {
    const overlay = document.getElementById('gbAlertOverlay');
    if(overlay) {
        overlay.remove();
        document.body.style.overflow = 'auto';
    }
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

// Fonction de détection automatique du mode nuit
function detectAndApplyDarkMode() {
    // Détecter si l'utilisateur préfère le mode sombre
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Vérifier s'il y a une préférence stockée en localStorage
    const storedTheme = localStorage.getItem('theme');
    
    // Appliquer le thème
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.body.classList.add('night-mode');
        console.log('Mode nuit activé');
    } else {
        document.body.classList.remove('night-mode');
        console.log('Mode jour activé');
    }
}

// Écouter les changements de préférence système
if (window.matchMedia) {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addListener(function(e) {
        // Si aucune préférence n'est stockée, suivre les préférences système
        if (localStorage.getItem('theme') === null) {
            if (e.matches) {
                document.body.classList.add('night-mode');
                console.log('Passage automatique en mode nuit');
            } else {
                document.body.classList.remove('night-mode');
                console.log('Passage automatique en mode jour');
            }
        }
    });
}

// Fonction pour basculer manuellement le mode (si vous voulez ajouter un bouton plus tard)
function toggleDarkMode() {
    document.body.classList.toggle('night-mode');
    const isDark = document.body.classList.contains('night-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    console.log('Mode basculé vers:', isDark ? 'nuit' : 'jour');
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Détecter et appliquer le mode nuit dès le chargement
    detectAndApplyDarkMode();
    
    createParticles();
    
    // Vérifier si on doit ouvrir le modal d'ajout avec un code pré-rempli
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('add_product') === '1' && urlParams.get('reference')) {
        const reference = urlParams.get('reference');
        setTimeout(() => {
            gbOpen('gbAddModal');
            setTimeout(() => {
                const referenceField = document.querySelector('input[name="reference"]');
                if (referenceField) {
                    referenceField.value = reference;
                    referenceField.focus();
                }
            }, 300);
        }, 1000);
        
        // Nettoyer l'URL
        const cleanUrl = window.location.pathname + '?page=inventaire_moderne';
        window.history.replaceState({}, document.title, cleanUrl);
    }
    
    console.log('Inventaire moderne initialisé avec détection automatique du mode nuit');
});
</script>
