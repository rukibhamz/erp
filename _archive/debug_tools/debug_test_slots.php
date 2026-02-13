<?php
// Fix for internal testing - simulate CI environment
define('BASEPATH', 'C:/xampp/htdocs/erp/system/');
define('APPPATH', 'C:/xampp/htdocs/erp/application/');
define('ENVIRONMENT', 'development');

require_once BASEPATH . 'core/Common.php';

// Mock the CI super object
class CI_Controller {
    public static $instance;
    public function __construct() {
        self::$instance =& $this;
    }
    public static function &get_instance() {
        return self::$instance;
    }
}

class Mock_Loader {
    public function model($model) {
        $file = APPPATH . 'models/' . ucfirst($model) . '.php';
        if (file_exists($file)) {
            require_once $file;
            $CI =& CI_Controller::get_instance();
            $name = strtolower($model);
            // Handle naming conventions based on file content if needed, but simple instantiation usually works
            // Actually, CI models are classes. We need to instantiate them.
            // Let's rely on standard php require and manual instantiation for this test script 
            // since spinning up the full CI engine is complex.
        }
    }
}

// Since booting CI completely is hard from a loose script without index.php context, 
// let's just make a script that connects to DB and runs the logic manually or 
// try to hook into the existing index.php?
// Hooking into index.php is better.

echo "Use the command line to run this test properly via CodeIgniter CLI if possible, or usually: php index.php tools/test_slots\n";
echo "But we will try to just modify the Facility_model to print debug info in a log file, which is safer.";
?>
