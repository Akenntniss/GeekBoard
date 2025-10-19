-- Table pour stocker les tokens OAuth Google Shopping Content API
CREATE TABLE IF NOT EXISTS `oauth_tokens` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `shop_id` int(11) NOT NULL,
    `access_token` text NOT NULL,
    `refresh_token` text DEFAULT NULL,
    `expires_at` datetime NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `shop_id` (`shop_id`),
    KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
