<?php
// Navbar minimaliste unifiée (sans dock, sans scripts annexes)
?>
<nav class="navbar navbar-light bg-white border-bottom shadow-sm py-2" style="position:fixed;top:0;left:0;right:0;z-index:1030;">
    <div class="container-fluid px-3 d-flex align-items-center justify-content-between">
        <a class="navbar-brand d-flex align-items-center" href="/index.php">
            <img src="<?php echo (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/'; ?>images/logo/AppIcons_lightMode/appstore.png" alt="MDGeek" height="32">
            <span class="ms-2 fw-semibold">MD Geek</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal" title="Nouvelle action">
                <i class="fas fa-plus"></i>
            </button>
            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#futuristicMenuModal" title="Menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</nav>
<div style="height:56px"></div>


