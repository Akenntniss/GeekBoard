<?php
/**
 * Script pour ajouter le case admin_timetracking au switch statement
 */

$index_file = '/var/www/mdgeek.top/index.php';

// Lire le contenu du fichier
$content = file_get_contents($index_file);

if (!$content) {
    die("Erreur: Impossible de lire index.php\n");
}

// Rechercher la section case 'presence_gestion' et ajouter admin_timetracking après
$pattern = "/(case 'presence_gestion':\s*include BASE_PATH \. '\/pages\/presence_gestion\.php';\s*break;)/";

$replacement = "$1
        case 'admin_timetracking':
            include BASE_PATH . '/pages/admin_timetracking.php';
            break;";

$new_content = preg_replace($pattern, $replacement, $content);

// Vérifier que la modification a été appliquée
if (strpos($new_content, "case 'admin_timetracking'") === false) {
    die("Erreur: Case admin_timetracking non ajouté\n");
}

// Sauvegarder seulement si différent
if ($new_content !== $content) {
    if (file_put_contents($index_file, $new_content)) {
        echo "✅ Case admin_timetracking ajouté avec succès au switch statement !\n";
    } else {
        echo "❌ Erreur lors de la sauvegarde\n";
    }
} else {
    echo "ℹ️ Case admin_timetracking déjà présent\n";
}
?>
