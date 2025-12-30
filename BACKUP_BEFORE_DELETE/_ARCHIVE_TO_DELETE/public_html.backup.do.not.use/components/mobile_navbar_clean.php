<?php
// Barre de navigation mobile moderne - Version propre
// Détection du type d'appareil
$isMobile = preg_match('/(iPhone|iPod|Android|BlackBerry|IEMobile|Opera Mini)/i', $_SERVER['HTTP_USER_AGENT']);
$isIPad = preg_match('/(iPad)/i', $_SERVER['HTTP_USER_AGENT']) || 
          (preg_match('/(Macintosh)/i', $_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']));

// Récupérer la page courante
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Compter les tâches actives
$tasks_count = 0;
try {
    if (function_exists('getShopDBConnection')) {
        $shop_pdo = getShopDBConnection();
        if ($shop_pdo) {
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM taches WHERE statut IN ('en_cours', 'nouveau')");
            $result = $stmt->fetch();
            $tasks_count = $result['count'] ?? 0;
        }
    }
} catch (Exception $e) {
    // Ignorer les erreurs de comptage
}
?>

<!-- Inclure les styles et scripts de la barre moderne -->
<?php 
$assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
?>
<link rel="stylesheet" href="<?php echo $assets_path; ?>css/mobile-navbar-clean.css">
<link rel="stylesheet" href="<?php echo $assets_path; ?>css/mobile-dock-modern-buttons.css">
<script src="<?php echo $assets_path; ?>js/mobile-navbar-clean.js" defer></script>
<script src="<?php echo $assets_path; ?>js/mobile-dock-auto-hide.js" defer></script>

<!-- NAVBAR MOBILE MODERNE avec effet glassmorphism -->
<div id="mobile-dock-clean" class="d-block d-lg-none">
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
        <button class="dock-item plus-button btn-nouvelle-action" type="button" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal" aria-label="Nouvelle action">
            <div class="dock-icon-wrapper">
                <i class="fas fa-plus"></i>
            </div>
        </button>

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
        <a href="#" class="dock-item" data-bs-toggle="modal" data-bs-target="#menu_navigation_modal" aria-label="Menu principal">
            <div class="dock-icon-wrapper">
                <i class="fas fa-bars"></i>
            </div>
            <span>Menu</span>
        </a>
    </div>
</div>
