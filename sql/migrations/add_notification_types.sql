-- Migration: Add new notification types for push notification system
-- Date: 2024-12-20

-- Add missing notification types
INSERT IGNORE INTO `notification_types` (`type_code`, `description`, `icon`, `color`, `importance`) VALUES
('reparation_create', 'Nouvelle réparation créée', 'fas fa-plus-circle', '#3b82f6', 'haute'),
('reparation_cancel', 'Réparation annulée', 'fas fa-ban', '#ef4444', 'haute'),
('reparation_return', 'Réparation restituée', 'fas fa-undo', '#10b981', 'normale'),
('devis_accepte', 'Devis accepté par le client', 'fas fa-check-double', '#22c55e', 'haute'),
('devis_refuse', 'Devis refusé par le client', 'fas fa-times-circle', '#ef4444', 'haute'),
('devis_create', 'Nouveau devis créé', 'fas fa-file-invoice', '#8b5cf6', 'normale'),
('rachat_create', 'Nouveau rachat enregistré', 'fas fa-recycle', '#06b6d4', 'normale'),
('stock_low', 'Alerte stock bas', 'fas fa-exclamation-triangle', '#f59e0b', 'critique'),
('stock_out', 'Rupture de stock', 'fas fa-times-circle', '#ef4444', 'critique'),
('commande_create', 'Nouvelle commande pièce', 'fas fa-shopping-cart', '#8b5cf6', 'normale'),
('commande_received', 'Commande pièce reçue', 'fas fa-box', '#10b981', 'normale');

-- Update existing types if needed (ensure they have proper icons)
UPDATE `notification_types` SET `icon` = 'fas fa-play-circle' WHERE `type_code` = 'reparation_start' AND `icon` = '';
UPDATE `notification_types` SET `icon` = 'fas fa-stop-circle' WHERE `type_code` = 'reparation_stop' AND `icon` = '';
UPDATE `notification_types` SET `icon` = 'fas fa-edit' WHERE `type_code` = 'reparation_update' AND `icon` = '';
UPDATE `notification_types` SET `icon` = 'fas fa-check-circle' WHERE `type_code` = 'reparation_finish' AND `icon` = '';
UPDATE `notification_types` SET `icon` = 'fas fa-tasks' WHERE `type_code` = 'task_assigned' AND `icon` = '';
UPDATE `notification_types` SET `icon` = 'fas fa-clipboard-check' WHERE `type_code` = 'task_completed' AND `icon` = '';
