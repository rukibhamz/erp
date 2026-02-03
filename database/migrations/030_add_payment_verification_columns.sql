-- Migration to add payment verification tracking columns to bookings
-- Run this migration to fix the payment confirmation flow

-- Add missing columns for payment verification tracking
ALTER TABLE `erp_space_bookings`
ADD COLUMN IF NOT EXISTS `payment_verified_at` DATETIME NULL COMMENT 'When payment was verified from gateway' AFTER `payment_status`,
ADD COLUMN IF NOT EXISTS `confirmed_at` DATETIME NULL COMMENT 'When booking was confirmed' AFTER `status`,
ADD COLUMN IF NOT EXISTS `cancelled_at` DATETIME NULL AFTER `confirmed_at`,
ADD COLUMN IF NOT EXISTS `cancellation_reason` TEXT NULL AFTER `cancelled_at`;

-- Also ensure started_at and completed_at exist
ALTER TABLE `erp_space_bookings`
ADD COLUMN IF NOT EXISTS `started_at` DATETIME NULL AFTER `cancellation_reason`,
ADD COLUMN IF NOT EXISTS `completed_at` DATETIME NULL AFTER `started_at`;

-- Add index for payment verification lookups
ALTER TABLE `erp_space_bookings` ADD INDEX IF NOT EXISTS `idx_payment_verified` (`payment_verified_at`);
