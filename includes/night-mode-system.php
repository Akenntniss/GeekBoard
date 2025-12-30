<?php
/* ====================================================================
   🌙 SYSTÈME UNIFIÉ DE MODE NUIT - GEEKBOARD
   Template d'inclusion pour toutes les pages
==================================================================== */

// Vérifier si l'utilisateur est connecté pour personnaliser le thème
$current_user_id = $_SESSION['user_id'] ?? null;
?>

<!-- Système unifié de mode nuit -->
<?php 
// S'assurer que $assets_path est défini
if (!isset($assets_path)) {
    $assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
}
?>
<link rel="stylesheet" href="<?php echo $assets_path; ?>css/unified-night-mode.css?v=<?php echo time(); ?>">
<script>
    // Injecter l'ID utilisateur pour le JavaScript
    <?php if ($current_user_id): ?>
    window.current_user_id = <?php echo json_encode($current_user_id); ?>;
    window.php_user_id = <?php echo json_encode($current_user_id); ?>;
    <?php endif; ?>
</script>
<script src="<?php echo $assets_path; ?>js/unified-night-mode.js?v=<?php echo time(); ?>"></script>

<style>
/* Styles critiques pour éviter le flash */
html, body {
    transition: background-color 0.3s ease, color 0.3s ease !important;
}

/* Application immédiate basée sur les préférences système */
@keyframes gradientFlowNightSystem {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@media (prefers-color-scheme: dark) {
    html, body {
        background: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483) !important;
        background-size: 400% 400% !important;
        animation: gradientFlowNightSystem 15s ease infinite !important;
        color: #f8fafc !important;
    }
}

/* Éviter le flash de contenu non stylé */
.no-flash {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.no-flash.loaded {
    opacity: 1;
}
</style>

<script>
// Script critique pour éviter le flash - exécuté immédiatement
(function() {
    'use strict';
    
    // Appliquer immédiatement les classes si nécessaire
    function applyImmediateTheme() {
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const stored = localStorage.getItem('geekboard_theme<?php echo $current_user_id ? "_user_" . $current_user_id : ""; ?>');
        
        const shouldApplyDark = stored === 'dark' || (stored === null && prefersDark);
        
        if (shouldApplyDark) {
            document.documentElement.classList.add('night-mode');
            if (document.body) {
                document.body.classList.add('night-mode');
            }
        }
    }
    
    // Appliquer immédiatement
    applyImmediateTheme();
    
    // Retirer la classe no-flash une fois le DOM chargé
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.no-flash').forEach(function(el) {
            el.classList.add('loaded');
        });
    });
})();
</script>

