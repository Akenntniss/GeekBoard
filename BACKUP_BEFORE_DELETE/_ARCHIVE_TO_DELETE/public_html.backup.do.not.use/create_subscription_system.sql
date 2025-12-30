-- Création du système d'abonnement et d'essai gratuit pour GeekBoard
-- Base de données : geekboard_general

-- Table des plans d'abonnement
CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'EUR',
    billing_period ENUM('monthly', 'yearly') NOT NULL DEFAULT 'monthly',
    features JSON,
    sms_credits INT DEFAULT 0,
    max_users INT DEFAULT 1,
    storage_gb INT DEFAULT 5,
    active TINYINT(1) DEFAULT 1,
    stripe_price_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des abonnements actifs
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('trial', 'active', 'cancelled', 'expired', 'past_due') NOT NULL DEFAULT 'trial',
    trial_start_date TIMESTAMP NULL,
    trial_end_date TIMESTAMP NULL,
    current_period_start TIMESTAMP NULL,
    current_period_end TIMESTAMP NULL,
    stripe_subscription_id VARCHAR(100) NULL,
    stripe_customer_id VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

-- Table des transactions de paiement
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    status ENUM('pending', 'succeeded', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    stripe_payment_intent_id VARCHAR(100) NULL,
    stripe_invoice_id VARCHAR(100) NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
);

-- Ajouter des colonnes à la table shops existante pour le système d'abonnement
ALTER TABLE shops 
ADD COLUMN trial_started_at TIMESTAMP NULL AFTER updated_at,
ADD COLUMN trial_ends_at TIMESTAMP NULL AFTER trial_started_at,
ADD COLUMN subscription_status ENUM('trial', 'active', 'cancelled', 'expired', 'past_due') DEFAULT 'trial' AFTER trial_ends_at;

-- Ajouter index pour optimiser les requêtes
CREATE INDEX idx_shops_subscription_status ON shops(subscription_status);
CREATE INDEX idx_shops_trial_ends_at ON shops(trial_ends_at);
CREATE INDEX idx_subscriptions_status ON subscriptions(status);
CREATE INDEX idx_subscriptions_shop_id ON subscriptions(shop_id);

-- Insertion des plans d'abonnement par défaut
INSERT INTO subscription_plans (name, description, price, billing_period, features, sms_credits, max_users, storage_gb) VALUES
('Starter', 'Plan idéal pour débuter avec toutes les fonctionnalités essentielles', 29.99, 'monthly', 
 '["Gestion des réparations", "SMS automatiques", "Stocks", "Pointage employés", "Rapports de base"]', 
 500, 3, 10),

('Professional', 'Plan complet pour ateliers en croissance', 59.99, 'monthly',
 '["Toutes fonctionnalités Starter", "SMS illimités", "Multi-boutiques", "Rapports avancés", "API", "Support prioritaire"]',
 -1, 10, 50),

('Enterprise', 'Solution sur mesure pour grandes entreprises', 149.99, 'monthly',
 '["Toutes fonctionnalités Professional", "Formation personnalisée", "Intégrations sur mesure", "Support dédié", "SLA garantie"]',
 -1, -1, 200),

('Starter Annual', 'Plan Starter avec remise annuelle', 299.99, 'yearly',
 '["Gestion des réparations", "SMS automatiques", "Stocks", "Pointage employés", "Rapports de base"]',
 500, 3, 10),

('Professional Annual', 'Plan Professional avec remise annuelle', 599.99, 'yearly',
 '["Toutes fonctionnalités Starter", "SMS illimités", "Multi-boutiques", "Rapports avancés", "API", "Support prioritaire"]',
 -1, 10, 50);

-- Procédure pour initialiser l'essai gratuit d'un nouveau shop
DELIMITER //
CREATE PROCEDURE InitializeTrialPeriod(IN shop_id INT)
BEGIN
    DECLARE trial_end TIMESTAMP DEFAULT DATE_ADD(NOW(), INTERVAL 30 DAY);
    
    -- Mettre à jour le shop avec les dates d'essai
    UPDATE shops 
    SET trial_started_at = NOW(),
        trial_ends_at = trial_end,
        subscription_status = 'trial',
        active = 1
    WHERE id = shop_id;
    
    -- Créer l'enregistrement de subscription en mode trial
    INSERT INTO subscriptions (shop_id, plan_id, status, trial_start_date, trial_end_date)
    VALUES (shop_id, 2, 'trial', NOW(), trial_end); -- Plan 2 = Professional pour l'essai
    
END //
DELIMITER ;

-- Procédure pour vérifier et désactiver les essais expirés
DELIMITER //
CREATE PROCEDURE CheckExpiredTrials()
BEGIN
    -- Désactiver les shops dont l'essai a expiré
    UPDATE shops 
    SET active = 0, 
        subscription_status = 'expired'
    WHERE subscription_status = 'trial' 
    AND trial_ends_at < NOW() 
    AND active = 1;
    
    -- Mettre à jour les subscriptions expirées
    UPDATE subscriptions 
    SET status = 'expired'
    WHERE status = 'trial' 
    AND trial_end_date < NOW();
    
END //
DELIMITER ;

-- Vue pour obtenir facilement les informations d'abonnement d'un shop
CREATE VIEW shop_subscription_info AS
SELECT 
    s.id as shop_id,
    s.name as shop_name,
    s.subdomain,
    s.active,
    s.subscription_status,
    s.trial_started_at,
    s.trial_ends_at,
    DATEDIFF(s.trial_ends_at, NOW()) as days_remaining,
    sub.id as subscription_id,
    sp.name as plan_name,
    sp.price as plan_price,
    sp.billing_period,
    sub.current_period_start,
    sub.current_period_end
FROM shops s
LEFT JOIN subscriptions sub ON s.id = sub.shop_id AND sub.status IN ('trial', 'active')
LEFT JOIN subscription_plans sp ON sub.plan_id = sp.id;
