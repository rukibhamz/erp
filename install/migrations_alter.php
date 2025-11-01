<?php
/**
 * ALTER TABLE migrations - Run after initial migrations
 * Handles adding columns to existing tables safely
 */

function runAlterMigrations($pdo, $prefix = 'erp_') {
    // Helper function to check if column exists
    $columnExists = function($table, $column) use ($pdo, $prefix) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$prefix}{$table}` LIKE '{$column}'");
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    };
    
    // Helper function to check if index exists
    $indexExists = function($table, $index) use ($pdo, $prefix) {
        try {
            $stmt = $pdo->query("SHOW INDEXES FROM `{$prefix}{$table}` WHERE Key_name = '{$index}'");
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    };
    
    // Enhanced Accounts table
    if (!$columnExists('accounts', 'account_number')) {
        try {
            $pdo->exec("ALTER TABLE `{$prefix}accounts` ADD COLUMN `account_number` varchar(20) DEFAULT NULL AFTER `account_code`");
            $pdo->exec("ALTER TABLE `{$prefix}accounts` ADD INDEX `account_number` (`account_number`)");
        } catch (PDOException $e) {
            error_log("Error adding account_number: " . $e->getMessage());
        }
    }
    
    if (!$columnExists('accounts', 'is_default')) {
        try {
            $pdo->exec("ALTER TABLE `{$prefix}accounts` ADD COLUMN `is_default` tinyint(1) DEFAULT 0 AFTER `status`");
        } catch (PDOException $e) {
            error_log("Error adding is_default: " . $e->getMessage());
        }
    }
    
    // Enhanced Invoices
    $invoiceAlters = [
        'template_id' => "ADD COLUMN `template_id` int(11) DEFAULT NULL AFTER `currency`",
        'recurring' => "ADD COLUMN `recurring` tinyint(1) DEFAULT 0 AFTER `status`",
        'recurring_frequency' => "ADD COLUMN `recurring_frequency` enum('daily','weekly','monthly','quarterly','annually') DEFAULT NULL AFTER `recurring`",
        'recurring_next_date' => "ADD COLUMN `recurring_next_date` date DEFAULT NULL AFTER `recurring_frequency`",
        'recurring_end_date' => "ADD COLUMN `recurring_end_date` date DEFAULT NULL AFTER `recurring_next_date`",
        'invoice_prefix' => "ADD COLUMN `invoice_prefix` varchar(20) DEFAULT 'INV' AFTER `invoice_number`",
        'payment_link' => "ADD COLUMN `payment_link` varchar(255) DEFAULT NULL AFTER `notes`",
        'sent_at' => "ADD COLUMN `sent_at` datetime DEFAULT NULL AFTER `payment_link`"
    ];
    
    foreach ($invoiceAlters as $column => $sql) {
        if (!$columnExists('invoices', $column)) {
            try {
                $pdo->exec("ALTER TABLE `{$prefix}invoices` {$sql}");
            } catch (PDOException $e) {
                error_log("Error adding invoices.{$column}: " . $e->getMessage());
            }
        }
    }
    
    if (!$indexExists('invoices', 'template_id')) {
        try {
            $pdo->exec("ALTER TABLE `{$prefix}invoices` ADD INDEX `template_id` (`template_id`)");
        } catch (PDOException $e) {
            // Index might already exist
        }
    }
    
    // Enhanced Invoice Items
    $invoiceItemAlters = [
        'product_id' => "ADD COLUMN `product_id` int(11) DEFAULT NULL AFTER `invoice_id`",
        'tax_id' => "ADD COLUMN `tax_id` int(11) DEFAULT NULL AFTER `tax_rate`",
        'tax_amount' => "ADD COLUMN `tax_amount` decimal(15,2) DEFAULT 0.00 AFTER `tax_id`",
        'discount_rate' => "ADD COLUMN `discount_rate` decimal(5,2) DEFAULT 0.00 AFTER `tax_amount`",
        'discount_amount' => "ADD COLUMN `discount_amount` decimal(15,2) DEFAULT 0.00 AFTER `discount_rate`"
    ];
    
    foreach ($invoiceItemAlters as $column => $sql) {
        if (!$columnExists('invoice_items', $column)) {
            try {
                $pdo->exec("ALTER TABLE `{$prefix}invoice_items` {$sql}");
            } catch (PDOException $e) {
                error_log("Error adding invoice_items.{$column}: " . $e->getMessage());
            }
        }
    }
    
    // Enhanced Payments
    if (!$columnExists('payments', 'bank_account_id')) {
        try {
            $pdo->exec("ALTER TABLE `{$prefix}payments` ADD COLUMN `bank_account_id` int(11) DEFAULT NULL AFTER `account_id`");
            $pdo->exec("ALTER TABLE `{$prefix}payments` ADD INDEX `bank_account_id` (`bank_account_id`)");
        } catch (PDOException $e) {
            error_log("Error adding bank_account_id: " . $e->getMessage());
        }
    }
    
    // Enhanced Journal Entries
    $journalAlters = [
        'journal_type' => "ADD COLUMN `journal_type` enum('sales','purchases','cash','bank','general','adjustment') DEFAULT 'general' AFTER `entry_number`",
        'recurring' => "ADD COLUMN `recurring` tinyint(1) DEFAULT 0 AFTER `status`",
        'recurring_frequency' => "ADD COLUMN `recurring_frequency` enum('daily','weekly','monthly','quarterly','annually') DEFAULT NULL AFTER `recurring`",
        'recurring_next_date' => "ADD COLUMN `recurring_next_date` date DEFAULT NULL AFTER `recurring_frequency`",
        'recurring_end_date' => "ADD COLUMN `recurring_end_date` date DEFAULT NULL AFTER `recurring_next_date`",
        'reversed_entry_id' => "ADD COLUMN `reversed_entry_id` int(11) DEFAULT NULL AFTER `recurring_end_date`"
    ];
    
    foreach ($journalAlters as $column => $sql) {
        if (!$columnExists('journal_entries', $column)) {
            try {
                $pdo->exec("ALTER TABLE `{$prefix}journal_entries` {$sql}");
            } catch (PDOException $e) {
                error_log("Error adding journal_entries.{$column}: " . $e->getMessage());
            }
        }
    }
    
    // Enhanced Bank Reconciliations
    $reconciliationAlters = [
        'ending_balance' => "ADD COLUMN `ending_balance` decimal(15,2) DEFAULT NULL AFTER `closing_balance`",
        'cleared_transactions_count' => "ADD COLUMN `cleared_transactions_count` int(11) DEFAULT 0 AFTER `adjustments`",
        'outstanding_deposits' => "ADD COLUMN `outstanding_deposits` decimal(15,2) DEFAULT 0.00 AFTER `cleared_transactions_count`",
        'outstanding_checks' => "ADD COLUMN `outstanding_checks` decimal(15,2) DEFAULT 0.00 AFTER `outstanding_deposits`"
    ];
    
    foreach ($reconciliationAlters as $column => $sql) {
        if (!$columnExists('bank_reconciliations', $column)) {
            try {
                $pdo->exec("ALTER TABLE `{$prefix}bank_reconciliations` {$sql}");
            } catch (PDOException $e) {
                error_log("Error adding bank_reconciliations.{$column}: " . $e->getMessage());
            }
        }
    }
    
    // Enhanced Employees
    $employeeAlters = [
        'bank_name' => "ADD COLUMN `bank_name` varchar(255) DEFAULT NULL AFTER `basic_salary`",
        'bank_account_number' => "ADD COLUMN `bank_account_number` varchar(100) DEFAULT NULL AFTER `bank_name`",
        'bank_routing_number' => "ADD COLUMN `bank_routing_number` varchar(50) DEFAULT NULL AFTER `bank_account_number`",
        'tax_number' => "ADD COLUMN `tax_number` varchar(100) DEFAULT NULL AFTER `bank_routing_number`",
        'salary_structure_json' => "ADD COLUMN `salary_structure_json` text DEFAULT NULL AFTER `tax_number`",
        'allowances_json' => "ADD COLUMN `allowances_json` text DEFAULT NULL AFTER `salary_structure_json`",
        'deductions_json' => "ADD COLUMN `deductions_json` text DEFAULT NULL AFTER `allowances_json`"
    ];
    
    foreach ($employeeAlters as $column => $sql) {
        if (!$columnExists('employees', $column)) {
            try {
                $pdo->exec("ALTER TABLE `{$prefix}employees` {$sql}");
            } catch (PDOException $e) {
                error_log("Error adding employees.{$column}: " . $e->getMessage());
            }
        }
    }
    
    // Enhanced Payroll
    $payrollAlters = [
        'overtime_hours' => "ADD COLUMN `overtime_hours` decimal(5,2) DEFAULT 0.00 AFTER `basic_salary`",
        'overtime_amount' => "ADD COLUMN `overtime_amount` decimal(15,2) DEFAULT 0.00 AFTER `overtime_hours`",
        'bonus' => "ADD COLUMN `bonus` decimal(15,2) DEFAULT 0.00 AFTER `overtime_amount`",
        'leave_deduction' => "ADD COLUMN `leave_deduction` decimal(15,2) DEFAULT 0.00 AFTER `deductions`",
        'gross_salary' => "ADD COLUMN `gross_salary` decimal(15,2) DEFAULT 0.00 AFTER `tax_amount`",
        'employer_contribution' => "ADD COLUMN `employer_contribution` decimal(15,2) DEFAULT 0.00 AFTER `net_salary`"
    ];
    
    foreach ($payrollAlters as $column => $sql) {
        if (!$columnExists('payroll', $column)) {
            try {
                $pdo->exec("ALTER TABLE `{$prefix}payroll` {$sql}");
            } catch (PDOException $e) {
                error_log("Error adding payroll.{$column}: " . $e->getMessage());
            }
        }
    }
}

