<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_gateway {
    protected $gateway;
    protected $config;
    protected $db;
    
    public function __construct($gatewayCode, $config) {
        $this->gateway = $gatewayCode;
        $this->config = $config;
        $this->db = Database::getInstance();
    }
    
    /**
     * Initialize payment
     */
    public function initialize($amount, $currency, $customer, $metadata = []) {
        $method = 'initialize_' . $this->gateway;
        if (method_exists($this, $method)) {
            return $this->$method($amount, $currency, $customer, $metadata);
        }
        throw new Exception("Payment gateway {$this->gateway} not implemented");
    }
    
    /**
     * Verify payment
     */
    public function verify($reference) {
        $method = 'verify_' . $this->gateway;
        if (method_exists($this, $method)) {
            return $this->$method($reference);
        }
        throw new Exception("Payment verification for {$this->gateway} not implemented");
    }
    
    /**
     * Paystack implementation
     */
    private function initialize_paystack($amount, $currency, $customer, $metadata) {
        $publicKey = $this->config['public_key'] ?? '';
        $secretKey = $this->config['test_mode'] ? 
            ($this->config['test_secret_key'] ?? $this->config['private_key']) : 
            $this->config['private_key'];
        
        $url = 'https://api.paystack.co/transaction/initialize';
        
        $data = [
            'email' => $customer['email'],
            'amount' => $amount * 100, // Convert to kobo/cent
            'currency' => $currency,
            'reference' => $metadata['transaction_ref'] ?? '',
            'callback_url' => $this->config['callback_url'] ?? '',
            'metadata' => [
                'custom_fields' => [
                    ['display_name' => 'Customer Name', 'variable_name' => 'customer_name', 'value' => $customer['name'] ?? ''],
                    ['display_name' => 'Transaction Type', 'variable_name' => 'payment_type', 'value' => $metadata['payment_type'] ?? ''],
                    ['display_name' => 'Reference ID', 'variable_name' => 'reference_id', 'value' => $metadata['reference_id'] ?? '']
                ]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Paystack API error: " . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($result['status'] ?? false) {
            return [
                'success' => true,
                'authorization_url' => $result['data']['authorization_url'],
                'access_code' => $result['data']['access_code'],
                'reference' => $result['data']['reference']
            ];
        }
        
        throw new Exception($result['message'] ?? 'Failed to initialize payment');
    }
    
    private function verify_paystack($reference) {
        $secretKey = $this->config['test_mode'] ? 
            ($this->config['test_secret_key'] ?? $this->config['private_key']) : 
            $this->config['private_key'];
        
        $url = "https://api.paystack.co/transaction/verify/{$reference}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result['status'] ?? false) {
            $data = $result['data'];
            return [
                'success' => $data['status'] === 'success',
                'amount' => $data['amount'] / 100, // Convert back from kobo
                'currency' => $data['currency'],
                'gateway_reference' => $data['reference'],
                'customer' => [
                    'email' => $data['customer']['email'] ?? '',
                    'name' => $data['customer']['name'] ?? ''
                ],
                'raw_response' => $result
            ];
        }
        
        return ['success' => false, 'message' => $result['message'] ?? 'Verification failed'];
    }
    
    /**
     * Flutterwave implementation
     */
    private function initialize_flutterwave($amount, $currency, $customer, $metadata) {
        $publicKey = $this->config['public_key'] ?? '';
        $secretKey = $this->config['private_key'] ?? '';
        
        $url = 'https://api.flutterwave.com/v3/payments';
        
        $txRef = $metadata['transaction_ref'] ?? 'TXN-' . time();
        
        $data = [
            'tx_ref' => $txRef,
            'amount' => $amount,
            'currency' => $currency,
            'redirect_url' => $this->config['callback_url'] ?? '',
            'payment_options' => 'card,account,banktransfer,mpesa,mobilemoney,ussd',
            'customer' => [
                'email' => $customer['email'],
                'name' => $customer['name'] ?? '',
                'phone_number' => $customer['phone'] ?? ''
            ],
            'customizations' => [
                'title' => $metadata['title'] ?? 'Payment',
                'description' => $metadata['description'] ?? ''
            ],
            'meta' => [
                'payment_type' => $metadata['payment_type'] ?? '',
                'reference_id' => $metadata['reference_id'] ?? ''
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result['status'] === 'success') {
            return [
                'success' => true,
                'authorization_url' => $result['data']['link'],
                'reference' => $txRef
            ];
        }
        
        throw new Exception($result['message'] ?? 'Failed to initialize payment');
    }
    
    private function verify_flutterwave($reference) {
        $secretKey = $this->config['private_key'] ?? '';
        
        $url = "https://api.flutterwave.com/v3/transactions/{$reference}/verify";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result['status'] === 'success') {
            $data = $result['data'];
            return [
                'success' => $data['status'] === 'successful',
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'gateway_reference' => $data['tx_ref'],
                'customer' => [
                    'email' => $data['customer']['email'] ?? '',
                    'name' => $data['customer']['name'] ?? ''
                ],
                'raw_response' => $result
            ];
        }
        
        return ['success' => false, 'message' => $result['message'] ?? 'Verification failed'];
    }
    
    /**
     * Monnify implementation
     */
    private function initialize_monnify($amount, $currency, $customer, $metadata) {
        $apiKey = $this->config['public_key'] ?? '';
        $secretKey = $this->config['private_key'] ?? '';
        $contractCode = $this->config['additional_config']['contract_code'] ?? '';
        
        // Get access token first
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
        curl_close($ch);
        
        $tokenData = json_decode($tokenResponse, true);
        $accessToken = $tokenData['responseBody']['accessToken'] ?? '';
        
        if (!$accessToken) {
            throw new Exception('Failed to get Monnify access token');
        }
        
        // Initialize payment
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
            'redirectUrl' => $this->config['callback_url'] ?? ''
        ];
        
        $ch = curl_init($paymentUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result['requestSuccessful'] ?? false) {
            return [
                'success' => true,
                'authorization_url' => $result['responseBody']['checkoutUrl'],
                'reference' => $txRef
            ];
        }
        
        throw new Exception($result['responseMessage'] ?? 'Failed to initialize payment');
    }
    
    private function verify_monnify($reference) {
        $apiKey = $this->config['public_key'] ?? '';
        $secretKey = $this->config['private_key'] ?? '';
        
        // Get access token
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
        curl_close($ch);
        
        if ($tokenHttpCode !== 200) {
            return ['success' => false, 'message' => 'Monnify auth failed (HTTP ' . $tokenHttpCode . ')'];
        }
        
        $tokenData = json_decode($tokenResponse, true);
        $accessToken = $tokenData['responseBody']['accessToken'] ?? '';
        
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to obtain Monnify access token'];
        }
        
        // Verify transaction
        $verifyUrl = ($this->config['test_mode'] 
            ? 'https://sandbox.monnify.com' 
            : 'https://api.monnify.com') 
            . '/api/v2/transactions/' . urlencode($reference);
        
        $ch = curl_init($verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $body = $result['responseBody'] ?? [];
            
            if (($body['paymentStatus'] ?? '') === 'PAID') {
                return [
                    'success' => true,
                    'amount' => $body['amount'] ?? 0,
                    'currency' => $body['currencyCode'] ?? 'NGN',
                    'gateway_reference' => $body['transactionReference'] ?? $reference,
                    'raw_response' => $result
                ];
            }
            
            return ['success' => false, 'message' => 'Payment status: ' . ($body['paymentStatus'] ?? 'unknown')];
        }
        
        return ['success' => false, 'message' => 'Monnify verification failed (HTTP ' . $httpCode . ')'];
    }
    
    /**
     * Verify Flutterwave payment by tx_ref (fallback when transaction ID unavailable)
     */
    public function verify_flutterwave_by_ref($txRef) {
        $secretKey = $this->config['private_key'] ?? '';
        
        $url = "https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=" . urlencode($txRef);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (($result['status'] ?? '') === 'success') {
            $data = $result['data'] ?? [];
            return [
                'success' => ($data['status'] ?? '') === 'successful',
                'amount' => $data['amount'] ?? 0,
                'currency' => $data['currency'] ?? '',
                'gateway_reference' => $data['tx_ref'] ?? $txRef,
                'raw_response' => $result
            ];
        }
        
        return ['success' => false, 'message' => $result['message'] ?? 'Flutterwave tx_ref verification failed'];
    }
    
    /**
     * Stripe — returns graceful error (SDK not installed)
     */
    private function initialize_stripe($amount, $currency, $customer, $metadata) {
        return [
            'success' => false,
            'message' => 'Stripe integration requires the Stripe PHP SDK. Please install it via Composer.'
        ];
    }
    
    /**
     * PayPal — returns graceful error (not yet implemented)
     */
    private function initialize_paypal($amount, $currency, $customer, $metadata) {
        return [
            'success' => false,
            'message' => 'PayPal integration is not yet implemented. Please use another payment method.'
        ];
    }
}

