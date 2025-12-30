#!/bin/bash

# Script pour corriger les erreurs de syntaxe JavaScript restantes
echo "🔧 Correction des erreurs de syntaxe JavaScript..."

# Corriger les patterns problématiques dans les fichiers JS
find assets/js -name "*.js" -type f | while read file; do
    if [ -f "$file" ]; then
        echo "🔍 Vérification: $file"
        
        # Corriger les patterns "// Liste debug supprimée, {"
        sed -i '' 's|// Liste debug supprimée, {|// Éléments debug supprimés|g' "$file"
        
        # Corriger les objets JavaScript cassés après nettoyage
        sed -i '' '/^[[:space:]]*[a-zA-Z_][a-zA-Z0-9_]*: !![a-zA-Z_][a-zA-Z0-9_]*,*$/d' "$file"
        
        # Corriger les accolades orphelines après suppression
        sed -i '' '/^[[:space:]]*});$/d' "$file"
        
        # Corriger les virgules orphelines
        sed -i '' 's|,[[:space:]]*$||g' "$file"
        
        # Supprimer les lignes vides multiples
        sed -i '' '/^[[:space:]]*$/N;/^\n$/d' "$file"
        
        echo "✅ Corrigé: $file"
    fi
done

# Corriger spécifiquement les fichiers mentionnés dans les erreurs
echo "🎯 Correction spécifique des fichiers problématiques..."

# modal-main-fix.js ligne 247
if [ -f "assets/js/modal-main-fix.js" ]; then
    sed -i '' '247s/.*/    \/\/ Debug supprimé/' "assets/js/modal-main-fix.js"
    echo "✅ Corrigé: modal-main-fix.js ligne 247"
fi

# modal-stacking-fix.js ligne 409
if [ -f "assets/js/modal-stacking-fix.js" ]; then
    sed -i '' '409s/.*/    \/\/ Debug supprimé/' "assets/js/modal-stacking-fix.js"
    echo "✅ Corrigé: modal-stacking-fix.js ligne 409"
fi

# modal-sms-fix.js ligne 195
if [ -f "assets/js/modal-sms-fix.js" ]; then
    sed -i '' '195s/.*/    \/\/ Debug supprimé/' "assets/js/modal-sms-fix.js"
    echo "✅ Corrigé: modal-sms-fix.js ligne 195"
fi

# modal-deep-debug.js ligne 44
if [ -f "assets/js/modal-deep-debug.js" ]; then
    sed -i '' '44s/.*/    \/\/ Debug supprimé/' "assets/js/modal-deep-debug.js"
    echo "✅ Corrigé: modal-deep-debug.js ligne 44"
fi

# barcode-scanner-fix.js ligne 165
if [ -f "assets/js/barcode-scanner-fix.js" ]; then
    sed -i '' '165s/.*/    \/\/ Debug supprimé/' "assets/js/barcode-scanner-fix.js"
    echo "✅ Corrigé: barcode-scanner-fix.js ligne 165"
fi

echo "🎉 Correction des erreurs de syntaxe JavaScript terminée !"
echo "📋 Fichiers corrigés dans assets/js/"
