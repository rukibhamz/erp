# Permission System - Complete Documentation

> **This is the SINGLE SOURCE OF TRUTH for the permission system**

## Overview

The ERP system uses a **Role-Based Access Control (RBAC)** system where:
- Users are assigned to **roles** (`erp_roles`)
- Roles have **permissions** via junction table (`erp_role_permissions`)
- Permissions define **module access** (`erp_permissions`)

## Architecture

```
Users → Roles → Permissions → Modules
  ↓       ↓         ↓            ↓
erp_  erp_roles  erp_role_   erp_permissions
users            permissions
```

## Database Schema

### Tables

1. **`erp_roles`** - System roles
   - `id` - Primary key
   - `role_name` - Display name (e.g., "Manager")
   - `role_code` - Code identifier (e.g., "manager")
   - `description` - Role description
   - `is_system` - System role flag (cannot be deleted)
   - `is_active` - Active status

2. **`erp_permissions`** - Module permissions
   - `id` - Primary key
   - `module` - Module name (e.g., "accounting", "bookings")
   - `permission` - Action (e.g., "read", "write", "create", "update", "delete")
   - `description` - Permission description

3. **`erp_role_permissions`** - Role-permission mapping (Junction Table)
   - `id` - Primary key
   - `role_id` - Foreign key to `erp_roles`
   - `permission_id` - Foreign key to `erp_permissions`
   - Foreign keys with CASCADE delete

## System Roles

| Role Code | Role Name | Description | Permissions |
|-----------|-----------|-------------|-------------|
| `super_admin` | Super Admin | Full system access | All permissions (70+) |
| `admin` | Admin | Administrative access | All permissions (70+) |
| `manager` | Manager | Management role | All business modules + Accounting sub-modules + POS (Tax excluded) |
| `staff` | Staff | Basic staff role | POS, Bookings, Inventory, Utilities (read, update, create) |
| `user` | User | Basic user role | None (minimal access) |
| `accountant` | Accountant | Accounting focused | Accounting module only |

## Modules and Permissions

### Core Modules
- `accounting` - Accounting module
- `bookings` - Bookings module
- `properties` - Properties module
- `inventory` - Inventory module
- `utilities` - Utilities module
- `settings` - Settings module
- `dashboard` - Dashboard access
- `notifications` - Notifications

### Accounting Sub-modules
- `accounts` - Chart of accounts
- `cash` - Cash management
- `receivables` - Accounts receivable
- `payables` - Accounts payable
- `ledger` - General ledger
- `estimates` - Estimates/quotes

### Other Modules
- `pos` - Point of Sale
- `users` - User management
- `companies` - Company management
- `reports` - Reports
- `modules` - Module management

### Permission Actions
- `read` - View data
- `write` - Create/edit data
- `create` - Create new records
- `update` - Update existing records
- `delete` - Delete records

## Usage in Code

### Checking Permissions

```php
// In controllers
$permissionModel = $this->loadModel('User_permission_model');

if (!$permissionModel->hasPermission($userId, 'accounting', 'read')) {
    // Deny access
    redirect('dashboard');
    return;
}
```

### Permission Check Logic

The `hasPermission()` method checks:
1. User-specific permissions (`erp_user_permissions` table - if exists)
2. Role-based permissions (`erp_role_permissions` via `erp_roles`)

Returns `true` if user has permission, `false` otherwise.

## Adding New Permissions

### Step 1: Add Permission to Database

```sql
INSERT INTO erp_permissions (module, permission, description) VALUES
('new_module', 'read', 'View new module'),
('new_module', 'write', 'Create/edit new module');
```

### Step 2: Assign to Roles

```sql
-- Assign to manager role
INSERT INTO erp_role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM erp_roles r
CROSS JOIN erp_permissions p
WHERE r.role_code = 'manager'
AND p.module = 'new_module';
```

### Step 3: Check in Code

```php
if ($permissionModel->hasPermission($userId, 'new_module', 'read')) {
    // Allow access
}
```

## Adding New Roles

### Step 1: Create Role

```sql
INSERT INTO erp_roles (role_name, role_code, description, is_system, is_active) VALUES
('New Role', 'new_role', 'Description of new role', 0, 1);
```

### Step 2: Assign Permissions

```sql
-- Assign specific permissions
INSERT INTO erp_role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM erp_roles r
CROSS JOIN erp_permissions p
WHERE r.role_code = 'new_role'
AND p.module IN ('module1', 'module2')
AND p.permission IN ('read', 'write');
```

## Troubleshooting

### Permission Check Returns False

1. **Check user's role:**
   ```sql
   SELECT u.id, u.name, u.role FROM erp_users u WHERE u.id = ?;
   ```

2. **Check role permissions:**
   ```sql
   SELECT p.module, p.permission
   FROM erp_role_permissions rp
   JOIN erp_permissions p ON rp.permission_id = p.id
   JOIN erp_roles r ON rp.role_id = r.id
   WHERE r.role_code = ?;
   ```

3. **Check if permission exists:**
   ```sql
   SELECT * FROM erp_permissions WHERE module = ? AND permission = ?;
   ```

### Tables Missing

If permission tables are missing, run:
```bash
mysql -u username -p database_name < database/migrations/000_complete_system_migration.sql
```

### Permission System Broken

1. Check error logs for SQL errors
2. Verify all tables exist
3. Check foreign key constraints
4. Re-run migration if needed

## Migration

The permission system is set up via:
- **File:** `database/migrations/000_complete_system_migration.sql`
- **Run:** `mysql -u username -p database_name < database/migrations/000_complete_system_migration.sql`

Or use the migration runner:
```bash
php database/migrations/migrate.php up
```

## Security Notes

- **Default Behavior:** If permission system is incomplete (tables missing), access is **DENIED** (security-first approach)
- **Role-Based:** Permissions are assigned to roles, not individual users (easier management)
- **Cascade Delete:** Deleting a role removes all its permissions (via foreign key CASCADE)

## Related Files

- `application/models/User_permission_model.php` - Permission check logic
- `database/migrations/000_complete_system_migration.sql` - Database setup
- `application/helpers/module_helper.php` - Module access helpers

---

**Last Updated:** Current Session  
**Version:** 2.0  
**Status:** Production Ready

