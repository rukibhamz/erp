-- Reset all data except users for fresh testing
-- WARNING: This will delete all data from most tables!

SET FOREIGN_KEY_CHECKS = 0;

-- Payroll tables
TRUNCATE TABLE `payslips`;
TRUNCATE TABLE `payroll_runs`;

-- Accounting tables
TRUNCATE TABLE `journal_entry_lines`;
TRUNCATE TABLE `journal_entries`;
TRUNCATE TABLE `transactions`;

-- Cash accounts - reset balances
UPDATE `cash_accounts` SET `current_balance` = `opening_balance`;

-- Accounts - reset balances
UPDATE `accounts` SET `balance` = 0;

-- Employees (keep structure, clear payroll data)
-- Uncomment the line below if you want to delete all employees
-- TRUNCATE TABLE `employees`;

-- Activity logs
TRUNCATE TABLE `activity_logs`;

SET FOREIGN_KEY_CHECKS = 1;

-- Set a test cash account balance
UPDATE `cash_accounts` SET `current_balance` = 5000000.00, `opening_balance` = 5000000.00 WHERE `id` = 1;

SELECT 'Data reset complete. Users and structure preserved.' as Status;
