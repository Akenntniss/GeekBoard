<?php
/**
 * NAVBAR_NEW.PHP - NOUVEAU FICHIER POUR CONTOURNER LE CACHE
 * Trois formats:
 * 1. PC: Barre en haut avec logo, bouton Nouvelle et menus
 * 2. Mobile: Dock en bas de page (pleine largeur)
 * 3. PWA: Dock en bas de page (adaptatif selon taille d'écran)
 */

// Détecter le mode PWA
$isPWA = false;
if (isset($_SESSION['pwa_mode']) && $_SESSION['pwa_mode'] === true) {
    $isPWA = true;
} elseif (isset($_COOKIE['pwa_mode']) && $_COOKIE['pwa_mode'] === 'true') {
    $isPWA = true;
}

// Détecter si on est sur un appareil mobile ou iPad
$isMobile = false;
$isIPad = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/(android|iphone|mobile)/i', $_SERVER['HTTP_USER_AGENT']);
    $isIPad = preg_match('/(ipad)/i', $_SERVER['HTTP_USER_AGENT']) || 
              (preg_match('/(macintosh)/i', $_SERVER['HTTP_USER_AGENT']) && 
               strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false && 
               strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === false);
}

// Obtenir le nom de la base de données actuelle
$db_name = '';

// Récupérer les paramètres d'entreprise pour le logo
$company_logo = '';
try {
    $shop_pdo = getShopDBConnection();
    if ($shop_pdo) {
        $stmt = $shop_pdo->prepare("SELECT valeur FROM parametres WHERE cle = 'company_logo' LIMIT 1");
        $stmt->execute();
        $custom_logo = $stmt->fetchColumn();
        
        // Si un logo personnalisé existe et que le fichier est accessible, l'utiliser
        if (!empty($custom_logo) && file_exists('/var/www/mdgeek.top/' . $custom_logo)) {
            $company_logo = '/' . $custom_logo;
        }
    }
} catch (Exception $e) {
    // En cas d'erreur, garder le logo par défaut (sera défini plus bas)
}
$shop_pdo = null;

try {
    if (isset($_SESSION['shop_id'])) {
        $shop_pdo = getShopDBConnection();
        if ($shop_pdo !== null) {
            $query = $shop_pdo->query("SELECT DATABASE() as db_name");
            $result = $query->fetch(PDO::FETCH_ASSOC);
            if ($result && isset($result['db_name'])) {
                $db_name = $result['db_name'];
            }
        }
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération du nom de la base de données: " . $e->getMessage());
}

// Ajouter une classe CSS au body pour les iPad
if ($isIPad) {
    echo '<script>document.body.classList.add("ipad-device");</script>';
}

// Script amélioré pour la détection de tablette et l'application des styles appropriés
// Inclure les styles CSS modernes pour la navigation mobile
$navbar_assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
echo '<link rel="stylesheet" href="' . $navbar_assets_path . 'css/mobile-navbar-modern.css">';

echo '<script>
// Fonction pour détecter si c\'est un appareil tablette
function isTabletDevice() {
    return (window.innerWidth <= 1366 && window.innerWidth >= 600) || 
           /ipad|tablet|playbook|silk|android(?!.*mobile)/i.test(navigator.userAgent.toLowerCase());
}

// Fonction pour gérer l\'affichage selon la taille d\'écran
function handleNavbarDisplay() {
    // Pour toute taille d\'écran inférieure à 1366px, on considère comme tablette ou mobile
    if (window.innerWidth < 1366) {
        document.body.classList.add("tablet-device");
        
        // Forcer l\'affichage du dock mobile et cacher la barre de navigation desktop
        const desktopNavbar = document.getElementById("desktop-navbar");
        const mobileDock = document.getElementById("mobile-dock");
        
        if (desktopNavbar) desktopNavbar.style.display = "none";
        if (mobileDock) mobileDock.style.display = "block";
    } else {
        // Pour les grands écrans, même sur Safari
        document.body.classList.remove("tablet-device");
        
        const desktopNavbar = document.getElementById("desktop-navbar");
        const mobileDock = document.getElementById("mobile-dock");
        
        // Ne pas cacher la barre desktop sur les grands écrans, sauf si c\'est un iPad ou en mode PWA
        if (desktopNavbar && !document.body.classList.contains("ipad-device") && !document.body.classList.contains("pwa-mode")) {
            desktopNavbar.style.display = "block";
        }
        
        // Cacher le dock mobile sur desktop, sauf pour iPad ou PWA
        if (mobileDock && !document.body.classList.contains("ipad-device") && !document.body.classList.contains("pwa-mode")) {
            mobileDock.style.display = "none";
        }
    }
}

// Exécuter au chargement
document.addEventListener("DOMContentLoaded", function() {
    if (isTabletDevice()) {
        document.body.classList.add("tablet-device");
    }
    
    handleNavbarDisplay();
    
    // Vérifier à chaque redimensionnement
    window.addEventListener("resize", handleNavbarDisplay);
});
</script>';

// Récupérer la page courante
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Définir une fonction de secours pour count_active_tasks si elle n'existe pas
if (!function_exists('count_active_tasks')) {
    function count_active_tasks($user_id) {
        // Fonction temporaire pour éviter les erreurs
        return 0;
    }
}

// Récupérer le nombre de tâches en cours (si la fonction existe)
$tasks_count = 0;
if (isset($_SESSION['user_id'])) {
    $tasks_count = count_active_tasks($_SESSION['user_id']);
}
?>

<!-- NAVBAR DESKTOP (PC) -->
<?php 
// Vérifier si c'est Safari sur desktop
$isSafariDesktop = false;
if (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false && 
    strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === false) {
    $isSafariDesktop = true;
}

// Afficher la navbar desktop SI:
// - c'est Safari, OU
// - ce n'est pas un mobile ET ce n'est pas un iPad
?>
<nav id="desktop-navbar" class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-2" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: var(--navbar-height) !important; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; z-index: 1030 !important;">
    <div class="container-fluid px-3">
        <!-- Logo à gauche -->
        <a class="navbar-brand me-0 me-lg-4 d-flex align-items-center" href="index.php">
            <?php $navbar_assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/'; ?>
            <img src="<?php echo $navbar_assets_path; ?>images/logo/logoservo.png" alt="GeekBoard" height="40">
        </a>
        
        <!-- Message de bienvenue avec le nom de l'utilisateur -->
        <?php if (isset($_SESSION['full_name'])): ?>
        <div class="d-none d-md-flex align-items-center ms-3 me-2">
            <span class="fw-medium text-primary">
                Bonjour, <?php echo htmlspecialchars($_SESSION['full_name']); ?> 
                <?php if (isset($_SESSION['shop_name'])): ?>
                <span class="badge bg-info ms-1"><?php echo htmlspecialchars($_SESSION['shop_name']); ?> 
                    <?php if (!empty($db_name)): ?>
                    <small class="ms-1">(DB: <?php echo htmlspecialchars($db_name); ?>)</small>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
        
        <!-- Bouton Nouvelle avec dropdown -->
        <div class="dropdown d-none d-lg-block me-auto">
            <button class="btn btn-primary" type="button" id="btnNouvelle" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal">
                <i class="fas fa-plus-circle me-1"></i> Nouvelle
            </button>
        </div>
        
        <!-- Boutons de navigation à droite -->
        <div class="d-none d-lg-flex align-items-center ms-auto">
            <!-- Bouton hamburger pour menu principal futuriste -->
            <button class="btn btn-outline-secondary ms-2 main-menu-btn" type="button" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Version mobile du bouton hamburger -->
        <button class="navbar-toggler d-lg-none ms-auto main-menu-btn" type="button" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>

<!-- NAVBAR MOBILE ET PWA MODERNE (Dock en bas) -->
<div id="mobile-dock" class="<?php echo ($isMobile || $isIPad) ? 'd-block' : 'd-lg-none'; ?>" <?php if (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false && !strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false && !$isIPad && !$isMobile): ?>style="display: none !important; visibility: hidden !important;"<?php endif; ?>>
    <!-- Barre de navigation moderne -->
    <div class="mobile-dock-container">
        <!-- Accueil -->
        <a href="index.php?page=accueil" class="dock-item <?php echo $currentPage == 'accueil' ? 'active' : ''; ?>" aria-label="Accueil">
            <div class="dock-icon-wrapper">
                <i class="fas fa-home"></i>
            </div>
            <span>Accueil</span>
        </a>
        
        <!-- Réparations -->
        <a href="index.php?page=reparations" class="dock-item <?php echo $currentPage == 'reparations' ? 'active' : ''; ?>" aria-label="Réparations">
            <div class="dock-icon-wrapper">
                <i class="fas fa-tools"></i>
            </div>
            <span>Réparations</span>
        </a>
        
        <!-- Bouton + central -->
        <a href="#" class="dock-item plus-button" aria-label="Créer quelque chose de nouveau" role="button">
            <div class="dock-icon-wrapper">
                <i class="fas fa-plus"></i>
            </div>
        </a>
        
        <!-- Tâches -->
        <a href="index.php?page=taches" class="dock-item <?php echo $currentPage == 'taches' ? 'active' : ''; ?>" aria-label="Tâches">
            <div class="dock-icon-wrapper">
                <i class="fas fa-tasks"></i>
                <?php if ($tasks_count > 0): ?>
                    <span class="badge rounded-pill bg-danger">
                        <?php echo $tasks_count; ?>
                    </span>
                <?php endif; ?>
            </div>
            <span>Tâches</span>
        </a>
        
        <!-- Menu -->
        <a href="#" class="dock-item" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal" aria-label="Menu principal">
            <div class="dock-icon-wrapper">
                <i class="fas fa-bars"></i>
            </div>
            <span>Menu</span>
        </a>
    </div>
</div>

<style>
/* Style pour gérer l'affichage des logos en fonction du mode */
body:not(.dark-mode) .dark-mode-logo {
    display: none !important;
}

body:not(.dark-mode) .light-mode-logo {
    display: inline-block !important;
}

body.dark-mode .dark-mode-logo {
    display: inline-block !important;
}

body.dark-mode .light-mode-logo {
    display: none !important;
}

/* Styles pour le texte de la marque */
.brand-text-primary {
    color: var(--primary) !important;
}

.brand-text-secondary {
    color: var(--info) !important;
}

body:not(.dark-mode) .brand-text-primary {
    color: #2563eb !important;
}

body:not(.dark-mode) .brand-text-secondary {
    color: #3b82f6 !important;
}

body.dark-mode .brand-text-primary {
    color: #60a5fa !important;
}

body.dark-mode .brand-text-secondary {
    color: #93c5fd !important;
}

/* CORRECTION : Suppression des marges à gauche causées par la sidebar supprimée */
@media (min-width: 992px) {
    /* Réinitialiser les marges pour la navbar */
    #desktop-navbar {
        margin-left: 0 !important;
        width: 100% !important;
        left: 0 !important;
        right: 0 !important;
    }
    
    /* Réinitialiser les marges pour le conteneur principal */
    .container-fluid {
        margin-left: 0 !important;
        width: 100% !important;
    }
    
    /* Réinitialiser les marges pour le contenu principal */
    main {
        margin-left: 0 !important;
        width: 100% !important;
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
    
    /* Réinitialiser les marges pour tous les éléments avec sidebar */
    body:not(.touch-device) main {
        margin-left: 0 !important;
        width: 100% !important;
    }
}

/* Style pour la bannière de bienvenue mobile */
.mobile-welcome-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background-color: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    z-index: 1020;
    padding: 8px 0;
    font-size: 0.9rem;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.dark-mode .mobile-welcome-banner {
    background-color: rgba(25, 25, 25, 0.95);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

/* Ajustement du contenu principal pour éviter qu'il soit caché par la bannière */
body:has(.mobile-welcome-banner) #main-content {
    padding-top: 60px !important;
}

/* Styles pour le nouveau menu Offcanvas */
.offcanvas-launchpad {
    padding: 1rem;
}

.offcanvas-launchpad .launchpad-section {
    margin-bottom: 1.5rem;
}

.offcanvas-launchpad .launchpad-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.75rem;
    padding-left: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05rem;
}

.offcanvas-launchpad .launchpad-section-content {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

@media (min-width: 400px) {
    .offcanvas-launchpad .launchpad-section-content {
        grid-template-columns: repeat(3, 1fr);
    }
}

.offcanvas-launchpad .launchpad-item {
    animation-delay: 0.05s;
}

#mainMenuOffcanvas {
    max-width: 360px;
}

#mainMenuOffcanvas .launchpad-icon {
    width: 50px;
    height: 50px;
    font-size: 1.2rem;
}

#mainMenuOffcanvas .launchpad-item span {
    font-size: 0.8rem;
}

/* Bouton du menu hamburger amélioré */
.main-menu-btn {
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

/* Design moderne et responsive pour le dashboard latéral */
.sidebar-dashboard {
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: linear-gradient(145deg, rgba(255,255,255,0.95), rgba(248,250,252,0.98));
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-right: 1px solid rgba(255,255,255,0.2);
    position: relative;
    overflow: hidden;
}

/* Mode sombre */
body.dark-mode .sidebar-dashboard {
    background: linear-gradient(145deg, rgba(17,24,39,0.95), rgba(31,41,55,0.98));
    border-right: 1px solid rgba(55,65,81,0.3);
}

.sidebar-dashboard::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 200px;
    background: linear-gradient(135deg, rgba(59,130,246,0.1), rgba(147,51,234,0.1));
    z-index: -1;
}

body.dark-mode .sidebar-dashboard::before {
    background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(147,51,234,0.15));
}

.sidebar-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1.5rem 1.25rem 1rem 1.25rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    position: relative;
    z-index: 10;
}

body.dark-mode .sidebar-header {
    border-bottom: 1px solid rgba(255,255,255,0.08);
    background: rgba(0,0,0,0.1);
}

.sidebar-header img {
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.sidebar-header .brand-info {
    flex: 1;
}

.sidebar-header .brand-name {
    font-size: 1.1rem;
    font-weight: 700;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
    line-height: 1.2;
}

.sidebar-header .shop-info {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0;
    line-height: 1.3;
    opacity: 0.8;
}

body.dark-mode .sidebar-header .shop-info {
    color: #94a3b8;
}

.sidebar-groups {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 0.75rem 1.5rem 0.75rem;
    scrollbar-width: thin;
    scrollbar-color: rgba(0,0,0,0.1) transparent;
}

.sidebar-groups::-webkit-scrollbar {
    width: 4px;
}

.sidebar-groups::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-groups::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.1);
    border-radius: 2px;
}

body.dark-mode .sidebar-groups::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
}

.sidebar-group {
    margin-bottom: 1.5rem;
    animation: slideInLeft 0.4s ease-out;
    animation-fill-mode: both;
}

.sidebar-group:nth-child(1) { animation-delay: 0.1s; }
.sidebar-group:nth-child(2) { animation-delay: 0.15s; }
.sidebar-group:nth-child(3) { animation-delay: 0.2s; }
.sidebar-group:nth-child(4) { animation-delay: 0.25s; }
.sidebar-group:nth-child(5) { animation-delay: 0.3s; }
.sidebar-group:nth-child(6) { animation-delay: 0.35s; }

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.sidebar-group-title {
    font-size: 0.7rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 0.5rem 0.75rem 0.75rem 0.75rem;
    margin: 0;
    position: relative;
}

body.dark-mode .sidebar-group-title {
    color: #94a3b8;
}

.sidebar-group-title::after {
    content: '';
    position: absolute;
    bottom: 0.25rem;
    left: 0.75rem;
    right: 0.75rem;
    height: 1px;
    background: linear-gradient(90deg, rgba(59,130,246,0.3), transparent);
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 0 0.5rem;
}

.sidebar-link {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.75rem 0.875rem;
    border-radius: 12px;
    color: #374151;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    border: 1px solid transparent;
    background: rgba(255,255,255,0.3);
    backdrop-filter: blur(10px);
}

body.dark-mode .sidebar-link {
    color: #d1d5db;
    background: rgba(0,0,0,0.2);
}

.sidebar-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
    opacity: 0;
    transition: opacity 0.3s ease;
}

body.dark-mode .sidebar-link::before {
    background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
}

.sidebar-link:hover::before {
    opacity: 1;
}

.sidebar-link:hover {
    transform: translateX(4px) scale(1.02);
    border-color: rgba(59,130,246,0.2);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08), 0 3px 10px rgba(59,130,246,0.1);
}

body.dark-mode .sidebar-link:hover {
    border-color: rgba(59,130,246,0.3);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3), 0 3px 10px rgba(59,130,246,0.2);
}

.sidebar-link.active {
    background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(147,51,234,0.1));
    border-color: rgba(59,130,246,0.3);
    color: #1e40af;
    transform: translateX(4px);
    box-shadow: 0 8px 25px rgba(59,130,246,0.15);
}

body.dark-mode .sidebar-link.active {
    background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(147,51,234,0.15));
    border-color: rgba(59,130,246,0.4);
    color: #60a5fa;
    box-shadow: 0 8px 25px rgba(59,130,246,0.25);
}

.sidebar-link .icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    flex-shrink: 0;
}

.sidebar-link:hover .icon {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.sidebar-link .label {
    font-weight: 600;
    font-size: 0.875rem;
    flex: 1;
    transition: color 0.3s ease;
}

.sidebar-link .badge {
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.sidebar-sep {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(0,0,0,0.1), transparent);
    margin: 1rem 0.75rem;
    position: relative;
}

body.dark-mode .sidebar-sep {
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
}

.sidebar-sep::before {
    content: '';
    position: absolute;
    top: -1px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    background: #3b82f6;
    border-radius: 50%;
    box-shadow: 0 0 8px rgba(59,130,246,0.5);
}

/* Responsive Design */
@media (max-width: 768px) {
    .sidebar-dashboard {
        background: rgba(255,255,255,0.98);
        backdrop-filter: blur(30px);
    }
    
    body.dark-mode .sidebar-dashboard {
        background: rgba(17,24,39,0.98);
    }
    
    .sidebar-header {
        padding: 1rem;
    }
    
    .sidebar-groups {
        padding: 0.75rem 0.5rem 1rem 0.5rem;
    }
    
    .sidebar-link {
        padding: 0.625rem 0.75rem;
        gap: 0.75rem;
    }
    
    .sidebar-link .icon {
        width: 32px;
        height: 32px;
        font-size: 0.9rem;
    }
    
    .sidebar-link .label {
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .sidebar-header .brand-name {
        font-size: 1rem;
    }
    
    .sidebar-header .shop-info {
        font-size: 0.7rem;
    }
    
    .sidebar-group-title {
        font-size: 0.65rem;
        padding: 0.4rem 0.5rem 0.6rem 0.5rem;
    }
    
    .sidebar-link {
        padding: 0.5rem 0.625rem;
    }
    
    .sidebar-link .icon {
        width: 28px;
        height: 28px;
        font-size: 0.85rem;
    }
}

.main-menu-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Styles pour l'icône scanner */
.launchpad-icon-scanner {
    background-color: rgba(0, 150, 136, 0.15);
    color: #009688;
}

/* Styles pour l'icône ajouter */
.launchpad-icon-add {
    background-color: rgba(76, 175, 80, 0.15);
    color: #4CAF50;
}

/* Styles pour l'icône partenaires */
.launchpad-icon-partner {
    background-color: rgba(33, 150, 243, 0.15);
    color: #2196F3;
}

/* Styles pour l'icône bug */
.launchpad-icon-bug {
    background-color: rgba(244, 67, 54, 0.15);
    color: #F44336;
}

/* Styles pour l'icône de rachat */
.launchpad-icon-trade {
    background-color: rgba(76, 175, 80, 0.15);
    color: #4CAF50;
}

/* Styles pour l'icône retours */
.launchpad-icon-return {
    background-color: rgba(33, 150, 243, 0.15);
    color: #2196F3;
}

/* Styles pour l'icône logs */
.launchpad-icon-logs {
    background-color: rgba(33, 150, 243, 0.15);
    color: #2196F3;
}

/* Styles pour l'icône missions */
.launchpad-icon-mission {
    background-color: rgba(156, 39, 176, 0.15);
    color: #9C27B0;
}

/* Styles pour l'icône admin missions */
.launchpad-icon-admin-mission {
    background-color: rgba(255, 87, 34, 0.15);
    color: #FF5722;
}

/* Styles pour l'icône présence (absences & retards) */
.launchpad-icon-presence {
    background-color: rgba(156, 39, 176, 0.15);
    color: #9C27B0;
}

/* DASHBOARD MODERNE STYLES */

/* Agrandir la largeur du menu offcanvas */
#mainMenuOffcanvas {
    width: 450px !important;
    max-width: 90vw !important;
}

/* RESPONSIVE DESIGN POUR TABLETTES */
@media (min-width: 769px) and (max-width: 1024px) {
    #mainMenuOffcanvas {
        width: 380px !important;
        max-width: 85vw !important;
    }
    
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0.6rem !important;
    }
    
    .dashboard-card {
        padding: 0.65rem !important;
        min-height: 90px !important;
    }
    
    .dashboard-card-icon {
        width: 38px !important;
        height: 38px !important;
        margin-bottom: 0.25rem !important;
    }
    
    .dashboard-card-icon i {
        font-size: 1.25rem !important;
    }
    
    .dashboard-card-title {
        font-size: 0.78rem !important;
    }
    
    .dashboard-card-subtitle {
        font-size: 0.68rem !important;
    }
}

/* RESPONSIVE DESIGN POUR MOBILE ET PETITES TABLETTES - FORCER 4 COLONNES */
@media (max-width: 768px) {
    #mainMenuOffcanvas {
        width: 400px !important;
        max-width: 98vw !important;
    }
    
    .dashboard-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 0.3rem !important;
        width: 100% !important;
    }
    
    .dashboard-card {
        padding: 0.3rem !important;
        min-height: 70px !important;
        flex-direction: column !important;
        display: flex !important;
        align-items: center !important;
        text-align: center !important;
        width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
    }
    
    .dashboard-card-icon {
        width: 24px !important;
        height: 24px !important;
        margin-bottom: 0.1rem !important;
        border-radius: 6px !important;
        flex-shrink: 0 !important;
    }
    
    .dashboard-card-icon i {
        font-size: 0.8rem !important;
    }
    
    .dashboard-card-title {
        font-size: 0.6rem !important;
        line-height: 1 !important;
        margin: 0 !important;
        text-overflow: ellipsis !important;
        overflow: hidden !important;
        white-space: nowrap !important;
        width: 100% !important;
    }
    
    .dashboard-card-subtitle {
        font-size: 0.5rem !important;
        line-height: 1 !important;
        margin: 0 !important;
        text-overflow: ellipsis !important;
        overflow: hidden !important;
        white-space: nowrap !important;
        width: 100% !important;
    }
    
    .dashboard-section-title {
        font-size: 0.8rem !important;
        margin-bottom: 0.4rem !important;
    }
    
    .dashboard-container {
        width: 100% !important;
        max-width: none !important;
    }
}

@media (max-width: 480px) {
    #mainMenuOffcanvas {
        width: 380px !important;
        max-width: 99vw !important;
    }
    
    .dashboard-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 0.25rem !important;
        width: 100% !important;
    }
    
    .dashboard-card {
        padding: 0.25rem !important;
        min-height: 65px !important;
        width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
    }
    
    .dashboard-card-icon {
        width: 22px !important;
        height: 22px !important;
        border-radius: 5px !important;
        flex-shrink: 0 !important;
    }
    
    .dashboard-card-icon i {
        font-size: 0.7rem !important;
    }
    
    .dashboard-card-title {
        font-size: 0.55rem !important;
        line-height: 0.9 !important;
        text-overflow: ellipsis !important;
        overflow: hidden !important;
        white-space: nowrap !important;
        width: 100% !important;
        margin: 0 !important;
    }
    
    .dashboard-card-subtitle {
        font-size: 0.45rem !important;
        line-height: 0.9 !important;
        text-overflow: ellipsis !important;
        overflow: hidden !important;
        white-space: nowrap !important;
        width: 100% !important;
        margin: 0 !important;
    }
    
    .dashboard-container {
        max-height: calc(100vh - 150px) !important;
        width: 100% !important;
    }
}

/* RESPONSIVE DESIGN POUR TRÈS PETITS ÉCRANS - GARDER 4 COLONNES */
@media (max-width: 360px) {
    #mainMenuOffcanvas {
        width: 350px !important;
        max-width: 100vw !important;
    }
    
    .dashboard-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 0.2rem !important;
        width: 100% !important;
    }
    
    .dashboard-card {
        padding: 0.2rem !important;
        min-height: 60px !important;
        width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
    }
    
    .dashboard-card-icon {
        width: 20px !important;
        height: 20px !important;
        border-radius: 4px !important;
        flex-shrink: 0 !important;
        margin-bottom: 0.1rem !important;
    }
    
    .dashboard-card-icon i {
        font-size: 0.65rem !important;
    }
    
    .dashboard-card-title {
        font-size: 0.5rem !important;
        line-height: 0.85 !important;
        text-overflow: ellipsis !important;
        overflow: hidden !important;
        white-space: nowrap !important;
        width: 100% !important;
        margin: 0 !important;
    }
    
    .dashboard-card-subtitle {
        font-size: 0.4rem !important;
        line-height: 0.85 !important;
        text-overflow: ellipsis !important;
        overflow: hidden !important;
        white-space: nowrap !important;
        width: 100% !important;
        margin: 0 !important;
    }
}

/* MOBILE PAYSAGE - Optimisation pour les mobiles en mode horizontal */
@media (max-height: 500px) and (max-width: 900px) {
    #mainMenuOffcanvas {
        width: 300px !important;
    }
    
    .dashboard-container {
        max-height: calc(100vh - 100px) !important;
        padding: 0.5rem 0 !important;
    }
    
    .dashboard-section {
        margin-bottom: 1rem !important;
    }
    
    .dashboard-card {
        min-height: 70px !important;
        padding: 0.4rem !important;
    }
    
    .dashboard-card-icon {
        width: 32px !important;
        height: 32px !important;
    }
    
    .dashboard-section-title {
        font-size: 0.75rem !important;
        margin-bottom: 0.4rem !important;
    }
}

.dashboard-container {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
    padding: 0;
}

.dashboard-section {
    margin-bottom: 1.5rem;
}

.dashboard-section-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding-left: 0.5rem;
    border-left: 3px solid #e5e7eb;
}

body.dark-mode .dashboard-section-title {
    color: #9ca3af;
    border-left-color: #374151;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.dashboard-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.5rem;
    padding: 0.75rem;
    border-radius: 20px;
    text-decoration: none;
    color: inherit;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px) saturate(1.8);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.08),
        0 4px 16px rgba(0, 0, 0, 0.04),
        inset 0 1px 0 rgba(255, 255, 255, 0.4);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    min-height: 100px;
    /* Améliorations tactiles pour mobile */
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
    user-select: none;
    -webkit-touch-callout: none;
    /* Effets modernes */
    transform: translateY(0);
    will-change: transform, box-shadow;
}

/* Effets hover modernes */
.dashboard-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.12),
        0 8px 24px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.5);
    border-color: rgba(255, 255, 255, 0.4);
}

.dashboard-card:active {
    transform: translateY(-2px) scale(1.01);
    transition-duration: 0.1s;
}

.dashboard-card.active {
    background: rgba(59, 130, 246, 0.15);
    border-color: rgba(59, 130, 246, 0.3);
    box-shadow: 
        0 8px 32px rgba(59, 130, 246, 0.2),
        0 4px 16px rgba(59, 130, 246, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.4);
}

/* Effet de brillance animé */
.dashboard-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
    z-index: 1;
}

.dashboard-card:hover::before {
    left: 100%;
}

body.dark-mode .dashboard-card {
    background: rgba(31, 41, 55, 0.95);
    border-color: rgba(75, 85, 99, 0.4);
    color: #f3f4f6;
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.2),
        0 4px 16px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

body.dark-mode .dashboard-card:hover {
    background: rgba(31, 41, 55, 0.98);
    border-color: rgba(156, 163, 175, 0.4);
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.3),
        0 8px 24px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
}


.dashboard-card-icon {
    flex-shrink: 0;
    width: 45px;
    height: 45px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    margin-bottom: 0.25rem;
    /* Effets modernes sur les icônes */
    box-shadow: 
        0 4px 12px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 2;
}

.dashboard-card-icon i {
    font-size: 1.4rem;
    color: white;
    z-index: 2;
    position: relative;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    transition: all 0.3s ease;
}

.dashboard-card:hover .dashboard-card-icon {
    transform: scale(1.1) rotate(-2deg);
    box-shadow: 
        0 6px 20px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
}

.dashboard-card:hover .dashboard-card-icon i {
    transform: scale(1.05);
    filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.3));
}

.dashboard-card-content {
    flex: 1;
    min-width: 0;
}

.dashboard-card-title {
    font-size: 0.8rem;
    font-weight: 600;
    margin: 0;
    color: #1f2937;
    line-height: 1.2;
}

body.dark-mode .dashboard-card-title {
    color: #f9fafb;
}

.dashboard-card-subtitle {
    font-size: 0.7rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.2;
    font-weight: 400;
}

body.dark-mode .dashboard-card-subtitle {
    color: #9ca3af;
}

.dashboard-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ef4444;
    color: white;
    font-size: 0.65rem;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.dashboard-card-logout {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
    border-color: rgba(239, 68, 68, 0.2);
}

.dashboard-card-logout:hover {
    border-color: rgba(239, 68, 68, 0.4);
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.15) 100%);
}

/* Responsive pour petits écrans */
@media (max-width: 480px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-card {
        padding: 0.85rem;
    }
    
    .dashboard-card-icon {
        width: 44px;
        height: 44px;
    }
    
    .dashboard-card-icon i {
        font-size: 1.2rem;
    }
    
    .dashboard-card-title {
        font-size: 0.85rem;
    }
    
    .dashboard-card-subtitle {
        font-size: 0.7rem;
    }
}

/* Animation d'entrée */
@keyframes dashboardCardIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dashboard-card {
    animation: dashboardCardIn 0.4s ease forwards;
}

.dashboard-card:nth-child(1) { animation-delay: 0.05s; }
.dashboard-card:nth-child(2) { animation-delay: 0.1s; }
.dashboard-card:nth-child(3) { animation-delay: 0.15s; }
.dashboard-card:nth-child(4) { animation-delay: 0.2s; }
</style>

<!-- OFFCANVAS MENU PRINCIPAL (pour desktop) -->
<!-- Offcanvas legacy supprimé (remplacé par le modal futuriste) -->
    <div class="offcanvas-header p-0">
        <div class="sidebar-header w-100">
            <img src="<?php echo $navbar_assets_path; ?>images/logo/logoservo.png" alt="GeekBoard" height="40">
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
    </div>
    <div class="offcanvas-body p-3">
        <div class="dashboard-container">
            <!-- Section Actions Principales -->
            <div class="dashboard-section mb-4">
                <h6 class="dashboard-section-title">🏠 Actions Principales</h6>
                <div class="dashboard-grid">
                    <a class="dashboard-card <?php echo $currentPage == 'reparations' ? 'active' : ''; ?>" href="index.php?page=reparations">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Réparations</h6>
                            <p class="dashboard-card-subtitle">Gérer les réparations</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'taches' ? 'active' : ''; ?>" href="index.php?page=taches">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-tasks"></i>
                            <?php if ($tasks_count > 0): ?>
                                <span class="dashboard-badge"><?php echo $tasks_count; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Tâches</h6>
                            <p class="dashboard-card-subtitle">Gérer les tâches</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'commandes_pieces' ? 'active' : ''; ?>" href="index.php?page=commandes_pieces">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Commandes</h6>
                            <p class="dashboard-card-subtitle">Pièces & fournitures</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'inventaire' ? 'active' : ''; ?>" href="index.php?page=inventaire">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Inventaire</h6>
                            <p class="dashboard-card-subtitle">Stock disponible</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'rachat_appareils' ? 'active' : ''; ?>" href="index.php?page=rachat_appareils">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <i class="fas fa-exchange-alt"></i>
                </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Rachats</h6>
                            <p class="dashboard-card-subtitle">Appareils d'occasion</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'base_connaissances' ? 'active' : ''; ?>" href="index.php?page=base_connaissances">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Base de connaissance</h6>
                            <p class="dashboard-card-subtitle">Base documentaire</p>
                        </div>
                    </a>
                </div>
                </div>


            <!-- Section Outils & Qualité -->
            <div class="dashboard-section mb-4">
                <h6 class="dashboard-section-title">🔧 Outils & Qualité</h6>
                <div class="dashboard-grid">
                    <a class="dashboard-card <?php echo $currentPage == 'clients' ? 'active' : ''; ?>" href="index.php?page=clients">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Clients</h6>
                            <p class="dashboard-card-subtitle">Base clients</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'comptes_partenaires' ? 'active' : ''; ?>" href="index.php?page=comptes_partenaires">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Partenaires</h6>
                            <p class="dashboard-card-subtitle">Comptes externes</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'sms_historique' ? 'active' : ''; ?>" href="index.php?page=sms_historique">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Historique SMS</h6>
                            <p class="dashboard-card-subtitle">Messages envoyés</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo in_array($currentPage, ['presence_gestion', 'presence_ajouter', 'presence_calendrier', 'presence_export', 'presence_modifier']) ? 'active' : ''; ?>" href="index.php?page=presence_gestion">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Présences</h6>
                            <p class="dashboard-card-subtitle">Absences & Retards</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'mes_missions' ? 'active' : ''; ?>" href="index.php?page=mes_missions">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                            <i class="fas fa-clipboard-check"></i>
                </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Mes Missions</h6>
                            <p class="dashboard-card-subtitle">Tâches assignées</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'bug-reports' ? 'active' : ''; ?>" href="index.php?page=bug-reports">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                            <i class="fas fa-bug"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Bug Report</h6>
                            <p class="dashboard-card-subtitle">Signaler problème</p>
                        </div>
                    </a>
                </div>
                </div>

            <!-- Section Administration - Visible uniquement aux admins -->
            <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
            <div class="dashboard-section mb-4">
                <h6 class="dashboard-section-title">⚙️ Administration</h6>
                <div class="dashboard-grid">
                    <a class="dashboard-card <?php echo $currentPage == 'employes' ? 'active' : ''; ?>" href="index.php?page=employes">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Employés</h6>
                            <p class="dashboard-card-subtitle">Gestion équipe</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo (strpos($_SERVER['REQUEST_URI'], 'admin_timetracking') !== false) ? 'active' : ''; ?>" href="index.php?page=admin_timetracking">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Pointage</h6>
                            <p class="dashboard-card-subtitle">Temps de travail</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'reparation_logs' ? 'active' : ''; ?>" href="index.php?page=reparation_logs">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Log Réparation</h6>
                            <p class="dashboard-card-subtitle">Logs réparations</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'admin_missions' ? 'active' : ''; ?>" href="index.php?page=admin_missions">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Admin Mission</h6>
                            <p class="dashboard-card-subtitle">Gestion missions</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'campagne_sms' ? 'active' : ''; ?>" href="index.php?page=campagne_sms">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <i class="fas fa-sms"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">SMS Campagne</h6>
                            <p class="dashboard-card-subtitle">Campagnes SMS</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'template_sms' ? 'active' : ''; ?>" href="index.php?page=template_sms">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-comment-dots"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Template SMS</h6>
                            <p class="dashboard-card-subtitle">Modèles SMS</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'parametre' ? 'active' : ''; ?>" href="index.php?page=parametre">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Paramètres</h6>
                            <p class="dashboard-card-subtitle">Configuration</p>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Section Déconnexion -->
            <div class="dashboard-section">
                <div class="dashboard-grid">
                    <a class="dashboard-card dashboard-card-logout" href="index.php?action=logout">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Déconnexion</h6>
                            <p class="dashboard-card-subtitle">Se déconnecter</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include du nouveau menu futuriste/corporate -->
<?php include 'futuristic_menu.php'; ?>

<!-- Styles et scripts pour le menu futuriste -->
<link rel="stylesheet" href="<?php echo $navbar_assets_path; ?>css/futuristic-menu.css">
<script src="<?php echo $navbar_assets_path; ?>js/futuristic-menu.js"></script>

<!-- Script JavaScript pour la barre de navigation mobile moderne -->
<script src="<?php echo $navbar_assets_path; ?>js/mobile-navbar-modern.js"></script>

