-- ================================================================
-- SYSTÈME DE PRIMES GEEKBOARD - STRUCTURE SQL
-- ================================================================

-- Table des types de missions
CREATE TABLE IF NOT EXISTS mission_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fas fa-tasks',
    couleur VARCHAR(7) DEFAULT '#4361ee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des types de missions de base
INSERT INTO mission_types (nom, description, icon, couleur) VALUES
('Reconditionnement Trottinettes', 'Reconditionnement d\'appareils trottinettes pour la revente', 'fas fa-tools', '#2ecc71'),
('Reconditionnement Smartphones', 'Reconditionnement de smartphones pour la revente', 'fas fa-mobile-alt', '#3498db'),
('Publication LeBonCoin', 'Diffusion d\'annonces sur LeBonCoin', 'fas fa-bullhorn', '#f39c12'),
('Publication eBay', 'Diffusion d\'annonces sur eBay', 'fas fa-shopping-cart', '#e74c3c'),
('Réparation Express', 'Réparations rapides sous 24h', 'fas fa-clock', '#9b59b6'),
('Satisfaction Client', 'Obtenir des avis clients positifs', 'fas fa-star', '#f1c40f');

-- Table des missions
CREATE TABLE IF NOT EXISTS missions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    mission_type_id INT,
    objectif_nombre INT NOT NULL DEFAULT 1,
    periode_jours INT NOT NULL DEFAULT 30,
    recompense_euros DECIMAL(8,2) NOT NULL DEFAULT 0,
    recompense_points INT NOT NULL DEFAULT 0,
    statut ENUM('active', 'inactive', 'archivee') NOT NULL DEFAULT 'active',
    date_debut DATE,
    date_fin DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mission_type_id) REFERENCES mission_types(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de progression des utilisateurs sur les missions
CREATE TABLE IF NOT EXISTS user_missions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    mission_id INT NOT NULL,
    progression_actuelle INT NOT NULL DEFAULT 0,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_completion TIMESTAMP NULL,
    statut ENUM('en_cours', 'complete', 'abandonnee') NOT NULL DEFAULT 'en_cours',
    recompense_attribuee BOOLEAN DEFAULT FALSE,
    INDEX (user_id),
    INDEX (mission_id),
    INDEX (statut),
    UNIQUE KEY unique_user_mission (user_id, mission_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des validations de tâches
CREATE TABLE IF NOT EXISTS mission_validations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_mission_id INT NOT NULL,
    user_id INT NOT NULL,
    mission_id INT NOT NULL,
    description_tache TEXT NOT NULL,
    preuve_text TEXT,
    photo_filename VARCHAR(255),
    validee BOOLEAN DEFAULT FALSE,
    validee_par INT NULL,
    commentaire_validation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    validated_at TIMESTAMP NULL,
    INDEX (user_id),
    INDEX (mission_id),
    INDEX (validee),
    FOREIGN KEY (user_mission_id) REFERENCES user_missions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE,
    FOREIGN KEY (validee_par) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour suivre les récompenses versées
CREATE TABLE IF NOT EXISTS mission_recompenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    mission_id INT NOT NULL,
    user_mission_id INT NOT NULL,
    montant_euros DECIMAL(8,2) NOT NULL DEFAULT 0,
    points_attribues INT NOT NULL DEFAULT 0,
    date_attribution TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attribuee_par INT NULL,
    commentaire TEXT,
    INDEX (user_id),
    INDEX (mission_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_mission_id) REFERENCES user_missions(id) ON DELETE CASCADE,
    FOREIGN KEY (attribuee_par) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Missions d'exemple
INSERT INTO missions (titre, description, mission_type_id, objectif_nombre, periode_jours, recompense_euros, recompense_points, date_debut, date_fin) VALUES
(
    'Reconditionnement Trottinettes - Janvier 2025',
    'Reconditionner 5 trottinettes pour la vente en magasin dans le mois',
    1, 5, 30, 50.00, 100,
    '2025-01-01', '2025-01-31'
),
(
    'Reconditionnement Smartphones - Janvier 2025', 
    'Reconditionner 5 smartphones pour la vente en magasin dans le mois',
    2, 5, 30, 75.00, 150,
    '2025-01-01', '2025-01-31'
),
(
    'Publication LeBonCoin - Janvier 2025',
    'Diffuser 10 annonces sur LeBonCoin dans le mois',
    3, 10, 30, 25.00, 50,
    '2025-01-01', '2025-01-31'
),
(
    'Publication eBay - Janvier 2025',
    'Diffuser 10 annonces sur eBay dans le mois', 
    4, 10, 30, 30.00, 60,
    '2025-01-01', '2025-01-31'
);

-- Vue pour les statistiques des missions
CREATE OR REPLACE VIEW mission_stats AS
SELECT 
    m.id as mission_id,
    m.titre,
    m.objectif_nombre,
    m.recompense_euros,
    COUNT(DISTINCT um.user_id) as participants,
    COUNT(CASE WHEN um.statut = 'complete' THEN 1 END) as completions,
    SUM(CASE WHEN um.statut = 'complete' THEN m.recompense_euros ELSE 0 END) as total_recompenses,
    AVG(um.progression_actuelle) as progression_moyenne,
    m.statut as mission_statut
FROM missions m
LEFT JOIN user_missions um ON m.id = um.mission_id
GROUP BY m.id;

-- Vue pour le tableau de bord employé
CREATE OR REPLACE VIEW user_mission_dashboard AS
SELECT 
    u.id as user_id,
    u.full_name,
    u.role,
    COUNT(DISTINCT um.mission_id) as missions_actives,
    COUNT(CASE WHEN um.statut = 'complete' THEN 1 END) as missions_completees,
    SUM(CASE WHEN um.statut = 'complete' THEN mr.montant_euros ELSE 0 END) as total_gains,
    SUM(CASE WHEN um.statut = 'complete' THEN mr.points_attribues ELSE 0 END) as total_points_missions,
    u.score_total + COALESCE(SUM(CASE WHEN um.statut = 'complete' THEN mr.points_attribues ELSE 0 END), 0) as score_total_avec_missions
FROM users u
LEFT JOIN user_missions um ON u.id = um.user_id
LEFT JOIN mission_recompenses mr ON um.id = mr.user_mission_id
WHERE u.role = 'technicien'
GROUP BY u.id; 