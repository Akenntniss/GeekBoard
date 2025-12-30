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

// Détection précise pour Safari Desktop
$isSafariDesktop = false;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (preg_match('/Macintosh/i', $userAgent) && strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false && !preg_match('/(iPad|iPhone)/i', $userAgent)) {
    $isSafariDesktop = true;
}

// Ajouter une classe CSS au body pour les iPad
if ($isIPad) {
    echo '<script>document.body.classList.add("ipad-device");</script>';
}
if ($isSafariDesktop) {
    echo '<script>document.body.classList.add("safari-desktop", "safari-browser");</script>';
}

// Script amélioré pour la détection de tablette et l'application des styles appropriés
echo '<script>
// Fonction pour détecter si c\'est Safari sur Mac (desktop)
function isSafariDesktopBrowser() {
    const ua = navigator.userAgent;
    return /Macintosh.*Safari/i.test(ua) && 
           !/Chrome/i.test(ua) && 
           !/CriOS/i.test(ua) && 
           !/(iPad|iPhone)/i.test(ua) &&
           !navigator.maxTouchPoints; // Les Mac n\'ont pas de touchscreen
}

// Fonction pour détecter si c\'est un appareil tablette
function isTabletDevice() {
    return (window.innerWidth <= 1366 && window.innerWidth >= 600) || 
           /ipad|tablet|playbook|silk|android(?!.*mobile)/i.test(navigator.userAgent.toLowerCase());
}

// Fonction pour détecter si c\'est un iPad
function isIPadDevice() {
    // Détection iPadOS 13+ (se fait passer pour un Mac Intel)
    const isIpadOS = /Macintosh/i.test(navigator.userAgent) && navigator.maxTouchPoints && navigator.maxTouchPoints > 0;
    // Détection classique
    const isClassicIpad = /ipad/i.test(navigator.userAgent.toLowerCase());
    
    return isClassicIpad || isIpadOS;
}

// Fonction pour détecter l\'orientation de l\'iPad
function isIPadLandscape() {
    return isIPadDevice() && window.innerWidth > window.innerHeight;
}

// Fonction pour détecter l\'orientation de l\'iPad portrait
function isIPadPortrait() {
    return isIPadDevice() && window.innerWidth <= window.innerHeight;
}

// Fonction pour gérer l\'affichage selon la taille d\'écran et l\'orientation
function handleNavbarDisplay() {
    const desktopNavbar = document.getElementById("desktop-navbar");
    const internalDock = document.getElementById("mobile-dock"); // Dock interne
    const realMobileDock = document.getElementById("mobile_dock_bar"); // Le vrai dock actif
    
    // Détection
    const isSafari = isSafariDesktopBrowser();
    const isIPad = isIPadDevice();
    const isLandscape = isIPad ? isIPadLandscape() : false; // Doit être iPad ET paysage
    const isPortrait = isIPad ? isIPadPortrait() : false;
    const isMobileSize = window.innerWidth < 1366;
    
    console.log("🔍 [NAVBAR DEBUG]", {
        ua: navigator.userAgent,
        authIPad: isIPad,
        isSafari: isSafari,
        landscape: isLandscape,
        portrait: isPortrait,
        mobileSize: isMobileSize,
        w: window.innerWidth,
        h: window.innerHeight,
        touch: navigator.maxTouchPoints
    });
    
    // Classes body
    if (isSafari) {
        document.body.classList.add("safari-desktop", "safari-browser");
        document.body.classList.remove("ipad-device", "tablet-device");
    }

    // Helper pour masquer/afficher
    const show = (el) => { if(el) { el.style.setProperty("display", "block", "important"); el.style.setProperty("visibility", "visible", "important"); el.style.setProperty("opacity", "1", "important"); } };
    const hide = (el) => { if(el) { el.style.setProperty("display", "none", "important"); el.style.setProperty("visibility", "hidden", "important"); el.style.setProperty("opacity", "0", "important"); } };
    const setBodyPadding = (val) => document.body.style.setProperty("padding-top", val, "important");
    
    // LOGIQUE
    
    // 1. Safari Desktop : TOUJOURS afficher la navbar desktop
    if (isSafari) {
        console.log("🖥️ [NAVBAR] Safari Desktop détecté -> Force Navbar + Padding");
        if (desktopNavbar) {
            show(desktopNavbar);
            desktopNavbar.style.setProperty("position", "fixed", "important");
            desktopNavbar.style.setProperty("top", "0", "important");
            desktopNavbar.style.setProperty("z-index", "10000", "important");
            setBodyPadding("var(--navbar-height)"); // Utilise la variable CSS globale
        }
        hide(internalDock);
        hide(realMobileDock);
        return;
    }
    
    // 2. iPad Paysage : Afficher Navbar Desktop
    if (isIPad && isLandscape) {
        console.log("🖥️ [NAVBAR] iPad Paysage -> Navbar Desktop + Padding");
        document.body.classList.add("ipad-landscape");
        document.body.classList.remove("ipad-portrait");
        
        show(desktopNavbar);
        if (desktopNavbar) {
             desktopNavbar.style.setProperty("position", "fixed", "important");
             desktopNavbar.style.setProperty("top", "0", "important");
             desktopNavbar.style.setProperty("z-index", "10000", "important");
             setBodyPadding("var(--navbar-height)"); // Utilise la variable CSS globale
        }
        
        hide(internalDock);
        hide(realMobileDock);
        return;
    }
    
    // 3. iPad Portrait ou Mobile ou Petit écran
    if ((isIPad && isPortrait) || isMobileSize) {
        console.log("📱 [NAVBAR] Mobile/iPad Portrait -> Dock Mobile (No Padding)");
        if (isIPad) {
            document.body.classList.add("ipad-portrait");
            document.body.classList.remove("ipad-landscape");
        }
        
        hide(desktopNavbar);
        setBodyPadding("0px"); // Pas de padding nécessaire car dock en bas
        
        hide(internalDock);
        show(realMobileDock);
    } else {
        // 4. Grand écran (non Safari, non iPad) - PC Classique
        console.log("🖥️ [NAVBAR] PC Desktop -> Navbar Desktop");
        show(desktopNavbar);
        // Sur PC le CSS gère généralement le padding ou position relative/sticky
        // On ne force pas le padding ici pour éviter de casser le layout existant si géré autrement
        // Mais si fixed, il faudrait. Dans le doute, on laisse le CSS faire sauf si problème signalé.
        hide(internalDock);
        hide(realMobileDock);
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
    
    // Écouter les changements d\'orientation spécifiquement pour iPad
    window.addEventListener("orientationchange", function() {
        console.log("🔄 [ORIENTATION-CHANGE] Détecté");
        // Vérifier plusieurs fois pour être sûr que les dimensions sont à jour
        setTimeout(handleNavbarDisplay, 100);
        setTimeout(handleNavbarDisplay, 500);
        setTimeout(handleNavbarDisplay, 1000);
    });
    
    // Écouter les événements de redimensionnement avec debounce
    let resizeTimeout;
    window.addEventListener("resize", function() {
        clearTimeout(resizeTimeout);
        // Délai un peu plus long pour laisser le temps au layout de se stabiliser
        resizeTimeout = setTimeout(handleNavbarDisplay, 200);
    });
    
    // Force immédiate et périodique au début
    if (isSafariDesktopBrowser() || isIPadDevice()) {
        handleNavbarDisplay();
        // Vérifier après le chargement complet
        window.addEventListener("load", handleNavbarDisplay);
        // Et encore une fois un peu plus tard
        setTimeout(handleNavbarDisplay, 1000);
    }
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
<nav id="desktop-navbar" class="navbar navbar-light bg-white border-bottom shadow-sm py-2" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: var(--navbar-height) !important; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; z-index: 1030 !important;">
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
        
        <!-- Logo SERVO animé au centre -->
        <a href="/index.php" class="servo-logo-container" style="text-decoration: none; cursor: pointer;">
            <div class="loader">
                <svg height="0" width="0" viewBox="0 0 100 100" class="absolute">
                    <defs class="s-xJBuHA073rTt" xmlns="http://www.w3.org/2000/svg">
                        <linearGradient class="s-xJBuHA073rTt" gradientUnits="userSpaceOnUse" y2="2" x2="0" y1="62" x1="0" id="b">
                            <stop class="s-xJBuHA073rTt" stop-color="#0369a1"></stop>
                            <stop class="s-xJBuHA073rTt" stop-color="#67e8f9" offset="1.5"></stop>
                        </linearGradient>
                        <linearGradient class="s-xJBuHA073rTt" gradientUnits="userSpaceOnUse" y2="0" x2="0" y1="64" x1="0" id="c">
                            <stop class="s-xJBuHA073rTt" stop-color="#0369a1"></stop>
                            <stop class="s-xJBuHA073rTt" stop-color="#22d3ee" offset="1"></stop>
                            <animateTransform repeatCount="1.25" keySplines=".42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1" keyTimes="0; 0.125; 0.25; 0.375; 0.5; 0.625; 0.75; 0.875; 1" dur="8s" values="0 32 32;-270 32 32;-270 32 32;-540 32 32;-540 32 32;-810 32 32;-810 32 32;-1080 32 32;-1080 32 32" type="rotate" attributeName="gradientTransform"></animateTransform>
                        </linearGradient>
                        <linearGradient class="s-xJBuHA073rTt" gradientUnits="userSpaceOnUse" y2="2" x2="0" y1="62" x1="0" id="d">
                            <stop class="s-xJBuHA073rTt" stop-color="#38bdf8"></stop>
                            <stop class="s-xJBuHA073rTt" stop-color="#075985" offset="1.5"></stop>
                        </linearGradient>
                    </defs>
                </svg>
                <!-- S -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="48" height="48" class="inline-block" style="transform: translateY(5px);">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="11" stroke="url(#b)" d="M 75,25 Q 75,15 65,15 L 35,15 Q 25,15 25,25 Q 25,35 35,37 L 65,43 Q 75,45 75,55 Q 75,65 65,65 L 35,65 Q 25,65 25,75" class="dash" id="S" pathLength="360"></path>
                </svg>
                <!-- E -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="40" height="40" class="inline-block">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="8" stroke="url(#b)" d="M 20,20 L 80,20 L 80,27 L 27,27 L 27,50 L 70,50 L 70,57 L 25,57 L 25,80 L 80,80 L 80,87 L 20,87 Z" class="dash" id="E" pathLength="360"></path>
                </svg>
                <!-- R -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="40" height="40" class="inline-block">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="8" stroke="url(#d)" d="M 20,20 L 20,87 M 20,20 L 70,20 L 80,30 L 80,43 L 70,53 L 20,53 M 70,53 L 80,87" class="dash" id="R" pathLength="360"></path>
                </svg>
                <!-- V -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="40" height="40" class="inline-block">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="12" stroke="url(#d)" d="M 20,20 L 50,80 L 80,20" class="dash" id="V" pathLength="360"></path>
                </svg>
                <!-- O -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="40" height="40" class="inline-block">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="11" stroke="url(#c)" d="M 50,15 A 35,35 0 0 1 85,50 A 35,35 0 0 1 50,85 A 35,35 0 0 1 15,50 A 35,35 0 0 1 50,15 Z" class="spin" id="O" pathLength="360"></path>
                </svg>
            </div>
        </a>
        
        <!-- Boutons de navigation à droite -->
        <div class="d-flex align-items-center ms-auto gap-2">
            <!-- Notifications (Desktop) -->
            <div class="dropdown me-2" id="desktop-notifications-dropdown">
                <button class="nav-link position-relative notification-bell-link btn btn-link border-0" id="navbarDropdownNotifications" role="button" aria-expanded="false" style="background: none; padding: 0.5rem;">
                    <i class="fas fa-bell fs-5"></i>
                    <span class="position-absolute translate-middle badge rounded-pill bg-danger d-none" id="nav-notifications-badge" style="top: 5px; right: -10px; font-size: 0.65rem; padding: 0.25em 0.5em; border: 2px solid white;">
                        0
                    </span>
                </button>
                
                <!-- Notification Dropdown Preview -->
                <div class="notification-preview-dropdown" id="notificationPreviewDropdown" style="display: none;">
                    <div class="notif-preview-header">
                        <h6 class="mb-0">Notifications récentes</h6>
                        <button class="btn-close-preview" onclick="closeNotificationPreview()" aria-label="Fermer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="notif-preview-list" id="notifPreviewList">
                        <div class="notif-preview-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>Chargement...</span>
                        </div>
                    </div>
                    <div class="notif-preview-footer d-flex gap-2">
                        <button class="btn-mark-read" onclick="markAllNotificationsRead()">
                             Lu <i class="fas fa-check-double ms-1"></i>
                        </button>
                        <a href="index.php?page=notifications" class="btn-see-all">
                             Voir <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bouton + (toujours visible) -->
            <button class="btn btn-primary btn-nouvelle-improved" type="button" id="btnNouvelle" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal" title="Nouvelle action">
                <i class="fas fa-plus"></i>
                <span class="btn-text" style="display: none;">Nouvelle</span>
            </button>
            
            <!-- Bouton hamburger (toujours visible) -->
            <button class="btn main-menu-btn" style="background: #f8f9fa !important; border: 1px solid #dee2e6 !important; display: inline-flex !important; visibility: visible !important; opacity: 1 !important; color: #333 !important; min-width: 40px !important; min-height: 40px !important;" type="button" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal" aria-controls="futuristicMenuModal">
                <i class="fas fa-bars"></i>
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
            <button 
                class="btn-nouvelle-action" 
                type="button" 
                id="nouvelle-action-trigger" 
                data-bs-toggle="modal" 
                data-bs-target="#nouvelles_actions_modal" 
                style="transform: translateY(0) !important;"
            >
                <i class="fas fa-plus"></i>
            </button>
            </div>
        
        
        <a href="index.php?page=taches" class="dock-item <?php echo $currentPage == 'taches' ? 'active' : ''; ?>">
            <div class="dock-icon-wrapper">
                <i class="fas fa-tasks"></i>
            </div>
            <span>Tâches</span>
        </a>

        <a href="index.php?page=notifications" class="dock-item <?php echo $currentPage == 'notifications' ? 'active' : ''; ?>">
            <div class="dock-icon-wrapper position-relative">
                <i class="fas fa-bell"></i>
                <span class="position-absolute translate-middle badge rounded-pill bg-danger d-none" id="nav-notifications-badge-mobile" style="top: -5px; right: -15px; font-size: 0.6rem; padding: 0.2em 0.4em; border: 1.5px solid white;">
                    0
                </span>
            </div>
            <span>Notif.</span>
        </a>
        
        <a href="#" class="dock-item" id="mobile-menu-trigger" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal">
            <div class="dock-icon-wrapper">
                <i class="fas fa-bars"></i>
            </div>
            <span>Menu</span>
        </a>
    </div>
</div>

<!-- Offcanvas legacy supprimé (remplacé par le modal futuriste) -->

<!-- Injection du nouveau menu modal futuriste/corporate et de ses assets -->
<?php 
    $navbar_assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
    include __DIR__ . '/futuristic_menu.php';
?>
<link rel="stylesheet" href="<?php echo $navbar_assets_path; ?>css/futuristic-menu.css">
<script src="<?php echo $navbar_assets_path; ?>js/futuristic-menu.js"></script>


<style>
/* Notification Preview Dropdown */
#desktop-notifications-dropdown {
    position: relative;
}

.notification-preview-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 380px;
    max-width: 95vw;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    z-index: 10000;
    animation: dropdownSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

body.night-mode .notification-preview-dropdown {
    background: rgba(15, 23, 42, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

@keyframes dropdownSlideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notif-preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

body.night-mode .notif-preview-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.notif-preview-header h6 {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--day-text);
}

body.night-mode .notif-preview-header h6 {
    color: var(--night-text);
}

.btn-close-preview {
    background: none;
    border: none;
    padding: 0.25rem;
    cursor: pointer;
    color: var(--day-text-light);
    transition: all 0.2s;
}

body.night-mode .btn-close-preview {
    color: var(--night-text-light);
}

.btn-close-preview:hover {
    color: var(--day-text);
    transform: rotate(90deg);
}

body.night-mode .btn-close-preview:hover {
    color: var(--night-text);
}

.notif-preview-list {
    max-height: 400px;
    overflow-y: auto;
    padding: 0.5rem 0;
}

.notif-preview-list::-webkit-scrollbar {
    width: 6px;
}

.notif-preview-list::-webkit-scrollbar-track {
    background: transparent;
}

.notif-preview-list::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 3px;
}

body.night-mode .notif-preview-list::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
}

.notif-preview-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 2rem;
    color: var(--day-text-light);
}

body.night-mode .notif-preview-loading {
    color: var(--night-text-light);
}

.notif-preview-item {
    display: flex;
    padding: 0.875rem 1.25rem;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
    color: inherit;
    border-left: 3px solid transparent;
}

.notif-preview-item:hover {
    background: rgba(0, 0, 0, 0.03);
}

body.night-mode .notif-preview-item:hover {
    background: rgba(255, 255, 255, 0.03);
}

.notif-preview-item.unread {
    background: rgba(67, 97, 238, 0.04);
    border-left-color: #4361ee;
}

body.night-mode .notif-preview-item.unread {
    background: rgba(67, 97, 238, 0.08);
}

.notif-preview-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    margin-right: 0.875rem;
    flex-shrink: 0;
}

.notif-preview-content {
    flex: 1;
    min-width: 0;
}

.notif-preview-msg {
    font-size: 0.875rem;
    line-height: 1.4;
    margin-bottom: 0.25rem;
    color: var(--day-text);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

body.night-mode .notif-preview-msg {
    color: var(--night-text);
}

.notif-preview-time {
    font-size: 0.75rem;
    color: var(--day-text-light);
}

body.night-mode .notif-preview-time {
    color: var(--night-text-light);
}

.notif-preview-footer {
    padding: 0.875rem 1.25rem;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

body.night-mode .notif-preview-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.btn-see-all, .btn-mark-read {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
    padding: 0.625rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.btn-see-all {
    background: linear-gradient(135deg, #4361ee, #3b82f6);
    color: white;
}

.btn-mark-read {
    background: rgba(0, 0, 0, 0.05);
    color: var(--day-text);
}

body.night-mode .btn-mark-read {
    background: rgba(255, 255, 255, 0.1);
    color: var(--night-text);
}

.btn-see-all:hover {
    background: linear-gradient(135deg, #3b82f6, #4361ee);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
}

.btn-mark-read:hover {
    background: rgba(0, 0, 0, 0.08);
    transform: translateY(-1px);
}

body.night-mode .btn-mark-read:hover {
    background: rgba(255, 255, 255, 0.15);
}

.notif-preview-empty {
    padding: 2.5rem 1.5rem;
    text-align: center;
    color: var(--day-text-light);
}

body.night-mode .notif-preview-empty {
    color: var(--night-text-light);
}

.notif-preview-empty i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

@media (max-width: 576px) {
    .notification-preview-dropdown {
        width: calc(100vw - 2rem);
        right: 1rem;
    }
}
</style>

<script>
// Notification Preview Dropdown Logic
document.addEventListener('DOMContentLoaded', function() {
    const bellBtn = document.getElementById('navbarDropdownNotifications');
    const dropdown = document.getElementById('notificationPreviewDropdown');
    const previewList = document.getElementById('notifPreviewList');
    
    if (!bellBtn || !dropdown) return;
    
    // Cache key for sessionStorage
    const CACHE_KEY = 'notif_preview_cache';
    const CACHE_DURATION = 30000; // 30 seconds
    
    bellBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (dropdown.style.display === 'none') {
            loadNotifications();
            dropdown.style.display = 'block';
        } else {
            dropdown.style.display = 'none';
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
    
    function loadNotifications() {
        // Check cache first
        const cached = getCachedNotifications();
        if (cached) {
            renderNotifications(cached);
            return;
        }
        
        // Show loading state
        previewList.innerHTML = `
            <div class="notif-preview-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Chargement...</span>
            </div>
        `;
        
        // Fetch fresh data
        fetch('ajax/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cacheNotifications(data);
                    renderNotifications(data);
                } else {
                    showError();
                }
            })
            .catch(() => {
                showError();
            });
    }
    
    function renderNotifications(data) {
        if (!data.notifications || data.notifications.length === 0) {
            previewList.innerHTML = `
                <div class="notif-preview-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>Aucune notification</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        data.notifications.forEach(notif => {
            const icon = notif.icon || 'fas fa-bell';
            const color = notif.color || '#4361ee';
            const isUnread = notif.status === 'new';
            const timeAgo = notif.time_ago || formatTimeAgo(notif.created_at);
            const actionUrl = notif.action_url || 'index.php?page=notifications';
            
            html += `
                <a href="${actionUrl}" class="notif-preview-item ${isUnread ? 'unread' : ''}">
                    <div class="notif-preview-icon" style="background: ${color}20; color: ${color};">
                        <i class="${icon}"></i>
                    </div>
                    <div class="notif-preview-content">
                        <div class="notif-preview-msg">${escapeHtml(notif.message)}</div>
                        <div class="notif-preview-time">${timeAgo}</div>
                    </div>
                </a>
            `;
        });
        
        previewList.innerHTML = html;
    }
    
    function showError() {
        previewList.innerHTML = `
            <div class="notif-preview-empty">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Erreur de chargement</p>
            </div>
        `;
    }
    
    function getCachedNotifications() {
        try {
            const cached = sessionStorage.getItem(CACHE_KEY);
            if (!cached) return null;
            
            const data = JSON.parse(cached);
            if (Date.now() - data.timestamp < CACHE_DURATION) {
                return data.value;
            }
            sessionStorage.removeItem(CACHE_KEY);
            return null;
        } catch {
            return null;
        }
    }
    
    function cacheNotifications(data) {
        try {
            sessionStorage.setItem(CACHE_KEY, JSON.stringify({
                timestamp: Date.now(),
                value: data
            }));
        } catch {}
    }
    
    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        
        if (diff < 60) return "À l'instant";
        if (diff < 3600) return Math.floor(diff / 60) + ' min';
        if (diff < 86400) return Math.floor(diff / 3600) + ' h';
        return Math.floor(diff / 86400) + ' j';
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});

function closeNotificationPreview() {
    const dropdown = document.getElementById('notificationPreviewDropdown');
    if (dropdown) {
        dropdown.style.display = 'none';
    }
}

function markAllNotificationsRead() {
    const btn = document.querySelector('.btn-mark-read');
    if (btn) {
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        fetch('ajax/notification_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=mark_all_read'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear cache
                sessionStorage.removeItem('notif_preview_cache');
                
                // Update badges
                const badges = document.querySelectorAll('.badge.bg-danger');
                badges.forEach(badge => {
                    badge.textContent = '0';
                    badge.classList.add('d-none');
                });
                
                // Reload list
                const previewList = document.getElementById('notifPreviewList');
                if (previewList) {
                    previewList.innerHTML = `
                        <div class="notif-preview-empty">
                            <i class="fas fa-check-circle" style="color: #10b981; opacity: 1;"></i>
                            <p>Toutes les notifications lues</p>
                        </div>
                    `;
                }
            }
        })
        .catch(err => console.error(err))
        .finally(() => {
            if (btn) {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        });
    }
}
</script>

<!-- Fix Navbar Alignment CSS -->
<style>
/* ============================================
   NAVBAR ALIGNMENT FIX
   Properly center SERVO logo and align elements
   ============================================ */

#desktop-navbar .container-fluid {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    height: 100% !important;
}

/* Logo on the left */
#desktop-navbar .navbar-brand {
    flex-shrink: 0 !important;
    display: flex !important;
    align-items: center !important;
}

#desktop-navbar .navbar-brand img {
    height: 36px !important;
    width: auto !important;
}

/* SERVO Logo Container - Absolute center */
#desktop-navbar .servo-logo-container {
    position: absolute !important;
    left: 50% !important;
    top: 50% !important;
    transform: translate(-50%, -50%) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

#desktop-navbar .servo-logo-container .loader {
    display: flex !important;
    align-items: center !important;
    gap: 0 !important;
}

#desktop-navbar .servo-logo-container svg {
    display: inline-block !important;
    vertical-align: middle !important;
}

/* Right side buttons */
#desktop-navbar .d-flex.align-items-center.ms-auto {
    flex-shrink: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
}

/* Welcome message - hidden on smaller desktops */
#desktop-navbar .d-none.d-md-flex {
    position: absolute !important;
    left: 65px !important;
    max-width: 200px !important;
    font-size: 0.8rem !important;
}

@media (max-width: 1200px) {
    #desktop-navbar .d-none.d-md-flex {
        display: none !important;
    }
}

/* Notification button alignment */
#desktop-navbar #desktop-notifications-dropdown button {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    height: 40px !important;
    width: 40px !important;
}

/* + Button alignment */
#desktop-navbar .btn-nouvelle-improved {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 40px !important;
    height: 40px !important;
    padding: 0 !important;
    border-radius: 10px !important;
}

/* Menu button alignment */
#desktop-navbar .main-menu-btn {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 40px !important;
    height: 40px !important;
    padding: 0 !important;
    border-radius: 10px !important;
}

/* Dark mode specific fixes */
body.night-mode #desktop-navbar {
    background: #0f172a !important;
    border-color: #334155 !important;
}

body.night-mode #desktop-navbar .navbar-brand img {
    filter: brightness(1.1) !important;
}

body.night-mode #desktop-navbar .main-menu-btn {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #e2e8f0 !important;
}

body.night-mode #desktop-navbar #navbarDropdownNotifications {
    color: #e2e8f0 !important;
}
</style>

