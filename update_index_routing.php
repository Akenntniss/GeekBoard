<?php
/**
 * Script pour ajouter admin_timetracking au routing de GeekBoard
 */

$index_file = '/var/www/mdgeek.top/index.php';

// Lire le contenu du fichier
$content = file_get_contents($index_file);

if (!$content) {
    die("Erreur: Impossible de lire index.php\n");
}

// 1. Ajouter admin_timetracking à la liste des pages autorisées
$old_allowed = "'admin_notifications', 'retours'";
$new_allowed = "'admin_notifications', 'admin_timetracking', 'retours'";

$content = str_replace($old_allowed, $new_allowed, $content);

// 2. Trouver où insérer le case pour admin_timetracking
// Je vais l'ajouter après presence_form
$switch_pattern = "/case 'presence_form':\s*include BASE_PATH \. '\/pages\/presence_form\.php';\s*break;/";

$new_case = "case 'presence_form':
            include BASE_PATH . '/pages/presence_form.php';
            break;
        case 'admin_timetracking':
            include BASE_PATH . '/pages/admin_timetracking.php';
            break;";

$content = preg_replace($switch_pattern, $new_case, $content);

// Vérifier que les modifications ont été appliquées
if (strpos($content, 'admin_timetracking') === false) {
    die("Erreur: Modifications non appliquées\n");
}

// Sauvegarder
if (file_put_contents($index_file, $content)) {
    echo "✅ index.php modifié avec succès !\n";
    echo "- admin_timetracking ajouté aux pages autorisées\n";
    echo "- Case ajouté au switch statement\n";
} else {
    echo "❌ Erreur lors de la sauvegarde\n";
}
?>
