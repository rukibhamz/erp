# CRUD Errors Fixed - Database Column Mismatch Issues
**Date:** Current  
**Status:** ✅ **FIXED**

---

## Summary

Fixed database column mismatch errors where controllers were trying to insert/update columns that don't exist in the production database schema. These columns are added by migration files but may not have been run on all installations.

---

## Issues Fixed

### 1. Accounts Controller - `account_number` Column ✅
**Error:** `Column not found: 1054 Unknown column 'account_number' in 'INSERT INTO'`

**Root Cause:**
- The `account_number` column is added by `install/migrations_alter.php` but may not exist in production databases
- Controller was always trying to insert `account_number` without checking if column exists

**Fix Applied:**
- Added `checkColumnExists()` helper method to `Base_Controller`
- Modified `Accounts::create()` to check if `account_number` column exists before inserting
- Modified `Accounts::edit()` to check if `account_number` column exists before updating
- Added fallback logic to generate `account_code` if `account_number` column doesn't exist
- Also fixed `is_default` column handling

**Files Modified:**
- `application/controllers/Accounts.php`
- `application/core/Base_Controller.php` (added helper method)

---

### 2. Accounts Controller - `is_default` Column ✅
**Error:** Potential error if `is_default` column doesn't exist

**Fix Applied:**
- Added column existence check before inserting/updating `is_default`
- Removed `is_default` from update data if column doesn't exist

**Files Modified:**
- `application/controllers/Accounts.php`

---

### 3. Payables Controller - `bank_account_id` Column ✅
**Error:** Potential error if `bank_account_id` column doesn't exist in `payments` table

**Fix Applied:**
- Added column existence check before inserting `bank_account_id`
- Only adds `bank_account_id` to payment data if column exists

**Files Modified:**
- `application/controllers/Payables.php`

---

### 4. Recurring Controller - `journal_type` Column ✅
**Error:** Potential error if `journal_type` column doesn't exist in `journal_entries` table

**Fix Applied:**
- Added column existence check before inserting `journal_type`
- Only adds `journal_type` to journal data if column exists

**Files Modified:**
- `application/controllers/Recurring.php`

---

### 5. Payroll Controller - `journal_type` Column ✅
**Error:** Potential error if `journal_type` column doesn't exist in `journal_entries` table

**Fix Applied:**
- Added column existence check before inserting `journal_type`
- Only adds `journal_type` to journal data if column exists

**Files Modified:**
- `application/controllers/Payroll.php`

---

### 6. Templates Controller - `is_default` Column ✅
**Error:** Potential error if `is_default` column doesn't exist in `templates` table

**Fix Applied:**
- Added column existence check before inserting/updating `is_default`
- Only processes default template logic if column exists

**Files Modified:**
- `application/controllers/Templates.php`

---

## Helper Method Added

### `Base_Controller::checkColumnExists($table, $column)`

A reusable helper method that safely checks if a column exists in a table before attempting to use it.

**Features:**
- Uses parameterized queries (SQL injection safe)
- Handles errors gracefully
- Returns `false` if column doesn't exist or on error
- Logs errors for debugging

**Usage:**
```php
if ($this->checkColumnExists('accounts', 'account_number')) {
    $data['account_number'] = $value;
}
```

---

## Columns That May Not Exist

These columns are added by `install/migrations_alter.php` but may not exist in all databases:

### Accounts Table:
- `account_number` - Added by migration
- `is_default` - Added by migration

### Payments Table:
- `bank_account_id` - Added by migration

### Journal Entries Table:
- `journal_type` - Added by migration
- `recurring` - Added by migration
- `recurring_frequency` - Added by migration
- `recurring_next_date` - Added by migration
- `recurring_end_date` - Added by migration
- `reversed_entry_id` - Added by migration

### Invoices Table:
- `template_id` - Added by migration
- `recurring` - Added by migration
- `recurring_frequency` - Added by migration
- `recurring_next_date` - Added by migration
- `recurring_end_date` - Added by migration
- `invoice_prefix` - Added by migration
- `payment_link` - Added by migration
- `sent_at` - Added by migration

### Invoice Items Table:
- `product_id` - Added by migration
- `tax_id` - Added by migration
- `tax_amount` - Added by migration
- `discount_rate` - Added by migration
- `discount_amount` - Added by migration

### Templates Table:
- `is_default` - May not exist in all installations

---

## Testing Recommendations

1. **Test Account Creation:**
   - Create account with `account_number` column missing
   - Create account with `account_number` column present
   - Verify both scenarios work correctly

2. **Test Payment Creation:**
   - Create payment with `bank_account_id` column missing
   - Create payment with `bank_account_id` column present

3. **Test Journal Entry Creation:**
   - Create journal entry with `journal_type` column missing
   - Create journal entry with `journal_type` column present

4. **Test Template Creation:**
   - Create template with `is_default` column missing
   - Create template with `is_default` column present

---

## Migration Recommendation

To ensure all columns exist, run the alter migrations:

```php
// This should be run automatically, but can be run manually if needed
require_once 'install/migrations_alter.php';
runAlterMigrations($pdo, $prefix);
```

Or run the SQL directly from `install/migrations_alter.php`.

---

## Prevention Strategy

1. **Always check column existence** before inserting/updating optional columns
2. **Use the helper method** `checkColumnExists()` for consistency
3. **Document optional columns** in code comments
4. **Run migrations** during installation/upgrade

---

**Status:** ✅ All identified CRUD errors fixed  
**Next Steps:** Monitor error logs for any additional column mismatch issues

