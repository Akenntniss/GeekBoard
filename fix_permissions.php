<?php
/**
 * Script de correction des permissions pour GeekBoard
 * À exécuter une seule fois sur le serveur pour corriger les problèmes de permissions
 */

echo "🔧 Script de correction des permissions GeekBoard\n";
echo "================================================\n\n";

// Dossiers à créer/corriger
$directories = [
    'assets/images/reparations',
    'assets/debug',
    'assets/temp',
    'assets/uploads'
];

// Créer et corriger les permissions des dossiers
foreach ($directories as $dir) {
    echo "📁 Traitement du dossier: $dir\n";
    
    if (!is_dir($dir)) {
        echo "  → Création du dossier...\n";
        if (mkdir($dir, 0755, true)) {
            echo "  ✅ Dossier créé avec succès\n";
        } else {
            echo "  ❌ Échec de création du dossier\n";
        }
    } else {
        echo "  ✅ Dossier existe déjà\n";
    }
    
    // Vérifier les permissions
    if (is_dir($dir)) {
        $perms = fileperms($dir);
        echo "  📋 Permissions actuelles: " . substr(sprintf('%o', $perms), -4) . "\n";
        
        if (is_writable($dir)) {
            echo "  ✅ Dossier accessible en écriture\n";
        } else {
            echo "  ⚠️  Dossier non accessible en écriture\n";
            echo "  → Tentative de correction des permissions...\n";
            if (chmod($dir, 0755)) {
                echo "  ✅ Permissions corrigées\n";
            } else {
                echo "  ❌ Impossible de corriger les permissions\n";
            }
        }
    }
    
    echo "\n";
}

// Créer un fichier de test dans chaque dossier
echo "🧪 Test d'écriture dans les dossiers:\n";
echo "=====================================\n\n";

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $test_file = $dir . '/test_' . uniqid() . '.txt';
        echo "📝 Test d'écriture: $test_file\n";
        
        if (file_put_contents($test_file, 'Test de permissions - ' . date('Y-m-d H:i:s'))) {
            echo "  ✅ Écriture réussie\n";
            // Nettoyer le fichier de test
            unlink($test_file);
            echo "  🗑️  Fichier de test supprimé\n";
        } else {
            echo "  ❌ Échec d'écriture\n";
        }
    }
    echo "\n";
}

echo "🎯 Résumé:\n";
echo "==========\n";
echo "Si tous les tests sont ✅, les permissions sont correctes.\n";
echo "Si des tests échouent ❌, contactez l'administrateur serveur.\n\n";

echo "💡 Commandes manuelles à exécuter si nécessaire:\n";
echo "sudo chown -R www-data:www-data /var/www/mdgeek.top/assets/\n";
echo "sudo chmod -R 755 /var/www/mdgeek.top/assets/\n\n";

echo "✅ Script terminé.\n";
?>
