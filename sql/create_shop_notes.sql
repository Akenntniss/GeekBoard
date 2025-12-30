-- Table pour stocker les remarques contextuelles globales du magasin
-- Ces notes expliquent les variations de KPI liées à des événements externes

CREATE TABLE IF NOT EXISTS shop_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    note_type ENUM('fermeture', 'travaux', 'evenement', 'probleme_technique', 'stock', 'autre') NOT NULL COMMENT 'Type d''événement',
    title VARCHAR(200) NOT NULL COMMENT 'Titre de l''événement',
    description TEXT NOT NULL COMMENT 'Description détaillée',
    date_start DATE NOT NULL COMMENT 'Date de début',
    date_end DATE COMMENT 'Date de fin (NULL si événement ponctuel)',
    impact_level ENUM('info', 'low', 'medium', 'high', 'critical') DEFAULT 'info' COMMENT 'Niveau d''impact sur l''activité',
    affects_kpi TINYINT(1) DEFAULT 1 COMMENT '1 si cet événement impacte les KPI',
    include_in_ai_analysis TINYINT(1) DEFAULT 1 COMMENT '1 si inclus dans l''analyse IA',
    created_by INT NOT NULL COMMENT 'ID de l''utilisateur créateur',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_dates (date_start, date_end),
    INDEX idx_ai_analysis (include_in_ai_analysis, affects_kpi),
    INDEX idx_type (note_type),
    INDEX idx_impact (impact_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notes contextuelles du magasin pour analyses IA';
