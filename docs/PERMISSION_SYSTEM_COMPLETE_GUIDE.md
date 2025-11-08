# Complete Permission System Guide

## 📋 Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Problems Fixed](#problems-fixed)
4. [Migration Instructions](#migration-instructions)
5. [Testing](#testing)
6. [Role & Permission Matrix](#role--permission-matrix)
7. [Troubleshooting](#troubleshooting)
8. [Code Changes](#code-changes)
9. [Production Deployment](#production-deployment)

---

## Overview

This guide covers the complete permission system implementation, including fixes for:
- Missing `erp_role_permissions` table
- Manager role missing permissions
- Permission check logic not checking role-based permissions

### System Architecture

The permission system uses **two types of permissions**:

1. **User-Specific Permissions** (`user_permissions` table)
   - Directly assigned to individual users
   - Override role permissions if needed

2. **Role-Based Permissions** (`role_permissions` table)
   - Assigned to roles
   - All users with that role inherit these permissions
   - More efficient for managing permissions at scale

### Permission Check Flow

1. Get user's role from `users` table
2. Check if permission exists in `permissions` table
3. Check user-specific permissions (`user_permissions` table)
4. Check role-based permissions (`role_permissions` via `roles` table)
5. Return true if **EITHER** user-specific **OR** role-based permission exists
6. Log detailed debugging information

---

## Quick Start

### Step 1: Run Base Migration

**Option A: SQL Script (Recommended)**
```bash
mysql -u username -p database_name < database/migrations/001_permission_system_complete.sql
```

**Option B: PHP Script**
```bash
php database/migrations/001_permission_system_complete.php
```

### Step 2: Fix Manager Permissions (Accounting Sub-modules, Remove Tax, Add POS)

**Option A: SQL Script**
```bash
mysql -u username -p database_name < database/migrations/002_fix_manager_permissions.sql
```

**Option B: PHP Script**
```bash
php database/migrations/002_fix_manager_permissions.php
```

### Step 3: Test the System
```bash
php test_permission_system.php
```

---

## Problems Fixed

### Problem 1: Missing `erp_role_permissions` Table

**Error:** `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'database.erp_role_permissions' doesn't exist`

**Root Cause:** The junction table linking roles to permissions was missing from the database.

**Solution:** Migration creates the table with proper foreign keys and indexes.

### Problem 2: Manager Role Missing Permissions

**Error:** `Permission check: User 4, Module: settings, Permission: read, Result: NO`

**Root Cause:** Manager role had no permissions assigned in `erp_role_permissions` table.

**Solution:** Migration assigns all business module permissions to manager role.

### Problem 3: Permission Check Logic Bug

**Root Cause:** `hasPermission()` method only checked `user_permissions` table, ignoring `role_permissions` table.

**Solution:** Updated method to check BOTH user-specific and role-based permissions.

---

## Migration Instructions

### Files to Use

- **`database/migrations/001_permission_system_complete.sql`** - Complete SQL migration
- **`database/migrations/001_permission_system_complete.php`** - Complete PHP migration

### What Gets Created

#### Tables
- ✅ `erp_permissions` - All module permissions
- ✅ `erp_roles` - Role definitions
- ✅ `erp_role_permissions` - Junction table (CRITICAL)

#### Roles Created
- ✅ **super_admin** - All permissions (50+)
- ✅ **admin** - All permissions (50+)
- ✅ **manager** - Business modules (40+ permissions)
- ✅ **staff** - Read-only access (4 permissions)
- ✅ **user** - No default permissions
- ✅ **accountant** - Accounting module only (5 permissions)

#### Permissions Created

Each module has these permissions:
- `read` - View/list records
- `write` - Create/edit records (also covers `create` and `update`)
- `delete` - Delete records
- `create` - Create new records (mapped to `write` if not found)
- `update` - Update existing records (mapped to `write` if not found)

**Modules with permissions:**
- Accounting (5 permissions)
- Bookings (5 permissions)
- Properties (5 permissions)
- Inventory (5 permissions)
- Utilities (5 permissions)
- Settings (5 permissions)
- Dashboard (1 permission)
- Notifications (3 permissions)
- Users (5 permissions)
- Companies (3 permissions)
- Reports (2 permissions)
- Modules (2 permissions)

### Migration Safety

- ✅ **Idempotent** - Safe to run multiple times
- ✅ Uses `CREATE TABLE IF NOT EXISTS`
- ✅ Uses `INSERT IGNORE` for roles
- ✅ Checks for existing records before inserting
- ✅ No data loss - only adds missing tables/data

---

## Testing

### Comprehensive Test Script

Run the complete test suite:
```bash
php test_permission_system.php
```

This will:
- ✅ Check all tables exist
- ✅ Verify all 6 roles exist
- ✅ Verify all permissions exist
- ✅ Check role permission assignments
- ✅ Test permission checks for each role
- ✅ Create test users for each role
- ✅ Provide comprehensive report

### Manual Testing

#### Test Manager Role
1. Log in as a user with `role = 'manager'`
2. Should access: accounting, bookings, properties, inventory, utilities, settings, dashboard, notifications
3. Should NOT access: modules (unless explicitly granted)

#### Test Admin Role
1. Log in as a user with `role = 'admin'`
2. Should access ALL modules

#### Test Staff Role
1. Log in as a user with `role = 'staff'`
2. Should only READ: dashboard, notifications, bookings, properties

#### Test User Role
1. Log in as a user with `role = 'user'`
2. Should have NO default permissions

### Verification Queries

```sql
-- Check tables exist
SHOW TABLES LIKE 'erp_%permissions%';
SHOW TABLES LIKE 'erp_roles';

-- Check role permissions count
SELECT r.role_code, r.role_name, COUNT(rp.id) as permission_count
FROM erp_roles r
LEFT JOIN erp_role_permissions rp ON r.id = rp.role_id
GROUP BY r.id
ORDER BY r.role_code;

-- List manager permissions
SELECT p.module, p.permission
FROM erp_role_permissions rp
JOIN erp_permissions p ON rp.permission_id = p.id
JOIN erp_roles r ON rp.role_id = r.id
WHERE r.role_code = 'manager'
ORDER BY p.module, p.permission;

-- Verify User ID 4 has manager role
SELECT u.id, u.username, u.email, u.role as user_role, r.role_code, r.role_name
FROM erp_users u
LEFT JOIN erp_roles r ON u.role = r.role_code
WHERE u.id = 4;
```

---

## Role & Permission Matrix

| Role | Accounting | Bookings | Properties | Inventory | Utilities | Settings | Dashboard | Notifications | Users | Companies | Reports | Modules | POS | Tax |
|------|-----------|----------|------------|-----------|------------|----------|-----------|---------------|-------|-----------|---------|---------|-----|-----|
| **super_admin** | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All |
| **admin** | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All |
| **manager** | ✅ All* | ✅ All | ✅ All | ✅ All | ✅ All | ✅ All | ✅ Read | ✅ All | ❌ | ❌ | ❌ | ❌ | ✅ All | ❌ |
| **staff** | ❌ | ✅ Read | ✅ Read | ❌ | ❌ | ❌ | ✅ Read | ✅ Read | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **user** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **accountant** | ✅ All | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

**Legend:**
- ✅ All = read, write, delete, create, update
- ✅ All* = All permissions + Accounting sub-modules (accounts, cash, receivables, payables, ledger, estimates)
- ✅ Read = read only
- ❌ = No access

**Note:** Manager role has:
- ✅ All Accounting sub-modules: accounts, cash, receivables, payables, ledger, estimates
- ✅ POS module access
- ❌ Tax module access (removed)

---

## Troubleshooting

### "Table doesn't exist" Error

**Error:** `Table 'database.erp_role_permissions' doesn't exist`

**Solution:**
```bash
# Run the migration
php database/migrations/001_permission_system_complete.php

# Or SQL
mysql -u username -p database_name < database/migrations/001_permission_system_complete.sql
```

**Verify:**
```sql
SHOW TABLES LIKE 'erp_role_permissions';
```

### Permission Checks Failing

1. **Check user's role:**
   ```sql
   SELECT id, username, role FROM erp_users WHERE id = ?;
   ```

2. **Verify role exists:**
   ```sql
   SELECT * FROM erp_roles WHERE role_code = ?;
   ```

3. **Check permissions assigned to role:**
   ```sql
   SELECT COUNT(*) as count
   FROM erp_role_permissions rp
   JOIN erp_roles r ON rp.role_id = r.id
   WHERE r.role_code = ?;
   ```

4. **Check if permission exists:**
   ```sql
   SELECT * FROM erp_permissions 
   WHERE module = ? AND permission = ?;
   ```

5. **Check error logs** - The enhanced debugging logs detailed information

### Migration Fails

1. Check database connection
2. Check user has CREATE TABLE permissions
3. Check foreign key constraints aren't blocking
4. Review error logs for specific issues
5. Verify tables don't already exist with different structure

### Still Having Issues?

The enhanced `hasPermission()` method logs:
- User ID and role
- Whether user-specific permission exists
- Whether role-based permission exists
- All permissions for the role (when check fails)
- Permission ID being checked
- Full stack trace on errors

Check your error logs for this detailed information.

---

## Code Changes

### Modified: `application/models/User_permission_model.php`

**Changes:**
- ✅ `hasPermission()` now checks BOTH user_permissions AND role_permissions
- ✅ Added table existence check before querying
- ✅ Graceful fallback if tables missing
- ✅ Enhanced error messages pointing to migration script
- ✅ Better exception handling for missing tables
- ✅ Comprehensive debugging logs

**Key Code:**
```php
// Check user-specific permissions
$hasUserPermission = checkUserPermissions($userId, $permissionId);

// Check role-based permissions
$hasRolePermission = checkRolePermissions($userRole, $module, $permission);

// Return true if EITHER exists
return $hasUserPermission || $hasRolePermission;
```

### Files Created

- `database/migrations/001_permission_system_complete.sql` - Complete SQL migration
- `database/migrations/001_permission_system_complete.php` - Complete PHP migration
- `test_permission_system.php` - Comprehensive testing script

---

## Production Deployment

### Pre-Deployment Checklist

- [ ] Run migration on test environment
- [ ] Run test script: `php test_permission_system.php`
- [ ] Test each role manually
- [ ] Verify User ID 4 (manager) can access all modules
- [ ] Check error logs for permission issues
- [ ] Review permission matrix matches requirements
- [ ] Document any custom role assignments

### Deployment Steps

1. **Backup Database**
   ```bash
   mysqldump -u username -p database_name > backup_before_permission_migration.sql
   ```

2. **Run Migration**
   ```bash
   mysql -u username -p database_name < database/migrations/001_permission_system_complete.sql
   ```

3. **Verify Migration**
   ```bash
   php test_permission_system.php
   ```

4. **Test Production**
   - Log in as each role
   - Test access to each module
   - Monitor error logs

5. **Monitor**
   - Check error logs for permission issues
   - Verify all users can access appropriate modules
   - Review permission check logs

### Rollback Plan

If issues occur:
1. Restore database backup
2. Review error logs
3. Fix issues in test environment
4. Re-run migration

### Post-Deployment

- [ ] Monitor error logs for 24-48 hours
- [ ] Verify all roles working correctly
- [ ] Check user feedback
- [ ] Document any issues found

---

## Security Notes

- ✅ Super admin and admin bypass permission checks in code
- ✅ Role-based permissions are checked via `erp_role_permissions` table
- ✅ User-specific permissions override role permissions
- ✅ All permission checks are logged for auditing
- ✅ Foreign keys ensure data integrity
- ✅ Idempotent migrations prevent accidental duplicates

---

## File Structure

### Core Files
- `database/migrations/001_permission_system_complete.sql` - SQL migration
- `database/migrations/001_permission_system_complete.php` - PHP migration
- `test_permission_system.php` - Testing script
- `application/models/User_permission_model.php` - Permission check logic

### Documentation
- `PERMISSION_SYSTEM_COMPLETE_GUIDE.md` - This file (complete guide)

---

## Quick Reference

### Run Migration
```bash
mysql -u username -p database_name < database/migrations/001_permission_system_complete.sql
```

### Test System
```bash
php test_permission_system.php
```

### Check Role Permissions
```sql
SELECT r.role_code, COUNT(rp.id) as permissions
FROM erp_roles r
LEFT JOIN erp_role_permissions rp ON r.id = rp.role_id
GROUP BY r.id;
```

### Check User Permissions
```sql
SELECT p.module, p.permission
FROM erp_users u
JOIN erp_roles r ON u.role = r.role_code
JOIN erp_role_permissions rp ON r.id = rp.role_id
JOIN erp_permissions p ON rp.permission_id = p.id
WHERE u.id = ?;
```

---

## Support

For issues or questions:
1. Check error logs for detailed debugging info
2. Run test script to identify problems
3. Review this guide's troubleshooting section
4. Check code comments in `User_permission_model.php`

---

**Status:** ✅ Production Ready  
**Last Updated:** Consolidated for testing phase  
**Version:** 1.0

