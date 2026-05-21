<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Abstract_payment_provider.php';

class Paystack_provider extends Abstract_payment_provider {
    public function initialize($amount, $currency, array $customer, array $metadata = []) {
        $secretKey = $this->getSecretKey();
        $url = 'https://api.paystack.co/transaction/initialize';

        $data = [
            'email' => $customer['email'],
            'amount' => (int) round($amount * 100),
            'currency' => $currency,
            'reference' => $metadata['transaction_ref'] ?? '',
            'callback_url' => $this->config['callback_url'] ?? '',
            'metadata' => [
                'custom_fields' => [
                    ['display_name' => 'Customer Name', 'variable_name' => 'customer_name', 'value' => $customer['name'] ?? ''],
                    ['display_name' => 'Transaction Type', 'variable_name' => 'payment_type', 'value' => $metadata['payment_type'] ?? ''],
                    ['display_name' => 'Reference ID', 'variable_name' => 'reference_id', 'value' => $metadata['reference_id'] ?? ''],
                ],
            ],
        ];

        $response = $this->httpRequest($url, [
            'method' => 'POST',
            'body' => $data,
            'headers' => [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json',
            ],
        ]);

        if ($response['curl_error']) {
            throw new Exception('Paystack API error: ' . $response['curl_error']);
        }

        $result = json_decode($response['body'], true);
        if ($result['status'] ?? false) {
            return [
                'success' => true,
                'authorization_url' => $result['data']['authorization_url'],
                'access_code' => $result['data']['access_code'],
                'reference' => $result['data']['reference'],
            ];
        }

        throw new Exception($result['message'] ?? 'Failed to initialize payment');
    }

    public function verify($reference, array $options = []) {
        $secretKey = $this->getSecretKey();
        $url = 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference);

        $response = $this->httpRequest($url, [
            'headers' => ['Authorization: Bearer ' . $secretKey],
        ]);

        if ($response['curl_error']) {
            return ['success' => false, 'message' => 'cURL error: ' . $response['curl_error']];
        }

        $result = json_decode($response['body'], true);
        if ($result['status'] ?? false) {
            $data = $result['data'];
            return [
                'success' => ($data['status'] ?? '') === 'success',
                'amount' => ($data['amount'] ?? 0) / 100,
                'currency' => $data['currency'] ?? '',
                'gateway_reference' => $data['reference'] ?? $reference,
                'customer' => [
                    'email' => $data['customer']['email'] ?? '',
                    'name' => $data['customer']['name'] ?? '',
                ],
                'raw_response' => $result,
            ];
        }

        return ['success' => false, 'message' => $result['message'] ?? 'Verification failed'];
    }

    public function verifyWebhookSignature($rawBody, array $serverHeaders) {
        $signature = $serverHeaders['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
        if ($signature === '') {
            error_log('Paystack webhook: missing x-paystack-signature header');
            $secret = trim($this->getSecretKey());
            if ($secret !== '') {
                return false;
            }
            return true;
        }
        $computed = hash_hmac('sha512', $rawBody, $this->getSecretKey());
        return $this->timingSafeEquals($computed, $signature);
    }

    public function extractWebhookReference(array $payload) {
        return $payload['data']['reference'] ?? null;
    }

    public function isTestWebhook(array $payload) {
        $event = $payload['event'] ?? '';
        if ($event === '' || !in_array($event, ['charge.success', 'transfer.success', 'subscription.create'], true)) {
            return true;
        }
        return empty($payload['data']) || empty($payload['data']['reference']);
    }

    public function shouldProcessWebhookEvent(array $payload) {
        return ($payload['event'] ?? '') === 'charge.success';
    }
}
