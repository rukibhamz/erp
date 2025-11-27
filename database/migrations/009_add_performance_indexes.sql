-- Add Performance Indexes
-- Migration: 009_add_performance_indexes.sql
-- Purpose: Improve query performance on frequently accessed columns

-- Journal Entries
ALTER TABLE erp_journal_entries 
ADD INDEX IF NOT EXISTS idx_reference (reference_type, reference_id),
ADD INDEX IF NOT EXISTS idx_date (entry_date),
ADD INDEX IF NOT EXISTS idx_status (status);

-- Journal Entry Lines
ALTER TABLE erp_journal_entry_lines
ADD INDEX IF NOT EXISTS idx_entry_id (entry_id),
ADD INDEX IF NOT EXISTS idx_account_id (account_id);

-- Accounts
ALTER TABLE erp_accounts
ADD INDEX IF NOT EXISTS idx_account_code (account_code),
ADD INDEX IF NOT EXISTS idx_account_type (account_type),
ADD INDEX IF NOT EXISTS idx_parent (parent_account_id);

-- Transactions (old table, if still used)
ALTER TABLE erp_transactions
ADD INDEX IF NOT EXISTS idx_reference (reference_type, reference_id),
ADD INDEX IF NOT EXISTS idx_account (account_id),
ADD INDEX IF NOT EXISTS idx_date (transaction_date);

-- Invoices
ALTER TABLE erp_invoices
ADD INDEX IF NOT EXISTS idx_customer (customer_id),
ADD INDEX IF NOT EXISTS idx_status (status),
ADD INDEX IF NOT EXISTS idx_date (invoice_date);

-- Bills
ALTER TABLE erp_bills
ADD INDEX IF NOT EXISTS idx_supplier (supplier_id),
ADD INDEX IF NOT EXISTS idx_status (status),
ADD INDEX IF NOT EXISTS idx_date (bill_date);

-- Fixed Assets
ALTER TABLE erp_fixed_assets
ADD INDEX IF NOT EXISTS idx_status (asset_status),
ADD INDEX IF NOT EXISTS idx_category (asset_category),
ADD INDEX IF NOT EXISTS idx_location (location_id);

-- Bookings
ALTER TABLE erp_bookings
ADD INDEX IF NOT EXISTS idx_customer (customer_id),
ADD INDEX IF NOT EXISTS idx_status (status),
ADD INDEX IF NOT EXISTS idx_dates (check_in_date, check_out_date);

-- Payroll Runs
ALTER TABLE erp_payroll_runs
ADD INDEX IF NOT EXISTS idx_status (status),
ADD INDEX IF NOT EXISTS idx_period (period_start, period_end);

-- Users
ALTER TABLE erp_users
ADD INDEX IF NOT EXISTS idx_email (email),
ADD INDEX IF NOT EXISTS idx_role (role),
ADD INDEX IF NOT EXISTS idx_status (status);
