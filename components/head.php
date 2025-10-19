<!-- Meta tags requis -->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<!-- Style critique pour forcer l'affichage de la barre de navigation -->
<style id="critical-nav-styles">
    /* Styles pour Safari desktop uniquement */
    @media screen and (min-width: 992px) {
        #desktop-navbar {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: 55px !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1030 !important;
            background-color: white !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
        }
        
        body:not(.ipad-device):not(.mobile-device):not(.tablet-device) #mobile-dock {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        
        body:not(.ipad-device):not(.mobile-device):not(.tablet-device) {
            /* padding-top: 55px !important; */ /* Retiré pour éviter le décalage */
            padding-bottom: 0 !important;
        }
    }
    
    /* Styles pour mobile et iPad */
    @media screen and (max-width: 991px) {
        #mobile-dock {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1030 !important;
            background-color: white !important;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1) !important;
        }
        
        #desktop-navbar {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        
        body {
            padding-top: 0 !important;
            padding-bottom: 55px !important;
        }
    }
</style>

<!-- Bootstrap CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">

<!-- Styles personnalisés -->
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/modal-search.css">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">

<!-- Script Safari supprimé - navbar désormais gérée par navbar_clean.php uniquement -->

<title>GestiRep - <?php echo $page_title ?? 'Gestion de réparations'; ?></title> 
<link rel="icon" href="assets/images/logo/AppIcons_lightMode/logo.png">
<link rel="apple-touch-icon" href="assets/images/logo/AppIcons_lightMode/appstore.png">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#0078e8">

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" rel="stylesheet">

<!-- Animate.css -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

<!-- Navigation futuriste et styles modernes -->
<link href="assets/css/neo-dock.css" rel="stylesheet">

<!-- Styles personnalisés -->
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/modal-search.css">

<!-- Modal de recherche premium -->
<link rel="stylesheet" href="assets/css/recherche-modal-premium.css">

<!-- CORRECTION DEFINITIVE DES TABLEAUX -->
<link rel="stylesheet" href="assets/css/table-alignment-fix.css?v=<?php echo time(); ?>">

<!-- AMÉLIORATION DU BOUTON + AVEC MÊME STYLE QUE LE HAMBURGER -->
<link rel="stylesheet" href="assets/css/plus-button-improvements.css?v=<?php echo time(); ?>">

<!-- RESOLVEUR DE CONFLITS JAVASCRIPT - CHARGÉ EN PRIORITÉ -->
<script src="assets/js/conflict-resolver.js?v=<?php echo time(); ?>" defer></script>