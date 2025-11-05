<?php
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'rachat_moderne.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=rachat_moderne');
    exit();
}

// Vérifier si la session est déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Aucune restriction d'accès - tous les utilisateurs peuvent accéder à cette page
// Si vous souhaitez rétablir la restriction plus tard, décommentez le code ci-dessous
/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/403.php');
    exit();
}
*/

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../config/database.php';

$shop_pdo = getShopDBConnection();

// Afficher les données de session pour le débogage
if (isset($_GET['debug']) && $_GET['debug'] == 1) {
    echo '<pre>Session: ' . print_r($_SESSION, true) . '</pre>';
}

// Liste des clients (fallback si AJAX échoue)
$clients = [];
try {
    // Vérifier que la connexion à la base de données est établie
    if (isset($shop_pdo) && $shop_pdo !== null) {
        $stmt = $shop_pdo->prepare("SELECT id, nom, prenom FROM clients ORDER BY nom, prenom");
        $stmt->execute();
        $clients = $stmt->fetchAll();
    } else {
        error_log("Erreur: La connexion à la base de données n'est pas disponible");
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des clients: " . $e->getMessage());
}

?>

<style>
/* ===========================================
   FIX NAVBAR DESKTOP - ABSOLUMENT NÉCESSAIRE
   =========================================== */

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
        margin: 0;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        overflow-x: hidden;
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

/* ===========================================
   VARIABLES CSS POUR LES THÈMES (IDENTIQUES À ACCUEIL-MODERN)
   =========================================== */
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

/* ===========================================
   ANIMATIONS DE FOND IDENTIQUES À ACCUEIL-MODERN
   =========================================== */
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

/* ===========================================
   STYLES MODERNES POUR RACHAT D'APPAREILS
   =========================================== */

.modern-page-container {
    min-height: 100vh;
    padding: 1rem;
    position: relative;
    margin-top: -80px; /* Remonter sous la navbar */
    padding-top: calc(80px + 1rem); /* Compenser avec padding */
}

.modern-page-header {
    background: var(--day-card-bg);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px var(--day-shadow);
    border: 1px solid var(--day-border);
}

.modern-page-title {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.modern-page-subtitle {
    color: var(--day-text-light);
    font-size: 1.1rem;
    margin-bottom: 2rem;
}

.modern-content-card {
    background: var(--day-card-bg);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 8px 32px var(--day-shadow);
    border: 1px solid var(--day-border);
    margin-bottom: 2rem;
}

.modern-btn-primary {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    border: none;
    border-radius: 15px;
    padding: 0.8rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px var(--day-shadow);
    color: white;
}

.modern-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--day-shadow);
    color: white;
}

.modern-search-card {
    background: var(--day-card-bg);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px var(--day-shadow);
    border: 1px solid var(--day-border);
}

.modern-search-input {
    border: 2px solid var(--day-border);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    background: #ffffff !important;
    color: var(--day-text) !important;
}

.modern-search-input:focus {
    border-color: var(--day-primary);
    box-shadow: 0 0 0 0.2rem var(--day-shadow);
    background: #ffffff !important;
    color: var(--day-text) !important;
}

/* Correction pour les champs de recherche et selects */
#searchInput, #filterStatus {
    background: #ffffff !important;
    color: var(--day-text) !important;
    border: 2px solid var(--day-border) !important;
}

#searchInput:focus, #filterStatus:focus {
    background: #ffffff !important;
    color: var(--day-text) !important;
    border-color: var(--day-primary) !important;
}

/* ===========================================
   TABLEAU MODERNE ADAPTATIF
   =========================================== */

.modern-table-wrapper {
    background: var(--day-card-bg);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 32px var(--day-shadow);
    border: 1px solid var(--day-border);
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.modern-table thead {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
}

.modern-table thead th {
    color: white;
    font-weight: 600;
    padding: 1.2rem 1rem;
    text-align: left;
    border: none;
    font-size: 0.9rem;
}

.modern-table tbody tr {
    border-bottom: 1px solid var(--day-border);
    transition: all 0.3s ease;
}

.modern-table tbody tr:hover {
    background-color: rgba(59, 130, 246, 0.05);
    transform: scale(1.01);
}

.modern-table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border: none;
    color: var(--day-text);
    font-weight: 500;
}

.modern-table tbody td strong {
    color: var(--day-text);
    font-weight: 600;
}

.modern-status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-nouveau {
    background: linear-gradient(135deg, #10b981, #059669) !important;
    color: white !important;
}

.status-en-cours {
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    color: white !important;
}

.status-termine {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)) !important;
    color: white !important;
}

/* Corrections pour améliorer la lisibilité du tableau */
.modern-table tbody td {
    color: var(--day-text) !important;
    font-weight: 500 !important;
}

.modern-table tbody td strong,
.modern-table tbody td .fw-bold {
    color: var(--day-text) !important;
    font-weight: 700 !important;
}

.modern-table tbody tr td small,
.modern-table tbody tr td .small {
    color: var(--day-text-light) !important;
    font-weight: 500 !important;
}

/* Corrections pour les liens dans le tableau */
.modern-table tbody td a {
    color: var(--day-primary) !important;
    text-decoration: none;
    font-weight: 600;
}

.modern-table tbody td a:hover {
    color: var(--day-secondary) !important;
    text-decoration: underline;
}

/* ===========================================
   MODAL MODERNE
   =========================================== */

.modal-content {
    border: none;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    background: var(--day-card-bg) !important;
}

.modal-header {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)) !important;
    border: none;
    padding: 1.5rem 2rem;
}

.modal-title {
    color: white !important;
    font-weight: 600;
    font-size: 1.3rem;
}

.btn-close-white {
    filter: brightness(0) invert(1);
}

.modal-body {
    padding: 2rem;
    background: var(--day-card-bg) !important;
    color: var(--day-text) !important;
}

/* Correction pour tous les éléments du modal */
.modal-body .form-control,
.modal-body .form-select,
.modal-body input,
.modal-body textarea,
.modal-body select {
    background: #ffffff !important;
    color: var(--day-text) !important;
    border: 2px solid var(--day-border) !important;
}

.modal-body .form-control:focus,
.modal-body .form-select:focus,
.modal-body input:focus,
.modal-body textarea:focus,
.modal-body select:focus {
    background: #ffffff !important;
    color: var(--day-text) !important;
    border-color: var(--day-primary) !important;
    box-shadow: 0 0 0 0.2rem var(--day-shadow) !important;
}

.modal-body .form-label,
.modal-body label {
    color: var(--day-text) !important;
    font-weight: 600;
}

.modal-body .form-text {
    color: var(--day-text-light) !important;
}

.modal-body .card {
    background: var(--day-card-bg) !important;
    border: 1px solid var(--day-border) !important;
}

.modal-body .card-header {
    background: rgba(59, 130, 246, 0.1) !important;
    color: var(--day-text) !important;
    border-bottom: 1px solid var(--day-border) !important;
}

/* ===========================================
   STEPPER MODERNE
   =========================================== */

.stepper-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    position: relative;
}

.stepper-wrapper::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
    background: #e2e8f0;
    z-index: 1;
}

.stepper-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
}

.step-counter {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.step-counter.active {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    transform: scale(1.1);
}

.step-counter.completed {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}

.step-name {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--day-text-light);
    text-align: center;
}

.stepper-item.active .step-name {
    color: var(--day-primary);
    font-weight: 600;
}

/* ===========================================
   FORMULAIRE MODERNE
   =========================================== */

.form-label {
    font-weight: 600;
    color: var(--day-text) !important;
    margin-bottom: 0.5rem;
}

.form-control {
    border: 2px solid var(--day-border) !important;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    background: #ffffff !important;
    color: var(--day-text) !important;
}

.form-control:focus {
    border-color: var(--day-primary) !important;
    box-shadow: 0 0 0 0.2rem var(--day-shadow) !important;
    background: #ffffff !important;
    color: var(--day-text) !important;
}

.input-group-text {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)) !important;
    color: white !important;
    border: none;
    border-radius: 12px 0 0 12px;
}

.btn-outline-primary {
    border: 2px solid var(--day-primary) !important;
    color: var(--day-primary) !important;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    background: #ffffff !important;
}

.btn-outline-primary:hover {
    background: var(--day-primary) !important;
    color: white !important;
    transform: translateY(-1px);
}

/* Corrections spécifiques pour les selects */
.form-select {
    border: 2px solid var(--day-border) !important;
    background: #ffffff !important;
    color: var(--day-text) !important;
}

.form-select:focus {
    border-color: var(--day-primary) !important;
    background: #ffffff !important;
    color: var(--day-text) !important;
}

.form-select option {
    background: #ffffff !important;
    color: var(--day-text) !important;
}

/* Corrections pour les dropdowns Bootstrap */
.dropdown-menu {
    background: #ffffff !important;
    border: 2px solid var(--day-border) !important;
}

.dropdown-item {
    color: var(--day-text) !important;
}

.dropdown-item:hover,
.dropdown-item:focus {
    background: rgba(59, 130, 246, 0.1) !important;
    color: var(--day-text) !important;
}

/* ===========================================
   CARDS ET APERÇUS D'IMAGES
   =========================================== */

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border: none;
    border-radius: 15px 15px 0 0;
    font-weight: 600;
    color: var(--day-text) !important;
}

/* ===========================================
   BOUTONS ACTIONS ET NAVIGATION
   =========================================== */

.btn {
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    border: none !important;
    color: white !important;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
    color: white !important;
    transform: translateY(-1px);
}

.btn-info {
    background: linear-gradient(135deg, #06b6d4, #0891b2) !important;
    border: none !important;
    color: white !important;
}

.btn-info:hover {
    background: linear-gradient(135deg, #0891b2, #0e7490) !important;
    color: white !important;
    transform: translateY(-1px);
}

.btn-success {
    background: linear-gradient(135deg, #10b981, #059669) !important;
    border: none !important;
    color: white !important;
}

.btn-success:hover {
    background: linear-gradient(135deg, #059669, #047857) !important;
    color: white !important;
    transform: translateY(-1px);
}

.btn-primary {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)) !important;
    border: none !important;
    color: white !important;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--day-secondary), var(--day-primary)) !important;
    color: white !important;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d !important;
    border: none !important;
    color: white !important;
}

.btn-secondary:hover {
    background: #5c636a !important;
    color: white !important;
    transform: translateY(-1px);
}

.img-preview {
    max-height: 200px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.img-preview:hover {
    transform: scale(1.05);
}

/* ===========================================
   ALERTES MODERNES
   =========================================== */

.alert {
    border: none;
    border-radius: 15px;
    padding: 1rem 1.5rem;
    font-weight: 500;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
}

.alert-info {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    color: #0c5460;
}

/* ===========================================
   RESPONSIVE
   =========================================== */

@media (max-width: 768px) {
    .modern-page-container {
        padding: 0.5rem;
    }
    
    .modern-page-header {
        padding: 1.5rem;
        border-radius: 15px;
    }
    
    .modern-page-title {
        font-size: 2rem;
    }
    
    .modern-content-card {
        padding: 1.5rem;
        border-radius: 15px;
    }
    
    .modern-table-wrapper {
        border-radius: 15px;
    }
    
    .stepper-wrapper {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .step-counter {
        width: 35px;
        height: 35px;
    }
    
    body {
        padding-top: 0 !important;
    }
}

/* ===========================================
   CAMERA MODAL STYLES
   =========================================== */

.camera-preview-fullscreen {
    width: 100%;
    max-width: 600px;
    height: 400px;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.camera-video-fullscreen {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.camera-controls {
    margin-top: 2rem;
}

#cameraInstructions {
    font-size: 1.1rem;
    text-align: center;
    max-width: 400px;
    margin: 0 auto;
}

/* ===========================================
   ANIMATIONS
   =========================================== */

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modern-content-card {
    animation: fadeInUp 0.6s ease-out;
}

.modern-table tbody tr {
    animation: fadeInUp 0.4s ease-out;
}

/* ===========================================
   DARK MODE SUPPORT (MODE NUIT)
   =========================================== */

body.night-mode .bg-animated {
    background: var(--night-bg-animated);
}

body.night-mode .modern-page-header,
body.night-mode .modern-content-card,
body.night-mode .modern-table-wrapper,
body.night-mode .modern-search-card {
    background: var(--night-card-bg);
    color: var(--night-text);
    border: 1px solid var(--night-border);
    box-shadow: 0 8px 32px var(--night-shadow);
}

body.night-mode .modern-page-title {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

body.night-mode .modern-page-subtitle {
    color: var(--night-text-light);
}

body.night-mode .modern-table thead {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
}

body.night-mode .modern-table tbody tr:hover {
    background-color: rgba(0, 212, 255, 0.1);
}

body.night-mode .form-control,
body.night-mode .modern-search-input {
    background: var(--night-card-bg);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode .form-control:focus,
body.night-mode .modern-search-input:focus {
    background: var(--night-card-bg);
    border-color: var(--night-primary);
    box-shadow: var(--night-glow);
}

body.night-mode .modern-btn-primary {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    color: var(--night-text);
}

/* ===========================================
   CORRECTIONS SPÉCIFIQUES POUR LE CONTRASTE
   =========================================== */

/* Texte général de la page */
body, .modern-page-container {
    color: var(--day-text) !important;
}

/* Titres et sous-titres */
h1, h2, h3, h4, h5, h6 {
    color: var(--day-text) !important;
}

/* Textes dans les modals */
.modal-body p,
.modal-body div,
.modal-body span,
.modal-body .form-text {
    color: var(--day-text) !important;
}

.modal-body .text-muted {
    color: var(--day-text-light) !important;
}

/* Corrections pour les placeholders */
.form-control::placeholder {
    color: var(--day-text-light) !important;
    opacity: 0.7;
}

/* Corrections pour les badges */
.badge {
    font-weight: 600 !important;
}

/* Corrections pour les alertes */
.alert {
    border: none;
    border-radius: 12px;
}

.alert-info {
    background: rgba(59, 130, 246, 0.1) !important;
    color: var(--day-text) !important;
    border: 1px solid rgba(59, 130, 246, 0.2) !important;
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1) !important;
    color: var(--day-text) !important;
    border: 1px solid rgba(239, 68, 68, 0.2) !important;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1) !important;
    color: var(--day-text) !important;
    border: 1px solid rgba(16, 185, 129, 0.2) !important;
}

/* Navigation dans le stepper */
.btn-nav {
    font-weight: 600;
    padding: 0.75rem 2rem;
    border-radius: 12px;
}

/* Corrections pour les icônes */
.fa, .fas, .fab, .far {
    opacity: 1 !important;
}

/* Amélioration des boutons de navigation dans les modals */
.modal-footer .btn {
    margin: 0 0.25rem;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
}

/* ===========================================
   CORRECTION Z-INDEX MODALS CAMERA
   =========================================== */

/* Modal caméra doit être au-dessus du modal rachat */
#cameraModal {
    z-index: 1070 !important;
}

#cameraModal.show {
    z-index: 1070 !important;
}

/* Backdrop du modal caméra */
#cameraModal + .modal-backdrop,
.modal-backdrop.show:last-of-type {
    z-index: 1065 !important;
}

/* S'assurer que le modal rachat reste en dessous */
#newRachatModal {
    z-index: 1050 !important;
}

#newRachatModal + .modal-backdrop {
    z-index: 1045 !important;
}

/* Correction générale pour les modals imbriqués */
.modal.fade.show {
    display: block !important;
}

/* Modal caméra plein écran avec priorité maximale */
#cameraModal .modal-dialog {
    z-index: 1071 !important;
}

#cameraModal .modal-content {
    z-index: 1072 !important;
}
</style>

<div class="modern-page-container bg-animated">
    <!-- En-tête de la page -->
    <div class="modern-page-header">
        <h1 class="modern-page-title">
            <i class="fas fa-hand-holding-usd me-3"></i>
            Rachat d'Appareils
        </h1>
        <p class="modern-page-subtitle">
            Gérez les rachats d'appareils électroniques de vos clients
        </p>
        
        <div class="row align-items-center">
            <div class="col-md-6">
                <button type="button" class="btn modern-btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#newRachatModal">
                    <i class="fas fa-plus me-2"></i>
                    Nouveau Rachat
                </button>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="d-inline-block">
                    <small class="text-muted">Dernière mise à jour: <?php echo date('d/m/Y H:i'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte de recherche -->
    <div class="modern-content-card">
        <div class="modern-search-card">
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control modern-search-input" id="searchInput" 
                               placeholder="Rechercher par client, modèle, numéro de série...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-control modern-search-input" id="filterStatus">
                        <option value="">Tous les statuts</option>
                        <option value="nouveau">Nouveau</option>
                        <option value="en_cours">En cours</option>
                        <option value="termine">Terminé</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tableau des rachats -->
        <div class="modern-table-wrapper">
            <div id="loadingSpinner" class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-2 text-muted">Chargement des données...</p>
            </div>

            <table class="modern-table d-none" id="rachatsTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag me-2"></i>ID</th>
                        <th><i class="fas fa-user me-2"></i>Client</th>
                        <th><i class="fas fa-mobile-alt me-2"></i>Appareil</th>
                        <th><i class="fas fa-calendar me-2"></i>Date</th>
                        <th><i class="fas fa-euro-sign me-2"></i>Prix</th>
                        <th><i class="fas fa-info-circle me-2"></i>Statut</th>
                        <th><i class="fas fa-cogs me-2"></i>Actions</th>
                    </tr>
                </thead>
                <tbody id="rachatsTableBody">
                    <!-- Les données seront chargées via AJAX -->
                </tbody>
            </table>

            <div id="noResultsMessage" class="text-center p-4 d-none">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Aucun rachat trouvé</h5>
                <p class="text-muted">Essayez de modifier vos critères de recherche</p>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3" id="paginationContainer">
            <div class="text-muted">
                <span id="resultsInfo">Affichage de 0 résultats</span>
            </div>
            <nav>
                <ul class="pagination mb-0" id="paginationList">
                    <!-- Pagination sera générée par JavaScript -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Modal Détails Rachat -->
<div class="modal fade" id="rachatDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>
                    Détails du rachat
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="rachat-details-header mb-4">
                    <div class="client-info">
                        <h6 class="mb-1">
                            <i class="fas fa-user me-2"></i>
                            Client: <span id="modalClientName">-</span>
                        </h6>
                        <p class="text-muted small" id="modalRachatDate">Date: -</p>
                    </div>
                    <div class="price-info text-end">
                        <h5 class="text-success" id="modalRachatPrice">- €</h5>
                        <span class="badge" id="modalRachatState">-</span>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-id-card me-2 text-primary"></i>
                                    Pièce d'identité
                                </h6>
                                <button class="btn btn-sm btn-outline-secondary download-btn" data-img="modalIdentite">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                            <div class="card-body text-center">
                                <img id="modalIdentite" class="img-fluid rounded img-preview" alt="Pièce d'identité" onerror="this.src='/assets/images/no-image.png'">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-mobile-alt me-2 text-primary"></i>
                                    Photo de l'appareil
                                </h6>
                                <button class="btn btn-sm btn-outline-secondary download-btn" data-img="modalAppareil">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                            <div class="card-body text-center">
                                <img id="modalAppareil" class="img-fluid rounded img-preview" alt="Appareil" onerror="this.src='/assets/images/no-image.png'">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-user me-2 text-primary"></i>
                                    Photo du client
                                </h6>
                                <button class="btn btn-sm btn-outline-secondary download-btn" data-img="modalPhotoClient">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                            <div class="card-body text-center">
                                <img id="modalPhotoClient" class="img-fluid rounded img-preview" alt="Photo du client" onerror="this.src='/assets/images/no-image.png'">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-signature me-2 text-primary"></i>
                                    Signature du client
                                </h6>
                                <button class="btn btn-sm btn-outline-secondary download-btn" data-img="modalSignature">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                            <div class="card-body text-center">
                                <img id="modalSignature" class="img-fluid rounded img-preview" alt="Signature" onerror="this.src='/assets/images/no-image.png'">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn modern-btn-primary" id="btnExportDetails">
                    <i class="fas fa-file-pdf me-2"></i>Exporter l'attestation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Caméra Plein Écran -->
<div class="modal fade" id="cameraModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title">
                    <i class="fas fa-camera me-2"></i>
                    <span id="cameraModalTitle">Prendre une photo</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeCameraModal()"></button>
            </div>
            <div class="modal-body bg-dark d-flex align-items-center justify-content-center">
                <div class="camera-container text-center">
                    <div id="cameraPreviewFullscreen" class="camera-preview-fullscreen">
                        <video id="cameraVideoFullscreen" autoplay muted playsinline class="camera-video-fullscreen"></video>
                        <canvas id="cameraCanvasFullscreen" class="d-none"></canvas>
                    </div>
                    <div class="camera-controls mt-4">
                        <button type="button" class="btn btn-success btn-lg me-3" onclick="takePictureFullscreen()">
                            <i class="fas fa-camera me-2"></i>Prendre la photo
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="closeCameraModal()">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                    </div>
                    <div id="cameraInstructions" class="text-white-50 mt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Positionnez l'appareil ou le document dans le cadre et cliquez sur "Prendre la photo"
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouveau Rachat -->
<div class="modal fade" id="newRachatModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-hand-holding-usd me-2"></i>
                    Nouveau rachat d'appareil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="rachatForm" class="needs-validation" novalidate enctype="multipart/form-data">
                    <div id="rachatFormError" class="alert alert-danger d-none" role="alert"></div>
                    <div id="rachatFormSuccess" class="alert alert-success d-none" role="alert"></div>
                    <input type="hidden" name="debug_mode" value="0">
                    
                    <!-- Indicateur d'étapes amélioré -->
                    <div class="stepper-wrapper mb-4">
                        <div class="stepper-item" data-step="1">
                            <div class="step-counter active">1</div>
                            <div class="step-name">Client</div>
                        </div>
                        <div class="stepper-item" data-step="2">
                            <div class="step-counter">2</div>
                            <div class="step-name">Appareil</div>
                        </div>
                        <div class="stepper-item" data-step="3">
                            <div class="step-counter">3</div>
                            <div class="step-name">Signature</div>
                        </div>
                        <div class="stepper-item" data-step="4">
                            <div class="step-counter">4</div>
                            <div class="step-name">Prix</div>
                        </div>
                    </div>

                    <div class="progress mb-4 d-none">
                        <div class="progress-bar" role="progressbar" style="width: 20%;" id="rachatProgressBar">Étape 1/4</div>
                    </div>

                    <!-- Étape 1: Informations client -->
                    <div class="rachat-step" id="step1">
                        <h4 class="mb-3">Informations sur le client</h4>
                        <div class="card mb-3 border-primary">
                            <div class="card-header">
                                <i class="fas fa-search me-2"></i> Rechercher un client existant
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" class="form-control" id="recherche_client_rachat" placeholder="Rechercher par nom, prénom ou téléphone...">
                                        <button type="button" class="btn modern-btn-primary" id="btn_recherche_client">
                                            <i class="fas fa-search me-1"></i> Rechercher
                                        </button>
                                    </div>
                                    <div class="form-text">Saisissez au moins 2 caractères pour lancer la recherche</div>
                                </div>

                                <div id="resultats_clients" class="mb-3 d-none">
                                    <div class="modern-table-wrapper">
                                        <table class="modern-table">
                                            <thead>
                                                <tr>
                                                    <th><i class="fas fa-user me-2"></i>Nom</th>
                                                    <th><i class="fas fa-user-tag me-2"></i>Prénom</th>
                                                    <th><i class="fas fa-phone me-2"></i>Téléphone</th>
                                                    <th><i class="fas fa-cog me-2"></i>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="liste_clients">
                                                <!-- Résultats de recherche ici -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div id="no_results" class="alert alert-info d-none">
                                    <p class="mb-2"><i class="fas fa-info-circle me-2"></i>Aucun client trouvé avec ces critères.</p>
                                    <button type="button" class="btn modern-btn-primary" id="btn_nouveau_client">
                                        <i class="fas fa-user-plus me-2"></i>Ajouter un nouveau client
                                    </button>
                                </div>
                                
                                <div id="client_selectionne" class="alert alert-success d-none">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-user-check me-2"></i>
                                            Client sélectionné: <strong id="nom_client_selectionne"></strong>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="reset_client">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="client_id" id="client_id" required>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Pièce d'identité (recto) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="file" class="form-control" name="photo_identite" id="photo_identite" accept="image/*" required>
                                <button type="button" class="btn btn-outline-primary" onclick="openCameraModal('identite')">
                                    <i class="fas fa-camera me-1"></i> Prendre une photo
                                </button>
                                <div class="invalid-feedback">Veuillez ajouter une photo de la pièce d'identité</div>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-shield-alt me-1"></i> Cette photo est utilisée uniquement pour vérifier l'identité du client
                            </div>
                            <!-- Aperçu de la photo capturée -->
                            <div id="previewIdentite" class="mt-2 d-none">
                                <img id="previewIdentiteImg" class="img-fluid rounded border" style="max-height: 200px;">
                                <button type="button" class="btn btn-sm btn-danger mt-1" id="retakeIdentite">
                                    <i class="fas fa-redo me-1"></i> Reprendre
                                </button>
                            </div>
                            <!-- Canvas caché pour la capture -->
                            <canvas id="canvasIdentite" style="display: none;"></canvas>
                        </div>
                    </div>
                    
                    <!-- Étape 2: Informations sur l'appareil -->
                    <div class="rachat-step d-none" id="step2">
                        <h4 class="mb-3">Informations sur l'appareil</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Modèle</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                    <input type="text" class="form-control" name="modele" id="modele" placeholder="Ex: iPhone 12, Galaxy S21...">
                                </div>
                                <input type="hidden" name="type_appareil" id="type_appareil">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Numéro de série (SIN)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                    <input type="text" class="form-control" name="sin" id="sin" placeholder="Numéro de série">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Photo de l'appareil <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-camera"></i></span>
                                    <input type="file" class="form-control" name="photo_appareil" id="photo_appareil" accept="image/*" required>
                                    <button type="button" class="btn btn-outline-primary" onclick="openCameraModal('appareil')">
                                        <i class="fas fa-camera me-1"></i> Prendre une photo
                                    </button>
                                    <div class="invalid-feedback">Veuillez ajouter une photo de l'appareil</div>
                                </div>
                                <div class="form-text"><i class="fas fa-info-circle"></i> Prenez une photo claire de l'appareil</div>
                                <!-- Aperçu de la photo capturée -->
                                <div id="previewAppareil" class="mt-2 d-none">
                                    <img id="previewAppareilImg" class="img-fluid rounded border" style="max-height: 200px;">
                                    <button type="button" class="btn btn-sm btn-danger mt-1" id="retakeAppareil">
                                        <i class="fas fa-redo me-1"></i> Reprendre
                                    </button>
                                </div>
                                <!-- Canvas caché pour la capture -->
                                <canvas id="canvasAppareil" style="display: none;"></canvas>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Photo du client avec l'appareil <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="file" class="form-control" name="photo_client" id="photo_client" accept="image/*" required>
                                    <button type="button" class="btn btn-outline-primary" onclick="openCameraModal('client')">
                                        <i class="fas fa-camera me-1"></i> Prendre une photo
                                    </button>
                                    <div class="invalid-feedback">Veuillez ajouter une photo du client</div>
                                </div>
                                <div class="form-text"><i class="fas fa-info-circle"></i> Photo du client tenant l'appareil</div>
                                <!-- Aperçu de la photo capturée -->
                                <div id="previewClient" class="mt-2 d-none">
                                    <img id="previewClientImg" class="img-fluid rounded border" style="max-height: 200px;">
                                    <button type="button" class="btn btn-sm btn-danger mt-1" id="retakeClient">
                                        <i class="fas fa-redo me-1"></i> Reprendre
                                    </button>
                                </div>
                                <!-- Canvas caché pour la capture -->
                                <canvas id="canvasClient" style="display: none;"></canvas>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Description de l'état</label>
                                <textarea class="form-control" name="description_etat" id="description_etat" rows="3" placeholder="Décrivez l'état général de l'appareil..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Étape 3: Signature -->
                    <div class="rachat-step d-none" id="step3">
                        <h4 class="mb-3">Signature du client</h4>
                        <div class="signature-section">
                            <label class="form-label">Signature du client <span class="text-danger">*</span></label>
                            <div class="signature-pad-container">
                                <canvas id="signaturePad" class="signature-pad"></canvas>
                            </div>
                            <div class="signature-controls mt-2">
                                <button type="button" class="btn btn-outline-secondary" id="clearSignature">
                                    <i class="fas fa-eraser me-1"></i> Effacer
                                </button>
                                <div class="form-text mt-2">
                                    <i class="fas fa-pen me-1"></i> Le client doit signer dans le cadre ci-dessus
                                </div>
                            </div>
                            <input type="hidden" name="signature" id="signature" required>
                            <div class="invalid-feedback">La signature est obligatoire</div>
                        </div>
                    </div>

                    <!-- Étape 4: Prix et finalisation -->
                    <div class="rachat-step d-none" id="step4">
                        <h4 class="mb-3">Prix de rachat et finalisation</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Prix de rachat (€) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-euro-sign"></i></span>
                                    <input type="number" class="form-control" name="prix_rachat" id="prix_rachat" step="0.01" min="0" placeholder="0.00" required>
                                    <div class="invalid-feedback">Veuillez saisir un prix valide</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Mode de paiement</label>
                                <select class="form-control" name="mode_paiement" id="mode_paiement">
                                    <option value="especes">Espèces</option>
                                    <option value="cheque">Chèque</option>
                                    <option value="virement">Virement</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Commentaires</label>
                                <textarea class="form-control" name="commentaires" id="commentaires" rows="2" placeholder="Commentaires supplémentaires..."></textarea>
                            </div>

                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Récapitulatif :</strong> Vérifiez toutes les informations avant de finaliser le rachat.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation entre les étapes -->
                    <div class="step-navigation mt-4">
                        <button type="button" class="btn btn-secondary" id="prevStepBtn" style="display: none;">
                            <i class="fas fa-arrow-left me-1"></i> Précédent
                        </button>
                        <button type="button" class="btn modern-btn-primary float-end" id="nextStepBtn">
                            Suivant <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                        <button type="submit" class="btn btn-success float-end" id="submitRachatBtn" style="display: none;">
                            <i class="fas fa-check me-1"></i> Finaliser le rachat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
let currentStep = 1;
let totalSteps = 4;
let currentPage = 1;
let isLoading = false;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeRachatPage();
    loadRachats();
    initializeModal();
});

// Initialisation de la page
function initializeRachatPage() {
    // Gestionnaires d'événements pour la recherche
    document.getElementById('searchInput').addEventListener('input', debounce(handleSearch, 300));
    document.getElementById('filterStatus').addEventListener('change', handleSearch);
    
    // Gestionnaire pour le bouton nouveau rachat
    document.getElementById('newRachatModal').addEventListener('show.bs.modal', function () {
        resetRachatForm();
    });

    // Gestionnaires pour les étapes du formulaire
    document.getElementById('nextStepBtn').addEventListener('click', nextStep);
    document.getElementById('prevStepBtn').addEventListener('click', prevStep);
    document.getElementById('rachatForm').addEventListener('submit', submitRachatForm);

    // Gestionnaires pour la recherche de clients
    document.getElementById('btn_recherche_client').addEventListener('click', searchClients);
    
    // Recherche dynamique dès le 2ème caractère
    let searchTimeout;
    document.getElementById('recherche_client_rachat').addEventListener('input', function(e) {
        const searchTerm = e.target.value.trim();
        
        // Effacer le timeout précédent pour éviter trop de requêtes
        clearTimeout(searchTimeout);
        
        // Si moins de 2 caractères, masquer les résultats
        if (searchTerm.length < 2) {
            document.getElementById('resultats_clients').classList.add('d-none');
            document.getElementById('no_results').classList.add('d-none');
            return;
        }
        
        // Lancer la recherche après un petit délai (300ms)
        searchTimeout = setTimeout(() => {
            console.log('🚀 Recherche automatique déclenchée pour:', searchTerm);
            searchClients();
        }, 300);
    });
    
    // Maintenir la fonctionnalité Entrée pour compatibilité
    document.getElementById('recherche_client_rachat').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(searchTimeout); // Annuler le délai et rechercher immédiatement
            searchClients();
        }
    });
    
    // Gestionnaire pour l'ajout d'un nouveau client
    document.getElementById('btn_nouveau_client').addEventListener('click', function() {
        // Fermer le modal de rachat et ouvrir le modal de nouveau client
        const rachatModal = bootstrap.Modal.getInstance(document.getElementById('newRachatModal'));
        if (rachatModal) {
            rachatModal.hide();
        }
        
        // Ouvrir le modal de nouveau client (utiliser le modal existant de la page)
        if (document.getElementById('nouveauClientModal')) {
            const clientModal = new bootstrap.Modal(document.getElementById('nouveauClientModal'));
            clientModal.show();
        } else if (document.getElementById('nouveauClientModal_commande')) {
            const clientModal = new bootstrap.Modal(document.getElementById('nouveauClientModal_commande'));
            clientModal.show();
        } else {
            // Si aucun modal existe, afficher un message pour rediriger
            showError('Fonctionnalité d\'ajout de client en cours de développement. Vous pouvez ajouter des clients depuis la page Clients.');
        }
    });

    // Gestionnaires pour les aperçus d'images
    initializeImagePreviews();
    
    // Initialiser le pad de signature
    initializeSignaturePad();
}

// Fonction de chargement des rachats
function loadRachats(search = '', page = 1, status = '') {
    if (isLoading) return;
    
    isLoading = true;
    showLoading();

    const formData = new FormData();
    formData.append('search', search);
    formData.append('page', page);
    formData.append('status', status);

    fetch('/ajax/recherche_rachat.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            displayRachats(data.rachats);
            displayPagination(data.pagination);
        } else {
            showError('Erreur lors du chargement des rachats');
        }
    })
    .catch(error => {
        hideLoading();
        showError('Erreur de connexion');
        console.error('Error:', error);
    })
    .finally(() => {
        isLoading = false;
    });
}

// Fonction pour afficher les rachats
function displayRachats(rachats) {
    const tbody = document.getElementById('rachatsTableBody');
    const table = document.getElementById('rachatsTable');
    const noResults = document.getElementById('noResultsMessage');

    if (rachats.length === 0) {
        table.classList.add('d-none');
        noResults.classList.remove('d-none');
        return;
    }

    noResults.classList.add('d-none');
    table.classList.remove('d-none');

    tbody.innerHTML = '';

    rachats.forEach(rachat => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>#${rachat.id}</strong></td>
            <td>${rachat.client_nom} ${rachat.client_prenom}</td>
            <td>
                <div>${rachat.modele}</div>
                <small class="text-muted">${rachat.sin || 'N/A'}</small>
            </td>
            <td>${formatDate(rachat.date_creation)}</td>
            <td><strong>${rachat.prix_rachat} €</strong></td>
            <td><span class="modern-status-badge status-${rachat.statut}">${getStatusLabel(rachat.statut)}</span></td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="showDetails(${rachat.id})" title="Voir détails">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-success" onclick="exportPDF(${rachat.id})" title="Exporter PDF">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteRachat(${rachat.id})" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Fonction pour afficher la pagination
function displayPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    const list = document.getElementById('paginationList');
    const info = document.getElementById('resultsInfo');

    // Mise à jour des informations
    info.textContent = `Affichage de ${pagination.total} résultat(s)`;

    // Générer la pagination
    list.innerHTML = '';
    
    if (pagination.total_pages <= 1) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'flex';

    // Bouton précédent
    if (pagination.current_page > 1) {
        const prevLi = document.createElement('li');
        prevLi.className = 'page-item';
        prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})">Précédent</a>`;
        list.appendChild(prevLi);
    }

    // Pages
    for (let i = 1; i <= pagination.total_pages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === pagination.current_page ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i})">${i}</a>`;
        list.appendChild(li);
    }

    // Bouton suivant
    if (pagination.current_page < pagination.total_pages) {
        const nextLi = document.createElement('li');
        nextLi.className = 'page-item';
        nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})">Suivant</a>`;
        list.appendChild(nextLi);
    }
}

// Gestion de la recherche
function handleSearch() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('filterStatus').value;
    currentPage = 1;
    loadRachats(search, currentPage, status);
}

// Changement de page
function changePage(page) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('filterStatus').value;
    loadRachats(search, currentPage, status);
}

// Gestion des détails de rachat
async function showDetails(id) {
    try {
        const response = await fetch(`/ajax/details_rachat.php?id=${id}`);
        if (!response.ok) {
            throw new Error('Erreur lors de la récupération des détails');
        }
        
        const data = await response.json();
        if (data.success) {
            displayRachatDetails(data.rachat);
            new bootstrap.Modal(document.getElementById('rachatDetailsModal')).show();
        } else {
            showError(data.message || 'Erreur lors de la récupération des détails');
        }
    } catch (error) {
        showError('Erreur de connexion');
        console.error('Error:', error);
    }
}

// Afficher les détails du rachat dans le modal
function displayRachatDetails(rachat) {
    document.getElementById('modalClientName').textContent = `${rachat.client_nom} ${rachat.client_prenom}`;
    document.getElementById('modalRachatDate').textContent = `Date: ${formatDate(rachat.date_creation)}`;
    document.getElementById('modalRachatPrice').textContent = `${rachat.prix_rachat} €`;
    document.getElementById('modalRachatState').textContent = getStatusLabel(rachat.statut);
    document.getElementById('modalRachatState').className = `badge modern-status-badge status-${rachat.statut}`;

    // Images
    if (rachat.photo_identite) {
        document.getElementById('modalIdentite').src = rachat.photo_identite;
    }
    if (rachat.photo_appareil) {
        document.getElementById('modalAppareil').src = rachat.photo_appareil;
    }
    if (rachat.photo_client) {
        document.getElementById('modalPhotoClient').src = rachat.photo_client;
    }
    if (rachat.signature) {
        document.getElementById('modalSignature').src = rachat.signature;
    }
}

// Gestion du formulaire multi-étapes
function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
            updateStepIndicator();
        }
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
        updateStepIndicator();
    }
}

function showStep(step) {
    // Masquer toutes les étapes
    document.querySelectorAll('.rachat-step').forEach(stepDiv => {
        stepDiv.classList.add('d-none');
    });
    
    // Afficher l'étape actuelle
    document.getElementById(`step${step}`).classList.remove('d-none');
    
    // Gérer les boutons de navigation
    document.getElementById('prevStepBtn').style.display = step > 1 ? 'inline-block' : 'none';
    document.getElementById('nextStepBtn').style.display = step < totalSteps ? 'inline-block' : 'none';
    document.getElementById('submitRachatBtn').style.display = step === totalSteps ? 'inline-block' : 'none';
}

function updateStepIndicator() {
    document.querySelectorAll('.step-counter').forEach((counter, index) => {
        counter.classList.remove('active', 'completed');
        if (index + 1 < currentStep) {
            counter.classList.add('completed');
        } else if (index + 1 === currentStep) {
            counter.classList.add('active');
        }
    });
}

function validateCurrentStep() {
    // Validation selon l'étape actuelle
    switch (currentStep) {
        case 1:
            return validateStep1();
        case 2:
            return validateStep2();
        case 3:
            return validateStep3();
        case 4:
            return validateStep4();
        default:
            return true;
    }
}

function validateStep1() {
    const clientId = document.getElementById('client_id').value;
    
    if (!clientId) {
        showError('Veuillez sélectionner un client');
        return false;
    }
    
    // L'étape 1 ne demande que la sélection du client
    // Les photos seront demandées à l'étape 2
    return true;
}

function validateStep2() {
    const modele = document.getElementById('modele').value;
    const photoIdentite = document.getElementById('photo_identite').files[0] || document.getElementById('previewIdentiteImg').src;
    const photoAppareil = document.getElementById('photo_appareil').files[0] || document.getElementById('previewAppareilImg').src;
    const photoClient = document.getElementById('photo_client').files[0] || document.getElementById('previewClientImg').src;
    
    if (!modele.trim()) {
        showError('Veuillez saisir le modèle de l\'appareil');
        return false;
    }
    
    if (!photoIdentite) {
        showError('Veuillez ajouter une photo de la pièce d\'identité');
        return false;
    }
    
    if (!photoAppareil) {
        showError('Veuillez ajouter une photo de l\'appareil');
        return false;
    }
    
    if (!photoClient) {
        showError('Veuillez ajouter une photo du client avec l\'appareil');
        return false;
    }
    
    return true;
}

function validateStep3() {
    const signature = document.getElementById('signature').value;
    
    if (!signature) {
        showError('La signature du client est obligatoire');
        return false;
    }
    
    return true;
}

function validateStep4() {
    const prix = document.getElementById('prix_rachat').value;
    
    if (!prix || prix <= 0) {
        showError('Veuillez saisir un prix de rachat valide');
        return false;
    }
    
    return true;
}

// Soumission du formulaire
async function submitRachatForm(e) {
    e.preventDefault();
    
    if (!validateCurrentStep()) {
        return;
    }
    
    const form = document.getElementById('rachatForm');
    const formData = new FormData(form);
    
    // Ajouter la signature si elle existe
    const signature = document.getElementById('signature').value;
    if (signature) {
        formData.set('signature', signature);
    }
    
    try {
        const submitBtn = document.getElementById('submitRachatBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...';
        
        const response = await fetch('/ajax/save_rachat.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Rachat enregistré avec succès');
            bootstrap.Modal.getInstance(document.getElementById('newRachatModal')).hide();
            resetRachatForm();
            loadRachats(); // Recharger la liste
        } else {
            showError(data.message || 'Erreur lors de l\'enregistrement');
        }
    } catch (error) {
        showError('Erreur de connexion');
        console.error('Error:', error);
    } finally {
        const submitBtn = document.getElementById('submitRachatBtn');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check me-1"></i> Finaliser le rachat';
    }
}

// Réinitialisation du formulaire
function resetRachatForm() {
    currentStep = 1;
    document.getElementById('rachatForm').reset();
    document.querySelectorAll('.alert').forEach(alert => alert.classList.add('d-none'));
    document.querySelectorAll('[id^="preview"]').forEach(preview => preview.classList.add('d-none'));
    showStep(1);
    updateStepIndicator();
    
    // Réinitialiser la recherche client
    document.getElementById('client_selectionne').classList.add('d-none');
    document.getElementById('resultats_clients').classList.add('d-none');
    document.getElementById('no_results').classList.add('d-none');
    
    // Réinitialiser le pad de signature
    if (window.signaturePad) {
        window.signaturePad.clear();
        document.getElementById('signature').value = '';
    }
}

// Recherche de clients
async function searchClients() {
    const searchTerm = document.getElementById('recherche_client_rachat').value.trim();
    
    if (searchTerm.length < 2) {
        showError('Veuillez saisir au moins 2 caractères');
        return;
    }
    
    // Masquer les résultats précédents
    document.getElementById('resultats_clients').classList.add('d-none');
    document.getElementById('no_results').classList.add('d-none');
    
    try {
        console.log('🔍 Recherche de clients avec le terme:', searchTerm);
        
        const response = await fetch('ajax/recherche_clients.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `terme=${encodeURIComponent(searchTerm)}`
        });
        
        console.log('📡 Réponse HTTP reçue:', response.status);
        
        const data = await response.json();
        console.log('📋 Données reçues:', data);
        
        if (data.success) {
            displayClientResults(data.clients);
            console.log('✅ Affichage des résultats:', data.clients.length, 'clients trouvés');
        } else {
            showNoClientResults();
            showError(data.message || 'Erreur lors de la recherche');
            console.error('❌ Erreur serveur:', data.message);
        }
    } catch (error) {
        showError('Erreur lors de la recherche');
        console.error('❌ Erreur fetch:', error);
    }
}

// Afficher les résultats de recherche de clients
function displayClientResults(clients) {
    const resultsDiv = document.getElementById('resultats_clients');
    const noResultsDiv = document.getElementById('no_results');
    const tbody = document.getElementById('liste_clients');
    
    if (clients.length === 0) {
        resultsDiv.classList.add('d-none');
        noResultsDiv.classList.remove('d-none');
        return;
    }
    
    noResultsDiv.classList.add('d-none');
    resultsDiv.classList.remove('d-none');
    
    tbody.innerHTML = '';
    
    clients.forEach(client => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${client.nom}</td>
            <td>${client.prenom}</td>
            <td>${client.telephone || 'N/A'}</td>
            <td>
                <button class="btn btn-sm modern-btn-primary" onclick="selectClient(${client.id}, '${client.nom}', '${client.prenom}')">
                    <i class="fas fa-check me-1"></i> Sélectionner
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Afficher message aucun résultat
function showNoClientResults() {
    document.getElementById('resultats_clients').classList.add('d-none');
    document.getElementById('no_results').classList.remove('d-none');
}

// Sélectionner un client
function selectClient(id, nom, prenom) {
    document.getElementById('client_id').value = id;
    document.getElementById('nom_client_selectionne').textContent = `${nom} ${prenom}`;
    document.getElementById('client_selectionne').classList.remove('d-none');
    document.getElementById('resultats_clients').classList.add('d-none');
    document.getElementById('no_results').classList.add('d-none');
}

// Réinitialiser la sélection client
document.getElementById('reset_client')?.addEventListener('click', function() {
    document.getElementById('client_id').value = '';
    document.getElementById('client_selectionne').classList.add('d-none');
});

// Initialisation des aperçus d'images
function initializeImagePreviews() {
    ['identite', 'appareil', 'client'].forEach(type => {
        const input = document.getElementById(`photo_${type}`);
        const preview = document.getElementById(`preview${type.charAt(0).toUpperCase() + type.slice(1)}`);
        const img = document.getElementById(`preview${type.charAt(0).toUpperCase() + type.slice(1)}Img`);
        const retake = document.getElementById(`retake${type.charAt(0).toUpperCase() + type.slice(1)}`);
        
        if (input) {
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        img.src = e.target.result;
                        preview.classList.remove('d-none');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
        
        if (retake) {
            retake.addEventListener('click', function() {
                input.value = '';
                preview.classList.add('d-none');
            });
        }
    });
}

// Initialisation du pad de signature
function initializeSignaturePad() {
    const canvas = document.getElementById('signaturePad');
    if (canvas) {
        // Configuration du canvas
        canvas.width = 500;
        canvas.height = 200;
        canvas.style.border = '2px solid #e2e8f0';
        canvas.style.borderRadius = '12px';
        canvas.style.width = '100%';
        canvas.style.maxWidth = '500px';
        
        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        
        function startDrawing(e) {
            isDrawing = true;
            draw(e);
        }
        
        function draw(e) {
            if (!isDrawing) return;
            
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#000';
            
            ctx.lineTo(x, y);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(x, y);
        }
        
        function stopDrawing() {
            if (isDrawing) {
                isDrawing = false;
                ctx.beginPath();
                // Sauvegarder la signature
                document.getElementById('signature').value = canvas.toDataURL();
            }
        }
        
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);
        
        // Support tactile
        canvas.addEventListener('touchstart', function(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousedown', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        });
        
        canvas.addEventListener('touchmove', function(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousemove', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        });
        
        canvas.addEventListener('touchend', function(e) {
            e.preventDefault();
            const mouseEvent = new MouseEvent('mouseup', {});
            canvas.dispatchEvent(mouseEvent);
        });
        
        window.signaturePad = {
            clear: function() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                document.getElementById('signature').value = '';
            }
        };
        
        // Bouton effacer
        document.getElementById('clearSignature')?.addEventListener('click', function() {
            window.signaturePad.clear();
        });
    }
}

// Gestion de la caméra
function openCameraModal(type) {
    window.currentCameraType = type;
    document.getElementById('cameraModalTitle').textContent = `Prendre une photo - ${getPhotoTypeLabel(type)}`;
    
    // S'assurer que le modal caméra s'affiche au-dessus
    const cameraModal = document.getElementById('cameraModal');
    cameraModal.style.zIndex = '1070';
    
    const modalInstance = new bootstrap.Modal(cameraModal, {
        backdrop: 'static',
        keyboard: false
    });
    
    // Gérer le backdrop après ouverture
    cameraModal.addEventListener('shown.bs.modal', function() {
        const backdrop = document.querySelector('.modal-backdrop:last-of-type');
        if (backdrop) {
            backdrop.style.zIndex = '1065';
        }
    }, { once: true });
    
    modalInstance.show();
    
    // Délai pour initialiser la caméra après l'ouverture complète du modal
    setTimeout(() => {
        initCamera();
    }, 300);
}

function getPhotoTypeLabel(type) {
    const labels = {
        'identite': 'Pièce d\'identité',
        'appareil': 'Appareil',
        'client': 'Client avec appareil'
    };
    return labels[type] || type;
}

function initCamera() {
    const video = document.getElementById('cameraVideoFullscreen');
    
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => {
            video.srcObject = stream;
            window.currentStream = stream;
        })
        .catch(error => {
            console.error('Erreur caméra:', error);
            showError('Impossible d\'accéder à la caméra');
        });
}

function takePictureFullscreen() {
    const video = document.getElementById('cameraVideoFullscreen');
    const canvas = document.getElementById('cameraCanvasFullscreen');
    const ctx = canvas.getContext('2d');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    ctx.drawImage(video, 0, 0);
    
    canvas.toBlob(blob => {
        const file = new File([blob], `photo_${window.currentCameraType}.jpg`, { type: 'image/jpeg' });
        
        // Créer un DataTransfer pour simuler la sélection de fichier
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        
        const input = document.getElementById(`photo_${window.currentCameraType}`);
        input.files = dataTransfer.files;
        
        // Déclencher l'événement change pour l'aperçu
        input.dispatchEvent(new Event('change'));
        
        closeCameraModal();
    }, 'image/jpeg', 0.8);
}

function closeCameraModal() {
    if (window.currentStream) {
        window.currentStream.getTracks().forEach(track => track.stop());
    }
    
    const cameraModal = document.getElementById('cameraModal');
    const modalInstance = bootstrap.Modal.getInstance(cameraModal);
    
    if (modalInstance) {
        modalInstance.hide();
    }
    
    // Nettoyer les z-index après fermeture
    cameraModal.addEventListener('hidden.bs.modal', function() {
        // Réinitialiser les z-index
        cameraModal.style.zIndex = '';
        
        // Nettoyer les backdrops orphelins
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            if (!backdrop.previousElementSibling || !backdrop.previousElementSibling.classList.contains('show')) {
                backdrop.remove();
            }
        });
    }, { once: true });
}

// Fonctions utilitaires
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getStatusLabel(status) {
    const labels = {
        'nouveau': 'Nouveau',
        'en_cours': 'En cours',
        'termine': 'Terminé'
    };
    return labels[status] || status;
}

function showLoading() {
    document.getElementById('loadingSpinner').classList.remove('d-none');
    document.getElementById('rachatsTable').classList.add('d-none');
    document.getElementById('noResultsMessage').classList.add('d-none');
}

function hideLoading() {
    document.getElementById('loadingSpinner').classList.add('d-none');
}

function showError(message) {
    // Vous pouvez utiliser une bibliothèque de toast ou afficher dans un élément existant
    alert('Erreur: ' + message);
}

function showSuccess(message) {
    // Vous pouvez utiliser une bibliothèque de toast ou afficher dans un élément existant
    alert('Succès: ' + message);
}

// Export PDF
function exportPDF(id) {
    window.open(`/ajax/export_rachat_pdf.php?id=${id}`, '_blank');
}

// Suppression de rachat
function deleteRachat(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce rachat ?')) {
        fetch(`/ajax/delete_rachat.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('Rachat supprimé avec succès');
                loadRachats();
            } else {
                showError(data.message || 'Erreur lors de la suppression');
            }
        })
        .catch(error => {
            showError('Erreur de connexion');
            console.error('Error:', error);
        });
    }
}

function initializeModal() {
    // Gérer les boutons de téléchargement d'images
    document.querySelectorAll('.download-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const imgId = this.getAttribute('data-img');
            const img = document.getElementById(imgId);
            if (img && img.src) {
                const link = document.createElement('a');
                link.href = img.src;
                link.download = `${imgId}.jpg`;
                link.click();
            }
        });
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
