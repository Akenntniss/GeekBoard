#!/bin/bash

# Script pour vérifier le serveur sans sshpass
echo "=== Vérification du serveur GeekBoard ==="

# Fonction pour exécuter des commandes SSH
run_ssh_cmd() {
    expect -c "
    spawn ssh -o StrictHostKeyChecking=no root@82.29.168.205 \"$1\"
    expect \"password:\"
    send \"Mamanmaman01#\r\"
    expect eof
    "
}

echo "1. Vérification des tables dans geekboard_mkmkmk..."
run_ssh_cmd "mysql -u root -e 'USE geekboard_mkmkmk; SHOW TABLES;'"

echo -e "\n2. Vérification des bases de données disponibles..."
run_ssh_cmd "mysql -u root -e 'SHOW DATABASES;'"

echo -e "\n3. Recherche des tables rachat dans toutes les bases..."
run_ssh_cmd "mysql -u root -e \"SELECT table_schema, table_name FROM information_schema.tables WHERE table_name LIKE '%rachat%';\""

echo -e "\n4. Vérification du fichier export_rachat_pdf.php..."
run_ssh_cmd "ls -la /var/www/mdgeek.top/ajax/export_rachat*"

echo -e "\n=== Fin de la vérification ==="
