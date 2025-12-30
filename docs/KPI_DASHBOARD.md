# Dashboard KPI GeekBoard - Documentation Complète

## 📊 Vue d'ensemble

Le Dashboard KPI est un système complet d'analyse de performance pour GeekBoard, intégrant :
- **Analytics avancées** : CA, réparations, gardiennage, temps, autonomie
- **IA contextuelle** : 8 profils d'experts avec analyses personnalisées
- **Notes contextuelles** : Employés et magasin pour enrichir les analyses

## 🗂️ Architecture des Fichiers

### Backend - APIs

| Fichier | Description |
|---------|-------------|
| `kpi_api.php` | API principale - toutes les analyses KPI |
| `ajax/employee_notes_api.php` | CRUD notes employés |
| `ajax/shop_notes_api.php` | CRUD notes magasin |
| `ajax/get_ai_profiles.php` | Liste des profils IA actifs |
| `ajax/generate_ai_analysis.php` | Génération analyses IA |
| `ajax/manage_ai_profiles.php` | CRUD profils IA |
| `includes/kpi_ai_analysis.php` | Moteur d'analyse IA (Groq) |

### Frontend

| Fichier | Description |
|---------|-------------|
| `pages/kpi_dashboard.php` | Page principale avec 4 onglets |
| `assets/js/kpi_dashboard.js` | JavaScript complet du dashboard |
| `includes/kpi_modals.php` | Modals Bootstrap pour formulaires |

### Base de Données

```sql
-- Tables créées
kpi_ai_profiles      -- Profils d'experts IA (8 par défaut)
employee_notes       -- Notes contextuelles employés
shop_notes           -- Notes contextuelles magasin
```

## 🔑 Fonctionnalités Principales

### Onglet 1 : Dashboard KPI

**Filtres :**
- Employé (si admin)
- Date début / Date fin (défaut : 30 derniers jours)

**KPI Cards :**
- CA Encaissé (réparations restituées)
- CA Total (encaissé + à encaisser)
- Nombre de réparations
- Gardiennage actif

**Graphiques :**
- Évolution du CA (Chart.js line)
- Répartition réparations (Chart.js doughnut)

**Tableaux :**
- Performance par employé (CA, réparations, panier moyen, autonomie)

**Analyses IA :**
- Accordéon avec 8 profils d'experts
- Génération à la demande
- Contexte automatique (KPI + notes employés + notes magasin)

### Onglet 2 : Notes Employés (Admin uniquement)

**Fonctionnalités :**
- ✅ Ajouter une note
- ✏️ Modifier une note
- 🗑️ Supprimer une note
- 🔍 Filtrer par employé/type/gravité

**Types de notes :**
- 🚨 Avertissement
- ⚠️ Incident
- 👍 Appréciation
- 📌 Remarque
- 🔴 Sanction
- 📋 Autre

**Niveaux de gravité :**
- ℹ️ Info | ⚡ Faible | ⚠️ Moyen | 🔴 Élevé | 🚨 Critique

**Options :**
- Inclure dans l'analyse IA
- Note privée (visible admins uniquement)
- Marquer comme résolu

### Onglet 3 : Notes Magasin (Admin uniquement)

**Fonctionnalités :**
- ✅ Ajouter un événement
- ✏️ Modifier un événement
- 🗑️ Supprimer un événement
- 📅 Timeline des événements

**Types d'événements :**
- 🚪 Fermeture
- 🛠️ Travaux
- 🎉 Événement
- ⚡ Problème technique
- 📦 Stock/Approvisionnement
- 📋 Autre

**Options :**
- Période (date début → date fin)
- Niveau d'impact
- Affecte les KPI
- Inclure dans l'analyse IA

### Onglet 4 : Profils IA (Admin uniquement)

**Gestion des profils d'experts :**
- ✅ Créer un profil personnalisé
- ✏️ Modifier un profil
- 🗑️ Supprimer un profil (sauf les 8 par défaut)
- ✅/❌ Activer/Désactiver
- 🔄 Dupliquer

**8 Profils par Défaut :**
1. Expert Gestion Entreprise
2. Expert Ventes
3. Expert Comptable
4. Manager Constructif
5. Coach Motivant
6. Manager Critique
7. Directeur
8. Analyste Comportemental

## 🔌 API Endpoints

### KPI API (`kpi_api.php`)

```
GET /kpi_api.php?action=chiffre_affaires_global&date_start=...&date_end=...
GET /kpi_api.php?action=chiffre_affaires_employe&user_id=...
GET /kpi_api.php?action=kpi_reparations
GET /kpi_api.php?action=analyse_comportement
GET /kpi_api.php?action=analyse_temps
GET /kpi_api.php?action=analyse_autonomie
GET /kpi_api.php?action=analyse_gardiennage
GET /kpi_api.php?action=panier_moyen
```

### Notes Employés API

```
GET  /ajax/employee_notes_api.php?action=get_notes&employee_id=...&type=...
GET  /ajax/employee_notes_api.php?action=get_note&id=...
POST /ajax/employee_notes_api.php (action=create_note, form data)
POST /ajax/employee_notes_api.php (action=update_note, form data)
POST /ajax/employee_notes_api.php (action=delete_note, id=...)
GET  /ajax/employee_notes_api.php?action=get_employee_context&employee_id=...
```

### Notes Magasin API

```
GET  /ajax/shop_notes_api.php?action=get_notes
GET  /ajax/shop_notes_api.php?action=get_active_notes&date_start=...&date_end=...
POST /ajax/shop_notes_api.php (action=create_note, form data)
POST /ajax/shop_notes_api.php (action=update_note, form data)
POST /ajax/shop_notes_api.php (action=delete_note, id=...)
GET  /ajax/shop_notes_api.php?action=get_shop_context&date_start=...&date_end=...
```

### Profils IA API

```
GET  /ajax/get_ai_profiles.php
POST /ajax/manage_ai_profiles.php (action=create, form data)
POST /ajax/manage_ai_profiles.php (action=update, form data)
POST /ajax/manage_ai_profiles.php (action=delete, id=...)
POST /ajax/generate_ai_analysis.php (profile_id, kpi_data, employee_id, dates)
```

## 🤖 Système d'Analyse IA

### Fonctionnement

1. **Récupération des données KPI** : CA, réparations, temps, autonomie, gardiennage
2. **Récupération du contexte employé** : Notes managériales si analyse par employé
3. **Récupération du contexte magasin** : Événements de la période analysée
4. **Construction du prompt** : Combinaison profil + KPI + contextes
5. **Appel API Groq** : Modèle `llama-3.1-8b-instant`
6. **Parsing et affichage** : Formatage markdown → HTML

### Format du Contexte IA

```
=== DONNÉES KPI ===
CHIFFRE D'AFFAIRES:
  - CA encaiss é: 12500€
  - CA total: 15000€
  ...

=== CONTEXTE MANAGÉRIAL EMPLOYÉ ===
[AVERTISSEMENT - GRAVITÉ MOYENNE - 22/06/2024]
Retard fréquent malgré l'avertissement oral
...

=== CONTEXTE MAGASIN ===
[TRAVAUX - IMPACT ÉLEVÉ - 15/06/2024 au 19/06/2024]
Fermeture complète du magasin à cause des travaux
⮕ Impact: Aucune activité pendant 4 jours
...
```

## 🎨 Design & UI

**Framework CSS** : Bootstrap 5.3.3
**Graphiques** : Chart.js 4.4.0
**Icônes** : Font Awesome 6.5.1
**Typographie** : Inter (Google Fonts)

**Thème** :
- Design SERVO (style GeekBoard)
- Mode nuit compatible
- Responsive mobile
- Animations modernes

## 🔒 Sécurité

- ✅ Authentification requise pour tous les endpoints
- ✅ Rôle admin requis pour notes et profils IA
- ✅ Validation des entrées côté serveur
- ✅ Échappement HTML pour prévenir XSS
- ✅ Profils par défaut non supprimables

## 📝 Statuts de Réparation Utilisés

- `nouvelle_intervention` : Nouvelle réparation
- `en_cours_intervention` : Réparation en cours
- `reparation_effectue` : Réparation terminée (technique)
- `restitue` : Appareil restitué au client (CA encaissé)

## 🚀 Utilisation

### Accès
```
URL: https://mkmkmk.mdgeek.top/index.php?page=kpi_dashboard
Ou: GeekBoard > Menu > Dashboard KPI
```

### Workflow Recommandé

1. **Sélectionner la période** (filtres en haut)
2. **Consulter les KPI cards** (vue d'ensemble instantanée)
3. **Analyser les graphiques** (tendances et répartitions)
4. **Consulter le tableau employés** (performances individuelles)
5. **Générer les analyses IA** (clic sur accordéons ou "Générer toutes")
6. **Ajouter des notes contextuelles** (onglets Notes) pour enrichir futures analyses

### Ajout de Notes Employé

1. Onglet "Notes Employés"
2. Clic "Ajouter une note"
3. Sélectionner employé, type, gravité
4. Remplir titre et description
5. Cocher "Inclure dans analyse IA" si pertinent
6. Enregistrer

### Ajout d'Événement Magasin

1. Onglet "Notes Magasin"
2. Clic "Ajouter un événement"
3. Sélectionner type et niveau d'impact
4. Définir la période (date début → date fin)
5. Cocher "Affecte les KPI" si l'événement explique des variations
6. Enregistrer

### Création de Profil IA Personnalisé

1. Onglet "Profils IA"
2. Clic "Créer un profil"
3. Définir nom, icône, description
4. **Important** : Rédiger un prompt système clair
5. Tester avec générer une analyse
6. Enregistrer si satisfait

**Exemple de prompt** :
```
Tu es un expert en optimisation de flux de travail. 
Analyse les données KPI fournies pour identifier les goulots d'étranglement 
et proposer des solutions concrètes d'amélioration. 
Structure ton rapport en : 1) Analyse des flux, 2) Problèmes identifiés, 
3) Solutions rapides, 4) Plan d'action.
```

## 🐛 Débogage

### Logs
- Logs PHP : Check serveur web
- Logs JS : Console navigateur (F12)
- Réponses API : Network tab (F12)

### Problèmes Courants

**Graphiques ne s'affichent pas** :
- Vérifier que Chart.js est chargé
- Vérifier les données retournées par l'API

**Analyses IA ne se génèrent pas** :
- Vérifier clé API Groq dans `kpi_ai_analysis.php`
- Vérifier logs serveur pour erreurs API
- Tester manuellement l'endpoint

**Notes ne s'enregistrent pas** :
- Vérifier permissions (admin requis)
- Vérifier structure des tables en DB
- Vérifier logs API

## 📊 KPI Calculés

### Chiffre d'Affaires
- **CA Encaissé** : SUM(prix_reparation) WHERE statut='restitue'
- **CA Total** : SUM(prix_reparation) WHERE statut IN ('restitue', 'reparation_effectue')
- **Panier Moyen** : CA / Nombre de réparations

### Attribution Employé
Une réparation est attribuée à un employé SI :
- L'employé a **démarré** la réparation (action='demarrage')
- ET l'employé a **terminé** la réparation (statut_apres='reparation_effectue')

### Gardiennage
- **Appareils actifs** : COUNT(*) WHERE est_actif=1
- **Coût total** : SUM(montant_total)
- **Durée moyenne** : AVG(DATEDIFF(CURDATE(), date_debut))

##  🎯 Améliorations Futures

- [ ] Export PDF des rapports
- [ ] Alertes automatiques (seuils KPI)
- [ ] Comparaison périodes (vs mois précédent)
- [ ] Dashboard temps réel (WebSocket)
- [ ] Analyse prédictive IA
- [ ] Intégration calendrier pour événements magasin
- [ ] Graphiques personnalisables
- [ ] Widgets configurables

## 📞 Support

Pour toute question ou problème :
- Documentation technique : Ce fichier
- Code source : Fichiers listés ci-dessus  
- Logs : Serveur web + console navigateur

---

**Version** : 1.0.0
**Date** : <?php echo date('Y-m-d'); ?>
**Auteur** : GeekBoard Dev Team
