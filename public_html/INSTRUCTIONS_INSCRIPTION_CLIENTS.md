# Instructions pour le Déploiement de la Fonctionnalité d'Inscription Clients

## 📋 Résumé de la Fonctionnalité

J'ai créé une fonctionnalité complète d'inscription client permettant aux futurs propriétaires de magasins de s'inscrire et de créer automatiquement leur boutique GeekBoard.

## 🗂️ Fichiers Créés

### 1. `create_shop_owners_table.sql`
**Description :** Script SQL pour créer la table des propriétaires de magasins  
**Emplacement :** `/Users/admin/Documents/GeekBoard/create_shop_owners_table.sql`  
**Usage :** À exécuter dans la base de données principale `geekboard_general`

### 2. `inscription.php`
**Description :** Page publique d'inscription avec formulaire complet  
**Emplacement :** `/Users/admin/Documents/GeekBoard/public_html/inscription.php`  
**Usage :** Page accessible publiquement à l'adresse `mdgeek.top/inscription.php`

## 🚀 Étapes de Déploiement

### Étape 1 : Créer la Table des Propriétaires
```bash
# Se connecter au serveur
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205

# Se connecter à MySQL et exécuter le script
mysql -u root -p'Mamanmaman01#' geekboard_general < /var/www/mdgeek.top/create_shop_owners_table.sql
```

### Étape 2 : Uploader les Fichiers
```bash
# Uploader le script SQL
sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/create_shop_owners_table.sql root@82.29.168.205:/var/www/mdgeek.top/

# Uploader la page d'inscription
sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/public_html/inscription.php root@82.29.168.205:/var/www/mdgeek.top/

# Corriger les permissions
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "chown www-data:www-data /var/www/mdgeek.top/inscription.php"
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "chown www-data:www-data /var/www/mdgeek.top/create_shop_owners_table.sql"
```

### Étape 3 : Exécuter le Script SQL
```bash
# Se connecter au serveur et exécuter le script
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "mysql -u root -p'Mamanmaman01#' geekboard_general < /var/www/mdgeek.top/create_shop_owners_table.sql"
```

## 📋 Fonctionnalités Incluses

### 🎯 Formulaire d'Inscription Complet
- **Informations personnelles :** Nom, Prénom
- **Informations commerciales :** Nom commercial (facultatif), SIRET (obligatoire)
- **Informations de connexion :** Email, Mot de passe
- **Informations de contact :** Téléphone, Adresse postale, Code postal, Ville
- **Conditions légales :** Acceptation CGU et CGV

### 🔐 Validations Intégrées
- **SIRET :** Validation avec algorithme de Luhn modifié
- **Email :** Validation format et unicité
- **Mot de passe :** Minimum 6 caractères avec confirmation
- **Champs obligatoires :** Validation côté client et serveur
- **Doublons :** Vérification email et SIRET uniques

### 🏪 Création Automatique de Magasin
- **Base de données :** Création automatique `geekboard_{subdomain}`
- **Utilisateur MySQL :** Création avec mot de passe `Admin123!`
- **Sous-domaine :** Génération automatique basée sur nom commercial/nom
- **Structure complète :** Toutes les tables et données essentielles
- **Mapping automatique :** Mise à jour du fichier `login_auto.php`
- **Utilisateur admin :** Création avec email du propriétaire

### 🎨 Interface Moderne
- **Design responsive :** Compatible mobile et desktop
- **Bannière "Essayez maintenant" :** Visible en haut de page
- **Animations :** Transitions fluides et professionnelles
- **Validation temps réel :** SIRET et confirmation mot de passe
- **Retours utilisateur :** Messages d'erreur et de succès clairs

## 📊 Structure de la Table `shop_owners`

```sql
- id (auto-increment)
- nom, prenom
- nom_commercial (nullable)
- siret (unique, obligatoire)
- email (unique, obligatoire)
- password (hash)
- telephone, adresse, code_postal, ville
- cgu_acceptees, cgv_acceptees (boolean)
- shop_id (foreign key vers shops.id)
- statut (en_attente, approuve, refuse, actif)
- date_inscription, date_creation_shop
- notes_admin (pour suivi administratif)
```

## 🔄 Workflow Complet

1. **Client accède à :** `mdgeek.top/inscription.php`
2. **Remplit le formulaire** avec toutes les informations
3. **Valide les conditions** CGU et CGV
4. **Soumission :** 
   - Insertion dans table `shop_owners`
   - Création automatique du magasin
   - Génération sous-domaine unique
   - Création base de données complète
   - Mise à jour mapping sous-domaines
5. **Accès immédiat :** Lien vers `{subdomain}.mdgeek.top`

## ⚙️ Paramètres Automatiques

- **Mot de passe DB :** `Admin123!` (fixe)
- **Mot de passe admin initial :** `Admin123!` (temporaire)
- **Username admin :** Email du propriétaire
- **Sous-domaine :** Basé sur nom commercial ou nom/prénom
- **Gestion collisions :** Ajout automatique de suffixes numériques

## 🔗 Intégration avec l'Existant

La fonctionnalité utilise exactement la même logique que `superadmin/create_shop.php` pour garantir :
- **Compatibilité :** Avec l'architecture multi-database existante
- **Cohérence :** Structure identique aux magasins créés manuellement
- **Fiabilité :** Réutilisation du code testé et validé

## 🎯 Avantages

1. **Automatisation complète :** Zéro intervention manuelle
2. **Expérience utilisateur :** Process fluide et professionnel
3. **Sécurité :** Validations multiples et données chiffrées
4. **Évolutivité :** Structure préparée pour fonctionnalités futures
5. **Traçabilité :** Historique complet des inscriptions

## 📞 URL d'Accès Final

Une fois déployé, la page sera accessible à :
**https://mdgeek.top/inscription.php**

Avec la bannière "Essayez maintenant" mise en évidence pour attirer l'attention des visiteurs.

---

**✅ La fonctionnalité est prête au déploiement !**
