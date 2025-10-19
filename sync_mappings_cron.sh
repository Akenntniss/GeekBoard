#!/bin/bash
# Script wrapper pour la synchronisation automatique des mappings
# Exécuté par cron toutes les 2 minutes

# Configuration
SCRIPT_DIR="/var/www/mdgeek.top"
PHP_SCRIPT="auto_sync_mappings.php"
LOG_FILE="/var/log/geekboard_sync_mappings.log"

# Créer le fichier de log s'il n'existe pas
if [ ! -f "$LOG_FILE" ]; then
    touch "$LOG_FILE"
    chmod 644 "$LOG_FILE"
    chown www-data:www-data "$LOG_FILE"
fi

# Changer vers le répertoire de l'application
cd "$SCRIPT_DIR" || exit 1

# Exécuter le script PHP avec timeout de 30 secondes
timeout 30 php "$PHP_SCRIPT" 2>&1

# Code de sortie
exit_code=$?

if [ $exit_code -eq 124 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] TIMEOUT: Script interrompu après 30 secondes" >> "$LOG_FILE"
elif [ $exit_code -ne 0 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: Script terminé avec le code $exit_code" >> "$LOG_FILE"
fi

exit $exit_code
