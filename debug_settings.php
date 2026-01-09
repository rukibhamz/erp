<?php
// Try to get database details from config.installed.php
$db_host = 'localhost';
$db_name = 'erp';
$db_user = 'root';
$db_pass = '';

if (file_exists('application/config/config.installed.php')) {
    $config_content = file_get_contents('application/config/config.installed.php');
    if (preg_match("/'hostname' => '(.+?)'/", $config_content, $m)) $db_host = $m[1];
    if (preg_match("/'database' => '(.+?)'/", $config_content, $m)) $db_name = $m[1];
    if (preg_match("/'username' => '(.+?)'/", $config_content, $m)) $db_user = $m[1];
    if (preg_match("/'password' => '(.+?)'/", $config_content, $m)) $db_pass = $m[1];
}

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_error) {
    die("Connect Error ($mysqli->connect_errno) $mysqli->connect_error");
}

echo "Table structure for erp_settings:\n";
$result = $mysqli->query("DESCRIBE erp_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
}

echo "\nData in erp_settings:\n";
$result = $mysqli->query("SELECT * FROM erp_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
}
$mysqli->close();
