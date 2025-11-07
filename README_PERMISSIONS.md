# Permission System - Quick Start

> **For complete documentation, see:** `PERMISSION_SYSTEM_COMPLETE_GUIDE.md`

## 🚀 Quick Start

### 1. Run Migration
```bash
# SQL (Recommended)
mysql -u username -p database_name < database/migrations/001_permission_system_complete.sql

# OR PHP
php database/migrations/001_permission_system_complete.php
```

### 2. Test All Roles
```bash
php test_permission_system.php
```

## 📁 Files

- **`database/migrations/001_permission_system_complete.sql`** - Complete SQL migration
- **`database/migrations/001_permission_system_complete.php`** - Complete PHP migration  
- **`test_permission_system.php`** - Comprehensive test script
- **`PERMISSION_SYSTEM_COMPLETE_GUIDE.md`** - Complete documentation

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
| **manager** | Business modules (40+) |
| **staff** | Read-only (4) |
| **user** | None |
| **accountant** | Accounting only (5) |

## 📖 Full Documentation

See **`PERMISSION_SYSTEM_COMPLETE_GUIDE.md`** for:
- Complete migration instructions
- Troubleshooting guide
- Role permission matrix
- Code changes
- Production deployment checklist

---

**Status:** ✅ Ready for Testing

