# CRITICAL PERMISSION BUG FIX - Manager Role Permissions

## Problem Summary

The "manager" role was missing permissions for critical business modules:
- accounting
- bookings
- properties
- inventory
- utilities
- settings

The permission check system was only checking `user_permissions` table and NOT checking `role_permissions` table, causing managers to be denied access even when their role should have permissions.

## Files Modified

### 1. `application/models/User_permission_model.php`
**Fixed:** `hasPermission()` method now checks BOTH:
- User-specific permissions (`user_permissions` table)
- Role-based permissions (`role_permissions` table via `roles` table`)

**Added:** Comprehensive debugging that logs:
- User ID and role
- Whether user-specific permission exists
- Whether role-based permission exists
- All permissions for the user's role (when check fails)
- Permission ID being checked

### 2. `fix_manager_permissions.sql`
**Purpose:** Immediate SQL fix to grant all required permissions to manager role

**Usage:**
```bash
mysql -u your_username -p your_database < fix_manager_permissions.sql
```

Or run directly in phpMyAdmin/MySQL client.

### 3. `database/fix_manager_permissions.php`
**Purpose:** PHP script to programmatically fix manager permissions (idempotent - safe to run multiple times)

**Usage:**
```bash
php database/fix_manager_permissions.php
```

Or include it in your application:
```php
require_once 'database/fix_manager_permissions.php';
fixManagerPermissions($db);
```

## Immediate Fix Steps

### Option 1: SQL Script (Fastest)
1. Run `fix_manager_permissions.sql` in your MySQL client
2. Verify with the diagnostic queries in the SQL file

### Option 2: PHP Script
1. Run `php database/fix_manager_permissions.php`
2. Check the output for success messages

## Verification Queries

After applying the fix, run these queries to verify:

```sql
-- 1. Check manager role exists
SELECT * FROM erp_roles WHERE role_code = 'manager';

-- 2. Check all permissions for manager role
SELECT p.module, p.permission, r.role_code as role
FROM erp_role_permissions rp
JOIN erp_permissions p ON rp.permission_id = p.id
JOIN erp_roles r ON rp.role_id = r.id
WHERE r.role_code = 'manager' 
AND p.module IN ('accounting', 'bookings', 'properties', 'inventory', 'utilities', 'settings')
ORDER BY p.module, p.permission;

-- Expected: Should return at least 30 rows (6 modules × 5 permissions each)

-- 3. Verify User ID 4 has manager role
SELECT u.id, u.username, u.email, u.role as user_role, r.role_code, r.role_name
FROM erp_users u
LEFT JOIN erp_roles r ON u.role = r.role_code
WHERE u.id = 4;

-- 4. Test permission check for User 4
-- This should now return permissions via role_permissions
SELECT p.module, p.permission
FROM erp_users u
JOIN erp_roles r ON u.role = r.role_code
JOIN erp_role_permissions rp ON r.id = rp.role_id
JOIN erp_permissions p ON rp.permission_id = p.id
WHERE u.id = 4
AND p.module IN ('accounting', 'bookings', 'properties', 'inventory', 'utilities', 'settings')
ORDER BY p.module, p.permission;
```

## Expected Outcome

After applying the fix:
- ✅ User ID 4 (and all managers) can access all modules: accounting, bookings, properties, inventory, utilities, settings
- ✅ Permission check logs show "Result: YES" for manager + all these modules
- ✅ Fix is permanent and handles future role assignments
- ✅ Enhanced debugging provides clear information when permission checks fail

## Permission Structure

The system now supports two types of permissions:

1. **User-Specific Permissions** (`user_permissions` table)
   - Directly assigned to individual users
   - Override role permissions if needed

2. **Role-Based Permissions** (`role_permissions` table)
   - Assigned to roles
   - All users with that role inherit these permissions
   - More efficient for managing permissions at scale

## Permission Check Logic Flow

1. Get user's role from `users` table
2. Check if permission exists in `permissions` table
3. Check user-specific permissions (`user_permissions` table)
4. Check role-based permissions (`role_permissions` via `roles` table)
5. Return true if EITHER user-specific OR role-based permission exists
6. Log detailed debugging information

## Modules and Permissions

Each module should have these permissions:
- `read` - View/list records
- `write` - Create/edit records (also covers `create` and `update`)
- `delete` - Delete records
- `create` - Create new records (mapped to `write` if not found)
- `update` - Update existing records (mapped to `write` if not found)

## Troubleshooting

### Permission check still failing?

1. Check error logs for detailed debugging output
2. Verify user has correct role:
   ```sql
   SELECT id, username, role FROM erp_users WHERE id = 4;
   ```

3. Verify role exists in roles table:
   ```sql
   SELECT * FROM erp_roles WHERE role_code = 'manager';
   ```

4. Verify permissions are assigned to role:
   ```sql
   SELECT COUNT(*) as count
   FROM erp_role_permissions rp
   JOIN erp_roles r ON rp.role_id = r.id
   WHERE r.role_code = 'manager';
   ```

5. Check if permission exists:
   ```sql
   SELECT * FROM erp_permissions 
   WHERE module = 'settings' AND permission = 'read';
   ```

### Still having issues?

The enhanced debugging in `hasPermission()` will now log:
- User ID and role
- Whether user-specific permission exists
- Whether role-based permission exists
- All permissions for the role
- The exact SQL queries being executed

Check your error logs for this detailed information.

## Testing

After applying the fix:

1. Log in as User ID 4 (manager role)
2. Try accessing:
   - Settings module
   - Accounting module
   - Bookings module
   - Properties module
   - Inventory module
   - Utilities module
3. Check error logs - should see "Result: YES" for all permission checks
4. If any fail, check the detailed debug logs for the reason

## Notes

- The fix is **idempotent** - safe to run multiple times
- The SQL script uses `INSERT IGNORE` to prevent duplicate errors
- The PHP script checks for existing permissions before creating/assigning
- Both user-specific and role-based permissions are checked (OR logic)
- Super admin and admin roles still bypass all permission checks

