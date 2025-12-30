-- Table pour les créneaux horaires
CREATE TABLE IF NOT EXISTS time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,  -- NULL = règle globale, valeur = règle spécifique à un utilisateur
    slot_type ENUM('morning', 'afternoon') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_slot (user_id, slot_type)
);

-- Insérer des créneaux par défaut
INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES
(NULL, 'morning', '08:00:00', '12:30:00', TRUE),
(NULL, 'afternoon', '14:00:00', '19:00:00', TRUE)
ON DUPLICATE KEY UPDATE 
start_time = VALUES(start_time),
end_time = VALUES(end_time);

-- Modifier la table time_tracking pour ajouter une colonne auto_approved
ALTER TABLE time_tracking 
ADD COLUMN auto_approved BOOLEAN DEFAULT FALSE AFTER admin_approved,
ADD COLUMN approval_reason VARCHAR(255) NULL AFTER auto_approved;

-- Index pour optimiser les requêtes
CREATE INDEX idx_time_slots_user_type ON time_slots(user_id, slot_type, is_active);
CREATE INDEX idx_time_tracking_approval ON time_tracking(admin_approved, auto_approved, status);
