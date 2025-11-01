<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment extends Base_Controller {
    private $gatewayModel;
    private $paymentTransactionModel;
    private $bookingModel;
    private $bookingPaymentModel;
    private $transactionModel;
    private $cashAccountModel;
    private $accountModel;
    
    public function __construct() {
        parent::__construct();
        // Payment controller can be accessed without auth for public payments
        $this->gatewayModel = $this->loadModel('Payment_gateway_model');
        $this->paymentTransactionModel = $this->loadModel('Payment_transaction_model');
    }
    
    protected function checkAuth() {
        // Allow public access for payment processing
        return true;
    }
    
    /**
     * Initialize payment
     */
    public function initialize() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        
        try {
            $gatewayCode = sanitize_input($_POST['gateway_code'] ?? '');
            $amount = floatval($_POST['amount'] ?? 0);
            $currency = sanitize_input($_POST['currency'] ?? 'NGN');
            $paymentType = sanitize_input($_POST['payment_type'] ?? 'booking_payment');
            $referenceId = intval($_POST['reference_id'] ?? 0);
            
            if (!$gatewayCode || $amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid payment parameters']);
                exit;
            }
            
            // Get gateway
            $gateway = $this->gatewayModel->getByCode($gatewayCode);
            if (!$gateway || !$gateway['is_active']) {
                echo json_encode(['success' => false, 'message' => 'Payment gateway not available']);
                exit;
            }
            
            // Get customer info based on payment type
            $customer = $this->getCustomerInfo($paymentType, $referenceId);
            
            // Generate transaction reference
            $transactionRef = $this->paymentTransactionModel->generateTransactionRef('PGW');
            
            // Create payment transaction record
            $transactionData = [
                'transaction_ref' => $transactionRef,
                'gateway_code' => $gatewayCode,
                'payment_type' => $paymentType,
                'reference_id' => $referenceId,
                'amount' => $amount,
                'currency' => $currency,
                'customer_email' => $customer['email'] ?? '',
                'customer_name' => $customer['name'] ?? '',
                'customer_phone' => $customer['phone'] ?? '',
                'status' => 'pending'
            ];
            
            $paymentTransactionId = $this->paymentTransactionModel->create($transactionData);
            
            if (!$paymentTransactionId) {
                throw new Exception('Failed to create payment transaction');
            }
            
            // Initialize payment with gateway
            require_once BASEPATH . 'libraries/Payment_gateway.php';
            
            $gatewayConfig = [
                'public_key' => $gateway['public_key'],
                'private_key' => $gateway['private_key'],
                'secret_key' => $gateway['secret_key'] ?? '',
                'test_mode' => $gateway['test_mode'],
                'callback_url' => $gateway['callback_url'] ?: base_url('payment/callback'),
                'additional_config' => json_decode($gateway['additional_config'] ?? '{}', true)
            ];
            
            $paymentGateway = new Payment_gateway($gatewayCode, $gatewayConfig);
            
            $metadata = [
                'transaction_ref' => $transactionRef,
                'payment_type' => $paymentType,
                'reference_id' => $referenceId,
                'description' => $this->getPaymentDescription($paymentType, $referenceId)
            ];
            
            $result = $paymentGateway->initialize($amount, $currency, $customer, $metadata);
            
            // Update transaction with gateway reference
            $this->paymentTransactionModel->update($paymentTransactionId, [
                'gateway_transaction_id' => $result['reference'] ?? '',
                'gateway_response' => json_encode($result)
            ]);
            
            echo json_encode([
                'success' => true,
                'authorization_url' => $result['authorization_url'] ?? '',
                'transaction_ref' => $transactionRef,
                'gateway_reference' => $result['reference'] ?? ''
            ]);
            
        } catch (Exception $e) {
            error_log('Payment initialize error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * Payment callback (redirect after payment)
     */
    public function callback() {
        $transactionRef = $_GET['reference'] ?? $_GET['tx_ref'] ?? '';
        $gatewayCode = $_GET['gateway'] ?? 'paystack';
        
        if (!$transactionRef) {
            $this->setFlashMessage('danger', 'Invalid payment reference.');
            redirect('booking-portal');
        }
        
        // Verify payment
        $this->verifyPayment($transactionRef, $gatewayCode);
        
        // Redirect based on payment type
        $transaction = $this->paymentTransactionModel->getByTransactionRef($transactionRef);
        if ($transaction) {
            if ($transaction['payment_type'] === 'booking_payment') {
                redirect('booking-portal?payment=success&ref=' . $transactionRef);
            }
        }
        
        redirect('booking-portal');
    }
    
    /**
     * Webhook handler for payment notifications
     */
    public function webhook() {
        header('Content-Type: application/json');
        
        // Get gateway from header or request
        $gatewayCode = $_GET['gateway'] ?? 
                      ($_SERVER['HTTP_X_GATEWAY'] ?? 'paystack');
        
        try {
            $gateway = $this->gatewayModel->getByCode($gatewayCode);
            if (!$gateway) {
                throw new Exception('Gateway not found');
            }
            
            $input = file_get_contents('php://input');
            $payload = json_decode($input, true);
            
            if (!$payload) {
                $payload = $_POST;
            }
            
            // Process webhook based on gateway
            $reference = $this->extractReferenceFromWebhook($gatewayCode, $payload);
            
            if ($reference) {
                $this->verifyPayment($reference, $gatewayCode, true);
                http_response_code(200);
                echo json_encode(['status' => 'success']);
            } else {
                throw new Exception('Reference not found in webhook');
            }
            
        } catch (Exception $e) {
            error_log('Payment webhook error: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * Verify payment manually
     */
    public function verify($transactionRef) {
        header('Content-Type: application/json');
        
        $gatewayCode = $_GET['gateway'] ?? 'paystack';
        
        try {
            $result = $this->verifyPayment($transactionRef, $gatewayCode);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * Verify payment with gateway
     */
    private function verifyPayment($transactionRef, $gatewayCode, $fromWebhook = false) {
        try {
            $transaction = $this->paymentTransactionModel->getByTransactionRef($transactionRef);
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            if ($transaction['status'] === 'success' && !$fromWebhook) {
                return ['already_verified' => true];
            }
            
            $gateway = $this->gatewayModel->getByCode($gatewayCode);
            if (!$gateway) {
                throw new Exception('Gateway not found');
            }
            
            require_once BASEPATH . 'libraries/Payment_gateway.php';
            
            $gatewayConfig = [
                'public_key' => $gateway['public_key'],
                'private_key' => $gateway['private_key'],
                'secret_key' => $gateway['secret_key'] ?? '',
                'test_mode' => $gateway['test_mode'],
                'additional_config' => json_decode($gateway['additional_config'] ?? '{}', true)
            ];
            
            $paymentGateway = new Payment_gateway($gatewayCode, $gatewayConfig);
            $verification = $paymentGateway->verify($transactionRef);
            
            if ($verification['success']) {
                // Update transaction status
                $this->paymentTransactionModel->updateStatus(
                    $transaction['id'],
                    'success',
                    $verification['gateway_reference'] ?? '',
                    $verification
                );
                
                // Process payment based on type
                $this->processPaymentSuccess($transaction);
                
                return $verification;
            } else {
                // Update as failed
                $this->paymentTransactionModel->updateStatus(
                    $transaction['id'],
                    'failed',
                    null,
                    $verification
                );
                
                throw new Exception($verification['message'] ?? 'Payment verification failed');
            }
            
        } catch (Exception $e) {
            error_log('Payment verification error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Process successful payment
     */
    private function processPaymentSuccess($transaction) {
        try {
            $this->loadModel('Booking_model');
            $this->loadModel('Booking_payment_model');
            $this->loadModel('Transaction_model');
            $this->loadModel('Cash_account_model');
            $this->loadModel('Account_model');
            
            if ($transaction['payment_type'] === 'booking_payment') {
                $booking = $this->bookingModel->getById($transaction['reference_id']);
                if ($booking) {
                    // Create booking payment record
                    $paymentData = [
                        'booking_id' => $booking['id'],
                        'payment_number' => $this->bookingPaymentModel->getNextPaymentNumber(),
                        'payment_date' => date('Y-m-d'),
                        'payment_type' => 'full',
                        'payment_method' => 'gateway',
                        'amount' => $transaction['amount'],
                        'currency' => $transaction['currency'],
                        'status' => 'completed',
                        'gateway_transaction_id' => $transaction['transaction_ref'],
                        'created_by' => null
                    ];
                    
                    $paymentId = $this->bookingPaymentModel->create($paymentData);
                    
                    // Update booking payment
                    $this->bookingModel->addPayment($booking['id'], $transaction['amount']);
                    
                    // Create accounting entries (similar to Bookings controller)
                    // This should integrate with the existing accounting system
                }
            }
        } catch (Exception $e) {
            error_log('Process payment success error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get customer info based on payment type
     */
    private function getCustomerInfo($paymentType, $referenceId) {
        if ($paymentType === 'booking_payment') {
            $booking = $this->loadModel('Booking_model')->getById($referenceId);
            if ($booking) {
                return [
                    'email' => $booking['customer_email'],
                    'name' => $booking['customer_name'],
                    'phone' => $booking['customer_phone']
                ];
            }
        }
        
        return [
            'email' => $_POST['customer_email'] ?? '',
            'name' => $_POST['customer_name'] ?? '',
            'phone' => $_POST['customer_phone'] ?? ''
        ];
    }
    
    /**
     * Get payment description
     */
    private function getPaymentDescription($paymentType, $referenceId) {
        if ($paymentType === 'booking_payment') {
            $booking = $this->loadModel('Booking_model')->getById($referenceId);
            if ($booking) {
                return 'Booking payment for ' . $booking['booking_number'];
            }
        }
        return 'Payment';
    }
    
    /**
     * Extract reference from webhook payload
     */
    private function extractReferenceFromWebhook($gatewayCode, $payload) {
        switch ($gatewayCode) {
            case 'paystack':
                return $payload['data']['reference'] ?? null;
            case 'flutterwave':
                return $payload['data']['tx_ref'] ?? null;
            case 'monnify':
                return $payload['transactionReference'] ?? null;
            default:
                return $payload['reference'] ?? $payload['tx_ref'] ?? null;
        }
    }
}

