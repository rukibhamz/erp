<?php
/**
 * ROBUST WEB FUNCTIONAL TEST RUNNER
 */

define('BASEPATH', __DIR__ . '/../application/');
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<!DOCTYPE html><html><head><title>ERP Robust Tests</title><style>
    body { font-family: monospace; line-height: 1.5; padding: 20px; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .pass { color: green; } .fail { color: red; font-weight: bold; } .info { color: #0066cc; }
    h1 { border-bottom: 2px solid #eee; padding-bottom: 10px; }
    h3 { margin-top: 20px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
    pre { background: #f8f8f8; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
</style></head><body><div class="container"><h1>ERP Robust Functional Test Runner</h1><pre>';

require_once __DIR__ . '/../application/core/Database.php';
require_once __DIR__ . '/../application/core/Base_Model.php';

try {
    $db = Database::getInstance();
    echo "<span class='pass'>✓ Database Connected</span>\n";
} catch (Exception $e) {
    die("<span class='fail'>✗ Database connection failed: " . $e->getMessage() . "</span>\n");
}

$results = ['passed' => 0, 'failed' => 0];
function pass($msg) { global $results; $results['passed']++; echo "<span class='pass'>  [PASS] {$msg}</span>\n"; }
function fail($msg, $details = '') { global $results; $results['failed']++; echo "<span class='fail'>  [FAIL] {$msg}</span>\n"; if ($details) echo "         Details: {$details}\n"; }

$db->beginTransaction();
try {
    echo "<h3>SETTING UP TEST ENVIRONMENT</h3>";
    
    // 1. Create a Property
    echo "  > Creating Test Property...\n";
    $propertyId = $db->insert('properties', [
        'property_code' => 'TEST-PROP-' . rand(1000,9999),
        'property_name' => 'Test Location',
        'status' => 'operational',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    pass("Created Test Property");

    // 2. Create Facility
    echo "  > Creating Test Facility...\n";
    $facilityId = $db->insert('facilities', [
        'facility_name' => 'Test Facility',
        'facility_code' => 'TEST-FAC-' . rand(1000,9999),
        'resource_type' => 'meeting_room',
        'capacity' => 10,
        'hourly_rate' => 100,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    pass("Created Test Facility");

    // 3. Create Space
    echo "  > Creating Test Space...\n";
    $spaceId = $db->insert('spaces', [
        'property_id' => $propertyId,
        'facility_id' => $facilityId,
        'space_name' => 'Test Space',
        'space_number' => 'TS-001',
        'category' => 'event_space',
        'capacity' => 10,
        'operational_status' => 'active',
        'is_bookable' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    pass("Created Test Space");

    // 4. Create Bookable Config
    echo "  > Creating Bookable Config...\n";
    $db->insert('bookable_config', [
        'space_id' => $spaceId,
        'is_bookable' => 1,
        'booking_types' => json_encode(['hourly', 'daily']),
        'availability_rules' => json_encode([
            'operating_hours' => ['start' => '08:00', 'end' => '22:00'],
            'days_available' => [0,1,2,3,4,5,6]
        ]),
        'pricing_rules' => json_encode(['base_hourly' => 100, 'base_daily' => 800])
    ]);
    pass("Created Bookable Config");

    // 5. Create Resource Availability (Mon-Sun)
    require_once __DIR__ . '/../application/models/Resource_availability_model.php';
    $availModel = new Resource_availability_model();
    for ($i=0; $i<=6; $i++) {
        $availModel->setDayAvailability($facilityId, $i, true, '08:00', '22:00');
    }
    pass("Created Resource Availability Rules");

    echo "<h3>RUNNING LOGIC TESTS</h3>";
    require_once __DIR__ . '/../application/models/Facility_model.php';
    $facilityModel = new Facility_model();
    $testDate = date('Y-m-d', strtotime('+1 day'));

    // Test: Availability
    $slots = $facilityModel->getAvailableTimeSlots($facilityId, $testDate);
    if (is_array($slots) && !empty($slots['slots'])) {
        pass("Availability: Found " . count($slots['slots']) . " slots");
    } else {
        fail("Availability: No slots found", json_encode($slots));
    }

    // Test: Conflict
    $db->insert('bookings', [
        'facility_id' => $facilityId,
        'space_id' => $spaceId,
        'booking_date' => $testDate,
        'start_time' => '10:00:00',
        'end_time' => '12:00:00',
        'status' => 'confirmed',
        'booking_type' => 'hourly',
        'base_amount' => 200,
        'total_amount' => 200,
        'balance_amount' => 200,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    pass("Created conflicting booking");

    $slotsAfter = $facilityModel->getAvailableTimeSlots($facilityId, $testDate);
    $foundConflict = false;
    foreach ($slotsAfter['occupied'] as $occ) {
        if ($occ['start'] == '10:00') $foundConflict = true;
    }
    if ($foundConflict) pass("Availability correctly identifies occupied slot");
    else fail("Availability did not return the occupied slot");

    // Test: Price Calculation
    $price = $facilityModel->calculatePrice($facilityId, $testDate, '14:00', '16:00', 'hourly', 1);
    if ($price == 200) pass("Price Calculation correct (2 hrs @ 100/hr = 200)");
    else fail("Price Calculation incorrect", "Expected 200, got {$price}");

    echo "\n<span class='pass'>All tests completed successfully!</span>\n";

} catch (Exception $e) {
    fail("CRITICAL ERROR", $e->getMessage());
} finally {
    $db->rollBack();
    echo "\n<span class='info'>\n> Transaction rolled back. DB is clean.</span>\n";
}
echo "</pre><h2>" . ($results['failed'] == 0 ? "ALL TESTS PASSED" : "TESTS FAILED") . "</h2>";
echo "</div></body></html>";
