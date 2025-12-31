# ERP System Tests

This directory contains automated test scripts to verify the functionality and integrity of the ERP system.

## Available Tests

### 1. Functional Test Runner (`run_functional_tests.php`)
**Purpose**: Tests specific business logic and module functions (e.g., Booking Wizard pricing, availability, Invoicing).
**Method**: Uses database transactions to run tests safely without modifying your real data (rollback is performed at the end).
**Usage**:
```bash
php tests/run_functional_tests.php
```

### 2. Comprehensive System Test (`comprehensive_system_test.php`)
**Purpose**: Checks system integrity, file existence, database structure, and basic counts.
**Usage**:
```bash
php tests/comprehensive_system_test.php
```

## Troubleshooting: "could not find driver"

If you see a `Database Connection Error: could not find driver` message, it means your PHP CLI environment does not have the **MySQL PDO driver** enabled.

**Fix for XAMPP:**
1. Locate your PHP configuration file (`php.ini`). It is usually in `C:\xampp\php\php.ini`.
2. Open it and search for `;extension=pdo_mysql`.
3. Remove the semicolon (`;`) to uncomment it:
   ```ini
   extension=pdo_mysql
   ```
4. Save the file.
5. Try running the test command again.
