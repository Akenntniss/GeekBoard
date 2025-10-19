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
        
        <a href="#" class="dock-item" id="mobile-menu-trigger">
            <div class="dock-icon-wrapper">
                <i class="fas fa-bars"></i>
            </div>
            <span>Menu</span>
        </a>
    </div>
</div>

<!-- Ancien menu offcanvas supprimé - remplacé par le menu futuriste -->
<!-- <div class="offcanvas offcanvas-end" tabindex="-1" id="mainMenuOffcanvas" aria-labelledby="mainMenuOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mainMenuOffcanvasLabel">
            <img src="<?php echo $navbar_assets_path; ?>images/logo/logoservo.png" alt="GeekBoard" height="30" class="me-2">
        </h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
    <div class="offcanvas-body p-0">
        <div class="launchpad-container offcanvas-launchpad">
            <!-- Section Gestion Principale -->
            <div class="launchpad-section">
                <h3 class="launchpad-section-title">Gestion Principale</h3>
                <div class="launchpad-section-content">
                    <a href="index.php" class="launchpad-item <?php echo empty($_GET['page']) ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-home">
                            <i class="fas fa-home"></i>
    </div>
                        <span>Accueil</span>
                    </a>
                    
                    <a href="index.php?page=reparations" class="launchpad-item <?php echo $currentPage == 'reparations' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-repair">
                            <i class="fas fa-tools"></i>
                        </div>
                        <span>Réparations</span>
                    </a>
                    
                    <a href="index.php?page=ajouter_reparation" class="launchpad-item <?php echo $currentPage == 'ajouter_reparation' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-add">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <span>Nouvelle Réparation</span>
                    </a>

                    <a href="index.php?page=commandes_pieces" class="launchpad-item <?php echo $currentPage == 'commandes_pieces' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-order">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <span>Commandes</span>
                    </a>
                    
                    <a href="index.php?page=taches" class="launchpad-item <?php echo $currentPage == 'taches' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-task">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <span>Tâches</span>
                        <?php if ($tasks_count > 0): ?>
                            <span class="badge rounded-pill bg-danger position-absolute top-0 end-0 translate-middle"><?php echo $tasks_count; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="index.php?page=rachat_appareils" class="launchpad-item <?php echo $currentPage == 'rachat_appareils' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-trade">
                            <i class="fas fa-exchange-alt"></i>
                </div>
                        <span>Rachat</span>
                    </a>

                    <a href="index.php?page=base_connaissances" class="launchpad-item <?php echo $currentPage == 'base_connaissances' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-knowledge">
                            <i class="fas fa-book"></i>
                        </div>
                        <span>Base de connaissance</span>
                    </a>
                    
                    <a href="index.php?page=clients" class="launchpad-item <?php echo $currentPage == 'clients' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-client">
                            <i class="fas fa-users"></i>
                        </div>
                        <span>Clients</span>
                    </a>
                </div>
            </div>

            <!-- Section Missions -->
            <div class="launchpad-section">
                <h3 class="launchpad-section-title">Missions</h3>
                <div class="launchpad-section-content">
                    <a href="index.php?page=mes_missions" class="launchpad-item">
                        <div class="launchpad-icon launchpad-icon-mission">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <span>Mes missions</span>
                    </a>
                    
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="index.php?page=admin_missions" class="launchpad-item">
                        <div class="launchpad-icon launchpad-icon-admin-mission">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <span>Admin missions</span>
                    </a>
                    <?php endif; ?>
                        </div>
                        </div>

            <!-- Section Communication -->
            <div class="launchpad-section">
                <h3 class="launchpad-section-title">Communication</h3>
                <div class="launchpad-section-content">
                    <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                    <a href="index.php?page=campagne_sms" class="launchpad-item <?php echo $currentPage == 'campagne_sms' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-sms">
                            <i class="fas fa-sms"></i>
                        </div>
                        <span>Campagne SMS</span>
                    </a>
                    
                    <a href="index.php?page=template_sms" class="launchpad-item <?php echo $currentPage == 'template_sms' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-template">
                            <i class="fas fa-comment-dots"></i>
            </div>
                        <span>Template SMS</span>
                    </a>
                    <?php endif; ?>
                    
                    <a href="index.php?page=sms_historique" class="launchpad-item <?php echo $currentPage == 'sms_historique' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-history">
                            <i class="fas fa-history"></i>
                        </div>
                        <span>Historique SMS</span>
                    </a>
                </div>
            </div>

            <!-- Section Administration -->
            <div class="launchpad-section">
                <h3 class="launchpad-section-title">Administration</h3>
                <div class="launchpad-section-content">
                    <a href="index.php?page=employes" class="launchpad-item <?php echo $currentPage == 'employes' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-employee">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <span>Employés</span>
                    </a>
                    
                    <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                    <a href="index.php?page=presence_gestion" class="launchpad-item <?php echo in_array($currentPage, ['presence_gestion', 'presence_ajouter', 'presence_calendrier', 'presence_export', 'presence_modifier']) ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-presence">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <span>Absences & Retards</span>
                    </a>

                    <a href="index.php?page=admin_timetracking" class="launchpad-item <?php echo (strpos($_SERVER['REQUEST_URI'], 'admin_timetracking') !== false) ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-timetracking">
                            <i class="fas fa-clock"></i>
                        </div>
                        <span>Pointage Admin</span>
                    </a>
                    <?php endif; ?>

                    <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                    <a href="index.php?page=reparation_logs" class="launchpad-item <?php echo $currentPage == 'reparation_logs' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-logs">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <span>Journaux de réparation</span>
                    </a>
                    <?php endif; ?>
                    
                    <a href="index.php?page=kpi_dashboard" class="launchpad-item <?php echo $currentPage == 'kpi_dashboard' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-kpi">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span>KPI Dashboard</span>
                    </a>
                    
                    <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                    <a href="index.php?page=bug-reports" class="launchpad-item <?php echo $currentPage == 'bug-reports' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-bug">
                            <i class="fas fa-bug"></i>
                        </div>
                        <span>Signalements bugs</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                    <a href="index.php?page=parametre" class="launchpad-item <?php echo $currentPage == 'parametre' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-settings">
                            <i class="fas fa-cog"></i>
                        </div>
                        <span>Parametre</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['shop_id'])): ?>
                    <a href="/pages/change_shop.php" class="launchpad-item">
                        <div class="launchpad-icon" style="background-color: rgba(0, 120, 232, 0.15); color: #0078E8;">
                            <i class="fas fa-store"></i>
                        </div>
                        <span>Changer de magasin</span>
                    </a>
            <?php endif; ?>

                    <a href="index.php?action=logout" class="launchpad-item">
                        <div class="launchpad-icon launchpad-icon-danger">
                            <i class="fas fa-sign-out-alt"></i>
        </div>
                        <span>Déconnexion</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div> -->

<!-- Include du nouveau menu futuriste/corporate -->
<?php include 'futuristic_menu.php'; ?>

<!-- Styles et scripts pour le menu futuriste -->
<link rel="stylesheet" href="<?php echo $navbar_assets_path; ?>css/futuristic-menu.css">
<script src="<?php echo $navbar_assets_path; ?>js/futuristic-menu.js"></script>

