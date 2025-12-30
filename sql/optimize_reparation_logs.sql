-- Script d'optimisation pour la base de données GeekBoard
-- Optimise les requêtes de logs de réparations et tâches

-- ============================================
-- INDEX POUR TABLE reparation_logs
-- ============================================

-- Index composite pour les requêtes principales (tri par date)
ALTER TABLE reparation_logs 
ADD INDEX idx_date_employe (date_action DESC, employe_id);

-- Index pour filtrage par employé seul
ALTER TABLE reparation_logs 
ADD INDEX idx_employe_date (employe_id, date_action DESC);

-- Index pour filtrage par type d'action
ALTER TABLE reparation_logs 
ADD INDEX idx_action_date (action_type, date_action DESC);

-- Index pour filtrage par statut après
ALTER TABLE reparation_logs 
ADD INDEX idx_statut_apres_date (statut_apres, date_action DESC);

-- Index pour jointure avec reparations
ALTER TABLE reparation_logs 
ADD INDEX idx_reparation_id (reparation_id);

-- Index composite pour les activités en cours
ALTER TABLE reparation_logs 
ADD INDEX idx_ongoing_activities (employe_id, statut_apres, date_action DESC);


-- ============================================
-- INDEX POUR TABLE task_logs
-- ============================================

-- Index composite pour les requêtes principales (tri par date)
ALTER TABLE task_logs 
ADD INDEX idx_date_employe (date_action DESC, employe_id);

-- Index pour filtrage par employé seul
ALTER TABLE task_logs 
ADD INDEX idx_employe_date (employe_id, date_action DESC);

-- Index pour filtrage par type d'action
ALTER TABLE task_logs 
ADD INDEX idx_action_date (action_type, date_action DESC);

-- Index pour filtrage par statut après
ALTER TABLE task_logs 
ADD INDEX idx_statut_apres_date (statut_apres, date_action DESC);

-- Index pour jointure avec taches
ALTER TABLE task_logs 
ADD INDEX idx_tache_id (tache_id);

-- Index composite pour les activités en cours
ALTER TABLE task_logs 
ADD INDEX idx_ongoing_activities (employe_id, statut_apres, date_action DESC);


-- ============================================
-- TABLE DE CACHE POUR STATISTIQUES
-- ============================================

CREATE TABLE IF NOT EXISTS log_statistics_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL DEFAULT 0,
    cache_key VARCHAR(255) NOT NULL,
    cache_data TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_cache_lookup (shop_id, cache_key, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- VUE OPTIMISÉE POUR LES LOGS COMBINÉS
-- ============================================

-- Vue pour combiner les logs de réparations et tâches
-- Utilise les index pour améliorer les performances
CREATE OR REPLACE VIEW v_combined_logs AS
SELECT 
    CONCAT('R', rl.id) as unique_id,
    rl.id,
    rl.date_action,
    rl.action_type,
    rl.statut_avant,
    rl.statut_apres,
    rl.details,
    rl.employe_id,
    COALESCE(u.full_name, 'Employé inconnu') as employe_nom,
    'reparation' as log_source,
    rl.reparation_id as reference_id,
    CONCAT('Réparation #', rl.reparation_id) as reference_title
FROM reparation_logs rl
LEFT JOIN users u ON rl.employe_id = u.id

UNION ALL

SELECT 
    CONCAT('T', tl.id) as unique_id,
    tl.id,
    tl.date_action,
    tl.action_type,
    tl.statut_avant,
    tl.statut_apres,
    tl.details,
    tl.employe_id,
    COALESCE(u.full_name, 'Employé inconnu') as employe_nom,
    'tache' as log_source,
    tl.tache_id as reference_id,
    COALESCE(t.titre, CONCAT('Tâche #', tl.tache_id)) as reference_title
FROM task_logs tl
LEFT JOIN users u ON tl.employe_id = u.id
LEFT JOIN taches t ON tl.tache_id = t.id;


-- ============================================
-- PROCÉDURE POUR NETTOYER LE CACHE
-- ============================================

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS clean_expired_cache()
BEGIN
    DELETE FROM log_statistics_cache 
    WHERE expires_at < NOW();
END //

DELIMITER ;


-- ============================================
-- EVENT POUR NETTOYAGE AUTOMATIQUE DU CACHE
-- ============================================

-- Activer le scheduler d'événements
SET GLOBAL event_scheduler = ON;

-- Créer un événement pour nettoyer le cache toutes les heures
DROP EVENT IF EXISTS clean_cache_hourly;

CREATE EVENT clean_cache_hourly
ON SCHEDULE EVERY 1 HOUR
DO
CALL clean_expired_cache();


-- ============================================
-- OPTIMISATION DES TABLES
-- ============================================

OPTIMIZE TABLE reparation_logs;
OPTIMIZE TABLE task_logs;
OPTIMIZE TABLE users;
OPTIMIZE TABLE taches;


-- ============================================
-- NOTES D'OPTIMISATION
-- ============================================

-- 1. Les index composites permettent d'optimiser les requêtes avec tri par date
-- 2. La vue v_combined_logs évite de répéter le UNION ALL dans le code PHP
-- 3. Le cache permet de stocker les statistiques calculées pour 5 minutes
-- 4. Le nettoyage automatique du cache évite l'accumulation de données
-- 5. OPTIMIZE TABLE réorganise les données pour améliorer les performances

-- Pour vérifier l'utilisation des index :
-- EXPLAIN SELECT * FROM v_combined_logs WHERE employe_id = 1 ORDER BY date_action DESC LIMIT 20;

