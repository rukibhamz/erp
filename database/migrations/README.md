# Database Migrations

## Overview

This directory contains SQL migration files for the ERP system. Migrations are used to set up and update the database schema.

## Migration Files

### 001_create_initial_tables.sql
- Creates core database tables
- Sets up users, permissions, and authentication
- **Status**: Core system requirement

### 002_create_accounting_tables.sql
- Creates accounting-related tables
- Journal entries, accounts, transactions
- **Status**: Required for accounting module

### 003_create_module_tables.sql
- Creates tables for various modules
- Inventory, payroll, bookings, etc.
- **Status**: Required for full system functionality

### 004_standardize_accounts_table.sql
- Adds columns to accounts table
- parent_account_id, account_category, is_system_account
- **Status**: Required for account hierarchy

### 005_create_payroll_tables.sql
- Creates payroll_runs and payslips tables
- **Status**: Required for payroll module

### 006_install_default_coa.sql
- Installs default Chart of Accounts
- QuickBooks-style account structure
- **Status**: Recommended (can customize after)

### 007_add_payroll_posted_columns.sql
- Adds posted_date and posted_by to payroll_runs
- **Status**: Required for payroll accounting integration

### 008_add_phase12_accounts.sql
- Adds Fixed Assets accounts
- Security Deposits, Gain/Loss accounts
- **Status**: Required for Fixed Assets and Leases modules

### 009_add_performance_indexes.sql
- Adds database indexes for performance
- **Status**: Recommended for production

## How to Run Migrations

### Method 1: Automatic (Recommended)

The system automatically runs pending migrations on first load via `AutoMigration.php`.

1. Place SQL files in `database/migrations/`
2. Access the application
3. Migrations run automatically

### Method 2: Manual

Run migrations manually via MySQL:

```bash
# Run all migrations
cd database/migrations
for file in *.sql; do
    mysql -u username -p database_name < "$file"
done
```

Or run individual migrations:

```bash
mysql -u username -p database_name < 008_add_phase12_accounts.sql
```

### Method 3: Via PHP

```php
// In your controller or script
$this->db->query(file_get_contents('database/migrations/008_add_phase12_accounts.sql'));
```

## Migration Naming Convention

Migrations are numbered sequentially:

```
XXX_descriptive_name.sql
```

- **XXX**: 3-digit number (001, 002, etc.)
- **descriptive_name**: Brief description of migration
- **.sql**: SQL file extension

## Creating New Migrations

1. **Determine next number**: Check existing migrations
2. **Create file**: `010_your_migration_name.sql`
3. **Write SQL**: Use idempotent operations when possible
4. **Test**: Run on development database first
5. **Document**: Add entry to this README

### Best Practices

- **Use IF NOT EXISTS**: For CREATE TABLE statements
- **Use ON DUPLICATE KEY UPDATE**: For INSERT statements
- **Add comments**: Explain complex operations
- **Be reversible**: Consider how to undo changes
- **Test thoroughly**: On development environment first

Example:

```sql
-- Add new column if it doesn't exist
ALTER TABLE erp_users 
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) AFTER email;

-- Insert with duplicate handling
INSERT INTO erp_accounts (account_code, account_name, account_type)
VALUES ('1000', 'Cash', 'Assets')
ON DUPLICATE KEY UPDATE account_name = account_name;
```

## Troubleshooting

### "Table already exists"
- Migration already run
- Check if using `IF NOT EXISTS`
- Safe to ignore if table structure is correct

### "Duplicate entry"
- Record already exists
- Check if using `ON DUPLICATE KEY UPDATE`
- Verify unique constraints

### "Column already exists"
- Migration already run
- Check if using `IF NOT EXISTS` for ALTER TABLE
- Safe to ignore if column exists

### "Syntax error"
- Check SQL syntax
- Verify table/column names
- Check for missing semicolons
- Ensure proper quoting

## Migration Status Tracking

The system tracks which migrations have been run in the `migrations` table:

```sql
SELECT * FROM erp_migrations ORDER BY migration_name;
```

## Rollback

To rollback a migration:

1. **Create reverse migration**: Write SQL to undo changes
2. **Test carefully**: Ensure data integrity
3. **Backup first**: Always backup before rollback

Example rollback:

```sql
-- Rollback 008_add_phase12_accounts.sql
DELETE FROM erp_accounts WHERE account_code IN (
    '1500', '1510', '1520', '1530', '1540', '1550', 
    '1590', '2210', '4900', '6200', '7000'
);
```

## Production Deployment

Before deploying to production:

1. ✅ **Backup database**: Full backup before migrations
2. ✅ **Test on staging**: Run migrations on staging environment
3. ✅ **Review changes**: Verify all migrations are correct
4. ✅ **Plan rollback**: Have rollback plan ready
5. ✅ **Monitor**: Watch for errors during deployment
6. ✅ **Verify**: Check application functionality after

## Support

If you encounter issues with migrations:

1. Check error logs
2. Verify database permissions
3. Review migration SQL
4. Test on development database
5. Contact support if needed

## Notes

- Migrations are run in numerical order
- Once run, migrations should not be modified
- Create new migration to make changes
- Always backup before running migrations
- Test on development environment first
