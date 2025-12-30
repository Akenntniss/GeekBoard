<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Récupérer la liste des fournisseurs
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->query("SELECT * FROM fournisseurs ORDER BY nom");
    $fournisseurs = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des fournisseurs: " . $e->getMessage() . "</div>";
    $fournisseurs = [];
}

// Compter les fournisseurs
$total_fournisseurs = count($fournisseurs);
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* ========================================
       FIX NAVBAR - FROM COMMANDE_MODERNE.PHP
       Obligatoire pour affichage correct
    ======================================== */
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
            padding: 0.75rem 1rem !important;
            min-height: 60px !important;
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
        --day-input-bg: #ffffff;

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
        --night-input-bg: rgba(255, 255, 255, 0.05);
    }

    /* ========================================
       STRUCTURE & ANIMATIONS
    ======================================== */
    body {
        font-family: 'Inter', sans-serif;
        transition: all 0.3s ease;
        min-height: 100vh;
        /* Background appliqué au body pour couvrir toute la page même si incluse */
        background: var(--day-bg-animated) !important;
        background-size: 300% 300% !important;
        animation: gradientFlow 20s ease infinite !important;
        background-attachment: fixed !important;
    }

    /* Override pour le mode nuit via la classe ajoutée par JS ou PHP */
    body.night-mode, body.dark-mode {
        background: var(--night-bg-animated) !important;
        background-size: 300% 300% !important;
        color: var(--night-text);
    }

    .content-wrapper {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    @keyframes gradientFlow {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    @keyframes slideInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ========================================
       COMPOSANTS UI
    ======================================== */
    
    /* Titres */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        animation: slideInUp 0.5s ease-out;
        /* Ajout d'un fond pour lisibilité si nécessaire */
        padding: 1.5rem;
        background: var(--day-card-bg);
        border-radius: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        backdrop-filter: blur(10px);
    }

    body.night-mode .page-header, body.dark-mode .page-header {
        background: var(--night-card-bg);
        border: 1px solid var(--night-border);
    }

    .page-title {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin: 0;
    }

    body.night-mode .page-title, body.dark-mode .page-title {
        background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
        -webkit-background-clip: text;
    }

    /* Cartes */
    .glass-card {
        background: var(--day-card-bg);
        border: 1px solid var(--day-border);
        border-radius: 24px;
        padding: 1.5rem;
        box-shadow: 0 10px 30px -10px var(--day-shadow);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        transition: all 0.3s ease;
        animation: slideInUp 0.6s ease-out;
    }

    body.night-mode .glass-card, body.dark-mode .glass-card {
        background: var(--night-card-bg);
        border-color: var(--night-border);
        box-shadow: 0 10px 40px -10px var(--night-shadow);
    }

    .glass-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px -10px var(--day-shadow);
    }

    body.night-mode .glass-card:hover, body.dark-mode .glass-card:hover {
        box-shadow: 0 0 20px var(--night-shadow);
        border-color: var(--night-primary);
    }

    /* Statistiques */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        padding: 2rem;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), transparent);
        opacity: 0.5;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
        color: white;
        box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
    }

    body.night-mode .stat-icon, body.dark-mode .stat-icon {
        background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
        box-shadow: 0 0 20px var(--night-shadow);
        color: #000;
    }

    .stat-info h3 {
        font-size: 2.5rem;
        font-weight: 800;
        margin: 0;
        color: var(--day-text);
        line-height: 1;
    }

    body.night-mode .stat-info h3, body.dark-mode .stat-info h3 {
        color: var(--night-text);
        text-shadow: 0 0 10px var(--night-shadow);
    }

    .stat-label {
        color: var(--day-text-light);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.85rem;
        margin-top: 0.5rem;
        display: block;
    }

    body.night-mode .stat-label, body.dark-mode .stat-label {
        color: var(--night-text-light);
    }

    /* Recherche */
    .search-container {
        margin-bottom: 2rem;
        position: relative;
        animation: slideInUp 0.7s ease-out;
    }

    .modern-input {
        width: 100%;
        padding: 1.25rem 1.5rem 1.25rem 3.5rem;
        border: 2px solid var(--day-border);
        border-radius: 16px;
        background: var(--day-input-bg);
        font-size: 1rem;
        color: var(--day-text);
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    body.night-mode .modern-input, body.dark-mode .modern-input {
        background: var(--night-input-bg);
        border-color: var(--night-border);
        color: var(--night-text);
    }

    .modern-input:focus {
        outline: none;
        border-color: var(--day-primary);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    body.night-mode .modern-input:focus, body.dark-mode .modern-input:focus {
        border-color: var(--night-primary);
        box-shadow: 0 0 15px rgba(0, 212, 255, 0.2);
    }

    .search-icon {
        position: absolute;
        left: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--day-text-light);
        font-size: 1.2rem;
    }

    /* Liste des fournisseurs */
    .suppliers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .supplier-card {
        background: var(--day-card-bg);
        border: 1px solid var(--day-border);
        border-radius: 20px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 200px;
        position: relative;
        overflow: hidden;
        animation: slideInUp 0.8s ease-out;
    }

    body.night-mode .supplier-card, body.dark-mode .supplier-card {
        background: var(--night-card-bg) !important;
        border-color: var(--night-border) !important;
    }

    .supplier-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 20px 40px -10px var(--day-shadow);
        border-color: var(--day-primary);
    }

    body.night-mode .supplier-card:hover, body.dark-mode .supplier-card:hover {
        box-shadow: 0 0 30px var(--night-shadow);
        border-color: var(--night-primary);
    }

    .supplier-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .supplier-avatar {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--day-primary);
        font-size: 1.5rem;
        font-weight: 700;
    }

    body.night-mode .supplier-avatar, body.dark-mode .supplier-avatar {
        background: rgba(0, 212, 255, 0.1);
        color: var(--night-primary);
    }

    .supplier-name {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--day-text);
        margin: 0;
    }

    body.night-mode .supplier-name, body.dark-mode .supplier-name {
        color: var(--night-text);
    }

    .supplier-id {
        font-size: 0.75rem;
        color: var(--day-text-light);
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .supplier-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: auto;
    }

    .btn-modern {
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        border: none;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }

    .btn-primary-modern {
        background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
        color: white;
        box-shadow: 0 4px 12px var(--day-shadow);
    }

    body.night-mode .btn-primary-modern, body.dark-mode .btn-primary-modern {
        background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
        color: #000;
        box-shadow: 0 0 15px var(--night-shadow);
    }

    .btn-primary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px var(--day-shadow);
        color: white;
    }

    body.night-mode .btn-primary-modern:hover, body.dark-mode .btn-primary-modern:hover {
        box-shadow: 0 0 25px var(--night-shadow);
        color: #000;
    }

    .btn-outline-modern {
        background: transparent;
        border: 2px solid var(--day-primary);
        color: var(--day-primary);
    }

    body.night-mode .btn-outline-modern, body.dark-mode .btn-outline-modern {
        border-color: var(--night-primary);
        color: var(--night-primary);
    }

    .btn-outline-modern:hover {
        background: rgba(59, 130, 246, 0.1);
        transform: translateY(-2px);
    }

    body.night-mode .btn-outline-modern:hover, body.dark-mode .btn-outline-modern:hover {
        background: rgba(0, 212, 255, 0.1);
        box-shadow: 0 0 15px var(--night-shadow);
    }

    .btn-danger-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: none;
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-danger-icon:hover {
        background: #ef4444;
        color: white;
        transform: rotate(90deg);
    }

    body.night-mode .glass-card, body.dark-mode .glass-card {
        background: var(--night-card-bg) !important;
        border-color: var(--night-border) !important;
        box-shadow: 0 10px 40px -10px var(--night-shadow) !important;
    }

    /* Modal Styles Resets/Overrides */
    .modal-content {
        border-radius: 24px;
        border: none;
        overflow: hidden;
    }

    body:not(.night-mode):not(.dark-mode) .modal-content {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
    }

    body.night-mode .modal-content, body.dark-mode .modal-content {
        background: #1a1f2e !important;
        border: 1px solid var(--night-border) !important;
        box-shadow: 0 0 50px rgba(0, 212, 255, 0.2) !important;
        color: var(--night-text) !important;
    }

    /* Force input styles in modal for night mode */
    body.night-mode .modal-content .modern-input, 
    body.dark-mode .modal-content .modern-input {
        background: rgba(255, 255, 255, 0.05) !important;
        border-color: var(--night-border) !important;
        color: var(--night-text) !important;
    }
    
    body.night-mode .form-label, body.dark-mode .form-label {
        color: var(--night-text-light) !important;
    }

    .modal-header {
        border-bottom: 1px solid var(--day-border);
        padding: 1.5rem;
    }

    body.night-mode .modal-header, body.dark-mode .modal-header {
        border-bottom-color: var(--night-border);
    }
    
    body.night-mode .modal-header .btn-close, body.dark-mode .modal-header .btn-close {
        filter: invert(1);
    }

    .modal-title {
        font-weight: 700;
        color: var(--day-text);
    }

    body.night-mode .modal-title, body.dark-mode .modal-title {
        color: var(--night-text);
    }

    .modal-body {
        padding: 2rem;
    }

    .modal-footer {
        border-top: 1px solid var(--day-border);
        padding: 1.5rem;
    }

    body.night-mode .modal-footer, body.dark-mode .modal-footer {
        border-top-color: var(--night-border);
    }

    .empty-state {
        text-align: center;
        padding: 4rem;
        color: var(--day-text-light);
    }
    
    body.night-mode .empty-state, body.dark-mode .empty-state {
        color: var(--night-text-light);
    }
</style>

<div class="main-container content-wrapper">
    <!-- En-tête -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Fournisseurs</h1>
            <p class="text-muted mb-0" style="color: var(--day-text-light) !important;">Gérez vos partenaires et approvisionnements</p>
        </div>
        <button type="button" class="btn-modern btn-primary-modern" data-bs-toggle="modal" data-bs-target="#ajouterFournisseurModal">
            <i class="fas fa-plus-circle"></i> Nouveau Fournisseur
        </button>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="glass-card stat-card">
            <div class="stat-icon">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $total_fournisseurs; ?></h3>
                <span class="stat-label">Partenaires Actifs</span>
            </div>
        </div>
    </div>

    <!-- Barre de recherche -->
    <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="modern-input" id="searchSupplier" placeholder="Rechercher un fournisseur, une URL...">
    </div>

    <?php echo display_message(); ?>

    <!-- Grille des fournisseurs -->
    <div class="suppliers-grid" id="suppliersContainer">
        <?php if (!empty($fournisseurs)): ?>
            <?php foreach ($fournisseurs as $fournisseur): ?>
                <div class="supplier-card" data-name="<?php echo strtolower(htmlspecialchars($fournisseur['nom'])); ?>">
                    <div>
                        <div class="supplier-header">
                            <div class="supplier-avatar">
                                <?php echo strtoupper(substr($fournisseur['nom'], 0, 1)); ?>
                            </div>
                            <div>
                                <h4 class="supplier-name"><?php echo htmlspecialchars($fournisseur['nom']); ?></h4>
                                <span class="supplier-id">#<?php echo $fournisseur['id']; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="supplier-actions">
                        <?php if (!empty($fournisseur['url'])): ?>
                            <a href="<?php echo htmlspecialchars($fournisseur['url']); ?>" 
                               target="_blank" 
                               class="btn-modern btn-outline-modern flex-grow-1" 
                               style="justify-content: center;">
                                <i class="fas fa-external-link-alt"></i> Visiter
                            </a>
                        <?php else: ?>
                            <button class="btn-modern btn-outline-modern flex-grow-1" disabled style="opacity: 0.5; justify-content: center;">
                                <i class="fas fa-ban"></i> Pas d'URL
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" 
                                class="btn-danger-icon delete-supplier" 
                                data-id="<?php echo $fournisseur['id']; ?>"
                                data-nom="<?php echo htmlspecialchars($fournisseur['nom']); ?>"
                                data-bs-toggle="tooltip"
                                title="Supprimer">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="glass-card empty-state" style="grid-column: 1 / -1;">
                <i class="fas fa-box-open fa-4x mb-4" style="color: var(--day-text-light);"></i>
                <h3>Aucun fournisseur</h3>
                <p>Commencez par ajouter votre premier fournisseur.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal d'ajout de fournisseur -->
<div class="modal fade" id="ajouterFournisseurModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- NUCLEAR OPTION CSS INJECTION -->
            <style>
                .night-mode #ajouterFournisseurModal .modal-content,
                .night-mode #ajouterFournisseurModal input {
                    background-color: #1a1f2e !important;
                    color: white !important;
                    border-color: #2d3748 !important;
                }
                .night-mode #ajouterFournisseurModal .form-label {
                    color: #a0aec0 !important;
                }
                .night-mode #ajouterFournisseurModal .btn-close {
                    filter: invert(1) !important;
                }
            </style>
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2 text-primary"></i>
                    Ajouter un fournisseur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ajouterFournisseurForm">
                    <div class="mb-4">
                        <label for="nom" class="form-label fw-bold">Nom du fournisseur</label>
                        <input type="text" class="modern-input" id="nom" name="nom" required placeholder="Ex: Amazon, AliExpress...">
                    </div>
                    <div class="mb-3">
                        <label for="url" class="form-label fw-bold">Site Web</label>
                        <input type="url" class="modern-input" id="url" name="url" placeholder="https://...">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modern btn-outline-modern" data-bs-dismiss="modal">
                    Annuler
                </button>
                <button type="button" class="btn-modern btn-primary-modern" id="saveSupplierBtn">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteFournisseurModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmer la suppression
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="fs-5">Êtes-vous sûr de vouloir supprimer <strong id="deleteNomFournisseur"></strong> ?</p>
                <p class="text-muted small mb-0">Cette action est irréversible et retirera ce fournisseur de votre liste.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modern btn-outline-modern" data-bs-dismiss="modal">
                    Annuler
                </button>
                <button type="button" class="btn-modern btn-danger-icon" style="width: auto; padding: 0.75rem 1.5rem; background: #ef4444; color: white;" id="confirmDelete">
                    <i class="fas fa-trash me-2"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===================================
    // GESTION DU THÈME (AUTOMATIQUE SYSTÈME)
    // ===================================
    // Supprimer toute préférence manuelle pour forcer le mode automatique
    localStorage.removeItem('geekboard_theme');

    // ===================================
    // SEARCH FILTER
    // ===================================
    const searchInput = document.getElementById('searchSupplier');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.supplier-card');
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (name.includes(searchText)) {
                    card.style.display = 'flex';
                    card.style.animation = 'slideInUp 0.5s ease-out';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // ===================================
    // MODALS LOGIC
    // ===================================
    const addModalEl = document.getElementById('ajouterFournisseurModal');
    const deleteModalEl = document.getElementById('deleteFournisseurModal');

    // Vérifier l'existence des modals avant d'initialiser Bootstrap
    if (addModalEl && deleteModalEl) {
        const addModal = new bootstrap.Modal(addModalEl);
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        
        // Initialiser les tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Add Supplier
        const saveSupplierBtn = document.getElementById('saveSupplierBtn');
        if (saveSupplierBtn) {
            saveSupplierBtn.addEventListener('click', async function() {
                const form = document.getElementById('ajouterFournisseurForm');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                const formData = new FormData(form);
                const btn = this;
                const originalHtml = btn.innerHTML;
                
                try {
                    // Animation de chargement
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                    const response = await fetch('../ajax/add_supplier.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Erreur lors de l\'ajout');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert(error.message);
                    // Reset button
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
                }
            });
        }

        // Delete Supplier
        document.querySelectorAll('.delete-supplier').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nom = this.getAttribute('data-nom');
                
                document.getElementById('deleteNomFournisseur').textContent = nom;
                document.getElementById('confirmDelete').setAttribute('data-id', id);
                
                deleteModal.show();
            });
        });

        const confirmDeleteBtn = document.getElementById('confirmDelete');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', async function() {
                const id = this.getAttribute('data-id');
                const btn = this;
                const originalHtml = btn.innerHTML;
                
                try {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                    const response = await fetch('../ajax/delete_supplier.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Erreur lors de la suppression');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert(error.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-trash me-2"></i> Supprimer';
                }
            });
        }
    }
});
</script>

<!-- Inclusion du script unifié de mode nuit -->
<script src="../assets/js/unified-night-mode.js?v=<?php echo time(); ?>"></script>