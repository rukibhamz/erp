<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payroll Diagnostic Script
 * Run this to check why payroll isn't showing employees and cash accounts
 */

// Include the application bootstrap
require_once __DIR__ . '/index.php';

echo "<h1>Payroll Module Diagnostic</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;} table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}</style>";

// Load database
$db = new Database();

echo "<h2>1. Database Connection</h2>";
try {
    $pdo = $db->getConnection();
    echo "<p class='success'>✓ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Check employees table
echo "<h2>2. Employees Table</h2>";
try {
    $prefix = $db->getPrefix();
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM `{$prefix}employees`");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total employees: <strong>{$result['total']}</strong></p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM `{$prefix}employees` WHERE status = 'active'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Active employees: <strong class='" . ($result['total'] > 0 ? 'success' : 'error') . "'>{$result['total']}</strong></p>";
    
    if ($result['total'] == 0) {
        echo "<p class='warning'>⚠ No active employees found. You need to create employees first.</p>";
        echo "<p><a href='/erp/employees/create'>Create Employee</a></p>";
    } else {
        // Show sample employees
        $stmt = $pdo->query("SELECT id, employee_code, first_name, last_name, email, status FROM `{$prefix}employees` WHERE status = 'active' LIMIT 5");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Sample Active Employees:</h3>";
        echo "<table><tr><th>ID</th><th>Code</th><th>Name</th><th>Email</th><th>Status</th></tr>";
        foreach ($employees as $emp) {
            echo "<tr>";
            echo "<td>{$emp['id']}</td>";
            echo "<td>{$emp['employee_code']}</td>";
            echo "<td>{$emp['first_name']} {$emp['last_name']}</td>";
            echo "<td>{$emp['email']}</td>";
            echo "<td>{$emp['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking employees: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check cash_accounts table
echo "<h2>3. Cash Accounts Table</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM `{$prefix}cash_accounts`");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total cash accounts: <strong>{$result['total']}</strong></p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM `{$prefix}cash_accounts` WHERE status = 'active'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Active cash accounts: <strong class='" . ($result['total'] > 0 ? 'success' : 'error') . "'>{$result['total']}</strong></p>";
    
    if ($result['total'] == 0) {
        echo "<p class='warning'>⚠ No active cash accounts found. AutoMigration should create one automatically.</p>";
        echo "<p>Try refreshing the page or <a href='/erp/cash/accounts/create'>Create Cash Account manually</a></p>";
    } else {
        // Show sample cash accounts
        $stmt = $pdo->query("SELECT ca.id, ca.account_name, ca.account_type, ca.current_balance, ca.balance, ca.currency, ca.status 
                             FROM `{$prefix}cash_accounts` ca 
                             WHERE ca.status = 'active' LIMIT 5");
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Sample Active Cash Accounts:</h3>";
        echo "<table><tr><th>ID</th><th>Name</th><th>Type</th><th>Current Balance</th><th>Balance</th><th>Currency</th><th>Status</th></tr>";
        foreach ($accounts as $acc) {
            echo "<tr>";
            echo "<td>{$acc['id']}</td>";
            echo "<td>{$acc['account_name']}</td>";
            echo "<td>{$acc['account_type']}</td>";
            echo "<td>" . ($acc['current_balance'] ?? 'NULL') . "</td>";
            echo "<td>" . ($acc['balance'] ?? 'NULL') . "</td>";
            echo "<td>{$acc['currency']}</td>";
            echo "<td>{$acc['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking cash accounts: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check accounts table
echo "<h2>4. Chart of Accounts</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM `{$prefix}accounts`");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total accounts: <strong>{$result['total']}</strong></p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM `{$prefix}accounts` WHERE status = 'active'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Active accounts: <strong>{$result['total']}</strong></p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking accounts: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test the model methods directly
echo "<h2>5. Model Method Tests</h2>";
try {
    require_once BASEPATH . 'models/Base_Model.php';
    require_once BASEPATH . 'models/Employee_model.php';
    require_once BASEPATH . 'models/Cash_account_model.php';
    
    $employeeModel = new Employee_model();
    $cashAccountModel = new Cash_account_model();
    
    echo "<h3>Employee_model::getActiveEmployees()</h3>";
    try {
        $employees = $employeeModel->getActiveEmployees();
        echo "<p class='success'>✓ Method executed successfully</p>";
        echo "<p>Returned " . count($employees) . " employees</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Method failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h3>Cash_account_model::getActive()</h3>";
    try {
        $cashAccounts = $cashAccountModel->getActive();
        echo "<p class='success'>✓ Method executed successfully</p>";
        echo "<p>Returned " . count($cashAccounts) . " cash accounts</p>";
        if (count($cashAccounts) > 0) {
            echo "<p>First account fields: " . implode(', ', array_keys($cashAccounts[0])) . "</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Method failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error loading models: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>6. Recommendations</h2>";
echo "<ul>";
echo "<li>If no employees: <a href='/erp/employees/create'>Create an employee</a></li>";
echo "<li>If no cash accounts: <a href='/erp/cash/accounts/create'>Create a cash account</a> or refresh the page to trigger AutoMigration</li>";
echo "<li>Check error logs at: <code>xampp/htdocs/erp/logs/error.log</code></li>";
echo "</ul>";

echo "<p><a href='/erp/payroll/process'>← Back to Process Payroll</a></p>";
