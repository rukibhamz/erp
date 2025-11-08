# Permission System - Quick Start

> **For complete documentation, see:** `PERMISSION_SYSTEM.md` (single source of truth)

## 🚀 Quick Start

### Single Migration (All-in-One)
```bash
# SQL (Recommended)
mysql -u username -p database_name < database/migrations/001_permission_system_complete.sql

# OR PHP
php database/migrations/001_permission_system_complete.php
```

**That's it!** This single migration includes:
- ✅ All permission tables
- ✅ All roles and permissions
- ✅ Manager permissions (Accounting sub-modules, POS, no Tax)
- ✅ Staff permissions (POS, Bookings, Inventory, Utilities)

### Test All Roles
```bash
php test_permission_system.php
```

## 📁 Files

- **`database/migrations/001_permission_system_complete.sql`** - **ALL-IN-ONE SQL migration** (includes everything)
- **`database/migrations/001_permission_system_complete.php`** - **ALL-IN-ONE PHP migration** (includes everything)
- **`database/migrations/002_fix_manager_permissions.sql`** - Legacy: Manager permissions fix (now merged into 001)
- **`database/migrations/002_fix_manager_permissions.php`** - Legacy: Manager permissions fix (now merged into 001)
- **`database/migrations/003_fix_staff_permissions.sql`** - Legacy: Staff permissions fix (now merged into 001)
- **`database/migrations/003_fix_staff_permissions.php`** - Legacy: Staff permissions fix (now merged into 001)
- **`test_permission_system.php`** - Comprehensive test script
- **`PERMISSION_SYSTEM.md`** - Complete documentation (single source of truth)

> **Note:** For new installations, you only need to run `001_permission_system_complete.sql` or `.php`. The other migration files are kept for reference or if you need to apply fixes separately.

## ✅ What It Does

1. Creates missing tables (erp_permissions, erp_roles, erp_role_permissions)
2. Seeds all roles (super_admin, admin, manager, staff, user, accountant)
3. Seeds all permissions for all modules
4. Assigns permissions to each role appropriately
5. Tests all roles to verify everything works

## 🎯 Roles & Permissions

| Role | Permissions |
|------|------------|
| **super_admin** | All permissions (50+) |
| **admin** | All permissions (50+) |
| **manager** | Business modules + Accounting sub-modules + POS (70+) |
| **staff** | POS, Bookings, Inventory, Utilities (read, update, create) (20+) |
| **user** | None |
| **accountant** | Accounting only (5) |

**Manager includes:**
- ✅ All Accounting sub-modules: accounts, cash, receivables, payables, ledger, estimates
- ✅ POS module
- ❌ Tax module (removed)

**Staff includes:**
- ✅ POS module (read, update, create)
- ✅ Bookings module (read, update, create)
- ✅ Inventory module (read, update, create)
- ✅ Utilities module (read, update, create)
- ✅ Dashboard & Notifications (read)

## 📖 Full Documentation

See **`PERMISSION_SYSTEM.md`** for:
- Complete migration instructions
- Troubleshooting guide
- Role permission matrix
- Code changes
- Production deployment checklist

---

**Status:** ✅ Ready for Testing

