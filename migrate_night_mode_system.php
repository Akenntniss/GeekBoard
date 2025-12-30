<?php
/* ====================================================================
   🔄 SCRIPT DE MIGRATION - SYSTÈME UNIFIÉ DE MODE NUIT
   Met à jour toutes les pages pour utiliser le nouveau système
==================================================================== */

echo "🌙 Migration du système de mode nuit GeekBoard\n";
echo "===============================================\n\n";

// Configuration
$pages_directory = __DIR__ . '/pages';
$backup_directory = __DIR__ . '/pages_backup_night_mode_' . date('Y-m-d_H-i-s');

// Créer le dossier de sauvegarde
if (!file_exists($backup_directory)) {
    mkdir($backup_directory, 0755, true);
    echo "✅ Dossier de sauvegarde créé: $backup_directory\n";
}

// Patterns à rechercher et remplacer
$patterns_to_remove = [
    // Anciens scripts de mode nuit
    '/<script[^>]*futuristic-night-mode\.js[^>]*><\/script>/i',
    '/<script[^>]*dark-mode-auto\.js[^>]*><\/script>/i',
    '/<script[^>]*night-mode[^>]*\.js[^>]*><\/script>/i',
    
    // Anciens CSS de mode nuit
    '/<link[^>]*futuristic-night-mode\.css[^>]*>/i',
    '/<link[^>]*dark-mode-auto\.css[^>]*>/i',
    '/<link[^>]*night-mode[^>]*\.css[^>]*>/i',
    '/<link[^>]*dark-theme\.css[^>]*>/i',
    '/<link[^>]*homepage-night-mode[^>]*\.css[^>]*>/i',
    
    // Anciens scripts inline de détection
    '/\/\/ Fonction de détection automatique du mode nuit.*?(?=<\/script>|$)/s',
    '/function detectAndApplyDarkMode\(\).*?(?=<\/script>|function [a-zA-Z]|$)/s',
    '/function initTheme\(\).*?(?=<\/script>|function [a-zA-Z]|$)/s',
    '/function setupThemeListener\(\).*?(?=<\/script>|function [a-zA-Z]|$)/s',
];

$include_pattern = '<?php include_once \'includes/night-mode-system.php\'; ?>';

// Fonction pour nettoyer le contenu
function cleanNightModeCode($content) {
    global $patterns_to_remove;
    
    foreach ($patterns_to_remove as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }
    
    // Nettoyer les lignes vides multiples
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
    
    return $content;
}

// Fonction pour ajouter le nouveau système
function addUnifiedNightMode($content) {
    global $include_pattern;
    
    // Chercher la balise </head>
    if (preg_match('/<\/head>/i', $content)) {
        $content = preg_replace(
            '/<\/head>/i',
            "    $include_pattern\n</head>",
            $content
        );
    }
    // Si pas de </head>, chercher après les includes existants
    elseif (preg_match('/include.*?header.*?;/i', $content)) {
        $content = preg_replace(
            '/(include.*?header.*?;)/i',
            "$1\n$include_pattern",
            $content
        );
    }
    // Sinon, ajouter au début après <?php
    else {
        $content = preg_replace(
            '/(<\?php.*?\n)/s',
            "$1$include_pattern\n",
            $content
        );
    }
    
    return $content;
}

// Scanner tous les fichiers PHP
$files = glob($pages_directory . '/*.php');
$processed = 0;
$errors = 0;

echo "📁 Traitement de " . count($files) . " fichiers...\n\n";

foreach ($files as $file) {
    $filename = basename($file);
    
    try {
        // Lire le contenu
        $content = file_get_contents($file);
        
        if ($content === false) {
            echo "❌ Erreur lecture: $filename\n";
            $errors++;
            continue;
        }
        
        // Sauvegarder l'original
        $backup_file = $backup_directory . '/' . $filename;
        file_put_contents($backup_file, $content);
        
        // Vérifier si le fichier contient déjà le nouveau système
        if (strpos($content, 'night-mode-system.php') !== false) {
            echo "⏭️  Déjà migré: $filename\n";
            continue;
        }
        
        // Nettoyer l'ancien code
        $cleaned_content = cleanNightModeCode($content);
        
        // Ajouter le nouveau système
        $updated_content = addUnifiedNightMode($cleaned_content);
        
        // Sauvegarder le fichier mis à jour
        if (file_put_contents($file, $updated_content) !== false) {
            echo "✅ Migré: $filename\n";
            $processed++;
        } else {
            echo "❌ Erreur écriture: $filename\n";
            $errors++;
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur $filename: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n===============================================\n";
echo "🎯 Résumé de la migration:\n";
echo "   • Fichiers traités: $processed\n";
echo "   • Erreurs: $errors\n";
echo "   • Sauvegarde: $backup_directory\n";

if ($errors === 0) {
    echo "\n✅ Migration terminée avec succès!\n";
    echo "🔄 Redémarrez votre serveur web pour appliquer les changements.\n";
} else {
    echo "\n⚠️  Migration terminée avec des erreurs.\n";
    echo "📋 Vérifiez les fichiers mentionnés ci-dessus.\n";
}

echo "\n🌙 Le nouveau système unifié de mode nuit est maintenant actif!\n";
echo "📱 Il s'adapte automatiquement aux préférences système de l'utilisateur.\n";
echo "💾 Les préférences sont sauvegardées par utilisateur.\n";
echo "🎨 Tous les éléments sont maintenant couverts par les styles unifiés.\n";

// Créer un fichier de rapport
$report_file = __DIR__ . '/night_mode_migration_report.txt';
$report_content = "Migration du système de mode nuit - " . date('Y-m-d H:i:s') . "\n";
$report_content .= "Fichiers traités: $processed\n";
$report_content .= "Erreurs: $errors\n";
$report_content .= "Sauvegarde: $backup_directory\n";

file_put_contents($report_file, $report_content);
echo "\n📄 Rapport sauvegardé: $report_file\n";
?>
