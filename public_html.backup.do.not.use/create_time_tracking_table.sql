-- Table pour le système de pointage Clock-In/Clock-Out
-- Compatible avec la structure existante de GeekBoard

CREATE TABLE IF NOT EXISTS time_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    clock_in DATETIME NOT NULL,
    clock_out DATETIME NULL,
    break_start DATETIME NULL,
    break_end DATETIME NULL,
    total_hours DECIMAL(5,2) NULL,
    break_duration DECIMAL(5,2) DEFAULT 0.00,
    work_duration DECIMAL(5,2) NULL,
    status ENUM('active', 'completed', 'break') DEFAULT 'active',
    location VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    notes TEXT NULL,
    admin_approved BOOLEAN DEFAULT FALSE,
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Relations avec les tables existantes
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Index pour optimiser les performances
    INDEX idx_user_date (user_id, clock_in),
    INDEX idx_status (status),
    INDEX idx_clock_in (clock_in),
    INDEX idx_active_sessions (user_id, status)
);

-- Table pour les paramètres du système de pointage
CREATE TABLE IF NOT EXISTS time_tracking_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insérer les paramètres par défaut
INSERT INTO time_tracking_settings (setting_name, setting_value, description) VALUES
('auto_break_time', '120', 'Durée automatique de pause en minutes (0 = désactivé)'),
('max_work_hours', '12', 'Nombre maximum d\'heures de travail par jour'),
('require_location', 'false', 'Exiger la géolocalisation pour pointer'),
('admin_approval_required', 'false', 'Approbation admin requise pour les pointages'),
('allow_manual_edit', 'true', 'Permettre la modification manuelle des pointages'),
('break_threshold', '6', 'Heures de travail avant pause obligatoire'),
('overtime_threshold', '8', 'Heures de travail avant heures supplémentaires')
ON DUPLICATE KEY UPDATE 
setting_value = VALUES(setting_value),
updated_at = CURRENT_TIMESTAMP;

-- Vue pour faciliter les requêtes de rapport
CREATE OR REPLACE VIEW time_tracking_report AS
SELECT 
    tt.id,
    tt.user_id,
    u.username,
    u.full_name,
    u.role,
    DATE(tt.clock_in) as work_date,
    tt.clock_in,
    tt.clock_out,
    tt.break_start,
    tt.break_end,
    tt.total_hours,
    tt.break_duration,
    tt.work_duration,
    tt.status,
    tt.location,
    tt.notes,
    tt.admin_approved,
    tt.admin_notes,
    CASE 
        WHEN tt.total_hours > 8 THEN tt.total_hours - 8
        ELSE 0
    END as overtime_hours,
    CASE 
        WHEN tt.status = 'active' AND tt.clock_in < DATE_SUB(NOW(), INTERVAL 12 HOUR) 
        THEN 'session_longue'
        WHEN tt.status = 'active' 
        THEN 'en_cours'
        WHEN tt.status = 'break' 
        THEN 'en_pause'
        ELSE 'termine'
    END as display_status
FROM time_tracking tt
JOIN users u ON tt.user_id = u.id
ORDER BY tt.clock_in DESC;

