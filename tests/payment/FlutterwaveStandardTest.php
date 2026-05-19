<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once BASEPATH . 'helpers/url_helper.php';

$runner = new SimpleTestRunner();

class Flutterwave_standard_stub extends Flutterwave_provider {
    private $mockResponse;

    public function setMockHttpResponse(array $response) {
        $this->mockResponse = $response;
    }

    protected function httpRequest($url, array $options = []) {
        if (isset($this->mockResponse) && strpos($url, '/payments') !== false) {
            return $this->mockResponse;
        }
        return parent::httpRequest($url, $options);
    }
}

$stub = new Flutterwave_standard_stub([
    'public_key' => 'FLWPUBK_TEST',
    'private_key' => 'FLWSECK_TEST',
    'callback_url' => 'https://example.com/payment/callback',
    'test_mode' => true,
]);

$stub->setMockHttpResponse([
    'body' => json_encode([
        'status' => 'success',
        'message' => 'Hosted Link',
        'data' => [
            'link' => 'https://checkout.flutterwave.com/v3/hosted/pay/flwlnk-test123',
        ],
    ]),
    'http_code' => 200,
    'curl_error' => '',
]);

$result = $stub->initialize(7500, 'NGN', [
    'email' => 'test@example.com',
    'name' => 'Test',
    'phone' => '09012345678',
], ['transaction_ref' => 'PGW-STD-001']);

$runner->assertTrue($result['success'] ?? false, 'Standard initialize succeeds');
$runner->assertTrue(empty($result['inline'] ?? null), 'Does not use inline mode');
$runner->assertTrue(
    is_trusted_payment_gateway_url($result['authorization_url'] ?? ''),
    'Returns trusted hosted checkout URL'
);
$runner->assertTrue(
    strpos($result['authorization_url'] ?? '', '/v3/hosted/pay/') !== false,
    'URL is a Flutterwave Standard hosted pay link'
);
$runner->assertEquals('PGW-STD-001', $result['reference'] ?? '', 'Preserves tx_ref');

exit($runner->summary());
