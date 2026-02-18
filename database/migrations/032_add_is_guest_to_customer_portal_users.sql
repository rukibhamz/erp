-- Migration: Add is_guest column to customer_portal_users
-- This column tracks whether the user was created as a guest during booking checkout

ALTER TABLE `erp_customer_portal_users`
ADD COLUMN IF NOT EXISTS `is_guest` TINYINT(1) NOT NULL DEFAULT 0 
    COMMENT 'Whether user was created as guest during booking checkout' 
    AFTER `status`;

-- Add index for guest user lookups
ALTER TABLE `erp_customer_portal_users` 
ADD INDEX IF NOT EXISTS `idx_is_guest` (`is_guest`);
