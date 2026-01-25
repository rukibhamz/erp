-- Update booking_type enum to include half_day, multi_day, and full_day
ALTER TABLE `erp_bookings` 
MODIFY COLUMN `booking_type` ENUM('hourly', 'daily', 'weekly', 'monthly', 'half_day', 'multi_day', 'full_day', 'custom') NOT NULL DEFAULT 'hourly';
