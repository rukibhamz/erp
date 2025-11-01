<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bookings extends Base_Controller {
    private $bookingModel;
    private $facilityModel;
    private $paymentModel;
    private $transactionModel;
    private $cashAccountModel;
    private $accountModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('bookings', 'read');
        $this->bookingModel = $this->loadModel('Booking_model');
        $this->facilityModel = $this->loadModel('Facility_model');
        $this->paymentModel = $this->loadModel('Booking_payment_model');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->cashAccountModel = $this->loadModel('Cash_account_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        $status = $_GET['status'] ?? 'all';
        $date = $_GET['date'] ?? date('Y-m-d');
        
        try {
            if ($status === 'all') {
                $bookings = $this->bookingModel->getByDateRange(date('Y-m-01'), date('Y-m-t'));
            } else {
                $bookings = $this->bookingModel->getByStatus($status);
            }
        } catch (Exception $e) {
            $bookings = [];
        }

        $data = [
            'page_title' => 'Bookings',
            'bookings' => $bookings,
            'selected_status' => $status,
            'selected_date' => $date,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('bookings/index', $data);
    }

    public function calendar() {
        $facilityId = $_GET['facility_id'] ?? null;
        $month = $_GET['month'] ?? date('Y-m');
        
        try {
            $startDate = date('Y-m-01', strtotime($month));
            $endDate = date('Y-m-t', strtotime($month));
            
            $bookings = $this->bookingModel->getByDateRange($startDate, $endDate, $facilityId);
            $facilities = $this->facilityModel->getActive();
        } catch (Exception $e) {
            $bookings = [];
            $facilities = [];
        }

        $data = [
            'page_title' => 'Booking Calendar',
            'bookings' => $bookings,
            'facilities' => $facilities,
            'selected_facility_id' => $facilityId,
            'selected_month' => $month,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('bookings/calendar', $data);
    }

    public function create() {
        $this->requirePermission('bookings', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $facilityId = intval($_POST['facility_id'] ?? 0);
            $bookingDate = sanitize_input($_POST['booking_date'] ?? '');
            $startTime = sanitize_input($_POST['start_time'] ?? '');
            $endTime = sanitize_input($_POST['end_time'] ?? '');
            $bookingType = sanitize_input($_POST['booking_type'] ?? 'hourly');

            // Check availability
            if (!$this->facilityModel->checkAvailability($facilityId, $bookingDate, $startTime, $endTime)) {
                $this->setFlashMessage('danger', 'The selected time slot is not available. Please choose another time.');
                redirect('bookings/create');
            }

            $facility = $this->facilityModel->getById($facilityId);
            if (!$facility) {
                $this->setFlashMessage('danger', 'Facility not found.');
                redirect('bookings/create');
            }

            // Calculate price
            $baseAmount = $this->facilityModel->calculatePrice($facilityId, $bookingDate, $startTime, $endTime, $bookingType);
            
            // Calculate duration
            $start = new DateTime($bookingDate . ' ' . $startTime);
            $end = new DateTime($bookingDate . ' ' . $endTime);
            $duration = $end->diff($start);
            $hours = $duration->h + ($duration->i / 60);

            $data = [
                'booking_number' => $this->bookingModel->getNextBookingNumber(),
                'facility_id' => $facilityId,
                'customer_name' => sanitize_input($_POST['customer_name'] ?? ''),
                'customer_email' => sanitize_input($_POST['customer_email'] ?? ''),
                'customer_phone' => sanitize_input($_POST['customer_phone'] ?? ''),
                'customer_address' => sanitize_input($_POST['customer_address'] ?? ''),
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_hours' => $hours,
                'number_of_guests' => intval($_POST['number_of_guests'] ?? 0),
                'booking_type' => $bookingType,
                'base_amount' => $baseAmount,
                'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
                'tax_amount' => floatval($_POST['tax_amount'] ?? 0),
                'security_deposit' => floatval($facility['security_deposit'] ?? 0),
                'total_amount' => $baseAmount + floatval($_POST['tax_amount'] ?? 0) - floatval($_POST['discount_amount'] ?? 0),
                'paid_amount' => 0,
                'balance_amount' => $baseAmount + floatval($_POST['tax_amount'] ?? 0) - floatval($_POST['discount_amount'] ?? 0),
                'currency' => 'NGN',
                'status' => sanitize_input($_POST['status'] ?? 'pending'),
                'payment_status' => 'unpaid',
                'booking_notes' => sanitize_input($_POST['booking_notes'] ?? ''),
                'special_requests' => sanitize_input($_POST['special_requests'] ?? ''),
                'created_by' => $this->session['user_id']
            ];

            try {
                $pdo = $this->db->getConnection();
                $pdo->beginTransaction();

                $bookingId = $this->bookingModel->create($data);
                if (!$bookingId) {
                    throw new Exception('Failed to create booking');
                }

                // Create booking slots
                $this->bookingModel->createSlots($bookingId, $facilityId, $bookingDate, $startTime, $endTime);

                // If confirmed, recognize revenue (or create unearned revenue entry)
                if ($data['status'] === 'confirmed') {
                    $this->recognizeBookingRevenue($bookingId, $data);
                }

                // If confirmed and payment received, create payment record
                if ($data['status'] === 'confirmed' && !empty($_POST['initial_payment'])) {
                    $paymentAmount = floatval($_POST['initial_payment']);
                    $this->processPayment($bookingId, $paymentAmount, $_POST['payment_method'] ?? 'cash', 'deposit');
                }

                $pdo->commit();
                $this->activityModel->log($this->session['user_id'], 'create', 'Bookings', 'Created booking: ' . $data['booking_number']);
                $this->setFlashMessage('success', 'Booking created successfully.');
                redirect('bookings/view/' . $bookingId);
            } catch (Exception $e) {
                if (isset($pdo)) {
                    $pdo->rollBack();
                }
                error_log('Bookings create error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to create booking: ' . $e->getMessage());
            }
        }

        try {
            $facilities = $this->facilityModel->getActive();
        } catch (Exception $e) {
            $facilities = [];
        }

        $data = [
            'page_title' => 'Create Booking',
            'facilities' => $facilities,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('bookings/create', $data);
    }

    public function view($id) {
        try {
            $booking = $this->bookingModel->getWithFacility($id);
            if (!$booking) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('bookings');
            }

            $payments = $this->paymentModel->getByBooking($id);
        } catch (Exception $e) {
            $booking = null;
            $payments = [];
        }

        $data = [
            'page_title' => 'Booking: ' . ($booking['booking_number'] ?? ''),
            'booking' => $booking,
            'payments' => $payments,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('bookings/view', $data);
    }

    public function recordPayment() {
        $this->requirePermission('bookings', 'update');

        $bookingId = intval($_POST['booking_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'cash');
        $paymentType = sanitize_input($_POST['payment_type'] ?? 'partial');

        if (!$bookingId || $amount <= 0) {
            $this->setFlashMessage('danger', 'Invalid payment details.');
            redirect('bookings');
        }

        try {
            $this->processPayment($bookingId, $amount, $paymentMethod, $paymentType);
            $this->setFlashMessage('success', 'Payment recorded successfully.');
            redirect('bookings/view/' . $bookingId);
        } catch (Exception $e) {
            error_log('Bookings recordPayment error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Failed to record payment: ' . $e->getMessage());
            redirect('bookings/view/' . $bookingId);
        }
    }

    private function processPayment($bookingId, $amount, $paymentMethod, $paymentType) {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            // Get booking details
            $booking = $this->bookingModel->getById($bookingId);
            if (!$booking) {
                throw new Exception('Booking not found');
            }

            // Create payment record
            $paymentData = [
                'booking_id' => $bookingId,
                'payment_number' => $this->paymentModel->getNextPaymentNumber(),
                'payment_date' => date('Y-m-d'),
                'payment_type' => $paymentType,
                'payment_method' => $paymentMethod,
                'amount' => $amount,
                'currency' => 'NGN',
                'status' => 'completed',
                'created_by' => $this->session['user_id']
            ];

            $paymentId = $this->paymentModel->create($paymentData);
            if (!$paymentId) {
                throw new Exception('Failed to create payment record');
            }

            // Update booking payment
            $this->bookingModel->addPayment($bookingId, $amount);

            // Get default accounts
            $bookingRevenueAccount = $this->accountModel->getDefaultAccount('booking_revenue');
            $cashAccountId = !empty($_POST['cash_account_id']) ? intval($_POST['cash_account_id']) : null;
            $cashAccount = $cashAccountId ? $this->cashAccountModel->getById($cashAccountId) : null;

            // Create accounting entries using double-entry bookkeeping
            if ($cashAccount) {
                $cashAccountId_gl = $cashAccount['account_id'];
                
                // Entry 1: Debit Cash Account (Asset increases)
                $this->transactionModel->create([
                    'transaction_number' => $paymentData['payment_number'] . '-CASH',
                    'transaction_date' => date('Y-m-d'),
                    'transaction_type' => 'receipt',
                    'reference_id' => $paymentId,
                    'reference_type' => 'booking_payment',
                    'account_id' => $cashAccountId_gl,
                    'description' => 'Booking payment received - ' . $booking['booking_number'],
                    'debit' => $amount,
                    'credit' => 0,
                    'status' => 'posted',
                    'created_by' => $this->session['user_id']
                ]);
                $this->accountModel->updateBalance($cashAccountId_gl, $amount, 'debit');
                $this->cashAccountModel->updateBalance($cashAccountId, $amount, 'deposit');

                // Entry 2: Credit Booking Revenue Account (Revenue increases)
                // Check if revenue was already recognized when booking was confirmed
                // If booking is already confirmed, credit revenue directly
                // Otherwise, we could use Unearned Revenue (liability) account
                if ($booking['status'] === 'confirmed' || $booking['status'] === 'completed') {
                    $revenueAccountId = $bookingRevenueAccount ? $bookingRevenueAccount['id'] : null;
                    
                    if ($revenueAccountId) {
                        $this->transactionModel->create([
                            'transaction_number' => $paymentData['payment_number'] . '-REV',
                            'transaction_date' => date('Y-m-d'),
                            'transaction_type' => 'revenue',
                            'reference_id' => $paymentId,
                            'reference_type' => 'booking_payment',
                            'account_id' => $revenueAccountId,
                            'description' => 'Booking revenue - ' . $booking['booking_number'],
                            'debit' => 0,
                            'credit' => $amount,
                            'status' => 'posted',
                            'created_by' => $this->session['user_id']
                        ]);
                        $this->accountModel->updateBalance($revenueAccountId, $amount, 'credit');
                    }
                } else {
                    // For pending bookings, use Unearned Revenue (if exists)
                    $unearnedRevenueAccount = $this->accountModel->getDefaultAccount('unearned_revenue');
                    $unearnedAccountId = $unearnedRevenueAccount ? $unearnedRevenueAccount['id'] : 
                                         ($bookingRevenueAccount ? $bookingRevenueAccount['id'] : null);
                    
                    if ($unearnedAccountId) {
                        $this->transactionModel->create([
                            'transaction_number' => $paymentData['payment_number'] . '-UNREV',
                            'transaction_date' => date('Y-m-d'),
                            'transaction_type' => 'receipt',
                            'reference_id' => $paymentId,
                            'reference_type' => 'booking_payment',
                            'account_id' => $unearnedAccountId,
                            'description' => 'Unearned booking revenue - ' . $booking['booking_number'],
                            'debit' => 0,
                            'credit' => $amount,
                            'status' => 'posted',
                            'created_by' => $this->session['user_id']
                        ]);
                        $this->accountModel->updateBalance($unearnedAccountId, $amount, 'credit');
                    }
                }
            }

            $pdo->commit();
            $this->activityModel->log($this->session['user_id'], 'create', 'Bookings', 'Recorded payment for booking: ' . $booking['booking_number']);
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updateStatus($id) {
        $this->requirePermission('bookings', 'update');

        $status = sanitize_input($_POST['status'] ?? '');
        $oldStatus = '';
        
        try {
            $booking = $this->bookingModel->getById($id);
            if ($booking) {
                $oldStatus = $booking['status'];
                
                // If status changed to confirmed, recognize revenue
                if ($status === 'confirmed' && $oldStatus !== 'confirmed') {
                    $this->recognizeBookingRevenue($id, $booking);
                }
                
                // If status changed to completed, finalize revenue recognition
                if ($status === 'completed' && $oldStatus !== 'completed') {
                    $this->finalizeBookingRevenue($id, $booking);
                }
                
                // If status changed to cancelled, reverse revenue entries
                if ($status === 'cancelled' && $oldStatus !== 'cancelled') {
                    $this->reverseBookingRevenue($id, $booking);
                }
            }
        } catch (Exception $e) {
            error_log('Bookings updateStatus error: ' . $e->getMessage());
        }
        
        if ($this->bookingModel->updateStatus($id, $status)) {
            $this->activityModel->log($this->session['user_id'], 'update', 'Bookings', 'Updated booking status to: ' . $status);
            $this->setFlashMessage('success', 'Booking status updated successfully.');
        } else {
            $this->setFlashMessage('danger', 'Failed to update booking status.');
        }

        redirect('bookings/view/' . $id);
    }

    /**
     * Recognize booking revenue when booking is confirmed
     */
    private function recognizeBookingRevenue($bookingId, $booking) {
        try {
            $bookingRevenueAccount = $this->accountModel->getDefaultAccount('booking_revenue');
            $unearnedRevenueAccount = $this->accountModel->getDefaultAccount('unearned_revenue');
            
            if (!$bookingRevenueAccount) {
                // Try to find any revenue account if default not set
                $revenueAccounts = $this->accountModel->getByType('Income');
                $bookingRevenueAccount = !empty($revenueAccounts) ? $revenueAccounts[0] : null;
            }
            
            if (!$bookingRevenueAccount) {
                error_log('No booking revenue account found. Please configure default accounts.');
                return;
            }
            
            $totalAmount = floatval($booking['total_amount'] ?? 0);
            if ($totalAmount <= 0) {
                return;
            }
            
            // Check if revenue entry already exists
            $existingEntries = $this->transactionModel->getByReference('booking_revenue', $bookingId);
            if (!empty($existingEntries)) {
                return; // Already recognized
            }
            
            // If unearned revenue account exists and booking was prepaid, recognize it
            // Otherwise, create AR entry if not fully paid
            $paidAmount = floatval($booking['paid_amount'] ?? 0);
            $balanceAmount = $totalAmount - $paidAmount;
            
            if ($balanceAmount > 0) {
                // Create Accounts Receivable entry for unpaid portion
                $arAccount = $this->accountModel->getDefaultAccount('accounts_receivable');
                if ($arAccount) {
                    $this->transactionModel->create([
                        'transaction_number' => $booking['booking_number'] . '-AR',
                        'transaction_date' => $booking['booking_date'],
                        'transaction_type' => 'invoice',
                        'reference_id' => $bookingId,
                        'reference_type' => 'booking_revenue',
                        'account_id' => $arAccount['id'],
                        'description' => 'Booking receivable - ' . $booking['booking_number'],
                        'debit' => $balanceAmount,
                        'credit' => 0,
                        'status' => 'posted',
                        'created_by' => $this->session['user_id'] ?? null
                    ]);
                    $this->accountModel->updateBalance($arAccount['id'], $balanceAmount, 'debit');
                }
            }
            
            // Credit Revenue Account (or recognize from Unearned Revenue if prepaid)
            if ($paidAmount > 0 && $unearnedRevenueAccount) {
                // Transfer from Unearned Revenue to Revenue
                // Debit Unearned Revenue
                $this->transactionModel->create([
                    'transaction_number' => $booking['booking_number'] . '-UNREV-D',
                    'transaction_date' => $booking['booking_date'],
                    'transaction_type' => 'adjustment',
                    'reference_id' => $bookingId,
                    'reference_type' => 'booking_revenue',
                    'account_id' => $unearnedRevenueAccount['id'],
                    'description' => 'Recognize revenue from unearned - ' . $booking['booking_number'],
                    'debit' => $paidAmount,
                    'credit' => 0,
                    'status' => 'posted',
                    'created_by' => $this->session['user_id'] ?? null
                ]);
                $this->accountModel->updateBalance($unearnedRevenueAccount['id'], $paidAmount, 'debit');
            }
            
            // Credit Revenue Account
            $this->transactionModel->create([
                'transaction_number' => $booking['booking_number'] . '-REV',
                'transaction_date' => $booking['booking_date'],
                'transaction_type' => 'revenue',
                'reference_id' => $bookingId,
                'reference_type' => 'booking_revenue',
                'account_id' => $bookingRevenueAccount['id'],
                'description' => 'Booking revenue - ' . $booking['booking_number'],
                'debit' => 0,
                'credit' => $totalAmount,
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($bookingRevenueAccount['id'], $totalAmount, 'credit');
            
        } catch (Exception $e) {
            error_log('Bookings recognizeBookingRevenue error: ' . $e->getMessage());
        }
    }

    /**
     * Finalize booking revenue when booking is completed
     */
    private function finalizeBookingRevenue($bookingId, $booking) {
        // Revenue should already be recognized when confirmed
        // This method can handle any final adjustments if needed
        // For now, just log the completion
    }

    /**
     * Reverse booking revenue when booking is cancelled
     */
    private function reverseBookingRevenue($bookingId, $booking) {
        try {
            // Find all transactions related to this booking
            $transactions = $this->transactionModel->getByReference('booking_revenue', $bookingId);
            $transactions = array_merge($transactions, $this->transactionModel->getByReference('booking_payment', $bookingId));
            
            foreach ($transactions as $trans) {
                // Create reversing entries
                $reverseData = [
                    'transaction_number' => $trans['transaction_number'] . '-REV',
                    'transaction_date' => date('Y-m-d'),
                    'transaction_type' => 'reversal',
                    'reference_id' => $bookingId,
                    'reference_type' => 'booking_cancellation',
                    'account_id' => $trans['account_id'],
                    'description' => 'Reversal: ' . $trans['description'],
                    'debit' => $trans['credit'], // Reverse the credit
                    'credit' => $trans['debit'], // Reverse the debit
                    'status' => 'posted',
                    'created_by' => $this->session['user_id'] ?? null
                ];
                
                $this->transactionModel->create($reverseData);
                
                // Update account balance
                if ($trans['debit'] > 0) {
                    $this->accountModel->updateBalance($trans['account_id'], $trans['debit'], 'credit');
                } else {
                    $this->accountModel->updateBalance($trans['account_id'], $trans['credit'], 'debit');
                }
            }
        } catch (Exception $e) {
            error_log('Bookings reverseBookingRevenue error: ' . $e->getMessage());
        }
    }
}

