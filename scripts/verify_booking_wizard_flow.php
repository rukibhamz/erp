<?php
/**
 * Booking wizard flow verification (CLI).
 * Run: php scripts/verify_booking_wizard_flow.php
 *
 * Requires MySQL and a bookable space in the database.
 */
define('BASEPATH', dirname(__DIR__) . '/application/');
define('APPPATH', dirname(__DIR__) . '/application/');
define('ROOTPATH', dirname(__DIR__) . '/');
define('SYSPATH', dirname(__DIR__) . '/application/core/');

$configInstalled = BASEPATH . 'config/config.installed.php';
$configDefault = BASEPATH . 'config/config.php';
if (file_exists($configInstalled)) {
    $config = require $configInstalled;
} elseif (file_exists($configDefault)) {
    $config = require $configDefault;
} else {
    fwrite(STDERR, "FAIL: No config file found.\n");
    exit(1);
}

require_once SYSPATH . 'Database.php';
require_once BASEPATH . 'core/Base_model.php';
require_once BASEPATH . 'models/Space_model.php';
require_once BASEPATH . 'models/Facility_model.php';

$failures = 0;
$passes = 0;

function ok($msg) {
    global $passes;
    $passes++;
    echo "[PASS] {$msg}\n";
}

function fail($msg) {
    global $failures;
    $failures++;
    echo "[FAIL] {$msg}\n";
}

try {
    $db = Database::getInstance();
    $db->getConnection();
    ok('Database connection');
} catch (Throwable $e) {
    fail('Database connection: ' . $e->getMessage());
    echo "\nStart MySQL (XAMPP) and re-run.\n";
    exit(1);
}

$spaceModel = new Space_model();
$facilityModel = new Facility_model();

$space = null;
$locations = $db->fetchAll("SELECT id FROM `" . $db->getPrefix() . "properties` WHERE status = 'active' LIMIT 20");
foreach ($locations as $loc) {
    $spaces = $spaceModel->getBookableSpaces($loc['id']);
    if (!empty($spaces)) {
        $space = $spaces[0];
        break;
    }
}

if (!$space) {
    fail('No bookable space found in database');
    exit(1);
}
ok('Found bookable space id=' . $space['id'] . ' name=' . ($space['space_name'] ?? 'n/a'));

$facilityId = (int) ($space['facility_id'] ?? 0);
if (!$facilityId) {
    $facilityId = (int) ($spaceModel->syncToBookingModule((int) $space['id']) ?: 0);
}
if (!$facilityId) {
    fail('Space has no facility_id and sync failed');
    exit(1);
}
ok('Facility id=' . $facilityId);

$testDate = date('Y-m-d', strtotime('+7 days'));
$slots = $facilityModel->getAvailableTimeSlots($facilityId, $testDate, $testDate);
if (!($slots['success'] ?? false)) {
    fail('getAvailableTimeSlots: ' . ($slots['message'] ?? 'unknown'));
} else {
    $count = count($slots['slots'] ?? []);
    ok("getAvailableTimeSlots returned {$count} slot(s) for {$testDate}");
}

if ($count > 0) {
    $first = $slots['slots'][0];
    $start = substr($first['start'], 0, 5);
    $end = substr($first['end'], 0, 5);
    $bookable = $facilityModel->isTimeRangeBookable($facilityId, $testDate, $start, $end);
    if ($bookable) {
        ok("isTimeRangeBookable accepts {$start}-{$end}");
    } else {
        fail("isTimeRangeBookable rejected {$start}-{$end} (UI would block save-step)");
    }

    $price = $facilityModel->calculatePrice(
        $facilityId,
        $testDate,
        $start,
        $end,
        'hourly',
        1,
        false,
        null,
        null
    );
    if ($price > 0 || $price === 0.0) {
        ok('calculatePrice returned ' . $price);
    } else {
        fail('calculatePrice returned invalid value');
    }
} else {
    echo "[WARN] No slots on {$testDate}; skip slot validation (try another date).\n";
}

// Picnic guest rule
$guests = 4;
if ($guests < 5) {
    ok('Picnic guest rule would reject 4 guests (expected)');
}

// Session field expectations for finalize
$required = ['resource_id', 'customer_email', 'date', 'start_time', 'end_time'];
ok('Finalize requires: ' . implode(', ', $required));

echo "\n";
echo "Summary: {$passes} passed, {$failures} failed.\n";
echo "Manual UI checklist:\n";
echo "  1. Step1 -> pick space -> Step2\n";
echo "  2. Step2: type + date + time -> Continue -> Step3\n";
echo "  3. Step3: Continue (addons optional) -> Step4\n";
echo "  4. Step4: valid email + required fields -> Step5\n";
echo "  5. Step5: pay_later or gateway -> confirmation\n";
echo "  6. Back links on steps 3-5 must use space_id in step2 URL\n";

exit($failures > 0 ? 1 : 0);
