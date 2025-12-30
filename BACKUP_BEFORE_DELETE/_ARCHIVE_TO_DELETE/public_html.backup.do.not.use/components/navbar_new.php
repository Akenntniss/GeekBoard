<?php
/**
 * Nouvelle barre de navigation pour GeekBoard
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

// Ancien système mobile-dock désactivé - remplacé par mobile_dock_bar
// $navbar_assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
// echo '<link rel="stylesheet" href="' . $navbar_assets_path . 'css/mobile-dock-modern-buttons.css">';
// echo '<script src="' . $navbar_assets_path . 'js/mobile-dock-auto-hide.js" defer></script>';

// Script amélioré pour la détection de tablette et l'application des styles appropriés
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
        
        <!-- ESPACE FLEXIBLE POUR POUSSER LES BOUTONS À DROITE -->
        <div class="flex-grow-1"></div>
        
        <!-- Boutons de navigation à l'extrême droite -->
        <div class="d-none d-lg-flex align-items-center navbar-actions-container">
            <!-- Nouveau bouton Action (ex-Nouvelle) -->
            <button class="geek-action-btn geek-btn-primary me-2" type="button" id="geekActionBtn" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal" title="Nouvelle action">
                <i class="fas fa-plus-circle me-1"></i> 
                <span class="geek-btn-text">Nouvelle</span>
            </button>
            
            <!-- Nouveau bouton Menu (ex-hamburger) -->
            <button class="geek-menu-btn geek-btn-secondary" type="button" id="geekMenuBtn" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal" aria-controls="futuristicMenuModal" title="Menu principal">
                <i class="fas fa-bars geek-menu-icon"></i>
            </button>
        </div>

    </div>
</nav>

<!-- NAVBAR MOBILE ET PWA (Dock en bas) -->
<div id="mobile-dock" class="<?php echo ($isMobile || $isIPad) ? 'd-block' : 'd-lg-none'; ?>" <?php if (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false && !strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false && !$isIPad && !$isMobile): ?>style="display: none !important; visibility: hidden !important;"<?php endif; ?>>
    <!-- Message de bienvenue pour mobile en haut du dock -->
    <?php if (isset($_SESSION['full_name'])): ?>
    <div class="mobile-welcome-banner">
        <div class="container-fluid py-1 text-center">
            <span class="fw-medium">
                Bonjour, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                <?php if (isset($_SESSION['shop_name'])): ?>
                <span class="badge bg-info ms-1">
                    <?php echo htmlspecialchars($_SESSION['shop_name']); ?>
                    <?php if (!empty($db_name)): ?>
                    <small class="ms-1">(DB: <?php echo htmlspecialchars($db_name); ?>)</small>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="mobile-dock-container">
        <a href="index.php" class="dock-item <?php echo $currentPage == 'accueil' ? 'active' : ''; ?>">
            <div class="dock-icon-wrapper">
                <i class="fas fa-home"></i>
            </div>
            <span>Accueil</span>
        </a>
        
        <a href="index.php?page=reparations" class="dock-item <?php echo $currentPage == 'reparations' ? 'active' : ''; ?>">
            <div class="dock-icon-wrapper">
                <i class="fas fa-tools"></i>
            </div>
            <span>Réparations</span>
        </a>
        
        <!-- Bouton Nouvelle au centre (stylisé différemment) -->
        <div class="dock-item-center" style="overflow: visible !important; position: relative !important;">
            <button class="btn-nouvelle-action" type="button" id="nouvelle-action-trigger" style="transform: translateY(0) !important;">
                <i class="fas fa-plus"></i>
            </button>
            </div>
        
        
        <a href="index.php?page=kpi_dashboard" class="dock-item <?php echo $currentPage == 'kpi_dashboard' ? 'active' : ''; ?>">
            <div class="dock-icon-wrapper">
                <i class="fas fa-chart-line"></i>
            </div>
            <span>KPI</span>
        </a>
        
        <a href="#" class="dock-item" id="mobile-menu-trigger" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal">
            <div class="dock-icon-wrapper">
                <i class="fas fa-bars"></i>
            </div>
            <span>Menu</span>
        </a>
    </div>
</div>

<!-- Offcanvas legacy supprimé (remplacé par modal futuriste) -->

<!-- Injection du nouveau menu modal futuriste/corporate et de ses assets -->
<?php 
    $navbar_assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
    include __DIR__ . '/futuristic_menu.php';
?>
<link rel="stylesheet" href="<?php echo $navbar_assets_path; ?>css/futuristic-menu.css">
<script src="<?php echo $navbar_assets_path; ?>js/futuristic-menu.js"></script>
<script src="<?php echo $navbar_assets_path; ?>js/futuristic-menu-fix.js"></script>

