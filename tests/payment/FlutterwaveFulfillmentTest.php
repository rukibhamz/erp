<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$runner = new SimpleTestRunner();

class Flutterwave_verify_order_stub extends Flutterwave_provider {
    public $txRefCalls = 0;
    public $idCalls = 0;

    public function verify($reference, array $options = []) {
        $secretKey = 'test';
        $txRef = $options['tx_ref'] ?? null;
        $transactionId = $options['transaction_id'] ?? null;

        if ($txRef !== null && $txRef !== '') {
            $this->txRefCalls++;
            return ['success' => true, 'amount' => 100, 'currency' => 'NGN', 'gateway_reference' => $txRef];
        }
        if ($transactionId) {
            $this->idCalls++;
            return ['success' => true, 'amount' => 100, 'currency' => 'NGN', 'gateway_reference' => 'wrong-ref'];
        }
        return ['success' => false];
    }
}

$stub = new Flutterwave_verify_order_stub([
    'private_key' => 'FLWSECK_TEST',
    'secret_key' => 'hash',
    'test_mode' => true,
]);

$stub->verify('ignored', ['tx_ref' => 'BKG-001', 'transaction_id' => '999']);
$runner->assertEquals(1, $stub->txRefCalls, 'Verify prefers tx_ref when provided');
$runner->assertEquals(0, $stub->idCalls, 'Does not fall through to transaction_id when tx_ref succeeds');

// Status mapping
$provider = Payment_provider_factory::create('flutterwave', [
    'private_key' => 'FLWSECK_TEST',
    'secret_key' => 'hash',
    'test_mode' => true,
]);

$reflection = new ReflectionClass($provider);
$method = $reflection->getMethod('parseVerifyResponse');
$method->setAccessible(true);

$paid = $method->invoke($provider, [
    'curl_error' => '',
    'body' => json_encode([
        'status' => 'success',
        'data' => ['status' => 'paid', 'amount' => 50, 'currency' => 'NGN', 'tx_ref' => 'X'],
    ]),
]);
$runner->assertTrue($paid['success'], 'Treats Flutterwave status "paid" as successful');

exit($runner->summary());
