-- Migration 035: Per-Person Booking Types & Rental Items
-- Adds picnic, photoshoot, workspace booking types with per-person pricing
-- Creates booking_rentals table for inventory-linked rental items

-- 1. Expand booking_type ENUM to include new types
ALTER TABLE `erp_bookings` MODIFY `booking_type` 
  ENUM('hourly','half_day','full_day','daily','multi_day','weekly','monthly','custom','picnic','photoshoot','videoshoot','workspace') 
  DEFAULT 'hourly';

-- 2. Add equipment_tier column to bookings
ALTER TABLE `erp_bookings` ADD COLUMN `equipment_tier` VARCHAR(50) DEFAULT NULL AFTER `booking_type`;

-- 3. Add rental fields to items table
ALTER TABLE `erp_items` ADD COLUMN `is_rentable` TINYINT(1) DEFAULT 0 AFTER `item_status`;
ALTER TABLE `erp_items` ADD COLUMN `rental_rate` DECIMAL(15,2) DEFAULT 0 AFTER `is_rentable`;
ALTER TABLE `erp_items` ADD COLUMN `rental_rate_type` ENUM('per_event','per_day','per_hour') DEFAULT 'per_event' AFTER `rental_rate`;

-- 4. Create booking_rentals table
CREATE TABLE IF NOT EXISTS `erp_booking_rentals` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `rental_rate` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `rental_total` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `checked_out_at` DATETIME DEFAULT NULL,
  `returned_at` DATETIME DEFAULT NULL,
  `return_condition` TEXT DEFAULT NULL,
  `status` ENUM('reserved','checked_out','returned','damaged','lost') DEFAULT 'reserved',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking_id` (`booking_id`),
  KEY `idx_item_id` (`item_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Add index for rental item queries
CREATE INDEX `idx_items_rentable` ON `erp_items` (`is_rentable`, `item_status`);
