#!/bin/bash

echo "🔧 Correction massive des erreurs JavaScript..."

# Liste des fichiers avec erreurs de syntaxe
files=(
    "assets/js/modal-nouvelles-actions-fix.js"
    "assets/js/modal-test-simple.js"
    "assets/js/modal-transitions.js"
    "assets/js/modal-guard-fix.js"
    "assets/js/modal-commande-debug.js"
    "assets/js/client-search-debug.js"
    "assets/js/modal-main-fix.js"
    "assets/js/modal-stacking-fix.js"
    "assets/js/modal-sms-fix.js"
    "assets/js/modal-deep-debug.js"
    "assets/js/barcode-diagnostic.js"
    "assets/js/barcode-test.js"
    "assets/js/barcode-debug-real.js"
    "assets/js/bootstrap-focus-fix.js"
    "assets/js/scanner-enhancement.js"
    "assets/js/css-debug.js"
    "assets/js/modal-force-render.js"
    "assets/js/scanner-etiquette.js"
    "assets/js/recherche-avancee.js"
    "assets/js/dock-effects.js"
    "assets/js/bug-reporter-simple.js"
    "assets/js/commandes-details.js"
    "assets/js/modal-recherche-moderne.js"
    "assets/js/modal-priority-manager-fixed.js"
    "assets/js/modal-no-backdrop.js"
    "assets/js/modal-commande-priority-fix.js"
    "assets/js/fix-modal-dark-mode.js"
    "assets/js/pull-to-refresh.js"
)

# Fonction de correction pour chaque fichier
fix_js_file() {
    local file="$1"
    if [ -f "$file" ]; then
        echo "🔧 Correction: $file"
        
        # Créer une sauvegarde
        cp "$file" "$file.backup.$(date +%Y%m%d_%H%M%S)"
        
        # Corrections spécifiques aux erreurs JavaScript
        
        # 1. Corriger les objets avec propriétés manquant des virgules
        sed -i '' 's/\([a-zA-Z_][a-zA-Z0-9_]*\): \([^,}]*\)$/\1: \2,/g' "$file"
        
        # 2. Corriger les fetch() avec propriétés manquant des virgules
        sed -i '' "s/method: 'POST'$/method: 'POST',/g" "$file"
        sed -i '' "s/method: 'GET'$/method: 'GET',/g" "$file"
        sed -i '' "s/headers: {$/headers: {/g" "$file"
        sed -i '' "s/credentials: 'same-origin'$/credentials: 'same-origin',/g" "$file"
        
        # 3. Corriger les headers sans virgules
        sed -i '' "s/'Content-Type': 'application\/json'$/'Content-Type': 'application\/json',/g" "$file"
        sed -i '' "s/'Content-Type': 'application\/x-www-form-urlencoded'$/'Content-Type': 'application\/x-www-form-urlencoded',/g" "$file"
        
        # 4. Supprimer les virgules en trop avant les accolades fermantes
        sed -i '' 's/,\([[:space:]]*}\)/\1/g' "$file"
        sed -i '' 's/,\([[:space:]]*]\)/\1/g' "$file"
        
        # 5. Corriger les parenthèses manquantes dans les addEventListener
        sed -i '' 's/addEventListener(\([^)]*\)$/addEventListener(\1);/g' "$file"
        
        # 6. Corriger les console.log cassés
        sed -i '' 's/console\.log(\([^)]*\)$/console.log(\1);/g' "$file"
        
        # 7. Ajouter les accolades manquantes pour les if/else
        sed -i '' 's/if (\([^)]*\))$/if (\1) {/g' "$file"
        
        # 8. Corriger les objets JavaScript cassés
        sed -i '' '/^[[:space:]]*[a-zA-Z_][a-zA-Z0-9_]*: [^,{}]*$/{
            N
            s/\([a-zA-Z_][a-zA-Z0-9_]*: [^,{}]*\)\n\([[:space:]]*[a-zA-Z_][a-zA-Z0-9_]*:\)/\1,\n\2/
        }' "$file"
        
        echo "✅ Corrigé: $file"
    else
        echo "❌ Fichier non trouvé: $file"
    fi
}

# Corriger tous les fichiers
for file in "${files[@]}"; do
    fix_js_file "$file"
done

echo "🎉 Correction massive terminée !"
echo "📋 Fichiers corrigés: ${#files[@]}"
