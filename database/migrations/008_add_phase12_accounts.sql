-- Add Fixed Assets and Related Accounts
-- Migration: 008_add_phase12_accounts.sql
-- Purpose: Add accounts required for Fixed Assets, Leases, and related modules

-- Buildings
INSERT INTO erp_accounts (account_code, account_name, account_type, account_category, is_system_account, description, created_at)
VALUES ('1500', 'Buildings', 'Assets', 'Fixed Assets', 1, 'Building and property assets', NOW())
ON DUPLICATE KEY UPDATE account_name = account_name;

-- Furniture & Fixtures
INSERT INTO erp_accounts (account_code, account_name, account_type, account_category, is_system_account, description, created_at)
VALUES ('1510', 'Furniture & Fixtures', 'Assets', 'Fixed Assets', 1, 'Office furniture and fixtures', NOW())
ON DUPLICATE KEY UPDATE account_name = account_name;

-- Equipment
INSERT INTO erp_accounts (account_code, account_name, account_type, account_category, is_system_account, description, created_at)
VALUES ('1520', 'Equipment', 'Assets', 'Fixed Assets', 1, 'Machinery and equipment', NOW())
ON DUPLICATE KEY UPDATE account_name = account_name;

-- Vehicles
INSERT INTO erp_accounts (account_code, account_name, account_type, account_category, is_system_account, description, created_at)
VALUES ('1530', 'Vehicles', 'Assets', 'Fixed Assets', 1, 'Company vehicles', NOW())
ON DUPLICATE KEY UPDATE account_name = account_name;

-- Computer Equipment
INSERT INTO erp_accounts (account_code, account_name, account_type, account_category, is_system_account, description, created_at)
VALUES ('1540', 'Computer Equipment', 'Assets', 'Fixed Assets', 1, 'Computers and IT equipment', NOW())
ON DUPLICATE KEY UPDATE account_name = account_name;

-- Leasehold Improvements
INSERT INTO erp_accounts (account_code, account_name, account_type, account_category, is_system_account, description, created_at)
VALUES ('1550', 'Leasehold Improvements', 'Assets', 'Fixed Assets', 1, 'Improvements to leased property', NOW())
ON DUPLICATE KEY UPDATE account_name = account_name;

-- Accumulated Depreciation (Contra-Asset)
INSERT INTO erp_accounts (account_code, account_name, account_type, account_category, is_system_account, description, created_at)
VALUES ('1590', 'Accumulated Depreciation', 'Assets', 'Contra-Asset', 1, 'Accumulated depreciation on fixed assets', NOW())
ON DUPLICATE KEY UPDATE account_name = account_name;

-- Security Deposits Payable
INSERT INTO erp_accounts (account_code, account_name, account_type, account_category, is_system_account, description, created_at)
VALUES ('2210', 'Security Deposits Payable', 'Liabilities', 'Current Liabilities', 1, 'Security deposits received from tenants', NOW())
ON DUPLICATE KEY UPDATE account_name = account_name;

-- Gain on Asset Disposal
INSERT INTO erp_accounts (account_code, account_name, account_type, account_category, is_system_account, description, created_at)
VALUES ('4900', 'Gain on Asset Disposal', 'Revenue', 'Other Income', 1, 'Gains from sale of fixed assets', NOW())
ON DUPLICATE KEY UPDATE account_name = account_name;

-- Depreciation Expense
INSERT INTO erp_accounts (account_code, account_name, account_type, account_category, is_system_account, description, created_at)
VALUES ('6200', 'Depreciation Expense', 'Expenses', 'Operating Expenses', 1, 'Depreciation on fixed assets', NOW())
ON DUPLICATE KEY UPDATE account_name = account_name;

-- Loss on Asset Disposal
INSERT INTO erp_accounts (account_code, account_name, account_type, account_category, is_system_account, description, created_at)
VALUES ('7000', 'Loss on Asset Disposal', 'Expenses', 'Other Expenses', 1, 'Losses from sale of fixed assets', NOW())
ON DUPLICATE KEY UPDATE account_name = account_name;
