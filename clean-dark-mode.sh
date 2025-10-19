#!/bin/bash

# Script pour nettoyer tous les anciens CSS du mode nuit
# et éviter les conflits avec le nouveau thème V2

echo "🧹 Nettoyage des anciens CSS du mode nuit..."

# Dossier des CSS
CSS_DIR="/Users/admin/Documents/GeekBoard/assets/css"

# Fichiers à nettoyer complètement (supprimer tout le contenu mode nuit)
CRITICAL_FILES=(
    "modern-effects.css"
    "dashboard-ultra-modern.css"
    "dashboard-special-effects.css"
    "dashboard-background-fix.css"
    "dashboard-subtle-animations.css"
    "action-buttons-enhanced.css"
    "futuristic-interface.css"
    "modal-nouvelles-actions-enhanced.css"
    "dashboard-optimized.css"
    "dashboard-simple-animations.css"
)

# Fonction pour supprimer les blocs @media (prefers-color-scheme: dark)
clean_dark_mode_css() {
    local file="$1"
    echo "  🔧 Nettoyage de $file..."
    
    # Créer une sauvegarde
    cp "$file" "$file.backup"
    
    # Supprimer tous les blocs @media (prefers-color-scheme: dark) avec leur contenu
    # Utiliser perl pour une suppression plus précise des blocs
    perl -i -pe '
        BEGIN { $in_dark_block = 0; $brace_count = 0; }
        
        # Détecter le début d un bloc dark mode
        if (/@media\s*\(\s*prefers-color-scheme\s*:\s*dark\s*\)/) {
            $in_dark_block = 1;
            $brace_count = 0;
            $_ = "";
            next;
        }
        
        # Si on est dans un bloc dark mode
        if ($in_dark_block) {
            # Compter les accolades
            $brace_count += tr/{/{/;
            $brace_count -= tr/}/}/;
            
            # Si on ferme le bloc principal
            if ($brace_count <= 0) {
                $in_dark_block = 0;
            }
            
            # Supprimer la ligne
            $_ = "";
        }
    ' "$file"
    
    # Supprimer aussi les sélecteurs .dark-mode et body.dark-mode
    sed -i '' '/\.dark-mode/,/^[[:space:]]*}[[:space:]]*$/d' "$file"
    sed -i '' '/body\.dark-mode/,/^[[:space:]]*}[[:space:]]*$/d' "$file"
    
    # Nettoyer les lignes vides multiples
    sed -i '' '/^[[:space:]]*$/N;/^\n$/d' "$file"
    
    echo "    ✅ $file nettoyé"
}

# Nettoyer les fichiers critiques
for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$CSS_DIR/$file" ]; then
        clean_dark_mode_css "$CSS_DIR/$file"
    else
        echo "  ⚠️  Fichier non trouvé: $file"
    fi
done

echo ""
echo "🎯 Nettoyage des autres fichiers CSS..."

# Nettoyer tous les autres fichiers CSS qui contiennent du mode nuit
find "$CSS_DIR" -name "*.css" -type f | while read -r file; do
    # Vérifier si le fichier contient du code mode nuit
    if grep -q "prefers-color-scheme.*dark\|\.dark-mode\|body\.dark-mode" "$file"; then
        filename=$(basename "$file")
        
        # Ignorer les fichiers déjà traités et le nouveau thème
        if [[ ! " ${CRITICAL_FILES[@]} " =~ " ${filename} " ]] && [[ "$filename" != "homepage-dark-theme-v2.css" ]]; then
            clean_dark_mode_css "$file"
        fi
    fi
done

echo ""
echo "✅ Nettoyage terminé !"
echo "📋 Les sauvegardes sont disponibles avec l'extension .backup"
echo "🌙 Le nouveau thème V2 devrait maintenant fonctionner sans conflits"

