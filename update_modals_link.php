<?php
/**
 * Script pour corriger le lien admin_timetracking dans modals.php
 */

$modals_file = '/var/www/mdgeek.top/includes/modals.php';

// Lire le contenu du fichier
$content = file_get_contents($modals_file);

if (!$content) {
    die("Erreur: Impossible de lire modals.php\n");
}

// Remplacer le lien direct par le routing GeekBoard
$old_link = 'href="pages/admin_timetracking.php"';
$new_link = 'href="index.php?page=admin_timetracking"';

$content = str_replace($old_link, $new_link, $content);

// Vérifier que la modification a été appliquée
if (strpos($content, 'index.php?page=admin_timetracking') === false) {
    die("Erreur: Modification du lien non appliquée\n");
}

// Sauvegarder
if (file_put_contents($modals_file, $content)) {
    echo "✅ modals.php modifié avec succès !\n";
    echo "- Lien corrigé vers index.php?page=admin_timetracking\n";
} else {
    echo "❌ Erreur lors de la sauvegarde de modals.php\n";
}
?>
