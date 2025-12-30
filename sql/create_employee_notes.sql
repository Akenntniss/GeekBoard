-- Table pour stocker les remarques contextuelles sur les employés
-- Ces notes enrichissent les analyses IA avec du contexte managérial

CREATE TABLE IF NOT EXISTS employee_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL COMMENT 'ID de l''employé concerné',
    note_type ENUM('avertissement', 'incident', 'appreciation', 'remarque', 'sanction', 'autre') NOT NULL COMMENT 'Type de note',
    title VARCHAR(200) NOT NULL COMMENT 'Titre court de la note',
    description TEXT NOT NULL COMMENT 'Description détaillée',
    date_incident DATE COMMENT 'Date de l''incident ou de la remarque',
    severity ENUM('info', 'low', 'medium', 'high', 'critical') DEFAULT 'info' COMMENT 'Niveau de gravité',
    is_resolved TINYINT(1) DEFAULT 0 COMMENT '1 si le problème est résolu',
    is_private TINYINT(1) DEFAULT 1 COMMENT '1 si visible uniquement par les admins',
    include_in_ai_analysis TINYINT(1) DEFAULT 1 COMMENT '1 si inclus dans l''analyse IA',
    created_by INT NOT NULL COMMENT 'ID de l''utilisateur qui a créé la note',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_employee (employee_id),
    INDEX idx_ai_analysis (employee_id, include_in_ai_analysis),
    INDEX idx_date (date_incident),
    INDEX idx_type_severity (note_type, severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notes contextuelles sur les employés pour analyses IA';
