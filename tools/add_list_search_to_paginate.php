<?php
$varEntity = [
    'customers' => 'customer', 'customer' => 'customer', 'vendors' => 'vendor', 'vendor' => 'vendor',
    'bookings' => 'booking', 'booking' => 'booking', 'invoices' => 'invoice', 'invoice' => 'invoice',
    'bills' => 'bill', 'bill' => 'bill', 'payments' => 'payment', 'payment' => 'payment',
    'users' => 'user', 'user' => 'user', 'accounts' => 'account', 'products' => 'product', 'items' => 'item',
    'employees' => 'employee', 'tenants' => 'tenant', 'leases' => 'lease', 'suppliers' => 'supplier',
    'allMismatched' => 'booking', 'modules' => 'module', 'notifications' => 'notification',
    'entities' => 'entity', 'companies' => 'company', 'orders' => 'order', 'terminals' => 'terminal',
    'meters' => 'meter', 'tariffs' => 'generic', 'properties' => 'generic', 'locations' => 'generic',
    'currencies' => 'generic', 'spaces' => 'generic', 'promo_codes' => 'promo', 'promos' => 'promo',
    'stock_takes' => 'generic', 'stock_adjustments' => 'generic', 'goods_receipts' => 'grn',
    'purchase_orders' => 'order', 'utility_bills' => 'utility_bill', 'utility_providers' => 'generic',
    'utility_alerts' => 'notification', 'utility_payments' => 'payment', 'vendor_utility_bills' => 'vendor_bill',
    'rent_invoices' => 'invoice', 'fixed_assets' => 'generic', 'customer_types' => 'generic',
    'financial_years' => 'generic', 'wholesale_pricing' => 'generic', 'report_builder' => 'generic',
    'staff' => 'employee', 'staff_members' => 'employee', 'education_tax' => 'generic', 'vat' => 'generic',
    'taxes' => 'generic', 'wht' => 'generic', 'paye' => 'generic', 'payroll' => 'employee',
    'recurring' => 'generic', 'meter_readings' => 'meter', 'pos' => 'terminal',
];

$files = glob(__DIR__ . '/../application/controllers/*.php');
$updated = 0;
foreach ($files as $file) {
    $content = file_get_contents($file);
    $orig = $content;
    $content = preg_replace_callback(
        '/paginateList\(\s*\$(\w+)\s*(?:,\s*([^)]+))?\s*\)/',
        function ($m) use ($varEntity, $file) {
            if (strpos($m[0], 'standard_list_search_fields') !== false) {
                return $m[0];
            }
            $var = $m[1];
            $second = isset($m[2]) ? trim($m[2]) : '';
            if (basename($file) === 'Cash.php' && $var === 'all') {
                $entity = 'cash_account';
            } elseif (isset($varEntity[$var])) {
                $entity = $varEntity[$var];
            } else {
                $entity = 'generic';
            }
            $fields = "standard_list_search_fields('{$entity}')";
            if ($second !== '') {
                return "paginateList(\${$var}, {$second}, {$fields})";
            }
            return "paginateList(\${$var}, null, {$fields})";
        },
        $content
    );
    if ($content !== $orig) {
        file_put_contents($file, $content);
        $updated++;
        echo basename($file) . PHP_EOL;
    }
}
echo "Updated {$updated} controller files\n";
