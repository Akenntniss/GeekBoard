-- Script pour vider les tables de la base geekboard_mkmkmk (adapté aux tables existantes)
-- Préserve : admin dans users, utopya/mobilax dans fournisseurs
-- Date de création : 2025-09-19

-- Désactiver les contraintes de clés étrangères temporairement
SET FOREIGN_KEY_CHECKS = 0;

-- Vider bug_reports (pas bugs_report)
TRUNCATE TABLE bug_reports;

-- Vider cagnotte_historique
TRUNCATE TABLE cagnotte_historique;

-- Vider clients
TRUNCATE TABLE clients;

-- Vider users (sauf admin)
DELETE FROM users WHERE username != 'admin' AND role != 'admin';

-- Vider commandes_pieces
TRUNCATE TABLE commandes_pieces;

-- Vider devis et tables associées
TRUNCATE TABLE devis_acceptations;
TRUNCATE TABLE devis_logs;
TRUNCATE TABLE devis_notifications;
TRUNCATE TABLE devis_pannes;
TRUNCATE TABLE devis_solutions_items;
TRUNCATE TABLE devis_solutions;
TRUNCATE TABLE devis_templates;
TRUNCATE TABLE devis;

-- Vider fournisseurs (sauf utopya et mobilax)
DELETE FROM fournisseurs WHERE nom NOT IN ('utopya', 'mobilax', 'Utopya', 'Mobilax', 'UTOPYA', 'MOBILAX');

-- Vider kb (knowledge base) tables
TRUNCATE TABLE kb_article_ratings;
TRUNCATE TABLE kb_article_tags;
TRUNCATE TABLE kb_files;
TRUNCATE TABLE kb_articles;
TRUNCATE TABLE kb_categories;
TRUNCATE TABLE kb_tags;

-- Vider marges_reference
TRUNCATE TABLE marges_reference;

-- Vider missions et tables associées
TRUNCATE TABLE mission_validations;
TRUNCATE TABLE missions;
TRUNCATE TABLE mission_types;

-- Vider notification_types
TRUNCATE TABLE notification_types;

-- Vider paiements_sumup
TRUNCATE TABLE paiements_sumup;

-- Vider partenaires et tables associées
TRUNCATE TABLE partner_transactions_pending;
TRUNCATE TABLE services_partenaires;
TRUNCATE TABLE soldes_partenaires;
TRUNCATE TABLE transactions_partenaires;
TRUNCATE TABLE partenaires;

-- Vider photos_reparation
TRUNCATE TABLE photos_reparation;

-- Vider presence tables
TRUNCATE TABLE presence_events;
TRUNCATE TABLE presence_types;

-- Vider produits
TRUNCATE TABLE produits;

-- Vider rachat_appareils
TRUNCATE TABLE rachat_appareils;

-- Vider reparations et tables associées
TRUNCATE TABLE reparation_logs;
TRUNCATE TABLE reparation_sms;
TRUNCATE TABLE reparations;

-- Vider sms tables
TRUNCATE TABLE sms_campaign_details;
TRUNCATE TABLE sms_campaigns;
TRUNCATE TABLE sms_logs;

-- Vider taches et attachments
TRUNCATE TABLE tache_attachments;
TRUNCATE TABLE taches;

-- Vider user_preferences
TRUNCATE TABLE user_preferences;

-- Vider wifi_authorized_ssids
TRUNCATE TABLE wifi_authorized_ssids;

-- Réactiver les contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- Message de confirmation
SELECT 'VIDAGE DES TABLES TERMINÉ - Préservé: admin dans users, utopya/mobilax dans fournisseurs' AS status;
