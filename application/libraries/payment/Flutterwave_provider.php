<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Abstract_payment_provider.php';
require_once BASEPATH . 'helpers/payment_config_helper.php';
require_once BASEPATH . 'helpers/url_helper.php';

class Flutterwave_provider extends Abstract_payment_provider {
    private const API_BASE = 'https://api.flutterwave.com/v3';

    public function initialize($amount, $currency, array $customer, array $metadata = []) {
        $secretKey = $this->getSecretKey();
        $txRef = $metadata['transaction_ref'] ?? ('TXN-' . time());

        $data = [
            'tx_ref' => $txRef,
            'amount' => $amount,
            'currency' => $currency,
            'redirect_url' => $this->appendGatewayToCallback($this->config['callback_url'] ?? ''),
            'payment_options' => 'card,account,banktransfer,mpesa,mobilemoney,ussd',
            'customer' => [
                'email' => $customer['email'],
                'name' => $customer['name'] ?? '',
                'phone_number' => $customer['phone'] ?? '',
            ],
            'customizations' => [
                'title' => $metadata['title'] ?? 'Payment',
                'description' => $metadata['description'] ?? '',
            ],
            'meta' => [
                'payment_type' => $metadata['payment_type'] ?? '',
                'reference_id' => $metadata['reference_id'] ?? '',
            ],
        ];

        $response = $this->httpRequest(self::API_BASE . '/payments', [
            'method' => 'POST',
            'body' => $data,
            'headers' => [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json',
            ],
        ]);

        if ($response['curl_error']) {
            throw new Exception('Flutterwave API error: ' . $response['curl_error']);
        }

        $result = json_decode($response['body'], true);
        if (($result['status'] ?? '') === 'success') {
            $checkoutLink = $this->extractCheckoutLink($result);
            if ($checkoutLink === '') {
                error_log('Flutterwave initialize: success response but no checkout link. HTTP ' . $response['http_code']);
                throw new Exception('Flutterwave did not return a checkout URL. Check API keys and test mode settings.');
            }
            return [
                'success' => true,
                'authorization_url' => $checkoutLink,
                'reference' => $txRef,
            ];
        }

        $message = $result['message'] ?? 'Failed to initialize payment';
        error_log('Flutterwave initialize failed (HTTP ' . $response['http_code'] . '): ' . $message);
        throw new Exception($message);
    }

    /**
     * Resolve hosted checkout URL from Flutterwave Standard API response.
     * @see https://developer.flutterwave.com/v3.0.0/docs/e-commerce
     */
    private function extractCheckoutLink(array $result) {
        $data = $result['data'] ?? [];
        $candidates = [
            $data['link'] ?? null,
            $data['checkout_url'] ?? null,
            $result['link'] ?? null,
        ];
        foreach ($candidates as $link) {
            if (!is_string($link) || $link === '') {
                continue;
            }
            if (!preg_match('/^https?:\/\//i', $link)) {
                $link = 'https://checkout.flutterwave.com' . (strpos($link, '/') === 0 ? '' : '/') . $link;
            }
            if ($this->isCheckoutUrl($link)) {
                return $link;
            }
        }
        return '';
    }

    private function isCheckoutUrl($url) {
        if (!is_trusted_payment_gateway_url($url)) {
            return false;
        }
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        return (bool) preg_match('#/(hosted/pay|v3/hosted/pay|pay/)#i', $path . '/');
    }

    public function verify($reference, array $options = []) {
        $secretKey = $this->getSecretKey();
        $transactionId = $options['transaction_id'] ?? null;
        $txRef = $options['tx_ref'] ?? null;

        if ($transactionId) {
            $result = $this->verifyByTransactionId($secretKey, $transactionId);
            if ($result['success'] || empty($txRef)) {
                return $result;
            }
        }

        if ($txRef) {
            return $this->verifyByTxRef($secretKey, $txRef);
        }

        if (ctype_digit((string) $reference)) {
            return $this->verifyByTransactionId($secretKey, $reference);
        }

        return $this->verifyByTxRef($secretKey, $reference);
    }

    private function verifyByTransactionId($secretKey, $transactionId) {
        $url = self::API_BASE . '/transactions/' . rawurlencode((string) $transactionId) . '/verify';
        return $this->parseVerifyResponse($this->httpRequest($url, [
            'headers' => ['Authorization: Bearer ' . $secretKey],
        ]));
    }

    private function verifyByTxRef($secretKey, $txRef) {
        $url = self::API_BASE . '/transactions/verify_by_reference?tx_ref=' . rawurlencode($txRef);
        return $this->parseVerifyResponse($this->httpRequest($url, [
            'headers' => ['Authorization: Bearer ' . $secretKey],
        ]));
    }

    private function parseVerifyResponse(array $response) {
        if ($response['curl_error']) {
            return ['success' => false, 'message' => 'cURL error: ' . $response['curl_error']];
        }

        $result = json_decode($response['body'], true);
        if (($result['status'] ?? '') !== 'success') {
            return ['success' => false, 'message' => $result['message'] ?? 'Verification failed'];
        }

        $data = $result['data'] ?? [];
        $status = strtolower($data['status'] ?? '');

        return [
            'success' => $status === 'successful',
            'pending' => in_array($status, ['pending', 'processing'], true),
            'amount' => (float) ($data['amount'] ?? 0),
            'currency' => $data['currency'] ?? '',
            'gateway_reference' => $data['tx_ref'] ?? ($data['flw_ref'] ?? ''),
            'flutterwave_transaction_id' => $data['id'] ?? null,
            'customer' => [
                'email' => $data['customer']['email'] ?? '',
                'name' => $data['customer']['name'] ?? '',
            ],
            'raw_response' => $result,
        ];
    }

    /**
     * Flutterwave signs webhooks with a shared secret hash (verif-hash header).
     * Compare using constant-time equality — do not parse the body first.
     */
    public function verifyWebhookSignature($rawBody, array $serverHeaders) {
        $signature = $serverHeaders['HTTP_VERIF_HASH'] ?? '';
        if ($signature === '') {
            error_log('Flutterwave webhook: missing verif-hash header');
            return false;
        }

        $secretHash = $this->config['secret_key'] ?? '';
        if ($secretHash === '') {
            $secretHash = $this->getSecretKey();
        }

        if ($secretHash === '') {
            error_log('Flutterwave webhook: webhook secret hash not configured');
            return false;
        }

        return $this->timingSafeEquals($secretHash, $signature);
    }

    public function extractWebhookReference(array $payload) {
        return $payload['data']['tx_ref'] ?? null;
    }

    public function isTestWebhook(array $payload) {
        $event = $payload['event'] ?? $payload['type'] ?? '';
        $processable = [
            'charge.completed',
            'transfer.completed',
            'refund.completed',
            'refund.failed',
        ];
        if ($event === '' || !in_array($event, $processable, true)) {
            return true;
        }
        if (in_array($event, ['charge.completed', 'refund.completed', 'refund.failed'], true)) {
            return empty($payload['data']) || empty($payload['data']['tx_ref']);
        }
        return empty($payload['data']);
    }

    public function shouldProcessWebhookEvent(array $payload) {
        $event = $payload['event'] ?? $payload['type'] ?? '';
        return in_array($event, ['charge.completed', 'transfer.completed'], true);
    }

    private function appendGatewayToCallback($callbackUrl) {
        if ($callbackUrl === '') {
            return '';
        }
        $separator = (strpos($callbackUrl, '?') !== false) ? '&' : '?';
        if (stripos($callbackUrl, 'gateway=flutterwave') !== false) {
            return $callbackUrl;
        }
        return $callbackUrl . $separator . 'gateway=flutterwave';
    }
}
