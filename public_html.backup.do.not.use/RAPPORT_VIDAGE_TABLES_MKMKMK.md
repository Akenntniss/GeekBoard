# Rapport de Vidage des Tables - Base geekboard_mkmkmk

## Informations Générales
- **Date d'exécution** : 19 septembre 2025
- **Serveur** : 82.29.168.205
- **Base de données** : geekboard_mkmkmk
- **Statut** : ✅ RÉUSSI

## Tables Vidées avec Succès

### 1. Tables Système et Bugs
- ✅ `bug_reports` (était `bugs_report` dans la demande - table corrigée)

### 2. Tables Clients et Utilisateurs
- ✅ `cagnotte_historique`
- ✅ `clients`
- ✅ `users` (SAUF admin - préservé)

### 3. Tables Commandes et Devis
- ✅ `commandes_pieces`
- ✅ `devis`
- ✅ `devis_acceptations`
- ✅ `devis_logs`
- ✅ `devis_notifications`
- ✅ `devis_pannes`
- ✅ `devis_solutions`
- ✅ `devis_solutions_items`
- ✅ `devis_templates`

### 4. Tables Fournisseurs
- ✅ `fournisseurs` (SAUF Utopya et Mobilax - préservés)

### 5. Tables Knowledge Base
- ✅ `kb_articles`
- ✅ `kb_article_ratings`
- ✅ `kb_article_tags`
- ✅ `kb_categories`
- ✅ `kb_files`
- ✅ `kb_tags`

### 6. Tables Marges et Missions
- ✅ `marges_reference`
- ✅ `missions`
- ✅ `mission_types`
- ✅ `mission_validations`

### 7. Tables Notifications et Paiements
- ✅ `notification_types`
- ✅ `paiements_sumup`

### 8. Tables Partenaires
- ✅ `partenaires`
- ✅ `partner_transactions_pending`
- ✅ `services_partenaires`
- ✅ `soldes_partenaires`
- ✅ `transactions_partenaires`

### 9. Tables Photos et Présence
- ✅ `photos_reparation`
- ✅ `presence_events`
- ✅ `presence_types`

### 10. Tables Produits et Rachats
- ✅ `produits`
- ✅ `rachat_appareils`

### 11. Tables Réparations
- ✅ `reparations`
- ✅ `reparation_logs`
- ✅ `reparation_sms`

### 12. Tables SMS
- ✅ `sms_campaigns`
- ✅ `sms_campaign_details`
- ✅ `sms_logs`

### 13. Tables Tâches
- ✅ `taches`
- ✅ `tache_attachments`

### 14. Tables Utilisateurs et WiFi
- ✅ `user_preferences`
- ✅ `wifi_authorized_ssids`

## Éléments Préservés

### Utilisateurs
- **admin** (role: admin) - ✅ PRÉSERVÉ

### Fournisseurs
- **Utopya** - ✅ PRÉSERVÉ
- **Mobilax** - ✅ PRÉSERVÉ

## Vérifications Post-Exécution

### Comptages de Vérification
- **Clients** : 0 (vidé avec succès)
- **Users** : 1 (admin préservé)
- **Fournisseurs** : 2 (Utopya et Mobilax préservés)

## Script SQL Utilisé
- **Fichier local** : `/Users/admin/Documents/GeekBoard/vider_tables_mkmkmk_corrected.sql`
- **Options de sécurité** : Désactivation temporaire des contraintes de clés étrangères
- **Méthode** : TRUNCATE pour les tables complètes, DELETE avec conditions pour les exceptions

## Commandes Exécutées

```bash
# Upload du script
sshpass -p "Mamanmaman01#" scp -o StrictHostKeyChecking=no /Users/admin/Documents/GeekBoard/vider_tables_mkmkmk_corrected.sql root@82.29.168.205:/tmp/

# Exécution du script
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "mysql -u root -pMamanmaman01# geekboard_mkmkmk < /tmp/vider_tables_mkmkmk_corrected.sql"

# Vérifications
sshpass -p "Mamanmaman01#" ssh -o StrictHostKeyChecking=no root@82.29.168.205 "mysql -u root -pMamanmaman01# geekboard_mkmkmk -e 'SELECT COUNT(*) FROM clients; SELECT COUNT(*) FROM users; SELECT COUNT(*) FROM fournisseurs;'"
```

## Notes Importantes

1. **Correction effectuée** : La table `bugs_report` demandée n'existait pas, corrigée en `bug_reports`
2. **Sécurité** : Contraintes de clés étrangères désactivées pendant l'opération
3. **Intégrité** : Toutes les exceptions demandées ont été respectées
4. **Nettoyage** : Fichiers temporaires supprimés du serveur après exécution

## Statut Final
🎯 **OPÉRATION TERMINÉE AVEC SUCCÈS**

Toutes les tables demandées ont été vidées en préservant les éléments spécifiés (admin et fournisseurs Utopya/Mobilax).
