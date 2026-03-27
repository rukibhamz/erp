<?php
define('BASEPATH', true);
require 'application/config/config.php';
// Need a minimal CodeIgniter mock to test the model
require 'application/core/Base_Model.php';
require 'application/core/Database.php';

// Mock DB 
class MockDB {
    private $pdo;
    private $prefix;
    public function __construct($c) {
        $this->prefix = $c['db']['dbprefix'];
        $this->pdo = new PDO("mysql:host={$c['db']['hostname']};dbname={$c['db']['database']}", $c['db']['username'], $c['db']['password']);
    }
    public function getPrefix() { return $this->prefix; }
    public function fetchAll($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function fetchOne($sql, $params = []) {
        $res = $this->fetchAll($sql, $params);
        return $res ? $res[0] : null;
    }
}

class MockCI {
    public $db;
    public function __construct() {
        $c = require 'application/config/config.php';
        $this->db = new MockDB($c);
    }
}

function get_instance() {
    static $ci;
    if (!$ci) $ci = new MockCI();
    return $ci;
}

require 'application/models/Space_model.php';

$model = new Space_model();
echo "Testing getBookableSpaces(1)...\n";
$spaces = $model->getBookableSpaces(1);
echo "Result count: " . count($spaces) . "\n";
print_r($spaces);

if (!empty($spaces)) {
    echo "\nTesting getBookingTypes(".$spaces[0]['id'].")...\n";
    $types = $model->getBookingTypes($spaces[0]['id']);
    print_r($types);
}
