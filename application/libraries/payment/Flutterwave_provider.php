<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Abstract_payment_provider.php';
require_once BASEPATH . 'helpers/payment_config_helper.php';
require_once BASEPATH . 'helpers/url_helper.php';

class Flutterwave_provider extends Abstract_payment_provider {
    private const API_BASE = 'https://api.flutterwave.com/v3';

    /**
     * Flutterwave Standard — server POST /v3/payments, redirect to data.link.
     * @see https://developer.flutterwave.com/v3.0.0/docs/flutterwave-standard-1
     */
    public function initialize($amount, $currency, array $customer, array $metadata = []) {
        $secretKey = $this->getSecretKey();
        if ($secretKey === '') {
            throw new Exception('Flutterwave secret key is not configured');
        }

        $txRef = $metadata['transaction_ref'] ?? ('TXN-' . time());

        $data = [
            'tx_ref' => $txRef,
            'amount' => $amount,
            'currency' => $currency,
            'redirect_url' => $this->appendGatewayToCallback($this->config['callback_url'] ?? ''),
            'payment_options' => 'card,account,banktransfer,mpesa,mobilemoney,ussd',
            'customer' => [
                'email' => $customer['email'] ?? '',
                'name' => $customer['name'] ?? '',
                'phonenumber' => $customer['phone'] ?? '',
            ],
            'customizations' => [
                'title' => $metadata['title'] ?? 'Payment',
                'description' => $metadata['description'] ?? ($metadata['payment_type'] ?? 'Payment'),
            ],
            'meta' => [
                'payment_type' => $metadata['payment_type'] ?? '',
                'reference_id' => (string) ($metadata['reference_id'] ?? ''),
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
                error_log('Flutterwave Standard: no checkout link in response (HTTP ' . $response['http_code'] . ')');
                throw new Exception('Flutterwave did not return a payment link. Check API keys and dashboard settings.');
            }
            return [
                'success' => true,
                'authorization_url' => $checkoutLink,
                'reference' => $txRef,
            ];
        }

        $message = $result['message'] ?? 'Failed to initialize payment';
        error_log('Flutterwave Standard initialize failed (HTTP ' . $response['http_code'] . '): ' . $message);
        throw new Exception($message);
    }

    /**
     * Hosted payment link from POST /v3/payments (data.link).
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
        return (bool) preg_match('#/(hosted/pay|v3/hosted/pay)#i', $path);
    }

    public function verify($reference, array $options = []) {
        $secretKey = $this->getSecretKey();
        $transactionId = $options['transaction_id'] ?? null;
        $txRef = $options['tx_ref'] ?? null;

        if ($txRef === null && !ctype_digit((string) $reference)) {
            $txRef = (string) $reference;
        }

        // Prefer tx_ref — it matches our payment_transactions.transaction_ref.
        if ($txRef !== null && $txRef !== '') {
            $byRef = $this->verifyByTxRef($secretKey, $txRef);
            if ($byRef['success'] || empty($transactionId)) {
                return $byRef;
            }
        }

        if ($transactionId) {
            $byId = $this->verifyByTransactionId($secretKey, $transactionId);
            if (!empty($txRef) && !empty($byId['gateway_reference']) && $byId['gateway_reference'] !== $txRef) {
                error_log("Flutterwave verify: transaction_id {$transactionId} tx_ref mismatch (expected {$txRef}, got {$byId['gateway_reference']})");
                return [
                    'success' => false,
                    'message' => 'Transaction reference mismatch',
                ];
            }
            return $byId;
        }

        if (ctype_digit((string) $reference)) {
            return $this->verifyByTransactionId($secretKey, $reference);
        }

        return $this->verifyByTxRef($secretKey, (string) $reference);
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
        $status = strtolower((string) ($data['status'] ?? ''));
        $successStatuses = ['successful', 'success', 'completed', 'paid'];
        $pendingStatuses = ['pending', 'processing', 'pending-validation', 'pending_validation'];

        return [
            'success' => in_array($status, $successStatuses, true),
            'pending' => in_array($status, $pendingStatuses, true),
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

    public function verifyWebhookSignature($rawBody, array $serverHeaders) {
        $signature = $serverHeaders['HTTP_VERIF_HASH'] ?? '';
        if ($signature === '') {
            error_log('Flutterwave webhook: missing verif-hash header');
            return false;
        }

        $secretHash = $this->config['webhook_secret_hash']
            ?? $this->config['secret_key']
            ?? ($this->config['additional_config']['webhook_secret_hash'] ?? '');
        if ($secretHash === '') {
            error_log('Flutterwave webhook: webhook secret hash not configured');
            return false;
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
            'charge.success',
            'transfer.completed',
            'refund.completed',
            'refund.failed',
        ];
        if ($event === '' || !in_array($event, $processable, true)) {
            return true;
        }
        if (in_array($event, ['charge.completed', 'charge.success', 'refund.completed', 'refund.failed'], true)) {
            return empty($payload['data']) || empty($payload['data']['tx_ref']);
        }
        return empty($payload['data']);
    }

    public function shouldProcessWebhookEvent(array $payload) {
        $event = $payload['event'] ?? $payload['type'] ?? '';
        return in_array($event, ['charge.completed', 'charge.success', 'transfer.completed'], true);
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
