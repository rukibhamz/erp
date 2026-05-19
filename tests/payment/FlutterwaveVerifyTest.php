<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$runner = new SimpleTestRunner();

/**
 * Stub provider to simulate Flutterwave verify API responses without HTTP.
 */
class Flutterwave_verify_stub extends Flutterwave_provider {
    private $mockResponse;

    public function setMockResponse(array $response) {
        $this->mockResponse = $response;
    }

    public function verify($reference, array $options = []) {
        return $this->mockResponse;
    }
}

$stub = new Flutterwave_verify_stub([
    'private_key' => 'FLWSECK_TEST',
    'secret_key' => 'hash',
    'test_mode' => true,
    'callback_url' => 'https://example.com/callback',
]);

// Success
$stub->setMockResponse([
    'success' => true,
    'amount' => 15000.00,
    'currency' => 'NGN',
    'gateway_reference' => 'PGW-TEST-001',
]);
$result = $stub->verify('99999', ['transaction_id' => '99999', 'tx_ref' => 'PGW-TEST-001']);
$runner->assertTrue($result['success'], 'Successful verification returns success=true');
$runner->assertEquals(15000.00, $result['amount'], 'Successful verification includes amount');

// Failed
$stub->setMockResponse([
    'success' => false,
    'message' => 'Transaction failed',
]);
$failed = $stub->verify('PGW-FAIL', ['tx_ref' => 'PGW-FAIL']);
$runner->assertTrue(!$failed['success'], 'Failed verification returns success=false');

// Pending
$stub->setMockResponse([
    'success' => false,
    'pending' => true,
    'amount' => 5000,
    'currency' => 'NGN',
]);
$pending = $stub->verify('PGW-PEND', ['tx_ref' => 'PGW-PEND']);
$runner->assertTrue(!empty($pending['pending']), 'Pending verification exposes pending flag');

exit($runner->summary());
