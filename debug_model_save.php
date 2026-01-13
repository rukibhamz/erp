<?php
define('BASEPATH', __DIR__ . '/application/');
define('APPPATH', __DIR__ . '/application/');
define('ENVIRONMENT', 'development');

require_once 'application/core/Base_Controller.php'; // Mock or load if needed
require_once 'application/core/Base_Model.php';
require_once 'application/core/Database.php';
require_once 'application/models/Bookable_config_model.php';

// Mock DB connection
try {
    $model = new Bookable_config_model();
    echo "Model instantiated.\n";
    
    $testData = [
        'space_id' => 99999,
        'is_bookable' => 1,
        'pricing_rules' => json_encode(['test' => 'data'])
    ];
    
    echo "Attempting create...\n";
    $id = $model->create($testData);
    
    if ($id) {
        echo "Create successful. ID: $id\n";
        
        $retrieved = $model->getBySpace(99999);
        if ($retrieved) {
            echo "Retrieve successful: " . $retrieved['pricing_rules'] . "\n";
        } else {
            echo "Retrieve FAILED.\n";
        }
        
        // Cleanup
        $model->deleteBy(['space_id' => 99999]);
        echo "Cleanup done.\n";
    } else {
        echo "Create FAILED.\n";
        // Check error log
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
