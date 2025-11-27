-- Create system logs table for centralized error logging
-- This table stores all application errors and logs with context

CREATE TABLE IF NOT EXISTS `erp_system_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `level` VARCHAR(20) NOT NULL COMMENT 'Log level: DEBUG, INFO, WARNING, ERROR, CRITICAL',
  `message` TEXT NOT NULL COMMENT 'Log message',
  `context` JSON NULL COMMENT 'Additional context data',
  `user_id` INT(11) NULL COMMENT 'User who triggered the log',
  `ip_address` VARCHAR(45) NULL COMMENT 'IP address of the request',
  `user_agent` VARCHAR(255) NULL COMMENT 'Browser user agent',
  `module` VARCHAR(50) NULL COMMENT 'Module/controller that generated the log',
  `url` VARCHAR(255) NULL COMMENT 'Request URL',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_level` (`level`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System logs for error tracking and monitoring';

-- Add foreign key constraint
ALTER TABLE `erp_system_logs`
ADD CONSTRAINT `fk_system_logs_user`
FOREIGN KEY (`user_id`) REFERENCES `erp_users`(`id`) ON DELETE SET NULL;
