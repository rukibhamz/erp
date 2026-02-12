<?php
/**
 * Fix missing columns in existing installs
 * Safe to run multiple times — checks before altering
 */
define('BASEPATH', realpath(__DIR__ . '/../application') . DIRECTORY_SEPARATOR);
require_once BASEPATH . 'core/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$prefix = $db->getPrefix();

$columnExists = function($table, $column) use ($pdo, $prefix) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$prefix}{$table}` LIKE '{$column}'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
};

$fixed = 0;

// 1. Add current_balance to customers
if (!$columnExists('customers', 'current_balance')) {
    try {
        $pdo->exec("ALTER TABLE `{$prefix}customers` ADD COLUMN `current_balance` DECIMAL(15,2) DEFAULT 0.00 AFTER `credit_limit`");
        echo "  Added customers.current_balance\n";
        $fixed++;
    } catch (PDOException $e) {
        echo "! Failed to add customers.current_balance: " . $e->getMessage() . "\n";
    }
} else {
    echo "- customers.current_balance already exists\n";
}

// 2. Add customer_type_id to customers (if missing)
if (!$columnExists('customers', 'customer_type_id')) {
    try {
        $pdo->exec("ALTER TABLE `{$prefix}customers` ADD COLUMN `customer_type_id` INT(11) DEFAULT NULL AFTER `id`");
        echo "  Added customers.customer_type_id\n";
        $fixed++;
    } catch (PDOException $e) {
        echo "! Failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "- customers.customer_type_id already exists\n";
}

// 3. Fix invoice_items missing columns
$invoiceItemAlters = [
    'product_id' => "ADD COLUMN `product_id` INT(11) DEFAULT NULL AFTER `invoice_id`",
    'tax_amount' => "ADD COLUMN `tax_amount` DECIMAL(15,2) DEFAULT 0.00 AFTER `tax_rate`",
    'discount_rate' => "ADD COLUMN `discount_rate` DECIMAL(5,2) DEFAULT 0.00 AFTER `tax_amount`",
    'discount_amount' => "ADD COLUMN `discount_amount` DECIMAL(15,2) DEFAULT 0.00 AFTER `discount_rate`"
];

foreach ($invoiceItemAlters as $column => $sql) {
    if (!$columnExists('invoice_items', $column)) {
        try {
            $pdo->exec("ALTER TABLE `{$prefix}invoice_items` {$sql}");
            echo "  Added invoice_items.{$column}\n";
            $fixed++;
        } catch (PDOException $e) {
            echo "! Failed to add invoice_items.{$column}: " . $e->getMessage() . "\n";
        }
    } else {
        echo "- invoice_items.{$column} already exists\n";
    }
}

echo "\nDone. Fixed {$fixed} columns.\n";
