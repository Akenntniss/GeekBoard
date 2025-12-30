#!/bin/bash

echo "=== DÉPLOIEMENT DES FICHIERS MODIFIÉS ==="

# 1. CSS nettoyé
echo "1. Déploiement du CSS nettoyé..."
sshpass -p "Mamanmanan01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 'cat > /var/www/mdgeek.top/assets/css/tableaux-master.css' < public_html/assets/css/tableaux-master.css

# 2. JavaScript modifié  
echo "2. Déploiement du JavaScript modifié..."
sshpass -p "Mamanmanan01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 'cat > /var/www/mdgeek.top/assets/js/modern-interactions.js' < public_html/assets/js/modern-interactions.js

# 3. Page clients nettoyée
echo "3. Déploiement de la page clients nettoyée..."
sshpass -p "Mamanmanan01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 'cat > /var/www/mdgeek.top/pages/clients.php' < public_html/pages/clients.php

echo "=== DÉPLOIEMENT TERMINÉ ==="
