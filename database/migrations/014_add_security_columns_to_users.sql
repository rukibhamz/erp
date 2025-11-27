-- Add security-related columns to users table
-- Supports password policies, account lockout, and session management

-- Add password security columns
ALTER TABLE `erp_users` 
ADD COLUMN `password_changed_at` TIMESTAMP NULL COMMENT 'When password was last changed',
ADD COLUMN `password_expires_at` TIMESTAMP NULL COMMENT 'When password expires',
ADD COLUMN `failed_login_attempts` INT DEFAULT 0 COMMENT 'Count of failed login attempts',
ADD COLUMN `locked_until` TIMESTAMP NULL COMMENT 'Account locked until this time',
ADD COLUMN `last_login_at` TIMESTAMP NULL COMMENT 'Last successful login',
ADD COLUMN `last_login_ip` VARCHAR(45) NULL COMMENT 'IP of last login';

-- Add indexes for performance
ALTER TABLE `erp_users`
ADD KEY `idx_locked_until` (`locked_until`),
ADD KEY `idx_password_expires_at` (`password_expires_at`);
