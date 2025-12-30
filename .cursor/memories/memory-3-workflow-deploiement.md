# Mémoire 3 - Workflow de Déploiement GeekBoard

**Processus obligatoire pour GeekBoard :**

1. **TOUJOURS faire les modifications en local d'abord** dans `/Users/admin/Documents/GeekBoard/`
2. Tester localement avant déploiement
3. Upload manuel par l'utilisateur vers le serveur
4. Corriger les permissions avec `chown www-data:www-data`
5. Vider le cache PHP si nécessaire
6. À la fin de chaque session, **TOUJOURS indiquer clairement** quels fichiers ont été modifiés, ajoutés ou supprimés avec leurs chemins complets

**Informations serveur :**
- **IP serveur** : `82.29.168.205`
- **Utilisateur** : `root`
- **Mot de passe** : `Mamanmaman01#`
- **Dossier du site sur le serveur** : `/var/www/mdgeek.top`
- **Connexion SSH** : `sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205`
- **Permissions** : Propriétaire `www-data:www-data`, dossiers `755`, fichiers `644`

**Commandes utiles :**
```bash
# Test de connexion
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "pwd && ls -la /var/www/mdgeek.top/"

# Vider le cache PHP
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "php -r 'if (function_exists(\"opcache_reset\")) opcache_reset();'"

# Vérifier les permissions
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "ls -la /var/www/mdgeek.top/ | head -20"
```

Ce workflow garantit la synchronisation entre local et serveur.

