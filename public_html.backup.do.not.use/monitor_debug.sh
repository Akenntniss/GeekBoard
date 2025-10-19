#!/bin/bash
# Script pour surveiller les logs de debug d'inscription.php

echo "=== SURVEILLANCE DES LOGS DE DEBUG INSCRIPTION.PHP ==="
echo "Création d'une boutique test pour déclencher le debug..."
echo "Surveillez les logs ci-dessous :"
echo "=================================================="

# Vider les logs récents pour avoir une vue claire
echo "Effacement des anciens logs..."
> /tmp/debug_logs.txt

# Fonction pour surveiller les logs
monitor_logs() {
    tail -f /var/log/nginx/error.log | grep --line-buffered -E "(SERVO SSL DEBUG|INSCRIPTION DEBUG)" | while read line; do
        echo "[$(date '+%H:%M:%S')] $line"
    done
}

# Lancer la surveillance en arrière-plan
monitor_logs &
MONITOR_PID=$!

echo "Surveillance des logs lancée (PID: $MONITOR_PID)"
echo "Créez maintenant une boutique via https://servo.tools/inscription.php"
echo "Les logs de debug apparaîtront ci-dessous en temps réel..."
echo "Appuyez sur Ctrl+C pour arrêter la surveillance"
echo "=================================================="

# Attendre l'interruption
trap "echo 'Arrêt de la surveillance...'; kill $MONITOR_PID 2>/dev/null; exit 0" INT

# Boucle infinie pour maintenir le script actif
while true; do
    sleep 1
done
