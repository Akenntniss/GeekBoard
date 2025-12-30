#!/bin/bash
# Déploiement des nouvelles pages marketing sur servo.tools
# Serveur: root@82.29.168.205

echo "🚀 Déploiement des pages marketing SERVO..."

# Variables
SERVER="root@82.29.168.205"
REMOTE_PATH="/var/www/mdgeek.top"
LOCAL_PATH="/Users/admin/Documents/GeekBoard"

# Upload des nouvelles pages marketing
echo "📤 Upload de sms-automatiques.php..."
sshpass -p "2VNfJgUVvhrHNOeJItSbgEhJ" scp -o StrictHostKeyChecking=no \
    "$LOCAL_PATH/marketing/pages/sms-automatiques.php" \
    "$SERVER:$REMOTE_PATH/marketing/pages/"

echo "📤 Upload de missions-gamification.php..."
sshpass -p "2VNfJgUVvhrHNOeJItSbgEhJ" scp -o StrictHostKeyChecking=no \
    "$LOCAL_PATH/marketing/pages/missions-gamification.php" \
    "$SERVER:$REMOTE_PATH/marketing/pages/"

echo "📤 Upload du router.php mis à jour..."
sshpass -p "2VNfJgUVvhrHNOeJItSbgEhJ" scp -o StrictHostKeyChecking=no \
    "$LOCAL_PATH/marketing/router.php" \
    "$SERVER:$REMOTE_PATH/marketing/"

# Mise à jour de la config nginx
echo "🔧 Mise à jour de la configuration nginx..."
sshpass -p "2VNfJgUVvhrHNOeJItSbgEhJ" ssh -o StrictHostKeyChecking=no "$SERVER" << 'EOF'
    # Backup de la config actuelle
    cp /etc/nginx/sites-available/servo.tools /etc/nginx/sites-available/servo.tools.backup

    # Ajouter les nouvelles routes dans la regex ligne 29
    sed -i 's|vs-repairdesk)|vs-repairdesk|sms-automatiques|sms|missions-gamification|missions)|' /etc/nginx/sites-available/servo.tools

    # Tester la configuration
    nginx -t

    # Si le test passe, reload nginx
    if [ $? -eq 0 ]; then
        systemctl reload nginx
        echo "✅ Nginx rechargé avec succès"
    else
        echo "❌ Erreur de configuration nginx"
        # Restaurer le backup
        cp /etc/nginx/sites-available/servo.tools.backup /etc/nginx/sites-available/servo.tools
        exit 1
    fi
EOF

echo "✅ Déploiement terminé !"
echo ""
echo "🌐 Pages disponibles sur:"
echo "   - https://servo.tools/sms-automatiques"
echo "   - https://servo.tools/missions-gamification"
echo "   - https://mdgeek.top/sms-automatiques"
echo "   - https://mdgeek.top/missions-gamification"
