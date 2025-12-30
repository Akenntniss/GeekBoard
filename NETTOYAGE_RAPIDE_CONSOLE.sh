#!/bin/bash
# Nettoyage rapide des console.log les plus fréquents

echo "🧹 Nettoyage rapide des console.log..."

# Fonction pour nettoyer un fichier
clean_logs() {
    local file="$1"
    if [[ -f "$file" ]]; then
        echo "🔧 Nettoyage: $file"
        
        # Créer une sauvegarde
        cp "$file" "$file.backup.$(date +%Y%m%d_%H%M%S)"
        
        # Nettoyer les logs spécifiques vus dans la console
        sed -i.tmp '
            # Logs avec emojis spécifiques
            s/console\.log('\''🔧 \[.*\] .*'\''/\/\/ Debug: Configuration supprimée/g
            s/console\.log('\''✅ \[.*\] .*'\''/\/\/ Debug: Succès supprimé/g
            s/console\.log('\''🔍 \[.*\] .*'\''/\/\/ Debug: Diagnostic supprimé/g
            s/console\.log('\''💡 \[.*\] .*'\''/\/\/ Debug: Info supprimée/g
            s/console\.log('\''🚀 \[.*\] .*'\''/\/\/ Debug: Initialisation supprimée/g
            s/console\.log('\''🎯 \[.*\] .*'\''/\/\/ Debug: Priorité supprimée/g
            s/console\.log('\''🔄 \[.*\] .*'\''/\/\/ Debug: Stacking supprimé/g
            s/console\.log('\''🚫 \[.*\] .*'\''/\/\/ Debug: No-backdrop supprimé/g
            s/console\.log('\''🧪 \[.*\] .*'\''/\/\/ Debug: Test supprimé/g
            
            # Logs de diagnostic spécifiques
            s/console\.log('\''🔍 \[DIAGNOSTIC\] .*'\''/\/\/ Diagnostic supprimé/g
            s/console\.log('\''🔍 \[BARCODE-DIAGNOSTIC\] .*'\''/\/\/ Barcode diagnostic supprimé/g
            s/console\.log('\''🧪 \[BARCODE-TEST\] .*'\''/\/\/ Barcode test supprimé/g
            s/console\.log('\''🔍 \[BARCODE-DEBUG-REAL\] .*'\''/\/\/ Barcode debug supprimé/g
            
            # Logs Bootstrap et Modal
            s/console\.log('\''🔧 \[BOOTSTRAP-FOCUS-FIX\] .*'\''/\/\/ Bootstrap fix supprimé/g
            s/console\.log('\''✅ Modal initialisé: .*'\''/\/\/ Modal init supprimé/g
            
            # Logs CSS et Debug
            s/console\.log('\''🎨 .*'\''/\/\/ CSS debug supprimé/g
            s/console\.log('\''📋 .*'\''/\/\/ Liste debug supprimée/g
            s/console\.log('\''📊 .*'\''/\/\/ Stats debug supprimées/g
            
            # Logs génériques
            s/console\.log('\''Mode desktop activé.*'\''/\/\/ Mode desktop détecté/g
            s/console\.log('\''🚀 Initialisation.*'\''/\/\/ Initialisation/g
            
        ' "$file"
        
        # Supprimer le fichier temporaire
        rm -f "$file.tmp"
        
        echo "  ✓ Nettoyé"
    fi
}

# Nettoyer les fichiers principaux identifiés
echo "📁 Nettoyage des fichiers critiques..."

# Fichiers JS avec beaucoup de logs
clean_logs "assets/js/modal-test-simple.js"
clean_logs "assets/js/modal-main-fix.js"
clean_logs "assets/js/modal-stacking-fix.js"
clean_logs "assets/js/modal-sms-fix.js"
clean_logs "assets/js/scanner-diagnostic.js"
clean_logs "assets/js/barcode-diagnostic.js"
clean_logs "assets/js/barcode-test.js"
clean_logs "assets/js/barcode-debug-real.js"
clean_logs "assets/js/bootstrap-focus-fix.js"
clean_logs "assets/js/scanner-enhancement.js"
clean_logs "assets/js/modal-quantity-debug.js"
clean_logs "assets/js/modal-priority-manager-fixed.js"
clean_logs "assets/js/modal-no-backdrop.js"
clean_logs "assets/js/modal-commande-priority-fix.js"
clean_logs "assets/js/css-debug.js"
clean_logs "components/futuristic-menu.js"

# Fichiers PHP avec JS intégré
clean_logs "includes/modals.php"
clean_logs "modals.php"
clean_logs "index.php"

echo ""
echo "✅ Nettoyage rapide terminé!"
echo "📋 Sauvegardes créées avec extension .backup.YYYYMMDD_HHMMSS"
echo ""
echo "🚀 Pour déployer:"
echo "sshpass -p 'Mamanmaman01#' scp -r assets/js/ root@82.29.168.205:/var/www/mdgeek.top/assets/"
echo "sshpass -p 'Mamanmaman01#' scp includes/modals.php modals.php index.php root@82.29.168.205:/var/www/mdgeek.top/"
echo "sshpass -p 'Mamanmaman01#' ssh root@82.29.168.205 'chown -R www-data:www-data /var/www/mdgeek.top/'"
