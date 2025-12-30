# Analyse de Migration - base_client.sql vers mdg.servo.tools

## 📊 Situation Actuelle

### Base de Données Cible
- **Sous-domaine** : `mdg.servo.tools`
- **Shop ID** : 162
- **Base de données** : `geekboard_mdg`

### État Actuel de la Base mdg.servo.tools
- **Clients** : 0
- **Utilisateurs** : 1 (utilisateur par défaut)
- **Réparations** : 0
- **SMS** : 0

## 📋 Données à Migrer depuis base_client.sql

### 1. Clients (605 clients)
- **Plage d'IDs** : 384 à 988
- **Données** : nom, prénom, téléphone, email, dates
- **Exemple** :
  ```sql
  (384, 'Ouerghemi', 'Sofien', '3354115219', NULL, '2025-04-08 11:44:35', 0, NULL, NULL)
  (985, 'ihebguissem', 'ihebguissem', '33780708155', '', '2025-08-28 13:35:22', 0, NULL, NULL)
  ```

### 2. Utilisateurs (4 utilisateurs)
- **Données** :
  ```sql
  (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur', 'admin', '2025-03-20 10:15:29', 1, 1622)
  (2, 'Merlin', '$2y$10$RO7GypkGgX2g/0UPzONXZOGNKe82FvDfsHRPP.nt79ts1Ufru7C/S', 'Merlin', 'technicien', '2025-03-20 10:15:29', 0, NULL)
  (3, 'benjamin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Benjamin', 'admin', '2025-03-20 10:15:29', 1, 1560)
  (5, 'asd', '$2y$10$N.iV7OhGEUBMa7vWjF2jY.CE2/STVvPSdw6ekMZ.XinO7CybzxdJ2', 'asd', 'technicien', '2025-04-16 22:34:10', 0, NULL)
  ```

### 3. Réparations (~175 réparations)
- **Plage d'IDs** : 586 à 761+
- **Types d'appareils** : Trottinette, Informatique, iPhone, iPad
- **Statuts variés** : gardiennage, restitue, annule
- **Dates** : Avril 2025 à Mai 2025
- **Exemples** :
  - iPhone 11 - Remplacement écran (60€)
  - Trottinette Xiaomi M365 - Garde boue/Béquille (40€)
  - iPad A2297 - SAV Vitre tactile (0€)

### 4. SMS Logs (~100+ SMS)
- **Messages types** :
  - Notifications de réception
  - Devis disponibles
  - Rappels de gardiennage
  - Notifications de restitution
- **Période** : Avril-Mai 2025
- **Exemples** :
  ```
  "Bonjour, saber, votre devis pour le Test est disponible"
  "Nous avons bien reçu votre Hitway et nos experts geeks sont déjà à l'œuvre"
  "Ton Test est prêt mais t'attend toujours ! Des frais de gardiennage s'appliquent"
  ```

### 5. Autres Tables Importantes
- **Employés** : Données des techniciens
- **Statuts** : Configuration des statuts de réparation
- **SMS Templates** : Templates de messages
- **Fournisseurs** : Liste des fournisseurs
- **Produits** : Catalogue de pièces

## 🔍 Points d'Attention pour la Migration

### Statuts des Réparations
- **gardiennage** : Réparations terminées en attente de récupération
- **restitue** : Réparations rendues au client
- **annule** : Réparations annulées
- **en_cours** : Réparations en cours

### Dates Importantes
- **date_reception** : Date de dépôt de l'appareil
- **date_modification** : Dernière modification
- **date_gardiennage** : Date de mise en gardiennage
- **date_envoi** : Date d'envoi des SMS

### Intégrité Référentielle
- Vérifier les liens client_id → clients
- Vérifier les liens employe_id → employes
- Vérifier les liens reparation_id → reparations dans SMS

## 📝 Plan de Migration

### Phase 1 : Préparation
1. Analyser les conflits d'IDs potentiels
2. Vérifier la structure des tables cibles
3. Préparer les scripts de migration

### Phase 2 : Migration des Données de Base
1. Clients
2. Employés
3. Utilisateurs (en évitant les doublons)

### Phase 3 : Migration des Données Métier
1. Réparations avec vérification des statuts
2. SMS logs avec liens vers réparations
3. Autres données (fournisseurs, produits, etc.)

### Phase 4 : Vérification
1. Contrôle d'intégrité
2. Test des fonctionnalités
3. Validation des données migrées
