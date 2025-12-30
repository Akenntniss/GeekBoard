<?php
include_once 'includes/night-mode-system.php';
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Récupérer les données
try {
    $shop_pdo = getShopDBConnection();
    
    // Récupérer la liste des fournisseurs
    $stmtFournisseurs = $shop_pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
    $fournisseurs = $stmtFournisseurs->fetchAll();
    
    // Statistiques globales
    $stmtStats = $shop_pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(DISTINCT fournisseur_id) as nb_fournisseurs,
            COUNT(DISTINCT type) as nb_types,
            COUNT(DISTINCT brand) as nb_marques,
            SUM(CASE WHEN stock LIKE '%En stock%' THEN 1 ELSE 0 END) as en_stock,
            SUM(CASE WHEN stock LIKE '%Rupture%' THEN 1 ELSE 0 END) as rupture
        FROM catalogue_fournisseur
    ");
    $stats = $stmtStats->fetch();
    
    // Types uniques
    $stmtTypes = $shop_pdo->query("SELECT DISTINCT type FROM catalogue_fournisseur WHERE type IS NOT NULL AND type != '' ORDER BY type");
    $types = $stmtTypes->fetchAll(PDO::FETCH_COLUMN);
    
    // Marques uniques (top 50)
    $stmtBrands = $shop_pdo->query("SELECT DISTINCT brand FROM catalogue_fournisseur WHERE brand IS NOT NULL AND brand != '' ORDER BY brand LIMIT 50");
    $brands = $stmtBrands->fetchAll(PDO::FETCH_COLUMN);
    
    // Types d'appareils
    $stmtDeviceTypes = $shop_pdo->query("SELECT DISTINCT device_type FROM catalogue_fournisseur WHERE device_type IS NOT NULL AND device_type != '' ORDER BY device_type");
    $deviceTypes = $stmtDeviceTypes->fetchAll(PDO::FETCH_COLUMN);
    
    // Modèles uniques (top 100)
    $stmtModels = $shop_pdo->query("SELECT DISTINCT model FROM catalogue_fournisseur WHERE model IS NOT NULL AND model != '' ORDER BY model LIMIT 100");
    $models = $stmtModels->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $fournisseurs = [];
    $stats = ['total' => 0, 'nb_fournisseurs' => 0, 'nb_types' => 0, 'nb_marques' => 0, 'en_stock' => 0, 'rupture' => 0];
    $types = [];
    $brands = [];
    $deviceTypes = [];
    $models = [];
}
?>

<style>
/* =========================================
   DESIGN SYSTEM 2026 - CATALOGUE
   ========================================= */

:root {
    /* --- Mode Jour (Minimalist / Apple / Stripe) --- */
    --k-bg: #ffffff;
    --k-bg-subtle: #f8fafc;
    --k-card-bg: #ffffff;
    --k-text: #0f172a;
    --k-text-light: #64748b;
    --k-border: #e2e8f0;
    --k-primary: #000000; /* Minimaliste : noir pur pour actions principales */
    --k-primary-hover: #333333;
    --k-accent: #3b82f6; /* Bleu subtil pour liens/focus */
    --k-success: #10b981;
    --k-danger: #ef4444;
    --k-shadow: 0 1px 3px rgba(0,0,0,0.05);
    --k-shadow-hover: 0 10px 30px -10px rgba(0,0,0,0.1);
    --k-radius: 12px;
    --k-font: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    
    /* Gradients (subtles) */
    --k-grad-surface: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.dark-mode {
    /* --- Mode Nuit (Futurist / Cyberpunk / Neon) --- */
    --k-bg: #030712; /* Très noir, légèrement bleu */
    --k-bg-subtle: #0f172a;
    --k-card-bg: rgba(17, 24, 39, 0.7); /* Glassmorphism base */
    --k-text: #f8fafc;
    --k-text-light: #94a3b8;
    --k-border: rgba(56, 189, 248, 0.2); /* Cyan subtil */
    --k-primary: #38bdf8; /* Cyan Néon */
    --k-primary-hover: #0ea5e9;
    --k-accent: #8b5cf6; /* Violet Néon */
    --k-success: #2dd4bf; /* Teal Néon */
    --k-danger: #f87171; /* Rouge Néon */
    --k-shadow: 0 0 20px rgba(56, 189, 248, 0.1);
    --k-shadow-hover: 0 0 30px rgba(56, 189, 248, 0.25);
    
    /* Effets Spéciaux Dark Mode */
    --k-glow-border: 0 0 10px rgba(56, 189, 248, 0.3);
    --k-backdrop: blur(12px);
}

/* Base Reset & Typography */
body {
    background-color: var(--k-bg-subtle);
    color: var(--k-text);
    font-family: var(--k-font);
    transition: background-color 0.3s ease, color 0.3s ease;
    -webkit-font-smoothing: antialiased;
}

/* Header Redesign */
.catalogue-header {
    background: var(--k-card-bg);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--k-border);
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    backdrop-filter: blur(10px);
    position: sticky;
    top: 0;
    z-index: 100;
}

.dark-mode .catalogue-header {
    border-bottom: 1px solid rgba(56, 189, 248, 0.1);
    box-shadow: 0 4px 30px rgba(0,0,0,0.5);
}

.catalogue-title {
    font-weight: 800;
    font-size: 1.5rem;
    letter-spacing: -0.5px;
    margin: 0;
    background: linear-gradient(135deg, var(--k-text) 0%, var(--k-text-light) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dark-mode .catalogue-title {
    background: linear-gradient(135deg, #fff 0%, #38bdf8 100%);
    -webkit-background-clip: text;
    text-shadow: 0 0 20px rgba(56, 189, 248, 0.5);
}

/* Actions Header */
.header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.btn-icon-modern {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--k-border);
    color: var(--k-text);
    background: var(--k-card-bg);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-icon-modern:hover {
    transform: translateY(-2px);
    box-shadow: var(--k-shadow-hover);
    border-color: var(--k-primary);
    color: var(--k-primary);
}

.dark-mode .btn-icon-modern:hover {
    box-shadow: 0 0 15px var(--k-primary);
    text-shadow: 0 0 5px var(--k-primary);
}

/* Stats Cards Modernes */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    padding: 0 2rem 2rem;
}

.stat-card {
    background: var(--k-card-bg);
    border: 1px solid var(--k-border);
    border-radius: var(--k-radius);
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--k-shadow-hover);
    border-color: var(--k-accent);
}

.dark-mode .stat-card {
    background: rgba(17, 24, 39, 0.4);
    backdrop-filter: blur(5px);
}

.dark-mode .stat-card:hover {
    border-color: var(--k-primary);
    box-shadow: 0 0 20px rgba(56, 189, 248, 0.2);
}

.stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: var(--k-text);
    line-height: 1;
}

.stat-label {
    font-size: 0.85rem;
    color: var(--k-text-light);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Filters Hero Section */
.filters-hero {
    margin: 0 2rem 2rem;
    background: var(--k-card-bg);
    border-radius: var(--k-radius);
    padding: 1.5rem;
    border: 1px solid var(--k-border);
    box-shadow: var(--k-shadow);
}

.dark-mode .filters-hero {
    background: rgba(15, 23, 42, 0.6);
    border-color: rgba(255, 255, 255, 0.05);
}

.search-hero-wrapper {
    position: relative;
    margin-bottom: 1.5rem;
}

.search-hero-input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    font-size: 1.1rem;
    border: 2px solid var(--k-border);
    border-radius: var(--k-radius);
    background: var(--k-bg-subtle);
    color: var(--k-text);
    transition: all 0.3s ease;
}

.search-hero-input:focus {
    outline: none;
    border-color: var(--k-primary);
    background: var(--k-card-bg);
    box-shadow: 0 0 0 4px rgba(0,0,0,0.05);
}

.dark-mode .search-hero-input:focus {
    border-color: var(--k-primary);
    box-shadow: 0 0 20px rgba(56, 189, 248, 0.2);
}

.search-icon-hero {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--k-text-light);
    font-size: 1.2rem;
}

.filters-row {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.filter-pill {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    border: 1px solid var(--k-border);
    background: var(--k-bg);
    color: var(--k-text);
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-pill:hover, .filter-select:hover {
    border-color: var(--k-accent);
}

.filter-select {
    padding: 0.5rem 2rem 0.5rem 1rem;
    border-radius: 8px;
    border: 1px solid var(--k-border);
    background-color: var(--k-bg);
    color: var(--k-text);
    font-size: 0.9rem;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    background-size: 1rem;
}

/* Toggle Switch Moderne */
.switch-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}

.switch-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--k-text);
}

.switch-input {
    display: none;
}

.switch-track {
    width: 44px;
    height: 24px;
    background: var(--k-border);
    border-radius: 20px;
    position: relative;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.switch-thumb {
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    position: absolute;
    top: 2px;
    left: 2px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.switch-input:checked + .switch-track {
    background: var(--k-success);
}

.dark-mode .switch-input:checked + .switch-track {
    background: var(--k-primary); /* Cyan en dark mode */
    box-shadow: 0 0 15px var(--k-primary);
}

.switch-input:checked + .switch-track .switch-thumb {
    transform: translateX(20px);
}

/* Tableau Futuriste */
.table-container {
    margin: 0 2rem 2rem;
    background: var(--k-card-bg);
    border-radius: var(--k-radius);
    border: 1px solid var(--k-border);
    overflow: hidden;
    box-shadow: var(--k-shadow);
}

.catalogue-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.catalogue-table th {
    background: var(--k-bg-subtle);
    padding: 1.25rem 1rem;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 700;
    color: var(--k-text-light);
    border-bottom: 2px solid var(--k-border);
    position: sticky;
    top: 0; /* Sticky headers are tricky with custom scroll container, keeping simple for now */
}

.catalogue-table td {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid var(--k-border);
    color: var(--k-text);
    font-size: 0.95rem;
    transition: background 0.2s;
}

.catalogue-table tr:hover td {
    background: rgba(59, 130, 246, 0.05); /* Light blue tint */
}

.dark-mode .catalogue-table tr:hover td {
    background: rgba(56, 189, 248, 0.1);
    text-shadow: 0 0 8px rgba(255,255,255,0.3);
}

.product-name {
    font-weight: 600;
    font-size: 1rem;
    white-space: normal;
    min-width: 280px;
    line-height: 1.5;
}

.price-display {
    font-family: 'SF Mono', 'Fira Code', monospace;
    font-weight: 700;
    color: var(--k-text);
    background: var(--k-bg-subtle);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    border: 1px solid var(--k-border);
}

.dark-mode .price-display {
    color: var(--k-success);
    border-color: rgba(45, 212, 191, 0.3);
    text-shadow: 0 0 10px rgba(45, 212, 191, 0.4);
}

/* Badges Modernisés */
.k-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.k-badge.stock {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.dark-mode .k-badge.stock {
    color: var(--k-success);
    border-color: var(--k-success);
    box-shadow: 0 0 10px rgba(45, 212, 191, 0.2);
}

.k-badge.rupture {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.dark-mode .k-badge.rupture {
    color: var(--k-danger);
    border-color: var(--k-danger);
    box-shadow: 0 0 10px rgba(248, 113, 113, 0.2);
}

.k-badge.brand {
    background: var(--k-bg-subtle);
    color: var(--k-text-light);
    border: 1px solid var(--k-border);
}

/* Action Button Minimalist */
.btn-action-cart {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: var(--k-primary);
    color: white; /* Always white on primary */
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.btn-action-cart:hover {
    transform: scale(1.1);
}

.btn-action-cart.added {
    background: var(--k-success);
    transform: scale(1);
}

/* Pagination Minimalist */
.pagination-modern {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    padding: 2rem;
}

.page-link-modern {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: var(--k-card-bg);
    border: 1px solid var(--k-border);
    color: var(--k-text);
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    font-weight: 500;
}

.page-link-modern:hover, .page-link-modern.active {
    background: var(--k-primary);
    color: white; /* Important pour contraste */
    border-color: var(--k-primary);
}

.dark-mode .page-link-modern.active {
    box-shadow: 0 0 15px var(--k-primary);
}

/* Popover Redesign */
#cartClientPopover {
    background: var(--k-card-bg);
    border: 1px solid var(--k-border);
    border-radius: var(--k-radius);
    box-shadow: 0 20px 50px rgba(0,0,0,0.2);
    backdrop-filter: blur(20px);
}

.dark-mode #cartClientPopover {
    border-color: var(--k-primary);
    box-shadow: 0 0 40px rgba(56, 189, 248, 0.15);
}

.popover-header {
    background: transparent;
    border-bottom: 1px solid var(--k-border);
    color: var(--k-text);
    font-weight: 700;
}

/* Loading Overlay Glass */
.loading-overlay {
    background: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(5px);
}

.loading-spinner {
    border-color: rgba(255, 255, 255, 0.1);
    border-top-color: var(--k-primary);
}

.modal-modern .modal-footer {
    border-top: 1px solid var(--k-border);
    padding: 1rem 1.5rem;
}


/* Autocomplete Modern */
.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--k-card-bg);
    border: 1px solid var(--k-border);
    border-radius: 0 0 12px 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.autocomplete-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.autocomplete-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--k-border);
    transition: background 0.2s;
}

.autocomplete-item:last-child {
    border-bottom: none;
}

.autocomplete-item:hover, .autocomplete-item.selected {
    background: var(--k-bg-subtle);
}

.model-name {
    font-weight: 600;
    color: var(--k-text);
    display: block;
}

.model-brand {
    font-size: 0.75rem;
    color: var(--k-text-light);
}

.model-count {
    background: var(--k-primary);
    color: white; /* Always white for text inside badge */
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
}

.dark-mode .model-count {
    box-shadow: 0 0 10px var(--k-primary);
}

.upload-zone {
    border: 2px dashed var(--day-border);
    border-radius: 16px;
    padding: 3rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f8fafc;
}

.upload-zone:hover {
    border-color: var(--day-primary);
    background: rgba(59, 130, 246, 0.05);
}

.upload-zone i {
    font-size: 3rem;
    color: var(--day-primary);
    margin-bottom: 1rem;
}

.upload-zone p {
    margin: 0;
    color: var(--day-text-light);
}

.file-input {
    display: none;
}

/* Responsive */
@media (max-width: 768px) {
    .catalogue-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .filters-row {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* ========================================
   CORRECTION DU DÉCALAGE EN HAUT DE PAGE
   ======================================== */
body {
    padding-top: 0 !important;
    margin-top: 0 !important;
}
</style>

<div class="catalogue-container">
    <!-- En-tête -->
    <div class="catalogue-header">
        <h1 class="catalogue-title">
            <i class="fas fa-boxes"></i>
            Catalogue
        </h1>
        <div class="header-actions">
            <!-- Bouton Import (Gardé mais stylisé) -->
            <button class="btn-modern btn-primary-modern" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-upload"></i> <span class="d-none d-md-inline">Importer</span>
            </button>
            <!-- Toggle Dark Mode -->
            <button type="button" class="btn-icon-modern" id="toggleDarkMode" title="Mode Jour/Nuit">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </div>
    
    <!-- Statistiques Modernes -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['total'] ?? 0); ?></div>
            <div class="stat-label">Total produits</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['nb_fournisseurs'] ?? 0); ?></div>
            <div class="stat-label">Fournisseurs</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--k-success);">
            <div class="stat-value" style="color: var(--k-success);"><?php echo number_format($stats['en_stock'] ?? 0); ?></div>
            <div class="stat-label">En stock</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--k-danger);">
            <div class="stat-value" style="color: var(--k-danger);"><?php echo number_format($stats['rupture'] ?? 0); ?></div>
            <div class="stat-label">Rupture</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['nb_marques'] ?? 0); ?></div>
            <div class="stat-label">Marques</div>
        </div>
    </div>
    
    <!-- Filtres & Recherche HERO -->
    <div class="filters-hero">
        <!-- Barre de recherche Principale et Modèle -->
        <div class="search-hero-wrapper" style="display: flex; gap: 1rem; align-items: center;">
            
            <!-- Autocomplete Modèle (Restauré à gauche) -->
            <div class="model-autocomplete-group position-relative" style="flex: 1; max-width: 300px;">
                <i class="fas fa-cube position-absolute text-muted" style="left: 15px; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                <input type="text" class="search-hero-input ps-5" id="modelAutocomplete" placeholder="Modèle (ex: iPhone 13)..." autocomplete="off" style="width: 100%;">
                <div class="autocomplete-dropdown" id="modelDropdown"></div>
                <input type="hidden" id="selectedModel" value="">
            </div>

            <div style="width: 1px; height: 30px; background: var(--k-border);"></div>

            <!-- Recherche Globale -->
            <div class="position-relative" style="flex: 2;">
                <i class="fas fa-search search-icon-hero"></i>
                <input type="text" class="search-hero-input" id="searchInput" placeholder="Rechercher un produit, une référence, une marque..." style="width: 100%;">
            </div>
        </div>

        <div class="filters-row">
            <!-- Filtre Stock (Switch Modern) -->
            <div class="switch-wrapper" title="Afficher seulement le stock">
                <input type="checkbox" id="stockCheckbox" class="switch-input">
                <label for="stockCheckbox" class="switch-track">
                    <span class="switch-thumb"></span>
                </label>
                <span class="switch-label">En stock uniquement</span>
            </div>
            
            <div style="width: 1px; height: 24px; background: var(--k-border); margin: 0 1rem;"></div>

            <!-- Selects (Pills style) -->
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <select class="filter-select" id="fournisseurFilter">
                    <option value="">Tous les fournisseurs</option>
                    <?php foreach ($fournisseurs as $f): ?>
                        <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['nom']); ?></option>
                    <?php endforeach; ?>
                </select>

                <select class="filter-select" id="brandFilter">
                    <option value="">Toutes les marques</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?php echo htmlspecialchars($b); ?>"><?php echo htmlspecialchars($b); ?></option>
                    <?php endforeach; ?>
                </select>

                <select class="filter-select" id="typeFilter">
                    <option value="">Tous les types</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select class="filter-select" id="deviceTypeFilter">
                    <option value="">Tous les appareils</option>
                    <?php foreach ($deviceTypes as $d): ?>
                        <option value="<?php echo htmlspecialchars($d); ?>"><?php echo htmlspecialchars($d); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Tableau -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="catalogue-table">
                <thead>
                    <tr>
                        <th class="sortable header-sort-name" data-sort="name" style="cursor: pointer;">Produit <i class="fas fa-sort small ms-1 text-muted"></i></th>
                        <th class="sortable header-sort-price" data-sort="price" style="cursor: pointer;">Prix HT <i class="fas fa-sort small ms-1 text-muted"></i></th>
                        <th>Référence</th>
                        <th>Marque</th>
                        <th>Modèle</th>
                        <th>Stock</th>
                        <th style="width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody id="catalogueBody">
                    <!-- Chargé via JavaScript -->
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination-modern">
            <div class="pagination-buttons" id="paginationButtons" style="display:flex; gap: 0.5rem;">
                <!-- Généré via JavaScript -->
            </div>
        </div>
        <div class="text-center pb-3" style="color: var(--k-text-light); font-size: 0.8rem;" id="paginationInfo">
            Affichage de 0 à 0 sur 0 produits
        </div>
    </div>
</div>

<!-- Modal Import -->
<div class="modal fade modal-modern" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-upload me-2 text-primary"></i>
                    Importer un catalogue
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Fournisseur</label>
                    <select class="form-select" id="importFournisseur" required>
                        <option value="">Sélectionner un fournisseur</option>
                        <?php foreach ($fournisseurs as $f): ?>
                            <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="upload-zone" id="uploadZone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p class="mb-2"><strong>Cliquez ou glissez un fichier ici</strong></p>
                    <p class="small">Formats acceptés : CSV, JSON</p>
                    <input type="file" class="file-input" id="fileInput" accept=".csv,.json">
                </div>
                
                <div id="filePreview" class="mt-3" style="display: none;">
                    <div class="alert alert-success">
                        <i class="fas fa-file me-2"></i>
                        <span id="fileName"></span>
                        <button type="button" class="btn-close float-end" onclick="clearFile()"></button>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Colonnes attendues :</strong> name, url, price, reference, stock, type, device_type, brand, series, model
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="importBtn" disabled>
                    <i class="fas fa-upload me-1"></i> Importer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<!-- Popover Selection Client -->
<div id="cartClientPopover" class="popover fade show bs-popover-start" role="tooltip" style="position: absolute; display: none; z-index: 1060; width: 280px;">
    <div class="popover-arrow" style="position: absolute; top: 15px;"></div>
    <h3 class="popover-header d-flex justify-content-between align-items-center bg-light">
        <span><i class="fas fa-user-plus me-2"></i>Pour qui ?</span>
        <button type="button" class="btn-close small" onclick="closeCartPopover()"></button>
    </h3>
    <div class="popover-body">
        <div id="popoverSearchView">
            <div class="d-grid gap-2 mb-3">
                 <button class="btn btn-outline-primary btn-sm fw-bold" onclick="confirmAddToCart(null)">
                    <i class="fas fa-store me-2"></i> Magasin Atelier
                 </button>
            </div>
            <div class="text-center text-muted small mb-2 position-relative">
                <span class="bg-white px-2" style="z-index: 1; position: relative;">OU</span>
                <hr class="position-absolute w-100 top-50 m-0" style="z-index: 0;">
            </div>
            <div class="position-relative">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="clientSearchInput" placeholder="Rechercher un client..." autocomplete="off" style="background-color: #2c2f33; border-color: #444; color: white !important;">
                    <button class="btn btn-outline-success" type="button" onclick="showCreateClientView()" title="Nouveau client"><i class="fas fa-plus"></i></button>
                </div>
                <div id="clientSearchResults" class="list-group position-absolute w-100 shadow-lg" style="display:none; max-height: 200px; overflow-y: auto; z-index: 1100; top: 100%;"></div>
            </div>
        </div>
        
        <style>
            #clientSearchInput::placeholder {
                color: rgba(255, 255, 255, 0.6) !important;
            }
        </style>
        
        <div id="popoverCreateView" style="display:none;">
            <h6 class="mb-3 text-primary"><i class="fas fa-user-plus me-1"></i> Nouveau Client</h6>
            <div class="mb-3">
                <div class="mb-2 position-relative">
                    <i class="fas fa-user position-absolute text-muted" style="left: 10px; top: 9px; font-size: 0.8rem;"></i>
                    <input class="form-control form-control-sm ps-4 text-white" id="newClientNom" placeholder="Nom" required style="background-color: #2c2f33; border-color: #444; color: white !important;">
                </div>
                <div class="mb-2 position-relative">
                    <i class="fas fa-user position-absolute text-muted" style="left: 10px; top: 9px; font-size: 0.8rem;"></i>
                    <input class="form-control form-control-sm ps-4 text-white" id="newClientPrenom" placeholder="Prénom" required style="background-color: #2c2f33; border-color: #444; color: white !important;">
                </div>
                <div class="mb-2 position-relative">
                    <i class="fas fa-phone position-absolute text-muted" style="left: 10px; top: 9px; font-size: 0.8rem;"></i>
                    <input class="form-control form-control-sm ps-4 text-white" id="newClientTel" placeholder="Téléphone (33612345678)" required style="background-color: #2c2f33; border-color: #444; color: white !important;">
                </div>
                <div class="mb-3 position-relative">
                    <i class="fas fa-envelope position-absolute text-muted" style="left: 10px; top: 9px; font-size: 0.8rem;"></i>
                    <input class="form-control form-control-sm ps-4 text-white" id="newClientEmail" placeholder="Email (facultatif)" style="background-color: #2c2f33; border-color: #444; color: white !important;">
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm flex-fill" onclick="showSearchView()">Retour</button>
                <button class="btn btn-success btn-sm flex-fill" onclick="createNewClient()">Créer</button>
            </div>
        </div>
        
        <style>
            #popoverCreateView input::placeholder {
                color: rgba(255, 255, 255, 0.6) !important;
            }
        </style>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let totalPages = 1;
    const limit = 50;
    
    // État du tri
    let currentSort = '';
    let currentOrder = 'ASC';
    
    // Charger les données initiales
    loadCatalogue();
    
    // Événements de tri
    document.querySelectorAll('.sortable').forEach(th => {
        th.addEventListener('click', () => {
             const sort = th.dataset.sort;
             if (currentSort === sort) {
                 currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
             } else {
                 currentSort = sort;
                 currentOrder = 'ASC';
             }
             updateSortIcons();
             loadCatalogue();
        });
    });
    
    function updateSortIcons() {
        document.querySelectorAll('.sortable i').forEach(icon => {
            icon.className = 'fas fa-sort small ms-1 text-muted';
        });
        
        if (currentSort) {
            const activeTh = document.querySelector(`.sortable[data-sort="${currentSort}"]`);
            if (activeTh) {
                const icon = activeTh.querySelector('i');
                icon.className = `fas fa-sort-${currentOrder === 'ASC' ? 'up' : 'down'} small ms-1 text-primary`;
            }
        }
    }
    
    // Événements des filtres
    let debounceTimer;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            currentPage = 1;
            loadCatalogue();
        }, 300);
    });
    
    // Événements des selects
    ['fournisseurFilter', 'typeFilter', 'brandFilter', 'deviceTypeFilter'].forEach(id => {
        const el = document.getElementById(id);
        if(el) {
            el.addEventListener('change', function() {
                currentPage = 1;
                loadCatalogue();
            });
        }
    });
    
    // Checkbox Stock
    const stockCheckbox = document.getElementById('stockCheckbox');
    if(stockCheckbox) {
        stockCheckbox.addEventListener('change', function() {
            currentPage = 1;
            loadCatalogue();
        });
    }
    

    

    
    // ========== AUTOCOMPLETE MODÈLE ==========
    const modelInput = document.getElementById('modelAutocomplete');
    const modelDropdown = document.getElementById('modelDropdown');
    const selectedModelInput = document.getElementById('selectedModel');
    let autocompleteTimer;
    let selectedIndex = -1;
    
    // Debounce pour l'autocomplete
    if (modelInput) {
        modelInput.addEventListener('input', function() {
            clearTimeout(autocompleteTimer);
            const query = this.value.trim();
            
            if (query.length < 1) {
                hideAutocomplete();
                selectedModelInput.value = '';
                return;
            }
            
            autocompleteTimer = setTimeout(() => {
                fetchModels(query);
            }, 150);
        });
        
        // Fermer le dropdown quand on clique ailleurs
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.model-autocomplete-group')) {
                hideAutocomplete();
            }
        });
        
        // Navigation clavier
        modelInput.addEventListener('keydown', function(e) {
            const items = modelDropdown.querySelectorAll('.autocomplete-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, 0);
                updateSelection(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    selectModel(items[selectedIndex].dataset.model, items[selectedIndex].dataset.brand);
                } else if (modelInput.value.trim()) {
                    // Si pas de sélection, utiliser le texte comme recherche directe
                    selectedModelInput.value = modelInput.value.trim();
                    hideAutocomplete();
                    currentPage = 1;
                    loadCatalogue();
                }
            } else if (e.key === 'Escape') {
                hideAutocomplete();
            }
        });
    }
    
    function fetchModels(query) {
        const brand = document.getElementById('brandFilter').value;
        let url = 'ajax/catalogue_models_autocomplete.php?q=' + encodeURIComponent(query);
        if (brand) url += '&brand=' + encodeURIComponent(brand);
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    renderAutocomplete(data.data);
                } else {
                    hideAutocomplete();
                }
            })
            .catch(error => {
                console.error('Erreur autocomplete:', error);
                hideAutocomplete();
            });
    }
    
    function renderAutocomplete(models) {
        selectedIndex = -1;
        modelDropdown.innerHTML = models.map((m, i) => `
            <div class="autocomplete-item" data-model="${escapeHtml(m.model)}" data-brand="${escapeHtml(m.brand)}">
                <div>
                    <span class="model-name">${escapeHtml(m.model)}</span>
                    <span class="model-brand">${escapeHtml(m.brand)}</span>
                </div>
                <span class="model-count">${m.product_count}</span>
            </div>
        `).join('');
        
        // Événements de clic
        modelDropdown.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', function() {
                selectModel(this.dataset.model, this.dataset.brand);
            });
        });
        
        modelDropdown.classList.add('active');
    }
    
    function selectModel(model, brand) {
        modelInput.value = model;
        selectedModelInput.value = model;
        hideAutocomplete();
        currentPage = 1;
        loadCatalogue();
    }
    
    function hideAutocomplete() {
        if(modelDropdown) {
          modelDropdown.classList.remove('active');
        }
        selectedIndex = -1;
    }
    
    function updateSelection(items) {
        items.forEach((item, i) => {
            item.classList.toggle('selected', i === selectedIndex);
        });
        if (items[selectedIndex]) {
            items[selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }
    // ========== FIN AUTOCOMPLETE ==========

    // Zone d'upload
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    
    uploadZone.addEventListener('click', () => fileInput.click());
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.style.borderColor = 'var(--day-primary)';
    });
    uploadZone.addEventListener('dragleave', () => {
        uploadZone.style.borderColor = '';
    });
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.style.borderColor = '';
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelect();
        }
    });
    
    fileInput.addEventListener('change', handleFileSelect);
    
    // Import
    document.getElementById('importBtn').addEventListener('click', importCatalogue);
    
    function loadCatalogue() {
        const searchValue = document.getElementById('searchInput').value;
        const params = new URLSearchParams({
            page: currentPage,
            limit: limit,
            fournisseur_id: document.getElementById('fournisseurFilter').value,
            type: document.getElementById('typeFilter').value,
            brand: document.getElementById('brandFilter').value,
            device_type: document.getElementById('deviceTypeFilter').value,
            model: document.getElementById('selectedModel') ? document.getElementById('selectedModel').value : '',
            stock: document.getElementById('stockCheckbox') && document.getElementById('stockCheckbox').checked ? 'en_stock' : '',
            search: searchValue,
            sort: currentSort,
            order: currentOrder
        });
        
        fetch('ajax/get_catalogue.php?' + params)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderTable(data.data);
                    renderPagination(data.pagination);
                    // AI features removed

                } else {
                    console.error('Erreur:', data.message);
                }
            })
            .catch(error => console.error('Erreur:', error));
    }
    

    


    
    function renderTable(products) {
        const tbody = document.getElementById('catalogueBody');
        
        if (!products.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>Aucun produit</h3>
                            <p>Importez un catalogue pour commencer</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = products.map(p => `
            <tr>
                <td>
                    <div class="product-name" title="${escapeHtml(p.name)}">
                        ${p.url ? `<a href="${escapeHtml(p.url)}" target="_blank" class="product-link">${escapeHtml(p.name)}</a>` : escapeHtml(p.name)}
                    </div>
                </td>
                <td><span class="price-badge">${p.price ? parseFloat(p.price).toFixed(2) + ' €' : '-'}</span></td>
                <td><code>${escapeHtml(p.reference || '-')}</code></td>
                <td>${p.brand ? `<span class="brand-badge">${escapeHtml(p.brand)}</span>` : '-'}</td>
                <td>${escapeHtml(p.model || '-')}</td>
                <td>
                    ${p.stock && p.stock.includes('En stock') 
                        ? '<span class="stock-badge in-stock"><i class="fas fa-check"></i> En stock</span>'
                        : p.stock && p.stock.includes('Rupture')
                            ? '<span class="stock-badge out-of-stock"><i class="fas fa-times"></i> Rupture</span>'
                            : escapeHtml(p.stock || '-')
                    }
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="addToCart(${p.id}, ${p.fournisseur_id}, '${escapeHtml(p.name.replace(/'/g, "\\'"))}', ${p.price || 0}, '${escapeHtml((p.reference || '').replace(/'/g, "\\'"))}')" title="Ajouter au panier">
                        <i class="fas fa-cart-plus"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    let pendingCartItem = null;
    let pendingButton = null;

    // Nouvelle fonction addToCart
    window.addToCart = function(catalogueId, fournisseurId, name, price, reference) {
        pendingCartItem = { catalogueId, fournisseurId, name, price, reference };
        pendingButton = event.currentTarget;
        
        const popover = document.getElementById('cartClientPopover');
        const btnRect = pendingButton.getBoundingClientRect();
        
        // Reset Search & Views
        document.getElementById('clientSearchInput').value = '';
        document.getElementById('clientSearchResults').style.display = 'none';
        showSearchView(); // Reset view
        
        // Positionnement (à gauche du bouton)
        popover.style.display = 'block';
        popover.style.top = (window.scrollY + btnRect.top - 20) + 'px';
        popover.style.left = (window.scrollX + btnRect.left - popover.offsetWidth - 10) + 'px';
        
        // Focus search
        setTimeout(() => document.getElementById('clientSearchInput').focus(), 100);
        
        // Fermer si clic ailleurs
        document.addEventListener('click', closeCartPopoverOutside);
        event.stopPropagation();
    };

    window.closeCartPopover = function() {
        document.getElementById('cartClientPopover').style.display = 'none';
        document.removeEventListener('click', closeCartPopoverOutside);
        pendingCartItem = null;
        pendingButton = null;
    };
    
    function closeCartPopoverOutside(e) {
        if (!e.target.closest('#cartClientPopover') && e.target !== pendingButton && !pendingButton.contains(e.target)) {
            closeCartPopover();
        }
    }

    // Confirmation Ajout
    window.confirmAddToCart = function(clientId) {
        if (!pendingCartItem || !pendingButton) return;
        
        const { catalogueId, fournisseurId, name, price, reference } = pendingCartItem;
        const btn = pendingButton;
        const originalHtml = btn.innerHTML;
        
        closeCartPopover();
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const formData = new FormData();
        formData.append('catalogue_id', catalogueId);
        formData.append('fournisseur_id', fournisseurId);
        formData.append('nom_piece', name);
        formData.append('prix', price);
        formData.append('reference', reference);
        if (clientId) {
            formData.append('client_id', clientId);
        }
        
        fetch('ajax/add_catalogue_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            
            if (data.success) {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-success');
                btn.innerHTML = '<i class="fas fa-check"></i>';
                
                setTimeout(() => {
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-primary');
                    btn.innerHTML = '<i class="fas fa-cart-plus"></i>';
                }, 2000);
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    };
    
    // Recherche Client
    let clientSearchTimer;
    document.getElementById('clientSearchInput').addEventListener('input', function() {
        clearTimeout(clientSearchTimer);
        const query = this.value.trim();
        const resultsDiv = document.getElementById('clientSearchResults');
        
        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }
        
        clientSearchTimer = setTimeout(() => {
            fetch('ajax/search_clients.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    if (data.success && data.clients.length > 0) {
                        html = data.clients.map(c => `
                            <button class="list-group-item list-group-item-action" onclick="confirmAddToCart(${c.id})">
                                <strong>${escapeHtml(c.nom)} ${escapeHtml(c.prenom)}</strong><br>
                                <small class="text-muted">${escapeHtml(c.telephone || '')}</small>
                            </button>
                        `).join('');
                    } 
                    
                    // Toujours ajouter l'option de création à la fin si pas de résultat exact ou pour commodité
                    if (data.clients.length === 0) {
                         html += `
                            <button class="list-group-item list-group-item-action text-center text-primary" onclick="showCreateClientView('${escapeHtml(query)}')">
                                <i class="fas fa-plus-circle me-1"></i> Créer "${escapeHtml(query)}"
                            </button>
                        `;
                    }
                    
                    if (html) {
                        resultsDiv.innerHTML = html;
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.style.display = 'none';
                    }
                })
                .catch(err => console.error(err));
        }, 300);
    });
    
    // Gestion des vues du Popover
    window.showSearchView = function() {
        document.getElementById('popoverSearchView').style.display = 'block';
        document.getElementById('popoverCreateView').style.display = 'none';
        setTimeout(() => document.getElementById('clientSearchInput').focus(), 100);
    };
    
    window.showCreateClientView = function(prefillName = '') {
        document.getElementById('popoverSearchView').style.display = 'none';
        document.getElementById('popoverCreateView').style.display = 'block';
        
        // Reset inputs
        document.getElementById('newClientNom').value = prefillName;
        document.getElementById('newClientPrenom').value = '';
        document.getElementById('newClientTel').value = '';
        document.getElementById('newClientEmail').value = '';
        
        setTimeout(() => document.getElementById('newClientNom').focus(), 100);
    };
    
    window.createNewClient = function() {
        const nom = document.getElementById('newClientNom').value.trim();
        const prenom = document.getElementById('newClientPrenom').value.trim();
        const tel = document.getElementById('newClientTel').value.trim();
        const email = document.getElementById('newClientEmail').value.trim();
        
        if (!nom || !prenom || !tel) {
            alert('Nom, prénom et téléphone sont requis.');
            return;
        }

        // Validation format international strict (ex: 33612345678)
        const phoneRegex = /^33[0-9]{9}$/;
        if (!phoneRegex.test(tel)) {
            alert('Format de téléphone invalide.\nLe format international est OBLIGATOIRE.\nExemple : 33612345678 (pas de 0 au début, pas de +)');
            return;
        }
        
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const formData = new FormData();
        formData.append('nom', nom);
        formData.append('prenom', prenom);
        formData.append('telephone', tel);
        formData.append('email', email);
        formData.append('adresse', ''); // Requis par ajax/add_client.php mais peut être vide
        
        fetch('ajax/add_client.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            
            if (data.success && data.client && data.client.id) {
                // Client créé ! On l'utilise directement pour la commande
                confirmAddToCart(data.client.id);
            } else {
                alert('Erreur: ' + (data.message || 'Impossible de créer le client'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            console.error(err);
            alert('Erreur technique lors de la création');
        });
    };
    
    function renderPagination(pagination) {
        totalPages = pagination.totalPages;
        
        const start = pagination.total === 0 ? 0 : (pagination.page - 1) * pagination.limit + 1;
        const end = Math.min(pagination.page * pagination.limit, pagination.total);
        
        document.getElementById('paginationInfo').textContent = 
            `Affichage de ${start} à ${end} sur ${pagination.total.toLocaleString()} produits`;
        
        const buttonsContainer = document.getElementById('paginationButtons');
        let html = '';
        
        html += `<button class="page-btn" onclick="changePage(1)" ${pagination.page === 1 ? 'disabled' : ''}><i class="fas fa-angle-double-left"></i></button>`;
        html += `<button class="page-btn" onclick="changePage(${pagination.page - 1})" ${!pagination.hasPrev ? 'disabled' : ''}><i class="fas fa-angle-left"></i></button>`;
        
        const startPage = Math.max(1, pagination.page - 2);
        const endPage = Math.min(totalPages, pagination.page + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="page-btn ${i === pagination.page ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
        }
        
        html += `<button class="page-btn" onclick="changePage(${pagination.page + 1})" ${!pagination.hasNext ? 'disabled' : ''}><i class="fas fa-angle-right"></i></button>`;
        html += `<button class="page-btn" onclick="changePage(${totalPages})" ${pagination.page === totalPages ? 'disabled' : ''}><i class="fas fa-angle-double-right"></i></button>`;
        
        buttonsContainer.innerHTML = html;
    }
    
    window.changePage = function(page) {
        if (page >= 1 && page <= totalPages) {
            currentPage = page;
            loadCatalogue();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };
    
    function handleFileSelect() {
        const file = fileInput.files[0];
        if (file) {
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('filePreview').style.display = 'block';
            document.getElementById('importBtn').disabled = !document.getElementById('importFournisseur').value;
        }
    }
    
    window.clearFile = function() {
        fileInput.value = '';
        document.getElementById('filePreview').style.display = 'none';
        document.getElementById('importBtn').disabled = true;
    };
    
    document.getElementById('importFournisseur').addEventListener('change', function() {
        document.getElementById('importBtn').disabled = !this.value || !fileInput.files.length;
    });
    
    function importCatalogue() {
        const fournisseurId = document.getElementById('importFournisseur').value;
        const file = fileInput.files[0];
        
        if (!fournisseurId || !file) {
            alert('Veuillez sélectionner un fournisseur et un fichier');
            return;
        }
        
        const formData = new FormData();
        formData.append('file', file);
        formData.append('fournisseur_id', fournisseurId);
        
        document.getElementById('loadingOverlay').style.display = 'flex';
        
        fetch('ajax/import_catalogue.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('loadingOverlay').style.display = 'none';
            
            if (data.success) {
                alert(data.message);
                bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
                clearFile();
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            document.getElementById('loadingOverlay').style.display = 'none';
            console.error('Erreur:', error);
            alert('Erreur lors de l\'import');
        });
    }
    

    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>


