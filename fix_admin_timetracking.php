<?php
/**
 * Script pour corriger les chemins d'include dans admin_timetracking.php
 */

$file_path = '/var/www/mdgeek.top/pages/admin_timetracking.php';
$content = file_get_contents($file_path);

// Corrections des chemins d'includes
$replacements = [
    "__DIR__ . '/config/database.php'" => "__DIR__ . '/../config/database.php'",
    "__DIR__ . '/includes/functions.php'" => "__DIR__ . '/../includes/functions.php'",
    "require_once __DIR__ . '/config/database.php';" => "require_once __DIR__ . '/../config/database.php';",
    "require_once __DIR__ . '/includes/functions.php';" => "require_once __DIR__ . '/../includes/functions.php';",
    "include 'includes/header.php';" => "include '../includes/header.php';",
    "include 'includes/navbar.php';" => "include '../includes/navbar.php';", 
    "include 'includes/footer.php';" => "include '../includes/footer.php';",
    "'includes/header.php'" => "'../includes/header.php'",
    "'includes/navbar.php'" => "'../includes/navbar.php'",
    "'includes/footer.php'" => "'../includes/footer.php'"
];

foreach ($replacements as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

// Sauvegarder le fichier corrigé
file_put_contents($file_path, $content);

echo "✅ Chemins d'includes corrigés dans admin_timetracking.php\n";
echo "📁 Corrections appliquées :\n";
foreach ($replacements as $search => $replace) {
    echo "   - $search → $replace\n";
}
?>
