-- Add missing columns to payroll_runs table
ALTER TABLE `payroll_runs` 
ADD COLUMN `posted_date` DATETIME NULL AFTER `processed_date`,
ADD COLUMN `posted_by` INT(11) NULL AFTER `posted_date`;
