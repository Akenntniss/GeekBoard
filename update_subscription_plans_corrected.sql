-- Mise à jour des plans d'abonnement selon les nouvelles spécifications
-- Base de données : geekboard_general

USE geekboard_general;

-- Mise à jour du plan Starter
UPDATE subscription_plans 
SET 
    name = 'Starter',
    description = 'Plan idéal pour débuter avec toutes les fonctionnalités essentielles',
    price = 40.00,
    features = '["Gestion des réparations", "SMS automatiques", "Stocks", "Rapport avancé", "Support Email"]',
    sms_credits = 100,
    max_users = 1
WHERE id = 1;

-- Mise à jour du plan Professional  
UPDATE subscription_plans 
SET 
    name = 'Professional',
    description = 'Plan complet pour ateliers en croissance',
    price = 50.00,
    features = '["Toutes fonctionnalités Starter", "Pointage employés", "Base de connaissance", "API Recherche fournisseur", "Support prioritaire"]',
    sms_credits = 250,
    max_users = 3
WHERE id = 2;

-- Mise à jour du plan Enterprise
UPDATE subscription_plans 
SET 
    name = 'Enterprise',
    description = 'Solution sur mesure pour grandes entreprises',
    price = 60.00,
    features = '["Toutes fonctionnalités Professional", "Gestion RH", "Formation personnalisée", "Intégrations sur mesure", "Support dédié"]',
    sms_credits = 500,
    max_users = 15
WHERE id = 3;

-- Vérification des modifications
SELECT id, name, price, sms_credits, max_users, features 
FROM subscription_plans 
WHERE id IN (1, 2, 3);
