# Entities and Locations Migration Guide

## Overview

The system has been refactored to use **Entities** (formerly Companies) and **Locations** (formerly Properties). This change allows each entity to manage its own records, taxes, utilities, employees, etc., while locations replace properties in the system.

## Automatic Migration

The AutoMigration system has been enhanced to automatically detect and apply these updates for existing installations.

### How It Works

1. **On Application Startup**: The `AutoMigration` class runs automatically
2. **Detection**: It checks for:
   - `entities` module label in `erp_module_labels` table
   - `locations` module label in `erp_module_labels` table
   - `entities` permissions in `erp_permissions` table
   - `locations` permissions in `erp_permissions` table
3. **Auto-Update**: If any of these are missing, it automatically re-runs the migration to add them

### What Gets Updated

When the migration runs, it will:

1. **Add Module Labels**:
   - `entities` - "Entities" (formerly Companies)
   - `locations` - "Locations" (formerly Properties)
   - Legacy `properties` label is maintained for backward compatibility

2. **Add Permissions**:
   - `entities.read`, `entities.write`, `entities.create`, `entities.update`, `entities.delete`
   - `locations.read`, `locations.write`, `locations.create`, `locations.update`, `locations.delete`
   - Legacy `properties.*` and `companies.*` permissions are maintained

3. **Assign Permissions to Roles**:
   - Super Admin and Admin: All permissions (including entities and locations)
   - Manager: Business module permissions (including locations, excluding tax)
   - Staff: POS, Bookings, Inventory, Utilities permissions

### Idempotency

The migration is **completely safe** to run multiple times:
- Uses `INSERT IGNORE` for permissions (skips if already exists)
- Uses `ON DUPLICATE KEY UPDATE` for module labels (updates if exists)
- Uses `NOT EXISTS` checks for role_permissions (only inserts new ones)

### For Existing Installations

**No manual action required!** When you pull the latest code:

1. The AutoMigration will detect missing entities/locations data
2. It will automatically run the migration on the next page load
3. The update happens silently in the background
4. Your existing data is preserved (companies and properties tables remain unchanged)

### Backward Compatibility

- Legacy routes still work: `/companies` → `Entities`, `/properties` → `Locations`
- Database table names unchanged: `companies` and `properties` tables remain
- Legacy permissions maintained alongside new ones
- Views include both new and legacy variable names

### Front-End Customization

Module names can be customized via **Settings → Module Customization**:
- Default: "Entities" and "Locations"
- Super Admins can rename these without code changes
- Changes apply immediately across the system

## Manual Migration (If Needed)

If for some reason the automatic migration doesn't run, you can manually execute:

```bash
mysql -u username -p database_name < database/migrations/000_complete_system_migration.sql
```

This is safe to run multiple times and will only add missing data.

## Verification

After the migration runs, verify the updates:

```sql
-- Check module labels
SELECT * FROM erp_module_labels WHERE module_code IN ('entities', 'locations');

-- Check permissions
SELECT * FROM erp_permissions WHERE module IN ('entities', 'locations');

-- Check role permissions
SELECT r.role_code, p.module, p.permission
FROM erp_role_permissions rp
JOIN erp_roles r ON rp.role_id = r.id
JOIN erp_permissions p ON rp.permission_id = p.id
WHERE p.module IN ('entities', 'locations')
ORDER BY r.role_code, p.module, p.permission;
```

## Troubleshooting

If the migration doesn't run automatically:

1. Check error logs: `error_log` entries will show AutoMigration activity
2. Verify database connection in `config/config.installed.php`
3. Check that `erp_migrations` table exists
4. Manually run the migration if needed (see above)

## Summary

- ✅ Automatic migration on application startup
- ✅ Detects missing entities/locations data
- ✅ Idempotent and safe to run multiple times
- ✅ Preserves existing data
- ✅ Backward compatible with legacy routes and tables
- ✅ No manual intervention required

