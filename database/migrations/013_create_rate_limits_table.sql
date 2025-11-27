-- Create rate limits table for preventing abuse
-- Tracks API/endpoint usage to prevent brute force and DoS attacks

CREATE TABLE IF NOT EXISTS `erp_rate_limits` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `identifier` VARCHAR(255) NOT NULL COMMENT 'IP address or user ID',
  `endpoint` VARCHAR(255) NOT NULL COMMENT 'Endpoint being accessed',
  `attempts` INT DEFAULT 1 COMMENT 'Number of attempts',
  `reset_at` TIMESTAMP NOT NULL COMMENT 'When the counter resets',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_limit` (`identifier`, `endpoint`),
  KEY `idx_reset_at` (`reset_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Rate limiting for preventing abuse';
