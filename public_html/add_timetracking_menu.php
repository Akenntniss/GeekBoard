<?php
/**
 * Script pour ajouter l'entrée "Gestion Pointage" au menu latéral
 * Ce script modifie le fichier de navigation pour ajouter le lien vers l'interface admin
 */

// Chemin vers le fichier de navigation (ajustez selon la structure)
$navigation_files = [
    '/var/www/mdgeek.top/includes/navigation.php',
    '/var/www/mdgeek.top/includes/navbar.php',
    '/var/www/mdgeek.top/components/sidebar.php',
    '/var/www/mdgeek.top/includes/modals.php' // Le menu est souvent dans les modals
];

// Code HTML à ajouter pour l'entrée de menu
$menu_entry_html = '
                    <!-- Gestion Pointage (Admin uniquement) -->
                    <?php if (isset($_SESSION[\'user_role\']) && $_SESSION[\'user_role\'] === \'admin\'): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo (strpos($_SERVER[\'SCRIPT_NAME\'], \'/pages/\') !== false) ? \'..\' : \'pages\'; ?>/admin_timetracking.php">
                            <i class="fas fa-clock me-2"></i> Gestion Pointage
                            <span class="badge bg-danger ms-2" id="active-users-count" style="display: none;">0</span>
                        </a>
                    </li>
                    <?php endif; ?>
';

// Code JavaScript pour mettre à jour le compteur d'utilisateurs actifs
$js_code = '
<script>
// Mettre à jour le compteur d\'utilisateurs actifs en pointage
function updateActiveUsersCount() {
    fetch(\'time_tracking_api.php?action=admin_get_active\')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.active_users) {
                const count = data.data.count;
                const badge = document.getElementById(\'active-users-count\');
                if (badge) {
                    badge.textContent = count;
                    badge.style.display = count > 0 ? \'inline-block\' : \'none\';
                }
            }
        })
        .catch(error => console.log(\'Erreur compteur pointage:\', error));
}

// Mettre à jour toutes les 30 secondes
document.addEventListener(\'DOMContentLoaded\', function() {
    updateActiveUsersCount();
    setInterval(updateActiveUsersCount, 30000);
});
</script>
';

echo "Script d'ajout du menu de pointage\n";
echo "=====================================\n\n";

// Instructions pour l'ajout manuel
echo "INSTRUCTIONS POUR AJOUTER LE MENU POINTAGE:\n\n";

echo "1. Ouvrir le fichier de navigation/menu latéral (probablement dans includes/navigation.php ou includes/modals.php)\n\n";

echo "2. Chercher la section des liens de menu admin et ajouter ce code HTML:\n";
echo "```html\n";
echo htmlspecialchars($menu_entry_html);
echo "\n```\n\n";

echo "3. Ajouter ce code JavaScript dans le footer ou à la fin du fichier:\n";
echo "```javascript\n";
echo htmlspecialchars($js_code);
echo "\n```\n\n";

echo "4. Exemples de placement typique:\n";
echo "   - Après les liens d'administration existants\n";
echo "   - Avant le lien de déconnexion\n";
echo "   - Dans la section 'Administration' du menu\n\n";

echo "5. Le menu sera visible uniquement pour les utilisateurs admin\n";
echo "6. Un badge rouge indiquera le nombre d'utilisateurs actuellement pointés\n\n";

// Créer un fichier de patch pour faciliter l'intégration
$patch_content = "
/* Patch pour ajouter le menu de gestion des pointages */

/* Insérer dans le menu latéral admin: */
$menu_entry_html

/* Insérer le JavaScript à la fin du fichier: */
$js_code
";

file_put_contents('timetracking_menu_patch.txt', $patch_content);
echo "7. Un fichier 'timetracking_menu_patch.txt' a été créé avec le code à copier.\n\n";

echo "ALTERNATIVE - Code d'injection automatique:\n";
echo "Si vous voulez injecter automatiquement, décommentez et adaptez le code ci-dessous:\n\n";

/*
// ATTENTION: Code d'injection automatique - à utiliser avec précaution
foreach ($navigation_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Chercher où insérer le menu (après un autre élément de menu admin)
        $patterns = [
            '/<li class="nav-item">\s*<a[^>]*admin[^>]*>.*?<\/a>\s*<\/li>/is',
            '/<li>\s*<a[^>]*admin[^>]*>.*?<\/a>\s*<\/li>/is'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                // Insérer après le dernier élément de menu admin trouvé
                $content = preg_replace($pattern, '$0' . $menu_entry_html, $content, 1);
                break;
            }
        }
        
        // Ajouter le JavaScript à la fin
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', $js_code . '</body>', $content);
        } else if (strpos($content, '</html>') !== false) {
            $content = str_replace('</html>', $js_code . '</html>', $content);
        } else {
            $content .= $js_code;
        }
        
        // Sauvegarder le fichier modifié
        file_put_contents($file . '.backup', file_get_contents($file)); // Backup
        file_put_contents($file, $content);
        echo "Fichier modifié: $file (backup créé)\n";
        break; // Ne modifier qu'un seul fichier
    }
}
*/

?>

STRUCTURE TYPIQUE D'UN MENU LATÉRAL GEEKBOARD:
==============================================

<!-- Menu latéral typique -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="mainMenuOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu Principal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav nav-pills flex-column">
            
            <!-- Section Administration (si admin) -->
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <li class="nav-item">
                <h6 class="nav-header">Administration</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="admin_users.php">
                    <i class="fas fa-users"></i> Gestion Utilisateurs
                </a>
            </li>
            
            <!-- INSÉRER LE MENU POINTAGE ICI -->
            <li class="nav-item">
                <a class="nav-link" href="index.php?page=admin_timetracking">
                    <i class="fas fa-clock"></i> Gestion Pointage
                    <span class="badge bg-danger ms-2" id="active-users-count" style="display: none;">0</span>
                </a>
            </li>
            <!-- FIN INSERTION -->
            
            <?php endif; ?>
        </ul>
    </div>
</div>

EMPLACEMENT EXACT:
==================
Le code doit être inséré dans le fichier qui contient le menu latéral,
généralement dans includes/modals.php ou includes/navigation.php,
dans la section des liens d'administration.

