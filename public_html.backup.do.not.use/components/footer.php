<?php
// Déterminer le bon chemin selon l'emplacement du fichier
$assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
?>
<!-- Scripts JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo $assets_path; ?>js/app.js"></script>
<script src="<?php echo $assets_path; ?>js/dark-mode.js"></script>
<script src="<?php echo $assets_path; ?>js/dock-effects.js"></script> 
<!-- Script de recherche universelle et compatibilité -->
<script src="<?php echo $assets_path; ?>js/recherche-universelle-new.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo $assets_path; ?>js/recherche-compatibility-fix.js?v=<?php echo time(); ?>"></script>
<!-- <script src="<?php echo $assets_path; ?>js/recherche-simple-v2.js?v=<?php echo time(); ?>"></script> -->