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
    private $bookingResourceModel;
    private $bookingAddonModel;
    private $addonModel;
    private $promoCodeModel;
    private $cancellationPolicyModel;
    private $paymentScheduleModel;
    private $bookingModificationModel;

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
        $this->bookingResourceModel = $this->loadModel('Booking_resource_model');
        $this->bookingAddonModel = $this->loadModel('Booking_addon_model');
        $this->addonModel = $this->loadModel('Addon_model');
        $this->promoCodeModel = $this->loadModel('Promo_code_model');
        $this->cancellationPolicyModel = $this->loadModel('Cancellation_policy_model');
        $this->paymentScheduleModel = $this->loadModel('Payment_schedule_model');
        $this->bookingModificationModel = $this->loadModel('Booking_modification_model');
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

            // Validate customer email if provided
            if (!empty($_POST['customer_email']) && !validate_email($_POST['customer_email'])) {
                $this->setFlashMessage('danger', 'Invalid email address.');
                redirect('bookings/create');
            }
            
            // Validate customer phone if provided
            if (!empty($_POST['customer_phone']) && !validate_phone($_POST['customer_phone'])) {
                $this->setFlashMessage('danger', 'Invalid phone number. Please enter a valid phone number.');
                redirect('bookings/create');
            }
            
            // Validate customer name
            if (!empty($_POST['customer_name']) && !validate_name($_POST['customer_name'])) {
                $this->setFlashMessage('danger', 'Invalid customer name.');
                redirect('bookings/create');
            }
            
            // Sanitize phone
            $customerPhone = !empty($_POST['customer_phone']) ? sanitize_phone($_POST['customer_phone']) : '';
            
            $data = [
                'booking_number' => $this->bookingModel->getNextBookingNumber(),
                'facility_id' => $facilityId,
                'customer_name' => sanitize_input($_POST['customer_name'] ?? ''),
                'customer_email' => sanitize_input($_POST['customer_email'] ?? ''),
                'customer_phone' => $customerPhone,
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
            
            // Send payment notification
            try {
                $notificationModel = $this->loadModel('Notification_model');
                $notificationModel->createNotification([
                    'customer_email' => $booking['customer_email'],
                    'type' => 'payment_received',
                    'title' => 'Payment Received',
                    'message' => "Payment of " . format_currency($amount) . " received for booking {$booking['booking_number']}",
                    'related_module' => 'booking',
                    'related_id' => $bookingId
                ]);
            } catch (Exception $e) {
                error_log('Bookings payment notification error: ' . $e->getMessage());
            }
            
            $this->activityModel->log($this->session['user_id'], 'create', 'Bookings', 'Recorded payment for booking: ' . $booking['booking_number']);
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Reschedule booking
     */
    public function reschedule($id) {
        $this->requirePermission('bookings', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newDate = sanitize_input($_POST['booking_date'] ?? '');
            $newStartTime = sanitize_input($_POST['start_time'] ?? '');
            $newEndTime = sanitize_input($_POST['end_time'] ?? '');
            $reason = sanitize_input($_POST['reason'] ?? '');
            
            try {
                $booking = $this->bookingModel->getById($id);
                if (!$booking) {
                    $this->setFlashMessage('danger', 'Booking not found.');
                    redirect('bookings');
                }
                
                // Check new availability
                $resourceId = $booking['facility_id'];
                if (!$this->facilityModel->checkAdvancedAvailability($resourceId, $newDate . ' ' . $newStartTime, $newDate . ' ' . $newEndTime, $id)) {
                    $this->setFlashMessage('danger', 'The selected time slot is not available.');
                    redirect('bookings/reschedule/' . $id);
                }
                
                $pdo = $this->db->getConnection();
                $pdo->beginTransaction();
                
                // Log modification
                $oldValue = json_encode([
                    'date' => $booking['booking_date'],
                    'start_time' => $booking['start_time'],
                    'end_time' => $booking['end_time']
                ]);
                $newValue = json_encode([
                    'date' => $newDate,
                    'start_time' => $newStartTime,
                    'end_time' => $newEndTime
                ]);
                
                $this->bookingModificationModel->logModification(
                    $id,
                    'reschedule',
                    $oldValue,
                    $newValue,
                    $reason,
                    $this->session['user_id']
                );
                
                // Update booking
                $duration = $this->calculateDuration($newDate, $newStartTime, $newEndTime);
                $updateData = [
                    'booking_date' => $newDate,
                    'start_time' => $newStartTime,
                    'end_time' => $newEndTime,
                    'duration_hours' => $duration
                ];
                
                $this->bookingModel->update($id, $updateData);
                
                // Update booking slots
                $this->bookingModel->createSlots($id, $resourceId, $newDate, $newStartTime, $newEndTime);
                
                $pdo->commit();
                $this->activityModel->log($this->session['user_id'], 'update', 'Bookings', 'Rescheduled booking: ' . $booking['booking_number']);
                $this->setFlashMessage('success', 'Booking rescheduled successfully.');
                redirect('bookings/view/' . $id);
            } catch (Exception $e) {
                if (isset($pdo)) {
                    $pdo->rollBack();
                }
                error_log('Bookings reschedule error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to reschedule booking.');
                redirect('bookings/view/' . $id);
            }
        }
        
        try {
            $booking = $this->bookingModel->getWithFacility($id);
            if (!$booking) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('bookings');
            }
        } catch (Exception $e) {
            $booking = null;
        }
        
        $data = [
            'page_title' => 'Reschedule Booking',
            'booking' => $booking,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('bookings/reschedule', $data);
    }
    
    /**
     * Cancel booking
     */
    public function cancel($id) {
        $this->requirePermission('bookings', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reason = sanitize_input($_POST['cancellation_reason'] ?? '');
            $applyRefund = !empty($_POST['apply_refund']);
            
            try {
                $booking = $this->bookingModel->getById($id);
                if (!$booking) {
                    $this->setFlashMessage('danger', 'Booking not found.');
                    redirect('bookings');
                }
                
                if ($booking['status'] === 'cancelled') {
                    $this->setFlashMessage('warning', 'Booking is already cancelled.');
                    redirect('bookings/view/' . $id);
                }
                
                $pdo = $this->db->getConnection();
                $pdo->beginTransaction();
                
                // Calculate refund if applicable
                $refundAmount = 0;
                if ($applyRefund && $booking['cancellation_policy_id']) {
                    $refundAmount = $this->cancellationPolicyModel->calculateRefund(
                        $booking['cancellation_policy_id'],
                        $booking['booking_date'],
                        date('Y-m-d'),
                        $booking['total_amount']
                    );
                }
                
                // Update booking status
                $updateData = [
                    'status' => 'cancelled',
                    'cancelled_at' => date('Y-m-d H:i:s'),
                    'cancellation_reason' => $reason
                ];
                
                // If refund applies, update balance
                if ($refundAmount > 0) {
                    $updateData['paid_amount'] = max(0, floatval($booking['paid_amount']) - $refundAmount);
                    $updateData['balance_amount'] = floatval($booking['total_amount']) - floatval($updateData['paid_amount']);
                }
                
                $this->bookingModel->update($id, $updateData);
                
                // Log modification
                $this->bookingModificationModel->logModification(
                    $id,
                    'status_change',
                    $booking['status'],
                    'cancelled',
                    $reason,
                    $this->session['user_id']
                );
                
                // Reverse accounting entries if booking was confirmed
                if ($booking['status'] === 'confirmed' || $booking['status'] === 'in_progress') {
                    $this->reverseBookingRevenue($id);
                }
                
                $pdo->commit();
                $this->activityModel->log($this->session['user_id'], 'update', 'Bookings', 'Cancelled booking: ' . $booking['booking_number']);
                $this->setFlashMessage('success', 'Booking cancelled successfully.' . ($refundAmount > 0 ? ' Refund: ' . format_currency($refundAmount) : ''));
                redirect('bookings/view/' . $id);
            } catch (Exception $e) {
                if (isset($pdo)) {
                    $pdo->rollBack();
                }
                error_log('Bookings cancel error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to cancel booking.');
                redirect('bookings/view/' . $id);
            }
        }
        
        try {
            $booking = $this->bookingModel->getWithFacility($id);
            if (!$booking) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('bookings');
            }
            
            $cancellationPolicy = null;
            if ($booking['cancellation_policy_id']) {
                $cancellationPolicy = $this->cancellationPolicyModel->getById($booking['cancellation_policy_id']);
            } else {
                $cancellationPolicy = $this->cancellationPolicyModel->getDefault();
            }
            
            // Calculate potential refund
            $potentialRefund = 0;
            if ($cancellationPolicy) {
                $potentialRefund = $this->cancellationPolicyModel->calculateRefund(
                    $cancellationPolicy['id'],
                    $booking['booking_date'],
                    date('Y-m-d'),
                    $booking['total_amount']
                );
            }
        } catch (Exception $e) {
            $booking = null;
            $cancellationPolicy = null;
            $potentialRefund = 0;
        }
        
        $data = [
            'page_title' => 'Cancel Booking',
            'booking' => $booking,
            'cancellation_policy' => $cancellationPolicy,
            'potential_refund' => $potentialRefund,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('bookings/cancel', $data);
    }
    
    /**
     * Update booking status
     */
    public function updateStatus($id) {
        $this->requirePermission('bookings', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newStatus = sanitize_input($_POST['status'] ?? '');
            $reason = sanitize_input($_POST['reason'] ?? '');
            
            try {
                $booking = $this->bookingModel->getById($id);
                if (!$booking) {
                    $this->setFlashMessage('danger', 'Booking not found.');
                    redirect('bookings');
                }
                
                $oldStatus = $booking['status'];
                
                $pdo = $this->db->getConnection();
                $pdo->beginTransaction();
                
                // Update status
                $updateData = ['status' => $newStatus];
                
                // Handle status-specific logic
                if ($newStatus === 'confirmed' && $oldStatus === 'pending') {
                    // Recognize revenue when confirmed
                    $this->recognizeBookingRevenue($id, $booking);
                    // Send confirmation notification
                    $this->sendBookingNotification($id, $booking);
                } elseif ($newStatus === 'in_progress' && $oldStatus === 'confirmed') {
                    // Booking has started
                    $updateData['started_at'] = date('Y-m-d H:i:s');
                } elseif ($newStatus === 'completed' && in_array($oldStatus, ['confirmed', 'in_progress'])) {
                    // Finalize revenue
                    $this->finalizeBookingRevenue($id);
                    $updateData['completed_at'] = date('Y-m-d H:i:s');
                } elseif ($newStatus === 'cancelled' && in_array($oldStatus, ['pending', 'confirmed', 'in_progress'])) {
                    // Reverse revenue if cancelled
                    $this->reverseBookingRevenue($id);
                    $updateData['cancelled_at'] = date('Y-m-d H:i:s');
                    $updateData['cancellation_reason'] = $reason;
                }
                
                $this->bookingModel->update($id, $updateData);
                
                // Log modification
                $this->bookingModificationModel->logModification(
                    $id,
                    'status_change',
                    $oldStatus,
                    $newStatus,
                    $reason,
                    $this->session['user_id']
                );
                
                $pdo->commit();
                $this->activityModel->log($this->session['user_id'], 'update', 'Bookings', 'Updated booking status: ' . $booking['booking_number'] . ' (' . $oldStatus . ' → ' . $newStatus . ')');
                $this->setFlashMessage('success', 'Booking status updated successfully.');
                redirect('bookings/view/' . $id);
            } catch (Exception $e) {
                if (isset($pdo)) {
                    $pdo->rollBack();
                }
                error_log('Bookings updateStatus error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to update booking status.');
                redirect('bookings/view/' . $id);
            }
        }
        
        redirect('bookings/view/' . $id);
    }
    
    /**
     * View booking modifications history
     */
    public function modifications($id) {
        $this->requirePermission('bookings', 'read');
        
        try {
            $booking = $this->bookingModel->getById($id);
            $modifications = $this->bookingModificationModel->getByBooking($id);
        } catch (Exception $e) {
            $booking = null;
            $modifications = [];
        }
        
        $data = [
            'page_title' => 'Booking Modifications',
            'booking' => $booking,
            'modifications' => $modifications,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('bookings/modifications', $data);
    }
    
    /**
     * Add resources to booking (multiple resource booking)
     */
    public function addResource($bookingId) {
        $this->requirePermission('bookings', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resourceId = intval($_POST['resource_id'] ?? 0);
            $startDateTime = sanitize_input($_POST['start_datetime'] ?? '');
            $endDateTime = sanitize_input($_POST['end_datetime'] ?? '');
            $quantity = intval($_POST['quantity'] ?? 1);
            
            try {
                $booking = $this->bookingModel->getById($bookingId);
                if (!$booking) {
                    throw new Exception('Booking not found');
                }
                
                $resource = $this->facilityModel->getById($resourceId);
                if (!$resource) {
                    throw new Exception('Resource not found');
                }
                
                // Check availability
                if (!$this->facilityModel->checkAdvancedAvailability($resourceId, $startDateTime, $endDateTime, $bookingId)) {
                    throw new Exception('Time slot not available');
                }
                
                // Calculate rate
                $rate = floatval($resource['hourly_rate']);
                $start = new DateTime($startDateTime);
                $end = new DateTime($endDateTime);
                $hours = $end->diff($start)->h + ($end->diff($start)->i / 60);
                $amount = $rate * $hours * $quantity;
                
                // Add to booking_resources
                $this->bookingResourceModel->addResource(
                    $bookingId,
                    $resourceId,
                    $startDateTime,
                    $endDateTime,
                    $quantity,
                    $rate,
                    'hourly'
                );
                
                // Update booking total
                $resourceTotal = $this->bookingResourceModel->getTotalByBooking($bookingId);
                $addonsTotal = $this->bookingAddonModel->getTotalByBooking($bookingId);
                $subtotal = $resourceTotal + $addonsTotal;
                
                $this->bookingModel->update($bookingId, [
                    'subtotal' => $subtotal,
                    'total_amount' => $subtotal + floatval($booking['security_deposit'] ?? 0),
                    'balance_amount' => ($subtotal + floatval($booking['security_deposit'] ?? 0)) - floatval($booking['paid_amount'])
                ]);
                
                $this->activityModel->log($this->session['user_id'], 'update', 'Bookings', 'Added resource to booking: ' . $booking['booking_number']);
                $this->setFlashMessage('success', 'Resource added to booking successfully.');
                redirect('bookings/view/' . $bookingId);
            } catch (Exception $e) {
                error_log('Bookings addResource error: ' . $e->getMessage());
                $this->setFlashMessage('danger', $e->getMessage());
                redirect('bookings/view/' . $bookingId);
            }
        }
        
        redirect('bookings/view/' . $bookingId);
    }
    
    /**
     * Remove resource from booking
     */
    public function removeResource($bookingResourceId) {
        $this->requirePermission('bookings', 'update');
        
        try {
            $bookingResource = $this->bookingResourceModel->getById($bookingResourceId);
            if (!$bookingResource) {
                $this->setFlashMessage('danger', 'Booking resource not found.');
                redirect('bookings');
            }
            
            $bookingId = $bookingResource['booking_id'];
            $booking = $this->bookingModel->getById($bookingId);
            
            // Delete resource
            $this->bookingResourceModel->delete($bookingResourceId);
            
            // Recalculate totals
            $resourceTotal = $this->bookingResourceModel->getTotalByBooking($bookingId);
            $addonsTotal = $this->bookingAddonModel->getTotalByBooking($bookingId);
            $subtotal = $resourceTotal + $addonsTotal;
            
            $this->bookingModel->update($bookingId, [
                'subtotal' => $subtotal,
                'total_amount' => $subtotal + floatval($booking['security_deposit'] ?? 0),
                'balance_amount' => ($subtotal + floatval($booking['security_deposit'] ?? 0)) - floatval($booking['paid_amount'])
            ]);
            
            $this->activityModel->log($this->session['user_id'], 'update', 'Bookings', 'Removed resource from booking: ' . $booking['booking_number']);
            $this->setFlashMessage('success', 'Resource removed from booking successfully.');
            redirect('bookings/view/' . $bookingId);
        } catch (Exception $e) {
            error_log('Bookings removeResource error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Failed to remove resource.');
            redirect('bookings');
        }
    }
    
    private function calculateDuration($date, $startTime, $endTime) {
        try {
            $start = new DateTime($date . ' ' . $startTime);
            $end = new DateTime($date . ' ' . $endTime);
            $duration = $end->diff($start);
            return $duration->h + ($duration->i / 60);
        } catch (Exception $e) {
            error_log('Bookings calculateDuration error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Send booking notification
     */
    private function sendBookingNotification($bookingId, $bookingData = null) {
        try {
            $notificationModel = $this->loadModel('Notification_model');
            $booking = $bookingData ?: $this->bookingModel->getWithFacility($bookingId);
            
            if ($booking && !empty($booking['customer_email'])) {
                $notificationModel->sendBookingConfirmation($bookingId, $booking);
            }
        } catch (Exception $e) {
            error_log('Bookings sendBookingNotification error: ' . $e->getMessage());
        }
    }
    
    /**
     * Send cancellation notification
     */
    private function sendCancellationNotification($bookingId, $booking, $reason = '') {
        try {
            $notificationModel = $this->loadModel('Notification_model');
            
            if ($booking && !empty($booking['customer_email'])) {
                $notificationModel->createNotification([
                    'customer_email' => $booking['customer_email'],
                    'type' => 'booking_cancelled',
                    'title' => 'Booking Cancelled',
                    'message' => "Your booking {$booking['booking_number']} has been cancelled. " . ($reason ? "Reason: {$reason}" : ''),
                    'related_module' => 'booking',
                    'related_id' => $bookingId
                ]);
            }
        } catch (Exception $e) {
            error_log('Bookings sendCancellationNotification error: ' . $e->getMessage());
        }
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
    private function finalizeBookingRevenue($bookingId, $booking = null) {
        // Revenue should already be recognized when confirmed
        // This method can handle any final adjustments if needed
        // For now, just log the completion
        if (!$booking) {
            $booking = $this->bookingModel->getById($bookingId);
        }
    }

    /**
     * Reverse booking revenue when booking is cancelled
     */
    private function reverseBookingRevenue($bookingId, $booking = null) {
        if (!$booking) {
            $booking = $this->bookingModel->getById($bookingId);
            if (!$booking) {
                return;
            }
        }
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

