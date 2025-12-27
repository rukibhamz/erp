-- Default Chart of Accounts Seed Data
-- Standard Nigerian Business Chart of Accounts
-- Run this SQL to seed default accounts for a new installation

-- Clear existing accounts (CAUTION: only for fresh installs)
-- DELETE FROM `erp_accounts` WHERE 1=1;

-- ============================================
-- ASSETS (1xxx)
-- ============================================

-- Current Assets (11xx)
INSERT INTO `erp_accounts` (`account_code`, `account_name`, `account_type`, `description`, `parent_id`, `balance`, `is_active`, `is_system`, `created_at`) VALUES
('1100', 'Current Assets', 'asset', 'Short-term assets expected to be converted to cash within a year', NULL, 0.00, 1, 1, NOW()),
('1110', 'Cash on Hand', 'asset', 'Physical cash held by the business', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '1100') as t), 0.00, 1, 1, NOW()),
('1120', 'Bank Accounts', 'asset', 'Cash held in bank accounts', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '1100') as t), 0.00, 1, 1, NOW()),
('1130', 'Accounts Receivable', 'asset', 'Money owed by customers', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '1100') as t), 0.00, 1, 1, NOW()),
('1140', 'Inventory', 'asset', 'Goods held for sale', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '1100') as t), 0.00, 1, 1, NOW()),
('1150', 'Prepaid Expenses', 'asset', 'Expenses paid in advance', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '1100') as t), 0.00, 1, 1, NOW());

-- Fixed Assets (12xx)
INSERT INTO `erp_accounts` (`account_code`, `account_name`, `account_type`, `description`, `parent_id`, `balance`, `is_active`, `is_system`, `created_at`) VALUES
('1200', 'Fixed Assets', 'asset', 'Long-term tangible assets', NULL, 0.00, 1, 1, NOW()),
('1210', 'Land', 'asset', 'Land owned by the business', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '1200') as t), 0.00, 1, 0, NOW()),
('1220', 'Buildings', 'asset', 'Buildings owned by the business', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '1200') as t), 0.00, 1, 0, NOW()),
('1230', 'Equipment', 'asset', 'Equipment and machinery', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '1200') as t), 0.00, 1, 0, NOW()),
('1240', 'Furniture & Fixtures', 'asset', 'Office furniture and fixtures', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '1200') as t), 0.00, 1, 0, NOW()),
('1250', 'Vehicles', 'asset', 'Company vehicles', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '1200') as t), 0.00, 1, 0, NOW()),
('1290', 'Accumulated Depreciation', 'asset', 'Total depreciation of fixed assets', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '1200') as t), 0.00, 1, 1, NOW());

-- ============================================
-- LIABILITIES (2xxx)
-- ============================================

-- Current Liabilities (21xx)
INSERT INTO `erp_accounts` (`account_code`, `account_name`, `account_type`, `description`, `parent_id`, `balance`, `is_active`, `is_system`, `created_at`) VALUES
('2100', 'Current Liabilities', 'liability', 'Short-term obligations due within a year', NULL, 0.00, 1, 1, NOW()),
('2110', 'Accounts Payable', 'liability', 'Money owed to suppliers', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '2100') as t), 0.00, 1, 1, NOW()),
('2120', 'Accrued Expenses', 'liability', 'Expenses incurred but not yet paid', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '2100') as t), 0.00, 1, 0, NOW()),
('2130', 'Unearned Revenue', 'liability', 'Payments received for services not yet provided', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '2100') as t), 0.00, 1, 1, NOW()),
('2140', 'VAT Payable', 'liability', 'Value Added Tax collected and due to government', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '2100') as t), 0.00, 1, 1, NOW()),
('2150', 'WHT Payable', 'liability', 'Withholding Tax collected and due to government', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '2100') as t), 0.00, 1, 1, NOW()),
('2160', 'PAYE Payable', 'liability', 'Pay As You Earn tax deducted from employees', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '2100') as t), 0.00, 1, 1, NOW()),
('2170', 'Pension Payable', 'liability', 'Employee pension contributions due', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '2100') as t), 0.00, 1, 1, NOW());

-- Long-term Liabilities (22xx)
INSERT INTO `erp_accounts` (`account_code`, `account_name`, `account_type`, `description`, `parent_id`, `balance`, `is_active`, `is_system`, `created_at`) VALUES
('2200', 'Long-term Liabilities', 'liability', 'Obligations due beyond one year', NULL, 0.00, 1, 1, NOW()),
('2210', 'Bank Loans', 'liability', 'Long-term bank borrowings', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '2200') as t), 0.00, 1, 0, NOW()),
('2220', 'Mortgage Payable', 'liability', 'Mortgage on property', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '2200') as t), 0.00, 1, 0, NOW());

-- ============================================
-- EQUITY (3xxx)
-- ============================================

INSERT INTO `erp_accounts` (`account_code`, `account_name`, `account_type`, `description`, `parent_id`, `balance`, `is_active`, `is_system`, `created_at`) VALUES
('3000', 'Equity', 'equity', 'Owner\'s equity in the business', NULL, 0.00, 1, 1, NOW()),
('3100', 'Share Capital', 'equity', 'Capital contributed by shareholders', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '3000') as t), 0.00, 1, 0, NOW()),
('3200', 'Retained Earnings', 'equity', 'Accumulated profits retained in the business', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '3000') as t), 0.00, 1, 1, NOW()),
('3300', 'Drawings', 'equity', 'Owner withdrawals', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '3000') as t), 0.00, 1, 0, NOW());

-- ============================================
-- REVENUE (4xxx)
-- ============================================

INSERT INTO `erp_accounts` (`account_code`, `account_name`, `account_type`, `description`, `parent_id`, `balance`, `is_active`, `is_system`, `created_at`) VALUES
('4000', 'Revenue', 'revenue', 'Income from business operations', NULL, 0.00, 1, 1, NOW()),
('4100', 'Sales Revenue', 'revenue', 'Income from sale of goods', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '4000') as t), 0.00, 1, 1, NOW()),
('4200', 'Service Revenue', 'revenue', 'Income from services rendered', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '4000') as t), 0.00, 1, 1, NOW()),
('4300', 'Rental Income', 'revenue', 'Income from property rentals', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '4000') as t), 0.00, 1, 1, NOW()),
('4400', 'Booking Revenue', 'revenue', 'Income from facility/space bookings', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '4000') as t), 0.00, 1, 1, NOW()),
('4500', 'Interest Income', 'revenue', 'Income from interest earned', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '4000') as t), 0.00, 1, 0, NOW()),
('4900', 'Other Income', 'revenue', 'Miscellaneous income', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '4000') as t), 0.00, 1, 0, NOW());

-- ============================================
-- EXPENSES (5xxx)
-- ============================================

INSERT INTO `erp_accounts` (`account_code`, `account_name`, `account_type`, `description`, `parent_id`, `balance`, `is_active`, `is_system`, `created_at`) VALUES
('5000', 'Expenses', 'expense', 'Costs incurred in business operations', NULL, 0.00, 1, 1, NOW()),
('5100', 'Cost of Goods Sold', 'expense', 'Direct costs of goods sold', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '5000') as t), 0.00, 1, 1, NOW()),
('5200', 'Salaries & Wages', 'expense', 'Employee compensation', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '5000') as t), 0.00, 1, 1, NOW()),
('5210', 'Employer Pension Contribution', 'expense', 'Employer share of pension', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '5000') as t), 0.00, 1, 1, NOW()),
('5300', 'Rent Expense', 'expense', 'Cost of rented premises', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '5000') as t), 0.00, 1, 0, NOW()),
('5400', 'Utilities Expense', 'expense', 'Electricity, water, gas costs', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '5000') as t), 0.00, 1, 1, NOW()),
('5500', 'Office Supplies', 'expense', 'Stationery and office materials', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '5000') as t), 0.00, 1, 0, NOW()),
('5600', 'Depreciation Expense', 'expense', 'Depreciation of fixed assets', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '5000') as t), 0.00, 1, 1, NOW()),
('5700', 'Insurance Expense', 'expense', 'Business insurance costs', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '5000') as t), 0.00, 1, 0, NOW()),
('5800', 'Bank Charges', 'expense', 'Bank fees and charges', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '5000') as t), 0.00, 1, 0, NOW()),
('5900', 'Maintenance & Repairs', 'expense', 'Maintenance and repair costs', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '5000') as t), 0.00, 1, 0, NOW()),
('5950', 'Professional Fees', 'expense', 'Legal, accounting, consulting fees', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '5000') as t), 0.00, 1, 0, NOW()),
('5990', 'Other Expenses', 'expense', 'Miscellaneous expenses', (SELECT id FROM (SELECT id FROM `erp_accounts` WHERE account_code = '5000') as t), 0.00, 1, 0, NOW());

-- ============================================
-- Set default accounts
-- ============================================

-- Mark key accounts as defaults
UPDATE `erp_accounts` SET `is_default` = 1 WHERE `account_code` IN ('1130', '2110', '4100', '5100');

-- Add comment for documentation
-- This seed data creates a standard Nigerian business chart of accounts with:
-- - Asset accounts (1xxx): Current assets, fixed assets
-- - Liability accounts (2xxx): Current liabilities, long-term liabilities  
-- - Equity accounts (3xxx): Share capital, retained earnings
-- - Revenue accounts (4xxx): Sales, services, rental, booking income
-- - Expense accounts (5xxx): COGS, salaries, utilities, depreciation
