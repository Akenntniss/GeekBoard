# 📧 Système de Contact SERVO - Guide d'Administration

## 🎯 Présentation

Le système de contact pour servo.tools est maintenant opérationnel avec :
- ✅ Formulaire de contact sur https://servo.tools/contact
- ✅ Stockage des soumissions en base de données
- ✅ Envoi d'emails via SMTP Hostinger
- ✅ Interface d'administration pour gérer les contacts
- ✅ Emails de confirmation automatiques

---

## 🔗 URLs d'Administration

### Pages principales :
- **Dashboard Admin** : https://servo.tools/admin/
- **Voir les soumissions** : https://servo.tools/admin/contact_submissions.php
- **Tester les emails** : https://servo.tools/admin/test_email.php

---

## 📊 Fonctionnalités

### 1. **Formulaire de Contact**
- Localisation : https://servo.tools/contact
- Champs : Prénom, Nom, Email, Téléphone, Entreprise, Employés, Réparations/mois, Sujet, Message
- Validation côté client et serveur
- Sauvegarde automatique en base de données

### 2. **Emails Automatiques**
#### Email à l'équipe (`contact@maisondugeek.fr`)
- Notification instantanée de nouveau contact
- Template HTML professionnel
- Toutes les informations du formulaire
- Bouton "Répondre" direct

#### Email de confirmation au client
- Confirmation de réception de la demande
- Informations sur les prochaines étapes
- Contact direct de l'équipe
- Design professionnel SERVO

### 3. **Interface d'Administration**
- **Dashboard** : Statistiques et aperçu
- **Liste des soumissions** : Recherche, filtres, pagination
- **Détails des contacts** : Vue complète avec actions rapides
- **Test emails** : Vérification de la configuration SMTP

---

## ⚙️ Configuration Technique

### Configuration SMTP (Hostinger)
```php
Serveur : smtp.hostinger.com
Port : 465
Encryption : SSL
Email : servo@maisondugeek.fr
Mot de passe : Merguez01#
```

### Emails configurés :
- **Expéditeur** : servo@maisondugeek.fr
- **Destinataire notifications** : contact@maisondugeek.fr
- **Reply-To** : contact@maisondugeek.fr

### Base de données :
- Table : `contact_requests` dans la base principale
- Indexation sur email et date de création
- Stockage IP et User-Agent pour sécurité

---

## 🚀 Comment utiliser

### Pour voir les nouvelles demandes :
1. Aller sur https://servo.tools/admin/
2. Consulter le dashboard pour les statistiques
3. Cliquer sur "Voir les soumissions" pour la liste complète

### Pour rechercher un contact :
1. Aller sur la page des soumissions
2. Utiliser la barre de recherche (nom, email, entreprise)
3. Filtrer par date si nécessaire

### Pour répondre à un contact :
1. Cliquer sur l'icône "👁️" pour voir les détails
2. Cliquer sur "Répondre par email" dans la modal
3. Ou utiliser l'icône "↩️" pour répondre directement

### Pour tester le système email :
1. Aller sur https://servo.tools/admin/test_email.php
2. Saisir votre email de test
3. Choisir le type de test (simple, notification, confirmation)
4. Cliquer sur "Envoyer le test"

---

## 📈 Statistiques Disponibles

Le dashboard affiche automatiquement :
- **Total contacts** : Nombre total de demandes reçues
- **Aujourd'hui** : Demandes reçues aujourd'hui
- **7 derniers jours** : Demandes de la semaine
- **30 derniers jours** : Demandes du mois

---

## 🔧 Maintenance

### Vérifications régulières :
1. **Test emails** : Vérifier que les emails partent bien
2. **Espace disque** : Les logs peuvent s'accumuler
3. **Performance** : Archiver les anciens contacts si nécessaire

### En cas de problème :
1. Vérifier les logs PHP du serveur
2. Tester la configuration SMTP
3. Vérifier que la base de données est accessible
4. Contrôler les permissions des fichiers

---

## 📞 Support

En cas de problème technique :
- Contacter l'administrateur système
- Vérifier les logs dans `/var/log/` sur le serveur
- Utiliser la page de test pour diagnostiquer

---

## 📝 Historique des Modifications

**Version 1.0 - 22/09/2025**
- ✅ Configuration initiale du système
- ✅ Installation PHPMailer
- ✅ Configuration SMTP Hostinger
- ✅ Interface d'administration complète
- ✅ Templates d'emails professionnels
- ✅ Système de recherche et filtres

---

*Système développé pour SERVO by Maison Du Geek*
