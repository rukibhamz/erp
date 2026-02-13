<?php
// Debug script to check Booking List Query logic
define('BASEPATH', __DIR__ . '/application/');
$config = require 'application/config/config.installed.php';
$dbConfig = $config['db'];

try {
    $dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Booking List Debug</h1>";
    
    // 1. Raw Count
    $stmt = $pdo->query("SELECT COUNT(*) FROM erp_space_bookings");
    $count = $stmt->fetchColumn();
    echo "<h3>Total Rows in erp_space_bookings: $count</h3>";
    
    if ($count == 0) {
        die("Table is empty. Verify your INSERT logic in Booking_wizard.");
    }

    // 2. Simulate getAllWithDetails Query
    echo "<h3>Testing Query (JOINs):</h3>";
    $sql = "SELECT sb.id, sb.booking_number, sb.status,
                   t.id as tenant_id,
                   s.space_name,
                   p.property_name as location_name
            FROM erp_space_bookings sb
            LEFT JOIN erp_tenants t ON sb.tenant_id = t.id
            LEFT JOIN erp_spaces s ON sb.space_id = s.id
            LEFT JOIN erp_properties p ON s.property_id = p.id
            ORDER BY sb.booking_date DESC, sb.start_time DESC";
            
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Results: " . count($results) . " rows</h4>";
    echo "<table border='1'><tr><th>ID</th><th>Booking #</th><th>Status</th><th>Tenant</th><th>Space</th><th>Location</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        foreach ($row as $k => $v) {
            echo "<td>" . ($v ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Check for Controller logic traps
    echo "<h3>Controller Logic Simulation</h3>";
    $statusFilter = $_GET['status'] ?? 'all'; // Simulate default
    echo "Filter Status: $statusFilter <br>";
    
    $filtered = array_filter($results, function($b) use ($statusFilter) {
        if ($statusFilter === 'all') return true;
        return ($b['status'] ?? 'pending') === $statusFilter;
    });
    
    echo "Filtered Count: " . count($filtered);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
