<?php
/**
 * Space Sync Diagnostic Tool
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Space Sync Diagnostic</h1>";

$config_file = dirname(__DIR__) . '/application/config/database.php';
if (!file_exists($config_file)) {
    die("Database config not found at $config_file");
}

$db = [];
$active_group = 'default';
$query_builder = TRUE;
require($config_file);

$c = $db['default'];
$dsn = "mysql:host={$c['hostname']};dbname={$c['database']};charset={$c['char_set']}";

try {
    $pdo = new PDO($dsn, $c['username'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $prefix = $c['dbprefix'];

    $locationId = $_GET['location_id'] ?? null;

    echo "<h3>1. Tables Check</h3>";
    $tables = ['properties', 'spaces', 'bookable_config'];
    foreach ($tables as $t) {
        $check = $pdo->query("SHOW TABLES LIKE '{$prefix}$t'");
        echo "Table <b>{$prefix}$t</b>: " . ($check->rowCount() > 0 ? "EXISTS" : "MISSING") . "<br>";
    }

    echo "<h3>2. Properties (Locations)</h3>";
    $props = $pdo->query("SELECT id, property_name, property_code FROM `{$prefix}properties` ORDER BY property_name")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Action</th></tr>";
    foreach ($props as $p) {
        $link = "?location_id=" . $p['id'];
        echo "<tr><td>{$p['id']}</td><td>{$p['property_code']}</td><td>{$p['property_name']}</td><td><a href='$link'>Check Spaces</a></td></tr>";
    }
    echo "</table>";

    if ($locationId) {
        echo "<h3>3. Spaces for Location ID: $locationId</h3>";
        $stmt = $pdo->prepare("SELECT id, space_name, space_number, is_bookable, operational_status FROM `{$prefix}spaces` WHERE property_id = ?");
        $stmt->execute([$locationId]);
        $spaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($spaces)) {
            echo "<p style='color:orange;'>No spaces found for this location.</p>";
        } else {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Number</th><th>Is Bookable?</th><th>Status</th><th>Note</th></tr>";
            foreach ($spaces as $s) {
                $note = "";
                if ($s['is_bookable'] != 1) $note .= "Not marked as Bookable. ";
                if ($s['operational_status'] != 'active') $note .= "Status not 'active'. ";
                
                $color = ($s['is_bookable'] == 1 && $s['operational_status'] == 'active') ? "green" : "red";
                
                echo "<tr style='color: $color;'>";
                echo "<td>{$s['id']}</td>";
                echo "<td>{$s['space_name']}</td>";
                echo "<td>{$s['space_number']}</td>";
                echo "<td>" . ($s['is_bookable'] ? 'YES (1)' : 'NO (0)') . "</td>";
                echo "<td>{$s['operational_status']}</td>";
                echo "<td>$note</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p>Only spaces with <b>YES (1)</b> and <b>Status: active</b> will appear in the Booking Module.</p>";
        }
    } else {
        echo "<p>Click 'Check Spaces' to see details for a specific location.</p>";
    }

    echo "<hr><p>Diagnostic tool version 1.0</p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
