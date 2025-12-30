#!/bin/bash
# Script pour appliquer les optimisations SQL à toutes les bases de données shop

# Couleurs pour l'affichage
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "═══════════════════════════════════════════════════════════════"
echo -e "${BLUE}🚀 Application des Optimisations SQL aux Bases Shop${NC}"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Paramètres de connexion
SSH_CMD="sshpass -p 'Mamanmaman01#' ssh -o StrictHostKeyChecking=no root@82.29.168.205"
MYSQL_CMD="mysql -u root -pMamanmaman01#"
SQL_FILE="/var/www/mdgeek.top/sql/optimize_reparation_logs.sql"

# Liste des bases à optimiser (ajouter les nouvelles bases ici)
DATABASES=(
    "geekboard_mkmkmk"
    "geekboard_cannesphones"
    # Ajouter d'autres bases ici au besoin
)

echo -e "${YELLOW}📋 Bases de données à optimiser:${NC}"
for db in "${DATABASES[@]}"; do
    echo "   - $db"
done
echo ""

# Fonction pour appliquer les optimisations à une base
optimize_database() {
    local db=$1
    echo "───────────────────────────────────────────────────────────────"
    echo -e "${BLUE}🔧 Optimisation de: ${GREEN}$db${NC}"
    echo "───────────────────────────────────────────────────────────────"
    
    # Vérifier si la base existe
    echo -e "${YELLOW}➜${NC} Vérification de l'existence de la base..."
    DB_EXISTS=$($SSH_CMD "$MYSQL_CMD -e 'SHOW DATABASES LIKE \"$db\";'" 2>/dev/null | grep -c "$db")
    
    if [ "$DB_EXISTS" -eq 0 ]; then
        echo -e "${RED}✗${NC} Base de données '$db' introuvable. Ignoré."
        echo ""
        return 1
    fi
    
    echo -e "${GREEN}✓${NC} Base trouvée"
    
    # Appliquer le script SQL
    echo -e "${YELLOW}➜${NC} Application du script SQL d'optimisation..."
    RESULT=$($SSH_CMD "$MYSQL_CMD $db < $SQL_FILE 2>&1")
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} Optimisations appliquées avec succès"
        
        # Afficher un résumé des index créés
        echo -e "${YELLOW}➜${NC} Vérification des index créés..."
        INDEX_COUNT=$($SSH_CMD "$MYSQL_CMD -e 'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=\"$db\" AND (INDEX_NAME LIKE \"idx_%\");'" 2>/dev/null | tail -1)
        echo -e "${GREEN}✓${NC} $INDEX_COUNT index optimisés créés"
        
        # Vérifier la table de cache
        CACHE_TABLE=$($SSH_CMD "$MYSQL_CMD -e 'SHOW TABLES FROM $db LIKE \"log_statistics_cache\";'" 2>/dev/null | grep -c "log_statistics_cache")
        if [ "$CACHE_TABLE" -gt 0 ]; then
            echo -e "${GREEN}✓${NC} Table de cache créée"
        fi
        
        # Vérifier la vue
        VIEW_EXISTS=$($SSH_CMD "$MYSQL_CMD -e 'SHOW FULL TABLES FROM $db WHERE TABLE_TYPE LIKE \"VIEW\" AND Tables_in_$db = \"v_combined_logs\";'" 2>/dev/null | grep -c "v_combined_logs")
        if [ "$VIEW_EXISTS" -gt 0 ]; then
            echo -e "${GREEN}✓${NC} Vue v_combined_logs créée"
        fi
        
        echo -e "${GREEN}✓${NC} Optimisation de '$db' terminée avec succès!"
    else
        echo -e "${RED}✗${NC} Erreur lors de l'optimisation de '$db'"
        echo "$RESULT"
    fi
    
    echo ""
}

# Boucle sur toutes les bases
SUCCESS_COUNT=0
FAIL_COUNT=0

for db in "${DATABASES[@]}"; do
    if optimize_database "$db"; then
        ((SUCCESS_COUNT++))
    else
        ((FAIL_COUNT++))
    fi
done

# Résumé final
echo "═══════════════════════════════════════════════════════════════"
echo -e "${BLUE}📊 RÉSUMÉ FINAL${NC}"
echo "═══════════════════════════════════════════════════════════════"
echo -e "${GREEN}✓${NC} Bases optimisées avec succès: $SUCCESS_COUNT"
if [ $FAIL_COUNT -gt 0 ]; then
    echo -e "${RED}✗${NC} Bases en échec: $FAIL_COUNT"
fi
echo ""

# Afficher les commandes de vérification
echo "═══════════════════════════════════════════════════════════════"
echo -e "${BLUE}🔍 COMMANDES DE VÉRIFICATION${NC}"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Vérifier les index d'une base:"
echo "  sshpass -p 'Mamanmaman01#' ssh root@82.29.168.205 \\"
echo "    \"mysql -u root -pMamanmaman01# -e 'SHOW INDEX FROM [BASE].reparation_logs;'\""
echo ""
echo "Vérifier le cache:"
echo "  sshpass -p 'Mamanmaman01#' ssh root@82.29.168.205 \\"
echo "    \"mysql -u root -pMamanmaman01# -e 'SELECT COUNT(*) FROM [BASE].log_statistics_cache;'\""
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo -e "${GREEN}🎉 OPTIMISATIONS COMPLÈTES!${NC}"
echo "═══════════════════════════════════════════════════════════════"

