# Complete Installation Guide

## Quick Start for New Installations

After running the initial installer, you need to run **ONE** migration file to set up the complete system:

```bash
# SQL (Recommended)
mysql -u username -p database_name < database/migrations/000_complete_system_migration.sql

# OR PHP
php database/migrations/000_complete_system_migration.php
```

**That's it!** This single migration includes everything:
- ✅ Permission system (tables, roles, permissions)
- ✅ Business module tables (spaces, stock_levels, items, leases, work_orders, tax_deadlines, utility_bills)
- ✅ Role-based permission assignments (manager, staff, etc.)

## Installation Steps

### Step 1: Run the Installer
1. Navigate to `http://yourdomain.com/install/`
2. Follow the installation wizard
3. Complete database setup and create admin account

### Step 2: Migration Runs Automatically! ✅
**No manual steps needed!** The migration runs automatically when you first access the application after installation.

**What happens:**
1. User logs in or visits any page
2. System automatically checks for pending migrations
3. Migration runs in the background (if needed)
4. User continues normally - no interruption

**Manual Option (if needed):**
If you prefer to run manually:
```bash
mysql -u your_username -p your_database < database/migrations/000_complete_system_migration.sql
```

### Step 3: Verify Installation
```sql
-- Check permission tables
SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = DATABASE()
AND table_name IN ('erp_permissions', 'erp_roles', 'erp_role_permissions');

-- Check business module tables
SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = DATABASE()
AND table_name IN ('erp_spaces', 'erp_stock_levels', 'erp_items', 
                   'erp_leases', 'erp_work_orders', 'erp_tax_deadlines', 
                   'erp_utility_bills');
```

### Step 4: Delete Install Directory
```bash
rm -rf install/
```

## What Gets Created

### Permission System
- `erp_permissions` - All module permissions
- `erp_roles` - System roles (super_admin, admin, manager, staff, user, accountant)
- `erp_role_permissions` - Role-permission mappings

### Business Module Tables
- `erp_spaces` - Property spaces/units
- `erp_stock_levels` - Inventory stock levels
- `erp_items` - Inventory items master
- `erp_leases` - Property leases
- `erp_work_orders` - Maintenance work orders
- `erp_tax_deadlines` - Tax compliance deadlines
- `erp_utility_bills` - Utility bills

## Role Permissions

### Manager
- ✅ All business modules (Accounting, Bookings, Properties, Inventory, Utilities)
- ✅ Accounting sub-modules (accounts, cash, receivables, payables, ledger, estimates)
- ✅ POS module
- ❌ Tax module (excluded)

### Staff
- ✅ POS, Bookings, Inventory, Utilities (read, update, create)
- ✅ Dashboard & Notifications (read)

### Super Admin & Admin
- ✅ All permissions

## Troubleshooting

### Error: "Table already exists"
**Solution:** This is normal - the migration is idempotent and safe to run multiple times.

### Error: "Access denied"
**Solution:** Ensure your database user has CREATE, ALTER, and INSERT permissions.

### Dashboard shows "0" for metrics
**Solution:** This is normal for a fresh install. Data will populate as you use the system.

## Next Steps

1. Log in with your admin account
2. Create users and assign roles
3. Start using the system modules
4. Check dashboard for metrics (will populate as data is added)

---

**For detailed migration information, see:** `docs/MIGRATION_GUIDE.md`

