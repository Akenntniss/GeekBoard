---
title: Site marketing mdgeek.top
---

## Vue d'ensemble

Un site vitrine multi-pages professionnel a été ajouté pour le domaine racine `mdgeek.top`:

- Accueil `/`
- Fonctionnalités `/features` (alias `/fonctionnalites`)
- Tarifs `/pricing` (alias `/tarifs`)
- Témoignages `/testimonials` (alias `/temoignages`)
- Calculateur d'économies `/roi` (alias `/calculator`, `/calculateur`)
- Contact/Démo `/contact` (alias `/demo`)

## Architecture

- `public_html/index.php` : route vers `marketing/router.php` lorsque domaine = mdgeek.top et aucune session magasin/superadmin
- `public_html/marketing/router.php` : routeur marketing simple par chemin
- `public_html/marketing/shared/{header,footer}.php` : layout commun
- `public_html/marketing/pages/*.php` : pages marketing
- `public_html/contact_handler.php` : enregistre les leads (table `contact_requests`) et envoie un email
- `public_html/.htaccess` : réécritures SEO-friendly pour servir via `index.php`

## Base de données

Table `contact_requests` (auto-créée si absente) dans `geekboard_general` pour stocker les demandes de contact.

## Déploiement

1. Déployer les fichiers modifiés/ajoutés:
   - `public_html/index.php`
   - `public_html/.htaccess`
   - `public_html/marketing/**/*`
   - `public_html/contact_handler.php`
2. Corriger les permissions (www-data)
3. Vider l'opcache PHP si activé

### Commandes

Voir section « Processus de Déploiement GeekBoard ». Exemple:

```bash
sshpass -p "Mamanmaman01#" scp -r -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/public_html/{marketing,contact_handler.php} root@82.29.168.205:/var/www/mdgeek.top/public_html/
sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/public_html/{index.php,.htaccess} root@82.29.168.205:/var/www/mdgeek.top/public_html/
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "chown -R www-data:www-data /var/www/mdgeek.top/public_html/ && php -r 'if (function_exists(\"opcache_reset\")) opcache_reset();'"
```

## Notes

- Le routeur marketing n'affecte pas les sous-domaines boutiques
- Les routes API/app sont explicitement exclues des réécritures
- Remplacer les tarifs/avis par du contenu réel quand disponible


