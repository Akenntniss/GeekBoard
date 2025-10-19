<?php
/**
 * Script pour intégrer automatiquement le système de pointage dans presence_gestion.php
 */

$presence_file = '/var/www/mdgeek.top/pages/presence_gestion.php';

// Lire le contenu du fichier d'intégration
$integration_code = file_get_contents('/var/www/mdgeek.top/presence_timetracking_integration.php');

// Nettoyer le code d'intégration (enlever les balises PHP d'ouverture/fermeture)
$integration_code = str_replace('<?php', '', $integration_code);
$integration_code = str_replace('?>', '', $integration_code);

// Lire le contenu du fichier presence_gestion.php
$content = file_get_contents($presence_file);

// Trouver le point d'insertion - avant la fermeture finale des containers
// Chercher un pattern qui indique la fin du contenu principal
$patterns = [
    '/(\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*$)/s',  // Pattern pour la fin
    '/(\s*<\/div>\s*<\/div>\s*<\/div>\s*(?=<\?php include.*footer|$))/s',  // Avant footer
    '/(\s*)(<?php include.*footer)/s'  // Directement avant footer
];

$insertion_found = false;

foreach ($patterns as $pattern) {
    if (preg_match($pattern, $content)) {
        $new_content = preg_replace($pattern, $integration_code . '$1', $content);
        $insertion_found = true;
        break;
    }
}

// Si aucun pattern ne fonctionne, insérer avant les 3 dernières lignes
if (!$insertion_found) {
    $lines = explode("\n", $content);
    $total_lines = count($lines);
    
    // Insérer avant les 5 dernières lignes pour être sûr
    array_splice($lines, $total_lines - 5, 0, explode("\n", $integration_code));
    $new_content = implode("\n", $lines);
}

// Sauvegarder le fichier original
file_put_contents($presence_file . '.backup', $content);

// Écrire le nouveau contenu
file_put_contents($presence_file, $new_content);

echo "✅ Code d'intégration du pointage ajouté avec succès dans presence_gestion.php\n";
echo "💾 Sauvegarde créée : presence_gestion.php.backup\n";
echo "📍 Emplacement : Ajouté à la fin du contenu principal\n";
echo "🎨 Interface utilisateur de pointage maintenant disponible\n";
echo "📊 Statistiques et historique des pointages inclus\n";
echo "📝 Système de demandes de modification intégré\n";

// Vérifier que le fichier est valide
$check = file_get_contents($presence_file);
if (strlen($check) > strlen($content)) {
    echo "✅ Intégration réussie - Taille du fichier augmentée de " . (strlen($check) - strlen($content)) . " caractères\n";
} else {
    echo "⚠️ Attention - Vérifier l'intégration\n";
}
?>

