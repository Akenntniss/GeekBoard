<?php
// Barre de navigation desktop uniquement - Version propre
// Détection du type d'appareil
$isMobile = preg_match('/(iPhone|iPod|Android|BlackBerry|IEMobile|Opera Mini)/i', $_SERVER['HTTP_USER_AGENT']);
$isIPad = preg_match('/(iPad)/i', $_SERVER['HTTP_USER_AGENT']) || 
          (preg_match('/(Macintosh)/i', $_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']));

// Récupérer la page courante
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Récupérer le nom de la base de données actuelle pour affichage
$db_name = '';
try {
    if (function_exists('getShopDBConnection')) {
        $shop_pdo = getShopDBConnection();
        if ($shop_pdo) {
            $db_name = $shop_pdo->query("SELECT DATABASE()")->fetchColumn();
        }
    }
} catch (Exception $e) {
    // Ignorer les erreurs
}
?>

<!-- NAVBAR DESKTOP UNIQUEMENT -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-2" id="desktop-navbar">
    <div class="container-fluid px-3">
        <!-- Logo et nom -->
        <a class="navbar-brand me-0 me-lg-4 d-flex align-items-center" href="index.php">
            <img src="assets/images/logo/logoservo.png" alt="GeekBoard" height="40">
        </a>

        <!-- Barre de recherche universelle (desktop uniquement) -->
        <div class="d-none d-lg-flex flex-grow-1 me-3">
            <div class="search-container position-relative w-100" style="max-width: 500px;">
                <input type="text" id="universal-search" class="form-control pe-5" placeholder="Rechercher clients, réparations, tâches..." autocomplete="off">
                <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent" style="z-index: 10;">
                    <i class="fas fa-search text-muted"></i>
                </button>
                <div id="search-results" class="search-results position-absolute w-100 bg-white border rounded shadow-lg mt-1 d-none" style="z-index: 1000; max-height: 400px; overflow-y: auto;"></div>
            </div>
        </div>

        <!-- Informations utilisateur et magasin (desktop) -->
        <?php if (isset($_SESSION['full_name'])): ?>
        <div class="d-none d-lg-flex align-items-center me-3">
            <span class="fw-medium text-dark">
                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                <?php if (isset($_SESSION['shop_name'])): ?>
                <span class="badge bg-info ms-1">
                    <?php echo htmlspecialchars($_SESSION['shop_name']); ?>
                    <?php if (!empty($db_name)): ?>
                    <small class="ms-1">(<?php echo htmlspecialchars($db_name); ?>)</small>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
        
        <!-- Boutons de navigation à droite -->
        <div class="d-none d-lg-flex align-items-center ms-auto">
            <!-- Bouton hamburger pour menu principal -->
            <button class="btn btn-outline-secondary ms-2 main-menu-btn" type="button" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal" aria-controls="futuristicMenuModal">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Version mobile du bouton hamburger -->
        <button class="navbar-toggler d-lg-none ms-auto main-menu-btn" type="button" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal" aria-controls="futuristicMenuModal">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>

<!-- Offcanvas legacy supprimé (remplacé par le modal futuriste) -->

