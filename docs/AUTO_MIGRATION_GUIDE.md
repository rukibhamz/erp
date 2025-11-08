# Automatic Migration System

## Overview

The ERP system now includes **automatic migration execution** that runs on every application startup. This means users don't need to manually run SQL commands - migrations happen automatically!

## How It Works

1. **On Application Startup:** When any page loads, `Base_Controller` automatically checks for pending migrations
2. **Smart Execution:** Only runs migrations that haven't been executed yet
3. **Safe & Idempotent:** Can run multiple times without issues
4. **Silent Operation:** Runs in background, doesn't interrupt user experience

## What Gets Executed

The automatic migration system runs:
- `database/migrations/000_complete_system_migration.sql` - Complete system setup
  - Permission system tables
  - Business module tables
  - Role and permission data

## User Experience

### For New Installations

1. **User runs installer** (`/install/`)
2. **User logs in** - Migration runs automatically on first page load
3. **That's it!** No manual SQL commands needed

### For Existing Installations

- Migrations run automatically on next page load
- Only pending migrations are executed
- No user action required

## Technical Details

### Execution Flow

```
User visits page
    ↓
Base_Controller::__construct()
    ↓
AutoMigration::__construct()
    ↓
Check migrations table exists
    ↓
Check if 000_complete_system_migration.sql already executed
    ↓
If not executed: Run migration
    ↓
Record migration as executed
    ↓
Continue normal page load
```

### Safety Features

1. **Idempotent:** Safe to run multiple times
2. **Error Handling:** Silently handles errors (doesn't break application)
3. **Tracking:** Records executed migrations in `erp_migrations` table
4. **Smart SQL Parsing:** Skips SELECT/SHOW statements (verification queries)

### Migration Tracking

Migrations are tracked in the `erp_migrations` table:
- `migration` - Migration file name
- `batch` - Batch number (for rollback grouping)
- `executed_at` - Timestamp of execution

## Manual Override

If you need to manually run migrations (for debugging):

```bash
# Check status
php database/migrations/migrate.php status

# Force run (if needed)
php database/migrations/migrate.php up
```

## Troubleshooting

### Migration Not Running

1. **Check error logs:**
   ```
   application/logs/error.log
   ```

2. **Check database connection:**
   - Ensure database credentials are correct
   - Check `application/config/database.php`

3. **Check file permissions:**
   - Ensure `database/migrations/000_complete_system_migration.sql` exists
   - Ensure file is readable

### Migration Already Executed

- If migration was already run manually, it won't run again
- Check `erp_migrations` table to see executed migrations
- To re-run, delete entry from `erp_migrations` table (not recommended)

### Errors During Migration

- Errors are logged but don't break the application
- Check error logs for details
- Common errors:
  - "Table already exists" - Normal, migration is idempotent
  - "Duplicate key" - Normal, data already seeded
  - Connection errors - Check database configuration

## Disabling Auto-Migration

If you need to disable automatic migrations (not recommended):

1. Comment out in `Base_Controller.php`:
   ```php
   // new AutoMigration();
   ```

2. Run migrations manually when needed

## Benefits

✅ **User-Friendly:** No technical knowledge required  
✅ **Automatic:** Runs on deployment automatically  
✅ **Safe:** Idempotent, won't break if run multiple times  
✅ **Tracked:** Records execution for audit trail  
✅ **Silent:** Doesn't interrupt user experience  

---

**Status:** ✅ Active and Production Ready  
**Last Updated:** Current Session

