#!/bin/bash

echo "🚀 Déploiement admin_missions..."

# Vérifier si admin_missions est présent sur le serveur
ADMIN_MISSIONS_COUNT=$(sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "grep -c 'admin_missions' /var/www/mdgeek.top/index.php 2>/dev/null || echo 0")

echo "📊 admin_missions trouvé $ADMIN_MISSIONS_COUNT fois dans index.php sur le serveur"

if [ "$ADMIN_MISSIONS_COUNT" -lt 3 ]; then
    echo "⚠️  admin_missions manquant, redéploiement nécessaire..."
    
    # Sauvegarder la version actuelle
    sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "cp /var/www/mdgeek.top/index.php /var/www/mdgeek.top/index.php.backup_\$(date +%Y%m%d_%H%M%S)"
    
    # Déployer notre version
    sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no public_html/index.php root@82.29.168.205:/var/www/mdgeek.top/
    
    # Corriger les permissions
    sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "chown www-data:www-data /var/www/mdgeek.top/index.php"
    
    # Ajouter un marqueur
    sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "echo '// ADMIN_MISSIONS_DEPLOYED_\$(date +%Y%m%d_%H%M%S)' >> /var/www/mdgeek.top/index.php"
    
    echo "✅ Redéploiement terminé"
else
    echo "✅ admin_missions déjà présent, aucun déploiement nécessaire"
fi

# Déployer la page admin_missions
echo "📄 Déploiement de la page admin_missions.php..."
sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no public_html/pages/admin_missions.php root@82.29.168.205:/var/www/mdgeek.top/pages/
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "chown www-data:www-data /var/www/mdgeek.top/pages/admin_missions.php"

echo "🎉 Déploiement terminé !"
echo "🔗 Testez : https://mkmkmk.mdgeek.top/index.php?page=admin_missions"
