<?php
/**
 * Integration test — requires Flutterwave sandbox credentials in the environment.
 * Run: FLUTTERWAVE_SECRET_KEY=FLWSECK_TEST-xxx php tests/payment/FlutterwaveIntegrationTest.php
 */
require_once dirname(__DIR__) . '/bootstrap.php';

$runner = new SimpleTestRunner();
$secretKey = getenv('FLUTTERWAVE_SECRET_KEY') ?: '';

if ($secretKey === '' || strpos($secretKey, 'FLWSECK') === false) {
    echo "SKIP: Set FLUTTERWAVE_SECRET_KEY to a Flutterwave test secret key to run integration tests.\n";
    exit(0);
}

$publicKey = getenv('FLUTTERWAVE_PUBLIC_KEY') ?: '';
$config = [
    'public_key' => $publicKey,
    'private_key' => $secretKey,
    'secret_key' => getenv('FLUTTERWAVE_WEBHOOK_SECRET_HASH') ?: 'test-hash',
    'test_mode' => true,
    'callback_url' => 'https://example.com/payment/callback?gateway=flutterwave',
];

$config['public_key'] = $publicKey ?: 'FLWPUBK_TEST-placeholder';
$provider = Payment_provider_factory::create('flutterwave', $config);
$txRef = 'PGW-TEST-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));

try {
    $init = $provider->initialize(100, 'NGN', [
        'email' => 'integration-test@example.com',
        'name' => 'Integration Test',
        'phone' => '08000000000',
    ], [
        'transaction_ref' => $txRef,
        'payment_type' => 'booking_payment',
        'reference_id' => 1,
        'description' => 'Integration test payment',
    ]);

    $runner->assertTrue($init['success'] ?? false, 'Inline initialization succeeds');
    $runner->assertTrue(!empty($init['inline']), 'Initialization uses inline mode');
    $runner->assertTrue(!empty($init['checkout']['public_key']), 'Checkout config includes public_key');
    $runner->assertEquals($txRef, $init['checkout']['tx_ref'] ?? '', 'Checkout uses provided tx_ref');

    if ($secretKey !== '') {
        $verify = $provider->verify($txRef, ['tx_ref' => $txRef]);
        $runner->assertTrue(is_array($verify), 'Verify endpoint returns structured response');
        $runner->assertTrue(array_key_exists('success', $verify), 'Verify response includes success key');
    }

    echo "\nManual step: call FlutterwaveCheckout() in the browser with the returned config, then verify via transaction_id on callback.\n";

} catch (Exception $e) {
    $runner->assertTrue(false, 'Integration flow threw: ' . $e->getMessage());
}

exit($runner->summary());
