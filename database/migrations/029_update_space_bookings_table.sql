-- Migration to add financial and extra fields to space_bookings
-- and unify the booking system tables

-- Add missing columns
ALTER TABLE `erp_space_bookings`
ADD COLUMN `customer_address` TEXT NULL AFTER `customer_phone`,
ADD COLUMN `subtotal` DECIMAL(15,2) DEFAULT 0.00 AFTER `base_amount`,
ADD COLUMN `discount_amount` DECIMAL(15,2) DEFAULT 0.00 AFTER `subtotal`,
ADD COLUMN `security_deposit` DECIMAL(15,2) DEFAULT 0.00 AFTER `discount_amount`,
ADD COLUMN `paid_amount` DECIMAL(15,2) DEFAULT 0.00 AFTER `total_amount`,
ADD COLUMN `balance_amount` DECIMAL(15,2) DEFAULT 0.00 AFTER `paid_amount`,
ADD COLUMN `currency` VARCHAR(3) DEFAULT 'NGN' AFTER `balance_amount`,
ADD COLUMN `payment_plan` VARCHAR(20) DEFAULT 'full' AFTER `payment_status`,
ADD COLUMN `promo_code` VARCHAR(50) NULL AFTER `payment_plan`,
ADD COLUMN `booking_source` VARCHAR(20) DEFAULT 'online' AFTER `special_requests`,
ADD COLUMN `is_recurring` TINYINT(1) DEFAULT 0 AFTER `booking_source`,
ADD COLUMN `recurring_pattern` VARCHAR(20) NULL AFTER `is_recurring`,
ADD COLUMN `recurring_end_date` DATE NULL AFTER `recurring_pattern`,
ADD COLUMN `invoice_id` INT(11) NULL AFTER `recurring_end_date`;

-- Optimize keys
ALTER TABLE `erp_space_bookings` ADD INDEX `idx_booking_source` (`booking_source`);
ALTER TABLE `erp_space_bookings` ADD INDEX `idx_payment_status` (`payment_status`);
