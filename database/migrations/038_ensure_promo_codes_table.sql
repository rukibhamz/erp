-- Migration 038: Ensure promo_codes table exists
CREATE TABLE IF NOT EXISTS `erp_promo_codes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(50) NOT NULL,
    `description` text DEFAULT NULL,
    `discount_type` enum('percentage','fixed') NOT NULL,
    `discount_value` decimal(10,2) NOT NULL,
    `minimum_amount` decimal(15,2) DEFAULT NULL,
    `maximum_discount` decimal(15,2) DEFAULT NULL,
    `valid_from` date NOT NULL,
    `valid_to` date NOT NULL,
    `usage_limit` int(11) DEFAULT NULL,
    `used_count` int(11) DEFAULT 0,
    `applicable_to` enum('all','resource','category','addon') DEFAULT 'all',
    `applicable_ids` text DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `code` (`code`),
    KEY `valid_from` (`valid_from`),
    KEY `valid_to` (`valid_to`),
    KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
