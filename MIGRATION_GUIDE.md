# Database Migration Guide

## Overview
This guide explains how to run database migrations for the ERP system. Migrations are SQL scripts that create or modify database tables and data.

## Quick Start

### For New Installations

Run migrations in order:

```bash
# 1. Permission System (CRITICAL - Must run first)
mysql -u username -p database_name < database/migrations/001_permission_system_complete.sql

# 2. Business Module Tables
mysql -u username -p database_name < database/migrations/004_create_business_module_tables.sql
```

Or using PHP:

```bash
# 1. Permission System
php database/migrations/001_permission_system_complete.php

# 2. Business Module Tables
php database/migrations/004_create_business_module_tables.php
```

## Migration Files

### 001_permission_system_complete.sql / .php
**Purpose:** Creates the complete permission system
**Includes:**
- Permission tables (`erp_permissions`, `erp_roles`, `erp_role_permissions`)
- All system roles (super_admin, admin, manager, staff, user, accountant)
- All module permissions
- Role-based permission assignments
- Manager permissions (Accounting sub-modules, POS, no Tax)
- Staff permissions (POS, Bookings, Inventory, Utilities)

**When to Run:**
- New installations (required)
- If permission system is broken
- After database reset

**Idempotent:** Yes - Safe to run multiple times

### 004_create_business_module_tables.sql / .php
**Purpose:** Creates missing business module tables
**Includes:**
- `erp_spaces` - Property spaces/units
- `erp_stock_levels` - Inventory stock levels
- `erp_items` - Inventory items master
- `erp_leases` - Property leases
- `erp_work_orders` - Maintenance work orders
- `erp_tax_deadlines` - Tax compliance deadlines
- `erp_utility_bills` - Utility bills

**When to Run:**
- New installations
- If dashboard shows "Table not found" errors
- After database reset

**Idempotent:** Yes - Safe to run multiple times

## Running Migrations

### Method 1: SQL Files (Recommended)

```bash
# Single migration
mysql -u your_username -p your_database < database/migrations/001_permission_system_complete.sql

# Multiple migrations
mysql -u your_username -p your_database < database/migrations/001_permission_system_complete.sql
mysql -u your_username -p your_database < database/migrations/004_create_business_module_tables.sql
```

### Method 2: PHP Scripts

```bash
# From project root
php database/migrations/001_permission_system_complete.php
php database/migrations/004_create_business_module_tables.php
```

### Method 3: phpMyAdmin / MySQL Workbench

1. Open phpMyAdmin or MySQL Workbench
2. Select your database
3. Go to SQL tab
4. Copy and paste the contents of the migration file
5. Execute

## Verification

After running migrations, verify they worked:

```sql
-- Check permission tables exist
SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = DATABASE()
AND table_name IN ('erp_permissions', 'erp_roles', 'erp_role_permissions');

-- Check business module tables exist
SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = DATABASE()
AND table_name IN ('erp_spaces', 'erp_stock_levels', 'erp_items', 
                   'erp_leases', 'erp_work_orders', 'erp_tax_deadlines', 
                   'erp_utility_bills');

-- Check role permissions count
SELECT r.role_code, COUNT(rp.id) as permission_count
FROM erp_roles r
LEFT JOIN erp_role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.role_code
ORDER BY r.role_code;
```

## Troubleshooting

### Error: "Table already exists"
**Solution:** This is normal - migrations use `CREATE TABLE IF NOT EXISTS`. The migration is idempotent and safe to run multiple times.

### Error: "Access denied"
**Solution:** Ensure your database user has CREATE, ALTER, and INSERT permissions.

### Error: "Foreign key constraint fails"
**Solution:** Run migrations in order. Migration 001 must run before 004.

### Error: "Unknown column"
**Solution:** Check if you're running migrations on the correct database. Verify table structure matches expected schema.

## Migration Order

**Critical:** Always run migrations in this order:

1. `001_permission_system_complete.sql` - Foundation tables
2. `004_create_business_module_tables.sql` - Business module tables

## Production Deployment

### Pre-Deployment Checklist

- [ ] Backup database
- [ ] Test migrations on staging environment
- [ ] Verify all tables created successfully
- [ ] Test permission system
- [ ] Test dashboard loads without errors
- [ ] Verify role permissions are correct

### Deployment Steps

1. **Backup Database**
   ```bash
   mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
   ```

2. **Run Migrations**
   ```bash
   mysql -u username -p database_name < database/migrations/001_permission_system_complete.sql
   mysql -u username -p database_name < database/migrations/004_create_business_module_tables.sql
   ```

3. **Verify**
   - Check error logs
   - Test login with different roles
   - Verify dashboard loads
   - Check permission checks work

4. **Rollback Plan** (if needed)
   ```bash
   mysql -u username -p database_name < backup_YYYYMMDD.sql
   ```

## Migration Best Practices

1. **Always Backup First** - Never run migrations on production without backup
2. **Test on Staging** - Test all migrations on staging environment first
3. **Run in Order** - Follow the migration order specified
4. **Verify After** - Always verify migrations completed successfully
5. **Check Logs** - Review error logs after running migrations
6. **Document Changes** - Keep track of which migrations have been run

## Common Issues

### Issue: Dashboard shows "0" for all metrics
**Cause:** Missing business module tables
**Solution:** Run `004_create_business_module_tables.sql`

### Issue: Permission denied for manager/staff roles
**Cause:** Permission system tables missing or incomplete
**Solution:** Run `001_permission_system_complete.sql`

### Issue: "Table doesn't exist" errors in logs
**Cause:** Missing tables
**Solution:** Run appropriate migration file

## Support

If you encounter issues:
1. Check error logs (`application/logs/` or PHP error log)
2. Verify database connection settings
3. Check user permissions
4. Review migration file for syntax errors
5. Ensure migrations run in correct order

---

**Last Updated:** Current Session
**Version:** 1.0

