-- Migration 037: Add password reset columns to customer_portal_users if missing
-- Safe to run multiple times (uses IF NOT EXISTS pattern via ALTER IGNORE or column check)

ALTER TABLE `erp_customer_portal_users`
    ADD COLUMN IF NOT EXISTS `password_reset_token` VARCHAR(64) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `password_reset_expires` DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `failed_login_attempts` INT(11) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `locked_until` DATETIME DEFAULT NULL;

-- Add index on password_reset_token if not exists
ALTER TABLE `erp_customer_portal_users`
    ADD INDEX IF NOT EXISTS `idx_password_reset_token` (`password_reset_token`);
