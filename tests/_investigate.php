<?php
define('BASEPATH', __DIR__ . '/../application/');
require_once BASEPATH . 'core/Database.php';
$db = Database::getInstance();
$p = $db->getPrefix();

$out = [];

// 1. Booking tables
$tables = $db->fetchAll("SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name LIKE '%book%'");
$out['booking_tables'] = array_column($tables, 'table_name');

// 2. Customers columns
$cols = $db->fetchAll("SELECT column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='{$p}customers' ORDER BY ordinal_position");
$out['customer_columns'] = array_column($cols, 'column_name');

// 3. Journal entries columns
$cols = $db->fetchAll("SELECT column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='{$p}journal_entries' ORDER BY ordinal_position");
$out['journal_entries_columns'] = array_column($cols, 'column_name');

// 4. Invoice items columns
$cols = $db->fetchAll("SELECT column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='{$p}invoice_items' ORDER BY ordinal_position");
$out['invoice_items_columns'] = array_column($cols, 'column_name');

// 5. VAT/tax accounts
$accts = $db->fetchAll("SELECT account_code, account_name, account_type FROM `{$p}accounts` WHERE account_name LIKE '%vat%' OR account_name LIKE '%tax%' OR account_code LIKE '23%'");
$out['vat_tax_accounts'] = $accts;

// 6. Booking model table
require_once BASEPATH . 'core/Base_Model.php';
require_once BASEPATH . 'models/Booking_model.php';
$bm = new Booking_model();
$ref = new ReflectionClass($bm);
$tp = $ref->getProperty('table');
$tp->setAccessible(true);
$out['booking_model_table'] = $tp->getValue($bm);

// 7. Invoice model addItem source
require_once BASEPATH . 'models/Invoice_model.php';
$im = new Invoice_model();
$ref2 = new ReflectionClass($im);
$methods = [];
foreach (['addItem','getItems'] as $method) {
    if ($ref2->hasMethod($method)) {
        $m = $ref2->getMethod($method);
        $lines = file(BASEPATH . 'models/Invoice_model.php');
        $src = '';
        for ($i = $m->getStartLine()-1; $i < $m->getEndLine(); $i++) {
            $src .= $lines[$i];
        }
        $methods[$method] = trim($src);
    } else {
        $methods[$method] = 'NOT_FOUND';
    }
}
$out['invoice_methods'] = $methods;

// 8. Missing views
$viewDir = BASEPATH . 'views/';
$views = ['booking_wizard/step1.php','booking_wizard/step2.php','booking_wizard/step3.php',
    'booking_wizard/step4.php','booking_wizard/step5.php','booking_wizard/confirmation.php',
    'receivables/customers.php','receivables/create_customer.php','receivables/invoices.php',
    'receivables/create_invoice.php','receivables/view_invoice.php',
    'accounting/dashboard.php','payment/confirmation.php','dashboard/index.php'];
$missing = [];
foreach ($views as $v) {
    if (!file_exists($viewDir . $v)) $missing[] = $v;
}
$out['missing_views'] = $missing;

file_put_contents(__DIR__ . '/_investigate.json', json_encode($out, JSON_PRETTY_PRINT));
echo "Done - see tests/_investigate.json\n";
