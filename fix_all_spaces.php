<?php
// Standalone Repair Tool - Fixes ALL Spaces
// Usage: http://localhost/erp/fix_all_spaces.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define BASEPATH to bypass config security
define('BASEPATH', 'system');

echo "<h1>System Repair Tool: Fix All Spaces</h1>";

// 1. Load Config
$configFile = __DIR__ . '/application/config/config.installed.php';
if (!file_exists($configFile)) {
    die("Config file not found.");
}

$config = include $configFile;
if (!is_array($config) || !isset($config['db'])) {
    if (isset($db)) {
        $dbConf = $db['default'] ?? $db;
    } else {
        die("Could not load DB config.");
    }
} else {
    $dbConf = $config['db']['default'] ?? $config['db'];
}

// 2. Connect
$mysqli = new mysqli($dbConf['hostname'], $dbConf['username'], $dbConf['password'], $dbConf['database']);
if ($mysqli->connect_error) {
    // Retry localhost 127.0.0.1
    $mysqli = new mysqli('127.0.0.1', $dbConf['username'], $dbConf['password'], $dbConf['database']);
    if ($mysqli->connect_error) {
        die("DB Connection failed: " . $mysqli->connect_error);
    }
}

$prefix = $dbConf['dbprefix'] ?? '';
echo "Connected to DB. Prefix: " . ($prefix ?: 'None') . "<br><hr>";

// 3. Ensure Config Table Exists
$createTable = "CREATE TABLE IF NOT EXISTS {$prefix}bookable_config (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `space_id` int(11) NOT NULL,
    `availability_rules` text,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `space_id` (`space_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($mysqli->query($createTable)) {
    echo "Checked `bookable_config` table... OK.<br>";
} else {
    echo "Error checking `bookable_config`: " . $mysqli->error . "<br>";
}

// 4. Fetch All Spaces
$spacesResult = $mysqli->query("SELECT * FROM {$prefix}spaces");
if (!$spacesResult) {
    die("Error fetching spaces: " . $mysqli->error);
}

$count = 0;
$fixed = 0;

echo "<h3>Scanning Spaces...</h3>";

while ($space = $spacesResult->fetch_assoc()) {
    $count++;
    $spaceId = $space['id'];
    $spaceName = $space['space_name'];
    $facilityId = $space['facility_id'];
    
    echo "Processing Space #$spaceId: <strong>$spaceName</strong>... ";
    
    $needsFix = false;
    
    // Check Facility Link
    if (empty($facilityId)) {
        echo "<span style='color:red'>[Missing Facility]</span> ";
        
        // Create Facility
        $facName = $spaceName; // Use space name
        $stmt = $mysqli->prepare("INSERT INTO {$prefix}facilities (facility_name, status, created_at, updated_at) VALUES (?, 'active', NOW(), NOW())");
        $stmt->bind_param("s", $facName);
        if ($stmt->execute()) {
            $newFacId = $mysqli->insert_id;
            
            // Link back
            $linkStmt = $mysqli->prepare("UPDATE {$prefix}spaces SET facility_id = ? WHERE id = ?");
            $linkStmt->bind_param("ii", $newFacId, $spaceId);
            $linkStmt->execute();
            
            echo "-> Fixed! (Linked new Facility ID $newFacId). ";
            $needsFix = true;
        } else {
            echo "-> FAILED to create facility. ";
        }
    } else {
        echo "<span style='color:green'>[Facility OK]</span> ";
    }
    
    // Check Config
    $confRes = $mysqli->query("SELECT id FROM {$prefix}bookable_config WHERE space_id = $spaceId");
    if ($confRes->num_rows == 0) {
        echo "<span style='color:red'>[Missing Config]</span> ";
        
        // Create Default Config
        $defaultRules = json_encode([
            'operating_hours' => ['start' => '08:00', 'end' => '22:00'],
            'days_available' => [0,1,2,3,4,5,6]
        ]);
        
        $confStmt = $mysqli->prepare("INSERT INTO {$prefix}bookable_config (space_id, availability_rules, created_at) VALUES (?, ?, NOW())");
        $confStmt->bind_param("is", $spaceId, $defaultRules);
        if ($confStmt->execute()) {
             echo "-> Fixed! (Created default config). ";
             $needsFix = true;
        }
    } else {
         echo "<span style='color:green'>[Config OK]</span> ";
    }
    
    echo "<br>";
    if ($needsFix) $fixed++;
}

echo "<hr><h3>Summary</h3>";
echo "Total Spaces Scanned: $count<br>";
echo "Repaired Spaces: $fixed<br>";
echo "<strong style='color:blue'>System Repair Complete.</strong>";
?>
