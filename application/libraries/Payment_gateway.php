<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH . 'libraries/payment/Payment_provider_factory.php';

class Payment_gateway {
    protected $gateway;
    protected $config;
    protected $provider;

    public function __construct($gatewayCode, $config) {
        $this->gateway = strtolower($gatewayCode);
        $this->config = $config;

        if (Payment_provider_factory::supports($this->gateway)) {
            $this->provider = Payment_provider_factory::create($this->gateway, $config);
        }
    }

    /**
     * Initialize payment
     */
    public function initialize($amount, $currency, $customer, $metadata = []) {
        if ($this->provider) {
            return $this->provider->initialize($amount, $currency, $customer, $metadata);
        }

        $method = 'initialize_' . $this->gateway;
        if (method_exists($this, $method)) {
            return $this->$method($amount, $currency, $customer, $metadata);
        }
        throw new Exception("Payment gateway {$this->gateway} not implemented");
    }

    /**
     * Verify payment
     *
     * @param string $reference
     * @param array $options Optional gateway-specific options (e.g. transaction_id for Flutterwave)
     */
    public function verify($reference, array $options = []) {
        if ($this->provider) {
            return $this->provider->verify($reference, $options);
        }

        $method = 'verify_' . $this->gateway;
        if (method_exists($this, $method)) {
            return $this->$method($reference);
        }
        throw new Exception("Payment verification for {$this->gateway} not implemented");
    }

    /**
     * @return Payment_provider_interface|null
     */
    public function getProvider() {
        return $this->provider ?? null;
    }

    /**
     * Verify Flutterwave payment by tx_ref (backward compatibility)
     */
    public function verify_flutterwave_by_ref($txRef) {
        if ($this->provider instanceof Flutterwave_provider) {
            return $this->provider->verify($txRef, ['tx_ref' => $txRef]);
        }

        $secretKey = $this->config['private_key'] ?? '';
        $url = 'https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=' . urlencode($txRef);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $result = json_decode($response, true);

        if (($result['status'] ?? '') === 'success') {
            $data = $result['data'] ?? [];
            return [
                'success' => ($data['status'] ?? '') === 'successful',
                'amount' => $data['amount'] ?? 0,
                'currency' => $data['currency'] ?? '',
                'gateway_reference' => $data['tx_ref'] ?? $txRef,
                'raw_response' => $result,
            ];
        }

        return ['success' => false, 'message' => $result['message'] ?? 'Flutterwave tx_ref verification failed'];
    }

    /**
     * Monnify implementation
     */
    private function initialize_monnify($amount, $currency, $customer, $metadata) {
        $apiKey = $this->config['public_key'] ?? '';
        $secretKey = $this->config['private_key'] ?? '';
        $contractCode = $this->config['additional_config']['contract_code'] ?? '';

        $tokenUrl = 'https://sandbox.monnify.com/api/v1/auth/login';
        if (!$this->config['test_mode']) {
            $tokenUrl = 'https://api.monnify.com/api/v1/auth/login';
        }

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['apiKey' => $apiKey, 'secretKey' => $secretKey]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $tokenResponse = curl_exec($ch);
        $tokenData = json_decode($tokenResponse, true);
        $accessToken = $tokenData['responseBody']['accessToken'] ?? '';

        if (!$accessToken) {
            throw new Exception('Failed to get Monnify access token');
        }

        $txRef = $metadata['transaction_ref'] ?? 'TXN-' . time();
        $paymentUrl = 'https://sandbox.monnify.com/api/v1/merchant/transactions/init-transaction';
        if (!$this->config['test_mode']) {
            $paymentUrl = 'https://api.monnify.com/api/v1/merchant/transactions/init-transaction';
        }

        $data = [
            'amount' => $amount,
            'customerName' => $customer['name'] ?? '',
            'customerEmail' => $customer['email'],
            'customerPhoneNumber' => $customer['phone'] ?? '',
            'paymentReference' => $txRef,
            'paymentDescription' => $metadata['description'] ?? 'Payment',
            'currencyCode' => $currency,
            'contractCode' => $contractCode,
            'redirectUrl' => $this->config['callback_url'] ?? '',
        ];

        $ch = curl_init($paymentUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $result = json_decode($response, true);

        if ($result['requestSuccessful'] ?? false) {
            return [
                'success' => true,
                'authorization_url' => $result['responseBody']['checkoutUrl'],
                'reference' => $txRef,
            ];
        }

        throw new Exception($result['responseMessage'] ?? 'Failed to initialize payment');
    }

    private function verify_monnify($reference) {
        $apiKey = $this->config['public_key'] ?? '';
        $secretKey = $this->config['private_key'] ?? '';

        $tokenUrl = $this->config['test_mode']
            ? 'https://sandbox.monnify.com/api/v1/auth/login'
            : 'https://api.monnify.com/api/v1/auth/login';

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['apiKey' => $apiKey, 'secretKey' => $secretKey]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $tokenResponse = curl_exec($ch);
        $tokenHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($tokenHttpCode !== 200) {
            return ['success' => false, 'message' => 'Monnify auth failed (HTTP ' . $tokenHttpCode . ')'];
        }

        $tokenData = json_decode($tokenResponse, true);
        $accessToken = $tokenData['responseBody']['accessToken'] ?? '';

        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to obtain Monnify access token'];
        }

        $verifyUrl = ($this->config['test_mode']
            ? 'https://sandbox.monnify.com'
            : 'https://api.monnify.com')
            . '/api/v2/transactions/' . urlencode($reference);

        $ch = curl_init($verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $body = $result['responseBody'] ?? [];

            if (($body['paymentStatus'] ?? '') === 'PAID') {
                return [
                    'success' => true,
                    'amount' => $body['amount'] ?? 0,
                    'currency' => $body['currencyCode'] ?? 'NGN',
                    'gateway_reference' => $body['transactionReference'] ?? $reference,
                    'raw_response' => $result,
                ];
            }

            return ['success' => false, 'message' => 'Payment status: ' . ($body['paymentStatus'] ?? 'unknown')];
        }

        return ['success' => false, 'message' => 'Monnify verification failed (HTTP ' . $httpCode . ')'];
    }

    private function initialize_stripe($amount, $currency, $customer, $metadata) {
        return [
            'success' => false,
            'message' => 'Stripe integration requires the Stripe PHP SDK. Please install it via Composer.',
        ];
    }

    private function initialize_paypal($amount, $currency, $customer, $metadata) {
        return [
            'success' => false,
            'message' => 'PayPal integration is not yet implemented. Please use another payment method.',
        ];
    }
}
