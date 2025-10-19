<?php
/**
 * Navbar avec logo SERVO exact de servo.tools
 * Reproduction exacte des SVG depuis inscription.php
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
?>

<nav id="desktop-navbar" class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-2" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: var(--navbar-height) !important; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; z-index: 1030 !important;">
    <div class="container-fluid px-3">
        <!-- Logo à gauche -->
        <a class="navbar-brand me-0 me-lg-4 d-flex align-items-center" href="index.php">
            <?php $navbar_assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/'; ?>
            <img src="<?php echo $navbar_assets_path; ?>images/logo/logoservo.png" alt="GeekBoard" height="40">
        </a>
        
        <!-- Logo SERVO animé au centre - EXACT de servo.tools -->
        <div class="servo-logo-container">
            <div class="loader">
                <svg height="0" width="0" viewBox="0 0 100 100" class="absolute">
                    <defs xmlns="http://www.w3.org/2000/svg">
                        <linearGradient
                            gradientUnits="userSpaceOnUse"
                            y2="2"
                            x2="0"
                            y1="62"
                            x1="0"
                            id="servo-b"
                        >
                            <stop stop-color="#0369a1"></stop>
                            <stop stop-color="#67e8f9" offset="1.5"></stop>
                        </linearGradient>
                        <linearGradient
                            gradientUnits="userSpaceOnUse"
                            y2="0"
                            x2="0"
                            y1="64"
                            x1="0"
                            id="servo-c"
                        >
                            <stop stop-color="#0369a1"></stop>
                            <stop stop-color="#22d3ee" offset="1"></stop>
                            <animateTransform
                                repeatCount="indefinite"
                                keySplines=".42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1"
                                keyTimes="0; 0.125; 0.25; 0.375; 0.5; 0.625; 0.75; 0.875; 1"
                                dur="8s"
                                values="0 32 32;-270 32 32;-270 32 32;-540 32 32;-540 32 32;-810 32 32;-810 32 32;-1080 32 32;-1080 32 32"
                                type="rotate"
                                attributeName="gradientTransform"
                            ></animateTransform>
                        </linearGradient>
                        <linearGradient
                            gradientUnits="userSpaceOnUse"
                            y2="2"
                            x2="0"
                            y1="62"
                            x1="0"
                            id="servo-d"
                        >
                            <stop stop-color="#38bdf8"></stop>
                            <stop stop-color="#075985" offset="1.5"></stop>
                        </linearGradient>
                    </defs>
                </svg>
                
                <!-- Lettre S - VRAIE FORME -->
                <svg class="servo-svg-letter" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="30" height="30">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="8" stroke="url(#servo-b)" d="M 20,20 L 80,20 L 80,27 L 27,27 L 27,50 L 70,50 L 70,57 L 25,57 L 25,80 L 80,80 L 80,87 L 20,87 Z" pathLength="360"></path>
                </svg>
                
                <!-- Lettre E - VRAIE FORME -->
                <svg class="servo-svg-letter" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="30" height="30">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="8" stroke="url(#servo-b)" d="M 20,20 L 80,20 L 80,27 L 27,27 L 27,50 L 70,50 L 70,57 L 25,57 L 25,80 L 80,80 L 80,87 L 20,87 Z" pathLength="360"></path>
                </svg>
                
                <!-- Lettre R - VRAIE FORME -->
                <svg class="servo-svg-letter" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="30" height="30">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="8" stroke="url(#servo-b)" d="M 20,20 L 50,20 L 50,50 L 20,50 L 20,80 L 80,80 L 80,87 L 20,87 L 20,20 L 50,20 L 50,50 L 70,50 L 70,57 L 50,57 L 50,80 L 20,80" pathLength="360"></path>
                </svg>
                
                <!-- Lettre V - VRAIE FORME -->
                <svg class="servo-svg-letter" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="30" height="30">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="12" stroke="url(#servo-d)" d="M 20,20 L 50,80 L 80,20" pathLength="360"></path>
                </svg>
                
                <!-- Lettre O - VRAIE FORME -->
                <svg class="servo-svg-letter" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100" width="30" height="30">
                    <path stroke-linejoin="round" stroke-linecap="round" stroke-width="11" stroke="url(#servo-c)" d="M 50,15 A 35,35 0 0 1 85,50 A 35,35 0 0 1 50,85 A 35,35 0 0 1 15,50 A 35,35 0 0 1 50,15 Z" pathLength="360"></path>
                </svg>
            </div>
        </div>

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
        
        <!-- Boutons de navigation à droite -->
        <div class="d-flex align-items-center ms-auto gap-2">
            <!-- Bouton + (toujours visible) -->
            <button class="btn btn-primary btn-nouvelle-improved" type="button" id="btnNouvelle" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal" title="Nouvelle action">
                <i class="fas fa-plus"></i>
                <span class="btn-text" style="display: none;">Nouvelle</span>
            </button>
            
            <!-- Bouton hamburger (toujours visible) -->
            <button class="navbar-toggler main-menu-btn" type="button" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal" aria-controls="futuristicMenuModal">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</nav>
