#!/bin/bash
# Script pour surveiller les logs d'inscription.php en temps réel

echo "=== SURVEILLANCE LOGS INSCRIPTION.PHP ==="
echo "Créez maintenant une boutique via https://servo.tools/inscription.php"
echo "Les logs apparaîtront ci-dessous en temps réel..."
echo "Appuyez sur Ctrl+C pour arrêter"
echo "=================================================="

# Surveiller les logs avec filtrage
tail -f /var/log/nginx/error.log | grep --line-buffered -E "(INSCRIPTION DEBUG|SERVO SSL DEBUG|SERVO SSL:|SSL_SUCCESS)" | while read line; do
    timestamp=$(date '+%H:%M:%S')
    echo "[$timestamp] $line"
done
