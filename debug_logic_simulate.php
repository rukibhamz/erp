<?php
define('BASEPATH', __DIR__ . '/application/');
define('APPPATH', __DIR__ . '/application/');
define('ENVIRONMENT', 'development');

require_once 'application/core/Base_Controller.php'; 
require_once 'application/core/Base_Model.php';
require_once 'application/core/Database.php';
require_once 'application/models/Bookable_config_model.php';

try {
    echo "--- SIMULATION START ---\n";
    
    // Simulate POST data
    $postData = [
        'hourly_rate' => '100.00',
        'is_bookable' => '1',
        'days_available' => ['0', '1', '2']
    ];
    $spaceId = 99999;
    
    echo "Simulating for Space ID: $spaceId\n";
    
    $bookingTypes = [];
    if (!empty($postData['booking_types']) && is_array($postData['booking_types'])) {
        $bookingTypes = $postData['booking_types'];
    }
    
    $pricingRules = [
        'base_hourly' => floatval($postData['hourly_rate'] ?? 0),
        'base_daily' => floatval($postData['daily_rate'] ?? 0),
        'half_day' => floatval($postData['half_day_rate'] ?? 0),
        'weekly' => floatval($postData['weekly_rate'] ?? 0),
        'deposit' => floatval($postData['security_deposit'] ?? 0),
        'peak_rate_multiplier' => floatval($postData['peak_rate_multiplier'] ?? 1.0)
    ];
    
    echo "Pricing Rules: " . json_encode($pricingRules) . "\n";
    
    $availabilityRules = [
        'operating_hours' => [
            'start' => $postData['operating_start'] ?? '08:00',
            'end' => $postData['operating_end'] ?? '22:00'
        ],
        'days_available' => !empty($postData['days_available']) ? array_map('intval', $postData['days_available']) : [0,1,2,3,4,5,6],
        'blackout_dates' => []
    ];
    
    echo "Availability Rules: " . json_encode($availabilityRules) . "\n";
    
    $configData = [
        'space_id' => $spaceId,
        'is_bookable' => 1,
        'booking_types' => json_encode($bookingTypes),
        'minimum_duration' => intval($postData['minimum_duration'] ?? 1),
        'maximum_duration' => !empty($postData['maximum_duration']) ? intval($postData['maximum_duration']) : null,
        'advance_booking_days' => intval($postData['advance_booking_days'] ?? 365),
        'cancellation_policy_id' => !empty($postData['cancellation_policy_id']) ? intval($postData['cancellation_policy_id']) : null,
        'pricing_rules' => json_encode($pricingRules),
        'availability_rules' => json_encode($availabilityRules),
        'setup_time_buffer' => intval($postData['setup_time_buffer'] ?? 0),
        'cleanup_time_buffer' => intval($postData['cleanup_time_buffer'] ?? 0),
        'simultaneous_limit' => intval($postData['simultaneous_limit'] ?? 1)
    ];
    
    echo "Config Data Prepared.\n";
    
    $model = new Bookable_config_model();
    echo "Model Created.\n";
    
    $id = $model->create($configData);
    echo "Create Result ID: " . var_export($id, true) . "\n";
    
    // Cleanup
    if ($id) {
        $model->deleteBy(['space_id' => $spaceId]);
        echo "Cleanup Done.\n";
    }

} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
