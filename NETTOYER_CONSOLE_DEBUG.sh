#!/bin/bash
# Script pour nettoyer tous les console.log de debug dans GeekBoard

# Couleurs pour l'affichage
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "═══════════════════════════════════════════════════════════════"
echo -e "${BLUE}🧹 NETTOYAGE DES CONSOLE.LOG DE DEBUG${NC}"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Compteurs
TOTAL_FILES=0
CLEANED_FILES=0
TOTAL_LOGS_REMOVED=0

# Fonction pour nettoyer un fichier
clean_file() {
    local file="$1"
    local file_type="$2"
    
    if [[ ! -f "$file" ]]; then
        return 0
    fi
    
    # Compter les console.log avant nettoyage
    local logs_before=$(grep -c "console\.log\|console\.error\|console\.warn\|console\.debug\|console\.info" "$file" 2>/dev/null || echo "0")
    
    if [[ $logs_before -eq 0 ]]; then
        return 0
    fi
    
    echo -e "${YELLOW}🔧${NC} Nettoyage: $(basename "$file") ($logs_before logs)"
    
    # Créer une sauvegarde
    cp "$file" "$file.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Nettoyer les différents types de console.log
    # Remplacer par des commentaires pour garder une trace
    sed -i.tmp '
        # Console.log avec emoji et texte
        s/console\.log('\''🎯[^'\'']*'\''/\/\/ Debug: Filtrage par carte/g
        s/console\.log('\''👥[^'\'']*'\''/\/\/ Debug: Affichage activités employés/g
        s/console\.log('\''🌙[^'\'']*'\''/\/\/ Debug: Mode nuit activé/g
        s/console\.log('\''☀️[^'\'']*'\''/\/\/ Debug: Mode jour activé/g
        s/console\.log('\''🔄[^'\'']*'\''/\/\/ Debug: Réinitialisation filtres/g
        s/console\.log('\''✅[^'\'']*'\''/\/\/ Debug: Opération réussie/g
        s/console\.log('\''🚀[^'\'']*'\''/\/\/ Debug: Initialisation/g
        s/console\.log('\''🔧[^'\'']*'\''/\/\/ Debug: Configuration/g
        s/console\.log('\''🔍[^'\'']*'\''/\/\/ Debug: Recherche/g
        s/console\.log('\''💡[^'\'']*'\''/\/\/ Debug: Information/g
        s/console\.log('\''⚠️[^'\'']*'\''/\/\/ Debug: Avertissement/g
        s/console\.log('\''❌[^'\'']*'\''/\/\/ Debug: Erreur/g
        s/console\.log('\''📋[^'\'']*'\''/\/\/ Debug: Liste/g
        s/console\.log('\''📊[^'\'']*'\''/\/\/ Debug: Statistiques/g
        
        # Console.log simples
        s/console\.log([^;]*);/\/\/ Debug: Log supprimé/g
        s/console\.error([^;]*);/\/\/ Debug: Erreur supprimée/g
        s/console\.warn([^;]*);/\/\/ Debug: Avertissement supprimé/g
        s/console\.debug([^;]*);/\/\/ Debug: Debug supprimé/g
        s/console\.info([^;]*);/\/\/ Debug: Info supprimée/g
        
        # Console.log multilignes
        /console\.(log|error|warn|debug|info)(/d
        /^\s*console\./d
    ' "$file"
    
    # Supprimer le fichier temporaire
    rm -f "$file.tmp"
    
    # Compter les logs après nettoyage
    local logs_after=$(grep -c "console\.log\|console\.error\|console\.warn\|console\.debug\|console\.info" "$file" 2>/dev/null || echo "0")
    local logs_removed=$((logs_before - logs_after))
    
    if [[ $logs_removed -gt 0 ]]; then
        echo -e "${GREEN}  ✓${NC} $logs_removed logs supprimés"
        CLEANED_FILES=$((CLEANED_FILES + 1))
        TOTAL_LOGS_REMOVED=$((TOTAL_LOGS_REMOVED + logs_removed))
    else
        echo -e "${YELLOW}  ≈${NC} Aucun log standard trouvé"
    fi
    
    TOTAL_FILES=$((TOTAL_FILES + 1))
}

echo -e "${YELLOW}📁 Nettoyage des fichiers JavaScript principaux...${NC}"
echo ""

# Nettoyer les fichiers JavaScript principaux
echo "───────────────────────────────────────────────────────────────"
echo -e "${BLUE}🎯 Fichiers Assets JS${NC}"
echo "───────────────────────────────────────────────────────────────"

# Assets JS les plus importants
JS_FILES=(
    "assets/js/modal-test-simple.js"
    "assets/js/modal-main-fix.js"
    "assets/js/modal-stacking-fix.js"
    "assets/js/modal-sms-fix.js"
    "assets/js/scanner-diagnostic.js"
    "assets/js/barcode-diagnostic.js"
    "assets/js/barcode-test.js"
    "assets/js/barcode-debug-real.js"
    "assets/js/bootstrap-focus-fix.js"
    "assets/js/scanner-enhancement.js"
    "assets/js/modal-quantity-debug.js"
    "assets/js/modal-priority-manager-fixed.js"
    "assets/js/modal-no-backdrop.js"
    "assets/js/modal-commande-priority-fix.js"
    "assets/js/modal-commande-inject.js"
    "assets/js/modal-debug.js"
    "assets/js/barcode-scanner-fix.js"
    "assets/js/simple-barcode-detector.js"
    "assets/js/barcode-force-test.js"
    "assets/js/real-barcode-decoder.js"
    "assets/js/modal-nouvelles-actions-fix.js"
    "assets/js/modal-transitions.js"
    "assets/js/modal-guard-fix.js"
    "assets/js/modal-commande-debug.js"
    "assets/js/modal-commande.js"
    "assets/js/client-search-debug.js"
    "assets/js/modal-deep-debug.js"
    "assets/js/css-debug.js"
    "assets/js/futuristic-menu.js"
    "components/futuristic-menu.js"
)

for js_file in "${JS_FILES[@]}"; do
    if [[ -f "$js_file" ]]; then
        clean_file "$js_file" "js"
    fi
done

echo ""
echo "───────────────────────────────────────────────────────────────"
echo -e "${BLUE}🌐 Fichiers PHP avec JS intégré${NC}"
echo "───────────────────────────────────────────────────────────────"

# Pages PHP avec beaucoup de JavaScript
PHP_FILES=(
    "index.php"
    "pages/reparations.php"
    "pages/ajouter_reparation.php"
    "pages/accueil-modern.php"
    "pages/accueil_moderne2.php"
    "pages/inventaire_moderne.php"
    "pages/rachat_moderne.php"
    "pages/taches_moderne.php"
    "pages/visu_article_moderne.php"
    "pages/commande_moderne.php"
    "includes/modals.php"
    "includes/header.php"
    "includes/footer.php"
    "components/futuristic_menu.php"
)

for php_file in "${PHP_FILES[@]}"; do
    if [[ -f "$php_file" ]]; then
        clean_file "$php_file" "php"
    fi
done

echo ""
echo "───────────────────────────────────────────────────────────────"
echo -e "${BLUE}🔧 Nettoyage Automatique Récursif${NC}"
echo "───────────────────────────────────────────────────────────────"

# Nettoyer automatiquement tous les fichiers JS avec beaucoup de logs
echo -e "${YELLOW}➜${NC} Recherche des fichiers avec le plus de logs..."

# Trouver les fichiers avec le plus de console.log
find . -name "*.js" -type f -exec grep -l "console\." {} \; | head -20 | while read file; do
    log_count=$(grep -c "console\." "$file" 2>/dev/null || echo "0")
    if [[ $log_count -gt 5 ]]; then
        echo -e "${YELLOW}🎯${NC} $file ($log_count logs)"
        clean_file "$file" "js"
    fi
done

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo -e "${BLUE}📊 RÉSUMÉ DU NETTOYAGE${NC}"
echo "═══════════════════════════════════════════════════════════════"
echo -e "${GREEN}✓${NC} Fichiers traités: $TOTAL_FILES"
echo -e "${GREEN}✓${NC} Fichiers nettoyés: $CLEANED_FILES"
echo -e "${GREEN}✓${NC} Total logs supprimés: $TOTAL_LOGS_REMOVED"
echo ""

if [[ $TOTAL_LOGS_REMOVED -gt 0 ]]; then
    echo -e "${GREEN}🎉 NETTOYAGE RÉUSSI!${NC}"
    echo ""
    echo "📋 Sauvegardes créées avec extension .backup.YYYYMMDD_HHMMSS"
    echo "🚀 Console maintenant propre pour la production"
    echo ""
    echo "═══════════════════════════════════════════════════════════════"
    echo -e "${BLUE}🔄 DÉPLOIEMENT RECOMMANDÉ${NC}"
    echo "═══════════════════════════════════════════════════════════════"
    echo ""
    echo "Pour déployer les fichiers nettoyés:"
    echo ""
    echo "# Déployer les JS principaux"
    echo "sshpass -p 'Mamanmaman01#' scp -r assets/js/ root@82.29.168.205:/var/www/mdgeek.top/assets/"
    echo ""
    echo "# Déployer les pages PHP"
    echo "sshpass -p 'Mamanmaman01#' scp pages/*.php root@82.29.168.205:/var/www/mdgeek.top/pages/"
    echo ""
    echo "# Déployer index.php"
    echo "sshpass -p 'Mamanmaman01#' scp index.php root@82.29.168.205:/var/www/mdgeek.top/"
    echo ""
    echo "# Corriger les permissions"
    echo "sshpass -p 'Mamanmaman01#' ssh root@82.29.168.205 'chown -R www-data:www-data /var/www/mdgeek.top/'"
    echo ""
else
    echo -e "${YELLOW}ℹ️${NC} Aucun log de debug trouvé à nettoyer"
fi

echo "═══════════════════════════════════════════════════════════════"
echo -e "${GREEN}✅ NETTOYAGE TERMINÉ${NC}"
echo "═══════════════════════════════════════════════════════════════"
