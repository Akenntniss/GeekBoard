#!/bin/bash
# Script de déploiement automatique du système d'étiquettes multi-format
# Auteur: Assistant IA
# Date: $(date +%Y-%m-%d)

set -e  # Arrêter en cas d'erreur

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration serveur
SERVER_USER="root"
SERVER_IP="82.29.168.205"
SERVER_PASS="Mamanmaman01#"
SERVER_PATH="/var/www/mdgeek.top"
LOCAL_PATH="/Users/admin/Documents/GeekBoard"

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}   DÉPLOIEMENT SYSTÈME ÉTIQUETTES MULTI-FORMAT${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Fonction pour exécuter des commandes SSH
ssh_cmd() {
    sshpass -p "$SERVER_PASS" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP "$1"
}

# Fonction pour copier des fichiers
scp_file() {
    sshpass -p "$SERVER_PASS" scp -o StrictHostKeyChecking=no "$1" $SERVER_USER@$SERVER_IP:"$2"
}

# Fonction pour copier des dossiers
scp_dir() {
    sshpass -p "$SERVER_PASS" scp -r -o StrictHostKeyChecking=no "$1" $SERVER_USER@$SERVER_IP:"$2"
}

# Étape 1: Créer les dossiers nécessaires
echo -e "${YELLOW}[1/7] Création des dossiers sur le serveur...${NC}"
ssh_cmd "mkdir -p $SERVER_PATH/pages/labels/layouts"
ssh_cmd "mkdir -p $SERVER_PATH/ajax"
ssh_cmd "mkdir -p $SERVER_PATH/includes"
echo -e "${GREEN}✓ Dossiers créés${NC}"
echo ""

# Étape 2: Upload des layouts
echo -e "${YELLOW}[2/7] Upload des layouts d'étiquettes (11 fichiers)...${NC}"
scp_dir "$LOCAL_PATH/pages/labels/" "$SERVER_PATH/pages/"
echo -e "${GREEN}✓ Layouts uploadés${NC}"
echo ""

# Étape 3: Upload du gestionnaire
echo -e "${YELLOW}[3/7] Upload du gestionnaire de layouts...${NC}"
scp_file "$LOCAL_PATH/includes/label_manager.php" "$SERVER_PATH/includes/"
echo -e "${GREEN}✓ Gestionnaire uploadé${NC}"
echo ""

# Étape 4: Upload des APIs
echo -e "${YELLOW}[4/7] Upload des APIs...${NC}"
scp_file "$LOCAL_PATH/ajax/preview_label.php" "$SERVER_PATH/ajax/"
scp_file "$LOCAL_PATH/ajax/save_label_layout.php" "$SERVER_PATH/ajax/"
echo -e "${GREEN}✓ APIs uploadées${NC}"
echo ""

# Étape 5: Upload des fichiers modifiés
echo -e "${YELLOW}[5/7] Upload des fichiers modifiés...${NC}"
scp_file "$LOCAL_PATH/pages/imprimer_etiquette.php" "$SERVER_PATH/pages/"
scp_file "$LOCAL_PATH/public_html/public_html/pages/parametre.php" "$SERVER_PATH/pages/"
echo -e "${GREEN}✓ Fichiers modifiés uploadés${NC}"
echo ""

# Étape 6: Correction des permissions
echo -e "${YELLOW}[6/7] Correction des permissions...${NC}"
ssh_cmd "chown -R www-data:www-data $SERVER_PATH/pages/labels/"
ssh_cmd "chown www-data:www-data $SERVER_PATH/includes/label_manager.php"
ssh_cmd "chown www-data:www-data $SERVER_PATH/ajax/preview_label.php"
ssh_cmd "chown www-data:www-data $SERVER_PATH/ajax/save_label_layout.php"
ssh_cmd "chown www-data:www-data $SERVER_PATH/pages/imprimer_etiquette.php"
ssh_cmd "chown www-data:www-data $SERVER_PATH/pages/parametre.php"
ssh_cmd "chmod 755 $SERVER_PATH/pages/labels/layouts/*.php"
echo -e "${GREEN}✓ Permissions corrigées${NC}"
echo ""

# Étape 7: Vider le cache PHP
echo -e "${YELLOW}[7/7] Vidage du cache PHP...${NC}"
ssh_cmd "php -r 'if (function_exists(\"opcache_reset\")) { opcache_reset(); echo \"Cache vidé\n\"; } else { echo \"OPcache non disponible\n\"; }'"
echo -e "${GREEN}✓ Cache vidé${NC}"
echo ""

# Résumé
echo -e "${BLUE}================================================${NC}"
echo -e "${GREEN}✓ DÉPLOIEMENT TERMINÉ AVEC SUCCÈS !${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""
echo -e "📋 ${BLUE}FICHIERS DÉPLOYÉS :${NC}"
echo "   • 11 layouts d'étiquettes"
echo "   • 1 gestionnaire de layouts"
echo "   • 2 APIs (preview + save)"
echo "   • 2 fichiers modifiés (imprimer_etiquette + parametre)"
echo ""
echo -e "🌐 ${BLUE}ACCÈS :${NC}"
echo "   • Configuration : https://mkmkmk.mdgeek.top/index.php?page=parametre"
echo "   • Section : Imprimante"
echo ""
echo -e "📝 ${BLUE}PROCHAINES ÉTAPES :${NC}"
echo "   1. Tester l'accès à la page Paramètres > Imprimante"
echo "   2. Vérifier que les 11 layouts s'affichent"
echo "   3. Tester la prévisualisation d'un layout"
echo "   4. Sélectionner et sauvegarder un layout"
echo "   5. Imprimer une étiquette de test"
echo ""
echo -e "📖 ${BLUE}DOCUMENTATION :${NC}"
echo "   Voir DEPLOIEMENT_ETIQUETTES.md pour plus de détails"
echo ""
echo -e "${GREEN}Déploiement terminé à $(date +%H:%M:%S)${NC}"

