# Code Audit Fix Plan

## Overview
This document outlines the systematic plan to fix all issues identified in the code audit report, ordered by severity.

## Status Legend
- ✅ Completed
- 🔄 In Progress
- ⏳ Pending
- ❌ Blocked

---

## CRITICAL ISSUES (Priority 1)

### ✅ CRITICAL-1: Missing `erp_role_permissions` Table
**Status:** ✅ COMPLETED
**Solution:** Already fixed via merged migration `001_permission_system_complete.sql`
**Action Required:** Run migration on production

### 🔄 CRITICAL-2: Broken Permission Check Logic
**Status:** 🔄 IN PROGRESS
**File:** `application/models/User_permission_model.php`
**Issue:** Permission check has good error handling but needs verification
**Fix:** Verify error handling works correctly, update error messages to reference correct migration file

### ⏳ CRITICAL-3: Insecure Default Permissions
**Status:** ⏳ PENDING
**File:** `application/models/User_permission_model.php`
**Issue:** When role_permissions table doesn't exist, falls back to user permissions only
**Fix:** Ensure proper fallback behavior - deny by default when table missing (security-first)

---

## HIGH PRIORITY ISSUES (Priority 2)

### ⏳ HIGH-1: Missing Business Module Tables
**Status:** ⏳ PENDING
**Files:** `database/migrations/004_create_business_module_tables.sql`
**Missing Tables:**
- `erp_spaces`
- `erp_stock_levels` (or use `erp_stock` if exists)
- `erp_leases`
- `erp_work_orders`
- `erp_tax_deadlines`
- `erp_items`
- `erp_utility_bills`
- `erp_pos_transactions` (or use `erp_pos_sales` if exists)

**Fix:** Create comprehensive migration for all missing tables

### ⏳ HIGH-2: Missing Column in `erp_transactions`
**Status:** ⏳ PENDING
**File:** `application/controllers/Dashboard.php` (Line 648)
**Issue:** Query uses `t.amount` but table has `debit` and `credit` columns
**Fix:** Change query to use `t.debit` for expense transactions

### ⏳ HIGH-3: Silent Error Handling in Dashboard
**Status:** ⏳ PENDING
**File:** `application/controllers/Dashboard.php`
**Issue:** Functions return 0/[] on error, hiding failures
**Fix:** Return error objects/states that frontend can display

### ⏳ HIGH-4: Document Migration Process
**Status:** ⏳ PENDING
**Files:** `README.md`, `MIGRATION_GUIDE.md`
**Issue:** No clear instructions for running migrations
**Fix:** Create comprehensive migration guide and update README

### ⏳ HIGH-5: Fix Failing SQL Queries
**Status:** ⏳ PENDING
**File:** `application/controllers/Dashboard.php`
**Issue:** Multiple queries reference missing tables/columns
**Fix:** Fix all queries after creating missing tables

### ⏳ HIGH-6: Testing Gaps
**Status:** ⏳ PENDING
**Files:** Create test suite
**Issue:** No automated tests
**Fix:** Create basic PHPUnit test suite for critical components

---

## MEDIUM PRIORITY ISSUES (Priority 3)

### ⏳ MEDIUM-1: N+1 Query Problem
**Status:** ⏳ PENDING
**File:** `application/controllers/Dashboard.php`
**Issue:** `getRevenueTrend()` and `getBookingTrend()` execute 12 queries each
**Fix:** Refactor to single query with GROUP BY

### ⏳ MEDIUM-2: Manager Dashboard Tax Logic
**Status:** ⏳ PENDING
**File:** `application/controllers/Dashboard.php`
**Issue:** Manager dashboard calls `getTaxLiability()` despite no tax access
**Fix:** Create `getManagerKPIs()` that excludes tax data

### ⏳ MEDIUM-3: SQL Injection Risk with LIMIT
**Status:** ⏳ PENDING
**File:** `application/controllers/Dashboard.php`
**Issue:** LIMIT parameter embedded directly in query string
**Fix:** Use parameterized queries or validate strictly

### ⏳ MEDIUM-4: Code Quality - Dashboard Controller
**Status:** ⏳ PENDING
**File:** `application/controllers/Dashboard.php`
**Issue:** 600+ lines, duplicated code across dashboard methods
**Fix:** Refactor to extract common logic into helper methods

### ⏳ MEDIUM-5: Frontend Error Display
**Status:** ⏳ PENDING
**Files:** Dashboard views
**Issue:** Frontend displays 0 instead of error messages
**Fix:** Update views to handle error states from backend

---

## LOW PRIORITY ISSUES (Priority 4)

### ⏳ LOW-1: Permission Mapping Complexity
**Status:** ⏳ PENDING
**File:** `application/models/User_permission_model.php`
**Issue:** Maps create/update to write, adds complexity
**Fix:** Standardize permission names or document mapping clearly

### ⏳ LOW-2: Conflicting Documentation
**Status:** ⏳ PENDING
**Files:** `PERMISSIONS_AND_ACCESS_RIGHTS.md`, `PERMISSION_SYSTEM_COMPLETE_GUIDE.md`
**Issue:** Conflicting docs about user-based vs role-based system
**Fix:** Consolidate documentation, mark old docs as deprecated

---

## Implementation Order

1. ✅ CRITICAL-1 (Already done)
2. 🔄 CRITICAL-2 (In progress)
3. ⏳ CRITICAL-3
4. ⏳ HIGH-2 (Quick fix - column name)
5. ⏳ HIGH-1 (Create missing tables)
6. ⏳ HIGH-5 (Fix queries after tables exist)
7. ⏳ HIGH-3 (Improve error handling)
8. ⏳ HIGH-4 (Documentation)
9. ⏳ MEDIUM-1 (Performance)
10. ⏳ MEDIUM-2 (Business logic)
11. ⏳ MEDIUM-3 (Security)
12. ⏳ MEDIUM-4 (Code quality)
13. ⏳ LOW-1 (Cleanup)
14. ⏳ LOW-2 (Documentation)

---

## Notes
- All fixes will be idempotent where possible
- Migration files will be numbered sequentially
- Each fix will include verification queries
- Tests will be added for critical fixes

