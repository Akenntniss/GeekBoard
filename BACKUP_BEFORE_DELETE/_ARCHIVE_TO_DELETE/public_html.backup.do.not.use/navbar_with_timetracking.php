<?php
/**
 * Navbar modifiée avec système de pointage Clock-In/Clock-Out
 * Basée sur la navbar existante de GeekBoard
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

// Script de détection de tablette (conservé de l'original)
echo '<script>
function isTabletDevice() {
    return (window.innerWidth <= 1366 && window.innerWidth >= 600) || 
           /ipad|tablet|playbook|silk|android(?!.*mobile)/i.test(navigator.userAgent.toLowerCase());
}

function handleNavbarDisplay() {
    if (window.innerWidth < 1366) {
        document.body.classList.add("tablet-device");
        
        const desktopNavbar = document.getElementById("desktop-navbar");
        const mobileDock = document.getElementById("mobile-dock");
        
        if (desktopNavbar) desktopNavbar.style.display = "none";
        if (mobileDock) mobileDock.style.display = "block";
    } else {
        document.body.classList.remove("tablet-device");
        
        const desktopNavbar = document.getElementById("desktop-navbar");
        const mobileDock = document.getElementById("mobile-dock");
        
        if (desktopNavbar && !document.body.classList.contains("ipad-device") && !document.body.classList.contains("pwa-mode")) {
            desktopNavbar.style.display = "block";
        }
        
        if (mobileDock && !document.body.classList.contains("ipad-device") && !document.body.classList.contains("pwa-mode")) {
            mobileDock.style.display = "none";
        }
    }
}

document.addEventListener("DOMContentLoaded", function() {
    if (isTabletDevice()) {
        document.body.classList.add("tablet-device");
    }
    
    handleNavbarDisplay();
    window.addEventListener("resize", handleNavbarDisplay);
});
</script>';

// Afficher la navbar desktop
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
        
        <!-- SYSTÈME DE POINTAGE - Boutons Clock-In/Clock-Out -->
        <div class="time-tracking-controls d-none d-lg-flex align-items-center me-3">
            <!-- Statut actuel -->
            <div id="time-status-display" class="time-tracking-status me-2">
                <small class="text-muted">Chargement...</small>
            </div>
            
            <!-- Bouton principal Clock-In/Clock-Out -->
            <button id="clock-button" class="btn btn-success btn-sm mx-1" onclick="timeTracking?.clockIn()">
                <i class="fas fa-sign-in-alt"></i> Clock-In
            </button>
            
            <!-- Bouton Pause (affiché seulement quand pointé) -->
            <button id="break-button" class="btn btn-outline-secondary btn-sm mx-1" style="display: none;">
                <i class="fas fa-pause"></i> Pause
            </button>
        </div>
        
        <!-- Bouton Nouvelle avec dropdown -->
        <div class="dropdown d-none d-lg-block me-auto">
            <button class="btn btn-primary" type="button" id="btnNouvelle" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal">
                <i class="fas fa-plus-circle me-1"></i> Nouvelle
            </button>
        </div>
        
        <!-- Boutons de navigation à droite -->
        <div class="d-none d-lg-flex align-items-center ms-auto">
            <!-- Bouton de recherche -->
            <button class="btn btn-outline-primary me-2" type="button" data-bs-toggle="modal" data-bs-target="#rechercheModal">
                <i class="fas fa-search me-1"></i> Rechercher
            </button>
            
            <!-- Bouton hamburger pour menu principal -->
            <button class="btn btn-outline-secondary ms-2 main-menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenuOffcanvas" aria-controls="mainMenuOffcanvas">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Version mobile du bouton hamburger -->
        <button class="navbar-toggler d-lg-none ms-auto main-menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenuOffcanvas" aria-controls="mainMenuOffcanvas">
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
                <span class="badge bg-info ms-1"><?php echo htmlspecialchars($_SESSION['shop_name']); ?></span>
                <?php endif; ?>
            </span>
            
            <!-- Système de pointage pour mobile -->
            <div class="mobile-time-tracking mt-1">
                <div id="mobile-time-status-display" class="mb-1">
                    <small class="text-muted">Chargement...</small>
                </div>
                <div class="d-flex justify-content-center gap-2">
                    <button id="mobile-clock-button" class="btn btn-success btn-sm" onclick="timeTracking?.clockIn()">
                        <i class="fas fa-sign-in-alt"></i> Clock-In
                    </button>
                    <button id="mobile-break-button" class="btn btn-outline-secondary btn-sm" style="display: none;">
                        <i class="fas fa-pause"></i> Pause
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Navbar mobile classique (continuez avec le reste du code original...) -->
    <nav class="mobile-navbar-bottom">
        <div class="container-fluid">
            <div class="row text-center mobile-nav-grid">
                <!-- Accueil -->
                <div class="col mobile-nav-item">
                    <a href="<?php echo (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '.' : 'pages'; ?>/accueil.php" class="nav-link-mobile">
                        <i class="fas fa-home mobile-nav-icon"></i>
                        <span class="mobile-nav-text">Accueil</span>
                    </a>
                </div>

                <!-- Nouvelle -->
                <div class="col mobile-nav-item">
                    <a href="#" class="nav-link-mobile" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal">
                        <i class="fas fa-plus-circle mobile-nav-icon text-primary"></i>
                        <span class="mobile-nav-text">Nouvelle</span>
                    </a>
                </div>

                <!-- Recherche -->
                <div class="col mobile-nav-item">
                    <a href="#" class="nav-link-mobile" data-bs-toggle="modal" data-bs-target="#rechercheModal">
                        <i class="fas fa-search mobile-nav-icon"></i>
                        <span class="mobile-nav-text">Recherche</span>
                    </a>
                </div>

                <!-- Menu -->
                <div class="col mobile-nav-item">
                    <a href="#" class="nav-link-mobile main-menu-btn" data-bs-toggle="offcanvas" data-bs-target="#mainMenuOffcanvas">
                        <i class="fas fa-bars mobile-nav-icon"></i>
                        <span class="mobile-nav-text">Menu</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
</div>

<!-- Inclure le JavaScript de time tracking -->
<script src="<?php echo $navbar_assets_path; ?>js/time_tracking.js"></script>

<!-- Script pour synchroniser les boutons mobile et desktop -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Synchroniser l'affichage entre desktop et mobile
    document.addEventListener('timeTrackingUpdate', function(event) {
        const status = event.detail;
        
        // Mettre à jour l'affichage mobile
        const mobileClockButton = document.getElementById('mobile-clock-button');
        const mobileBreakButton = document.getElementById('mobile-break-button');
        const mobileStatusDisplay = document.getElementById('mobile-time-status-display');
        
        if (mobileClockButton) {
            if (status.is_clocked_in) {
                mobileClockButton.innerHTML = '<i class="fas fa-sign-out-alt"></i> Clock-Out';
                mobileClockButton.className = 'btn btn-danger btn-sm';
                mobileClockButton.onclick = () => timeTracking?.clockOut();
            } else {
                mobileClockButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> Clock-In';
                mobileClockButton.className = 'btn btn-success btn-sm';
                mobileClockButton.onclick = () => timeTracking?.clockIn();
            }
        }
        
        if (mobileBreakButton) {
            if (status.is_clocked_in) {
                mobileBreakButton.style.display = 'inline-block';
                if (status.is_on_break) {
                    mobileBreakButton.innerHTML = '<i class="fas fa-play"></i> Reprendre';
                    mobileBreakButton.className = 'btn btn-warning btn-sm';
                    mobileBreakButton.onclick = () => timeTracking?.endBreak();
                } else {
                    mobileBreakButton.innerHTML = '<i class="fas fa-pause"></i> Pause';
                    mobileBreakButton.className = 'btn btn-outline-secondary btn-sm';
                    mobileBreakButton.onclick = () => timeTracking?.startBreak();
                }
            } else {
                mobileBreakButton.style.display = 'none';
            }
        }
        
        // Mettre à jour l'affichage de statut mobile
        if (mobileStatusDisplay) {
            if (!status.is_clocked_in) {
                mobileStatusDisplay.innerHTML = '<small class="text-muted">Non pointé</small>';
            } else {
                const workDuration = status.work_duration || 0;
                const formatTime = (hours) => {
                    const h = Math.floor(hours);
                    const m = Math.floor((hours - h) * 60);
                    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
                };
                
                mobileStatusDisplay.innerHTML = `
                    <small class="text-success">
                        <i class="fas fa-clock"></i> ${formatTime(workDuration)}
                        ${status.is_on_break ? '<span class="text-warning">(Pause)</span>' : ''}
                    </small>
                `;
            }
        }
    });
});
</script>

<!-- Styles CSS additionnels pour le système de pointage -->
<style>
.time-tracking-controls {
    border-left: 1px solid #e9ecef;
    border-right: 1px solid #e9ecef;
    padding-left: 12px;
    padding-right: 12px;
}

.time-tracking-status {
    min-width: 120px;
    text-align: center;
}

.mobile-time-tracking {
    border-top: 1px solid #e9ecef;
    padding-top: 8px;
}

@media (max-width: 992px) {
    .time-tracking-controls {
        border: none;
        padding: 0;
    }
}

/* Animation pour les boutons de pointage */
.time-tracking-controls .btn {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.time-tracking-controls .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Effet de pulsation pour le bouton actif */
.btn-danger.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}

/* Styles pour le statut en temps réel */
.time-tracking-status .text-success {
    font-weight: 600;
    color: #28a745 !important;
}

.time-tracking-status .text-warning {
    font-weight: 600;
    color: #ffc107 !important;
}
</style>
?>

