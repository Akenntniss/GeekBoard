# Configuration Serveur FTP GeekBoard

## 📋 Informations de Connexion FTP

- **Serveur** : `82.29.168.205`
- **Port** : `21` (FTP standard)
- **Utilisateur** : `ftpuser`
- **Mot de passe** : `GeekBoard2024!`
- **Protocole** : FTPS (FTP avec SSL/TLS)

## 🔧 Configuration Technique

- **Dossier racine** : `/var/www/mdgeek.top/` (dossier web GeekBoard)
- **SSL/TLS** : Activé et obligatoire pour toutes les connexions
- **Ports passifs** : `10000-10100`
- **Chroot** : Activé (utilisateur confiné dans le dossier web)
- **Accès anonyme** : Désactivé

## 💻 Comment se connecter

### Avec un client FTP graphique (FileZilla, WinSCP, etc.)
```
Hôte : 82.29.168.205
Port : 21
Utilisateur : ftpuser
Mot de passe : GeekBoard2024!
Protocole : FTPS (FTP over TLS)
```

### Avec la ligne de commande
```bash
ftp 82.29.168.205
# Puis entrer : ftpuser / GeekBoard2024!
```

## 🔒 Sécurité

- ✅ SSL/TLS obligatoire pour toutes les connexions
- ✅ Utilisateur confiné dans le dossier web uniquement
- ✅ Accès anonyme désactivé
- ✅ Ports firewall ouverts (21 + 10000-10100)
- ✅ Utilisateur dédié avec permissions limitées

## 📁 Accès aux fichiers

Une fois connecté, vous aurez accès direct à tous les fichiers de votre site web dans `/var/www/mdgeek.top/`, ce qui vous permettra de :
- Uploader facilement des fichiers
- Modifier des fichiers existants
- Gérer l'arborescence de votre site
- Synchroniser vos modifications locales

## 🛠️ Commandes de gestion

### Redémarrer le service FTP
```bash
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "systemctl restart vsftpd"
```

### Vérifier le statut
```bash
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "systemctl status vsftpd"
```

### Vérifier les connexions actives
```bash
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "netstat -tlnp | grep :21"
```

## 📅 Date d'installation
Installé le : 28 septembre 2025

---
**Note** : Ce serveur FTP remplace avantageusement l'utilisation de `scp` pour les transferts de fichiers fréquents, offrant une interface graphique plus conviviale et une gestion plus simple des fichiers.
