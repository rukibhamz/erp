<?php
define('BASEPATH', __DIR__ . '/');
require_once __DIR__ . '/core/Database.php';

try {
    $db = Database::getInstance();
    $db->query("SELECT 1 FROM erp_vw_inventory_valuation LIMIT 1");
    echo "EXISTS";
} catch (Exception $e) {
    echo "NOT_EXISTS: " . $e->getMessage();
}
