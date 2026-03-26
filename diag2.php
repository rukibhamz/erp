<?php
define('BASEPATH', true);
$c = require 'application/config/config.php';
$p = $c['db']['dbprefix'];
$db = $c['db'];
$pdo = new PDO(
    "mysql:host={$db['hostname']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Check column exists
$col = $pdo->query("SHOW COLUMNS FROM `{$p}spaces` LIKE 'is_bookable'")->fetchAll(PDO::FETCH_ASSOC);
echo "IS_BOOKABLE COLUMN EXISTS: " . (empty($col) ? "NO - THIS IS THE BUG!" : "YES") . "\n";
if (!empty($col)) print_r($col);

// 2. operational_status column
$col2 = $pdo->query("SHOW COLUMNS FROM `{$p}spaces` LIKE 'operational_status'")->fetchAll(PDO::FETCH_ASSOC);
echo "\nOPERATIONAL_STATUS COLUMN EXISTS: " . (empty($col2) ? "NO" : "YES") . "\n";

// 3. Counts
$r = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_bookable=1 THEN 1 ELSE 0 END) as bookable FROM `{$p}spaces`")->fetch(PDO::FETCH_ASSOC);
echo "\nSPACES: total={$r['total']}, bookable={$r['bookable']}\n";

// 4. Properties
$r2 = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status='operational' THEN 1 ELSE 0 END) as op FROM `{$p}properties`")->fetch(PDO::FETCH_ASSOC);
echo "PROPERTIES: total={$r2['total']}, operational={$r2['op']}\n";

// 5. Show what the join query returns
$spaces = $pdo->query("SELECT s.id, s.space_name, s.is_bookable, s.operational_status, p.property_name, p.status as prop_status FROM `{$p}spaces` s JOIN `{$p}properties` p ON s.property_id = p.id LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo "\nSPACES + PROPERTIES JOIN (first 10):\n";
foreach ($spaces as $s) {
    echo "  id={$s['id']} name={$s['space_name']} is_bookable={$s['is_bookable']} op_status={$s['operational_status']} prop={$s['property_name']} prop_status={$s['prop_status']}\n";
}

// 6. The exact query used in getBookableSpaces()
$qs = $pdo->query("SELECT s.id FROM `{$p}spaces` s JOIN `{$p}properties` p ON s.property_id = p.id WHERE s.is_bookable = 1 AND s.operational_status NOT IN ('decommissioned','temporarily_closed')")->fetchAll(PDO::FETCH_ASSOC);
echo "\nBOOKABLE SPACES (exact model query): " . count($qs) . " rows\n";

// 7. Check bookable_config table
try {
    $bc = $pdo->query("SELECT COUNT(*) as cnt FROM `{$p}bookable_config`")->fetch(PDO::FETCH_ASSOC);
    echo "BOOKABLE_CONFIG rows: {$bc['cnt']}\n";
} catch(Exception $e) {
    echo "BOOKABLE_CONFIG TABLE ERROR: " . $e->getMessage() . "\n";
}

echo "\nDONE\n";
