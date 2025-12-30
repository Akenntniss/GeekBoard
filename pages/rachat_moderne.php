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

// Debug: Afficher le rôle de l'utilisateur
echo '<script>console.log("User role:", ' . json_encode($_SESSION['role'] ?? 'NOT SET') . ');</script>';
?>

<style>
/* ========================================
   FIX NAVBAR & ANIMATION SERVO
   ======================================== */
@media (max-width: 991.98px) {
    /* Masquer la navbar desktop sur mobile */
    #desktop-navbar, nav#desktop-navbar {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }
    
    /* Correction du container sur mobile pour afficher le titre */
    .modern-page-container {
        margin-top: 0 !important;
        padding-top: 1rem !important;
    }
}

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

/* ========================================
   MODE NUIT - MODAL RACHAT DETAILS
   ======================================== */
@media (prefers-color-scheme: dark) {
    #rachatDetailsModal .modal-content {
        background-color: #1e293b !important;
        color: #e2e8f0 !important;
    }
    
    #rachatDetailsModal .modal-header {
        background-color: #0f172a !important;
        border-bottom-color: #334155 !important;
    }
    
    #rachatDetailsModal .modal-body {
        background-color: #1e293b !important;
    }
    
    #rachatDetailsModal .card {
        background-color: #0f172a !important;
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    
    #rachatDetailsModal .card-header {
        background-color: #1e293b !important;
        border-bottom-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    
    #rachatDetailsModal .card-body {
        background-color: #0f172a !important;
    }
    
    #rachatDetailsModal .text-muted {
        color: #94a3b8 !important;
    }
    
    #rachatDetailsModal .btn-close-white {
        filter: brightness(0) invert(1);
    }
    
    #rachatDetailsModal .modal-dialog {
        margin-top: 80px !important;
    }

    /* Fix pour le modal caméra en mode nuit */
    #cameraModal .modal-content {
        background-color: #000 !important; /* Fond noir pour la caméra */
        color: #fff !important;
    }
    
    #cameraModal .modal-header {
        background-color: #000 !important;
        border-bottom: 1px solid #333 !important;
    }
    
    #cameraModal .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }
}

/* ========================================
   STYLES CAMÉRA ET MODAL (PORTÉS DE RACHAT_APPAREILS.PHP)
   ======================================== */

/* ===== CORRECTION Z-INDEX CAMERA ===== */
.camera-preview {
    position: relative !important;
    z-index: 1070 !important; /* Plus haut que le modal (1055) */
}

#cameraVideo {
    position: relative !important;
    z-index: 1071 !important;
    background-color: #000 !important;
}

#cameraCanvas {
    position: relative !important;
    z-index: 1072 !important;
}

.photo-preview {
    position: relative !important;
    z-index: 1069 !important;
}

/* Assurer que les boutons de photo soient visibles */
#takePhotoIdentite,
#takePhotoAppareil {
    position: relative !important;
    z-index: 1068 !important;
}

/* Correction pour le modal newRachatModal */
#newRachatModal .camera-preview {
    z-index: 1070 !important;
    display: block !important;
    visibility: visible !important;
}

#newRachatModal #cameraVideo {
    z-index: 1071 !important;
    display: block !important;
    visibility: visible !important;
}

/* Assurer que la vidéo est au premier plan */
.camera-preview.d-none {
    display: block !important;
}

.camera-preview video {
    z-index: 1075 !important;
    position: relative !important;
}

/* ===== MODAL CAMERA PLEIN ÉCRAN ===== */
#cameraModal {
    z-index: 2000 !important;
}

#cameraModal .modal-dialog {
    margin: 0 !important;
    max-width: none !important;
    height: 100vh !important;
    width: 100vw !important;
}

#cameraModal .modal-content {
    height: 100vh !important;
    border: none !important;
    border-radius: 0 !important;
}

.camera-preview-fullscreen {
    max-width: 90vw;
    max-height: 70vh;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    background: #000;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
}

.camera-video-fullscreen {
    width: 100%;
    height: auto;
    max-height: 70vh;
    object-fit: cover;
    display: block;
    background: #000;
}

#cameraCanvasFullscreen {
    width: 100%;
    height: auto;
    max-height: 70vh;
}

.camera-container {
    width: 100%;
    max-width: 1200px;
}

.camera-controls {
    padding: 20px 0;
}

.camera-controls .btn {
    font-size: 18px;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
}

#cameraInstructions {
    font-size: 16px;
    max-width: 600px;
    margin: 0 auto;
}

/* Animation d'ouverture du modal caméra */
#cameraModal.show {
    animation: cameraModalFadeIn 0.3s ease-out;
}

@keyframes cameraModalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* ===== STYLES SPÉCIFIQUES RACHAT ===== */
.clean-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.clean-badge-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #22c55e;
}

.clean-badge-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

/* Mode nuit pour les badges */
body.dark-mode .clean-badge-success {
    background: #065f46;
    color: #6ee7b7;
    border: 1px solid #10b981;
}

body.dark-mode .clean-badge-danger {
    background: #7f1d1d;
    color: #fca5a5;
    border: 1px solid #ef4444;
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
    overflow-x: auto; /* Scroll horizontal sur mobile */
    box-shadow: 0 8px 32px var(--day-shadow);
    border: 1px solid var(--day-border);
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
    white-space: nowrap; /* Empêcher le retour à la ligne pour forcer le scroll si nécessaire */
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

/* Style pour la ligne active (sélectionnée) */
.table-active {
    background-color: rgba(59, 130, 246, 0.15) !important;
}

/* Mode nuit pour la ligne active */
body.night-mode .table-active {
    background-color: rgba(0, 212, 255, 0.2) !important;
    box-shadow: inset 3px 0 0 var(--night-primary);
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
    box-shadow: 0 0 0 0.2rem rgba(var(--day-primary-rgb), 0.25);
}

/* Modal rachat - forcer fond clair en mode nuit */
body.night-mode #newRachatModal_v2 .modal-body {
    background: #1e293b !important;
    color: #e2e8f0 !important;
}

body.night-mode #newRachatModal_v2 .rachat-step {
    background: transparent !important;
    color: #e2e8f0 !important;
}
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

/* Styles complets pour le mode nuit */
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

body.night-mode .modern-table {
    background: #0f172a;
}

body.night-mode .modern-table th {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: var(--night-text);
}

body.night-mode .modern-table tbody td {
    color: var(--night-text) !important;
    background: rgba(15, 23, 42, 0.5);
}

body.night-mode .modern-search-input,
body.night-mode .modern-select,
body.night-mode .modern-form-input {
    background: rgba(15, 23, 42, 0.8);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode .modal-content {
    background: var(--night-card-bg);
    border: 1px solid var(--night-border);
}

body.night-mode .modal-header {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary)) !important;
}

body.night-mode .modal-body {
    background: var(--night-card-bg);
    color: var(--night-text);
}

body.night-mode .modal-body .form-label,
body.night-mode .modal-body label {
    color: var(--night-text) !important;
}

body.night-mode .modal-body .form-text {
    color: var(--night-text-light) !important;
}

body.night-mode .form-select {
    background: var(--night-card-bg) !important;
    border-color: var(--night-border) !important;
    color: var(--night-text) !important;
}

body.night-mode .form-select:focus {
    border-color: var(--night-primary) !important;
    box-shadow: var(--night-glow) !important;
}

body.night-mode .form-select option {
    background: var(--night-card-bg) !important;
    color: var(--night-text) !important;
}

body.night-mode .btn-outline-primary {
    border-color: var(--night-primary) !important;
    color: var(--night-primary) !important;
    background: var(--night-card-bg) !important;
}

body.night-mode .btn-outline-primary:hover {
    background: var(--night-primary) !important;
    color: var(--night-text) !important;
}

body.night-mode .input-group-text {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary)) !important;
    color: var(--night-text) !important;
}

body.night-mode .stepper-item.active .step-name {
    color: var(--night-primary);
}

body.night-mode .step-counter.active {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
}

body.night-mode .form-control::placeholder {
    color: var(--night-text-light) !important;
}

body.night-mode .text-muted {
    color: var(--night-text-light) !important;
}

body.night-mode .alert {
    background: var(--night-card-bg) !important;
    border-color: var(--night-border) !important;
    color: var(--night-text) !important;
}

body.night-mode .dropdown-menu {
    background: var(--night-card-bg) !important;
    border-color: var(--night-border) !important;
}

body.night-mode .dropdown-item {
    color: var(--night-text) !important;
}

body.night-mode .dropdown-item:hover {
    background: rgba(0, 212, 255, 0.1) !important;
    color: var(--night-primary) !important;
}

/* Corrections spécifiques pour la navbar en mode nuit */
body.night-mode #desktop-navbar,
body.night-mode nav#desktop-navbar,
body.night-mode .navbar {
    background: var(--night-card-bg) !important;
    border-bottom: 1px solid var(--night-border) !important;
    box-shadow: 0 2px 10px var(--night-shadow) !important;
}

body.night-mode #desktop-navbar .navbar-brand,
body.night-mode #desktop-navbar .nav-link,
body.night-mode #desktop-navbar .navbar-text {
    color: var(--night-text) !important;
}

body.night-mode #desktop-navbar .nav-link:hover {
    color: var(--night-primary) !important;
}

body.night-mode #desktop-navbar .servo-logo-container .servo-text,
body.night-mode #desktop-navbar .servo-logo-container .animated-text {
    color: var(--night-primary) !important;
}

/* Corrections pour les champs de recherche en mode nuit */
body.night-mode #searchInput,
body.night-mode #filterStatus {
    background: var(--night-card-bg) !important;
    color: var(--night-text) !important;
    border: 2px solid var(--night-border) !important;
}

body.night-mode #searchInput:focus,
body.night-mode #filterStatus:focus {
    background: var(--night-card-bg) !important;
    color: var(--night-text) !important;
    border-color: var(--night-primary) !important;
    box-shadow: 0 0 0 0.2rem var(--night-shadow) !important;
}

body.night-mode #searchInput::placeholder {
    color: var(--night-text-light) !important;
    opacity: 0.8;
}

/* Corrections pour les options des selects en mode nuit */
body.night-mode #filterStatus option {
    background: var(--night-card-bg) !important;
    color: var(--night-text) !important;
}

/* Corrections pour les input-group-text en mode nuit */
body.night-mode .input-group-text {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary)) !important;
    color: var(--night-text) !important;
    border: 1px solid var(--night-border) !important;
}

/* Corrections pour tous les éléments de navigation en mode nuit */
body.night-mode .navbar-nav .nav-item .nav-link {
    color: var(--night-text) !important;
}

body.night-mode .navbar-nav .nav-item .nav-link:hover,
body.night-mode .navbar-nav .nav-item .nav-link:focus {
    color: var(--night-primary) !important;
}

body.night-mode .navbar-toggler {
    border-color: var(--night-border) !important;
}

body.night-mode .navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2800, 212, 255, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
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

/* S'assurer que le modal rachat reste en dessous (sauf du modal caméra) */
#newRachatModal_v2 {
    z-index: 1060 !important; /* Au-dessus des backdrops standards (1055) */
}

#newRachatModal_v2 + .modal-backdrop {
    z-index: 1055 !important; /* Backdrop standard */
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

/* ===== STYLES POUR LE CANVAS DE SIGNATURE ===== */
.signature-pad {
    background: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    cursor: crosshair;
    touch-action: none;
}

.signature-pad canvas {
    display: block;
    width: 100% !important;
    height: 200px !important;
    border-radius: 6px;
}

/* Styles pour la section photo client */
.photo-preview {
    min-height: 200px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.photo-preview img {
    max-width: 100%;
    max-height: 200px;
    object-fit: contain;
}

#photoPlaceholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 180px;
    color: #6c757d;
}

#photoPlaceholder i {
    color: #cbd5e0;
}

#photoPlaceholder p {
    margin-top: 10px;
    font-size: 0.9rem;
}

/* Mode nuit pour les styles signature - OVERRIDES AGRESSIFS */
body.night-mode .signature-pad {
    background: #ffffff !important;
    border: 3px solid #00ffff !important;
    box-shadow: 0 0 20px rgba(0, 255, 255, 0.5) !important;
}

body.night-mode .signature-pad canvas {
    background: #ffffff !important;
}

body.night-mode .photo-preview {
    background: rgba(30, 41, 59, 0.8) !important;
    border: 2px solid #00ffff !important;
}

body.night-mode #photoPlaceholder {
    color: #00ffff !important;
}

body.night-mode #photoPlaceholder i {
    color: #00ffff !important;
}

/* ÉTAPE 3 - Forcer TOUS les éléments à être visibles */
body.night-mode #step3 {
    background: #1e293b !important;
    padding: 20px !important;
    min-height: 400px !important;
}

body.night-mode #step3 * {
    color: #e2e8f0 !important;
}

body.night-mode #step3 h4 {
    color: #00ffff !important;
    font-size: 1.5rem !important;
    margin-bottom: 1.5rem !important;
}

body.night-mode #step3 .form-label {
    color: #00ffff !important;
    font-weight: 600 !important;
}

body.night-mode #step3 .form-text {
    color: #94a3b8 !important;
}

body.night-mode #step3 .card {
    background: rgba(51, 65, 85, 0.8) !important;
    border: 2px solid rgba(0, 255, 255, 0.3) !important;
}

body.night-mode #step3 .card-header {
    background: rgba(0, 255, 255, 0.15) !important;
    border-bottom: 1px solid rgba(0, 255, 255, 0.4) !important;
    color: #00ffff !important;
}

body.night-mode #step3 .card-body {
    color: #e2e8f0 !important;
}

body.night-mode #step3 .card-body p {
    color: #cbd5e0 !important;
    margin-bottom: 0.5rem !important;
}

body.night-mode #step3 .card-body strong {
    color: #00ffff !important;
}

body.night-mode #step3 .btn {
    opacity: 1 !important;
    visibility: visible !important;
}

body.night-mode #step3 .camera-notice {
    color: #94a3b8 !important;
}

/* ===== STYLES POUR STEP3 (Mode Nuit) ===== */
body.night-mode #step3 {
    background: #1e293b;
    padding: 20px;
    border-radius: 8px;
}

body.night-mode #step3 h4 {
    color: #00ffff;
}

body.night-mode #step3 .card {
    background: rgba(51, 65, 85, 0.8);
    border: 1px solid rgba(0, 255, 255, 0.3);
}

body.night-mode #step3 .card-header {
    background: rgba(0, 255, 255, 0.15);
    color: #00ffff;
}

body.night-mode #step3 .card-body {
    color: #e2e8f0;
}

body.night-mode #step3 .signature-pad {
    background: #ffffff;
    border: 2px solid #e2e8f0;
}

body.night-mode #step3 .signature-pad canvas {
    background: #ffffff;
    width: 100%;
    height: 200px;
}

body.night-mode #step3 {
    background: #1e293b !important;
    padding: 30px !important;
}

body.night-mode #step3 h4 {
    color: #00ffff !important;
    font-size: 1.5rem !important;
}

body.night-mode #step3 label {
    color: #e2e8f0 !important;
}

body.night-mode #step3 .form-text {
    color: #94a3b8 !important;
}

body.night-mode #step3 .card {
    background: rgba(51, 65, 85, 0.8) !important;
    border: 2px solid rgba(0, 255, 255, 0.3) !important;
}

body.night-mode #step3 .card-header {
    background: rgba(0, 255, 255, 0.15) !important;
    color: #00ffff !important;
}

body.night-mode #step3 .card-body {
    color: #e2e8f0 !important;
}

body.night-mode #step3 .card-body p {
    color: #cbd5e0 !important;
}

body.night-mode #step3 .signature-pad {
    background: #ffffff !important;
    border: 2px solid #e2e8f0 !important;
}

body.night-mode #step3 .signature-pad canvas {
    background: #ffffff !important;
}

body.night-mode #step3 .photo-preview {
    background: rgba(30, 41, 59, 0.8) !important;
    border: 2px solid rgba(148, 163, 184, 0.5) !important;
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
                <button type="button" class="btn modern-btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#newRachatModal_v2">
                    <i class="fas fa-plus me-2"></i>
                    Nouveau Rachat
                </button>
                <button type="button" class="btn btn-outline-primary btn-lg ms-2" id="btnBulkExport" disabled onclick="exportBulkPDF()">
                    <i class="fas fa-file-pdf me-2"></i>
                    Exporter la sélection <span id="selectedCount" class="badge bg-primary ms-1 d-none">0</span>
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
                        <th style="width: 40px;" class="d-none d-md-table-cell">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAllRachats" onchange="toggleSelectAll(this)">
                            </div>
                        </th>
                        <th class="d-none d-md-table-cell"><i class="fas fa-hashtag me-2"></i>ID</th>
                        <th><i class="fas fa-user me-2"></i>Client</th>
                        <th><i class="fas fa-mobile-alt me-2"></i>Appareil</th>
                        <th class="d-none d-md-table-cell"><i class="fas fa-calendar me-2"></i>Date</th>
                        <th class="d-none d-md-table-cell"><i class="fas fa-euro-sign me-2"></i>Prix</th>
                        <th class="d-none d-md-table-cell"><i class="fas fa-info-circle me-2"></i>Statut</th>
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
<!-- Modal Nouveau Rachat -->
<div class="modal fade" id="newRachatModal_v2" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
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
                            <div class="card-header bg-primary bg-opacity-10">
                                <i class="fas fa-search me-2"></i> Rechercher un client existant
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-search text-primary"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0" id="recherche_client_rachat" placeholder="Rechercher par nom, prénom ou téléphone...">
                                        <button type="button" class="btn btn-primary rounded-end shadow-sm" id="btn_recherche_client">
                                            <i class="fas fa-search me-1"></i> Rechercher
                                        </button>
                                    </div>
                                    <div class="form-text">Saisissez au moins 2 caractères pour lancer la recherche</div>
                                </div>

                                <div id="resultats_clients" class="mb-3 d-none">
                                    <div class="search-results-wrapper">
                                        <table class="search-table">
                                            <thead class="search-table-head">
                                                <tr>
                                                    <th class="search-th"><i class="fas fa-user me-2"></i>Nom</th>
                                                    <th class="search-th"><i class="fas fa-user-tag me-2"></i>Prénom</th>
                                                    <th class="search-th"><i class="fas fa-phone me-2"></i>Téléphone</th>
                                                    <th class="search-th"><i class="fas fa-cog me-2"></i>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="search-table-body" id="liste_clients">
                                                <!-- Résultats de recherche ici -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div id="no_results" class="alert alert-info d-none">
                                    <p class="mb-2"><i class="fas fa-info-circle me-2"></i>Aucun client trouvé avec ces critères.</p>
                                    <button type="button" class="btn btn-primary" id="btn_nouveau_client">
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
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type d'appareil <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-mobile-alt"></i></span>
                                    <select class="form-select" name="type_appareil" id="type_appareil" required>
                                        <option value="">Sélectionnez le type d'appareil</option>
                                        <option value="smartphone">Smartphone</option>
                                        <option value="tablette">Tablette</option>
                                        <option value="ordinateur_portable">Ordinateur portable</option>
                                        <option value="ordinateur_fixe">Ordinateur fixe</option>
                                        <option value="console_jeux">Console de jeux</option>
                                        <option value="montre_connectee">Montre connectée</option>
                                        <option value="ecouteurs">Écouteurs/Casque</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                    <div class="invalid-feedback">Veuillez sélectionner le type d'appareil</div>
                                </div>
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
                                <label class="form-label">État <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group" aria-label="État de l'appareil">
                                    <input type="radio" class="btn-check" name="fonctionnel" id="fonctionnel_1" value="1" checked required>
                                    <label class="btn btn-outline-success" for="fonctionnel_1">
                                        <i class="fas fa-check-circle me-2"></i>Fonctionnel
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="fonctionnel" id="fonctionnel_0" value="0" required>
                                    <label class="btn btn-outline-danger" for="fonctionnel_0">
                                        <i class="fas fa-times-circle me-2"></i>Non fonctionnel
                                    </label>
                                </div>
                                <div class="invalid-feedback">Veuillez sélectionner l'état de l'appareil</div>
                            </div>
                        </div>
                    </div>

                    <!-- Étape 3: Conditions générales et Signature -->
                    <div class="rachat-step d-none" id="step3">
                        <h4 class="mb-3">Conditions générales et Signature</h4>
                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <div class="card border-info">
                                    <div class="card-header bg-info bg-opacity-10">
                                        <i class="fas fa-file-contract me-2"></i>Conditions générales de rachat
                                    </div>
                                    <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                                        <p><strong>1. Propriété</strong> - Le client certifie être le propriétaire légitime de l'appareil.</p>
                                        <p><strong>2. État</strong> - Le client s'engage à décrire fidèlement l'état de l'appareil.</p>
                                        <p><strong>3. Données</strong> - Le client est responsable de la suppression de ses données personnelles.</p>
                                        <p><strong>4. Prix</strong> - Le prix de rachat est ferme et définitif après acceptation.</p>
                                        <p><strong>5. Transaction</strong> - Une fois le rachat effectué, la transaction est considérée comme définitive.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold mb-0">Signature du client <span class="text-danger">*</span></label>
                                    <div class="form-text camera-notice">
                                        <i class="fas fa-camera me-1"></i> Une photo du client sera prise pendant la signature
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <div class="signature-pad border rounded p-2">
                                            <canvas id="signatureCanvas"></canvas>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <div class="form-text">
                                                <i class="fas fa-pen me-1"></i> Signez dans le cadre ci-dessus
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSignature()">
                                                <i class="fas fa-eraser me-1"></i>Effacer
                                            </button>
                                        </div>
                                        <input type="hidden" name="signature" id="signature">
                                    </div>
                                    <div class="col-md-4" style="opacity: 0; position: absolute; z-index: -1000; pointer-events: none;">
                                        <div class="camera-preview mb-2">
                                            <video id="cameraVideo" autoplay muted playsinline class="w-100 rounded"></video>
                                            <canvas id="cameraCanvas" class="d-none"></canvas>
                                        </div>
                                        <div id="photoPreview" class="photo-preview border rounded p-2 text-center">
                                            <img id="capturedPhoto" class="img-fluid d-none" alt="Photo client">
                                            <div id="photoPlaceholder" class="text-muted">
                                                <i class="fas fa-user fa-3x mb-2"></i>
                                                <p>Photo automatique lors de la signature</p>
                                            </div>
                                        </div>
                                        <input type="hidden" name="client_photo" id="clientPhotoInput">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Étape 4: Prix -->
                    <div class="rachat-step d-none" id="step4">
                        <h4 class="mb-3">Prix de rachat</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Prix (€) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-euro-sign"></i></span>
                                    <input type="number" step="0.01" class="form-control" name="prix_rachat" id="prix" required>
                                    <div class="invalid-feedback">Veuillez saisir un prix de rachat</div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Le prix est déterminé en fonction du modèle et de l'état de l'appareil.
                                </div>
                            </div>
                            
                            <div class="col-md-12 mt-4">
                                <div class="card border-success">
                                    <div class="card-header bg-success bg-opacity-10">
                                        <i class="fas fa-check-circle me-2"></i>Récapitulatif
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Appareil:</strong> <span id="recap_appareil">-</span></p>
                                                <p><strong>Modèle:</strong> <span id="recap_modele">-</span></p>
                                                <p><strong>État:</strong> <span id="recap_etat">-</span></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Client:</strong> <span id="recap_client">-</span></p>
                                                <p><strong>Prix proposé:</strong> <span id="recap_prix">-</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" id="prevStep" disabled>
                    <i class="fas fa-arrow-left me-2"></i>Précédent
                </button>
                <button type="button" class="btn btn-primary" id="nextStep">
                    <i class="fas fa-arrow-right me-2"></i>Suivant
                </button>
                <button type="button" class="btn btn-success d-none" id="submitRachat">
                    <i class="fas fa-save me-2"></i>Enregistrer le rachat
                </button>
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
let signaturePad;
let stream;
let photoTaken = false;
let capturedPhotoData = null;

// Gestionnaire d'erreur JavaScript global pour éviter les erreurs de scripts externes
window.addEventListener('error', function(e) {
    // Ignorer les erreurs des scripts externes qui ne sont pas critiques
    const ignoredErrors = [
        'Unexpected token',
        'has already been declared',
        'document.write',
        'modal-commande.js',
        'modal-main-fix.js',
        'modal-stacking-fix.js',
        'modal-sms-fix.js',
        'barcode-debug-real.js',
        'scanner-enhancement.js',
        'bug-reporter-simple.js',
        'modal-recherche-moderne.js',
        'Maximum call stack size exceeded',
        'focustrap',
        'FocusTrap'
    ];
    
    const errorMessage = e.message || e.error?.message || '';
    const filename = e.filename || '';
    
    const shouldIgnore = ignoredErrors.some(pattern => 
        errorMessage.includes(pattern) || filename.includes(pattern)
    );
    
    if (shouldIgnore) {
        console.warn('⚠️ Erreur JavaScript ignorée (script externe):', errorMessage, filename);
        e.preventDefault();
        return true;
    }
    
    // Laisser passer les autres erreurs pour le debug
    console.error('❌ Erreur JavaScript:', errorMessage, filename);
});

// Protection spécifique contre les erreurs FocusTrap et call stack
window.addEventListener('unhandledrejection', function(e) {
    if (e.reason && e.reason.message) {
        const errorMsg = e.reason.message;
        if (errorMsg.includes('FocusTrap') || errorMsg.includes('Maximum call stack') || errorMsg.includes('focustrap')) {
            console.warn('⚠️ Erreur FocusTrap/Stack ignorée:', errorMsg);
            e.preventDefault();
            return true;
        }
    }
});

// Override Bootstrap Modal pour désactiver FocusTrap par défaut
if (window.bootstrap && window.bootstrap.Modal) {
    const originalModal = window.bootstrap.Modal;
    window.bootstrap.Modal = class extends originalModal {
        constructor(element, config = {}) {
            // Désactiver focus par défaut pour éviter les conflits
            const safeConfig = {
                ...config,
                focus: config.focus !== undefined ? config.focus : false
            };
            super(element, safeConfig);
        }
    };
    console.log('🔧 Bootstrap Modal patché pour désactiver FocusTrap par défaut');
}

// Fonction de nettoyage des backdrops orphelins
function cleanOrphanBackdrops() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    const visibleModals = document.querySelectorAll('.modal.show');
    
    // S'il y a plus de backdrops que de modals visibles, nettoyer
    if (backdrops.length > visibleModals.length) {
        console.log(`🧹 Nettoyage de ${backdrops.length - visibleModals.length} backdrop(s) orphelin(s)`);
        
        // Garder seulement le nombre de backdrops correspondant aux modals visibles
        const backdropArray = Array.from(backdrops);
        backdropArray.slice(visibleModals.length).forEach(backdrop => {
            backdrop.remove();
        });
    }
}

// Nettoyer les backdrops au démarrage
document.addEventListener('DOMContentLoaded', function() {
    cleanOrphanBackdrops();
    
    // Nettoyer régulièrement les backdrops orphelins
    setInterval(cleanOrphanBackdrops, 1000);
});

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeRachatPage();
    loadRachats();
    initializeModal();
    
    // Préparer le canvas pour la photo
    const canvas = document.getElementById('cameraCanvas');
    if (canvas) {
        canvas.width = 640;
        canvas.height = 480;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#f8f9fa';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }

    // Gestionnaire pour le bouton nouveau rachat
    const newRachatModal = document.getElementById('newRachatModal_v2');
    if (newRachatModal) {
        // Nettoyer les backdrops avant d'ouvrir le modal
        newRachatModal.addEventListener('show.bs.modal', function () {
            cleanOrphanBackdrops();
            resetRachatForm();
            // Initialiser le pad signature quand le modal s'ouvre
            setTimeout(initSignaturePad, 500);
        });
        
        newRachatModal.addEventListener('hidden.bs.modal', function () {
            stopCamera();
            // Nettoyer les backdrops après la fermeture
            setTimeout(cleanOrphanBackdrops, 100);
        });
    }
});

// Initialisation de la page
function initializeRachatPage() {
    // Gestionnaires d'événements pour la recherche
    document.getElementById('searchInput').addEventListener('input', debounce(handleSearch, 300));
    document.getElementById('filterStatus').addEventListener('change', handleSearch);
    
    // Gestionnaire pour le bouton nouveau rachat
    // Gestionnaire pour le bouton nouveau rachat
    const newRachatModal = document.getElementById('newRachatModal_v2');
    if (newRachatModal) {
        newRachatModal.addEventListener('show.bs.modal', function () {
            resetRachatForm();
        });
    }

    // Gestionnaires pour les étapes du formulaire
    document.getElementById('rachatForm').addEventListener('submit', submitRachatForm);
    document.getElementById('submitRachat').addEventListener('click', submitRachatForm);

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
        const rachatModal = bootstrap.Modal.getInstance(document.getElementById('newRachatModal_v2'));
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
    initSignaturePad();
}

// Fonction d'initialisation du pad de signature
function initSignaturePad() {
    const canvas = document.getElementById('signatureCanvas');
    if (!canvas) return;
    
    // Assurons-nous que le canvas a la bonne taille
    const container = canvas.parentElement;
    canvas.width = container.clientWidth - 20; // -20 pour le padding
    canvas.height = 200;
    
    signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)',
        penColor: 'black',
        minWidth: 1,
        maxWidth: 3
    });

    // Attacher des événements pour capturer la photo lorsque la signature commence
    signaturePad.addEventListener("beginStroke", () => {
        console.log("Début de signature détecté, capture de la photo");
        capturePhoto();
    });
    
    console.log("Signature pad initialized");
    
    // Ajouter des événements de débogage
    canvas.addEventListener('mousedown', (e) => {
        console.log('Canvas mousedown event triggered');
    });
    
    canvas.addEventListener('touchstart', (e) => {
        console.log('Canvas touchstart event triggered');
    });
}

// Fonction pour démarrer la caméra
async function startCamera() {
    // Ne démarrer la caméra qu'une seule fois
    if (stream || photoTaken) return;
    
    console.log("Starting camera...");
    
    try {
        const video = document.getElementById('cameraVideo');
        const cameraPreview = document.querySelector('.camera-preview');
        
        // Stopper toute caméra précédente qui pourrait être en cours d'utilisation
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        
        // Demander l'accès à la caméra frontale avec des contraintes plus flexibles
        stream = await navigator.mediaDevices.getUserMedia({
            video: { 
                facingMode: 'user',
                width: { ideal: 640 },
                height: { ideal: 480 }
            },
            audio: false
        });
        
        // Afficher le flux vidéo
        video.srcObject = stream;
        cameraPreview.classList.remove('d-none');
        
        // Forcer l'affichage et le z-index
        cameraPreview.style.display = 'block';
        cameraPreview.style.visibility = 'visible';
        cameraPreview.style.zIndex = '1070';
        video.style.zIndex = '1071';
        video.style.display = 'block';
        video.style.visibility = 'visible';
        
        console.log('🎥 Caméra affichée:', {
            previewDisplay: cameraPreview.style.display,
            previewVisible: cameraPreview.style.visibility,
            previewZIndex: cameraPreview.style.zIndex,
            videoDisplay: video.style.display,
            videoZIndex: video.style.zIndex
        });
        
        // Attendre que la vidéo soit prête avec un gestionnaire d'événements
        video.onloadedmetadata = () => {
            video.play()
                .then(() => {
                    console.log("Vidéo démarrée avec succès");
                    // Attendre un court instant pour s'assurer que la caméra est bien initialisée
                    setTimeout(() => {
                        // Ajouter une classe pour montrer que la caméra est active
                        video.classList.add('camera-active');
                    }, 500);
                })
                .catch(e => console.error("Erreur lors du démarrage de la vidéo:", e));
        };
        
        console.log("Camera initialized successfully");
    } catch (err) {
        console.error("Erreur d'accès à la caméra:", err);
        // Informer l'utilisateur du problème de caméra
        alert("Impossible d'accéder à la caméra. Veuillez vérifier les permissions de votre navigateur.");
    }
}

// Variables pour la caméra plein écran
let currentPhotoType = null;
let cameraStreamFullscreen = null;

// Fonction pour ouvrir le modal caméra
function openCameraModal(type) {
    currentPhotoType = type;
    const modal = new bootstrap.Modal(document.getElementById('cameraModal'));
    
    // Mettre à jour le titre
    let title = 'Prendre une photo';
    switch(type) {
        case 'identite': title = 'Photo de la pièce d\'identité'; break;
        case 'appareil': title = 'Photo de l\'appareil'; break;
        case 'client': title = 'Photo du client avec l\'appareil'; break;
    }
    document.getElementById('cameraModalTitle').textContent = title;
    
    modal.show();
    startCameraFullscreen();
}

// Fonction pour fermer le modal caméra
function closeCameraModal() {
    const modalEl = document.getElementById('cameraModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) {
        modal.hide();
    }
    stopCameraFullscreen();
}

// Démarrer la caméra plein écran
async function startCameraFullscreen() {
    try {
        const video = document.getElementById('cameraVideoFullscreen');
        
        if (cameraStreamFullscreen) {
            cameraStreamFullscreen.getTracks().forEach(track => track.stop());
        }
        
        cameraStreamFullscreen = await navigator.mediaDevices.getUserMedia({
            video: { 
                facingMode: 'environment', // Caméra arrière par défaut pour les photos
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            },
            audio: false
        });
        
        video.srcObject = cameraStreamFullscreen;
        video.play();
    } catch (err) {
        console.error("Erreur caméra:", err);
        alert("Impossible d'accéder à la caméra");
    }
}

// Arrêter la caméra plein écran
function stopCameraFullscreen() {
    if (cameraStreamFullscreen) {
        cameraStreamFullscreen.getTracks().forEach(track => track.stop());
        cameraStreamFullscreen = null;
    }
}

// Prendre une photo depuis le modal plein écran
function takePictureFullscreen() {
    const video = document.getElementById('cameraVideoFullscreen');
    const canvas = document.getElementById('cameraCanvasFullscreen');
    
    if (!video || !canvas) return;
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    
    const imageData = canvas.toDataURL('image/jpeg', 0.8);
    
    // Mettre à jour l'aperçu dans le formulaire principal
    if (currentPhotoType) {
        const previewImg = document.getElementById(`preview${currentPhotoType.charAt(0).toUpperCase() + currentPhotoType.slice(1)}Img`);
        const previewDiv = document.getElementById(`preview${currentPhotoType.charAt(0).toUpperCase() + currentPhotoType.slice(1)}`);
        const input = document.getElementById(`photo_${currentPhotoType}`);
        
        if (previewImg && previewDiv) {
            previewImg.src = imageData;
            previewDiv.classList.remove('d-none');
            
            // Créer un fichier à partir du base64 pour l'input file (optionnel mais utile)
            // Note: On ne peut pas définir directement files sur un input, mais on peut stocker le base64
            // Le formulaire devra gérer l'envoi du base64 si l'input file est vide
            previewImg.dataset.base64 = imageData;
        }
    }
    
    closeCameraModal();
}

// Fonction pour capturer la photo (Signature - Caméra frontale)
async function capturePhoto() {
    if (!stream) {
        console.log("No camera stream available");
        return;
    }
    
    try {
        const video = document.getElementById('cameraVideo');
        const canvas = document.getElementById('cameraCanvas');
        const context = canvas.getContext('2d');
        
        // S'assurer que la vidéo est en cours de lecture
        if (video.paused || video.ended) {
            await video.play();
            // Attendre un court instant pour que la vidéo démarre réellement
            await new Promise(resolve => setTimeout(resolve, 300));
        }
        
        // Définir les dimensions du canvas aux dimensions actuelles de la vidéo
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        
        console.log(`Capture dimensions: ${canvas.width}x${canvas.height}`);
        
        // Vérifier si les dimensions sont correctes
        if (canvas.width === 0 || canvas.height === 0) {
            console.error("Dimensions de vidéo invalides");
            canvas.width = 640;
            canvas.height = 480;
        }
        
        // Dessiner la vidéo sur le canvas
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Convertir le canvas en image
        capturedPhotoData = canvas.toDataURL('image/jpeg', 0.9);
        
        // Vérifier si l'image est vide ou noire
        if (capturedPhotoData.length < 1000) {
            console.error("L'image capturée est potentiellement vide ou noire");
        }
        
        // Afficher l'image capturée
        const capturedPhoto = document.getElementById('capturedPhoto');
        capturedPhoto.src = capturedPhotoData;
        capturedPhoto.classList.remove('d-none');
        document.getElementById('photoPlaceholder').classList.add('d-none');
        
        // Mettre à jour l'input caché
        document.getElementById('clientPhotoInput').value = capturedPhotoData;
        
        // Arrêter la caméra après la capture
        stopCamera();
        
        // Marquer que la photo a été prise
        photoTaken = true;
        
        console.log("Photo captured successfully");
    } catch (err) {
        console.error("Erreur lors de la capture de la photo:", err);
        // Ne pas bloquer l'utilisateur si la photo échoue
    }
}

// Fonction pour arrêter la caméra
function stopCamera() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
        document.getElementById('cameraVideo').srcObject = null;
        document.querySelector('.camera-preview').classList.add('d-none');
    }
}

function clearSignature() {
    if (signaturePad) {
        signaturePad.clear();
        document.getElementById('signatureInput').value = '';
    }
    
    // Réinitialiser la photo également
    const capturedPhoto = document.getElementById('capturedPhoto');
    const photoPlaceholder = document.getElementById('photoPlaceholder');
    const clientPhotoInput = document.getElementById('clientPhotoInput');
    
    if (capturedPhoto) capturedPhoto.classList.add('d-none');
    if (photoPlaceholder) photoPlaceholder.classList.remove('d-none');
    if (clientPhotoInput) clientPhotoInput.value = '';
    photoTaken = false;
    
    // Redémarrer la caméra si on est à l'étape 3
    if (currentStep === 3) {
        startCamera();
    }
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
    const isAdmin = <?php echo ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')) ? 'true' : 'false'; ?>;

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
        row.style.cursor = 'pointer'; // Indiquer que la ligne est cliquable
        
        // Gestionnaire de clic sur la ligne
        row.onclick = function(e) {
            // Ne pas déclencher si on clique sur un bouton, un lien ou directement sur la checkbox (car elle a son propre handler)
            if (e.target.closest('button') || e.target.closest('a') || e.target.classList.contains('form-check-input')) {
                return;
            }
            
            const checkbox = this.querySelector('.rachat-checkbox');
            checkbox.checked = !checkbox.checked;
            
            // Mettre à jour le style et le bouton d'export
            if (checkbox.checked) {
                this.classList.add('table-active');
            } else {
                this.classList.remove('table-active');
            }
            updateBulkExportButton();
        };

        row.innerHTML = `
            <td class="d-none d-md-table-cell">
                <div class="form-check">
                    <input class="form-check-input rachat-checkbox" type="checkbox" value="${rachat.id}" onchange="updateBulkExportButton()">
                </div>
            </td>
            <td class="d-none d-md-table-cell"><strong>#${rachat.id}</strong></td>
            <td>${rachat.client_nom} ${rachat.client_prenom}</td>
            <td>
                <div>${rachat.modele}</div>
                <small class="text-muted">${rachat.sin || 'N/A'}</small>
            </td>
            <td class="d-none d-md-table-cell">${formatDate(rachat.date_creation)}</td>
            <td class="d-none d-md-table-cell"><strong>${rachat.prix_rachat} €</strong></td>
            <td class="d-none d-md-table-cell"><span class="modern-status-badge status-${rachat.statut}">${getStatusLabel(rachat.statut)}</span></td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="showDetails(${rachat.id})" title="Voir détails">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-success" onclick="exportPDF(${rachat.id})" title="Exporter PDF">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                    ${isAdmin ? `<button class="btn btn-outline-danger" onclick="deleteRachat(${rachat.id})" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>` : ''}
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

// Gestion des boutons de navigation
document.getElementById('prevStep').addEventListener('click', () => {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
        updateStepIndicator();
    }
});

document.getElementById('nextStep').addEventListener('click', () => {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
            updateStepIndicator();
        }
    }
});

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

    // Fonction pour afficher une étape spécifique
    function showStep(step) {
        // Masquer toutes les étapes
        document.querySelectorAll('.rachat-step').forEach(s => s.classList.add('d-none'));
        
        // Afficher l'étape demandée
        document.getElementById(`step${step}`).classList.remove('d-none');
        
        // Mettre à jour les boutons
        const prevBtn = document.getElementById('prevStep');
        const nextBtn = document.getElementById('nextStep');
        const submitBtn = document.getElementById('submitRachat');
        
        prevBtn.disabled = (step === 1);
        
        if (step === totalSteps) {
            nextBtn.classList.add('d-none');
            submitBtn.classList.remove('d-none');
        } else {
            nextBtn.classList.remove('d-none');
            submitBtn.classList.add('d-none');
        }
        
        // Cas spécifique pour l'étape 3 (Signature)
        if (step === 3) {
            console.log('📝 Affichage étape 3 - Signature');
            
            // Initialiser la caméra et le pad de signature avec un léger délai pour s'assurer que le DOM est prêt
            setTimeout(() => {
                initSignaturePad();
                startCamera();
            }, 100);
        } else {
            // Arrêter la caméra si on quitte l'étape 3
            stopCamera();
        }
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
    // Vérifier si une photo a été sélectionnée (input file) ou capturée (src de l'image d'aperçu)
    const photoIdentiteInput = document.getElementById('photo_identite');
    const previewIdentiteImg = document.getElementById('previewIdentiteImg');
    const hasPhotoIdentite = (photoIdentiteInput && photoIdentiteInput.files.length > 0) || 
                             (previewIdentiteImg && previewIdentiteImg.src && previewIdentiteImg.src.length > 100 && !previewIdentiteImg.src.endsWith('no-image.png'));
    
    if (!clientId) {
        showError('Veuillez sélectionner un client');
        return false;
    }
    
    if (!hasPhotoIdentite) {
        showError('Veuillez ajouter une photo de la pièce d\'identité');
        return false;
    }
    
    return true;
}

function validateStep2() {
    const typeAppareil = document.getElementById('type_appareil').value;
    const modele = document.getElementById('modele').value;
    
    // Vérifier si une photo a été sélectionnée (input file) ou capturée (src de l'image d'aperçu)
    const photoAppareilInput = document.getElementById('photo_appareil');
    const previewAppareilImg = document.getElementById('previewAppareilImg');
    const hasPhotoAppareil = (photoAppareilInput && photoAppareilInput.files.length > 0) || 
                             (previewAppareilImg && previewAppareilImg.src && previewAppareilImg.src.length > 100 && !previewAppareilImg.src.endsWith('no-image.png'));
    
    if (!typeAppareil) {
        showError('Veuillez sélectionner le type d\'appareil');
        return false;
    }
    
    if (!modele.trim()) {
        showError('Veuillez saisir le modèle de l\'appareil');
        return false;
    }
    
    if (!hasPhotoAppareil) {
        showError('Veuillez ajouter une photo de l\'appareil');
        return false;
    }
    
    return true;
}

function validateStep3() {
    // Tenter de récupérer la signature depuis l'instance globale signaturePad
    if (typeof signaturePad !== 'undefined' && signaturePad && !signaturePad.isEmpty()) {
        const signatureData = signaturePad.toDataURL();
        document.getElementById('signature').value = signatureData;
        console.log('✅ Signature récupérée depuis signaturePad');
    }

    const signature = document.getElementById('signature').value;
    
    if (!signature) {
        showError('La signature du client est obligatoire');
        return false;
    }
    
    return true;
}

function validateStep4() {
    const prix = document.getElementById('prix').value;
    
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
    
    // Ajouter les photos prises par caméra comme données base64 si elles existent
    const photoTypes = ['identite', 'appareil'];
    photoTypes.forEach(type => {
        const previewImg = document.getElementById(`preview${type.charAt(0).toUpperCase() + type.slice(1)}Img`);
        if (previewImg && previewImg.src && previewImg.src.startsWith('data:image')) {
            formData.set(`photo_${type}_data`, previewImg.src);
            console.log(`📷 Photo ${type} ajoutée comme base64`);
        }
    });

    // Cas spécifique pour la photo client (signature)
    const clientPhotoInput = document.getElementById('clientPhotoInput');
    if (clientPhotoInput && clientPhotoInput.value && clientPhotoInput.value.startsWith('data:image')) {
        formData.set('client_photo_data', clientPhotoInput.value);
        console.log('📷 Photo client ajoutée comme base64');
    } else {
        // Fallback: essayer de récupérer depuis l'image d'aperçu
        const capturedPhoto = document.getElementById('capturedPhoto');
        if (capturedPhoto && capturedPhoto.src && capturedPhoto.src.startsWith('data:image')) {
            formData.set('client_photo_data', capturedPhoto.src);
            console.log('📷 Photo client ajoutée depuis l\'aperçu');
        }
    }
    
    try {
        const submitBtn = document.getElementById('submitRachatBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...';
        }
        
        const response = await fetch('/ajax/save_rachat.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        console.log('🔍 Réponse du serveur:', data);
        
        if (data.success) {
            showSuccess('Rachat enregistré avec succès');
            const modal = bootstrap.Modal.getInstance(document.getElementById('newRachatModal_v2'));
            if (modal) modal.hide();
            resetRachatForm();
            loadRachats(); // Recharger la liste
        } else {
            console.error('❌ Erreur serveur:', data);
            let errorMsg = data.message || data.error || 'Erreur lors de l\'enregistrement';
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Erreur',
                    text: errorMsg,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } else {
                showError(errorMsg);
            }
        }
    } catch (error) {
        showError('Erreur de connexion');
        console.error('Error:', error);
    } finally {
        const submitBtn = document.getElementById('submitRachatBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check me-1"></i> Finaliser le rachat';
        }
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
                <button class="btn btn-sm modern-btn-primary client-select-btn" 
                        data-client-id="${client.id}" 
                        data-client-nom="${client.nom || ''}" 
                        data-client-prenom="${client.prenom || ''}">
                    <i class="fas fa-check me-1"></i> Sélectionner
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    // Ajouter les event listeners pour les boutons de sélection
    document.querySelectorAll('.client-select-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const clientId = this.getAttribute('data-client-id');
            const clientNom = this.getAttribute('data-client-nom');
            const clientPrenom = this.getAttribute('data-client-prenom');
            
            console.log('🎯 Clic sur bouton sélection client:', { clientId, clientNom, clientPrenom });
            selectClient(clientId, clientNom, clientPrenom);
        });
    });
}

// Afficher message aucun résultat
function showNoClientResults() {
    document.getElementById('resultats_clients').classList.add('d-none');
    document.getElementById('no_results').classList.remove('d-none');
}

// Sélectionner un client
function selectClient(id, nom, prenom) {
    console.log('🎯 Sélection client:', { id, nom, prenom });
    
    try {
        // Vérifier que les éléments existent
        const clientIdInput = document.getElementById('client_id');
        const nomClientElement = document.getElementById('nom_client_selectionne');
        const clientSelectionneDiv = document.getElementById('client_selectionne');
        
        if (!clientIdInput || !nomClientElement || !clientSelectionneDiv) {
            console.error('❌ Éléments manquants dans le DOM');
            showError('Erreur technique: éléments manquants');
            return;
        }
        
        // Mettre à jour les valeurs
        clientIdInput.value = id;
        nomClientElement.textContent = `${nom} ${prenom}`;
        
        // Afficher la sélection
        clientSelectionneDiv.classList.remove('d-none');
        document.getElementById('resultats_clients').classList.add('d-none');
        document.getElementById('no_results').classList.add('d-none');
        
        console.log('✅ Client sélectionné avec succès:', clientIdInput.value);
        
        // Afficher un message de succès temporaire
        showSuccess(`Client ${nom} ${prenom} sélectionné`);
        
    } catch (error) {
        console.error('❌ Erreur lors de la sélection du client:', error);
        showError('Erreur lors de la sélection du client');
    }
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



// Gestion de la caméra
function openCameraModal(type) {
    window.currentCameraType = type;
    document.getElementById('cameraModalTitle').textContent = `Prendre une photo - ${getPhotoTypeLabel(type)}`;
    
    // S'assurer que le modal caméra s'affiche au-dessus
    const cameraModal = document.getElementById('cameraModal');
    cameraModal.style.zIndex = '1070';
    
    // Désactiver FocusTrap pour éviter les conflits
    const modalInstance = new bootstrap.Modal(cameraModal, {
        backdrop: 'static',
        keyboard: false,
        focus: false  // Désactiver le focus automatique
    });
    
    // Gérer le backdrop après ouverture
    cameraModal.addEventListener('shown.bs.modal', function() {
        const backdrop = document.querySelector('.modal-backdrop:last-of-type');
        if (backdrop) {
            backdrop.style.zIndex = '1065';
        }
        
        // Désactiver tous les FocusTrap actifs pour éviter les conflits
        if (window.bootstrap && window.bootstrap.Modal) {
            const allModals = document.querySelectorAll('.modal');
            allModals.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance && modalInstance._focustrap) {
                    try {
                        modalInstance._focustrap.deactivate();
                        console.log('🔧 FocusTrap désactivé pour:', modal.id);
                    } catch (e) {
                        console.warn('⚠️ Erreur désactivation FocusTrap:', e);
                    }
                }
            });
        }
        
        // Initialiser la caméra après ouverture complète
        setTimeout(initCamera, 300);
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
    
    // Obtenir l'image en base64
    const imageDataUrl = canvas.toDataURL('image/jpeg', 0.8);
    
    // Afficher l'aperçu
    const type = window.currentCameraType;
    const previewDiv = document.getElementById(`preview${type.charAt(0).toUpperCase() + type.slice(1)}`);
    const previewImg = document.getElementById(`preview${type.charAt(0).toUpperCase() + type.slice(1)}Img`);
    
    if (previewImg && previewDiv) {
        previewImg.src = imageDataUrl;
        previewDiv.classList.remove('d-none');
        console.log(`📷 Photo ${type} capturée et aperçu affiché`);
    }
    
    // Créer aussi un fichier pour le FormData traditionnel
    canvas.toBlob(blob => {
        const file = new File([blob], `photo_${type}.jpg`, { type: 'image/jpeg' });
        
        // Créer un DataTransfer pour simuler la sélection de fichier
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        
        const input = document.getElementById(`photo_${type}`);
        if (input) {
            input.files = dataTransfer.files;
            // Déclencher l'événement change
            input.dispatchEvent(new Event('change'));
        }
        
        closeCameraModal();
    }, 'image/jpeg', 0.8);
}

function closeCameraModal() {
    // Arrêter le flux de la caméra
    if (window.currentStream) {
        window.currentStream.getTracks().forEach(track => track.stop());
        window.currentStream = null;
        console.log('📷 Flux caméra arrêté');
    }
    
    // Réactiver les FocusTrap des autres modals si nécessaire
    const cameraModal = document.getElementById('cameraModal');
    const modalInstance = bootstrap.Modal.getInstance(cameraModal);
    
    if (modalInstance) {
        // Fermer le modal caméra
        modalInstance.hide();
        
        // Réactiver le FocusTrap du modal parent après fermeture
        cameraModal.addEventListener('hidden.bs.modal', function() {
            const parentModal = document.querySelector('.modal.show:not(#cameraModal)');
            if (parentModal) {
                const parentModalInstance = bootstrap.Modal.getInstance(parentModal);
                if (parentModalInstance && parentModalInstance._focustrap) {
                    try {
                        parentModalInstance._focustrap.activate();
                        console.log('🔧 FocusTrap réactivé pour:', parentModal.id);
                    } catch (e) {
                        console.warn('⚠️ Erreur réactivation FocusTrap:', e);
                    }
                }
            }
        }, { once: true });
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
    console.error('🚨 Erreur:', message);
    
    // Afficher dans l'élément d'erreur du formulaire s'il existe
    const errorDiv = document.getElementById('rachatFormError');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.classList.remove('d-none');
        
        // Masquer après 5 secondes
        setTimeout(() => {
            errorDiv.classList.add('d-none');
        }, 5000);
    } else {
        // Fallback vers alert si l'élément n'existe pas
        alert('Erreur: ' + message);
    }
}

function showSuccess(message) {
    console.log('✅ Succès:', message);
    
    // Afficher dans l'élément de succès du formulaire s'il existe
    const successDiv = document.getElementById('rachatFormSuccess');
    if (successDiv) {
        successDiv.textContent = message;
        successDiv.classList.remove('d-none');
        
        // Masquer après 3 secondes
        setTimeout(() => {
            successDiv.classList.add('d-none');
        }, 3000);
    } else {
        // Fallback vers alert si l'élément n'existe pas
        alert('Succès: ' + message);
    }
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

// ===========================================
// DÉTECTION ET APPLICATION DU MODE NUIT
// ===========================================

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

// Fonction pour détecter et appliquer le mode sombre

// ===========================================
// FONCTIONS EXPORT DE MASSE
// ===========================================

function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('.rachat-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = source.checked;
        const row = cb.closest('tr');
        if (source.checked) {
            row.classList.add('table-active');
        } else {
            row.classList.remove('table-active');
        }
    });
    updateBulkExportButton();
}

function updateBulkExportButton() {
    const checkboxes = document.querySelectorAll('.rachat-checkbox');
    let checkedCount = 0;
    
    checkboxes.forEach(cb => {
        const row = cb.closest('tr');
        if (cb.checked) {
            checkedCount++;
            row.classList.add('table-active');
        } else {
            row.classList.remove('table-active');
        }
    });

    const btn = document.getElementById('btnBulkExport');
    const badge = document.getElementById('selectedCount');
    const selectAll = document.getElementById('selectAllRachats');
    
    // Mettre à jour le bouton
    if (checkedCount > 0) {
        btn.disabled = false;
        badge.textContent = checkedCount;
        badge.classList.remove('d-none');
    } else {
        btn.disabled = true;
        badge.classList.add('d-none');
    }
    
    // Mettre à jour la case "Tout sélectionner"
    if (checkboxes.length > 0 && checkedCount === checkboxes.length) {
        selectAll.checked = true;
        selectAll.indeterminate = false;
    } else if (checkedCount > 0) {
        selectAll.checked = false;
        selectAll.indeterminate = true;
    } else {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    }
}

function exportBulkPDF() {
    const checkboxes = document.querySelectorAll('.rachat-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    if (ids.length === 0) return;
    
    const url = `../ajax/export_rachat_bulk_pdf.php?ids=${ids.join(',')}`;
    window.open(url, '_blank');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
