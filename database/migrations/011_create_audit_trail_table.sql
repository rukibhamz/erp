-- Create audit trail table for tracking all critical actions
-- This table provides a complete history of who did what and when

CREATE TABLE IF NOT EXISTS `erp_audit_trail` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL COMMENT 'User who performed the action',
  `action` VARCHAR(50) NOT NULL COMMENT 'Action performed: CREATE, UPDATE, DELETE, etc.',
  `module` VARCHAR(50) NOT NULL COMMENT 'Module/table affected',
  `record_id` INT(11) NULL COMMENT 'ID of the affected record',
  `old_values` JSON NULL COMMENT 'Previous values before change',
  `new_values` JSON NULL COMMENT 'New values after change',
  `ip_address` VARCHAR(45) NULL COMMENT 'IP address of the request',
  `user_agent` VARCHAR(255) NULL COMMENT 'Browser user agent',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_module` (`module`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_record` (`module`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for tracking all system changes';

-- Add foreign key constraint
ALTER TABLE `erp_audit_trail`
ADD CONSTRAINT `fk_audit_trail_user`
FOREIGN KEY (`user_id`) REFERENCES `erp_users`(`id`) ON DELETE CASCADE;
