-- Migration 023: Add is_sellable flag to items table
-- Also adds opening_stock field for initial stock entry

-- Add is_sellable column to items table
-- Default: 1 (sellable) for most items, but will be set to 0 for fixed_asset type
ALTER TABLE `erp_items` 
ADD COLUMN IF NOT EXISTS `is_sellable` TINYINT(1) NOT NULL DEFAULT 1 
AFTER `item_status`;

-- Add opening_quantity column for initial stock (used during item creation)
ALTER TABLE `erp_items`
ADD COLUMN IF NOT EXISTS `opening_quantity` DECIMAL(15,4) DEFAULT 0
AFTER `is_sellable`;

-- Add opening_location_id column to specify where opening stock is located
ALTER TABLE `erp_items`
ADD COLUMN IF NOT EXISTS `opening_location_id` INT(11) DEFAULT NULL
AFTER `opening_quantity`;

-- Update existing fixed_asset items to not be sellable
UPDATE `erp_items` SET `is_sellable` = 0 WHERE `item_type` = 'fixed_asset';

-- Create index for faster POS queries
CREATE INDEX IF NOT EXISTS `idx_items_sellable` ON `erp_items` (`is_sellable`, `item_status`);
