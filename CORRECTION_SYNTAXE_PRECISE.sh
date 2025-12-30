#!/bin/bash

# Script de correction précise des erreurs de syntaxe JavaScript
echo "🔧 Correction précise des erreurs de syntaxe JavaScript..."

# Fonction pour corriger un fichier spécifique
fix_file() {
    local file="$1"
    if [ -f "$file" ]; then
        echo "🔍 Correction: $file"
        
        # Corriger les objets JavaScript cassés (virgules manquantes)
        sed -i '' 's/\([a-zA-Z_][a-zA-Z0-9_]*\): \([^,}]*\)$/\1: \2,/g' "$file"
        
        # Corriger les accolades fermantes manquantes
        sed -i '' 's/^\([[:space:]]*\)\([a-zA-Z_][a-zA-Z0-9_]*\): \([^,}]*\)$/\1\2: \3/g' "$file"
        
        # Ajouter les virgules manquantes dans les objets
        sed -i '' '/^[[:space:]]*[a-zA-Z_][a-zA-Z0-9_]*: [^,}]*$/{
            N
            s/\([a-zA-Z_][a-zA-Z0-9_]*: [^,}]*\)\n\([[:space:]]*[a-zA-Z_][a-zA-Z0-9_]*:\)/\1,\n\2/
        }' "$file"
        
        # Corriger les parenthèses fermantes manquantes
        sed -i '' 's/^\([[:space:]]*\)}\)$/\1});/g' "$file"
        
        # Corriger les fetch() cassés
        sed -i '' "s/method: 'POST'$/method: 'POST',/g" "$file"
        sed -i '' "s/method: 'GET'$/method: 'GET',/g" "$file"
        sed -i '' "s/'Content-Type': 'application\/json'$/'Content-Type': 'application\/json',/g" "$file"
        sed -i '' "s/'Content-Type': 'application\/x-www-form-urlencoded'$/'Content-Type': 'application\/x-www-form-urlencoded',/g" "$file"
        
        echo "✅ Corrigé: $file"
    fi
}

# Corriger les fichiers spécifiquement mentionnés dans les erreurs
fix_file "assets/js/modal-nouvelles-actions-fix.js"
fix_file "assets/js/modal-test-simple.js"
fix_file "assets/js/modal-transitions.js"
fix_file "assets/js/modal-guard-fix.js"
fix_file "assets/js/modal-commande-debug.js"
fix_file "assets/js/modal-commande.js"
fix_file "assets/js/client-search-debug.js"
fix_file "assets/js/modal-main-fix.js"
fix_file "assets/js/modal-stacking-fix.js"
fix_file "assets/js/modal-sms-fix.js"
fix_file "assets/js/modal-deep-debug.js"
fix_file "assets/js/barcode-diagnostic.js"
fix_file "assets/js/barcode-test.js"
fix_file "assets/js/barcode-debug-real.js"
fix_file "assets/js/bootstrap-focus-fix.js"
fix_file "assets/js/scanner-enhancement.js"
fix_file "assets/js/modal-quantity-debug.js"
fix_file "assets/js/css-debug.js"
fix_file "assets/js/modal-force-render.js"
fix_file "assets/js/scanner-etiquette.js"
fix_file "assets/js/recherche-avancee.js"
fix_file "assets/js/dock-effects.js"
fix_file "assets/js/bug-reporter-simple.js"
fix_file "assets/js/commandes-details.js"
fix_file "assets/js/modal-recherche-moderne.js"
fix_file "assets/js/modal-priority-manager-fixed.js"
fix_file "assets/js/modal-no-backdrop.js"
fix_file "assets/js/modal-commande-priority-fix.js"
fix_file "assets/js/fix-modal-dark-mode.js"
fix_file "assets/js/pull-to-refresh.js"

echo "🎉 Correction précise terminée !"
