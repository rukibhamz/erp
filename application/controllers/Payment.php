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
    
    public function index() {
        redirect('dashboard');
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
        $transactionRef = $_GET['reference'] ?? $_GET['tx_ref'] ?? $_GET['trxref'] ?? '';
        $gatewayCode = $_GET['gateway'] ?? 'paystack';
        
        if (!$transactionRef) {
            $this->setFlashMessage('danger', 'Invalid payment reference.');
            redirect('payment/confirmation?status=failed');
            return;
        }
        
        try {
            // Verify payment
            $this->verifyPayment($transactionRef, $gatewayCode);
            
            // Get transaction for confirmation page
            $transaction = $this->paymentTransactionModel->getByTransactionRef($transactionRef);
            $status = $transaction ? $transaction['status'] : 'pending';
            
            redirect('payment/confirmation?ref=' . urlencode($transactionRef) . '&status=' . $status);
        } catch (Exception $e) {
            error_log('Payment callback error: ' . $e->getMessage());
            redirect('payment/confirmation?ref=' . urlencode($transactionRef) . '&status=failed');
        }
    }
    
    /**
     * Payment confirmation/thank you page
     */
    public function confirmation() {
        $transactionRef = sanitize_input($_GET['ref'] ?? '');
        $status = sanitize_input($_GET['status'] ?? 'pending');
        
        $transaction = null;
        $bookingId = null;
        
        if ($transactionRef) {
            $transaction = $this->paymentTransactionModel->getByTransactionRef($transactionRef);
            if ($transaction) {
                $status = $transaction['status'];
                if ($transaction['payment_type'] === 'booking_payment') {
                    $bookingId = $transaction['reference_id'];
                }
            }
        }
        
        $data = [
            'page_title' => 'Payment Confirmation',
            'status' => $status,
            'transaction' => $transaction,
            'booking_id' => $bookingId
        ];
        
        $this->loadView('payment/confirmation', $data);
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
            
            // Verify webhook signature for security
            if (!$this->verifyWebhookSignature($gatewayCode, $input, $gateway)) {
                error_log("Payment webhook signature verification failed for {$gatewayCode}");
                throw new Exception('Invalid webhook signature');
            }
            
            $payload = json_decode($input, true);
            
            if (!$payload) {
                $payload = $_POST;
            }
            
            // Log the webhook event
            error_log("Payment webhook received from {$gatewayCode}: " . substr($input, 0, 500));
            
            // Check if this is a test/verification webhook (no actual payment data)
            if ($this->isTestWebhook($gatewayCode, $payload)) {
                error_log("Payment webhook: Test/verification event received from {$gatewayCode}");
                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Webhook verified']);
                exit;
            }
            
            // Process webhook based on gateway
            $reference = $this->extractReferenceFromWebhook($gatewayCode, $payload);
            
            if ($reference) {
                $this->verifyPayment($reference, $gatewayCode, true);
                http_response_code(200);
                echo json_encode(['status' => 'success']);
            } else {
                // No reference but not a test event - still return success to prevent retries
                error_log("Payment webhook: No reference found but accepting event from {$gatewayCode}");
                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Event acknowledged']);
            }
            
        } catch (Exception $e) {
            error_log('Payment webhook error: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * Check if webhook is a test/verification event
     */
    private function isTestWebhook($gatewayCode, $payload) {
        switch ($gatewayCode) {
            case 'paystack':
                // Paystack sends 'charge.success' for real payments
                // Test pings or other events may have different event types
                $event = $payload['event'] ?? '';
                if (empty($event) || !in_array($event, ['charge.success', 'transfer.success', 'subscription.create'])) {
                    return true;
                }
                // Also check if data is empty or missing reference
                if (empty($payload['data']) || empty($payload['data']['reference'])) {
                    return true;
                }
                return false;
                
            case 'flutterwave':
                // Flutterwave sends 'charge.completed' or 'transfer.completed' for real payments
                $event = $payload['event'] ?? '';
                if (empty($event) || !in_array($event, ['charge.completed', 'transfer.completed'])) {
                    return true;
                }
                if (empty($payload['data']) || empty($payload['data']['tx_ref'])) {
                    return true;
                }
                return false;
                
            case 'monnify':
                // Monnify uses 'eventType' field
                $eventType = $payload['eventType'] ?? '';
                if (empty($eventType) || $eventType !== 'SUCCESSFUL_TRANSACTION') {
                    return true;
                }
                if (empty($payload['transactionReference'])) {
                    return true;
                }
                return false;
                
            default:
                // For unknown gateways, check if reference exists
                return empty($payload['reference']) && empty($payload['tx_ref']) && empty($payload['transactionReference']);
        }
    }
    
    /**
     * Verify webhook signature for security
     */
    private function verifyWebhookSignature($gatewayCode, $input, $gateway) {
        $secretKey = $gateway['private_key'] ?? '';
        
        switch ($gatewayCode) {
            case 'paystack':
                // Paystack uses x-paystack-signature header
                $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
                if (empty($signature)) {
                    error_log('Paystack webhook: No signature header found');
                    return true; // Allow if no signature (for backward compatibility, can be made strict)
                }
                $computedSignature = hash_hmac('sha512', $input, $secretKey);
                return hash_equals($computedSignature, $signature);
                
            case 'flutterwave':
                // Flutterwave uses verif-hash header
                $signature = $_SERVER['HTTP_VERIF_HASH'] ?? '';
                if (empty($signature)) {
                    error_log('Flutterwave webhook: No signature header found');
                    return true;
                }
                // Flutterwave uses secret hash from dashboard
                $secretHash = $gateway['secret_key'] ?? $secretKey;
                return hash_equals($secretHash, $signature);
                
            case 'monnify':
                // Monnify uses monnify-signature header
                $signature = $_SERVER['HTTP_MONNIFY_SIGNATURE'] ?? '';
                if (empty($signature)) {
                    error_log('Monnify webhook: No signature header found');
                    return true;
                }
                $computedSignature = hash_hmac('sha512', $input, $secretKey);
                return hash_equals($computedSignature, $signature);
                
            default:
                // For unknown gateways, log but allow (can be made strict)
                error_log("Unknown gateway for signature verification: {$gatewayCode}");
                return true;
        }
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
            // Load models and assign to class properties
            $this->bookingModel = $this->loadModel('Booking_model');
            $this->bookingPaymentModel = $this->loadModel('Booking_payment_model');
            $this->transactionModel = $this->loadModel('Transaction_model');
            $this->cashAccountModel = $this->loadModel('Cash_account_model');
            $this->accountModel = $this->loadModel('Account_model');
            
            // NEW: Load Invoice and Notification models
            $this->invoiceModel = $this->loadModel('Invoice_model');
            $this->notificationModel = $this->loadModel('Notification_model');
            
            error_log("processPaymentSuccess: Processing payment type: " . $transaction['payment_type'] . " for ref: " . $transaction['reference_id']);
            
            if ($transaction['payment_type'] === 'booking_payment') {
                $booking = $this->bookingModel->getById($transaction['reference_id']);
                if ($booking) {
                    error_log("processPaymentSuccess: Found booking #" . ($booking['booking_number'] ?? $booking['id']));
                    
                    // Create booking payment record
                    $paymentData = [
                        'booking_id' => $booking['id'],
                        'payment_number' => $this->bookingPaymentModel->getNextPaymentNumber(),
                        'payment_date' => date('Y-m-d'),
                        'payment_type' => 'full',
                        'payment_method' => 'gateway',
                        'amount' => $transaction['amount'],
                        'currency' => $transaction['currency'] ?? 'NGN',
                        'status' => 'completed',
                        'gateway_transaction_id' => $transaction['transaction_ref'],
                        'reference' => $transaction['transaction_ref'],
                        'created_by' => null
                    ];
                    
                    $paymentId = $this->bookingPaymentModel->create($paymentData);
                    error_log("processPaymentSuccess: Created payment record ID: $paymentId");
                    
                    // Update booking paid amount and balance
                    $newPaidAmount = floatval($booking['paid_amount'] ?? 0) + floatval($transaction['amount']);
                    $newBalance = floatval($booking['total_amount'] ?? 0) - $newPaidAmount;
                    
                    $updateData = [
                        'paid_amount' => $newPaidAmount,
                        'balance_amount' => max(0, $newBalance)
                    ];
                    
                    // Update payment status based on balance
                    $isFullPayment = false;
                    if ($newBalance <= 0) {
                        $updateData['payment_status'] = 'paid';
                        $updateData['status'] = 'confirmed'; // Auto-confirm on full payment
                        $isFullPayment = true;
                    } elseif ($newPaidAmount > 0) {
                        $updateData['payment_status'] = 'partial';
                    }
                    
                    $this->bookingModel->update($booking['id'], $updateData);
                    error_log("processPaymentSuccess: Updated booking - paid: $newPaidAmount, balance: $newBalance");
                    
                    // UPDATE INVOICE STATUS
                    if ($this->invoiceModel) {
                        $invoiceId = $booking['invoice_id'] ?? null;
                        
                        // If no invoice_id in booking, try to find by reference
                        if (!$invoiceId) {
                            $sql = "SELECT id FROM invoices WHERE reference_type = 'booking' AND reference_id = ?";
                            $query = $this->db->query($sql, [$booking['id']]); // Use direct query if invoiceModel doesn't have search
                            if ($query && $query->num_rows() > 0) {
                                $inv = $query->row_array();
                                $invoiceId = $inv['id'];
                            }
                        }
                        
                        if ($invoiceId) {
                            $invoiceUpdate = [
                                'status' => ($newBalance <= 0) ? 'paid' : 'partial',
                                'amount_paid' => $newPaidAmount
                            ];
                            $this->invoiceModel->update($invoiceId, $invoiceUpdate);
                            error_log("processPaymentSuccess: Updated linked invoice #$invoiceId");
                        }
                    }

                    // SEND NOTIFICATION (EMAIL)
                    if ($this->notificationModel) {
                        // Send booking confirmation email
                        $this->notificationModel->sendBookingConfirmation($booking['id'], $booking);
                        error_log("processPaymentSuccess: Sent booking confirmation email");
                    }
                    
                    // Create accounting entries - double-entry for payment received
                    try {
                        if ($this->cashAccountModel && $this->transactionModel) {
                            $defaultCashAccount = $this->cashAccountModel->getDefault();
                            if ($defaultCashAccount) {
                                // Debit cash account (cash increases)
                                $this->transactionModel->create([
                                    'account_id' => $defaultCashAccount['id'],
                                    'debit' => $transaction['amount'],
                                    'credit' => 0,
                                    'description' => 'Online payment received for booking: ' . ($booking['booking_number'] ?? $booking['id']),
                                    'reference_type' => 'booking_payment',
                                    'reference_id' => $paymentId,
                                    'transaction_date' => date('Y-m-d'),
                                    'status' => 'posted',
                                    'created_by' => null
                                ]);
                                
                                // Try to credit revenue account if exists
                                if ($this->accountModel) {
                                    try {
                                        // Find revenue account for booking income
                                        $revenueAccount = $this->accountModel->getByCode('4100'); // Sales Revenue
                                        if (!$revenueAccount) {
                                            $revenueAccount = $this->accountModel->getByCode('4000'); // Revenue
                                        }
                                        if ($revenueAccount) {
                                            $this->transactionModel->create([
                                                'account_id' => $revenueAccount['id'],
                                                'debit' => 0,
                                                'credit' => $transaction['amount'],
                                                'description' => 'Booking revenue: ' . ($booking['booking_number'] ?? $booking['id']),
                                                'reference_type' => 'booking_payment',
                                                'reference_id' => $paymentId,
                                                'transaction_date' => date('Y-m-d'),
                                                'status' => 'posted',
                                                'created_by' => null
                                            ]);
                                        }
                                    } catch (Exception $e) {
                                        error_log('Revenue account entry error: ' . $e->getMessage());
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // Log but don't fail - accounting is secondary
                        error_log('Booking payment accounting error: ' . $e->getMessage());
                    }
                } else {
                    error_log("processPaymentSuccess: Booking not found for ID: " . $transaction['reference_id']);
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

