-- Mise à jour finale des plans d'abonnement avec les nouveaux prix et fonctionnalités
-- Base de données : geekboard_general

USE geekboard_general;

-- Mise à jour du plan Starter
UPDATE subscription_plans 
SET 
    name = 'Starter',
    description = 'Parfait pour débuter',
    price = 39.99,
    features = '["1 boutique", "SMS automatiques (100 inclus)", "Gestion stock basique", "Devis & facturation", "Pointage QR code", "Support email"]',
    sms_credits = 100,
    max_users = 3
WHERE id = 1;

-- Mise à jour du plan Professional  
UPDATE subscription_plans 
SET 
    name = 'Pro',
    description = 'Pour ateliers en croissance',
    price = 49.99,
    features = '["1 boutique", "SMS automatiques (250 inclus)", "Gestion stock avancée", "Analytics & rapports", "Portail client avancé", "Intégrations (Stripe, compta)", "Support téléphonique"]',
    sms_credits = 250,
    max_users = 5
WHERE id = 2;

-- Mise à jour du plan Enterprise
UPDATE subscription_plans 
SET 
    name = 'Entreprise',
    description = 'Solution complète avancée',
    price = 59.99,
    features = '["1 boutique", "SMS automatiques (500 inclus)", "Gestion stock avancée", "Analytics & rapports avancés", "Portail client premium", "API personnalisée", "Support prioritaire"]',
    sms_credits = 500,
    max_users = -1
WHERE id = 3;

-- Vérification des modifications
SELECT id, name, description, price, sms_credits, max_users, features 
FROM subscription_plans 
WHERE id IN (1, 2, 3);
