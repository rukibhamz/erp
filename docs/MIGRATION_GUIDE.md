# Database Migration Guide

## Overview
This guide explains how to run database migrations for the ERP system. Migrations are SQL scripts that create or modify database tables and data.

## Quick Start

### For New Installations

**Run ONE migration file for everything:**

```bash
# SQL (Recommended)
mysql -u username -p database_name < database/migrations/000_complete_system_migration.sql

# OR PHP Migration Runner
php database/migrations/migrate.php up
```

**That's it!** This single migration includes:
- ✅ Permission system (tables, roles, permissions)
- ✅ Business module tables (7 tables)
- ✅ Role-based permission assignments

## Migration Files

### 000_complete_system_migration.sql (RECOMMENDED FOR NEW INSTALLS)
**Purpose:** Complete system migration (ALL-IN-ONE)
**Includes:**
- ✅ Permission system (tables, roles, permissions)
- ✅ Business module tables (7 tables)
- ✅ Role-based permission assignments
- ✅ Manager permissions (Accounting sub-modules, POS, no Tax)
- ✅ Staff permissions (POS, Bookings, Inventory, Utilities)

**When to Run:**
- New installations (required)
- After database reset
- If system is completely broken

**Idempotent:** Yes - Safe to run multiple times

**Usage:**
```bash
mysql -u username -p database_name < database/migrations/000_complete_system_migration.sql
```

### Legacy Migration Files (For Reference Only)
- `001_permission_system_complete.sql` - Legacy (merged into 000)
- `004_create_business_module_tables.sql` - Legacy (merged into 000)

**Note:** For new installations, use `000_complete_system_migration.sql` only.

## Running Migrations

### Method 1: SQL File (Recommended for New Installs)

```bash
# Single complete migration
mysql -u your_username -p your_database < database/migrations/000_complete_system_migration.sql
```

### Method 2: Migration Runner (Recommended for Existing Installs)

```bash
# Check status
php database/migrations/migrate.php status

# Run all pending migrations
php database/migrations/migrate.php up

# Rollback last batch (manual process)
php database/migrations/migrate.php down
```

### Method 3: Legacy PHP Scripts (If Needed)

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

**For New Installations:**
1. Run installer (`/install/`)
2. Run `000_complete_system_migration.sql` - Complete system setup

**For Existing Installations:**
- Use migration runner: `php database/migrations/migrate.php up`
- Migrations are tracked in `erp_migrations` table

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

