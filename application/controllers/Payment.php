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

        $logFile = ROOTPATH . 'debug_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] PAYMENT INIT: " . print_r($_POST, true) . "\n", FILE_APPEND);
        
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
    $logFile = ROOTPATH . 'payment_callback_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    
    // DIAGNOSTIC: Log everything at entry
    $diagEntry = "\n" . str_repeat('=', 60) . "\n";
    $diagEntry .= "[$timestamp] === PAYMENT CALLBACK ENTERED ===\n";
    $diagEntry .= "  Full URL: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
    $diagEntry .= "  GET params: " . json_encode($_GET) . "\n";
    $diagEntry .= "  HTTP Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n";
    $diagEntry .= "  User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "\n";
    file_put_contents($logFile, $diagEntry, FILE_APPEND);
    
    $transactionRef = $_GET['reference'] ?? $_GET['tx_ref'] ?? $_GET['trxref'] ?? '';
    $gatewayCode = $_GET['gateway'] ?? 'paystack';
    
    file_put_contents($logFile, "[$timestamp] Extracted ref: '$transactionRef', gateway: '$gatewayCode'\n", FILE_APPEND);
    
    if (!$transactionRef) {
        file_put_contents($logFile, "[$timestamp] ERROR: Empty reference! Cannot proceed.\n", FILE_APPEND);
        $this->setFlashMessage('danger', 'Invalid payment reference.');
        redirect('payment/confirmation?status=failed');
        return;
    }
    
    try {
        // DIAGNOSTIC: Check if transaction exists in DB BEFORE verifying
        $existingTxn = $this->paymentTransactionModel->getByTransactionRef($transactionRef);
        file_put_contents($logFile, "[$timestamp] DB lookup for ref '$transactionRef': " . ($existingTxn ? 'FOUND (ID: ' . $existingTxn['id'] . ', status: ' . $existingTxn['status'] . ')' : 'NOT FOUND') . "\n", FILE_APPEND);
        
        // Verify payment
        file_put_contents($logFile, "[$timestamp] Calling verifyPayment()...\n", FILE_APPEND);
        $this->verifyPayment($transactionRef, $gatewayCode);
        file_put_contents($logFile, "[$timestamp] verifyPayment() completed successfully\n", FILE_APPEND);
        
        // Get transaction for confirmation page
        $transaction = $this->paymentTransactionModel->getByTransactionRef($transactionRef);
        $status = $transaction ? $transaction['status'] : 'pending';
        
        file_put_contents($logFile, "[$timestamp] Final transaction status: $status\n", FILE_APPEND);
        
        redirect('payment/confirmation?ref=' . urlencode($transactionRef) . '&status=' . $status);
    } catch (Exception $e) {
        file_put_contents($logFile, "[$timestamp] CALLBACK EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
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
        
        $logFile = ROOTPATH . 'debug_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] WEBHOOK RECEIVED: Gateway=$gatewayCode\n", FILE_APPEND);
        
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
    $logFile = ROOTPATH . 'payment_callback_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    
    try {
        file_put_contents($logFile, "[$timestamp] verifyPayment: Looking up transaction ref '$transactionRef'\n", FILE_APPEND);
        
        $transaction = $this->paymentTransactionModel->getByTransactionRef($transactionRef);
        if (!$transaction) {
            file_put_contents($logFile, "[$timestamp] verifyPayment: TRANSACTION NOT FOUND in payment_transactions table!\n", FILE_APPEND);
            
            // DIAGNOSTIC: Check if table exists and has any records
            try {
                $prefix = $this->db->getPrefix();
                $count = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM {$prefix}payment_transactions");
                file_put_contents($logFile, "[$timestamp] verifyPayment: payment_transactions table has " . ($count['cnt'] ?? '?') . " records\n", FILE_APPEND);
                
                // Show recent transactions for comparison
                $recent = $this->db->fetchAll("SELECT id, transaction_ref, status, created_at FROM {$prefix}payment_transactions ORDER BY id DESC LIMIT 5");
                file_put_contents($logFile, "[$timestamp] verifyPayment: Recent transactions: " . json_encode($recent) . "\n", FILE_APPEND);
            } catch (Exception $diagEx) {
                file_put_contents($logFile, "[$timestamp] verifyPayment: Could not query payment_transactions: " . $diagEx->getMessage() . "\n", FILE_APPEND);
            }
            
            throw new Exception('Transaction not found for ref: ' . $transactionRef);
        }
        
        file_put_contents($logFile, "[$timestamp] verifyPayment: Found transaction ID={$transaction['id']}, type={$transaction['payment_type']}, ref_id={$transaction['reference_id']}, status={$transaction['status']}\n", FILE_APPEND);
        
        if ($transaction['status'] === 'success' && !$fromWebhook) {
            file_put_contents($logFile, "[$timestamp] verifyPayment: Already verified — skipping\n", FILE_APPEND);
            return ['already_verified' => true];
        }
        
        $gateway = $this->gatewayModel->getByCode($gatewayCode);
        if (!$gateway) {
            file_put_contents($logFile, "[$timestamp] verifyPayment: GATEWAY NOT FOUND for code '$gatewayCode'\n", FILE_APPEND);
            throw new Exception('Gateway not found');
        }
        
        file_put_contents($logFile, "[$timestamp] verifyPayment: Gateway found: {$gateway['gateway_name']}, test_mode={$gateway['test_mode']}\n", FILE_APPEND);
        
        require_once BASEPATH . 'libraries/Payment_gateway.php';
        
        $gatewayConfig = [
            'public_key' => $gateway['public_key'],
            'private_key' => $gateway['private_key'],
            'secret_key' => $gateway['secret_key'] ?? '',
            'test_mode' => $gateway['test_mode'],
            'additional_config' => json_decode($gateway['additional_config'] ?? '{}', true)
        ];
        
        $paymentGateway = new Payment_gateway($gatewayCode, $gatewayConfig);
        
        file_put_contents($logFile, "[$timestamp] verifyPayment: Calling Paystack verify API for ref '$transactionRef'...\n", FILE_APPEND);
        $verification = $paymentGateway->verify($transactionRef);
        file_put_contents($logFile, "[$timestamp] verifyPayment: Paystack verify result: " . json_encode($verification) . "\n", FILE_APPEND);
        
        if ($verification['success']) {
            file_put_contents($logFile, "[$timestamp] verifyPayment: Payment VERIFIED SUCCESS. Updating transaction status...\n", FILE_APPEND);
            
            // Update transaction status
            $this->paymentTransactionModel->updateStatus(
                $transaction['id'],
                'success',
                $verification['gateway_reference'] ?? '',
                $verification
            );
            
            file_put_contents($logFile, "[$timestamp] verifyPayment: Transaction status updated. Calling processPaymentSuccess...\n", FILE_APPEND);
            
            // Process payment based on type
            $this->processPaymentSuccess($transaction);
            
            file_put_contents($logFile, "[$timestamp] verifyPayment: processPaymentSuccess completed.\n", FILE_APPEND);
            
            return $verification;
        } else {
            file_put_contents($logFile, "[$timestamp] verifyPayment: Payment VERIFICATION FAILED: " . ($verification['message'] ?? 'no message') . "\n", FILE_APPEND);
            
            // Update as failed
            $this->paymentTransactionModel->updateStatus(
                $transaction['id'],
                'failed',
                null,
                $verification
            );
            
            // Send payment failed email notification
            try {
                if ($transaction['payment_type'] === 'booking_payment') {
                    $notificationModel = $this->loadModel('Notification_model');
                    $bookingModel = $this->loadModel('Booking_model');
                    $booking = $bookingModel->getById($transaction['reference_id']);
                    if ($booking && $notificationModel && !empty($booking['customer_email'])) {
                        $retryUrl = base_url('booking-wizard/retry-payment/' . $booking['id']);
                        $notificationModel->sendPaymentFailedEmail($booking, $retryUrl);
                    }
                }
            } catch (Exception $emailEx) {
                // Non-critical
            }
            
            throw new Exception($verification['message'] ?? 'Payment verification failed');
        }
        
    } catch (Exception $e) {
        file_put_contents($logFile, "[$timestamp] verifyPayment EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
        error_log('Payment verification error: ' . $e->getMessage());
        throw $e;
    }
}
    
    /**
     * Process successful payment
     */
    private function processPaymentSuccess($transaction) {
        $logFile = ROOTPATH . 'payment_callback_debug.log';
        $ts = date('Y-m-d H:i:s');
        
        file_put_contents($logFile, "[$ts] processPaymentSuccess: ENTERED, type={$transaction['payment_type']}, ref_id={$transaction['reference_id']}, amount={$transaction['amount']}\n", FILE_APPEND);
        
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
            
            file_put_contents($logFile, "[$ts] processPaymentSuccess: Models loaded OK\n", FILE_APPEND);
            
            if ($transaction['payment_type'] === 'booking_payment') {
                file_put_contents($logFile, "[$ts] processPaymentSuccess: STEP 1 - Looking up booking ID={$transaction['reference_id']}\n", FILE_APPEND);
                
                $booking = $this->bookingModel->getById($transaction['reference_id']);
                
                if (!$booking) {
                    file_put_contents($logFile, "[$ts] processPaymentSuccess: BOOKING NOT FOUND for ID={$transaction['reference_id']}! Checking bookings table...\n", FILE_APPEND);
                    // Diagnostic: check what's in the bookings table
                    try {
                        $prefix = $this->db->getPrefix();
                        $recent = $this->db->fetchAll("SELECT id, booking_number, status, payment_status FROM {$prefix}bookings ORDER BY id DESC LIMIT 5");
                        file_put_contents($logFile, "[$ts] processPaymentSuccess: Recent bookings: " . json_encode($recent) . "\n", FILE_APPEND);
                    } catch (Exception $diagEx) {
                        file_put_contents($logFile, "[$ts] processPaymentSuccess: Cannot query bookings: " . $diagEx->getMessage() . "\n", FILE_APPEND);
                    }
                    return;
                }
                
                file_put_contents($logFile, "[$ts] processPaymentSuccess: STEP 2 - Booking found: #{$booking['booking_number']}, status={$booking['status']}, payment_status=" . ($booking['payment_status'] ?? 'N/A') . ", paid_amount=" . ($booking['paid_amount'] ?? '0') . ", total=" . ($booking['total_amount'] ?? '0') . "\n", FILE_APPEND);
                
                // IDEMPOTENCY CHECK: Skip if already processed
                if ($booking['status'] === 'confirmed' && !empty($booking['payment_verified_at'])) {
                    file_put_contents($logFile, "[$ts] processPaymentSuccess: SKIPPED - Already confirmed and verified (idempotency)\n", FILE_APPEND);
                    return;
                }
                
                // Create booking payment record
                file_put_contents($logFile, "[$ts] processPaymentSuccess: STEP 3 - Creating booking payment record...\n", FILE_APPEND);
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
                file_put_contents($logFile, "[$ts] processPaymentSuccess: STEP 3 RESULT - Payment record ID: " . var_export($paymentId, true) . "\n", FILE_APPEND);
                
                // Update booking paid amount and balance
                $newPaidAmount = floatval($booking['paid_amount'] ?? 0) + floatval($transaction['amount']);
                $newBalance = floatval($booking['total_amount'] ?? 0) - $newPaidAmount;
                
                // Core update data (columns that always exist)
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
                
                file_put_contents($logFile, "[$ts] processPaymentSuccess: STEP 4 - Updating booking #{$booking['id']}: " . json_encode($updateData) . "\n", FILE_APPEND);
                
                // Perform CORE update first (these columns always exist)
                $updateResult = $this->bookingModel->update($booking['id'], $updateData);
                
                file_put_contents($logFile, "[$ts] processPaymentSuccess: STEP 4 RESULT - Update returned: " . var_export($updateResult, true) . " (rows affected)\n", FILE_APPEND);
                
                // DIAGNOSTIC: Verify the update actually persisted
                $verifyBooking = $this->bookingModel->getById($booking['id']);
                file_put_contents($logFile, "[$ts] processPaymentSuccess: STEP 4 VERIFY - After update: status=" . ($verifyBooking['status'] ?? 'NULL') . ", payment_status=" . ($verifyBooking['payment_status'] ?? 'NULL') . ", paid_amount=" . ($verifyBooking['paid_amount'] ?? 'NULL') . ", balance=" . ($verifyBooking['balance_amount'] ?? 'NULL') . "\n", FILE_APPEND);
                
                error_log("processPaymentSuccess: Core booking update - paid: $newPaidAmount, balance: $newBalance, result: " . ($updateResult ? 'OK' : 'FAIL'));
                
                // Try to set optional tracking columns (may not exist if migration not run)
                // These run as a SEPARATE update so they don't block the core update
                try {
                    $optionalData = ['payment_verified_at' => date('Y-m-d H:i:s')];
                    if ($isFullPayment) {
                        $optionalData['confirmed_at'] = date('Y-m-d H:i:s');
                    }
                    $this->bookingModel->update($booking['id'], $optionalData);
                    file_put_contents($logFile, "[$ts] processPaymentSuccess: Optional columns updated OK\n", FILE_APPEND);
                } catch (Exception $ex) {
                    file_put_contents($logFile, "[$ts] processPaymentSuccess: Optional columns skipped: " . $ex->getMessage() . "\n", FILE_APPEND);
                    // Columns may not exist yet - that's OK, core update already succeeded
                    error_log("processPaymentSuccess: Optional columns update skipped: " . $ex->getMessage());
                }
                
                // BLOCK TIME SLOTS on payment confirmation
                    if ($isFullPayment) {
                        try {
                            // Create time slots if not already done
                            $this->bookingModel->createSlots(
                                $booking['id'],
                                $booking['space_id'] ?? $booking['facility_id'],
                                $booking['booking_date'],
                                $booking['start_time'],
                                $booking['booking_date'], // endDate
                                $booking['end_time']
                            );
                            error_log("processPaymentSuccess: Time slots created/confirmed for booking #" . $booking['id']);
                        } catch (Exception $e) {
                            error_log("processPaymentSuccess: Error creating time slots: " . $e->getMessage());
                            // Don't fail the payment processing - slots are optional
                        }
                    }
                    
                    // UPDATE INVOICE STATUS
                    if ($this->invoiceModel) {
                        $invoiceId = $booking['invoice_id'] ?? null;
                        
                        // If no invoice_id in booking, try to find by reference
                        if (!$invoiceId) {
                            // Try multiple lookup methods since invoice creation format may vary
                            $prefix = $this->db->getPrefix();
                            
                            // Method 1: Look for 'BKG-{booking_id}' in reference field
                            $sql = "SELECT id FROM {$prefix}invoices WHERE reference = ? LIMIT 1";
                            $inv = $this->db->fetchOne($sql, ['BKG-' . $booking['id']]);
                            if ($inv) {
                                $invoiceId = $inv['id'];
                            }
                            
                            // Method 2: Look in notes for booking number
                            if (!$invoiceId && !empty($booking['booking_number'])) {
                                $sql = "SELECT id FROM {$prefix}invoices WHERE notes LIKE ? LIMIT 1";
                                $inv = $this->db->fetchOne($sql, ['%' . $booking['booking_number'] . '%']);
                                if ($inv) {
                                    $invoiceId = $inv['id'];
                                }
                            }
                            
                            // Method 3: Try reference_type/reference_id if columns exist
                            if (!$invoiceId) {
                                try {
                                    $sql = "SELECT id FROM {$prefix}invoices WHERE reference_type = 'booking' AND reference_id = ? LIMIT 1";
                                    $inv = $this->db->fetchOne($sql, [$booking['id']]);
                                    if ($inv) {
                                        $invoiceId = $inv['id'];
                                    }
                                } catch (Exception $e) {
                                    // Columns don't exist, ignore
                                }
                            }
                        }
                        
                        if ($invoiceId) {
                            // Get current invoice data
                            $invoice = $this->invoiceModel->getById($invoiceId);
                            if ($invoice) {
                                $invoicePaidAmount = floatval($invoice['paid_amount'] ?? 0) + floatval($transaction['amount']);
                                $invoiceBalance = floatval($invoice['total_amount'] ?? 0) - $invoicePaidAmount;
                                
                                $invoiceUpdate = [
                                    'status' => ($invoiceBalance <= 0) ? 'paid' : 'partially_paid',
                                    'paid_amount' => $invoicePaidAmount,
                                    'balance_amount' => max(0, $invoiceBalance),
                                    'payment_date' => date('Y-m-d'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                ];
                                $this->invoiceModel->update($invoiceId, $invoiceUpdate);
                                error_log("processPaymentSuccess: Updated linked invoice #$invoiceId - paid: $invoicePaidAmount, balance: $invoiceBalance");
                                
                                // Update customer balance
                                if ($invoice['customer_id']) {
                                    try {
                                        $customerModel = $this->loadModel('Customer_model');
                                        if ($customerModel) {
                                            // Calculate total outstanding for this customer
                                            $outstandingResult = $this->db->fetchOne(
                                                "SELECT COALESCE(SUM(balance_amount), 0) as total_balance 
                                                 FROM `" . $this->db->getPrefix() . "invoices` 
                                                 WHERE customer_id = ? 
                                                 AND status IN ('sent', 'partially_paid', 'overdue', 'draft')",
                                                [$invoice['customer_id']]
                                            );
                                            $customerBalance = $outstandingResult ? floatval($outstandingResult['total_balance']) : 0;
                                            $customerModel->update($invoice['customer_id'], [
                                                'current_balance' => $customerBalance,
                                                'updated_at' => date('Y-m-d H:i:s')
                                            ]);
                                            error_log("processPaymentSuccess: Updated customer #" . $invoice['customer_id'] . " balance to: $customerBalance");
                                        }
                                    } catch (Exception $custEx) {
                                        error_log("processPaymentSuccess: Customer balance update error: " . $custEx->getMessage());
                                    }
                                }
                            }
                        } else {
                            // NO INVOICE EXISTS - Create one now
                            // This handles the case where the booking wizard's invoice creation failed
                            try {
                                $invoiceData = [
                                    'invoice_number' => 'INV-BKG-' . ($booking['booking_number'] ?? $booking['id']),
                                    'reference' => 'BKG-' . $booking['id'],
                                    'customer_name' => $booking['customer_name'] ?? '',
                                    'customer_email' => $booking['customer_email'] ?? '',
                                    'issue_date' => date('Y-m-d'),
                                    'due_date' => date('Y-m-d'),
                                    'subtotal' => $booking['total_amount'] ?? $transaction['amount'],
                                    'tax_amount' => $booking['tax_amount'] ?? 0,
                                    'total_amount' => $booking['total_amount'] ?? $transaction['amount'],
                                    'paid_amount' => $transaction['amount'],
                                    'balance_amount' => max(0, floatval($booking['total_amount'] ?? $transaction['amount']) - floatval($transaction['amount'])),
                                    'status' => ($newBalance <= 0) ? 'paid' : 'partially_paid',
                                    'payment_date' => date('Y-m-d'),
                                    'notes' => 'Auto-generated invoice for booking ' . ($booking['booking_number'] ?? $booking['id']),
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                ];
                                
                                // Try to add reference_type/reference_id columns
                                try {
                                    $invoiceData['reference_type'] = 'booking';
                                    $invoiceData['reference_id'] = $booking['id'];
                                } catch (Exception $e) {}
                                
                                $newInvoiceId = $this->invoiceModel->create($invoiceData);
                                if ($newInvoiceId) {
                                    error_log("processPaymentSuccess: Created new invoice #$newInvoiceId for booking " . ($booking['booking_number'] ?? $booking['id']));
                                    
                                    // Try to link invoice to booking
                                    try {
                                        $this->bookingModel->update($booking['id'], ['invoice_id' => $newInvoiceId]);
                                    } catch (Exception $e) {
                                        // invoice_id column may not exist
                                    }
                                }
                            } catch (Exception $invEx) {
                                error_log("processPaymentSuccess: Failed to create invoice: " . $invEx->getMessage());
                            }
                        }
                    }

                    // SEND NOTIFICATION (EMAIL) - use UPDATED booking data
                    if ($this->notificationModel && $isFullPayment) {
                        try {
                            // Refresh booking data to get updated status
                            $updatedBooking = $this->bookingModel->getWithFacility($booking['id']);
                            if ($updatedBooking) {
                                $this->notificationModel->sendBookingConfirmation($booking['id'], $updatedBooking);
                                error_log("processPaymentSuccess: Sent booking confirmation email to " . ($updatedBooking['customer_email'] ?? 'unknown'));
                            } else {
                                error_log("processPaymentSuccess: Could not refresh booking for email notification");
                            }
                        } catch (Exception $emailEx) {
                            error_log("processPaymentSuccess: Email notification error: " . $emailEx->getMessage());
                        }
                    }
                    
                    // Create accounting entries - double-entry for payment received
                    // Proper flow: Debit Cash, Credit Accounts Receivable (clearing the receivable)
                    // Note: Revenue was already credited when invoice was created
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
                                
                                // Credit Accounts Receivable (clearing the customer's debt)
                                if ($this->accountModel) {
                                    try {
                                        // Find Accounts Receivable account (code 1100, fallback to 1200)
                                        $arAccount = $this->accountModel->getByCode('1100');
                                        if (!$arAccount) {
                                            $arAccount = $this->accountModel->getByCode('1200');
                                        }
                                        if (!$arAccount) {
                                            // Try to find any AR account by name
                                            $assetAccounts = $this->accountModel->getByType('Asset');
                                            if (is_array($assetAccounts)) {
                                                foreach ($assetAccounts as $acc) {
                                                    if (stripos($acc['account_name'], 'receivable') !== false) {
                                                        $arAccount = $acc;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                        
                                        if ($arAccount) {
                                            $this->transactionModel->create([
                                                'account_id' => $arAccount['id'],
                                                'debit' => 0,
                                                'credit' => $transaction['amount'],
                                                'description' => 'Payment received - booking: ' . ($booking['booking_number'] ?? $booking['id']),
                                                'reference_type' => 'booking_payment',
                                                'reference_id' => $paymentId,
                                                'transaction_date' => date('Y-m-d'),
                                                'status' => 'posted',
                                                'created_by' => null
                                            ]);
                                            error_log("processPaymentSuccess: Created accounting entries - Cash DR, AR CR");
                                        } else {
                                            // Fallback: Credit Revenue if no AR account (simpler single-entry)
                                            $revenueAccount = $this->accountModel->getByCode('4100') ?? $this->accountModel->getByCode('4000');
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
                                        }
                                    } catch (Exception $e) {
                                        error_log('AR/Revenue account entry error: ' . $e->getMessage());
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // Log but don't fail - accounting is secondary
                        error_log('Booking payment accounting error: ' . $e->getMessage());
                    }
            } elseif ($transaction['payment_type'] === 'invoice_payment') {
                // ===== INVOICE PAYMENT via gateway =====
                $invoiceModel = $this->loadModel('Invoice_model');
                $invoice = $invoiceModel->getById($transaction['reference_id']);
                
                if ($invoice) {
                    error_log("processPaymentSuccess: Processing invoice payment for invoice #" . ($invoice['invoice_number'] ?? $invoice['id']));
                    
                    // Update invoice paid amount and balance
                    $invoicePaidAmount = floatval($invoice['paid_amount'] ?? 0) + floatval($transaction['amount']);
                    $invoiceBalance = floatval($invoice['total_amount'] ?? 0) - $invoicePaidAmount;
                    
                    $invoiceUpdate = [
                        'status' => ($invoiceBalance <= 0) ? 'paid' : 'partially_paid',
                        'paid_amount' => $invoicePaidAmount,
                        'balance_amount' => max(0, $invoiceBalance),
                        'payment_date' => date('Y-m-d'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $invoiceModel->update($transaction['reference_id'], $invoiceUpdate);
                    
                    // Update customer balance
                    if (!empty($invoice['customer_id'])) {
                        try {
                            $customerModel = $this->loadModel('Customer_model');
                            if ($customerModel) {
                                $outstandingResult = $this->db->fetchOne(
                                    "SELECT COALESCE(SUM(balance_amount), 0) as total_balance 
                                     FROM `" . $this->db->getPrefix() . "invoices` 
                                     WHERE customer_id = ? 
                                     AND status IN ('sent', 'partially_paid', 'overdue', 'draft')",
                                    [$invoice['customer_id']]
                                );
                                $customerBalance = $outstandingResult ? floatval($outstandingResult['total_balance']) : 0;
                                $customerModel->update($invoice['customer_id'], [
                                    'current_balance' => $customerBalance,
                                    'updated_at' => date('Y-m-d H:i:s')
                                ]);
                            }
                        } catch (Exception $custEx) {
                            error_log("processPaymentSuccess: Customer balance update error: " . $custEx->getMessage());
                        }
                    }
                    
                    // Create accounting entries: Dr Cash, Cr Accounts Receivable
                    try {
                        if ($this->cashAccountModel && $this->transactionModel) {
                            $defaultCashAccount = $this->cashAccountModel->getDefault();
                            $arAccount = $this->accountModel ? $this->accountModel->getByCode('1200') : null;
                            
                            if ($defaultCashAccount) {
                                // Debit Cash
                                $this->transactionModel->create([
                                    'account_id' => $defaultCashAccount['account_id'] ?? $defaultCashAccount['id'],
                                    'debit' => $transaction['amount'],
                                    'credit' => 0,
                                    'description' => 'Gateway payment for Invoice ' . ($invoice['invoice_number'] ?? $invoice['id']),
                                    'reference_type' => 'invoice_payment',
                                    'reference_id' => $transaction['reference_id'],
                                    'transaction_date' => date('Y-m-d'),
                                    'status' => 'posted',
                                    'created_by' => null
                                ]);
                                
                                $this->cashAccountModel->updateBalance($defaultCashAccount['id'], $transaction['amount'], 'deposit');
                                
                                // Credit AR
                                if ($arAccount) {
                                    $this->transactionModel->create([
                                        'account_id' => $arAccount['id'],
                                        'debit' => 0,
                                        'credit' => $transaction['amount'],
                                        'description' => 'Payment received - Invoice ' . ($invoice['invoice_number'] ?? $invoice['id']),
                                        'reference_type' => 'invoice_payment',
                                        'reference_id' => $transaction['reference_id'],
                                        'transaction_date' => date('Y-m-d'),
                                        'status' => 'posted',
                                        'created_by' => null
                                    ]);
                                }
                                error_log("processPaymentSuccess: Created accounting entries for invoice payment");
                            }
                        }
                    } catch (Exception $e) {
                        error_log('Invoice payment accounting error: ' . $e->getMessage());
                    }
                } else {
                    error_log("processPaymentSuccess: Invoice not found for ID: " . $transaction['reference_id']);
                }
            } elseif ($transaction['payment_type'] === 'rent_payment') {
                // ===== RENT PAYMENT via gateway =====
                $rentInvoiceModel = $this->loadModel('Rent_invoice_model');
                $rentInvoice = $rentInvoiceModel ? $rentInvoiceModel->getById($transaction['reference_id']) : null;
                
                if ($rentInvoice) {
                    error_log("processPaymentSuccess: Processing rent payment for rent invoice #" . ($rentInvoice['invoice_number'] ?? $rentInvoice['id']));
                    
                    // Update rent invoice paid amount and balance
                    $rentPaidAmount = floatval($rentInvoice['paid_amount'] ?? 0) + floatval($transaction['amount']);
                    $rentBalance = floatval($rentInvoice['total_amount'] ?? $rentInvoice['amount'] ?? 0) - $rentPaidAmount;
                    
                    $rentUpdate = [
                        'status' => ($rentBalance <= 0) ? 'paid' : 'partially_paid',
                        'paid_amount' => $rentPaidAmount,
                        'balance_amount' => max(0, $rentBalance),
                        'payment_date' => date('Y-m-d'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $rentInvoiceModel->update($transaction['reference_id'], $rentUpdate);
                    
                    // Create accounting entries: Dr Cash, Cr Rent Revenue
                    try {
                        if ($this->cashAccountModel && $this->transactionModel) {
                            $defaultCashAccount = $this->cashAccountModel->getDefault();
                            $rentRevenueAccount = $this->accountModel ? $this->accountModel->getByCode('4200') : null; // Rent Revenue
                            if (!$rentRevenueAccount && $this->accountModel) {
                                $rentRevenueAccount = $this->accountModel->getByCode('4100'); // General service revenue fallback
                            }
                            
                            if ($defaultCashAccount) {
                                // Debit Cash
                                $this->transactionModel->create([
                                    'account_id' => $defaultCashAccount['account_id'] ?? $defaultCashAccount['id'],
                                    'debit' => $transaction['amount'],
                                    'credit' => 0,
                                    'description' => 'Gateway payment for Rent Invoice ' . ($rentInvoice['invoice_number'] ?? $rentInvoice['id']),
                                    'reference_type' => 'rent_payment',
                                    'reference_id' => $transaction['reference_id'],
                                    'transaction_date' => date('Y-m-d'),
                                    'status' => 'posted',
                                    'created_by' => null
                                ]);
                                
                                $this->cashAccountModel->updateBalance($defaultCashAccount['id'], $transaction['amount'], 'deposit');
                                
                                // Credit Rent Revenue
                                if ($rentRevenueAccount) {
                                    $this->transactionModel->create([
                                        'account_id' => $rentRevenueAccount['id'],
                                        'debit' => 0,
                                        'credit' => $transaction['amount'],
                                        'description' => 'Rent payment received - Invoice ' . ($rentInvoice['invoice_number'] ?? $rentInvoice['id']),
                                        'reference_type' => 'rent_payment',
                                        'reference_id' => $transaction['reference_id'],
                                        'transaction_date' => date('Y-m-d'),
                                        'status' => 'posted',
                                        'created_by' => null
                                    ]);
                                }
                                error_log("processPaymentSuccess: Created accounting entries for rent payment");
                            }
                        }
                    } catch (Exception $e) {
                        error_log('Rent payment accounting error: ' . $e->getMessage());
                    }
                } else {
                    error_log("processPaymentSuccess: Rent invoice not found for ID: " . $transaction['reference_id']);
                }
            }
        } catch (Exception $e) {
            $logFile = ROOTPATH . 'payment_callback_debug.log';
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] processPaymentSuccess OUTER CATCH: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
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

