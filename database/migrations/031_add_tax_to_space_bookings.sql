-- Migration 031: Add tax columns to space_bookings table
-- This migration adds tax_rate and tax_amount columns to support VAT calculations

-- Add tax_rate column if not exists
ALTER TABLE `erp_space_bookings`
ADD COLUMN IF NOT EXISTS `tax_rate` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Tax rate percentage applied';

-- Add tax_amount column if not exists  
ALTER TABLE `erp_space_bookings`
ADD COLUMN IF NOT EXISTS `tax_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Calculated tax amount';

-- Ensure the columns are in the right position (after subtotal)
-- Note: MariaDB/some MySQL versions support AFTER, pure MySQL 8+ may not need repositioning
