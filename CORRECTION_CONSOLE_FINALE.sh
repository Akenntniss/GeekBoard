#!/bin/bash
# Correction finale et complète de la console de debug

echo "🔧 CORRECTION FINALE DE LA CONSOLE DE DEBUG"
echo "=============================================="

# Fonction pour corriger un fichier JavaScript
fix_js_file() {
    local file="$1"
    if [[ -f "$file" ]]; then
        echo "🔧 Correction: $file"
        
        # Créer une sauvegarde
        cp "$file" "$file.backup.final.$(date +%Y%m%d_%H%M%S)"
        
        # Corriger les erreurs de syntaxe créées par le script précédent
        sed -i.tmp '
            # Corriger les lignes cassées par le nettoyage précédent
            /\/\/ Liste debug supprimée.*{/,/});/d
            /\/\/ Stats debug supprimées.*{/,/});/d
            /\/\/ Debug: .*{/,/});/d
            
            # Supprimer complètement tous les console.log restants
            /console\.log(/d
            /console\.error(/d
            /console\.warn(/d
            /console\.info(/d
            /console\.debug(/d
            
            # Supprimer les lignes de debug avec emojis
            /🚀\|🔧\|✅\|🔍\|💡\|🧪\|🎯\|🔄\|🚫\|📋\|📊\|🎨\|🛒\|🔗\|👤\|🚚/d
            
            # Supprimer les lignes vides multiples
            /^[[:space:]]*$/N;/^\n$/d
        ' "$file"
        
        # Supprimer le fichier temporaire
        rm -f "$file.tmp"
        
        echo "  ✓ Corrigé"
    fi
}

# Fonction pour corriger un fichier PHP
fix_php_file() {
    local file="$1"
    if [[ -f "$file" ]]; then
        echo "🔧 Correction PHP: $file"
        
        # Créer une sauvegarde
        cp "$file" "$file.backup.final.$(date +%Y%m%d_%H%M%S)"
        
        # Corriger les erreurs et supprimer tous les console.log
        sed -i.tmp '
            # Supprimer tous les console.log JavaScript dans PHP
            /console\.log(/d
            /console\.error(/d
            /console\.warn(/d
            /console\.info(/d
            
            # Supprimer les echo de console.log
            /echo.*console\.log/d
            
            # Corriger les lignes cassées
            /\/\/ Stats debug supprimées.*{/,/});/d
            /\/\/ Debug: .*{/,/});/d
            
            # Supprimer les lignes avec emojis de debug
            /🚀\|🔧\|✅\|🔍\|💡\|🧪\|🎯\|🔄\|🚫\|📋\|📊/d
        ' "$file"
        
        rm -f "$file.tmp"
        echo "  ✓ Corrigé"
    fi
}

echo ""
echo "📁 CORRECTION DES FICHIERS JAVASCRIPT..."
echo "----------------------------------------"

# Corriger les fichiers JS avec erreurs de syntaxe
fix_js_file "assets/js/modal-test-simple.js"
fix_js_file "assets/js/modal-main-fix.js"
fix_js_file "assets/js/modal-stacking-fix.js"
fix_js_file "assets/js/modal-sms-fix.js"
fix_js_file "assets/js/modal-quantity-debug.js"
fix_js_file "assets/js/scanner-diagnostic.js"
fix_js_file "assets/js/barcode-diagnostic.js"
fix_js_file "assets/js/barcode-test.js"
fix_js_file "assets/js/barcode-debug-real.js"
fix_js_file "assets/js/bootstrap-focus-fix.js"
fix_js_file "assets/js/scanner-enhancement.js"
fix_js_file "assets/js/modal-priority-manager-fixed.js"
fix_js_file "assets/js/modal-no-backdrop.js"
fix_js_file "assets/js/modal-commande-priority-fix.js"
fix_js_file "assets/js/css-debug.js"
fix_js_file "assets/js/modal-nouvelles-actions-fix.js"
fix_js_file "assets/js/modal-transitions.js"
fix_js_file "assets/js/modal-guard-fix.js"
fix_js_file "assets/js/modal-commande-debug.js"
fix_js_file "assets/js/modal-commande.js"
fix_js_file "assets/js/client-search-debug.js"
fix_js_file "assets/js/modal-deep-debug.js"
fix_js_file "assets/js/modal-commande-inject.js"
fix_js_file "assets/js/modal-debug.js"
fix_js_file "assets/js/barcode-scanner-fix.js"
fix_js_file "assets/js/simple-barcode-detector.js"
fix_js_file "assets/js/barcode-force-test.js"
fix_js_file "assets/js/real-barcode-decoder.js"
fix_js_file "components/futuristic-menu.js"

echo ""
echo "📄 CORRECTION DES FICHIERS PHP..."
echo "---------------------------------"

# Corriger les fichiers PHP
fix_php_file "includes/modals.php"
fix_php_file "modals.php"
fix_php_file "index.php"

echo ""
echo "🧹 NETTOYAGE SPÉCIFIQUE DES LOGS RESTANTS..."
echo "--------------------------------------------"

# Nettoyer spécifiquement les logs qui apparaissent encore
if [[ -f "includes/modals.php" ]]; then
    echo "🎯 Nettoyage spécifique includes/modals.php"
    sed -i.tmp2 '
        s/echo "console\.log.*taskModalUsersFromPHP.*;//g
        /🚀 Utilisateurs chargés/d
        /📊 Shop:/d
        /🔧 \[SCANNER\]/d
        /✅ \[SCANNER\]/d
    ' "includes/modals.php"
    rm -f "includes/modals.php.tmp2"
fi

if [[ -f "modals.php" ]]; then
    echo "🎯 Nettoyage spécifique modals.php"
    sed -i.tmp2 '
        /🚀 Utilisateurs chargés/d
        /📊 Shop:/d
        /🔧 \[SCANNER\]/d
        /✅ \[SCANNER\]/d
        /console\.log.*Scanner universel/d
    ' "modals.php"
    rm -f "modals.php.tmp2"
fi

echo ""
echo "✅ CORRECTION TERMINÉE!"
echo "======================"
echo ""
echo "📋 Sauvegardes créées avec extension .backup.final.YYYYMMDD_HHMMSS"
echo ""
echo "🚀 Pour déployer les corrections:"
echo "sshpass -p 'Mamanmaman01#' scp -r assets/js/ root@82.29.168.205:/var/www/mdgeek.top/assets/"
echo "sshpass -p 'Mamanmaman01#' scp includes/modals.php modals.php index.php root@82.29.168.205:/var/www/mdgeek.top/"
echo "sshpass -p 'Mamanmaman01#' ssh root@82.29.168.205 'chown -R www-data:www-data /var/www/mdgeek.top/'"
echo ""
echo "🎯 La console devrait maintenant être complètement propre!"
