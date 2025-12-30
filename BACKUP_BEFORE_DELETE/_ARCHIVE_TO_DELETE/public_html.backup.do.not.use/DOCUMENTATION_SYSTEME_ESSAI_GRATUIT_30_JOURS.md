# **Documentation : Système d'Essai Gratuit 30 Jours SERVO**

## **📋 Vue d'ensemble**

Implémentation complète d'un système d'essai gratuit de 30 jours pour la plateforme SERVO, remplaçant le système de démonstration par un accès complet et sans limitation pendant la période d'essai.

## **🎯 Objectifs Atteints**

- ✅ Suppression de toutes les mentions "Démo gratuite" 
- ✅ Remplacement par "Essai gratuit 30 jours"
- ✅ Système de gestion d'abonnements complet
- ✅ Désactivation automatique après expiration
- ✅ Pages de checkout Stripe (prêtes à configurer)
- ✅ Middleware de vérification des essais
- ✅ Scripts CRON pour maintenance automatique

## **🗄️ Base de Données**

### **Tables Créées**

#### **`subscription_plans`**
Plans d'abonnement disponibles
```sql
- id, name, description, price, currency
- billing_period (monthly/yearly)
- features (JSON), sms_credits, max_users, storage_gb
- stripe_price_id, active, created_at, updated_at
```

#### **`subscriptions`**
Abonnements actifs des boutiques
```sql
- id, shop_id, plan_id, status (trial/active/cancelled/expired)
- trial_start_date, trial_end_date
- current_period_start, current_period_end
- stripe_subscription_id, stripe_customer_id
```

#### **`payment_transactions`**
Historique des paiements
```sql
- id, subscription_id, amount, currency, status
- stripe_payment_intent_id, stripe_invoice_id
- description, created_at, updated_at
```

### **Colonnes Ajoutées à `shops`**
```sql
- trial_started_at (TIMESTAMP)
- trial_ends_at (TIMESTAMP) 
- subscription_status (ENUM: trial/active/cancelled/expired)
```

### **Plans d'Abonnement Configurés**
1. **Starter** - 29,99€/mois - 500 SMS, 3 utilisateurs
2. **Professional** - 59,99€/mois - SMS illimités, 10 utilisateurs *(Recommandé)*
3. **Enterprise** - 149,99€/mois - Tout illimité + support dédié
4. **Plans annuels** avec remises automatiques

## **🔧 Architecture Technique**

### **Classes PHP**

#### **`SubscriptionManager.php`**
Gestionnaire central des abonnements
- `initializeTrialPeriod($shop_id)` : Initialise l'essai 30 jours
- `checkShopSubscriptionStatus($shop_id)` : Vérifier le statut
- `hasAccess($shop_id)` : Vérifier l'accès autorisé
- `deactivateExpiredTrials()` : Désactiver les essais expirés
- `getTrialsExpiringSoon($days)` : Essais expirant bientôt
- `createSubscription()` : Créer abonnement après paiement
- `getAvailablePlans()` : Récupérer les plans

#### **Middleware `trial_check_middleware.php`**
Vérification automatique dans les boutiques
- `checkTrialStatus($shop_id)` : Vérifier statut complet
- `handleTrialExpired()` : Redirection vers abonnement
- `showTrialWarning($days)` : Avertissement expiration proche

### **Scripts CRON**

#### **`cron/check_expired_trials.php`**
À exécuter quotidiennement à 6h :
```bash
0 6 * * * php /var/www/mdgeek.top/cron/check_expired_trials.php
```
- Désactive automatiquement les essais expirés
- Log des actions dans `trial_check.log`
- Préparé pour envoi d'emails de rappel

## **🎨 Modifications Interface**

### **Pages Marketing Mises à Jour**
- **Header** : "Démo gratuite" → "Essai gratuit 30 jours"
- **Footer** : Même changement
- **Home page** : CTA mis à jour vers `/inscription`
- **Toutes mentions "14 jours"** → "30 jours"

### **Page d'Inscription**
- **Hero section** : "30 jours d'essai gratuit complet"
- **Sous-titre** : "Toutes les fonctionnalités, SMS illimités, sans carte bancaire"
- **Points de confiance** :
  - "30 jours gratuits complets"
  - "Aucune CB requise" 
  - "SMS illimités inclus"
- **Intégration automatique** de l'essai lors de la création

### **Nouvelles Pages**

#### **`subscription_required.php`**
Page affichée après expiration de l'essai
- **Message d'expiration** personnalisé
- **Récapitulatif** des fonctionnalités testées
- **Choix d'abonnements** avec tarifs
- **FAQ** abonnements
- **Design cohérent** avec le marketing SERVO

#### **`checkout.php`** 
Page de paiement Stripe (prête à configurer)
- **Informations client** pré-remplies
- **Récapitulatif commande** avec prix TTC
- **Interface Stripe Elements** (à configurer avec vos clés)
- **Calcul automatique** des remises annuelles
- **Design sécurisé** et professionnel

#### **`payment_success.php`**
Page de confirmation après paiement
- **Message de félicitations**
- **Informations abonnement** activé
- **Liens directs** vers la boutique
- **Support et aide** disponibles

## **⚙️ Flux Utilisateur**

### **1. Inscription (Nouveau)**
1. **Visite** `https://mdgeek.top/inscription.php`
2. **Remplissage** du formulaire (sans CB)
3. **Modal de création** avec progression
4. **Boutique créée** avec essai 30 jours automatique
5. **Accès complet** à toutes les fonctionnalités

### **2. Pendant l'Essai**
- **Fonctionnalités complètes** : SMS illimités, utilisateurs multiples
- **Avertissements** 7, 3, 1 jour avant expiration  
- **Liens** vers choix d'abonnement

### **3. Expiration**
- **Désactivation automatique** (CRON + middleware)
- **Redirection** vers `subscription_required.php`
- **Impossibilité de connexion** à la boutique

### **4. Abonnement**
- **Choix du plan** sur `subscription_required.php`
- **Checkout** sur `checkout.php` avec Stripe
- **Activation immédiate** après paiement
- **Toutes données préservées**

## **🔐 Sécurité et Validation**

### **Vérifications Multiples**
- **Middleware** dans chaque boutique
- **CRON quotidien** pour nettoyage
- **Base de données** comme source de vérité
- **Logs détaillés** pour audit

### **Fail-Safe**
- **Mode dégradé** si erreur technique
- **Logs d'erreurs** complets
- **Accès préservé** en cas de problème temporaire

## **💳 Intégration Stripe**

### **Configuration Requise**
```javascript
// À ajouter dans checkout.php
const stripe = Stripe('pk_live_your_publishable_key');

// Webhook endpoints à configurer :
// /webhook/stripe-subscription-created
// /webhook/stripe-payment-succeeded  
// /webhook/stripe-subscription-cancelled
```

### **Variables d'Environnement**
```env
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### **Plans Stripe à Créer**
- Créer les Price IDs dans Stripe
- Mettre à jour `subscription_plans.stripe_price_id`
- Configurer les webhooks pour synchronisation

## **📊 Monitoring et Métriques**

### **KPI à Suivre**
- **Taux conversion** essai → abonnement
- **Durée moyenne** d'utilisation pendant l'essai
- **Plans** les plus choisis
- **Taux d'annulation** par plan

### **Requêtes Utiles**
```sql
-- Essais en cours
SELECT COUNT(*) FROM shops WHERE subscription_status = 'trial' AND active = 1;

-- Essais expirant cette semaine
SELECT * FROM shops WHERE subscription_status = 'trial' 
AND DATEDIFF(trial_ends_at, NOW()) BETWEEN 0 AND 7;

-- Taux de conversion mensuel
SELECT 
  MONTH(trial_started_at) as mois,
  COUNT(*) as essais_commences,
  SUM(CASE WHEN subscription_status = 'active' THEN 1 ELSE 0 END) as convertis
FROM shops 
WHERE trial_started_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY MONTH(trial_started_at);
```

## **🚀 Déploiement**

### **Fichiers Déployés**
- ✅ `inscription.php` (avec système d'essai)
- ✅ `classes/SubscriptionManager.php`
- ✅ `subscription_required.php`
- ✅ `checkout.php`
- ✅ `payment_success.php`
- ✅ `cron/check_expired_trials.php`
- ✅ `includes/trial_check_middleware.php`
- ✅ `marketing/` (pages mises à jour)

### **Base de Données**
- ✅ Tables créées avec données de test
- ✅ Procédures stockées configurées
- ✅ Index optimisés
- ✅ Vue `shop_subscription_info` pour rapports

### **Permissions**
- ✅ `www-data:www-data` sur tous les fichiers
- ✅ Script CRON exécutable
- ✅ Dossiers logs accessibles

## **📝 Prochaines Étapes**

### **1. Configuration Stripe (Requis)**
- Créer les produits et prix dans Stripe
- Configurer les webhooks
- Ajouter les clés API dans l'environnement
- Tester les paiements en mode test

### **2. Intégration Email (Optionnel)**
- Configurer SMTP pour rappels d'expiration  
- Templates d'emails de bienvenue
- Notifications équipe pour nouveaux abonnements

### **3. Analytics (Recommandé)**
- Dashboard admin pour métriques essais
- Rapports automatiques conversion
- Alertes expiration en masse

### **4. Tests Utilisateur**
- Tester workflow complet inscription → expiration
- Valider UX des pages d'abonnement
- Vérifier responsive design

## **⚠️ Important**

### **Sauvegarde**
- **Base principale** sauvegardée avant migration
- **Fichiers anciens** sauvegardés sur serveur
- **Possibilité de rollback** en cas de problème

### **Migration Progressive**
- **Boutiques existantes** : Système d'essai uniquement pour nouvelles
- **Rétrocompatibilité** : Anciennes boutiques non affectées
- **Double vérification** : Ancien et nouveau système coexistent

### **Support**
- **Documentation** complète pour l'équipe
- **Scripts de maintenance** prêts
- **Logs détaillés** pour troubleshooting

---

## **✅ Status Final**

**🎉 SYSTÈME D'ESSAI GRATUIT 30 JOURS DÉPLOYÉ AVEC SUCCÈS**

- **Marketing** : Mis à jour avec "Essai gratuit 30 jours"
- **Inscription** : Intègre automatiquement l'essai
- **Base de données** : Système d'abonnements opérationnel  
- **Pages** : Workflow complet jusqu'au paiement Stripe
- **Automation** : CRON et middleware déployés
- **Prêt** : Configuration Stripe pour finaliser

**Date de déploiement** : 19 septembre 2025  
**Status** : ✅ **OPÉRATIONNEL - Configuration Stripe en attente**
