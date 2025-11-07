# Module Access Permissions and Rights

## Overview

The ERP system uses a **granular, user-based permission system** where permissions are assigned individually to each user, rather than being role-based by default. However, the **Super Admin** role has special privileges that bypass all permission checks.

---

## User Roles

The system defines the following roles:

1. **Super Admin** (`super_admin`)
2. **Admin** (`admin`)
3. **Manager** (`manager`)
4. **Staff** (`staff`)
5. **User** (`user`)

---

## Permission System Architecture

### Permission Structure

Permissions are organized by:
- **Module**: The functional area (e.g., `accounting`, `bookings`, `properties`)
- **Action**: The operation type (`create`, `read`, `update`, `delete`)

### Permission Check Flow

1. **Super Admin Bypass**: If user role is `super_admin`, all permission checks return `true`
2. **User-Specific Permissions**: For all other roles, permissions are checked against the `user_permissions` table
3. **No Default Permissions**: By default, **Admin**, **Manager**, and **Staff** roles have **NO permissions** unless explicitly assigned

---

## Super Admin Rights

### Full System Access
- ✅ **All modules**: Full access to all modules
- ✅ **All actions**: Create, Read, Update, Delete on all resources
- ✅ **System settings**: Can manage modules, users, companies, and all settings
- ✅ **Permission management**: Can assign/revoke permissions for any user
- ✅ **Module management**: Can activate/deactivate and rename modules (exclusive to super admin)

### Special Privileges
- Bypasses all permission checks
- Cannot be restricted by permissions
- Only role that can access `/modules/` (Module Management)

---

## Admin Role

### Default Permissions
**⚠️ IMPORTANT**: Admin users have **NO default permissions**. Permissions must be explicitly assigned.

### Typical Admin Permissions (Recommended)
Admins typically need access to most modules for administrative tasks:

| Module | Create | Read | Update | Delete |
|--------|--------|------|--------|--------|
| **Users** | ✅ | ✅ | ✅ | ✅ |
| **Companies** | ✅ | ✅ | ✅ | ✅ |
| **Settings** | ✅ | ✅ | ✅ | ❌ |
| **Reports** | ✅ | ✅ | ✅ | ❌ |
| **Modules** | ❌ | ❌ | ❌ | ❌ |
| **Accounting** | ✅ | ✅ | ✅ | ✅ |
| **Bookings** | ✅ | ✅ | ✅ | ✅ |
| **Properties** | ✅ | ✅ | ✅ | ✅ |
| **Utilities** | ✅ | ✅ | ✅ | ✅ |
| **Inventory** | ✅ | ✅ | ✅ | ✅ |
| **Tax** | ✅ | ✅ | ✅ | ✅ |
| **POS** | ✅ | ✅ | ✅ | ✅ |

### Access Restrictions
- ❌ **Cannot** manage modules (Module Management is super admin only)
- ❌ **Cannot** access system-level configurations without explicit permissions
- ✅ **Can** manage users and assign permissions (if granted `users` permissions)

---

## Manager Role

### Default Permissions
**⚠️ IMPORTANT**: Manager users have **NO default permissions**. Permissions must be explicitly assigned.

### Typical Manager Permissions (Recommended)
Managers typically need read/write access to operational modules but limited access to system administration:

| Module | Create | Read | Update | Delete |
|--------|--------|------|--------|--------|
| **Users** | ❌ | ✅ | ❌ | ❌ |
| **Companies** | ❌ | ✅ | ❌ | ❌ |
| **Settings** | ❌ | ✅ | ❌ | ❌ |
| **Reports** | ✅ | ✅ | ✅ | ❌ |
| **Modules** | ❌ | ❌ | ❌ | ❌ |
| **Accounting** | ✅ | ✅ | ✅ | ❌ |
| **Bookings** | ✅ | ✅ | ✅ | ✅ |
| **Properties** | ✅ | ✅ | ✅ | ❌ |
| **Utilities** | ✅ | ✅ | ✅ | ❌ |
| **Inventory** | ✅ | ✅ | ✅ | ❌ |
| **Tax** | ✅ | ✅ | ✅ | ❌ |
| **POS** | ✅ | ✅ | ✅ | ❌ |

### Access Characteristics
- ✅ **Operational oversight**: Can view and manage day-to-day operations
- ✅ **Reporting**: Full access to reports for decision-making
- ❌ **System administration**: Limited or no access to system settings
- ❌ **User management**: Typically read-only or no access

---

## Staff Role

### Default Permissions
**⚠️ IMPORTANT**: Staff users have **NO default permissions**. Permissions must be explicitly assigned.

### Typical Staff Permissions (Recommended)
Staff typically need limited access focused on their specific job functions:

| Module | Create | Read | Update | Delete |
|--------|--------|------|--------|--------|
| **Users** | ❌ | ❌ | ❌ | ❌ |
| **Companies** | ❌ | ❌ | ❌ | ❌ |
| **Settings** | ❌ | ❌ | ❌ | ❌ |
| **Reports** | ❌ | ✅ | ❌ | ❌ |
| **Modules** | ❌ | ❌ | ❌ | ❌ |
| **Accounting** | ✅ | ✅ | ✅ | ❌ |
| **Bookings** | ✅ | ✅ | ✅ | ❌ |
| **Properties** | ❌ | ✅ | ❌ | ❌ |
| **Utilities** | ✅ | ✅ | ✅ | ❌ |
| **Inventory** | ✅ | ✅ | ✅ | ❌ |
| **Tax** | ❌ | ✅ | ❌ | ❌ |
| **POS** | ✅ | ✅ | ✅ | ❌ |

### Access Characteristics
- ✅ **Task execution**: Can perform assigned tasks (create, update)
- ✅ **Data viewing**: Can read relevant information
- ❌ **Data deletion**: Typically cannot delete records
- ❌ **System access**: No access to system administration
- ❌ **User management**: No access to user management

---

## Available Modules

The system includes the following modules:

### Core Modules
1. **Users** - User management
2. **Companies** - Company/organization management
3. **Settings** - System settings
4. **Reports** - Reporting and analytics
5. **Modules** - Module management (Super Admin only)

### Business Modules
6. **Accounting** - Financial management, invoicing, payments
   - Chart of Accounts
   - Cash Management
   - Receivables
   - Payables
   - General Ledger
   - Payroll
7. **Bookings** - Resource booking and scheduling
8. **Properties** - Property and lease management
9. **Utilities** - Utility bill management
10. **Inventory** - Inventory and stock management
11. **Tax** - Tax compliance and reporting
12. **POS** - Point of Sale system

---

## Permission Actions

Each module supports four standard actions:

1. **Create** (`create`) - Create new records
2. **Read** (`read`) - View/list records
3. **Update** (`update`) - Edit existing records
4. **Delete** (`delete`) - Remove records

---

## How Permissions Work

### Permission Checking

```php
// In views
<?php if (has_permission('accounting', 'create')): ?>
    <a href="<?= base_url('accounts/create') ?>">Create Account</a>
<?php endif; ?>

// In controllers
$this->requirePermission('accounting', 'read');
```

### Helper Functions

- `has_permission($module, $permission)` - Check if user has permission
- `canCreate($module)` - Check create permission
- `canRead($module)` - Check read permission
- `canUpdate($module)` - Check update permission
- `canDelete($module)` - Check delete permission
- `isSuperAdmin()` - Check if user is super admin
- `isAdmin()` - Check if user is admin or super admin
- `isManager()` - Check if user is manager, admin, or super admin

---

## Assigning Permissions

### Via User Management Interface

1. Navigate to **Users** → **Edit User**
2. Click **Manage Permissions** or go to **Users** → **Permissions** → Select User
3. Check/uncheck permissions in the permission matrix
4. Click **Save Permissions**

### Permission Matrix Structure

The permission matrix displays:
- **Rows**: Modules (e.g., Accounting, Bookings, Properties)
- **Columns**: Actions (Create, Read, Update, Delete)
- **Checkboxes**: Individual permissions

### During User Creation

When creating a new user:
1. Fill in user details
2. Select role (Admin, Manager, Staff, etc.)
3. Assign permissions using checkboxes
4. Save user

---

## Module Access Control

### Module Activation

- Modules can be activated/deactivated by **Super Admin** only
- Inactive modules are hidden from navigation
- Users are redirected if trying to access inactive modules
- Permission checks still apply even if module is active

### Access Flow

1. **Module Active Check**: Is the module enabled?
2. **Permission Check**: Does the user have required permission?
3. **Access Granted/Denied**: Based on both checks

---

## Best Practices

### For Super Admins
- Use super admin account sparingly
- Create admin accounts for daily administrative tasks
- Assign specific permissions to admin accounts

### For Admins
- Assign comprehensive permissions for administrative tasks
- Restrict access to module management
- Grant user management permissions if needed

### For Managers
- Focus on operational permissions
- Grant reporting access for decision-making
- Limit system administration access
- Consider department-based restrictions if using advanced permissions

### For Staff
- Follow principle of least privilege
- Grant only necessary permissions for job functions
- Typically no delete permissions
- No system administration access

---

## Advanced Permissions (Future)

The system includes infrastructure for:
- **Department-based permissions**: Restrict access by department
- **Location-based permissions**: Restrict access by location
- **Field-level permissions**: Control access to specific fields
- **Record-level permissions**: Control access to specific records

These features are available in the database schema but may require additional implementation.

---

## Security Notes

1. **Super Admin Bypass**: Super admin always has access - use carefully
2. **No Default Permissions**: Roles don't automatically grant permissions
3. **Explicit Assignment**: All permissions must be explicitly assigned
4. **Permission Inheritance**: No automatic permission inheritance between roles
5. **Module Activation**: Inactive modules are inaccessible regardless of permissions

---

## Troubleshooting

### User Cannot Access Module

1. Check if module is active (Super Admin → Modules)
2. Check if user has required permissions (Users → Edit → Permissions)
3. Verify permission is for correct module and action
4. Check user role (Super Admin bypasses all checks)

### Permission Not Working

1. Verify permission exists in database (`permissions` table)
2. Check user has permission assigned (`user_permissions` table)
3. Verify correct module name and action in code
4. Check for typos in permission checks

---

## Summary Table

| Role | Default Permissions | Module Management | User Management | System Settings |
|------|---------------------|-------------------|-----------------|-----------------|
| **Super Admin** | ✅ All (automatic) | ✅ Full Access | ✅ Full Access | ✅ Full Access |
| **Admin** | ❌ None (must assign) | ❌ No Access | ✅ If granted | ✅ If granted |
| **Manager** | ❌ None (must assign) | ❌ No Access | ❌ Typically No | ❌ Typically No |
| **Staff** | ❌ None (must assign) | ❌ No Access | ❌ No Access | ❌ No Access |

---

*Last Updated: Based on current system implementation*
*For questions or clarifications, refer to the codebase or contact system administrator*

