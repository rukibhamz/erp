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

// Load config
require 'application/config/config.installed.php';
$dbConfig = $config['db'];

// Setup DB connection
$dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

// Load Facility Model dependencies manually if needed, but we'll try to include the file
// We need to hackily include the model file bypassing 'defined(BASEPATH) OR exit' check?
// No, we defined BASEPATH at top.

require 'application/models/Facility_model.php';

// Instantiate
$mockDb = new MockDB($pdo, $dbConfig['dbprefix']);
$facilityModel = new Facility_model($mockDb);

// Get first available facility ID
echo "<h1>Debug Time Slots</h1>";
$facilities = $mockDb->fetchAll("SELECT id, facility_name FROM {$dbConfig['dbprefix']}facilities LIMIT 1");
if (empty($facilities)) {
    // Try spaces
    $spaces = $mockDb->fetchAll("SELECT id, space_name, facility_id FROM {$dbConfig['dbprefix']}spaces WHERE is_bookable=1 LIMIT 1");
    if (empty($spaces)) {
        die("No facilities or spaces found.");
    }
    $id = $spaces[0]['facility_id'] ?? $spaces[0]['id'];
    $name = $spaces[0]['space_name'];
    echo "Testing Space: $name (ID: $id)<br>";
} else {
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
    
    echo "<h3>First Slot Analysis:</h3>";
    if (!empty($result['slots'])) {
        $first = $result['slots'][0];
        echo "Display key exists: " . (array_key_exists('display', $first) ? 'YES' : 'NO') . "<br>";
        echo "Display value: '" . ($first['display'] ?? 'NULL') . "'<br>";
    } else {
        echo "No slots returned.<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
