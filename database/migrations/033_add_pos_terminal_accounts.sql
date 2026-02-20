-- Add missing account columns to POS terminals
-- Created by AutoFixer

ALTER TABLE `erp_pos_terminals` ADD COLUMN `cash_account_id` INT(11) DEFAULT NULL;
ALTER TABLE `erp_pos_terminals` ADD COLUMN `sales_account_id` INT(11) DEFAULT NULL;
ALTER TABLE `erp_pos_terminals` ADD COLUMN `tax_account_id` INT(11) DEFAULT NULL;
