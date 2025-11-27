-- Create password history table for tracking password changes
-- Prevents users from reusing recent passwords

CREATE TABLE IF NOT EXISTS `erp_password_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL COMMENT 'User whose password was changed',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hashed password',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Password history for preventing password reuse';

-- Add foreign key constraint
ALTER TABLE `erp_password_history`
ADD CONSTRAINT `fk_password_history_user`
FOREIGN KEY (`user_id`) REFERENCES `erp_users`(`id`) ON DELETE CASCADE;
