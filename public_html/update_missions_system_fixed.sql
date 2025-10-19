-- Mise à jour du système de missions GeekBoard
-- Ajout de la gestion des cagnottes et des photos

-- Ajouter la colonne cagnotte à la table users
ALTER TABLE users ADD COLUMN cagnotte DECIMAL(10,2) DEFAULT 0.00;

-- Ajouter la colonne photo à la table mission_validations
ALTER TABLE mission_validations ADD COLUMN photo_url VARCHAR(255) DEFAULT NULL;

-- Modifier les missions pour ajouter les colonnes manquantes
ALTER TABLE missions ADD COLUMN priorite INT DEFAULT 2;
ALTER TABLE missions ADD COLUMN nombre_taches INT DEFAULT 1;
ALTER TABLE missions ADD COLUMN actif TINYINT(1) DEFAULT 1;

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
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (mission_id) REFERENCES missions(id),
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- Créer des index pour optimiser les performances
CREATE INDEX idx_cagnotte_user ON cagnotte_historique(user_id);
CREATE INDEX idx_cagnotte_date ON cagnotte_historique(date_creation);
CREATE INDEX idx_mission_validations_user ON mission_validations(user_mission_id);

-- Mettre à jour les données existantes
UPDATE users SET cagnotte = 0.00 WHERE cagnotte IS NULL;

-- Créer un dossier pour les photos de missions
-- Cette commande doit être exécutée depuis le shell : mkdir -p /var/www/mdgeek.top/uploads/missions/ 