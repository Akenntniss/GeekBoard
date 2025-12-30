-- Création de la table pour gérer les sessions de l'espace abonnement par Token
CREATE TABLE IF NOT EXISTS subscription_sessions (
    token VARCHAR(64) PRIMARY KEY,
    shop_id INT NOT NULL,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- Index pour nettoyer les sessions expirées
CREATE INDEX idx_expires_at ON subscription_sessions(expires_at);
