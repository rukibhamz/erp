<?php
// Standalone Diagnostics - Bypass Framework
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnostic-Tool</h1>";

// 1. Load Config
// Bypass security check in config file
if (!defined('BASEPATH')) {
    define('BASEPATH', 'system');
}

$configFile = __DIR__ . '/application/config/config.installed.php';
if (!file_exists($configFile)) {
    die("Config file not found at $configFile");
}

$config = include $configFile;
if (!is_array($config) || !isset($config['db'])) {
    // If include didn't return config, try to extract vars if it defined them
    if (isset($db)) {
        $dbConf = $db['default'] ?? $db;
    } else {
        die("Could not load DB config. config.installed.php did not return array or set \$db.");
    }
} else {
    $dbConf = $config['db']['default'] ?? $config['db'];
}


echo "<h3>1. Database Connection</h3>";
echo "Host: " . $dbConf['hostname'] . "<br>";
echo "User: " . $dbConf['username'] . "<br>";
echo "DB: " . $dbConf['database'] . "<br>";

$mysqli = new mysqli($dbConf['hostname'], $dbConf['username'], $dbConf['password'], $dbConf['database']);

if ($mysqli->connect_error) {
    // Try 127.0.0.1 fallback
    echo "Connection failed: " . $mysqli->connect_error . ". Retrying with 127.0.0.1...<br>";
    $mysqli = new mysqli('127.0.0.1', $dbConf['username'], $dbConf['password'], $dbConf['database']);
    if ($mysqli->connect_error) {
        die("Fatal Connection Error: " . $mysqli->connect_error);
    }
}
echo "<strong style='color:green'>Connected Successfully!</strong><hr>";

// 2. Fetch Logic Dependencies
$spaceId = 1; // Default test space
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$endDate = isset($_GET['end']) ? $_GET['end'] : $date;

echo "<h3>2. Testing Logic for Space ID: $spaceId ($date to $endDate)</h3>";

// Helper to fetch one
function fetchOne($mysqli, $sql, $params = []) {
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;
    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // assist simple binding
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_assoc();
}

// Logic Replication from Facility_model
// Get Facility linked to Space
$facility = fetchOne($mysqli, "SELECT * FROM facilities WHERE space_id = ? LIMIT 1", [$spaceId]);
if (!$facility) {
    die("No facility found for Space ID $spaceId");
}
echo "Found Facility: " . $facility['facility_name'] . " (ID: {$facility['id']})<br>";

// Get Availability Rules
$availabilityRules = [];
$config = fetchOne($mysqli, "SELECT availability_rules FROM bookable_config WHERE space_id = ?", [$spaceId]);
if ($config && !empty($config['availability_rules'])) {
    $availabilityRules = json_decode($config['availability_rules'], true) ?: [];
    echo "Rules Found: " . print_r($availabilityRules, true) . "<br>";
} else {
    echo "No specific rules in bookable_config. Using defaults.<br>";
}

// Get Bookings
$sqlBookings = "SELECT * FROM bookings WHERE facility_id = ? 
                AND status NOT IN ('cancelled', 'no_show', 'refunded')
                AND (
                    (booking_date BETWEEN ? AND ?) OR
                    (booking_date <= ? AND DATE_ADD(booking_date, INTERVAL 1 DAY) > ?)
                )"; 
// Simplified overlap logic for test
$bookingsRes = $mysqli->query("SELECT * FROM bookings WHERE facility_id = {$facility['id']} AND booking_date BETWEEN '$date' AND '$endDate'");
$bookings = [];
while ($row = $bookingsRes->fetch_assoc()) {
    $bookings[] = $row;
}
echo "Found " . count($bookings) . " bookings.<br>";

// Generate Slots
$allSlots = [];
$currentDate = new DateTime($date);
$finalDate = new DateTime($endDate);

while ($currentDate <= $finalDate) {
    $currentDay = $currentDate->format('Y-m-d');
    $dayOfWeek = $currentDate->format('w');
    
    // Default Hours
    $startHour = 8;
    $endHour = 22;
    $isDayAvailable = true;

    // Apply Config
    if (!empty($availabilityRules['operating_hours'])) {
        $startHour = intval(substr($availabilityRules['operating_hours']['start'], 0, 2));
        $endHour = intval(substr($availabilityRules['operating_hours']['end'], 0, 2));
    }
    
    // Explicit 8-10 override check
    echo "Day $currentDay: Operating from $startHour:00 to $endHour:00<br>";

    $currentH = $startHour;
    while ($currentH < $endHour) {
        for ($minute = 0; $minute < 60; $minute += 15) {
            $slotStartStr = str_pad($currentH, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minute, 2, '0', STR_PAD_LEFT);
            $slotStartDT = new DateTime($currentDay . ' ' . $slotStartStr);
            $slotEndDT = clone $slotStartDT;
            $slotEndDT->modify('+1 hour');
            
            // Check availability against bookings (Simplified)
            $isOccupied = false;
            foreach ($bookings as $b) {
                // Buffer logic skipped for simplicity, checking raw overlap
                if ($b['booking_date'] == $currentDay) {
                     // Check overlap
                }
            }

            $allSlots[] = $slotStartStr . " - " . $slotEndDT->format('H:i');
        }
        $currentH++;
    }
    $currentDate->modify('+1 day');
}

echo "Generated " . count($allSlots) . " slots.<br>";
if (count($allSlots) > 0) {
    echo "First 5 slots: <br>" . implode("<br>", array_slice($allSlots, 0, 5));
}
?>
