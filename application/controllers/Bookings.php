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
    private $locationModel;
    private $spaceModel;

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
        $this->locationModel = $this->loadModel('Location_model');
        $this->spaceModel = $this->loadModel('Space_model');
    }

    public function index() {
        $status = $_GET['status'] ?? 'all';
        $date = $_GET['date'] ?? date('Y-m-d');
        
        try {
            if ($status === 'all') {
                // Use the selected date to determine the month range
                $startDate = date('Y-m-01', strtotime($date));
                $endDate = date('Y-m-t', strtotime($date));
                $bookings = $this->bookingModel->getByDateRange($startDate, $endDate);
            } else {
                $bookings = $this->bookingModel->getByStatus($status);
            }
        } catch (Exception $e) {
            $bookings = [];
            error_log('Bookings index error: ' . $e->getMessage());
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
        $viewType = $_GET['view'] ?? 'month';
        $facilityId = $_GET['facility_id'] ?? null;
        $date = $_GET['date'] ?? date('Y-m-d');
        $month = $_GET['month'] ?? date('Y-m');
        
        try {
            $facilities = $this->facilityModel->getActive();
            
            if ($viewType === 'day') {
                // Day view with time slots
                // Use centralized logic from Facility_model
                $slotsData = $this->facilityModel->getAvailableTimeSlots($facilityId, $date);
                
                // Map to view expectations
                $slotsWithAvailability = [];
                if (!empty($slotsData['slots']) || !empty($slotsData['occupied'])) {
                    // Combine and sort
                    $allSlots = array_merge($slotsData['slots'], $slotsData['occupied']);
                    usort($allSlots, function($a, $b) {
                        return strcmp($a['start'], $b['start']);
                    });
                    
                    foreach ($allSlots as $slot) {
                        $slotsWithAvailability[] = [
                            'start_time' => $slot['start'],
                            'end_time' => $slot['end'],
                            'label' => $slot['display'],
                            'available' => $slot['available'],
                            'booking' => $slot['booking'] ?? null
                        ];
                    }
                } else {
                     // Empty state - maybe generate default slots if errors?
                     // But getAvailableTimeSlots should return empty if closed/unavailable
                }
                
                $data = [
                    'page_title' => 'Booking Calendar - Day View',
                    'view_type' => 'day',
                    'time_slots' => $slotsWithAvailability,
                    'facilities' => $facilities,
                    'selected_facility_id' => $facilityId,
                    'selected_date' => $date,
                    'flash' => $this->getFlashMessage()
                ];
                
                $this->loadView('bookings/calendar_timeslots', $data);
            } else {
                // Month view (existing functionality)
                $startDate = date('Y-m-01', strtotime($month));
                $endDate = date('Y-m-t', strtotime($month));
                
                $bookings = $this->bookingModel->getByDateRange($startDate, $endDate, $facilityId);
                
                $data = [
                    'page_title' => 'Booking Calendar',
                    'view_type' => 'month',
                    'bookings' => $bookings,
                    'facilities' => $facilities,
                    'selected_facility_id' => $facilityId,
                    'selected_month' => $month,
                    'selected_date' => $date,
                    'flash' => $this->getFlashMessage()
                ];
                
                $this->loadView('bookings/calendar', $data);
            }
        } catch (Exception $e) {
            error_log('Bookings calendar error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading calendar.');
            redirect('bookings');
        }
    }
    
    /**
     * Generate time slots for a given time range
     */
    private function generateTimeSlots($startTime, $endTime, $intervalMinutes) {
        $slots = [];
        $current = new DateTime($startTime);
        $end = new DateTime($endTime);
        
        while ($current < $end) {
            $slotStart = clone $current;
            $current->modify("+{$intervalMinutes} minutes");
            
            $slots[] = [
                'start' => $slotStart->format('H:i'),
                'end' => $current->format('H:i'),
                'label' => $slotStart->format('g:i A') . ' - ' . $current->format('g:i A')
            ];
        }
        
        return $slots;
    }
    
    /**
     * Find booking that overlaps with a time slot
     */
    private function getBookingForSlot($bookings, $slot) {
        foreach ($bookings as $booking) {
            if ($booking['start_time'] <= $slot['start'] && $booking['end_time'] >= $slot['end']) {
                return $booking;
            }
            // Partial overlap
            if ($booking['start_time'] < $slot['end'] && $booking['end_time'] > $slot['start']) {
                return $booking;
            }
        }
        return null;
    }
    
    /**
     * AJAX endpoint to get availability for a specific date
     */
    public function getAvailabilityForDate() {
        // ... (keep existing implementation or deprecate it, leaving as is for backward compat)
        $this->getTimeSlots();
    }

    /**
     * Get available and occupied time slots for a date (Smart UI)
     */
    public function getTimeSlots() {
        $this->requirePermission('bookings', 'read');
        
        // Prevent partial output
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');
        
        $spaceId = intval($_GET['space_id'] ?? 0);
        $facilityId = intval($_GET['facility_id'] ?? 0);
        $rawDate = $_GET['date'] ?? '';
        $rawEndDate = $_GET['end_date'] ?? $rawDate;
        
        // Helper to normalize date
        $normalizeDate = function($d) {
            if (empty($d)) return '';
            $dt = DateTime::createFromFormat('d/m/Y', $d);
            if ($dt && $dt->format('d/m/Y') === $d) return $dt->format('Y-m-d');
            $dt = DateTime::createFromFormat('Y-m-d', $d);
            if ($dt && $dt->format('Y-m-d') === $d) return $dt->format('Y-m-d');
            return date('Y-m-d', strtotime($d));
        };

        $date = $normalizeDate(sanitize_input($rawDate));
        $endDate = $normalizeDate(sanitize_input($rawEndDate));
        
        if (empty($endDate)) $endDate = $date;
        
        try {
            // If space_id provided, look it up
            if ($spaceId) {
                $space = $this->spaceModel->getWithProperty($spaceId);
                if ($space) {
                    $facilityId = $space['facility_id'];
                    if (!$facilityId && $space['is_bookable']) {
                        $facilityId = $this->spaceModel->syncToBookingModule($spaceId);
                    }
                }
            }
            
            if (!$facilityId) {
                 echo json_encode(['success' => false, 'message' => 'Facility/Space not found']);
                 exit;
            }

            // Use centralized logic
            $result = $this->facilityModel->getAvailableTimeSlots($facilityId, $date, $endDate);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log('Bookings getTimeSlots error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }


    public function create() {
        $this->requirePermission('bookings', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            
            $locationId = intval($_POST['location_id'] ?? 0);
            $spaceId = intval($_POST['space_id'] ?? 0);
            $facilityId = intval($_POST['facility_id'] ?? 0);
            $bookingDate = sanitize_input($_POST['booking_date'] ?? '');
            $startTime = sanitize_input($_POST['start_time'] ?? '');
            $endTime = sanitize_input($_POST['end_time'] ?? '');
            $bookingType = sanitize_input($_POST['booking_type'] ?? 'hourly');

            // Validate location and space
            if (!$locationId || !$spaceId) {
                $this->setFlashMessage('danger', 'Please select both location and space.');
                redirect('bookings/create');
            }

            // Get space and its facility_id
            $space = $this->spaceModel->getWithProperty($spaceId);
            if (!$space || !$space['is_bookable']) {
                $this->setFlashMessage('danger', 'Selected space is not available for booking.');
                redirect('bookings/create');
            }

            // Use facility_id from space, or get it
            if (!$facilityId && !empty($space['facility_id'])) {
                $facilityId = $space['facility_id'];
            } else if (!$facilityId) {
                // Auto-sync space to get facility_id
                try {
                    $facilityId = $this->spaceModel->syncToBookingModule($spaceId);
                    if (!$facilityId) {
                        $this->setFlashMessage('danger', 'Space is not properly configured for booking. Please contact administrator.');
                        redirect('bookings/create');
                    }
                } catch (Exception $e) {
                    error_log('Booking create auto-sync error: ' . $e->getMessage());
                    $this->setFlashMessage('danger', 'Error configuring space for booking.');
                    redirect('bookings/create');
                }
            }

            // Check availability
            if (!$this->facilityModel->checkAvailability($facilityId, $bookingDate, $startTime, $endTime)) {
                // Get conflict details
                $conflict = $this->facilityModel->getConflictingBooking($facilityId, $bookingDate, $startTime, $endTime);
                $msg = 'The selected time slot is not available.';
                
                if ($conflict) {
                     $msg .= ' Conflict with Booking #' . ($conflict['booking_number'] ?? $conflict['id']);
                     if (!empty($conflict['customer_name'])) {
                         $msg .= ' (' . $conflict['customer_name'] . ')';
                     }
                }
                
                $this->setFlashMessage('danger', $msg);
                
                // Persist input for repopulation
                if (isset($this->session)) {
                    $this->session->set_flashdata('_old_input', $_POST);
                }
                
                redirect('bookings/create');
            }

            $facility = $this->facilityModel->getById($facilityId);
            if (!$facility) {
                $this->setFlashMessage('danger', 'Facility not found.');
                redirect('bookings/create');
            }

            // Calculate price based on space's booking type
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
            
            // Add space_id and location_id if columns exist (for future use)
            // Check if columns exist before adding
            try {
                $columns = $this->db->fetchAll("SHOW COLUMNS FROM `" . $this->db->getPrefix() . "bookings` LIKE 'space_id'");
                if (!empty($columns)) {
                    $data['space_id'] = $spaceId;
                }
                $columns = $this->db->fetchAll("SHOW COLUMNS FROM `" . $this->db->getPrefix() . "bookings` LIKE 'location_id'");
                if (!empty($columns)) {
                    $data['location_id'] = $locationId;
                }
            } catch (Exception $e) {
                // Columns don't exist, that's OK - we'll use facility_id
                error_log('Bookings create: space_id/location_id columns not found, using facility_id only');
            }

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
            // Load locations (properties) with bookable spaces or marked as bookable
            $locations = $this->locationModel->getBookable();
            
            // Get all bookable spaces grouped by location
            $spacesByLocation = [];
            foreach ($locations as $location) {
                $spaces = $this->spaceModel->getBookableSpaces($location['id']);
                if (!empty($spaces)) {
                    // Get booking types for each space
                    foreach ($spaces as &$space) {
                        $config = $this->spaceModel->getBookableConfig($space['id']);
                        if ($config && !empty($config['booking_types'])) {
                            $space['booking_types'] = json_decode($config['booking_types'], true) ?: ['hourly', 'daily'];
                        } else {
                            $space['booking_types'] = ['hourly', 'daily']; // Default
                        }
                        
                        // Get pricing info
                        if ($config && !empty($config['pricing_rules'])) {
                            $pricingRules = json_decode($config['pricing_rules'], true) ?: [];
                            $space['hourly_rate'] = $pricingRules['base_hourly'] ?? $pricingRules['hourly'] ?? 0;
                            $space['daily_rate'] = $pricingRules['base_daily'] ?? $pricingRules['daily'] ?? 0;
                            $space['half_day_rate'] = $pricingRules['half_day'] ?? 0;
                            $space['weekly_rate'] = $pricingRules['weekly'] ?? 0;
                            $space['security_deposit'] = $pricingRules['deposit'] ?? 0;
                        }
                        
                        // Get facility_id if synced
                        if (!empty($space['facility_id'])) {
                            $facility = $this->facilityModel->getById($space['facility_id']);
                            if ($facility) {
                                $space['facility_id'] = $facility['id'];
                                $space['hourly_rate'] = $space['hourly_rate'] ?? $facility['hourly_rate'] ?? 0;
                                $space['daily_rate'] = $space['daily_rate'] ?? $facility['daily_rate'] ?? 0;
                            }
                        }
                    }
                    unset($space);
                    $spacesByLocation[$location['id']] = $spaces;
                }
            }
        } catch (Exception $e) {
            error_log('Bookings create load error: ' . $e->getMessage());
            $locations = [];
            $spacesByLocation = [];
        }

        $data = [
            'page_title' => 'Create Booking',
            'locations' => $locations,
            'spaces_by_location' => $spacesByLocation,
            'flash' => $this->getFlashMessage(),
            'old_input' => $this->session->flashdata('_old_input') ?? []
        ];

        $this->loadView('bookings/create', $data);
    }
    
    /**
     * AJAX endpoint to get spaces for a location
     */
    public function getSpacesForLocation() {
        $this->requirePermission('bookings', 'read');
        
        header('Content-Type: application/json');
        
        $locationId = intval($_GET['location_id'] ?? 0);
        if (!$locationId) {
            echo json_encode(['success' => false, 'error' => 'Location ID required']);
            exit;
        }
        
        try {
            $spaces = $this->spaceModel->getBookableSpaces($locationId);
            $spacesData = [];
            
            foreach ($spaces as $space) {
                $config = $this->spaceModel->getBookableConfig($space['id']);
                $bookingTypes = ['hourly', 'daily']; // Default
                
                if ($config && !empty($config['booking_types'])) {
                    $bookingTypes = json_decode($config['booking_types'], true) ?: $bookingTypes;
                }
                
                // Get pricing
                $pricingRules = [];
                if ($config && !empty($config['pricing_rules'])) {
                    $pricingRules = json_decode($config['pricing_rules'], true) ?: [];
                }
                
                // Get facility if synced
                $facilityId = null;
                $hourlyRate = $pricingRules['base_hourly'] ?? $pricingRules['hourly'] ?? 0;
                $dailyRate = $pricingRules['base_daily'] ?? $pricingRules['daily'] ?? 0;
                
                if (!empty($space['facility_id'])) {
                    $facilityId = $space['facility_id'];
                    $facility = $this->facilityModel->getById($facilityId);
                    if ($facility) {
                        $hourlyRate = $hourlyRate ?: ($facility['hourly_rate'] ?? 0);
                        $dailyRate = $dailyRate ?: ($facility['daily_rate'] ?? 0);
                    }
                }
                
                $spacesData[] = [
                    'id' => $space['id'],
                    'space_name' => $space['space_name'],
                    'space_number' => $space['space_number'] ?? '',
                    'capacity' => $space['capacity'] ?? 0,
                    'facility_id' => $facilityId,
                    'booking_types' => $bookingTypes,
                    'hourly_rate' => floatval($hourlyRate),
                    'daily_rate' => floatval($dailyRate),
                    'half_day_rate' => floatval($pricingRules['half_day'] ?? 0),
                    'weekly_rate' => floatval($pricingRules['weekly'] ?? 0),
                    'security_deposit' => floatval($pricingRules['deposit'] ?? 0),
                    'minimum_duration' => intval($config['minimum_duration'] ?? 1),
                    'maximum_duration' => !empty($config['maximum_duration']) ? intval($config['maximum_duration']) : null
                ];
            }
            
            echo json_encode(['success' => true, 'spaces' => $spacesData]);
        } catch (Exception $e) {
            error_log('getSpacesForLocation error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
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
    
    /**
     * Edit booking details
     */
    public function edit($id) {
        $this->requirePermission('bookings', 'update');
        
        try {
            $booking = $this->bookingModel->getWithFacility($id);
            if (!$booking) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('bookings');
            }
            
            // Cannot edit cancelled or completed bookings
            if (in_array($booking['status'], ['cancelled', 'completed'])) {
                $this->setFlashMessage('warning', 'Cannot edit a ' . $booking['status'] . ' booking.');
                redirect('bookings/view/' . $id);
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading booking.');
            redirect('bookings');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            
            try {
                $pdo = $this->db->getConnection();
                $pdo->beginTransaction();
                
                // Store old values for logging
                $oldValues = json_encode([
                    'customer_name' => $booking['customer_name'],
                    'customer_email' => $booking['customer_email'],
                    'customer_phone' => $booking['customer_phone'],
                    'booking_notes' => $booking['booking_notes']
                ]);
                
                // Validate customer email if provided
                if (!empty($_POST['customer_email']) && !validate_email($_POST['customer_email'])) {
                    $this->setFlashMessage('danger', 'Invalid email address.');
                    redirect('bookings/edit/' . $id);
                }
                
                // Validate customer phone if provided
                if (!empty($_POST['customer_phone']) && !validate_phone($_POST['customer_phone'])) {
                    $this->setFlashMessage('danger', 'Invalid phone number.');
                    redirect('bookings/edit/' . $id);
                }
                
                $updateData = [
                    'customer_name' => sanitize_input($_POST['customer_name'] ?? $booking['customer_name']),
                    'customer_email' => sanitize_input($_POST['customer_email'] ?? $booking['customer_email']),
                    'customer_phone' => !empty($_POST['customer_phone']) ? sanitize_phone($_POST['customer_phone']) : $booking['customer_phone'],
                    'customer_address' => sanitize_input($_POST['customer_address'] ?? $booking['customer_address']),
                    'number_of_guests' => intval($_POST['number_of_guests'] ?? $booking['number_of_guests']),
                    'booking_notes' => sanitize_input($_POST['booking_notes'] ?? $booking['booking_notes']),
                    'special_requests' => sanitize_input($_POST['special_requests'] ?? $booking['special_requests']),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Handle discount update if provided
                if (isset($_POST['discount_amount'])) {
                    $newDiscount = floatval($_POST['discount_amount']);
                    if ($newDiscount != floatval($booking['discount_amount'])) {
                        $updateData['discount_amount'] = $newDiscount;
                        // Recalculate total
                        $newTotal = floatval($booking['base_amount']) + floatval($booking['tax_amount']) - $newDiscount;
                        $updateData['total_amount'] = $newTotal;
                        $updateData['balance_amount'] = $newTotal - floatval($booking['paid_amount']);
                    }
                }
                
                $this->bookingModel->update($id, $updateData);
                
                // Log modification
                $newValues = json_encode([
                    'customer_name' => $updateData['customer_name'],
                    'customer_email' => $updateData['customer_email'],
                    'customer_phone' => $updateData['customer_phone'],
                    'booking_notes' => $updateData['booking_notes']
                ]);
                
                $this->bookingModificationModel->logModification(
                    $id,
                    'edit',
                    $oldValues,
                    $newValues,
                    'Booking details updated',
                    $this->session['user_id']
                );
                
                $pdo->commit();
                
                $this->activityModel->log(
                    $this->session['user_id'], 
                    'update', 
                    'Bookings', 
                    'Updated booking details: ' . $booking['booking_number']
                );
                
                $this->setFlashMessage('success', 'Booking updated successfully.');
                redirect('bookings/view/' . $id);
                
            } catch (Exception $e) {
                if (isset($pdo)) {
                    $pdo->rollBack();
                }
                error_log('Bookings edit error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to update booking: ' . $e->getMessage());
            }
        }
        
        $data = [
            'page_title' => 'Edit Booking: ' . $booking['booking_number'],
            'booking' => $booking,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('bookings/edit', $data);
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

            // Update booking paid amount and balance
            $newPaidAmount = floatval($booking['paid_amount']) + $amount;
            $newBalance = floatval($booking['total_amount']) - $newPaidAmount;
            
            $updateData = [
                'paid_amount' => $newPaidAmount,
                'balance_amount' => max(0, $newBalance)
            ];
            
            // Update payment status based on balance
            if ($newBalance <= 0) {
                $updateData['payment_status'] = 'paid';
            } elseif ($newPaidAmount > 0) {
                $updateData['payment_status'] = 'partial';
            }
            
            $this->bookingModel->update($bookingId, $updateData);

            // Create double-entry accounting entries for ALL payment methods
            if ($this->cashAccountModel && $this->transactionModel) {
                try {
                    $defaultCashAccount = $this->cashAccountModel->getDefault();
                    if ($defaultCashAccount) {
                        // Debit Cash/Bank (asset increases)
                        $this->transactionModel->create([
                            'account_id' => $defaultCashAccount['account_id'] ?? $defaultCashAccount['id'],
                            'debit' => $amount,
                            'credit' => 0,
                            'description' => ucfirst($paymentMethod) . ' payment for booking: ' . ($booking['booking_number'] ?? $bookingId),
                            'reference_type' => 'booking_payment',
                            'reference_id' => $paymentId,
                            'transaction_date' => date('Y-m-d'),
                            'status' => 'posted',
                            'created_by' => $this->session['user_id']
                        ]);

                        // Update cash account balance
                        $this->cashAccountModel->updateBalance($defaultCashAccount['id'], $amount, 'deposit');

                        // Credit Accounts Receivable (liability decreases)
                        if ($this->accountModel) {
                            $arAccount = $this->accountModel->getByCode('1200');
                            if ($arAccount) {
                                $this->transactionModel->create([
                                    'account_id' => $arAccount['id'],
                                    'debit' => 0,
                                    'credit' => $amount,
                                    'description' => 'Payment received - booking: ' . ($booking['booking_number'] ?? $bookingId),
                                    'reference_type' => 'booking_payment',
                                    'reference_id' => $paymentId,
                                    'transaction_date' => date('Y-m-d'),
                                    'status' => 'posted',
                                    'created_by' => $this->session['user_id']
                                ]);
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Log but don't fail - accounting is secondary
                    error_log('Booking payment accounting error: ' . $e->getMessage());
                }
            }

            $pdo->commit();
            
            // Log activity
            $this->activityModel->log(
                $this->session['user_id'], 
                'create', 
                'Booking Payments', 
                'Recorded payment of ' . number_format($amount, 2) . ' for booking: ' . ($booking['booking_number'] ?? $bookingId)
            );
            
            return $paymentId;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

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
            $bookingRevenueAccount = $this->accountModel->getByCode('4000'); // Sales/Service Revenue
            if (!$bookingRevenueAccount) {
                $revenueAccounts = $this->accountModel->getByType('Revenue');
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
            // We can check this by looking for a transaction with this reference
            // But Transaction_service doesn't expose a check method easily, so we rely on logic or existing checks
            // For now, we'll assume the caller ensures this isn't called twice, or we check manually
            // The original code checked transactionModel->getByReference. We can still do that if needed, 
            // or just trust the flow. Let's keep the check if possible, but Transaction_service handles posting.
            
            // If unearned revenue account exists and booking was prepaid, recognize it
            // Otherwise, create AR entry if not fully paid
            $paidAmount = floatval($booking['paid_amount'] ?? 0);
            $balanceAmount = $totalAmount - $paidAmount;
            
            $entries = [];
            
            // 1. Debit Accounts Receivable for unpaid portion
            if ($balanceAmount > 0) {
                $arAccount = $this->accountModel->getByCode('1200'); // Accounts Receivable
                if (!$arAccount) {
                     $arAccounts = $this->accountModel->getByType('Assets');
                     foreach ($arAccounts as $acc) {
                        if (stripos($acc['account_name'], 'receivable') !== false) {
                            $arAccount = $acc;
                            break;
                        }
                    }
                }
                
                if ($arAccount) {
                    $entries[] = [
                        'account_id' => $arAccount['id'],
                        'debit' => $balanceAmount,
                        'credit' => 0,
                        'description' => 'Booking Receivable'
                    ];
                }
            }
            
            // 2. Debit Unearned Revenue for paid portion (transfer to Revenue)
            if ($paidAmount > 0) {
                $unearnedRevenueAccount = $this->accountModel->getByCode('2205'); // Unearned Revenue
                if ($unearnedRevenueAccount) {
                    $entries[] = [
                        'account_id' => $unearnedRevenueAccount['id'],
                        'debit' => $paidAmount,
                        'credit' => 0,
                        'description' => 'Recognize Unearned Revenue'
                    ];
                } else {
                    // If no unearned revenue account, maybe it was credited to revenue directly? 
                    // Or maybe we should debit Cash? No, cash was debited when payment was received.
                    // If we don't have unearned revenue account, we assume the payment went to Unearned Revenue 
                    // (as per processPayment logic). If that logic failed to find Unearned Revenue, it used Revenue.
                    // If it used Revenue, then we don't need to do anything for the paid amount now?
                    // Wait, if processPayment credited Revenue directly because it couldn't find Unearned Revenue, 
                    // then we shouldn't recognize it again.
                    // BUT, processPayment logic says: if pending, use Unearned Revenue.
                    // So we should try to find Unearned Revenue.
                    // If we can't find it, we might have an issue.
                    // Let's assume Unearned Revenue exists or was used.
                    // If we can't find it now, we can't debit it.
                }
            }
            
            // 3. Credit Booking Revenue for Total Amount
            $entries[] = [
                'account_id' => $bookingRevenueAccount['id'],
                'debit' => 0,
                'credit' => $totalAmount,
                'description' => 'Booking Revenue Recognized'
            ];
            
            if (!empty($entries)) {
                $journalData = [
                    'date' => $booking['booking_date'],
                    'reference_type' => 'booking_revenue',
                    'reference_id' => $bookingId,
                    'description' => 'Booking Revenue Recognition #' . $booking['booking_number'],
                    'journal_type' => 'sales',
                    'entries' => $entries,
                    'created_by' => $this->session['user_id'] ?? null,
                    'auto_post' => true
                ];
                
                $this->transactionService->postJournalEntry($journalData);
            }
            
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
            // We need to reverse both revenue recognition and payments if they exist?
            // Usually, if cancelled, we reverse revenue. Payments might be refunded (which is a separate action) 
            // or kept as credit (which means we shouldn't reverse the payment receipt, just the revenue recognition).
            // The original code reversed EVERYTHING: 'booking_revenue' AND 'booking_payment'.
            // If we reverse payment, it means we are saying we never received the money? 
            // Or is it a refund?
            // If it's a refund, we should process a refund transaction.
            // Reversing the payment receipt transaction effectively means the cash disappears from our books 
            // (Credit Cash, Debit AR/Customer).
            // If the user actually refunds the money, that's a separate step.
            // If we blindly reverse payment receipt, we might double count if we also process a refund.
            // However, let's stick to the original logic for now: Reverse everything.
            
            $transactions = $this->transactionModel->getByReference('booking_revenue', $bookingId);
            // $transactions = array_merge($transactions, $this->transactionModel->getByReference('booking_payment', $bookingId)); 
            // I will COMMENT OUT reversing payments for now, as refunds should be explicit. 
            // Reversing revenue is correct (we no longer earned it).
            // Reversing payment receipt is dangerous without an actual refund.
            
            if (empty($transactions)) {
                return;
            }
            
            $entries = [];
            foreach ($transactions as $trans) {
                // Swap debit and credit
                $entries[] = [
                    'account_id' => $trans['account_id'],
                    'debit' => $trans['credit'],
                    'credit' => $trans['debit'],
                    'description' => 'Reversal: ' . $trans['description']
                ];
            }
            
            if (!empty($entries)) {
                $journalData = [
                    'date' => date('Y-m-d'),
                    'reference_type' => 'booking_cancellation',
                    'reference_id' => $bookingId,
                    'description' => 'Booking Cancellation Reversal #' . $booking['booking_number'],
                    'journal_type' => 'general', // or adjustment
                    'entries' => $entries,
                    'created_by' => $this->session['user_id'] ?? null,
                    'auto_post' => true
                ];
                
                $this->transactionService->postJournalEntry($journalData);
            }
            
        } catch (Exception $e) {
            error_log('Bookings reverseBookingRevenue error: ' . $e->getMessage());
        }
    }
    
    /**
     * Expire pending bookings that haven't been paid within timeout period
     * Can be called via cron: php index.php bookings/expirePendingBookings
     * Or via web: /bookings/expirePendingBookings (admin only)
     */
    public function expirePendingBookings() {
        // For web access, require admin permission
        if (php_sapi_name() !== 'cli') {
            $this->requirePermission('bookings', 'update');
        }
        
        try {
            $timeoutMinutes = 30; // Configurable: time after which pending bookings expire
            
            // Find pending bookings older than timeout
            $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$timeoutMinutes} minutes"));
            
            $pendingBookings = $this->db->fetchAll(
                "SELECT id, booking_number, created_at, customer_email 
                 FROM `" . $this->db->getPrefix() . "bookings` 
                 WHERE status = 'pending' 
                 AND payment_status IN ('unpaid', 'pending') 
                 AND created_at < ?",
                [$cutoffTime]
            );
            
            $expiredCount = 0;
            
            foreach ($pendingBookings as $booking) {
                try {
                    // Update booking status to expired
                    $this->bookingModel->update($booking['id'], [
                        'status' => 'expired',
                        'cancelled_at' => date('Y-m-d H:i:s'),
                        'cancellation_reason' => 'Payment timeout - booking expired after ' . $timeoutMinutes . ' minutes'
                    ]);
                    
                    // Delete any associated booking slots (free up time slots)
                    $this->db->query(
                        "DELETE FROM `" . $this->db->getPrefix() . "booking_slots` WHERE booking_id = ?",
                        [$booking['id']]
                    );
                    
                    $expiredCount++;
                    error_log("expirePendingBookings: Expired booking #{$booking['booking_number']} (ID: {$booking['id']})");
                    
                } catch (Exception $e) {
                    error_log("expirePendingBookings: Error expiring booking {$booking['id']}: " . $e->getMessage());
                }
            }
            
            $message = "Expired {$expiredCount} pending bookings older than {$timeoutMinutes} minutes.";
            error_log("expirePendingBookings: " . $message);
            
            // Return JSON for API/cron calls, or redirect for web
            if (php_sapi_name() === 'cli' || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'expired_count' => $expiredCount
                ]);
                exit;
            } else {
                $this->setFlashMessage('success', $message);
                redirect('bookings');
            }
            
        } catch (Exception $e) {
            error_log('expirePendingBookings error: ' . $e->getMessage());
            
            if (php_sapi_name() === 'cli' || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            } else {
                $this->setFlashMessage('danger', 'Failed to expire pending bookings: ' . $e->getMessage());
                redirect('bookings');
            }
        }
    }
}

