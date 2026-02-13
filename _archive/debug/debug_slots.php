<?php
// Debug script for time slots
define('BASEPATH', __DIR__ . '/application/');

// Mock CI Controller
class CI_Controller {
    public static $instance;
    public $load;
    public function __construct() { self::$instance = $this; }
    public static function get_instance() { return self::$instance; }
}

// Manually read config to avoid variable scope issues
$configFile = 'application/config/config.installed.php';
if (!file_exists($configFile)) {
    die("Config file not found: $configFile");
}

$config = require $configFile;
if (!is_array($config) || !isset($config['db'])) {
    die("Config is invalid or missing 'db' key. Content type: " . gettype($config));
}

$dbConfig = $config['db'];

// Setup DB connection
try {
    $dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("DB Connection Error: " . $e->getMessage());
}

// Mock DB Class
class MockDB {
    private $pdo;
    private $prefix;
    public function __construct($pdo, $prefix) { $this->pdo = $pdo; $this->prefix = $prefix; }
    public function getPrefix() { return $this->prefix; }
    public function fetchAll($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function fetchOne($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

// Mock Base Model
class Base_Model {
    protected $db;
    public function __construct($db) { $this->db = $db; }
    public function getById($id) { return null; } // Override in child
}

// Load Facility Model
if (!file_exists('application/models/Facility_model.php')) {
    die("Facility_model.php not found");
}

require 'application/models/Facility_model.php';

// Instantiate
$mockDb = new MockDB($pdo, $dbConfig['dbprefix']);
$facilityModel = new Facility_model($mockDb);

// Get first available facility/space
echo "<h1>Debug Time Slots</h1>";

// Try spaces first as they are usually what user is booking
$spaces = $mockDb->fetchAll("SELECT id, space_name, facility_id FROM {$dbConfig['dbprefix']}spaces WHERE is_bookable=1 LIMIT 1");

if (!empty($spaces)) {
    // If it's a space, getAvailableTimeSlots expects the ID passed (which might be treated as facility ID internally or handled)
    // In Facility_model::getAvailableTimeSlots($facilityId...), it calls getById($facilityId).
    // getById handles both facility ID and space ID (via space lookup).
    // So passing space ID should work if it's treated as "ID".
    $id = $spaces[0]['id'];
    $name = $spaces[0]['space_name'];
    echo "Testing Space: $name (ID: $id)<br>";
} else {
    $facilities = $mockDb->fetchAll("SELECT id, facility_name FROM {$dbConfig['dbprefix']}facilities LIMIT 1");
    if (empty($facilities)) {
        die("No facilities or spaces found to test.");
    }
    $id = $facilities[0]['id'];
    $name = $facilities[0]['facility_name'];
    echo "Testing Facility: $name (ID: $id)<br>";
}

$date = date('Y-m-d');
echo "Date: $date<br>";

// Call Method
try {
    $result = $facilityModel->getAvailableTimeSlots($id, $date);
    echo "<h3>Result JSON:</h3>";
    echo "<textarea style='width:100%; height:300px;'>" . json_encode($result, JSON_PRETTY_PRINT) . "</textarea>";
    
    echo "<h3>Display Check:</h3>";
    if (!empty($result['slots'])) {
        $first = $result['slots'][0];
        echo "Slot 1 Display: '" . ($first['display'] ?? 'MISSING') . "'<br>";
    } else {
        echo "No available slots returned.<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
