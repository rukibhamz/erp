<?php
define('BASEPATH', __DIR__ . '/application/');
define('APPPATH', __DIR__ . '/application/');
define('ENVIRONMENT', 'development');

// Mock CodeIgniter Controller and basic classes
class CI_Controller {
    public $db;
    public $session;
    public $lang;
    
    public function __construct() {
        $this->db = new stdClass(); // Mock DB object just to see if it breaks type hints
        $this->session = new stdClass();
        $this->lang = new stdClass();
    }
    
    public static function get_instance() {
        return new self();
    }
}

// Mock functions
function get_instance() { return CI_Controller::get_instance(); }
function log_message($level, $message) { error_log("[$level] $message"); }
function check_csrf() { return true; }
function sanitize_input($str) { return $str; }
function site_url($uri) { return "http://localhost/$uri"; }
function base_url($uri) { return "http://localhost/$uri"; }
function redirect($uri) { echo "Redirecting to $uri\n"; }

// Load core classes
require_once 'application/core/Base_Controller.php'; 
require_once 'application/core/Base_Model.php';
require_once 'application/core/Database.php';
require_once 'application/models/Bookable_config_model.php';
require_once 'application/models/Space_model.php';

// Mock Space Model to avoid complex dependencies
class Mock_Space_model extends Space_model {
    public function __construct() {
        parent::__construct();
    }
    public function getById($id) { return ['id' => $id, 'space_name' => 'Test Space', 'is_bookable' => 1, 'property_id' => 1]; }
    public function getWithProperty($id) { return $this->getById($id); }
    public function update($id, $data) { echo "Space Updated.\n"; return true; }
    public function syncToBookingModule($id) { echo "Synced to Booking Module.\n"; return true; }
    public function getBookableConfig($id) { return false; } 
}

// Load Spaces Controller
require_once 'application/controllers/Spaces.php';

// Extend Spaces to override models
class TestSpaces extends Spaces {
    public function __construct() {
        // Manually setup models
        $this->spaceModel = new Mock_Space_model();
        $this->facilityModel = new stdClass(); // Mock
        $this->activityModel = new stdClass(); // Mock
        $this->activityModel->log = function() {};
        
        // Use the REAL instantiation logic we want to test
        require_once BASEPATH . 'models/Bookable_config_model.php';
        $this->bookableConfigModel = new Bookable_config_model(); // The fix
    }
    
    // Expose protected methods for testing
    public function testCreateBookableConfig($spaceId, $postData) {
        return $this->createBookableConfig($spaceId, $postData);
    }
}

try {
    echo "--- CONTROLLER INTEGRATION TEST CHECK ---\n";
    
    // Setup Global POST
    $_POST = [
        'hourly_rate' => '150.00',
        'is_bookable' => '1',
        'minimum_duration' => '2'
    ];
    
    $controller = new TestSpaces();
    echo "Controller Instantiated.\n";
    
    $result = $controller->testCreateBookableConfig(99998, $_POST);
    echo "Create Result: " . var_export($result, true) . "\n";
    
    if ($result) {
        $model = new Bookable_config_model();
        $saved = $model->getBySpace(99998);
        if ($saved) {
            echo "VERIFIED: Saved to DB correctly. Pricing: " . $saved['pricing_rules'] . "\n";
            $model->deleteBy(['space_id' => 99998]);
        } else {
            echo "FAILED: Result true but not in DB.\n";
        }
    } else {
        echo "FAILED: Create returned false.\n";
    }

} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
