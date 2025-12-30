#!/bin/bash

# Script de déploiement pour la solution de recherche GeekBoard
echo "🚀 Déploiement de la solution de recherche..."

# Variables
SERVER="root@82.29.168.205"
PASSWORD="Mamanmaman01#"
REMOTE_PATH="/var/www/html"
LOCAL_PATH="/Users/admin/Documents/GeekBoard/public_html"

# Fonction pour exécuter des commandes SSH
ssh_exec() {
    sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no "$SERVER" "$1"
}

# Fonction pour copier des fichiers
scp_file() {
    sshpass -p "$PASSWORD" scp -o StrictHostKeyChecking=no "$1" "$SERVER:$2"
}

echo "📁 Création des répertoires nécessaires..."
ssh_exec "mkdir -p $REMOTE_PATH/assets/js"
ssh_exec "mkdir -p $REMOTE_PATH/components"

echo "📤 Transfert du script de compatibilité..."
scp_file "$LOCAL_PATH/assets/js/recherche-compatibility-fix.js" "$REMOTE_PATH/assets/js/"

echo "📤 Transfert du script de recherche universelle..."
scp_file "$LOCAL_PATH/assets/js/recherche-universelle-new.js" "$REMOTE_PATH/assets/js/"

echo "📤 Transfert du footer modifié..."
scp_file "$LOCAL_PATH/components/footer.php" "$REMOTE_PATH/components/"

echo "📤 Transfert de la page de test..."
scp_file "$LOCAL_PATH/test-recherche-finale.php" "$REMOTE_PATH/"

echo "🔧 Configuration des permissions..."
ssh_exec "chmod 644 $REMOTE_PATH/assets/js/recherche-compatibility-fix.js"
ssh_exec "chmod 644 $REMOTE_PATH/assets/js/recherche-universelle-new.js"
ssh_exec "chmod 644 $REMOTE_PATH/components/footer.php"
ssh_exec "chmod 644 $REMOTE_PATH/test-recherche-finale.php"

echo "👤 Configuration du propriétaire..."
ssh_exec "chown www-data:www-data $REMOTE_PATH/assets/js/recherche-compatibility-fix.js"
ssh_exec "chown www-data:www-data $REMOTE_PATH/assets/js/recherche-universelle-new.js"
ssh_exec "chown www-data:www-data $REMOTE_PATH/components/footer.php"
ssh_exec "chown www-data:www-data $REMOTE_PATH/test-recherche-finale.php"

echo "✅ Déploiement terminé !"
echo "🌐 Testez la solution sur : https://mdgeek.top/test-recherche-finale.php"

# Vérification des fichiers
echo "🔍 Vérification des fichiers déployés..."
ssh_exec "ls -la $REMOTE_PATH/assets/js/recherche-*"
ssh_exec "ls -la $REMOTE_PATH/components/footer.php"
ssh_exec "ls -la $REMOTE_PATH/test-recherche-finale.php"