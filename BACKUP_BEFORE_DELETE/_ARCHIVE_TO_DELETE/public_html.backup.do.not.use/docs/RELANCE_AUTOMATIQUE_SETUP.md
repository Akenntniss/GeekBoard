# Configuration des Relances Automatiques sur Hostinger

## 1. Installation des Tables de Base de Données

Exécutez le script SQL suivant sur **CHAQUE** base de données de magasin :

```sql
-- Exécuter ce script sur chaque base geekboard_xxx
SOURCE /path/to/your/public_html/sql/create_relance_automatique.sql;
```

## 2. Configuration du Cron Job sur Hostinger

### Étape 1: Accéder au panneau de contrôle Hostinger
1. Connectez-vous à votre panneau Hostinger
2. Allez dans **Avancé** > **Cron Jobs**

### Étape 2: Créer le cron job
Ajoutez un nouveau cron job avec les paramètres suivants :

**Fréquence :** Chaque minute
```
* * * * *
```

**Commande :**
```bash
/usr/bin/php /home/u123456789/domains/mdgeek.top/public_html/scripts/relance_automatique.php >> /home/u123456789/domains/mdgeek.top/public_html/logs/relance_auto.log 2>&1
```

**⚠️ Important :** Remplacez `/home/u123456789/` par votre vrai chemin utilisateur Hostinger.

### Étape 3: Créer le dossier de logs
```bash
mkdir -p /home/u123456789/domains/mdgeek.top/public_html/logs
touch /home/u123456789/domains/mdgeek.top/public_html/logs/relance_auto.log
chmod 755 /home/u123456789/domains/mdgeek.top/public_html/logs/relance_auto.log
```

## 3. Configuration Alternative (si problème avec le cron Hostinger)

Si le cron job Hostinger ne fonctionne pas, vous pouvez utiliser un service externe comme **cron-job.org** :

1. Créez un fichier `public_html/cron/relance_auto.php` :

```php
<?php
// Vérification de sécurité basique
$secret_key = 'votre_cle_secrete_ici';
if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    http_response_code(403);
    exit('Accès refusé');
}

// Exécuter le script
include __DIR__ . '/../scripts/relance_automatique.php';
?>
```

2. Sur cron-job.org, programmez un appel à :
```
https://mdgeek.top/cron/relance_auto.php?key=votre_cle_secrete_ici
```

## 4. Test de Fonctionnement

### Test manuel du script :
```bash
cd /home/u123456789/domains/mdgeek.top/public_html/scripts
php relance_automatique.php
```

### Vérifier les logs :
```bash
tail -f /home/u123456789/domains/mdgeek.top/public_html/logs/relance_auto.log
```

## 5. Surveillance et Maintenance

### Vérifier que le cron fonctionne :
```bash
# Voir les dernières lignes du log
tail -20 /home/u123456789/domains/mdgeek.top/public_html/logs/relance_auto.log

# Voir les logs en temps réel
tail -f /home/u123456789/domains/mdgeek.top/public_html/logs/relance_auto.log
```

### Nettoyer les logs anciens (optionnel) :
Ajoutez ce cron job pour nettoyer les logs de plus de 30 jours :
```bash
# Tous les jours à 2h du matin
0 2 * * * find /home/u123456789/domains/mdgeek.top/public_html/logs -name "*.log" -mtime +30 -delete
```

## 6. Fonctionnement du Système

- Le script s'exécute **toutes les minutes**
- Il vérifie l'heure actuelle (format HH:MM)
- Pour chaque magasin avec relance automatique activée :
  - Vérifie si une relance est programmée à cette heure
  - Vérifie qu'aucune relance n'a déjà été envoyée aujourd'hui à cette heure
  - Envoie des SMS aux devis en attente (non expirés)
  - Enregistre les résultats dans les logs

## 7. Sécurité

- Les logs contiennent des informations sensibles (numéros de téléphone)
- Assurez-vous que le dossier `/logs` n'est pas accessible depuis le web
- Ajoutez un fichier `.htaccess` dans le dossier logs :

```apache
# /home/u123456789/domains/mdgeek.top/public_html/logs/.htaccess
Order Deny,Allow
Deny from all
```

## 8. Dépannage

### Problème : Le cron ne s'exécute pas
- Vérifiez que le chemin PHP est correct : `/usr/bin/php`
- Testez manuellement : `php /path/to/script/relance_automatique.php`
- Vérifiez les permissions du fichier script

### Problème : Erreurs de base de données
- Vérifiez que les tables sont créées sur toutes les bases de magasins
- Vérifiez les permissions de connexion

### Problème : SMS non envoyés
- Vérifiez la configuration SMS dans `includes/sms_functions.php`
- Vérifiez les crédits SMS disponibles
- Consultez les logs pour les erreurs détaillées

## 9. Interface de Gestion

L'interface de gestion des relances automatiques est accessible dans :
- Page des devis en attente (modal)
- Toggle pour activer/désactiver
- Configuration des horaires (jusqu'à 10 par jour)
- Historique dans les logs de la base de données

