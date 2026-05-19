<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$runner = new SimpleTestRunner();
$config = [
    'private_key' => 'FLWSECK_TEST-secret',
    'secret_key' => 'my-webhook-secret-hash',
    'test_mode' => true,
    'callback_url' => 'https://example.com/payment/callback',
];

$provider = Payment_provider_factory::create('flutterwave', $config);
$payload = json_encode([
    'event' => 'charge.completed',
    'data' => ['tx_ref' => 'PGW-20260101120000-ABCD', 'id' => 123456, 'status' => 'successful'],
]);

// Valid signature
$valid = $provider->verifyWebhookSignature($payload, ['HTTP_VERIF_HASH' => 'my-webhook-secret-hash']);
$runner->assertTrue($valid, 'Accepts webhook when verif-hash matches secret');

// Tampered signature
$tampered = $provider->verifyWebhookSignature($payload, ['HTTP_VERIF_HASH' => 'wrong-hash']);
$runner->assertTrue(!$tampered, 'Rejects webhook when verif-hash does not match');

// Missing header
$missing = $provider->verifyWebhookSignature($payload, []);
$runner->assertTrue(!$missing, 'Rejects webhook when verif-hash header is missing');

// Reference extraction
$decoded = json_decode($payload, true);
$ref = $provider->extractWebhookReference($decoded);
$runner->assertEquals('PGW-20260101120000-ABCD', $ref, 'Extracts tx_ref from charge.completed payload');

// Test ping / unknown event
$runner->assertTrue($provider->isTestWebhook(['event' => 'ping']), 'Treats unknown events as test webhooks');
$runner->assertTrue($provider->shouldProcessWebhookEvent($decoded), 'Processes charge.completed events');

exit($runner->summary());
