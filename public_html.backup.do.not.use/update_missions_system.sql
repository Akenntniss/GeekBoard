-- Mise à jour du système de missions GeekBoard
-- Ajout de la gestion des cagnottes et des photos

-- Ajouter la colonne cagnotte à la table utilisateurs
ALTER TABLE utilisateurs ADD COLUMN cagnotte DECIMAL(10,2) DEFAULT 0.00;

-- Ajouter la colonne photo à la table mission_validations
ALTER TABLE mission_validations ADD COLUMN photo_url VARCHAR(255) DEFAULT NULL;

-- Ajouter le statut par défaut à 'en_attente' pour les validations
ALTER TABLE mission_validations MODIFY statut ENUM('en_attente', 'approuve', 'rejete') DEFAULT 'en_attente';

-- Créer une table pour l'historique des cagnottes
CREATE TABLE IF NOT EXISTS cagnotte_historique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    type ENUM('credit', 'debit') NOT NULL,
    description TEXT,
    mission_id INT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (mission_id) REFERENCES missions(id),
    FOREIGN KEY (admin_id) REFERENCES utilisateurs(id)
);

-- Créer des index pour optimiser les performances
CREATE INDEX idx_cagnotte_user ON cagnotte_historique(user_id);
CREATE INDEX idx_cagnotte_date ON cagnotte_historique(date_creation);
CREATE INDEX idx_mission_validations_user ON mission_validations(user_mission_id);

-- Insérer des données de test pour les cagnottes
UPDATE utilisateurs SET cagnotte = 0.00 WHERE cagnotte IS NULL; 