# 🚀 COMMANDES DE DÉPLOIEMENT - SOLUTION RECHERCHE GEEKBOARD

## 1. Transfert des fichiers JavaScript
sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/public_html/assets/js/recherche-compatibility-fix.js root@82.29.168.205:/var/www/html/assets/js/

sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/public_html/assets/js/recherche-universelle-new.js root@82.29.168.205:/var/www/html/assets/js/

## 2. Transfert du footer modifié
sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/public_html/includes/footer.php root@82.29.168.205:/var/www/html/includes/

## 3. Transfert de la page de test
sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/public_html/test-recherche-finale.php root@82.29.168.205:/var/www/html/

## 4. Configuration des permissions
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "chmod 644 /var/www/html/assets/js/recherche-compatibility-fix.js /var/www/html/assets/js/recherche-universelle-new.js /var/www/html/components/footer.php /var/www/html/test-recherche-finale.php"

## 5. Configuration du propriétaire
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "chown www-data:www-data /var/www/html/assets/js/recherche-compatibility-fix.js /var/www/html/assets/js/recherche-universelle-new.js /var/www/html/components/footer.php /var/www/html/test-recherche-finale.php"

## 6. Vérification du déploiement
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "ls -la /var/www/html/assets/js/recherche-* && ls -la /var/www/html/components/footer.php && ls -la /var/www/html/test-recherche-finale.php"

## 🌐 URL de test après déploiement
# https://mdgeek.top/test-recherche-finale.php

## 📋 RÉSUMÉ DE LA SOLUTION
# ✅ Script de compatibilité créé (recherche-compatibility-fix.js)
# ✅ Footer modifié pour inclure les bons scripts
# ✅ Page de test complète avec debug
# ✅ Résolution du conflit d'IDs entre modal et script JavaScript
# ✅ Support multi-endpoints de recherche
# ✅ Gestion d'erreurs avancée