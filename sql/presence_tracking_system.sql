-- ============================================================================
-- SYSTÈME DE GESTION DES ABSENCES ET RETARDS - GEEKBOARD
-- ============================================================================

-- Table pour les types d'événements de présence
CREATE TABLE IF NOT EXISTS presence_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    color_code VARCHAR(7) DEFAULT '#007bff', -- Code couleur pour l'affichage
    is_paid BOOLEAN DEFAULT FALSE, -- Indique si c'est payé ou non
    affects_salary BOOLEAN DEFAULT TRUE, -- Si cela affecte le salaire
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insérer les types d'événements par défaut
INSERT INTO presence_types (name, display_name, description, color_code, is_paid, affects_salary) VALUES
('retard', 'Retard', 'Arrivée en retard au travail', '#ffc107', FALSE, TRUE),
('absence_justifiee', 'Absence Justifiée', 'Absence avec justification médicale ou autre', '#17a2b8', FALSE, FALSE),
('absence_injustifiee', 'Absence Injustifiée', 'Absence sans justification valable', '#dc3545', FALSE, TRUE),
('conges_payes', 'Congés Payés', 'Congés payés annuels', '#28a745', TRUE, FALSE),
('conges_sans_solde', 'Congés Sans Solde', 'Congés non rémunérés', '#6c757d', FALSE, TRUE),
('maladie', 'Arrêt Maladie', 'Arrêt maladie avec certificat médical', '#fd7e14', FALSE, FALSE),
('formation', 'Formation', 'Formation professionnelle', '#20c997', TRUE, FALSE);

-- Table principale pour les événements de présence
CREATE TABLE IF NOT EXISTS presence_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    presence_type_id INT NOT NULL,
    date_start DATE NOT NULL,
    date_end DATE NULL, -- NULL pour les événements d'une journée (retards)
    time_start TIME NULL, -- Heure de début (pour les retards)
    time_end TIME NULL, -- Heure de fin (pour les retards)
    duration_minutes INT NULL, -- Durée en minutes (pour les retards)
    duration_days DECIMAL(4,2) NULL, -- Durée en jours (pour les absences/congés)
    reason TEXT NULL, -- Raison/justification
    justification_document VARCHAR(255) NULL, -- Chemin vers le document justificatif
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    is_recurring BOOLEAN DEFAULT FALSE, -- Pour les événements récurrents
    recurring_pattern JSON NULL, -- Pattern de récurrence (JSON)
    created_by INT NOT NULL, -- ID de l'utilisateur qui a créé l'événement
    approved_by INT NULL, -- ID de l'administrateur qui a approuvé
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    
    -- Contraintes
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (presence_type_id) REFERENCES presence_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    
    -- Index pour les performances
    INDEX idx_employee_date (employee_id, date_start),
    INDEX idx_date_range (date_start, date_end),
    INDEX idx_status (status),
    INDEX idx_type (presence_type_id)
);

-- Table pour les horaires de travail des employés
CREATE TABLE IF NOT EXISTS employee_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    day_of_week TINYINT NOT NULL, -- 1=Lundi, 7=Dimanche
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_working_day BOOLEAN DEFAULT TRUE,
    break_start_time TIME NULL,
    break_end_time TIME NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL, -- NULL = toujours en vigueur
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_employee_schedule (employee_id, day_of_week, effective_from)
);

-- Table pour les congés payés - solde par employé
CREATE TABLE IF NOT EXISTS paid_leave_balance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    year YEAR NOT NULL,
    total_days DECIMAL(4,2) DEFAULT 25.00, -- Jours totaux pour l'année
    used_days DECIMAL(4,2) DEFAULT 0.00, -- Jours utilisés
    remaining_days DECIMAL(4,2) GENERATED ALWAYS AS (total_days - used_days) STORED,
    carry_over_days DECIMAL(4,2) DEFAULT 0.00, -- Jours reportés de l'année précédente
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_year (employee_id, year),
    INDEX idx_employee_year (employee_id, year)
);

-- Table pour les commentaires sur les événements
CREATE TABLE IF NOT EXISTS presence_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    presence_event_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE, -- Commentaire interne admin ou visible par l'employé
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (presence_event_id) REFERENCES presence_events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_event_comments (presence_event_id)
);

-- Table pour l'historique des modifications
CREATE TABLE IF NOT EXISTS presence_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    presence_event_id INT NOT NULL,
    action ENUM('created', 'updated', 'approved', 'rejected', 'deleted') NOT NULL,
    changed_fields JSON NULL, -- Champs modifiés (format JSON)
    old_values JSON NULL, -- Anciennes valeurs
    new_values JSON NULL, -- Nouvelles valeurs
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (presence_event_id) REFERENCES presence_events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_event_history (presence_event_id),
    INDEX idx_action_date (action, created_at)
);

-- Vue pour les statistiques rapides
CREATE OR REPLACE VIEW presence_stats AS
SELECT 
    u.id as employee_id,
    u.full_name as employee_name,
    pt.name as event_type,
    pt.display_name as event_display_name,
    COUNT(pe.id) as total_events,
    SUM(CASE WHEN pe.duration_days IS NOT NULL THEN pe.duration_days ELSE 0 END) as total_days,
    SUM(CASE WHEN pe.duration_minutes IS NOT NULL THEN pe.duration_minutes ELSE 0 END) as total_minutes,
    YEAR(pe.date_start) as year,
    MONTH(pe.date_start) as month
FROM users u
JOIN presence_events pe ON u.id = pe.employee_id
JOIN presence_types pt ON pe.presence_type_id = pt.id
WHERE pe.status = 'approved'
GROUP BY u.id, pt.id, YEAR(pe.date_start), MONTH(pe.date_start);

-- Procédure stockée pour calculer les retards
DELIMITER //
CREATE OR REPLACE PROCEDURE CalculateLateness(
    IN p_employee_id INT,
    IN p_date DATE,
    IN p_arrival_time TIME,
    OUT p_late_minutes INT
)
BEGIN
    DECLARE v_scheduled_start TIME;
    DECLARE v_day_of_week TINYINT;
    
    -- Obtenir le jour de la semaine (1=Lundi, 7=Dimanche)
    SET v_day_of_week = DAYOFWEEK(p_date);
    IF v_day_of_week = 1 THEN SET v_day_of_week = 7; -- Dimanche
    ELSE SET v_day_of_week = v_day_of_week - 1; -- Ajuster pour que Lundi = 1
    END IF;
    
    -- Récupérer l'heure de début prévue
    SELECT start_time INTO v_scheduled_start
    FROM employee_schedules
    WHERE employee_id = p_employee_id
    AND day_of_week = v_day_of_week
    AND is_working_day = TRUE
    AND effective_from <= p_date
    AND (effective_to IS NULL OR effective_to >= p_date)
    ORDER BY effective_from DESC
    LIMIT 1;
    
    -- Calculer le retard
    IF v_scheduled_start IS NOT NULL AND p_arrival_time > v_scheduled_start THEN
        SET p_late_minutes = TIMESTAMPDIFF(MINUTE, v_scheduled_start, p_arrival_time);
    ELSE
        SET p_late_minutes = 0;
    END IF;
END //
DELIMITER ;

-- Trigger pour mettre à jour automatiquement le solde des congés payés
DELIMITER //
CREATE OR REPLACE TRIGGER update_paid_leave_balance 
AFTER INSERT ON presence_events
FOR EACH ROW
BEGIN
    IF NEW.presence_type_id = (SELECT id FROM presence_types WHERE name = 'conges_payes') 
       AND NEW.status = 'approved'
       AND NEW.duration_days IS NOT NULL THEN
        
        INSERT INTO paid_leave_balance (employee_id, year, used_days)
        VALUES (NEW.employee_id, YEAR(NEW.date_start), NEW.duration_days)
        ON DUPLICATE KEY UPDATE
        used_days = used_days + NEW.duration_days,
        updated_at = CURRENT_TIMESTAMP;
    END IF;
END //
DELIMITER ;

-- ============================================================================
-- DONNÉES DE TEST (Optionnel - pour les tests)
-- ============================================================================

-- Insérer des horaires de travail par défaut (8h-17h, Lundi-Vendredi)
-- Remplacer les IDs d'employés par ceux existants dans votre base
-- INSERT INTO employee_schedules (employee_id, day_of_week, start_time, end_time, effective_from) VALUES
-- (1, 1, '08:00:00', '17:00:00', '2024-01-01'), -- Lundi
-- (1, 2, '08:00:00', '17:00:00', '2024-01-01'), -- Mardi
-- (1, 3, '08:00:00', '17:00:00', '2024-01-01'), -- Mercredi
-- (1, 4, '08:00:00', '17:00:00', '2024-01-01'), -- Jeudi
-- (1, 5, '08:00:00', '17:00:00', '2024-01-01'); -- Vendredi

-- Initialiser le solde de congés payés pour l'année en cours
-- INSERT INTO paid_leave_balance (employee_id, year, total_days) VALUES
-- (1, YEAR(CURDATE()), 25.00);

