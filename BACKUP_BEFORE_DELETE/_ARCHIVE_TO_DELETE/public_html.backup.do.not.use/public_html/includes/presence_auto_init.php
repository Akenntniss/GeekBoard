<?php
/**
 * Initialisation automatique du système de présence
 * Cette fonction vérifie et crée automatiquement les tables nécessaires
 */

function initializePresenceSystem() {
    try {
        $shop_pdo = getShopDBConnection();
        
        // Vérifier si les tables existent déjà
        $tables_to_check = [
            'presence_types',
            'presence_events', 
            'employee_schedules',
            'paid_leave_balance',
            'presence_comments',
            'presence_history'
        ];
        
        $missing_tables = [];
        foreach ($tables_to_check as $table) {
            $stmt = $shop_pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if (!$stmt->fetch()) {
                $missing_tables[] = $table;
            }
        }
        
        // Si des tables manquent, les créer
        if (!empty($missing_tables)) {
            $sql_commands = [
                // Table des types d'événements de présence
                "CREATE TABLE IF NOT EXISTS `presence_types` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(50) NOT NULL UNIQUE,
                    `display_name` varchar(100) NOT NULL,
                    `description` text,
                    `color_code` varchar(7) DEFAULT '#007bff',
                    `is_paid` tinyint(1) DEFAULT 0,
                    `affects_salary` tinyint(1) DEFAULT 1,
                    `is_absence` tinyint(1) DEFAULT 1,
                    `is_late` tinyint(1) DEFAULT 0,
                    `requires_justification` tinyint(1) DEFAULT 0,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Table des événements de présence (utilise users.id au lieu d'employes.id)
                "CREATE TABLE IF NOT EXISTS `presence_events` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `employee_id` int(11) NOT NULL COMMENT 'Référence users.id',
                    `type_id` int(11) NOT NULL,
                    `date_start` datetime NOT NULL,
                    `date_end` datetime NULL,
                    `duration_minutes` int(11) NULL,
                    `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
                    `comment` text NULL,
                    `created_by` int(11) NOT NULL,
                    `approved_by` int(11) NULL,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_employee_date` (`employee_id`, `date_start`),
                    KEY `idx_type` (`type_id`),
                    KEY `idx_status` (`status`),
                    KEY `idx_date_range` (`date_start`, `date_end`),
                    FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Table des horaires des employés
                "CREATE TABLE IF NOT EXISTS `employee_schedules` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `employee_id` int(11) NOT NULL,
                    `day_of_week` tinyint(1) NOT NULL COMMENT '1=Lundi, 7=Dimanche',
                    `start_time` time NOT NULL,
                    `end_time` time NOT NULL,
                    `is_working_day` tinyint(1) DEFAULT 1,
                    `effective_from` date NOT NULL,
                    `effective_to` date NULL,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_employee_day` (`employee_id`, `day_of_week`),
                    KEY `idx_effective_dates` (`effective_from`, `effective_to`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Table des soldes de congés payés
                "CREATE TABLE IF NOT EXISTS `paid_leave_balance` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `employee_id` int(11) NOT NULL,
                    `year` year NOT NULL,
                    `total_days` decimal(5,2) DEFAULT 25.00,
                    `used_days` decimal(5,2) DEFAULT 0.00,
                    `remaining_days` decimal(5,2) GENERATED ALWAYS AS (`total_days` - `used_days`) STORED,
                    `carried_over_days` decimal(5,2) DEFAULT 0.00,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_employee_year` (`employee_id`, `year`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Table des commentaires sur les événements
                "CREATE TABLE IF NOT EXISTS `presence_comments` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `presence_event_id` int(11) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `comment` text NOT NULL,
                    `is_internal` tinyint(1) DEFAULT 0,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_presence_event` (`presence_event_id`),
                    KEY `idx_user` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Table de l'historique des modifications
                "CREATE TABLE IF NOT EXISTS `presence_history` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `presence_event_id` int(11) NOT NULL,
                    `action` enum('created','updated','approved','rejected','cancelled') NOT NULL,
                    `changed_fields` json NULL,
                    `old_values` json NULL,
                    `new_values` json NULL,
                    `user_id` int(11) NOT NULL,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_presence_event` (`presence_event_id`),
                    KEY `idx_action` (`action`),
                    KEY `idx_user` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ];
            
            // Exécuter les commandes SQL
            foreach ($sql_commands as $sql) {
                $shop_pdo->exec($sql);
            }
            
            // Insérer les types d'événements par défaut si la table est vide
            $stmt = $shop_pdo->query("SELECT COUNT(*) FROM presence_types");
            if ($stmt->fetchColumn() == 0) {
                $default_types = [
                    ['retard', 'Retard', 'Arrivée en retard au travail', '#ffc107', 0, 1, 0, 1, 0],
                    ['absence_justifiee', 'Absence Justifiée', 'Absence avec justification médicale ou autre', '#17a2b8', 0, 0, 1, 0, 1],
                    ['absence_injustifiee', 'Absence Injustifiée', 'Absence sans justification valable', '#dc3545', 0, 1, 1, 0, 0],
                    ['conges_payes', 'Congés Payés', 'Congés payés annuels', '#28a745', 1, 0, 1, 0, 0],
                    ['conges_sans_solde', 'Congés Sans Solde', 'Congés non rémunérés', '#6c757d', 0, 1, 1, 0, 0],
                    ['maladie', 'Arrêt Maladie', 'Arrêt maladie avec certificat médical', '#fd7e14', 0, 0, 1, 0, 1],
                    ['formation', 'Formation', 'Formation professionnelle', '#20c997', 1, 0, 1, 0, 0]
                ];
                
                $stmt = $shop_pdo->prepare("
                    INSERT INTO presence_types (name, display_name, description, color_code, is_paid, affects_salary, is_absence, is_late, requires_justification) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($default_types as $type) {
                    $stmt->execute($type);
                }
            }
            
            // Créer des horaires par défaut pour les employés existants qui n'en ont pas
            $stmt = $shop_pdo->query("
                SELECT u.id 
                FROM users u 
                WHERE u.role IN ('admin', 'technicien')
                AND NOT EXISTS (
                    SELECT 1 FROM employee_schedules es 
                    WHERE es.employee_id = u.id
                )
            ");
            $employees_without_schedule = $stmt->fetchAll();
            
            if (!empty($employees_without_schedule)) {
                $stmt = $shop_pdo->prepare("
                    INSERT INTO employee_schedules (employee_id, day_of_week, start_time, end_time, is_working_day, effective_from)
                    VALUES (?, ?, '08:00:00', '17:00:00', 1, CURDATE())
                ");
                
                foreach ($employees_without_schedule as $employee) {
                    // Créer un horaire pour du lundi au vendredi
                    for ($day = 1; $day <= 5; $day++) {
                        $stmt->execute([$employee['id'], $day]);
                    }
                }
            }
            
            // Initialiser les soldes de congés payés pour l'année en cours
            $current_year = date('Y');
            $stmt = $shop_pdo->prepare("
                SELECT u.id 
                FROM users u 
                WHERE u.role IN ('admin', 'technicien')
                AND NOT EXISTS (
                    SELECT 1 FROM paid_leave_balance plb 
                    WHERE plb.employee_id = u.id AND plb.year = ?
                )
            ");
            $stmt->execute([$current_year]);
            $employees_without_balance = $stmt->fetchAll();
            
            if (!empty($employees_without_balance)) {
                $stmt = $shop_pdo->prepare("
                    INSERT INTO paid_leave_balance (employee_id, year, total_days)
                    VALUES (?, ?, 25.00)
                ");
                
                foreach ($employees_without_balance as $employee) {
                    $stmt->execute([$employee['id'], $current_year]);
                }
            }
        }
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Erreur d'initialisation du système de présence: " . $e->getMessage());
        return false;
    }
}

// Fonction pour vérifier si le système est initialisé
function isPresenceSystemInitialized() {
    try {
        $shop_pdo = getShopDBConnection();
        $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'presence_types'");
        $stmt->execute();
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}
?>


