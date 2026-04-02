# NewERP System Documentation & Developer Guide

This document (`skills.md`) serves as a comprehensive guide to understanding, debugging, and extending the NewERP system. It contains architectural context, known module behaviors, and gotchas discovered through ongoing development. Future developers or AI agents should read this file before modifying the codebase and **must update it** after making structural changes or non-obvious bug fixes.

---

## 🏗 System Architecture

NewERP is built on a **CodeIgniter-style MVC PHP framework**.

- **Controllers** (`application/controllers/`): 
  - Most controllers extend `Base_Controller`, which handles session validation, role-based access control (RBAC), and global data loading.
  - Core API/AJAX endpoints (like checkout or saving steps) usually reside here. They expect data in `$_POST` (using `application/x-www-form-urlencoded`), NOT raw JSON payloads.
  
- **Models** (`application/models/`):
  - Extend `Base_Model`.
  - Database interactions use a custom query builder or wrapper (e.g., `$this->db->fetchAll()`, `$this->db->getPrefix()`).
  - Table prefix is commonly `erp_` (retrieved via `$this->db->getPrefix()`).

- **Views** (`application/views/`):
  - Pure PHP templates mixed with HTML/JS.
  - Heavily relies on vanilla JS or jQuery for DOM manipulation and fetch/AJAX calls.
  
---

## 🧩 Core Modules & Gotchas

### 1. Booking System (`Booking_wizard.php`, `Facility_model.php`)
The booking system is a multi-step wizard spanning location selection, datetime selection, add-ons, customer info, and payment finalization.

- **AJAX State Saving (`saveStep`)**: 
  - The wizard saves state to `$_SESSION['booking_data']` asynchronously via `saveStep()`.
  - **GOTCHA - Step 2 Availability Check**: Multiday bookings (`booking_type === 'multi_day'`) previously caused server timeouts (NetworkError) on shared hosting because `Facility_model::checkAvailability()` runs a heavy loop (`getRecurringBookingsForDate()`) for every single day. **Fix**: The availability check is intentionally skipped in Step 2 for `multi_day` bookings (to fail open) and is vigorously validated during finalization (Step 5).
- **Pricing Calculation (`Facility_model::calculatePrice`)**:
  - For `multi_day`, the calculation formula is: `hourly_rate` × `actual_hours_per_day` × `number_of_days`. It previously used `daily_rate` erroneously.
- **Customer Deduplication**:
  - `Booking_wizard::finalize()` ties accounts strictly to the **email address**. If a user enters a new name but an existing email, the system enforces the existing name/phone to prevent DB duplication.

### 2. Inventory & Stock Management
The inventory module (`Stock_movements.php`, `Inventory.php`) tracks items across locations.

- **Tables**: `erp_items`, `erp_locations`, `erp_stock_levels`, `erp_stock_transactions`.
- **Location Confusion**:
  - The older Property module uses `erp_properties` (with `status = 'operational'`). 
  - The Inventory module uses `erp_locations` (with `is_active = 1`).
  - **GOTCHA**: When loading locations for inventory forms, ensure you are querying the correct table to avoid rendering empty dropdowns. `Location_model::getActive()` was previously mapping backward-compatible fields.
- **Stock Receiving (`Stock_movements::receive`)**:
  - Transaction creation auto-generates a `transaction_number` inside `Stock_transaction_model::create()`. Do not expect `$transactionData['transaction_number']` to exist in the controller immediately after inserting.
  
### 3. Financials, Taxes & P&L
- **P&L Report** (`profit_loss.php`):
  - Loops over revenue and expenses.
  - **GOTCHA**: Accounting rows may lack an `account_code` or `account_name` if they are manually injected or corrupted. Always use null-coalescing operators (`$item['account_code'] ?? ''`) in the views to prevent "Undefined array key" PHP warnings.
- **Taxes**:
  - Separate configurations exist for VAT and newly introduced taxes (like Education Tax). Ensure values are dynamically fetched from the tax configuration models (e.g., `erp_tax_types`) rather than hardcoded.

### 4. Authentication & SSO
- Employees created via SSO (Single Sign-On) need matching records injected into the employee tracking tables so they appear in standard HR/Admin employee lists.

---

## 🐛 Troubleshooting Guide

### 1. "Network Error" on Frontend `fetch()`
- **Cause**: This is almost always a PHP Fatal Error or Web Server Timeout crashing the backend script before it can return `{ "success": false }`. 
- **Debug Approach**:
  1. Wrap the target controller method in a `try/catch (\Throwable $t)` block.
  2. Implement local file logging: `@file_put_contents(ROOTPATH . 'logs/debug.log', $message, FILE_APPEND);` with `microtime(true)` timings.

### 2. Form Submission Fails Silently
- **Cause**: Usually an issue with `$_POST` not populating.
- **Debug Approach**: Ensure the frontend payload is serialized as `application/x-www-form-urlencoded`, not `application/json`. CodeIgniter `$this->input->post()` or raw `$_POST` will be empty if sent as raw JSON without explicitly parsing `php://input`.

---

## 📝 Rules for Updating this File

1. **New Modules**: If you add a new core table or controller logic, add a section here describing how it interacts with the rest of the system.
2. **Bug Fixes**: If you resolve a bug that required deep tracing (e.g., the multiday timeout), add it to the **Gotchas** section so the next developer doesn't revert your fix accidentally.
3. **Database Changes**: Note any major migrations or structural shifts (like separating `locations` from `properties`).

### 5. Mobile Responsiveness & Wizard Navigation
- **Header Layout**: The `header_public.php` uses `flex-column flex-sm-row` to stack the brand and action buttons on mobile, ensuring they don't overlap and maintaining visibility of key navigation items.
- **Wizard Progress Steps**: A custom `nav-wizard` pattern is implemented across all 5 steps:
  - Uses `flex-wrap: nowrap` and `overflow-x: auto` for horizontal scrolling on small screens.
  - Hides text labels (`.step-text`) via media queries on mobile, showing only step numbers in compact circles.
  - Uses compact padding and high-contrast active states for better accessibility on mobile viewports.
- **Sticky Summary Cards**: On mobile (below 992px), sticky summary cards (e.g., in Step 3 and Step 5) and order summaries are converted to static positioning (`position: static`) to ensure they flow naturally with the content and don't overlap other interactive elements.

*Last Updated: 2026-03-08*
