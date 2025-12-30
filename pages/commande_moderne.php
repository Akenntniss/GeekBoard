<?php
// Inclure la configuration de session avant de démarrer la session
require_once __DIR__ . '/../config/session_config.php';
// La session est déjà démarrée dans session_config.php

// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Vérifier que le shop_id est défini dans la session
if (!isset($_SESSION['shop_id'])) {
    error_log("Erreur: shop_id non défini dans la session pour commande_moderne.php");
    header('Location: /pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Vérifier que le shop_id est valide
try {
    $pdo_main = getMainDBConnection();
    $stmt = $pdo_main->prepare("SELECT id FROM shops WHERE id = ? AND active = 1");
    $stmt->execute([$_SESSION['shop_id']]);
    if (!$stmt->fetch()) {
        error_log("Erreur: shop_id invalide ou inactif pour commande_moderne.php");
        header('Location: /pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
} catch (Exception $e) {
    error_log("Erreur lors de la vérification du shop_id dans commande_moderne.php: " . $e->getMessage());
    header('Location: /pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Récupérer les commandes de pièces avec les informations associées
try {
    $shop_pdo = getShopDBConnection();
    // Tentative avec la sous-requête pour la quantité
    try {
        $sql_w_qty = "
            SELECT c.*, f.nom as fournisseur_nom, cl.nom as client_nom, cl.prenom as client_prenom, cl.telephone,
             r.type_appareil, r.modele,
             (SELECT COALESCE(SUM(quantite), 0) FROM commandes_pieces_items WHERE commande_id = c.id) as items_quantite
             FROM commandes_pieces c 
             LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id 
             LEFT JOIN clients cl ON c.client_id = cl.id 
             LEFT JOIN reparations r ON c.reparation_id = r.id 
             ORDER BY c.date_creation DESC
        ";
        $stmt = $shop_pdo->query($sql_w_qty);
        
        if ($stmt === false) {
            $err = $shop_pdo->errorInfo();
            throw new Exception("Erreur SQL avec quantité: " . implode(", ", $err));
        }
        
        $commandes = $stmt->fetchAll();
    } catch (Exception $e) {
        // En cas d'erreur (ex: table commandes_pieces_items inexistante), fallback sur l'ancienne requête
        error_log("Fallback commandes: " . $e->getMessage());
        
        $sql_fallback = "
            SELECT c.*, f.nom as fournisseur_nom, cl.nom as client_nom, cl.prenom as client_prenom, cl.telephone,
             r.type_appareil, r.modele
             FROM commandes_pieces c 
             LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id 
             LEFT JOIN clients cl ON c.client_id = cl.id 
             LEFT JOIN reparations r ON c.reparation_id = r.id 
             ORDER BY c.date_creation DESC
        ";
        $stmt = $shop_pdo->query($sql_fallback);
        $commandes = $stmt ? $stmt->fetchAll() : [];
        
        // Afficher une alerte discrète pour le debug
        echo "<div style='display:none' data-debug-error='" . htmlspecialchars($e->getMessage()) . "'></div>";
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des commandes: " . $e->getMessage() . "</div>";
    $commandes = [];
}

// Récupérer les fournisseurs pour le formulaire
try {
    $stmt_fournisseurs = $shop_pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
    $fournisseurs = $stmt_fournisseurs->fetchAll();
} catch (PDOException $e) {
    $fournisseurs = [];
}

// Récupérer les clients pour le formulaire  
try {
    $stmt_clients = $shop_pdo->query("SELECT id, nom, prenom FROM clients ORDER BY nom, prenom");
    $clients = $stmt_clients->fetchAll();
} catch (PDOException $e) {
    $clients = [];
}

// Récupérer les réparations pour le formulaire
try {
    $stmt_reparations = $shop_pdo->query("
        SELECT r.id, r.type_appareil, r.modele, c.nom, c.prenom 
        FROM reparations r 
        LEFT JOIN clients c ON r.client_id = c.id 
        WHERE r.statut NOT IN ('restitue', 'annule') 
        ORDER BY r.date_reception DESC
    ");
    $reparations = $stmt_reparations->fetchAll();
} catch (PDOException $e) {
    $reparations = [];
}

// Fonction pour obtenir le badge de statut
function getStatusBadge($statut) {
    $badges = [
        'en_attente' => '<span class="status-badge status-pending"><i class="fas fa-clock"></i>En attente</span>',
        'urgent' => '<span class="status-badge status-urgent"><i class="fas fa-exclamation-triangle"></i>Urgent</span>',
        'tres_urgent' => '<span class="status-badge status-critical"><i class="fas fa-fire"></i>Très urgent</span>',
        'commande' => '<span class="status-badge status-ordered"><i class="fas fa-shopping-cart"></i>Commandé</span>',
        'recue' => '<span class="status-badge status-received"><i class="fas fa-box"></i>Reçu</span>',
        'annulee' => '<span class="status-badge status-cancelled"><i class="fas fa-times"></i>Annulée</span>',
        'termine' => '<span class="status-badge status-completed"><i class="fas fa-check"></i>Terminé</span>',
        'utilise' => '<span class="status-badge status-used"><i class="fas fa-check-double"></i>Utilisé</span>',
        'a_retourner' => '<span class="status-badge status-return"><i class="fas fa-undo"></i>À retourner</span>'
    ];
    
    return isset($badges[$statut]) ? $badges[$statut] : '<span class="status-badge status-unknown">' . ucfirst($statut) . '</span>';
}

// Fonction pour obtenir le badge d'urgence
function getUrgenceBadge($urgence) {
    switch($urgence) {
        case 'urgent':
            return '<span class="urgence-badge urgence-urgent"><i class="fas fa-exclamation-triangle"></i>Urgent</span>';
        case 'tres_urgent':
            return '<span class="urgence-badge urgence-critical"><i class="fas fa-fire"></i>Très urgent</span>';
        default:
            return '';
    }
}
?>

<!-- Inclure le header OFFICIEL -->
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/night-mode-system.php'; ?>

<!-- Styles modernes sans Bootstrap pour tableau -->
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

/* Variables CSS */
:root {
    --day-bg-primary: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    --day-bg-animated: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff); /* Harmonisé avec index.php */
    --day-bg-card: rgba(255, 255, 255, 0.95);
    --day-text-primary: #1e293b;
    --day-text-secondary: #64748b;
    --day-border: rgba(148, 163, 184, 0.2);
    --day-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    --day-shadow-hover: 0 8px 30px rgba(0, 0, 0, 0.15);
    
    --night-bg-primary: linear-gradient(135deg, #0a0f1a 0%, #1e293b 50%, #0f172a 100%);
    --night-bg-card: rgba(15, 23, 42, 0.95);
    --night-text-primary: #f1f5f9;
    --night-text-secondary: #94a3b8;
    --night-border: rgba(0, 212, 255, 0.2);
    --night-shadow: 0 0 30px rgba(0, 212, 255, 0.1);
    --night-glow: 0 0 20px rgba(0, 212, 255, 0.3);
    
    --primary-blue: #3b82f6;
    --primary-cyan: #06b6d4;
    --success-green: #10b981;
    --warning-amber: #f59e0b;
    --error-red: #ef4444;
}

/* Mode Jour (par défaut) */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: var(--day-bg-animated) !important;
    background-size: 300% 300% !important;
    animation: gradientFlowDay 20s ease infinite !important;
    background-attachment: fixed !important;
    color: var(--day-text-primary);
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    line-height: 1.6;
    transition: all 0.4s ease;
    min-height: 100vh !important;
}

@keyframes gradientFlowDay {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Mode Nuit */

body.night-mode,
body.dark-mode {
    background: transparent !important; /* Transparent pour voir #animated-bg */
    animation: none !important;
    color: var(--night-text-primary);
    font-family: 'Orbitron', 'Inter', system-ui, sans-serif;
}

/* Container principal */
.main-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    min-height: 100vh;
}

/* Header de page */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 3rem;
    padding: 2rem;
    background: var(--day-bg-card);
    border-radius: 20px;
    box-shadow: var(--day-shadow);
    border: 1px solid var(--day-border);
    transition: all 0.4s ease;
}

body.night-mode .page-header {
    background: var(--night-bg-card);
    border: 1px solid var(--night-border);
    box-shadow: var(--night-shadow);
}

.page-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary-blue), var(--primary-cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

body.night-mode .page-title {
    text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
    -webkit-text-fill-color: #00d4ff;
}

/* Statistiques */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    padding: 2rem;
    background: var(--day-bg-card);
    border-radius: 16px;
    border: 1px solid var(--day-border);
    box-shadow: var(--day-shadow);
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    user-select: none;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-blue), var(--primary-cyan));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--day-shadow-hover);
}

.stat-card:hover::before {
    opacity: 1;
}

.stat-card:active {
    transform: translateY(-2px) scale(0.98);
}

.stat-card.active {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

body.night-mode .stat-card.active {
    border-color: #00d4ff;
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.3);
}

body.night-mode .stat-card {
    background: var(--night-bg-card);
    border: 1px solid var(--night-border);
    box-shadow: var(--night-shadow);
}

body.night-mode .stat-card:hover {
    box-shadow: var(--night-glow);
    border-color: rgba(0, 212, 255, 0.4);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    color: var(--primary-blue);
}

body.night-mode .stat-number {
    color: #00d4ff;
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
}

.stat-label {
    color: var(--day-text-secondary);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 0.9rem;
}

body.night-mode .stat-label {
    color: var(--night-text-secondary);
}

/* Filtres */
.filters-section {
    background: var(--day-bg-card);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--day-border);
    box-shadow: var(--day-shadow);
}

body.night-mode .filters-section {
    background: var(--night-bg-card);
    border: 1px solid var(--night-border);
    box-shadow: var(--night-shadow);
}

.filters-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 1rem;
    align-items: end;
}

.filters-grid-enhanced {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    align-items: end;
    margin-bottom: 1rem;
}

@media (max-width: 1200px) {
    .filters-grid-enhanced {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .filters-grid-enhanced {
        grid-template-columns: 1fr;
    }
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-weight: 600;
    color: var(--day-text-primary);
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

body.night-mode .filter-label {
    color: var(--night-text-primary);
}

.filter-input {
    padding: 0.75rem 1rem;
    border: 2px solid var(--day-border);
    border-radius: 12px;
    background: var(--day-bg-card);
    color: var(--day-text-primary);
    font-size: 1rem;
    transition: all 0.3s ease;
    outline: none;
}

.filter-input:focus {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

body.night-mode .filter-input {
    background: rgba(30, 41, 59, 0.8);
    border-color: var(--night-border);
    color: var(--night-text-primary);
}

body.night-mode .filter-input:focus {
    border-color: #00d4ff;
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
}

/* Effets futuristes pour les selects en mode nuit */
body.night-mode .filter-input[type="text"],
body.night-mode .filter-input[type="date"] {
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.8));
    border: 2px solid rgba(0, 212, 255, 0.3);
    color: #00d4ff;
    text-shadow: 0 0 8px rgba(0, 212, 255, 0.4);
    position: relative;
    overflow: hidden;
}

body.night-mode .filter-input[type="text"]::before,
body.night-mode .filter-input[type="date"]::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.2), transparent);
    transition: left 0.6s ease;
}

body.night-mode .filter-input[type="text"]:focus::before,
body.night-mode .filter-input[type="date"]:focus::before {
    left: 100%;
}

/* Selects futuristes en mode nuit */
body.night-mode select.filter-input {
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.9));
    border: 2px solid rgba(0, 212, 255, 0.4);
    color: #00d4ff;
    text-shadow: 0 0 6px rgba(0, 212, 255, 0.5);
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
    background-image: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    padding-right: 2.5rem;
}

/* Flèche personnalisée futuriste */
body.night-mode select.filter-input {
    background-image: 
        linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.9)),
        linear-gradient(45deg, transparent 50%, #00d4ff 50%),
        linear-gradient(-45deg, transparent 50%, #00d4ff 50%);
    background-position: 
        0 0,
        calc(100% - 20px) calc(50% - 2px),
        calc(100% - 16px) calc(50% - 2px);
    background-size: 
        100% 100%,
        4px 4px,
        4px 4px;
    background-repeat: no-repeat;
}

body.night-mode select.filter-input:hover {
    border-color: rgba(0, 212, 255, 0.7);
    box-shadow: 
        0 0 20px rgba(0, 212, 255, 0.3),
        inset 0 0 20px rgba(0, 212, 255, 0.1);
    transform: translateY(-1px);
}

body.night-mode select.filter-input:focus {
    border-color: #00d4ff;
    box-shadow: 
        0 0 30px rgba(0, 212, 255, 0.5),
        inset 0 0 30px rgba(0, 212, 255, 0.15),
        0 0 0 3px rgba(0, 212, 255, 0.3);
    outline: none;
}

/* Animation de scan futuriste */
body.night-mode select.filter-input::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #00d4ff, transparent);
    transition: left 0.8s ease;
    z-index: 1;
}

body.night-mode select.filter-input:focus::before {
    left: 100%;
}

/* Options du select futuristes */
body.night-mode select.filter-input option {
    background: rgba(15, 23, 42, 0.98);
    color: #00d4ff;
    padding: 0.75rem;
    border: none;
    text-shadow: 0 0 4px rgba(0, 212, 255, 0.3);
}

body.night-mode select.filter-input option:hover,
body.night-mode select.filter-input option:checked {
    background: rgba(0, 212, 255, 0.2);
    color: #ffffff;
    text-shadow: 0 0 8px rgba(0, 212, 255, 0.8);
}

/* Effet de pulsation pour les selects actifs */
body.night-mode select.filter-input.active {
    animation: futuristicPulse 2s ease-in-out infinite;
}

@keyframes futuristicPulse {
    0%, 100% {
        border-color: rgba(0, 212, 255, 0.4);
        box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
    }
    50% {
        border-color: rgba(0, 212, 255, 0.8);
        box-shadow: 0 0 40px rgba(0, 212, 255, 0.6);
    }
}

/* Effet holographique sur les labels */
body.night-mode .filter-label {
    color: #00d4ff;
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.6);
    position: relative;
}

body.night-mode .filter-label::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(0, 212, 255, 0.1) 50%, transparent 70%);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

body.night-mode .filter-group:hover .filter-label::before {
    opacity: 1;
}

/* Effet de circuit électronique */
body.night-mode .filter-group {
    position: relative;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

body.night-mode .filter-group::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        linear-gradient(90deg, transparent 48%, rgba(0, 212, 255, 0.1) 50%, transparent 52%),
        linear-gradient(0deg, transparent 48%, rgba(0, 212, 255, 0.1) 50%, transparent 52%);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
    border-radius: 8px;
}

body.night-mode .filter-group:hover::before {
    opacity: 1;
}

/* Effet de scan horizontal sur focus */
body.night-mode .filter-input:focus {
    position: relative;
    overflow: hidden;
}

body.night-mode .filter-input:focus::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, 
        transparent 0%, 
        rgba(0, 212, 255, 0.1) 25%, 
        rgba(0, 212, 255, 0.3) 50%, 
        rgba(0, 212, 255, 0.1) 75%, 
        transparent 100%);
    animation: scanEffect 1.5s ease-in-out;
    pointer-events: none;
}

@keyframes scanEffect {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}

/* Particules flottantes pour les selects ouverts */
body.night-mode select.filter-input:focus {
    position: relative;
}

body.night-mode select.filter-input:focus::after {
    content: '◦ ◦ ◦';
    position: absolute;
    top: -10px;
    right: 10px;
    color: rgba(0, 212, 255, 0.6);
    font-size: 8px;
    animation: floatingParticles 2s ease-in-out infinite;
    pointer-events: none;
}

@keyframes floatingParticles {
    0%, 100% {
        transform: translateY(0);
        opacity: 0.6;
    }
    50% {
        transform: translateY(-5px);
        opacity: 1;
    }
}

/* Tableau personnalisé sans Bootstrap */
.table-container {
    background: var(--day-bg-card);
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid var(--day-border);
    box-shadow: var(--day-shadow);
    margin-bottom: 2rem;
}

body.night-mode .table-container {
    background: var(--night-bg-card);
    border: 1px solid var(--night-border);
    box-shadow: var(--night-shadow);
}

.custom-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
}

.custom-table thead {
    background: linear-gradient(135deg, var(--primary-blue), var(--primary-cyan));
    color: white;
}

body.night-mode .custom-table thead {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border-bottom: 2px solid rgba(0, 212, 255, 0.3);
}

.custom-table th {
    padding: 1.5rem 1rem;
    font-weight: 600;
    text-align: left;
    letter-spacing: 0.025em;
    position: sticky;
    top: 0;
    z-index: 10;
}

body.night-mode .custom-table th {
    color: #00d4ff;
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
}

.custom-table th i {
    margin-right: 0.5rem;
}

.custom-table td {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid var(--day-border);
    vertical-align: top;
    transition: all 0.3s ease;
}

body.night-mode .custom-table td {
    border-bottom: 1px solid var(--night-border);
}

.custom-table tr {
    transition: all 0.3s ease;
}

.custom-table tbody tr:hover {
    background: rgba(59, 130, 246, 0.05);
    transform: scale(1.01);
}

body.night-mode .custom-table tbody tr:hover {
    background: rgba(0, 212, 255, 0.1);
    box-shadow: inset 0 0 20px rgba(0, 212, 255, 0.1);
}

.custom-table tbody tr:last-child td {
    border-bottom: none;
}

/* Colonnes spécifiques */
.col-reference {
    width: 10%;
    font-weight: 600;
}

.col-quantite {
    width: 8%;
    font-weight: 600;
    text-align: center;
}

.col-piece {
    width: 18%;
    font-size: 1.05rem;
    line-height: 1.4;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    font-feature-settings: "kern" 1, "liga" 1;
}

.col-client {
    width: 14%;
    font-size: 1.05rem;
    line-height: 1.4;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    font-feature-settings: "kern" 1, "liga" 1;
}

.col-fournisseur {
    width: 11%;
}

.col-statut {
    width: 11%;
}

.col-date {
    width: 11%;
}

.col-prix {
    width: 10%;
}

.col-actions {
    width: 7%;
}

/* Badges de statut sans Bootstrap */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    transition: all 0.3s ease;
}

.status-pending {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #92400e;
}

.status-urgent {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
}

.status-critical {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: white;
    animation: pulse-critical 2s infinite;
}

.status-ordered {
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    color: white;
}

.status-received {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.status-completed {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.status-used {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
}

.status-cancelled {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    color: white;
}

.status-return {
    background: linear-gradient(135deg, #374151, #1f2937);
    color: white;
}

.status-unknown {
    background: linear-gradient(135deg, #d1d5db, #9ca3af);
    color: #374151;
}

/* Badges d'urgence */
.urgence-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.25rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

.urgence-urgent {
    background: rgba(251, 191, 36, 0.2);
    color: #d97706;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.urgence-critical {
    background: rgba(239, 68, 68, 0.2);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.3);
    animation: pulse-urgent 1.5s infinite;
}

/* Animations */
@keyframes pulse-critical {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(220, 38, 38, 0);
    }
}

@keyframes pulse-urgent {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(251, 191, 36, 0.6);
    }
    50% {
        box-shadow: 0 0 0 8px rgba(251, 191, 36, 0);
    }
}

/* Boutons d'action sans Bootstrap */
.actions-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    position: relative;
    overflow: hidden;
}

.action-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
}

.action-btn:hover::before {
    transform: translateX(100%);
}

.action-btn-edit {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.action-btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
}

.action-btn-delete {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.action-btn-delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
}

.action-btn-google:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(234, 67, 53, 0.4);
}

/* ========================================
   TOOLTIPS SIMPLES POUR TOUS LES BOUTONS
   AU-DESSUS DU BOUTON - STYLE PROPRE
   ======================================== */

/* Pour les boutons action-btn avec data-tooltip */
.action-btn[data-tooltip] {
    position: relative;
}

/* Contenu du tooltip - utilise un data-attribute personnalisé */
.action-btn[data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%; /* AU-DESSUS */
    left: 50%;
    transform: translateX(-50%) translateY(-10px);
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s ease;
    z-index: 99999;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* Afficher au survol */
.action-btn[data-tooltip]:hover::after {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

/* DÉSACTIVATION COMPLÈTE DES TOOLTIPS POUR CES BOUTONS SPÉCIFIQUES */
.action-btn-edit::after,
.action-btn-delete::after,
.action-btn-google::after,
a.action-btn::after {
    display: none !important;
    content: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
}

/* Pour les boutons filter-btn avec data-tooltip */
.filter-btn[data-tooltip] {
    position: relative;
}

.filter-btn[data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%; /* AU-DESSUS */
    left: 50%;
    transform: translateX(-50%) translateY(-10px);
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s ease;
    z-index: 99999;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.filter-btn[data-tooltip]:hover::after {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

/* Mode nuit - tooltips avec dégradé */
body.night-mode .action-btn[data-tooltip]::after,
body.night-mode .filter-btn[data-tooltip]::after {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.95), rgba(37, 99, 235, 0.95));
    border: 1px solid rgba(59, 130, 246, 0.5);
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.4);
}

/* Tooltips génériques pour div et span */
div[data-tooltip],
span[data-tooltip] {
    position: relative;
}

div[data-tooltip]::after,
span[data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%; /* AU-DESSUS */
    left: 50%;
    transform: translateX(-50%) translateY(-10px);
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s ease;
    z-index: 99999;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

div[data-tooltip]:hover::after,
span[data-tooltip]:hover::after {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

/* Mode nuit pour div et span */
body.night-mode div[data-tooltip]::after,
body.night-mode span[data-tooltip]::after {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.95), rgba(37, 99, 235, 0.95));
    border: 1px solid rgba(59, 130, 246, 0.5);
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.4);
}

/* Bouton principal sans Bootstrap */
.primary-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, var(--primary-blue), var(--primary-cyan));
    color: white;
    border: none;
    border-radius: 16px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    position: relative;
    overflow: hidden;
}

.primary-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
}

.primary-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(59, 130, 246, 0.4);
}

.primary-btn:hover::before {
    transform: translateX(100%);
}

body.night-mode .primary-btn {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid rgba(0, 212, 255, 0.3);
    color: #00d4ff;
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
}

body.night-mode .primary-btn:hover {
    box-shadow: 0 0 30px rgba(0, 212, 255, 0.4);
    border-color: rgba(0, 212, 255, 0.6);
}

/* État vide */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--day-text-secondary);
}

body.night-mode .empty-state {
    color: var(--night-text-secondary);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--day-text-primary);
}

body.night-mode .empty-state h3 {
    color: var(--night-text-primary);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .main-container {
        padding: 1rem;
    }
    
    .page-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .custom-table {
        font-size: 0.85rem;
    }
    
    .custom-table th,
    .custom-table td {
        padding: 0.75rem 0.5rem;
    }
    
    .actions-group {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
}

/* Particules mode nuit */
body.night-mode .particles-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

body.night-mode .particle {
    position: absolute;
    width: 2px;
    height: 2px;
    background: #00d4ff;
    border-radius: 50%;
    opacity: 0.6;
    animation: float 6s ease-in-out infinite;
    box-shadow: 0 0 6px rgba(0, 212, 255, 0.8);
}

@keyframes float {
    0%, 100% {
        transform: translateY(0) translateX(0);
        opacity: 0.6;
    }
    50% {
        transform: translateY(-20px) translateX(10px);
        opacity: 1;
    }
}

/* Mode sombre spécifique */
body.night-mode {
    --webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

body.night-mode .reference-text {
    color: #00d4ff;
    font-weight: 700;
    text-shadow: 0 0 8px rgba(0, 212, 255, 0.3);
}

body.night-mode .price-text {
    color: #10b981;
    font-weight: 600;
    text-shadow: 0 0 6px rgba(16, 185, 129, 0.4);
}

body.night-mode .date-text {
    color: #a3a3a3;
}

body.night-mode .client-text {
    color: #e5e7eb;
}

body.night-mode .fournisseur-badge {
    background: rgba(30, 41, 59, 0.8);
    color: #00d4ff;
    padding: 0.3rem 0.8rem;
    border-radius: 12px;
    border: 1px solid rgba(0, 212, 255, 0.2);
}

/* Styles pour les actions de filtres */
.filter-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: rgba(59, 130, 246, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(59, 130, 246, 0.1);
}

body.night-mode .filter-actions {
    background: rgba(0, 212, 255, 0.05);
    border-color: rgba(0, 212, 255, 0.1);
}

.filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

/* Styles par défaut pour mode jour */
body:not(.night-mode) .filter-btn-reset {
    background: linear-gradient(135deg, #6b7280, #4b5563) !important;
    color: white !important;
    border: none !important;
}

body:not(.night-mode) .filter-btn-export {
    background: linear-gradient(135deg, #10b981, #059669) !important;
    color: white !important;
    border: none !important;
}

body:not(.night-mode) .filter-btn-bulk-toggle {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed) !important;
    color: white !important;
    border: none !important;
}

body:not(.night-mode) .filter-btn-toggle {
    background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
    color: white !important;
    border: none !important;
}

body:not(.night-mode) .filter-btn-toggle.active {
    background: linear-gradient(135deg, #059669, #047857) !important;
}

body:not(.night-mode) .stat-card {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
    color: #1f2937 !important;
}

/* Amélioration de la lisibilité du tableau en mode jour */
body:not(.night-mode) .custom-table {
    font-size: 1.05rem !important;
    color: #1f2937 !important;
}

body:not(.night-mode) .custom-table td {
    color: #1f2937 !important;
    font-weight: 500 !important;
}

body:not(.night-mode) .custom-table .col-quantite {
    color: #111827 !important;
    font-weight: 700 !important;
}

body:not(.night-mode) .custom-table .col-reference {
    color: #111827 !important;
    font-weight: 700 !important;
}

body:not(.night-mode) .custom-table .col-piece {
    color: #111827 !important;
    font-weight: 600 !important;
    font-size: 1.1rem !important;
}

body:not(.night-mode) .custom-table .col-client {
    color: #111827 !important;
    font-weight: 600 !important;
    font-size: 1.1rem !important;
}

body:not(.night-mode) .custom-table .col-fournisseur {
    color: #374151 !important;
    font-weight: 500 !important;
}

body:not(.night-mode) .custom-table .col-statut {
    color: #111827 !important;
    font-weight: 600 !important;
}

body:not(.night-mode) .custom-table .col-date {
    color: #374151 !important;
    font-weight: 500 !important;
}

body:not(.night-mode) .custom-table .col-actions {
    color: #111827 !important;
}

/* Amélioration des badges et éléments du tableau en mode jour */
body:not(.night-mode) .status-badge {
    font-weight: 600 !important;
    font-size: 0.9rem !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
}

body:not(.night-mode) .reference-text {
    color: #1e40af !important;
    font-weight: 700 !important;
    text-shadow: none !important;
}

body:not(.night-mode) .piece-name {
    color: #111827 !important;
    font-weight: 600 !important;
}

body:not(.night-mode) .piece-description {
    color: #374151 !important;
    font-weight: 500 !important;
}

body:not(.night-mode) .client-name {
    color: #111827 !important;
    font-weight: 600 !important;
}

body:not(.night-mode) .client-phone {
    color: #6b7280 !important;
    font-weight: 500 !important;
}

body:not(.night-mode) .fournisseur-name {
    color: #374151 !important;
    font-weight: 500 !important;
}

body:not(.night-mode) .date-text {
    color: #374151 !important;
    font-weight: 500 !important;
}

/* Amélioration du contraste pour les éléments interactifs */
body:not(.night-mode) .action-btn {
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    font-weight: 600 !important;
}

/* Correction des labels de filtres en mode jour */
body:not(.night-mode) .filter-label {
    color: #111827 !important;
    font-weight: 600 !important;
    font-size: 0.95rem !important;
    text-shadow: none !important;
}

body:not(.night-mode) .filter-label i {
    color: #374151 !important;
    margin-right: 0.5rem !important;
}

/* Correction des champs de filtres en mode jour */
body:not(.night-mode) .filter-input {
    color: #111827 !important;
    background: #ffffff !important;
    border: 1px solid #d1d5db !important;
    font-weight: 500 !important;
}

body:not(.night-mode) .filter-input::placeholder {
    color: #6b7280 !important;
}

body:not(.night-mode) .filter-input:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
}

/* Correction des groupes de filtres en mode jour */
body:not(.night-mode) .filter-group {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
}

body:not(.night-mode) .filters-section {
    background: #f9fafb !important;
    border: 1px solid #e5e7eb !important;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05) !important;
}

/* Correction du modal en mode jour */
body:not(.night-mode) .modal-content {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1) !important;
}

body:not(.night-mode) .modal-header {
    background: #f8fafc !important;
    border-bottom: 1px solid #e5e7eb !important;
}

body:not(.night-mode) .modal-title,
body:not(.night-mode) #commandeStatutModalLabel {
    color: #000000 !important;
    font-weight: 600 !important;
}

body:not(.night-mode) .modal-subtitle,
body:not(.night-mode) .modal-header .modal-subtitle {
    color: #000000 !important;
    font-weight: 500 !important;
}

/* Force spécifique pour les textes du modal */
body:not(.night-mode) .modal-header h5,
body:not(.night-mode) .modal-header p,
body:not(.night-mode) .modal-title-section h5,
body:not(.night-mode) .modal-title-section p {
    color: #000000 !important;
}

/* Force ultra-spécifique pour le modal de statut */
body:not(.night-mode) #commandeStatutModal .modal-title,
body:not(.night-mode) #commandeStatutModal .modal-subtitle,
body:not(.night-mode) #commandeStatutModal h5,
body:not(.night-mode) #commandeStatutModal p,
body:not(.night-mode) #commandeStatutModal .modal-header *,
body:not(.night-mode) #commandeStatutModal .modal-title-section * {
    color: #000000 !important;
}

/* Force avec sélecteurs d'attributs */
body:not(.night-mode) [id="commandeStatutModalLabel"],
body:not(.night-mode) #commandeStatutModal [class*="modal-subtitle"],
body:not(.night-mode) #commandeStatutModal [class*="modal-title"] {
    color: #000000 !important;
}

body:not(.night-mode) .modal-body {
    background: #ffffff !important;
    color: #111827 !important;
}

body:not(.night-mode) .modern-task-title {
    color: #111827 !important;
    font-weight: 600 !important;
}

body:not(.night-mode) .task-subtitle {
    color: #374151 !important;
    font-weight: 500 !important;
}

body:not(.night-mode) .priority-label {
    color: #6b7280 !important;
    font-weight: 500 !important;
}

body:not(.night-mode) .status-option {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    color: #111827 !important;
}

body:not(.night-mode) .status-option:hover {
    background: #f3f4f6 !important;
    border-color: #3b82f6 !important;
}

body:not(.night-mode) .btn-close {
    color: #6b7280 !important;
    filter: none !important;
}

body:not(.night-mode) .action-icon {
    color: #3b82f6 !important;
    background: rgba(59, 130, 246, 0.1) !important;
}

.filter-btn-reset {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    color: white;
}

.filter-btn-reset:hover {
    background: linear-gradient(135deg, #4b5563, #374151);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(107, 114, 128, 0.3);
}

.filter-btn-export {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.filter-btn-export:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
}

body.night-mode .filter-btn-reset {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid rgba(0, 212, 255, 0.2);
    color: #00d4ff;
}

body.night-mode .filter-btn-reset:hover {
    border-color: rgba(0, 212, 255, 0.4);
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.2);
}

body.night-mode .filter-btn-export {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #10b981;
}

body.night-mode .filter-btn-export:hover {
    border-color: rgba(16, 185, 129, 0.5);
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
}

.filter-results-count {
    font-size: 0.9rem;
    color: var(--day-text-secondary);
    font-weight: 500;
}

body.night-mode .filter-results-count {
    color: var(--night-text-secondary);
}

/* Amélioration des inputs de date */
.filter-input[type="date"] {
    position: relative;
    cursor: pointer;
}

.filter-input[type="date"]::-webkit-calendar-picker-indicator {
    background: transparent;
    bottom: 0;
    color: transparent;
    cursor: pointer;
    height: auto;
    left: 0;
    position: absolute;
    right: 0;
    top: 0;
    width: auto;
}

body.night-mode .filter-input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}

/* Animation pour les filtres actifs */
.filter-input.active {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

body.night-mode .filter-input.active {
    border-color: #00d4ff;
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
}

/* Responsive pour les actions de filtres */
@media (max-width: 768px) {
    .filter-actions {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .filter-btn {
        width: 100%;
        justify-content: center;
    }
}

/* Styles pour la modification en lot */
.filter-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: rgba(59, 130, 246, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(59, 130, 246, 0.1);
    flex-wrap: wrap;
    gap: 1rem;
}

.filter-actions-left,
.filter-actions-center,
.filter-actions-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.filter-actions-center {
    flex: 1;
    justify-content: center;
}

.bulk-edit-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1.5rem;
    background: rgba(16, 185, 129, 0.1);
    border: 2px solid rgba(16, 185, 129, 0.3);
    border-radius: 12px;
    animation: bulkEditSlideIn 0.3s ease-out;
}

@keyframes bulkEditSlideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

body.night-mode .bulk-edit-controls {
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.4);
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
}

.bulk-selected-count {
    font-weight: 600;
    color: #059669;
    font-size: 0.9rem;
    min-width: 120px;
}

body.night-mode .bulk-selected-count {
    color: #10b981;
    text-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
}

.bulk-status-select {
    padding: 0.5rem 1rem;
    border: 2px solid rgba(16, 185, 129, 0.3);
    border-radius: 8px;
    background: white;
    color: #059669;
    font-weight: 500;
    min-width: 200px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.bulk-status-select:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    outline: none;
}

body.night-mode .bulk-status-select {
    background: rgba(15, 23, 42, 0.9);
    border-color: rgba(16, 185, 129, 0.4);
    color: #10b981;
    text-shadow: 0 0 6px rgba(16, 185, 129, 0.3);
}

body.night-mode .bulk-status-select:focus {
    border-color: #10b981;
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
}

.filter-btn-bulk-apply {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
}

.filter-btn-bulk-apply:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.filter-btn-bulk-cancel {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    border: none;
}

.filter-btn-bulk-cancel:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
}

.filter-btn-bulk-toggle {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
    border: none;
}

.filter-btn-bulk-toggle:hover {
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
}

.filter-btn-bulk-toggle.active {
    background: linear-gradient(135deg, #059669, #047857);
}

.filter-btn-toggle {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: white;
    border: none;
}

.filter-btn-toggle:hover {
    background: linear-gradient(135deg, #4f46e5, #4338ca);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
}

.filter-btn-toggle.active {
    background: linear-gradient(135deg, #059669, #047857);
}

body.night-mode .filter-btn-toggle {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 2px solid rgba(99, 102, 241, 0.4);
    color: #6366f1;
}

body.night-mode .filter-btn-toggle:hover {
    border-color: rgba(99, 102, 241, 0.6);
    box-shadow: 0 0 25px rgba(99, 102, 241, 0.3);
}

body.night-mode .filter-btn-toggle.active {
    border-color: rgba(16, 185, 129, 0.6);
    color: #10b981;
}

body.night-mode .filter-btn-bulk-apply {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 2px solid rgba(16, 185, 129, 0.4);
    color: #10b981;
}

body.night-mode .filter-btn-bulk-apply:hover {
    border-color: rgba(16, 185, 129, 0.6);
    box-shadow: 0 0 25px rgba(16, 185, 129, 0.3);
}

body.night-mode .filter-btn-bulk-cancel {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 2px solid rgba(239, 68, 68, 0.4);
    color: #ef4444;
}

body.night-mode .filter-btn-bulk-cancel:hover {
    border-color: rgba(239, 68, 68, 0.6);
    box-shadow: 0 0 25px rgba(239, 68, 68, 0.3);
}

body.night-mode .filter-btn-bulk-toggle {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 2px solid rgba(139, 92, 246, 0.4);
    color: #8b5cf6;
}

body.night-mode .filter-btn-bulk-toggle:hover {
    border-color: rgba(139, 92, 246, 0.6);
    box-shadow: 0 0 25px rgba(139, 92, 246, 0.3);
}

body.night-mode .filter-btn-bulk-toggle.active {
    border-color: rgba(16, 185, 129, 0.6);
    color: #10b981;
}

/* Styles pour les checkboxes de sélection */
.col-bulk-select {
    width: 50px;
    text-align: center;
    padding: 0.75rem 0.5rem;
}

.bulk-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #10b981;
    transform: scale(1.2);
}

body.night-mode .bulk-checkbox {
    accent-color: #00d4ff;
    filter: brightness(1.2);
}

/* Animation pour les lignes sélectionnées */
.commande-row.selected {
    background: rgba(16, 185, 129, 0.1);
    border-left: 4px solid #10b981;
    transform: translateX(2px);
    transition: all 0.3s ease;
}

body.night-mode .commande-row.selected {
    background: rgba(0, 212, 255, 0.1);
    border-left-color: #00d4ff;
    box-shadow: 0 0 15px rgba(0, 212, 255, 0.2);
}

/* Styles pour les lignes cliquables en mode bulk edit */
.commande-row.bulk-mode {
    cursor: pointer !important;
    transition: all 0.2s ease;
}

.commande-row.bulk-mode:hover {
    background: rgba(16, 185, 129, 0.05);
    transform: translateX(1px);
}

body.night-mode .commande-row.bulk-mode:hover {
    background: rgba(0, 212, 255, 0.05);
    box-shadow: 0 0 10px rgba(0, 212, 255, 0.1);
}

.commande-row.bulk-mode.selected:hover {
    background: rgba(16, 185, 129, 0.15);
}

body.night-mode .commande-row.bulk-mode.selected:hover {
    background: rgba(0, 212, 255, 0.15);
}

/* Animation pour masquer/afficher les filtres */
.filters-section {
    transition: all 0.4s ease;
    overflow: hidden;
    max-height: 500px;
    opacity: 1;
}

.filters-section.hidden {
    max-height: 0;
    opacity: 0;
    margin-bottom: 0;
    padding-top: 0;
    padding-bottom: 0;
}

.filters-grid-enhanced {
    transition: all 0.3s ease;
}

.filters-section.hidden .filters-grid-enhanced {
    transform: translateY(-20px);
}

/* Responsive pour la modification en lot */
@media (max-width: 1200px) {
    .filter-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-actions-left,
    .filter-actions-center,
    .filter-actions-right {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .bulk-edit-controls {
        flex-wrap: wrap;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    /* Correction de l'espacement en haut de page sur mobile */
    body {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }

    .main-container {
        padding-top: 1rem !important;
        margin-top: 0 !important;
    }

    .bulk-edit-controls {
        flex-direction: row;
        gap: 0.5rem;
        padding: 0.5rem;
        flex-wrap: nowrap;
        align-items: center;
        width: 100%;
    }
    
    .bulk-edit-controls .filter-btn {
        font-size: 0 !important;
        padding: 0.5rem;
        flex: 0 0 auto;
    }

    .bulk-edit-controls .filter-btn i {
        font-size: 1.2rem;
        margin: 0 !important;
    }
    
    .bulk-status-select {
        min-width: 0;
        width: auto;
        flex: 1;
        font-size: 0.85rem;
        height: 38px;
    }
    
    .bulk-selected-count {
        display: none; /* Cache le compteur sur mobile pour gagner de la place */
    }

    /* Optimisation des boutons de filtres sur mobile */
    /* Optimisation GLOBALE des actions de filtre sur mobile */
    .filter-actions {
        display: none !important; /* Masquer les boutons sur mobile comme demandé */
    }

    /* Activation du scroll horizontal pour le tableau */
    .table-container {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch; /* Scroll fluide sur iOS */
    }
    
    .custom-table {
        min-width: 900px; /* Force la largeur pour activer le scroll */
    }
}

/* Colonne Pièce en mode nuit - texte blanc pour meilleure visibilité */
body.night-mode .col-piece {
    color: #ffffff !important;
}

body.night-mode .col-piece div {
    color: #ffffff !important;
}

body.night-mode .col-piece div[style*="color: var(--day-text-secondary)"] {
    color: #b0b0b0 !important;
}

body.night-mode .col-piece div[style*="color: var(--primary-blue)"] {
    color: #00d4ff !important;
}

/* ========================================
   BOUTONS SMS DANS LA COLONNE CLIENT
   ======================================== */
.sms-buttons-wrapper {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    margin-top: 0.5rem;
}

.btn-sms-notification,
.btn-sms-retard {
    padding: 0.4rem 0.6rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    position: relative; /* Pour le tooltip */
}

/* Tooltips personnalisés */
.btn-sms-notification::before,
.btn-sms-retard::before {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(-8px);
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s ease;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* Flèche du tooltip */
.btn-sms-notification::after,
.btn-sms-retard::after {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(-2px);
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid rgba(0, 0, 0, 0.9);
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s ease;
    z-index: 1000;
}

/* Afficher les tooltips au survol */
.btn-sms-notification:hover::before,
.btn-sms-notification:hover::after,
.btn-sms-retard:hover::before,
.btn-sms-retard:hover::after {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

/* Tooltip spécifique mode nuit */
body.night-mode .btn-sms-notification::before,
body.night-mode .btn-sms-retard::before {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.95), rgba(5, 150, 105, 0.95));
    border: 1px solid rgba(16, 185, 129, 0.5);
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
}

body.night-mode .btn-sms-retard::before {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.95), rgba(217, 119, 6, 0.95));
    border: 1px solid rgba(245, 158, 11, 0.5);
    box-shadow: 0 0 20px rgba(245, 158, 11, 0.4);
}

body.night-mode .btn-sms-notification::after {
    border-top-color: rgba(16, 185, 129, 0.95);
}

body.night-mode .btn-sms-retard::after {
    border-top-color: rgba(245, 158, 11, 0.95);
}

.btn-sms-notification {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.btn-sms-notification:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.btn-sms-notification:active {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
}

.btn-sms-retard {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.btn-sms-retard:hover {
    background: linear-gradient(135deg, #d97706, #b45309);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.btn-sms-retard:active {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3);
}

/* États disabled pour les boutons SMS */
.btn-sms-notification:disabled,
.btn-sms-retard:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.btn-sms-notification:disabled:hover,
.btn-sms-retard:disabled:hover {
    transform: none;
    box-shadow: none;
}

.btn-sms-notification:disabled::before,
.btn-sms-notification:disabled::after,
.btn-sms-retard:disabled::before,
.btn-sms-retard:disabled::after {
    display: none;
}

/* Mode nuit pour les boutons SMS */
body.night-mode .btn-sms-notification {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.3), rgba(5, 150, 105, 0.3));
    border: 2px solid rgba(16, 185, 129, 0.6);
    color: #10b981;
}

body.night-mode .btn-sms-notification:hover {
    border-color: rgba(16, 185, 129, 0.8);
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.4), rgba(5, 150, 105, 0.4));
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
}

body.night-mode .btn-sms-retard {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.3), rgba(217, 119, 6, 0.3));
    border: 2px solid rgba(245, 158, 11, 0.6);
    color: #f59e0b;
}

body.night-mode .btn-sms-retard:hover {
    border-color: rgba(245, 158, 11, 0.8);
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.4), rgba(217, 119, 6, 0.4));
    box-shadow: 0 0 20px rgba(245, 158, 11, 0.4);
}

/* Animation de chargement pour les boutons SMS */
.btn-sms-notification.loading,
.btn-sms-retard.loading {
    pointer-events: none;
}

.btn-sms-notification.loading i,
.btn-sms-retard.loading i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .sms-buttons-wrapper {
        gap: 0.3rem;
    }
    
    .btn-sms-notification,
    .btn-sms-retard {
        padding: 0.3rem 0.5rem;
        font-size: 0.9rem;
        min-width: 32px;
        height: 32px;
    }
    
    /* Tooltips plus petits sur mobile */
    .btn-sms-notification::before,
    .btn-sms-retard::before {
        font-size: 0.75rem;
        padding: 0.4rem 0.6rem;
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

/* ====================================================================
   ANIMATED BACKGROUND FOR NIGHT MODE (copié de taches_moderne.php)
==================================================================== */
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
<!-- Animated Background for Night Mode -->
<div id="animated-bg"></div>

<!-- Contenu principal -->
<div class="main-container">
    <!-- En-tête de page -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-cogs fa-fw" style="margin-right: 12px; color: var(--day-primary);"></i>
            Gestion des Commandes
        </h1>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <?php
        $stats_en_attente = count(array_filter($commandes, fn($c) => $c['statut'] == 'en_attente'));
        $stats_urgent = count(array_filter($commandes, fn($c) => in_array($c['statut'], ['urgent', 'tres_urgent'])));
        $stats_commande = count(array_filter($commandes, fn($c) => $c['statut'] == 'commande'));
        $stats_recue = count(array_filter($commandes, fn($c) => $c['statut'] == 'recue'));
        $stats_a_retourner = count(array_filter($commandes, fn($c) => $c['statut'] == 'a_retourner'));
        ?>
        
        <div class="stat-card" data-status="en_attente" onclick="filterByStatus('en_attente')" title="Cliquer pour filtrer par statut">
            <div class="stat-number"><?= $stats_en_attente ?></div>
            <div class="stat-label">En Attente</div>
        </div>
        
        <div class="stat-card" data-status="commande" onclick="filterByStatus('commande')" title="Cliquer pour filtrer par statut">
            <div class="stat-number"><?= $stats_commande ?></div>
            <div class="stat-label">En Livraison</div>
        </div>
        
        <div class="stat-card" data-status="recue" onclick="filterByStatus('recue')" title="Cliquer pour filtrer par statut">
            <div class="stat-number"><?= $stats_recue ?></div>
            <div class="stat-label">Reçues</div>
        </div>
        
        <div class="stat-card" data-status="a_retourner" onclick="filterByStatus('a_retourner')" title="Cliquer pour filtrer par statut">
            <div class="stat-number"><?= $stats_a_retourner ?></div>
            <div class="stat-label">À retourner</div>
        </div>
    </div>

    <!-- Boutons d'action toujours visibles -->
    <div class="filter-actions">
        <div class="filter-actions-left">
            <button class="filter-btn filter-btn-toggle active" onclick="toggleFilters()" id="filterToggleBtn">
                <i class="fas fa-eye"></i>
                Afficher filtres
            </button>
            <button class="filter-btn filter-btn-reset" onclick="resetAllFilters()">
                <i class="fas fa-undo"></i>
                Réinitialiser
            </button>
            <button class="filter-btn filter-btn-export" onclick="exportFilteredData()">
                <i class="fas fa-download"></i>
                Exporter
            </button>
        </div>
        
        <div class="filter-actions-center">
            <div class="bulk-edit-controls" id="bulkEditControls" style="display: none;">
                <button class="filter-btn filter-btn-bulk-select-all" onclick="forceSelectAll()" style="margin-right: 8px;">
                    <i class="fas fa-check-double"></i>
                    Tout sélectionner
                </button>
                <span class="bulk-selected-count" id="bulkSelectedCount">0 sélectionnée(s)</span>
                <select class="bulk-status-select" id="bulkStatusSelect">
                    <option value="">Changer le statut vers...</option>
                    <option value="en_attente">En attente</option>
                    <option value="commande">Commandé</option>
                    <option value="recue">Reçu</option>
                    <option value="utilise">Utilisé</option>
                    <option value="annulee">Annulée</option>
                    <option value="a_retourner">À retourner</option>
                </select>
                <button class="filter-btn filter-btn-bulk-apply" onclick="applyBulkStatusChange()">
                    <i class="fas fa-check"></i>
                    Appliquer
                </button>
                <button class="filter-btn filter-btn-bulk-cancel" onclick="cancelBulkEdit()">
                    <i class="fas fa-times"></i>
                    Annuler
                </button>
            </div>
        </div>
        
        <div class="filter-actions-right">
            <button class="filter-btn filter-btn-bulk-toggle" onclick="toggleBulkEdit()" id="bulkToggleBtn">
                <i class="fas fa-edit"></i>
                Modification en lot
            </button>
            <div class="filter-results-count">
                <span id="resultsCount">Affichage de <?= count($commandes) ?> commande(s)</span>
            </div>
        </div>
    </div>

    <!-- Filtres masqués par défaut -->
    <div class="filters-section hidden" id="filtersSection">
        <div class="filters-grid-enhanced" id="filtersGrid">
            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-search"></i>
                    Recherche
                </label>
                <input type="text" class="filter-input" id="searchInput" placeholder="Rechercher une commande...">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-filter"></i>
                    Statut
                </label>
                <select class="filter-input" id="statusFilter">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente">En attente</option>
                    <option value="commande">Commandé</option>
                    <option value="recue">Reçu</option>
                    <option value="utilise">Utilisé</option>
                    <option value="annulee">Annulée</option>
                    <option value="a_retourner">À retourner</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-exclamation-triangle"></i>
                    Urgence
                </label>
                <select class="filter-input" id="urgenceFilter">
                    <option value="">Toutes urgences</option>
                    <option value="normal">Normal</option>
                    <option value="urgent">Urgent</option>
                    <option value="tres_urgent">Très urgent</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-truck"></i>
                    Fournisseur
                </label>
                <select class="filter-input" id="fournisseurFilter">
                    <option value="">Tous les fournisseurs</option>
                    <?php foreach ($fournisseurs as $fournisseur): ?>
                        <option value="<?= htmlspecialchars($fournisseur['nom']) ?>"><?= htmlspecialchars($fournisseur['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-calendar-week"></i>
                    Période
                </label>
                <select class="filter-input" id="periodeFilter">
                    <option value="">Toutes les périodes</option>
                    <option value="today">Aujourd'hui</option>
                    <option value="yesterday">Hier</option>
                    <option value="last_3_days">3 derniers jours</option>
                    <option value="this_week">Cette semaine</option>
                    <option value="last_week">Semaine dernière</option>
                    <option value="this_month">Ce mois</option>
                    <option value="last_month">Mois dernier</option>
                    <option value="last_30_days">30 derniers jours</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-calendar-alt"></i>
                    Date de création
                </label>
                <input type="date" class="filter-input" id="dateFilter" title="Filtrer par date de création">
            </div>
        </div>
    </div>

    <!-- Tableau des commandes (sans Bootstrap) -->
    <div class="table-container">
        <?php if (!empty($commandes)): ?>
        <table class="custom-table" id="commandesTable">
            <thead>
                <tr>
                    <th class="col-bulk-select" id="bulkSelectHeader" style="display: none;">
                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()" class="bulk-checkbox">
                    </th>
                    <th class="col-reference"><i class="fas fa-hashtag"></i>Référence</th>
                    <th class="col-quantite"><i class="fas fa-cubes"></i>Qté</th>
                    <th class="col-piece"><i class="fas fa-cog"></i>Pièce</th>
                    <th class="col-client"><i class="fas fa-user"></i>Client</th>
                    <th class="col-fournisseur"><i class="fas fa-truck"></i>Fournisseur</th>
                    <th class="col-statut"><i class="fas fa-info-circle"></i>Statut</th>
                    <th class="col-date"><i class="fas fa-calendar"></i>Date</th>
                    <th class="col-prix"><i class="fas fa-euro-sign"></i>Prix</th>
                    <th class="col-actions"><i class="fas fa-tools"></i>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commandes as $commande): ?>
                <tr class="commande-row" 
                    data-statut="<?= htmlspecialchars($commande['statut']) ?>"
                    data-urgence="<?= htmlspecialchars($commande['urgence']) ?>"
                    data-date-creation="<?= htmlspecialchars($commande['date_creation']) ?>"
                    data-commande-id="<?= $commande['id'] ?>"
                    data-code-barre="<?= htmlspecialchars($commande['code_barre'] ?? '') ?>"
                    onclick="handleRowClick(event, this)">
                    <td class="col-bulk-select bulk-select-cell" style="display: none;">
                        <input type="checkbox" class="bulk-checkbox row-checkbox" 
                               value="<?= $commande['id'] ?>" 
                               onchange="updateBulkSelection()">
                    </td>
                    <td class="col-reference">
                        <div class="reference-text"><?= htmlspecialchars($commande['reference']) ?></div>
                        <?= getUrgenceBadge($commande['urgence']) ?>
                    </td>
                    <td class="col-quantite">
                        <?php 
                        $qty = isset($commande['items_quantite']) ? $commande['items_quantite'] : (isset($commande['quantite']) ? $commande['quantite'] : 1); 
                        echo $qty > 0 ? $qty : 1;
                        ?>
                    </td>
                    <td class="col-piece">
                        <div style="font-weight: 600; margin-bottom: 0.25rem; font-size: 1.1rem;">
                            <?= htmlspecialchars($commande['nom_piece']) ?>
                        </div>
                        <?php if ($commande['code_barre']): ?>
                            <div style="font-size: 0.9rem; color: var(--day-text-secondary); margin-bottom: 0.25rem;">
                                Code: <?= htmlspecialchars($commande['code_barre']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($commande['type_appareil'] && $commande['modele']): ?>
                            <div style="font-size: 0.9rem; color: var(--primary-blue);">
                                <?= htmlspecialchars($commande['type_appareil']) ?> - <?= htmlspecialchars($commande['modele']) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="col-client">
                        <?php if ($commande['client_nom']): ?>
                            <div class="client-info-wrapper">
                                <div class="client-text" style="font-weight: 500; font-size: 1.1rem;">
                                    <?= htmlspecialchars($commande['client_nom']) ?> <?= htmlspecialchars($commande['client_prenom']) ?>
                                </div>
                                <?php if ($commande['telephone']): ?>
                                    <div style="font-size: 0.9rem; color: var(--day-text-secondary); margin-bottom: 0.5rem;">
                                        <?= htmlspecialchars($commande['telephone']) ?>
                                    </div>
                                    <!-- Boutons SMS -->
                                    <div class="sms-buttons-wrapper" style="display: flex; gap: 0.5rem;">
                                        <button class="btn-sms-notification" 
                                                data-commande-id="<?= $commande['id'] ?>"
                                                data-client-id="<?= $commande['client_id'] ?>"
                                                data-telephone="<?= htmlspecialchars($commande['telephone']) ?>"
                                                data-client-nom="<?= htmlspecialchars($commande['client_nom'] . ' ' . $commande['client_prenom']) ?>"
                                                data-reparation-id="<?= $commande['reparation_id'] ?>"
                                                data-tooltip="Commande Reçue"
                                                title="Envoyer SMS - Commande arrivée"
                                                onclick="event.stopPropagation();">
                                            <i class="fas fa-sms"></i>
                                        </button>
                                        <button class="btn-sms-retard" 
                                                data-commande-id="<?= $commande['id'] ?>"
                                                data-client-id="<?= $commande['client_id'] ?>"
                                                data-telephone="<?= htmlspecialchars($commande['telephone']) ?>"
                                                data-client-nom="<?= htmlspecialchars($commande['client_nom'] . ' ' . $commande['client_prenom']) ?>"
                                                data-reparation-id="<?= $commande['reparation_id'] ?>"
                                                data-tooltip="Retard de livraison"
                                                title="Envoyer SMS - Retard livraison"
                                                onclick="event.stopPropagation();">
                                            <i class="fas fa-clock"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span style="color: var(--day-text-secondary);">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-fournisseur">
                        <span class="fournisseur-badge"><?= htmlspecialchars($commande['fournisseur_nom']) ?></span>
                    </td>
                    <td class="col-statut">
                        <div onclick="handleStatusClick(event, <?= $commande['id'] ?>, '<?= $commande['statut'] ?>', '<?= htmlspecialchars($commande['reference']) ?>', '<?= htmlspecialchars($commande['nom_piece']) ?>')" style="cursor: pointer;" data-tooltip="Cliquer pour modifier le statut de la commande">
                            <?= getStatusBadge($commande['statut']) ?>
                        </div>
                    </td>
                    <td class="col-date">
                        <div class="date-text" style="font-weight: 500;">
                            <?= date('d/m/Y', strtotime($commande['date_creation'])) ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--day-text-secondary);">
                            <?= date('H:i', strtotime($commande['date_creation'])) ?>
                        </div>
                    </td>
                    <td class="col-prix">
                        <?php if ($commande['prix_estime']): ?>
                            <span class="price-text"><?= number_format($commande['prix_estime'], 2) ?> €</span>
                        <?php else: ?>
                            <span style="color: var(--day-text-secondary);">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-actions">
                        <div class="actions-group">
                            <button class="action-btn action-btn-edit" 
                                    onclick="handleActionClick(event, 'edit', <?= $commande['id'] ?>)"
                                    title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn action-btn-delete" 
                                    onclick="handleActionClick(event, 'delete', <?= $commande['id'] ?>)"
                                    title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                            <a href="https://www.google.com/search?q=<?= urlencode(htmlspecialchars($commande['nom_piece']) . ' ' . htmlspecialchars($commande['fournisseur_nom']) . ' ' . htmlspecialchars($commande['code_barre'] ?: '')) ?>" 
                               target="_blank" 
                               class="action-btn"
                               title="Rechercher sur Google"
                               style="background-color: #ea4335; color: white; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold;">
                                G
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Aucune commande de pièce</h3>
            <p>Commencez par ajouter votre première commande</p>
            <button class="primary-btn" onclick="openAddModal()">
                <i class="fas fa-plus"></i>
                Ajouter une commande
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Scripts -->
<script>
// Détection automatique du mode sombre
function createParticles() {
    // Supprimer les particules existantes
    removeParticles();
    
    const container = document.createElement('div');
    container.className = 'particles-container';
    document.body.appendChild(container);
    
    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 6 + 's';
        particle.style.animationDuration = (Math.random() * 4 + 4) + 's';
        container.appendChild(particle);
    }
}

// Supprimer les particules
function removeParticles() {
    const existing = document.querySelector('.particles-container');
    if (existing) {
        existing.remove();
    }
}

// Filtres de recherche améliorés
function initializeFilters() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const urgenceFilter = document.getElementById('urgenceFilter');
    const fournisseurFilter = document.getElementById('fournisseurFilter');
    const dateFilter = document.getElementById('dateFilter');
    const periodeFilter = document.getElementById('periodeFilter');
    const table = document.getElementById('commandesTable');
    
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr.commande-row');
    
    function filterTable() {
        const searchTerm = searchInput?.value.toLowerCase() || '';
        const statusValue = statusFilter?.value || '';
        const urgenceValue = urgenceFilter?.value || '';
        const fournisseurValue = fournisseurFilter?.value || '';
        const dateValue = dateFilter?.value || '';
        const periodeValue = periodeFilter?.value || '';
        
        let visibleCount = 0;
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const statut = row.getAttribute('data-statut');
            const urgence = row.getAttribute('data-urgence');
            const fournisseur = row.querySelector('.fournisseur-badge')?.textContent || '';
            const dateCreation = row.getAttribute('data-date-creation') || '';
            
            // Filtres de base
            const matchesSearch = !searchTerm || text.includes(searchTerm);
            const matchesStatus = !statusValue || statut === statusValue;
            const matchesUrgence = !urgenceValue || urgence === urgenceValue;
            const matchesFournisseur = !fournisseurValue || fournisseur.includes(fournisseurValue);
            
            // Filtre par date spécifique
            let matchesDate = true;
            if (dateValue && dateCreation) {
                const rowDate = new Date(dateCreation).toISOString().split('T')[0];
                matchesDate = rowDate === dateValue;
            }
            
            // Filtre par période
            let matchesPeriode = true;
            if (periodeValue && dateCreation) {
                const rowDate = new Date(dateCreation);
                const today = new Date();
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                
                switch (periodeValue) {
                    case 'today':
                        matchesPeriode = rowDate.toDateString() === today.toDateString();
                        break;
                    case 'yesterday':
                        matchesPeriode = rowDate.toDateString() === yesterday.toDateString();
                        break;
                    case 'last_3_days':
                        const threeDaysAgo = new Date(today);
                        threeDaysAgo.setDate(today.getDate() - 3);
                        matchesPeriode = rowDate >= threeDaysAgo;
                        break;
                    case 'this_week':
                        const startOfWeek = new Date(today);
                        startOfWeek.setDate(today.getDate() - today.getDay());
                        matchesPeriode = rowDate >= startOfWeek;
                        break;
                    case 'last_week':
                        const startOfLastWeek = new Date(today);
                        startOfLastWeek.setDate(today.getDate() - today.getDay() - 7);
                        const endOfLastWeek = new Date(startOfLastWeek);
                        endOfLastWeek.setDate(startOfLastWeek.getDate() + 6);
                        matchesPeriode = rowDate >= startOfLastWeek && rowDate <= endOfLastWeek;
                        break;
                    case 'this_month':
                        matchesPeriode = rowDate.getMonth() === today.getMonth() && 
                                       rowDate.getFullYear() === today.getFullYear();
                        break;
                    case 'last_month':
                        const lastMonth = new Date(today);
                        lastMonth.setMonth(today.getMonth() - 1);
                        matchesPeriode = rowDate.getMonth() === lastMonth.getMonth() && 
                                       rowDate.getFullYear() === lastMonth.getFullYear();
                        break;
                    case 'last_30_days':
                        const thirtyDaysAgo = new Date(today);
                        thirtyDaysAgo.setDate(today.getDate() - 30);
                        matchesPeriode = rowDate >= thirtyDaysAgo;
                        break;
                }
            }
            
            const isVisible = matchesSearch && matchesStatus && matchesUrgence && 
                            matchesFournisseur && matchesDate && matchesPeriode;
            
            if (isVisible) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Mettre à jour le compteur
        updateResultsCount(visibleCount);
        
        // Marquer les filtres actifs
        markActiveFilters();
    }
    
    window.markActiveFilters = function() {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const urgenceFilter = document.getElementById('urgenceFilter');
        const fournisseurFilter = document.getElementById('fournisseurFilter');
        const dateFilter = document.getElementById('dateFilter');
        const periodeFilter = document.getElementById('periodeFilter');
        
        [searchInput, statusFilter, urgenceFilter, fournisseurFilter, dateFilter, periodeFilter].forEach(filter => {
            if (filter && filter.value) {
                filter.classList.add('active');
            } else if (filter) {
                filter.classList.remove('active');
            }
        });
    }
    
    // Ajouter les écouteurs d'événements
    searchInput?.addEventListener('input', filterTable);
    statusFilter?.addEventListener('change', filterTable);
    urgenceFilter?.addEventListener('change', filterTable);
    fournisseurFilter?.addEventListener('change', filterTable);
    dateFilter?.addEventListener('change', filterTable);
    periodeFilter?.addEventListener('change', filterTable);
    
    // Synchroniser les filtres de date et période
    dateFilter?.addEventListener('change', function() {
        if (this.value && periodeFilter) {
            periodeFilter.value = '';
        }
    });
    
    periodeFilter?.addEventListener('change', function() {
        if (this.value && dateFilter) {
            dateFilter.value = '';
        }
    });
}

// Mettre à jour le compteur de résultats
function updateResultsCount(count) {
    const resultsCount = document.getElementById('resultsCount');
    if (resultsCount) {
        resultsCount.textContent = `Affichage de ${count} commande(s)`;
    }
}

// Réinitialiser tous les filtres
function resetAllFilters() {
    const filters = [
        'searchInput', 'statusFilter', 'urgenceFilter', 
        'fournisseurFilter', 'dateFilter', 'periodeFilter'
    ];
    
    filters.forEach(filterId => {
        const filter = document.getElementById(filterId);
        if (filter) {
            filter.value = '';
            filter.classList.remove('active');
        }
    });
    
    // Réinitialiser les cartes de statistiques
    resetStatCardFilter();
    
    // Réafficher toutes les lignes
    const table = document.getElementById('commandesTable');
    if (table) {
        const rows = table.querySelectorAll('tbody tr.commande-row');
        rows.forEach(row => {
            row.style.display = '';
        });
        updateResultsCount(rows.length);
    }
}

// Exporter les données filtrées
function exportFilteredData() {
    const table = document.getElementById('commandesTable');
    if (!table) return;
    
    const visibleRows = Array.from(table.querySelectorAll('tbody tr.commande-row'))
        .filter(row => row.style.display !== 'none');
    
    if (visibleRows.length === 0) {
        alert('Aucune donnée à exporter');
        return;
    }
    
    // Créer le CSV
    let csv = 'Référence,Pièce,Client,Fournisseur,Statut,Date,Prix\n';
    
    visibleRows.forEach(row => {
        const cells = row.querySelectorAll('td:not(.col-bulk-select)'); // Exclure la colonne de sélection
        const reference = cells[0]?.textContent.trim().replace(/\n/g, ' ') || '';
        const piece = cells[1]?.textContent.trim().replace(/\n/g, ' ') || '';
        const client = cells[2]?.textContent.trim().replace(/\n/g, ' ') || '';
        const fournisseur = cells[3]?.textContent.trim() || '';
        const statut = cells[4]?.textContent.trim() || '';
        const date = cells[5]?.textContent.trim().replace(/\n/g, ' ') || '';
        const prix = cells[6]?.textContent.trim() || '';
        
        csv += `"${reference}","${piece}","${client}","${fournisseur}","${statut}","${date}","${prix}"\n`;
    });
    
    // Télécharger le fichier
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `commandes_pieces_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Variables globales pour la modification en lot
let bulkEditMode = false;
let selectedCommandes = new Set();

// Activer/désactiver le mode modification en lot
function toggleBulkEdit() {
    bulkEditMode = !bulkEditMode;
    const bulkControls = document.getElementById('bulkEditControls');
    const bulkToggleBtn = document.getElementById('bulkToggleBtn');
    const bulkSelectHeader = document.getElementById('bulkSelectHeader');
    const bulkSelectCells = document.querySelectorAll('.bulk-select-cell');
    const allRows = document.querySelectorAll('.commande-row');
    
    if (bulkEditMode) {
        // Activer le mode bulk edit
        bulkControls.style.display = 'flex';
        bulkSelectHeader.style.display = 'table-cell';
        bulkSelectCells.forEach(cell => cell.style.display = 'table-cell');
        bulkToggleBtn.classList.add('active');
        bulkToggleBtn.innerHTML = '<i class="fas fa-times"></i> Quitter modification';
        
        // Ajouter la classe bulk-mode aux lignes
        allRows.forEach(row => {
            row.classList.add('bulk-mode');
            row.style.cursor = 'pointer';
            row.title = 'Cliquer pour sélectionner/désélectionner';
        });
    } else {
        // Désactiver le mode bulk edit
        cancelBulkEdit();
    }
}

// Annuler la modification en lot
function cancelBulkEdit() {
    bulkEditMode = false;
    selectedCommandes.clear();
    
    const bulkControls = document.getElementById('bulkEditControls');
    const bulkToggleBtn = document.getElementById('bulkToggleBtn');
    const bulkSelectHeader = document.getElementById('bulkSelectHeader');
    const bulkSelectCells = document.querySelectorAll('.bulk-select-cell');
    const allCheckboxes = document.querySelectorAll('.bulk-checkbox');
    const selectedRows = document.querySelectorAll('.commande-row.selected');
    const allRows = document.querySelectorAll('.commande-row');
    
    // Masquer les contrôles
    bulkControls.style.display = 'none';
    bulkSelectHeader.style.display = 'none';
    bulkSelectCells.forEach(cell => cell.style.display = 'none');
    
    // Réinitialiser le bouton
    bulkToggleBtn.classList.remove('active');
    bulkToggleBtn.innerHTML = '<i class="fas fa-edit"></i> Modification en lot';
    
    // Décocher toutes les cases
    allCheckboxes.forEach(checkbox => checkbox.checked = false);
    
    // Retirer la classe selected des lignes
    selectedRows.forEach(row => row.classList.remove('selected'));
    
    // Retirer la classe bulk-mode et réinitialiser les styles
    allRows.forEach(row => {
        row.classList.remove('bulk-mode');
        row.style.cursor = '';
        row.title = '';
    });
    
    // Réinitialiser le select
    document.getElementById('bulkStatusSelect').value = '';
    
    updateBulkSelection();
}

// Sélectionner/désélectionner toutes les lignes visibles
// Fonction pour forcer la sélection de tout
function forceSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = true;
        toggleSelectAll();
    }
}

function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const visibleRows = Array.from(document.querySelectorAll('.commande-row'))
        .filter(row => row.style.display !== 'none');
    
    visibleRows.forEach(row => {
        const checkbox = row.querySelector('.row-checkbox');
        const commandeId = parseInt(checkbox.value);
        
        if (selectAllCheckbox.checked) {
            checkbox.checked = true;
            selectedCommandes.add(commandeId);
            row.classList.add('selected');
        } else {
            checkbox.checked = false;
            selectedCommandes.delete(commandeId);
            row.classList.remove('selected');
        }
    });
    
    updateBulkSelection();
}

// Mettre à jour la sélection en lot
function updateBulkSelection() {
    const selectedCount = selectedCommandes.size;
    const bulkSelectedCount = document.getElementById('bulkSelectedCount');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const visibleCheckboxes = Array.from(document.querySelectorAll('.commande-row:not([style*="display: none"]) .row-checkbox'));
    
    // Mettre à jour le compteur
    bulkSelectedCount.textContent = `${selectedCount} sélectionnée(s)`;
    
    // Mettre à jour l'état de la case "Tout sélectionner"
    const checkedVisibleCount = visibleCheckboxes.filter(cb => cb.checked).length;
    const totalVisibleCount = visibleCheckboxes.length;
    
    if (checkedVisibleCount === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (checkedVisibleCount === totalVisibleCount) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

// Gérer la sélection individuelle
function updateBulkSelection() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    selectedCommandes.clear();
    
    checkboxes.forEach(checkbox => {
        const commandeId = parseInt(checkbox.value);
        const row = checkbox.closest('.commande-row');
        
        if (checkbox.checked) {
            selectedCommandes.add(commandeId);
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
    });
    
    const selectedCount = selectedCommandes.size;
    const bulkSelectedCount = document.getElementById('bulkSelectedCount');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const visibleCheckboxes = Array.from(document.querySelectorAll('.commande-row:not([style*="display: none"]) .row-checkbox'));
    
    // Mettre à jour le compteur
    bulkSelectedCount.textContent = `${selectedCount} sélectionnée(s)`;
    
    // Mettre à jour l'état de la case "Tout sélectionner"
    const checkedVisibleCount = visibleCheckboxes.filter(cb => cb.checked).length;
    const totalVisibleCount = visibleCheckboxes.length;
    
    if (checkedVisibleCount === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (checkedVisibleCount === totalVisibleCount) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

// Appliquer le changement de statut en lot
function applyBulkStatusChange() {
    const newStatus = document.getElementById('bulkStatusSelect').value;
    
    if (!newStatus) {
        alert('Veuillez sélectionner un statut');
        return;
    }
    
    if (selectedCommandes.size === 0) {
        alert('Veuillez sélectionner au moins une commande');
        return;
    }
    
    const commandeIds = Array.from(selectedCommandes);
    const confirmMessage = `Êtes-vous sûr de vouloir changer le statut de ${commandeIds.length} commande(s) ?`;
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Afficher un loader
    const applyBtn = document.querySelector('.filter-btn-bulk-apply');
    const originalText = applyBtn.innerHTML;
    applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Application...';
    applyBtn.disabled = true;
    
    // Appliquer les changements séquentiellement
    Promise.all(commandeIds.map(commandeId => {
        return fetch('api/update_commande_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                commande_id: commandeId, 
                new_status: newStatus 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(`Erreur pour la commande ${commandeId}: ${data.message}`);
            }
            return data;
        });
    }))
    .then(results => {
        // Succès
        alert(`${results.length} commande(s) mise(s) à jour avec succès !`);
        location.reload(); // Recharger la page pour voir les changements
    })
    .catch(error => {
        console.error('Erreur lors de la mise à jour en lot:', error);
        alert('Une erreur est survenue lors de la mise à jour : ' + error.message);
    })
    .finally(() => {
        // Restaurer le bouton
        applyBtn.innerHTML = originalText;
        applyBtn.disabled = false;
    });
}

// Gérer le clic sur une ligne entière
function handleRowClick(event, row) {
    // Ne pas traiter le clic si on n'est pas en mode bulk edit
    if (!bulkEditMode) {
        return;
    }
    
    // Empêcher la propagation si on clique sur des éléments interactifs
    const target = event.target;
    if (target.tagName === 'BUTTON' || 
        target.tagName === 'INPUT' || 
        target.closest('button') || 
        target.closest('.action-btn') ||
        target.closest('.col-statut div[onclick]')) {
        return;
    }
    
    // Trouver la checkbox de cette ligne
    const checkbox = row.querySelector('.row-checkbox');
    if (checkbox) {
        // Inverser l'état de la checkbox
        checkbox.checked = !checkbox.checked;
        
        // Déclencher l'événement change pour mettre à jour la sélection
        const changeEvent = new Event('change');
        checkbox.dispatchEvent(changeEvent);
    }
}

// Gérer le clic sur le statut pour éviter les conflits
function handleStatusClick(event, commandeId, statut, reference, nomPiece) {
    event.stopPropagation(); // Empêcher la propagation vers handleRowClick
    
    if (bulkEditMode) {
        // En mode bulk edit, ne pas ouvrir le modal de statut
        return;
    }
    
    // Comportement normal : ouvrir le modal de statut
    ouvrirModalStatut(event, commandeId, statut, reference, nomPiece);
}

// Gérer le clic sur les boutons d'action pour éviter les conflits
function handleActionClick(event, action, commandeId, statut = null) {
    event.stopPropagation(); // Empêcher la propagation vers handleRowClick
    
    if (bulkEditMode) {
        // En mode bulk edit, désactiver les actions individuelles
        return;
    }
    
    // Comportement normal selon l'action
    switch (action) {
        case 'edit':
            editCommande(commandeId);
            break;
        case 'delete':
            deleteCommande(commandeId);
            break;
        case 'status':
            changeStatus(commandeId, statut);
            break;
    }
}

// Fonction pour filtrer par statut depuis les cartes de statistiques
function filterByStatus(status) {
    // Réinitialiser tous les autres filtres
    const filters = [
        'searchInput', 'urgenceFilter', 'fournisseurFilter', 
        'dateFilter', 'periodeFilter'
    ];
    
    filters.forEach(filterId => {
        const filter = document.getElementById(filterId);
        if (filter) {
            filter.value = '';
            filter.classList.remove('active');
        }
    });
    
    // Définir le filtre de statut
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.value = status;
        statusFilter.classList.add('active');
    }
    
    // Marquer la carte active
    document.querySelectorAll('.stat-card').forEach(card => {
        card.classList.remove('active');
    });
    
    const activeCard = document.querySelector(`.stat-card[data-status="${status}"]`);
    if (activeCard) {
        activeCard.classList.add('active');
    }
    
    // Déclencher le filtrage
    const table = document.getElementById('commandesTable');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr.commande-row');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-statut');
        
        if (rowStatus === status) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Mettre à jour le compteur
    updateResultsCount(visibleCount);
    
    // Marquer le filtre comme actif
    markActiveFilters();
    
    // Scroll vers le tableau
    const tableContainer = document.querySelector('.table-container');
    if (tableContainer) {
        tableContainer.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }
}

// Fonction pour réinitialiser le filtre des cartes
function resetStatCardFilter() {
    document.querySelectorAll('.stat-card').forEach(card => {
        card.classList.remove('active');
    });
}

// Variable pour l'état des filtres
let filtersVisible = false;

// Fonction pour basculer l'affichage des filtres
function toggleFilters() {
    const filtersSection = document.getElementById('filtersSection');
    const toggleBtn = document.getElementById('filterToggleBtn');
    
    if (filtersVisible) {
        // Masquer les filtres
        filtersSection.classList.add('hidden');
        toggleBtn.classList.add('active');
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i> Afficher filtres';
        filtersVisible = false;
    } else {
        // Afficher les filtres
        filtersSection.classList.remove('hidden');
        toggleBtn.classList.remove('active');
        toggleBtn.innerHTML = '<i class="fas fa-filter"></i> Filtres';
        filtersVisible = true;
    }
}

// Fonctions de gestion des commandes
function editCommande(id) {
    // Récupérer les données de la commande
    const row = document.querySelector(`tr[data-commande-id="${id}"]`);
    if (!row) return;
    
    // Extraire les informations de la ligne
    const reference = row.querySelector('.col-reference .reference-text').textContent.trim();
    const pieceElement = row.querySelector('.col-piece');
    const pieceName = pieceElement.querySelector('div:first-child').textContent.trim();
    const pieceDescription = pieceElement.querySelector('div:last-child').textContent.trim();
    
    const clientElement = row.querySelector('.col-client');
    const clientName = clientElement.querySelector('div:first-child').textContent.trim();
    const clientPhone = clientElement.querySelector('div:last-child').textContent.trim();
    
    const fournisseur = row.querySelector('.col-fournisseur').textContent.trim();
    const statutBadge = row.querySelector('.status-badge');
    const statut = statutBadge.getAttribute('data-status') || statutBadge.className.match(/status-(\w+)/)?.[1] || '';
    
    // Remplir le modal avec les données
    document.getElementById('editCommandeId').value = id;
    document.getElementById('editReference').value = reference;
    document.getElementById('editPieceName').value = pieceName;
    document.getElementById('editPieceDescription').value = pieceDescription;
    document.getElementById('editClientName').value = clientName;
    document.getElementById('editClientPhone').value = clientPhone;
    document.getElementById('editFournisseur').value = fournisseur;
    document.getElementById('editStatut').value = statut;
    
    // Code barre depuis le data-attribute
    const codeBarre = row.dataset.codeBarre || '';
    document.getElementById('editCodeBarre').value = codeBarre;
    
    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('editCommandeModal'));
    modal.show();
}

function changeStatus(id, currentStatus) {
    // Ouvrir un modal de changement de statut
    const newStatus = prompt(`Changer le statut de la commande ${id}:\n\n1. en_attente\n2. urgent\n3. tres_urgent\n4. commande\n5. recue\n6. utilise\n7. annulee\n8. a_retourner\n\nEntrez le nouveau statut:`);
    
    if (newStatus && ['en_attente', 'urgent', 'tres_urgent', 'commande', 'recue', 'utilise', 'annulee', 'a_retourner'].includes(newStatus)) {
        fetch('api/update_commande_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                commande_id: id,
                new_status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}

function deleteCommande(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette commande ?')) {
        fetch('api/delete_commande.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({id: id})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}

function openAddModal() {
    // Rediriger vers la page d'ajout ou ouvrir un modal
    window.location.href = 'index.php?page=nouvelle_commande';
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    initializeFilters();
    
    // Filtrer par défaut sur "en_attente" au chargement
    setTimeout(function() {
        filterByStatus('en_attente');
    }, 100);
    
    // Animation des cartes de statistiques au scroll
    const statCards = document.querySelectorAll('.stat-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, {threshold: 0.1});
    
    statCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.6s ease';
        observer.observe(card);
    });
    
    // Animation des lignes du tableau
    const tableRows = document.querySelectorAll('.commande-row');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        row.style.transition = 'all 0.4s ease';
        
        setTimeout(() => {
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, index * 50);
    });
});

// Gestion du mode sombre manuel (bouton optionnel)
function toggleDarkMode() {
    document.body.classList.toggle('night-mode');
    
    if (document.body.classList.contains('night-mode')) {
        createParticles();
        localStorage.setItem('darkMode', 'true');
    } else {
        removeParticles();
        localStorage.setItem('darkMode', 'false');
    }
}

// Restaurer les préférences utilisateur
const savedDarkMode = localStorage.getItem('darkMode');
if (savedDarkMode === 'true') {
    document.body.classList.add('night-mode');
    createParticles();
} else if (savedDarkMode === 'false') {
    document.body.classList.remove('night-mode');
}

// Fonction pour ouvrir le modal de changement de statut
function ouvrirModalStatut(event, commandeId, statutActuel, reference, nomPiece) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Mettre à jour les informations dans le modal
    document.getElementById('statut-commande-reference').textContent = reference || `REF-${commandeId}`;
    document.getElementById('statut-piece-nom').textContent = nomPiece || 'Pièce détachée';
    
    // Mettre à jour le badge de statut actuel
    const statutActuelElement = document.getElementById('statut-actuel');
    const statutLabels = {
        'en_attente': 'En attente',
        'commande': 'Commandé',
        'recue': 'Reçu',
        'utilise': 'Utilisé',
        'annulee': 'Annulé',
        'a_retourner': 'À retourner'
    };
    
    if (statutActuelElement) {
        statutActuelElement.textContent = statutLabels[statutActuel] || statutActuel;
        statutActuelElement.className = 'modern-priority-badge status-' + statutActuel;
    }
    
    // Stocker l'ID de la commande pour la mise à jour
    document.getElementById('commandeStatutModal').setAttribute('data-commande-id', commandeId);
    
    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('commandeStatutModal'));
    modal.show();
}

// Gestionnaire pour les options de statut
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter les gestionnaires d'événements pour les options de statut
    document.querySelectorAll('.status-option').forEach(option => {
        option.addEventListener('click', function() {
            const nouveauStatut = this.getAttribute('data-status');
            const commandeId = document.getElementById('commandeStatutModal').getAttribute('data-commande-id');
            
            if (nouveauStatut && commandeId) {
                changerStatutCommande(commandeId, nouveauStatut);
            }
        });
    });
});

// Fonction pour effectuer le changement de statut
function changerStatutCommande(commandeId, nouveauStatut) {
    // Afficher le loader
    const loader = document.getElementById('statut-update-loader');
    const errorContainer = document.getElementById('statut-error-container');
    
    if (loader) loader.style.display = 'flex';
    if (errorContainer) errorContainer.style.display = 'none';
    
    // Faire la requête de mise à jour
    fetch('api/update_commande_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            commande_id: commandeId,
            new_status: nouveauStatut
        })
    })
    .then(response => response.json())
    .then(data => {
        if (loader) loader.style.display = 'none';
        
        if (data.success) {
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('commandeStatutModal'));
            if (modal) modal.hide();
            
            // Recharger la page pour voir les changements
            location.reload();
        } else {
            if (errorContainer) {
                errorContainer.style.display = 'block';
                errorContainer.querySelector('.error-message').textContent = data.message || 'Une erreur est survenue';
            }
        }
    })
    .catch(error => {
        if (loader) loader.style.display = 'none';
        if (errorContainer) {
            errorContainer.style.display = 'block';
            errorContainer.querySelector('.error-message').textContent = 'Une erreur réseau est survenue';
        }
        console.error('Erreur:', error);
    });
}
</script>

<!-- Modal pour changer le statut des commandes -->
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
                        <h5 class="modal-title" id="commandeStatutModalLabel" style="margin:0; color: #000000 !important;">Changer le statut</h5>
                        <p class="modal-subtitle" style="color: #000000 !important;">Mettre à jour le statut de la commande</p>
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

<!-- Styles pour le modal de statut -->
<style>
.task-subtitle {
    color: var(--day-text-secondary);
    font-size: 1rem;
    margin: 0;
}

body.night-mode .task-subtitle {
    color: #b0b0b0;
}

.task-header-section {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--day-border);
}

body.night-mode .task-header-section {
    border-bottom: 1px solid rgba(0, 255, 255, 0.2);
}

.task-meta {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.priority-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.priority-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--day-text-secondary);
}

body.night-mode .priority-label {
    color: #b0b0b0;
}

.modern-priority-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.modern-priority-badge.status-en_attente {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #92400e;
}

.modern-priority-badge.status-urgent,
.modern-priority-badge.status-tres_urgent {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: white;
}

.modern-priority-badge.status-commande {
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    color: white;
}

.modern-priority-badge.status-recue {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.modern-priority-badge.status-utilise {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
}

.modern-priority-badge.status-annulee {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    color: white;
}

.modern-priority-badge.status-a_retourner {
    background: linear-gradient(135deg, #374151, #1f2937);
    color: white;
}

.status-options-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .status-options-grid {
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: repeat(4, 1fr);
    }
}

.status-option {
    cursor: pointer;
    transition: all 0.3s ease;
}

.status-option-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--day-bg-card);
    border: 2px solid var(--day-border);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.status-option:hover .status-option-card {
    border-color: var(--primary-blue);
    box-shadow: 0 4px 20px rgba(59, 130, 246, 0.2);
    transform: translateY(-2px);
}

body.night-mode .status-option-card {
    background: var(--night-bg-card);
    border-color: var(--night-border);
}

body.night-mode .status-option:hover .status-option-card {
    border-color: #00d4ff;
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
}

.status-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.status-info {
    flex: 1;
}

.status-title {
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 0.25rem;
    color: var(--day-text-primary);
}

body.night-mode .status-title {
    color: var(--night-text-primary);
}

.status-description {
    font-size: 0.875rem;
    color: var(--day-text-secondary);
}

body.night-mode .status-description {
    color: var(--night-text-secondary);
}

.modern-task-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    color: var(--day-text-primary);
}

body.night-mode .modern-task-title {
    color: var(--night-text-primary);
}

.description-loader {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 2rem;
    justify-content: center;
    color: var(--day-text-secondary);
}

body.night-mode .description-loader {
    color: var(--night-text-secondary);
}

.loader-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid var(--day-border);
    border-top: 3px solid var(--primary-blue);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

body.night-mode .loader-spinner {
    border: 3px solid rgba(0, 255, 255, 0.2);
    border-top: 3px solid #00d4ff;
}

.error-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 8px;
    color: #dc2626;
}

.error-icon {
    font-size: 1.2rem;
}

.error-message {
    font-weight: 500;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Améliorer le style du modal en mode nuit */
body.night-mode .modal-content {
    background: var(--night-bg-card);
    border: 1px solid var(--night-border);
    box-shadow: 0 0 30px rgba(0, 212, 255, 0.2);
}

body.night-mode .modal-header {
    border-bottom: 1px solid var(--night-border);
}

body.night-mode .modal-title {
    color: var(--night-text-primary);
}

body.night-mode .modal-subtitle {
    color: var(--night-text-secondary);
}

body.night-mode .btn-close {
    filter: invert(1);
}

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.2));
    color: var(--primary-blue);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    position: relative; /* Pour les tooltips */
}

/* Tooltips pour les boutons d'action */
.action-btn::before {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(-8px);
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s ease;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* Flèche du tooltip */
.action-btn::after {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(-2px);
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid rgba(0, 0, 0, 0.9);
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s ease;
    z-index: 1000;
}

/* Afficher les tooltips au survol */
.action-btn:hover::before,
.action-btn:hover::after {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

/* Tooltips mode nuit pour action buttons */
body.night-mode .action-btn::before {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.95), rgba(37, 99, 235, 0.95));
    border: 1px solid rgba(59, 130, 246, 0.5);
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.4);
}

body.night-mode .action-btn::after {
    border-top-color: rgba(59, 130, 246, 0.95);
}

/* Tooltip spécifique pour le bouton Google */
.action-btn[style*="ea4335"]::before {
    background: linear-gradient(135deg, #ea4335, #d32f2f);
}

body.night-mode .action-btn[style*="ea4335"]::before {
    background: linear-gradient(135deg, rgba(234, 67, 53, 0.95), rgba(211, 47, 47, 0.95));
    border: 1px solid rgba(234, 67, 53, 0.5);
    box-shadow: 0 0 20px rgba(234, 67, 53, 0.4);
}

body.night-mode .action-btn[style*="ea4335"]::after {
    border-top-color: rgba(234, 67, 53, 0.95);
}

.action-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary-blue), var(--primary-cyan));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

body.night-mode .action-icon {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid rgba(0, 212, 255, 0.3);
    color: #00d4ff;
}

.modal-subtitle {
    color: var(--day-text-secondary);
    font-size: 0.9rem;
    margin: 0;
}

body.night-mode .modal-subtitle {
    color: var(--night-text-secondary);
}

/* Styles pour le modal de modification */
.edit-modal-content {
    border-radius: 16px;
    overflow: hidden;
}

/* Z-index élevé pour le modal */
#editCommandeModal {
    z-index: 10500 !important;
}

#editCommandeModal .modal-dialog {
    z-index: 10501 !important;
}

#editCommandeModal .modal-content {
    z-index: 10502 !important;
}

/* S'assurer que le backdrop est aussi au bon niveau */
.modal-backdrop {
    z-index: 10499 !important;
}

/* Z-index pour les cartes de statistiques */
.stat-card {
    z-index: 1 !important;
    position: relative;
}

/* Z-index pour tous les modals Bootstrap */
.modal {
    z-index: 10500 !important;
}

.modal.show {
    z-index: 10500 !important;
}

/* Z-index pour le modal de changement de statut aussi */
#commandeStatutModal {
    z-index: 10500 !important;
}

#commandeStatutModal .modal-dialog {
    z-index: 10501 !important;
}

#commandeStatutModal .modal-content {
    z-index: 10502 !important;
}

.edit-modal-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border-bottom: 1px solid #e5e7eb;
    padding: 1.5rem;
}

.edit-action-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.edit-modal-body {
    padding: 2rem;
    background: #ffffff;
}

.edit-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    align-items: start;
}

.edit-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.edit-form-group-full {
    grid-column: 1 / -1;
}

.edit-form-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.edit-form-label i {
    color: #6b7280;
    width: 16px;
}

.edit-form-input,
.edit-form-select,
.edit-form-textarea {
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #ffffff;
    color: #111827;
}

.edit-form-input:focus,
.edit-form-select:focus,
.edit-form-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.edit-form-textarea {
    resize: vertical;
    min-height: 80px;
}

.edit-modal-footer {
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.edit-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.edit-btn-cancel {
    background: #6b7280;
    color: white;
}

.edit-btn-cancel:hover {
    background: #4b5563;
    transform: translateY(-2px);
}

.edit-btn-save {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.edit-btn-save:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
}

/* Mode nuit pour le modal de modification */
body.night-mode .edit-modal-content {
    background: var(--night-bg-card);
    border: 1px solid var(--night-border);
    box-shadow: 0 0 40px rgba(0, 212, 255, 0.3);
}

body.night-mode .edit-modal-header {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border-bottom: 1px solid rgba(0, 212, 255, 0.2);
}

body.night-mode .edit-action-icon {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 2px solid rgba(0, 212, 255, 0.4);
    color: #00d4ff;
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.2);
}

body.night-mode .edit-modal-body {
    background: var(--night-bg-card);
}

body.night-mode .edit-form-label {
    color: #00d4ff;
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
}

body.night-mode .edit-form-label i {
    color: #00d4ff;
}

body.night-mode .edit-form-input,
body.night-mode .edit-form-select,
body.night-mode .edit-form-textarea {
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid rgba(0, 212, 255, 0.3);
    color: #ffffff;
}

body.night-mode .edit-form-input:focus,
body.night-mode .edit-form-select:focus,
body.night-mode .edit-form-textarea:focus {
    border-color: #00d4ff;
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2), 0 0 20px rgba(0, 212, 255, 0.1);
}

body.night-mode .edit-form-input::placeholder,
body.night-mode .edit-form-textarea::placeholder {
    color: #64748b;
}

body.night-mode .edit-modal-footer {
    background: rgba(30, 41, 59, 0.8);
    border-top: 1px solid rgba(0, 212, 255, 0.2);
}

body.night-mode .edit-btn-cancel {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid rgba(0, 212, 255, 0.3);
    color: #00d4ff;
}

body.night-mode .edit-btn-cancel:hover {
    border-color: rgba(0, 212, 255, 0.5);
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.2);
}

body.night-mode .edit-btn-save {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 2px solid rgba(16, 185, 129, 0.4);
    color: #10b981;
}

body.night-mode .edit-btn-save:hover {
    border-color: rgba(16, 185, 129, 0.6);
    box-shadow: 0 0 25px rgba(16, 185, 129, 0.3);
    transform: translateY(-2px);
}

/* Effets futuristes pour le mode nuit */
body.night-mode .edit-form-input:focus,
body.night-mode .edit-form-select:focus,
body.night-mode .edit-form-textarea:focus {
    animation: pulse-glow 2s infinite;
}

@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2), 0 0 20px rgba(0, 212, 255, 0.1);
    }
    50% {
        box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.4), 0 0 30px rgba(0, 212, 255, 0.2);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .edit-form-grid {
        grid-template-columns: 1fr;
    }
    
    .edit-form-group-full {
        grid-column: 1;
    }
}
</style>

<!-- Modal pour modifier une commande -->
<div class="modal fade" id="editCommandeModal" tabindex="-1" aria-labelledby="editCommandeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content edit-modal-content">
            <!-- En-tête du modal -->
            <div class="modal-header edit-modal-header">
                <div class="modal-header-content" style="display:flex;align-items:center;gap:14px;">
                    <div class="edit-action-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="modal-title-section" style="display:flex;flex-direction:column;gap:4px;">
                        <h5 class="modal-title" id="editCommandeModalLabel" style="margin:0; color: #000000 !important;">Modifier la commande</h5>
                        <p class="modal-subtitle" style="color: #000000 !important;">Mettre à jour les informations de la commande</p>
                    </div>
                </div>
                <button type="button" class="btn-close edit-btn-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Corps du modal -->
            <div class="modal-body edit-modal-body">
                <form id="editCommandeForm" class="edit-form">
                    <input type="hidden" id="editCommandeId" name="commandeId">
                    
                    <!-- Grille de champs -->
                    <div class="edit-form-grid">
                        <!-- Référence -->
                        <div class="edit-form-group">
                            <label for="editReference" class="edit-form-label">
                                <i class="fas fa-hashtag"></i>
                                Référence
                            </label>
                            <input type="text" id="editReference" name="reference" class="edit-form-input" readonly>
                        </div>

                        <!-- Statut -->
                        <div class="edit-form-group">
                            <label for="editStatut" class="edit-form-label">
                                <i class="fas fa-info-circle"></i>
                                Statut
                            </label>
                            <select id="editStatut" name="statut" class="edit-form-select">
                                <option value="en_attente">En attente</option>
                                <option value="commande">En livraison</option>
                                <option value="recue">Reçu</option>
                                <option value="utilise">Utilisé</option>
                                <option value="annulee">Annulé</option>
                                <option value="a_retourner">À retourner</option>
                            </select>
                        </div>

                        <!-- Nom de la pièce -->
                        <div class="edit-form-group edit-form-group-full">
                            <label for="editPieceName" class="edit-form-label">
                                <i class="fas fa-cog"></i>
                                Nom de la pièce
                            </label>
                            <input type="text" id="editPieceName" name="pieceName" class="edit-form-input" required>
                        </div>

                        <!-- Code barre / SKU -->
                        <div class="edit-form-group edit-form-group-full">
                            <label for="editCodeBarre" class="edit-form-label">
                                <i class="fas fa-barcode"></i>
                                Code Barre / SKU
                            </label>
                            <input type="text" id="editCodeBarre" name="codeBarre" class="edit-form-input" placeholder="Référence fournisseur ou code barre">
                        </div>

                        <!-- Description de la pièce -->
                        <div class="edit-form-group edit-form-group-full">
                            <label for="editPieceDescription" class="edit-form-label">
                                <i class="fas fa-align-left"></i>
                                Description
                            </label>
                            <textarea id="editPieceDescription" name="pieceDescription" class="edit-form-textarea" rows="3"></textarea>
                        </div>

                        <!-- Nom du client -->
                        <div class="edit-form-group">
                            <label for="editClientName" class="edit-form-label">
                                <i class="fas fa-user"></i>
                                Nom du client
                            </label>
                            <input type="text" id="editClientName" name="clientName" class="edit-form-input" required>
                        </div>

                        <!-- Téléphone du client -->
                        <div class="edit-form-group">
                            <label for="editClientPhone" class="edit-form-label">
                                <i class="fas fa-phone"></i>
                                Téléphone
                            </label>
                            <input type="tel" id="editClientPhone" name="clientPhone" class="edit-form-input">
                        </div>

                        <!-- Fournisseur -->
                        <div class="edit-form-group edit-form-group-full">
                            <label for="editFournisseur" class="edit-form-label">
                                <i class="fas fa-truck"></i>
                                Fournisseur
                            </label>
                            <input type="text" id="editFournisseur" name="fournisseur" class="edit-form-input">
                        </div>
                    </div>
                </form>
            </div>

            <!-- Pied du modal -->
            <div class="modal-footer edit-modal-footer">
                <button type="button" class="edit-btn edit-btn-cancel" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                    Annuler
                </button>
                <button type="button" class="edit-btn edit-btn-save" onclick="saveCommandeChanges()">
                    <i class="fas fa-save"></i>
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour sauvegarder les modifications
function saveCommandeChanges() {
    const form = document.getElementById('editCommandeForm');
    const formData = new FormData(form);
    
    // Afficher un loader sur le bouton
    const saveBtn = document.querySelector('.edit-btn-save');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
    saveBtn.disabled = true;
    
    // Simuler une sauvegarde (remplacer par votre API)
    setTimeout(() => {
        // Ici vous pouvez ajouter votre logique de sauvegarde
        console.log('Données à sauvegarder:', Object.fromEntries(formData));
        
        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('editCommandeModal'));
        modal.hide();
        
        // Recharger la page ou mettre à jour la ligne
        location.reload();
        
        // Restaurer le bouton
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    }, 1000);
}

// ========================================
// GESTION DES BOUTONS SMS
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    // Bouton "Commande arrivée"
    document.querySelectorAll('.btn-sms-notification').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            
            const data = {
                commandeId: this.dataset.commandeId,
                clientId: this.dataset.clientId,
                telephone: this.dataset.telephone,
                clientNom: this.dataset.clientNom,
                reparationId: this.dataset.reparationId,
                type: 'commande_recue'
            };
            
            showSmsConfirmModal(data, this);
        });
    });
    
    // Bouton "Retard livraison"
    document.querySelectorAll('.btn-sms-retard').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            
            const data = {
                commandeId: this.dataset.commandeId,
                clientId: this.dataset.clientId,
                telephone: this.dataset.telephone,
                clientNom: this.dataset.clientNom,
                reparationId: this.dataset.reparationId,
                type: 'retard_livraison'
            };
            
            showSmsConfirmModal(data, this);
        });
    });
});

function showSmsConfirmModal(data, button) {
    const typeText = data.type === 'commande_recue' 
        ? 'de commande arrivée' 
        : 'de retard de livraison';
    
    const message = `Êtes-vous sûr de vouloir envoyer un SMS ${typeText} au client ${data.clientNom} (${data.telephone}) ?`;
    
    if (confirm(message)) {
        sendCommandeSms(data, button);
    }
}

function sendCommandeSms(data, button) {
    // Afficher un loader sur le bouton
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    button.classList.add('loading');
    
    fetch('/ajax/send_commande_sms.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('✅ SMS envoyé avec succès !', 'success');
        } else {
            showNotification('❌ Erreur : ' + (result.error || 'Échec de l\'envoi'), 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('❌ Erreur lors de l\'envoi du SMS', 'error');
    })
    .finally(() => {
        // Restaurer le bouton
        button.innerHTML = originalHTML;
        button.disabled = false;
        button.classList.remove('loading');
    });
}

function showNotification(message, type) {
    // Utiliser une notification toast si disponible, sinon alert
    if (typeof Swal !== 'undefined') {
        // Si SweetAlert2 est disponible
        Swal.fire({
            title: type === 'success' ? 'Succès' : 'Erreur',
            text: message,
            icon: type,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    } else {
        // Fallback avec alert
        alert(message);
    }
}
</script>

<!-- Inclure le footer -->
<?php include_once 'includes/footer.php'; ?>