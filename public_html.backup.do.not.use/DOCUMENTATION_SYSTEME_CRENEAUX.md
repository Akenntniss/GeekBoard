# 🕒 Système de Créneaux Horaires - GeekBoard

## 📋 Vue d'ensemble

Le système de créneaux horaires permet de gérer automatiquement l'approbation des pointages en fonction d'horaires prédéfinis, évitant ainsi les abus de pointage et simplifiant la gestion administrative.

## 🎯 Fonctionnalités principales

### ✅ Approbation automatique
- **Pointages dans les créneaux** → Approuvés automatiquement
- **Pointages hors créneaux** → Demandent une approbation manuelle
- **Système de priorité** : Créneaux spécifiques > Créneaux globaux

### ⚙️ Configuration flexible
- **Créneaux globaux** : Horaires par défaut pour tous les employés
- **Créneaux spécifiques** : Horaires personnalisés par employé
- **Deux périodes** : Matin et Après-midi

## 📊 Interface d'administration

### Navigation
L'interface contient **6 onglets** :

1. **📊 Dashboard** - Vue d'ensemble et analytics
2. **📡 Temps Réel** - Employés actuellement pointés
3. **📅 Calendrier** - Historique avec filtres
4. **✅ Demandes à approuver** - Gestion des validations
5. **⚙️ Paramètres** - Configuration des créneaux *(NOUVEAU)*
6. **🔔 Alertes** - Notifications et avertissements

### Onglet Paramètres

#### Créneaux globaux
```
┌─────────────────────────────────────────┐
│  🌅 Matin: [08:00] - [12:30]            │
│  🌆 Après-midi: [14:00] - [19:00]       │
│  [💾 Sauvegarder]                       │
└─────────────────────────────────────────┘
```

#### Créneaux spécifiques
```
┌─────────────────────────────────────────┐
│  [Employé▼] [08:30] [12:00] [14:30] [18:00] [+] │
│                                         │
│  👤 Jean Dupont                         │
│  🌅 08:30-12:00 🌆 14:30-18:00 [🗑️]      │
└─────────────────────────────────────────┘
```

## 🗄️ Structure de base de données

### Table `time_slots`
```sql
CREATE TABLE time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,              -- NULL = global, ID = spécifique
    slot_type ENUM('morning', 'afternoon'),
    start_time TIME,
    end_time TIME,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Modifications `time_tracking`
```sql
ALTER TABLE time_tracking ADD COLUMN (
    auto_approved BOOLEAN DEFAULT FALSE,    -- Approuvé automatiquement
    approval_reason VARCHAR(255) NULL       -- Raison de la demande
);
```

## 🔄 Logique de fonctionnement

### Processus d'approbation
```
Pointage reçu
    ↓
Déterminer créneau (matin/après-midi)
    ↓
Chercher règle spécifique utilisateur
    ↓ (si trouvée)
Vérifier si dans le créneau spécifique
    ↓ (si pas trouvée)
Chercher règle globale
    ↓
Vérifier si dans le créneau global
    ↓
Dans créneau ? → Approbation auto ✅
Hors créneau ? → Demande approbation ⚠️
```

### Exemples de scénarios

#### Scénario 1 : Pointage normal
- **Créneaux** : Matin 8h-12h30, A-midi 14h-19h
- **Pointage** : 8h15
- **Résultat** : ✅ Approuvé automatiquement

#### Scénario 2 : Pointage trop tôt
- **Créneaux** : Matin 8h-12h30
- **Pointage** : 7h30
- **Résultat** : ⚠️ Demande d'approbation "Pointage du matin hors créneau autorisé (08:00 - 12:30). Pointé à 07:30"

#### Scénario 3 : Employé avec créneaux spécifiques
- **Global** : 8h-12h30, 14h-19h
- **Spécifique Jean** : 9h-13h, 14h30-18h
- **Pointage Jean** : 8h30
- **Résultat** : ⚠️ Demande d'approbation (hors de SON créneau 9h-13h)

## 📝 Guide d'utilisation

### Configuration initiale

1. **Accéder à l'onglet Paramètres**
2. **Configurer les créneaux globaux** (horaires par défaut)
3. **Ajouter des créneaux spécifiques** si nécessaire

### Gestion quotidienne

1. **Consulter l'onglet "Demandes à approuver"**
2. **Examiner les pointages hors créneaux**
3. **Approuver ou rejeter** selon le contexte

### Actions disponibles

#### Dans "Demandes à approuver"
- **✅ Approuver** : Valider le pointage
- **❌ Rejeter** : Refuser avec raison
- **👁️ Voir détails** : Informations complètes

#### Dans "Paramètres"
- **💾 Sauvegarder globaux** : Modifier les créneaux par défaut
- **➕ Ajouter spécifique** : Créer des horaires personnalisés
- **🗑️ Supprimer spécifique** : Retour aux créneaux globaux

## 🚨 Notifications et alertes

### Types d'alertes
- **⚠️ Hors créneaux** : Pointage nécessitant approbation
- **⏰ Heures supplémentaires** : Travail prolongé
- **🔔 Notifications** : Messages aux employés

### Interface visuelle
- **Badges rouges** : Nombre de demandes en attente
- **Cartes colorées** : Status visuel des pointages
- **Animations** : Alertes urgentes (heures supplémentaires)

## 🔧 Intégration technique

### API endpoints
```php
POST ?page=admin_timetracking
- action=save_global_slots     // Sauvegarder créneaux globaux
- action=save_user_slots       // Sauvegarder créneaux utilisateur
- action=remove_user_slots     // Supprimer créneaux utilisateur
- action=approve_entry         // Approuver une demande
- action=reject_entry          // Rejeter une demande
```

### Fonctions principales
```php
processTimeTrackingApproval($user_id, $clock_time, $shop_pdo)
createTimeTrackingWithApproval($user_id, $clock_in, $data, $shop_pdo)
updateTimeTrackingWithApproval($tracking_id, $shop_pdo)
```

## 📈 Avantages du système

### Pour les administrateurs
- ✅ **Réduction des tâches** : Approbation automatique
- ✅ **Contrôle des abus** : Détection des pointages anormaux
- ✅ **Flexibilité** : Règles globales et spécifiques
- ✅ **Traçabilité** : Historique des approbations

### Pour les employés
- ✅ **Transparence** : Règles claires
- ✅ **Autonomie** : Pointages automatiques dans les créneaux
- ✅ **Feedback** : Information sur les approbations

## 🛠️ Maintenance et support

### Fichiers importants
- `admin_timetracking.php` - Interface principale
- `create_time_slots_table.sql` - Script de création
- `time_tracking_auto_approval.php` - Logique d'approbation

### Base de données
- Table `time_slots` - Configuration des créneaux
- Table `time_tracking` - Pointages avec statuts d'approbation

### Logs et debugging
- Colonnes `approval_reason` pour diagnostic
- Séparation `auto_approved` / `admin_approved`
- Timestamps pour traçabilité

---

## 🎯 Exemple de workflow complet

1. **Admin configure** : Créneaux matin 8h-12h30, après-midi 14h-19h
2. **Employé pointe** : 8h15 → ✅ Approuvé auto
3. **Employé pointe** : 7h30 → ⚠️ Demande d'approbation
4. **Admin examine** : Raison valable → ✅ Approuve
5. **Système notifie** : Pointage validé

**Résultat** : Gestion efficace et transparente des horaires de travail ! 🎉
