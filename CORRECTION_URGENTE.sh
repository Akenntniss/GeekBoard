#!/bin/bash

echo "🚨 Correction urgente des erreurs de syntaxe JavaScript..."

# Fonction pour corriger les virgules manquantes dans les objets
fix_commas() {
    local file="$1"
    if [ -f "$file" ]; then
        echo "🔧 Correction: $file"
        
        # Corriger method: 'POST' sans virgule
        sed -i '' "s/method: 'POST'$/method: 'POST',/g" "$file"
        sed -i '' "s/method: 'GET'$/method: 'GET',/g" "$file"
        
        # Corriger headers sans virgule
        sed -i '' "s/'Content-Type': 'application\/json'$/'Content-Type': 'application\/json',/g" "$file"
        sed -i '' "s/'Content-Type': 'application\/x-www-form-urlencoded'$/'Content-Type': 'application\/x-www-form-urlencoded',/g" "$file"
        sed -i '' "s/'X-Requested-With': 'XMLHttpRequest'$/'X-Requested-With': 'XMLHttpRequest'/g" "$file"
        
        # Corriger credentials sans virgule
        sed -i '' "s/credentials: 'same-origin'$/credentials: 'same-origin',/g" "$file"
        
        # Corriger les objets avec propriétés sans virgules
        sed -i '' 's/\([a-zA-Z_][a-zA-Z0-9_]*\): \([^,}]*\)$/\1: \2,/g' "$file"
        
        # Supprimer les virgules en trop avant }
        sed -i '' 's/,\([[:space:]]*}\)/\1/g' "$file"
        
        # Corriger les parenthèses manquantes
        sed -i '' 's/^\([[:space:]]*\)}\)$/\1});/g' "$file"
        
        echo "✅ Corrigé: $file"
    fi
}

# Corriger les fichiers les plus critiques
fix_commas "assets/js/modal-commande.js"
fix_commas "assets/js/modal-quantity-debug.js"
fix_commas "assets/js/modal-nouvelles-actions-fix.js"
fix_commas "assets/js/modal-test-simple.js"
fix_commas "assets/js/modal-transitions.js"
fix_commas "assets/js/modal-guard-fix.js"

echo "🎉 Correction urgente terminée !"
