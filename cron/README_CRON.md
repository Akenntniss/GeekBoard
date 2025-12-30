# Configuration CRON pour Auto-Reorder GeekBoard

## Installation

### 1. Sur le serveur de production

```bash
# Se connecter au serveur
sshpass -p "Mamanmamanm06400#@" ssh root@82.29.168.205

# Éditer la crontab
crontab -e
```

### 2. Ajouter cette ligne dans la crontab

```bash
# Auto-Reorder GeekBoard - Toutes les 5 minutes
*/5 * * * * /usr/bin/php /var/www/geekboard/cron/auto_reorder_cron.php >> /var/log/geekboard/auto_reorder.log 2>&1
```

**Note:** Ajustez le chemin `/var/www/geekboard` selon votre installation réelle.

### 3. Créer le dossier de logs

```bash
mkdir -p /var/log/geekboard
chmod 755 /var/log/geekboard
```

### 4. Vérifier que la crontab est bien installée

```bash
crontab -l | grep auto_reorder
```

## Test Manuel

Vous pouvez tester le script manuellement avant de l'installer dans la crontab :

```bash
/usr/bin/php /var/www/geekboard/cron/auto_reorder_cron.php
```

## Logs

Les logs seront écrits dans: `/var/log/geekboard/auto_reorder.log`

Pour voir les logs en temps réel :
```bash
tail -f /var/log/geekboard/auto_reorder.log
```

## Désactivation Temporaire

Pour désactiver temporairement :
```bash
crontab -e
# Commenter la ligne avec #
# */5 * * * * /usr/bin/php /var/www/geekboard/cron/auto_reorder_cron.php >> /var/log/geekboard/auto_reorder.log 2>&1
```

## Fréquence Alternative

Si vous voulez modifier la fréquence :

- **Toutes les heures :** `0 * * * *`
- **Toutes les 15 minutes :** `*/15 * * * *`
- **Tous les jours à 9h :** `0 9 * * *`
- **Toutes les 30 minutes :** `*/30 * * * *`
