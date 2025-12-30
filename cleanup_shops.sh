#!/bin/bash

# Script de suppression des magasins
# Ce script supprime tous les magasins SAUF: mkmkmk, phonesystem, phoneetoile, mdg

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}SUPPRESSION DES MAGASINS${NC}"
echo -e "${YELLOW}========================================${NC}"
echo ""
echo -e "${GREEN}Magasins à CONSERVER:${NC}"
echo "  - general (DatabaseGeneral)"
echo "  - mkmkmk"
echo "  - phonesystem"
echo "  - phoneetoile"
echo "  - mdg"
echo ""
echo -e "${RED}76 magasins seront SUPPRIMÉS${NC}"
echo ""

# Connexion SSH
SSH_CMD="sshpass -p 'Mamanmaman01#' ssh -o StrictHostKeyChecking=no root@82.29.168.205"

echo -e "${YELLOW}Étape 1: Récupération de la liste des bases de données à supprimer${NC}"

# Récupérer la liste des bases de données à supprimer
DATABASES=$($SSH_CMD "mysql -u root -p'Mamanmaman01#' geekboard_general -N -e \"SELECT db_name FROM shops WHERE subdomain NOT IN ('general', 'mkmkmk', 'phonesystem', 'phoneetoile', 'mdg');\"")

echo -e "${GREEN}Bases de données à supprimer:${NC}"
echo "$DATABASES"
echo ""

echo -e "${YELLOW}Étape 2: Suppression des bases de données${NC}"

# Supprimer chaque base de données
for db in $DATABASES; do
    echo -e "${YELLOW}  Suppression de la base de données: $db${NC}"
    $SSH_CMD "mysql -u root -p'Mamanmaman01#' -e 'DROP DATABASE IF EXISTS \`$db\`;'" 2>&1 | grep -v "Warning"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}    ✓ $db supprimée${NC}"
    else
        echo -e "${RED}    ✗ Erreur lors de la suppression de $db${NC}"
    fi
done

echo ""
echo -e "${YELLOW}Étape 3: Suppression des enregistrements dans la table shops${NC}"

$SSH_CMD "mysql -u root -p'Mamanmaman01#' geekboard_general -e \"DELETE FROM shops WHERE subdomain NOT IN ('general', 'mkmkmk', 'phonesystem', 'phoneetoile', 'mdg');\"" 2>&1 | grep -v "Warning"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}  ✓ Enregistrements supprimés de la table shops${NC}"
else
    echo -e "${RED}  ✗ Erreur lors de la suppression des enregistrements${NC}"
fi

echo ""
echo -e "${YELLOW}Étape 4: Vérification finale${NC}"

REMAINING=$($SSH_CMD "mysql -u root -p'Mamanmaman01#' geekboard_general -N -e \"SELECT COUNT(*) FROM shops;\"" 2>&1 | grep -v "Warning")

echo -e "${GREEN}Nombre de magasins restants: $REMAINING${NC}"
echo ""

$SSH_CMD "mysql -u root -p'Mamanmaman01#' geekboard_general -e \"SELECT id, name, subdomain, db_name, active FROM shops;\"" 2>&1 | grep -v "Warning"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}SUPPRESSION TERMINÉE${NC}"
echo -e "${GREEN}========================================${NC}"
