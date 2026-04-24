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
    private $spaceModel;
    private $locationModel;
    private $transactionService;
    private $invoiceModel;
    private $journalModel;
    private $journalCleanupService;
    private $customerModel;

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
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->journalModel = $this->loadModel('Journal_entry_model');
        $this->customerModel = $this->loadModel('Customer_model');
        
        // Load Transaction Service
        $transactionServicePath = BASEPATH . 'services/Transaction_service.php';
        if (file_exists($transactionServicePath)) {
            require_once $transactionServicePath;
            $this->transactionService = new Transaction_service();
        } else {
            error_log('Transaction_service.php not found at: ' . $transactionServicePath);
            $this->transactionService = null;
        }

        $journalCleanupPath = BASEPATH . 'services/Journal_cleanup_service.php';
        if (file_exists($journalCleanupPath)) {
            require_once $journalCleanupPath;
            $this->journalCleanupService = new Journal_cleanup_service();
        } else {
            error_log('Journal_cleanup_service.php not found at: ' . $journalCleanupPath);
            $this->journalCleanupService = null;
        }
    }

    public function index() {
        $status = $_GET['status'] ?? 'all';
        $date = $_GET['date'] ?? '';
        $hasDateFilter = !empty($_GET['date']); // Only filter by date if user explicitly set one

        // Auto-complete any confirmed/in_progress bookings whose date has passed
        $this->autoCompleteExpiredBookings();
        
        try {
            if ($hasDateFilter) {
                // User explicitly selected a date — show that month's bookings
                $startDate = date('Y-m-01', strtotime($date));
                $endDate = date('Y-m-t', strtotime($date));
                $bookings = $this->bookingModel->getByDateRange($startDate, $endDate);
                
                // Filter by status
                if ($status !== 'all') {
                    $bookings = array_filter($bookings, function($b) use ($status) {
                        return strcasecmp($b['status'], $status) === 0;
                    });
                } else {
                    // Hide cancelled by default in 'all' view
                    $bookings = array_filter($bookings, function($b) {
                        return !in_array(strtolower($b['status']), ['cancelled', 'refunded', 'no_show']);
                    });
                }
                $bookings = array_values($bookings);
            } else {
                // No date filter — show bookings (optionally filtered by status)
                $modelStatus = ($status === 'all') ? null : $status;
                $bookings = $this->bookingModel->getAllBookings($modelStatus);
                
                // If 'all' is selected, the model returns everything. Filter out cancelled for cleanliness.
                if ($status === 'all') {
                    $bookings = array_filter($bookings, function($b) {
                        return !in_array(strtolower($b['status']), ['cancelled', 'refunded', 'no_show']);
                    });
                    $bookings = array_values($bookings);
                }
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
            $endDate = sanitize_input($_POST['end_date'] ?? $bookingDate);
            $bookingType = sanitize_input($_POST['booking_type'] ?? 'hourly');
            $equipmentTier = sanitize_input($_POST['equipment_tier'] ?? '');
            $numberOfGuests = intval($_POST['number_of_guests'] ?? 1);

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
                        // Last resort: use space_id directly (getById handles both)
                        $facilityId = $spaceId;
                    }
                } catch (Exception $e) {
                    error_log('Booking create auto-sync error: ' . $e->getMessage());
                    // Use space_id as fallback
                    $facilityId = $spaceId;
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
                $_SESSION['_old_input'] = $_POST;
                
                redirect('bookings/create');
            }

            $facility = $this->facilityModel->getById($facilityId);
            if (!$facility) {
                $this->setFlashMessage('danger', 'Facility not found.');
                redirect('bookings/create');
            }

            // Calculate price based on booking type
            // For per-person types, quantity = number of guests
            $priceQuantity = in_array($bookingType, ['picnic', 'photoshoot', 'videoshoot', 'workspace']) ? $numberOfGuests : 1;
            $baseAmount = $this->facilityModel->calculatePrice($facilityId, $bookingDate, $startTime, $endTime, $bookingType, $priceQuantity, false, $endDate, $equipmentTier ?: null);

            // Calculate duration
            if ($bookingType === 'multi_day' && $endDate !== $bookingDate) {
                $startDT = new DateTime($bookingDate);
                $endDT = new DateTime($endDate);
                $interval = $endDT->diff($startDT);
                $days = $interval->days + 1; 
                $hours = $days * 24; 
            } else {
                $start = new DateTime($bookingDate . ' ' . $startTime);
                $end = new DateTime($bookingDate . ' ' . $endTime);
                $duration = $end->diff($start);
                $hours = $duration->h + ($duration->i / 60);
            }

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

            // Calculate VAT server-side — never trust client-submitted tax_amount
            $discountAmount = floatval($_POST['discount_amount'] ?? 0);
            $taxableAmount  = $baseAmount - $discountAmount;
            $taxRate        = 0;
            $taxAmount      = 0;
            try {
                $taxModel = $this->loadModel('Tax_type_model');
                if ($taxModel) {
                    $vatTax = $taxModel->getByCode('VAT') ?: ($taxModel->getAllActive()[0] ?? null);
                    if ($vatTax) {
                        $taxRate   = floatval($vatTax['rate'] ?? 0);
                        $taxCalc   = $taxModel->calculateTax($taxableAmount, $vatTax['id']);
                        $taxAmount = $taxCalc['tax_amount'] ?? 0;
                    }
                }
            } catch (Exception $taxEx) {
                error_log('Bookings create: tax calculation error - ' . $taxEx->getMessage());
            }
            $totalAmount = $taxableAmount + $taxAmount;

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
                'number_of_guests' => $numberOfGuests,
                'booking_type' => $bookingType,
                'equipment_tier' => $equipmentTier ?: null,
                'base_amount' => $baseAmount,
                'discount_amount' => $discountAmount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'security_deposit' => floatval($facility['security_deposit'] ?? 0),
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'balance_amount' => $totalAmount,
                'currency' => 'NGN',
                'status' => sanitize_input($_POST['status'] ?? 'pending'),
                'payment_status' => 'unpaid',
                'booking_notes' => sanitize_input($_POST['booking_notes'] ?? ''),
                'special_requests' => sanitize_input($_POST['special_requests'] ?? ''),
                'payment_plan' => sanitize_input($_POST['payment_plan'] ?? 'full'),
                'created_by' => $this->session['user_id']
            ];

            // Set payment deadline for part payment (3 days before event)
            if ($data['payment_plan'] === 'part') {
                $deadlineDate = new DateTime($bookingDate);
                $deadlineDate->modify('-3 days');
                $data['payment_deadline'] = $deadlineDate->format('Y-m-d');
            }
            
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
                $isNested = $this->db->inTransaction();
                if (!$isNested) $pdo->beginTransaction();

                $bookingId = $this->bookingModel->create($data);
                if (!$bookingId) {
                    error_log('Bookings controller: bookingModel->create returned false/0 for booking_number=' . ($data['booking_number'] ?? 'N/A') . ', total_amount=' . ($data['total_amount'] ?? 'N/A'));
                    throw new Exception('Failed to create booking');
                }

                // Create booking slots
                $this->bookingModel->createSlots($bookingId, $facilityId, $bookingDate, $startTime, $endDate, $endTime);

                // Process rental items if any were selected
                $rentalItems = $_POST['rental_items'] ?? [];
                if (!empty($rentalItems) && is_array($rentalItems)) {
                    $rentalModel = $this->loadModel('Booking_rental_model');
                    $rentalTotal = 0;
                    
                    foreach ($rentalItems as $rentalItem) {
                        $rItemId = intval($rentalItem['item_id'] ?? 0);
                        $rQuantity = intval($rentalItem['quantity'] ?? 0);
                        $rRate = floatval($rentalItem['rental_rate'] ?? 0);
                        
                        if ($rItemId > 0 && $rQuantity > 0 && $rRate > 0) {
                            $rentalModel->addRental($bookingId, $rItemId, $rQuantity, $rRate);
                            $rentalTotal += ($rRate * $rQuantity);
                        }
                    }
                    
                    // Update booking total with rental costs
                    if ($rentalTotal > 0) {
                        $newTotal = $data['total_amount'] + $rentalTotal;
                        $newBalance = $newTotal - $data['paid_amount'];
                        $this->bookingModel->update($bookingId, [
                            'total_amount' => $newTotal,
                            'balance_amount' => $newBalance
                        ]);
                    }
                }

                // If confirmed, recognize revenue (or create unearned revenue entry)
                if ($data['status'] === 'confirmed') {
                    $this->recognizeBookingRevenue($bookingId, $data);
                }

                // If confirmed and payment received, create payment record
                if ($data['status'] === 'confirmed' && !empty($_POST['initial_payment'])) {
                    $paymentAmount = floatval($_POST['initial_payment']);
                    $this->processPayment($bookingId, $paymentAmount, $_POST['payment_method'] ?? 'cash', 'deposit');
                }

                if (!$isNested) $pdo->commit();
                $this->activityModel->log($this->session['user_id'], 'create', 'Bookings', 'Created booking: ' . $data['booking_number']);
                $this->setFlashMessage('success', 'Booking created successfully.');
                redirect('bookings/view/' . $bookingId);
            } catch (Exception $e) {
                if (isset($pdo) && !$isNested && $this->db->inTransaction()) {
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

        // Load rentable items for the rental section
        $rentableItems = [];
        try {
            $rentalModel = $this->loadModel('Booking_rental_model');
            $rentableItems = $rentalModel->getRentableItems();
        } catch (Exception $e) {
            error_log('Bookings create: could not load rentable items: ' . $e->getMessage());
        }

        $data = [
            'page_title' => 'Create Booking',
            'locations' => $locations,
            'spaces_by_location' => $spacesByLocation,
            'rentable_items' => $rentableItems,
            'flash' => $this->getFlashMessage(),
            'old_input' => $_SESSION['_old_input'] ?? []
        ];
        
        // Clear the old input flash data after reading it
        unset($_SESSION['_old_input']);

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
                    'minimum_duration' => ($config && isset($config['minimum_duration'])) ? intval($config['minimum_duration']) : 1,
                    'maximum_duration' => ($config && !empty($config['maximum_duration'])) ? intval($config['maximum_duration']) : null,
                    'per_person_rates' => $pricingRules['per_person_rates'] ?? null,
                    'workspace_rates' => $pricingRules['workspace_rates'] ?? null
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

            // Always sync paid_amount/balance_amount from actual payment records
            $synced = $this->paymentModel->syncBookingBalance($id);
            if ($synced) {
                $booking['paid_amount']    = $synced['paid_amount'];
                $booking['balance_amount'] = $synced['balance_amount'];
                $booking['payment_status'] = $synced['payment_status'];
            }

            // Load rental items for this booking
            $rentalItems = [];
            try {
                $rentalModel = $this->loadModel('Booking_rental_model');
                $rentalItems = $rentalModel->getByBooking($id);
            } catch (Exception $e) {
                error_log('Bookings view: could not load rental items: ' . $e->getMessage());
            }

            // Load add-ons/extras for this booking
            $addonItems = [];
            try {
                $addonItems = $this->bookingAddonModel->getByBooking($id);
            } catch (Exception $e) {
                error_log('Bookings view: could not load add-ons: ' . $e->getMessage());
            }

            $data = [
                'page_title' => 'Booking Details - ' . $booking['booking_number'],
                'booking' => $booking,
                'payments' => $this->paymentModel->getByBooking($id),
                'addon_items' => $addonItems,
                'rental_items' => $rentalItems,
                'flash' => $this->getFlashMessage()
            ];

            $this->loadView('bookings/view', $data);
        } catch (Exception $e) {
            error_log('Bookings view error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading booking details.');
            redirect('bookings');
        }
    }

    public function invoice($id) {
        try {
            $booking = $this->bookingModel->getWithFacility($id);
            if (!$booking) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('bookings');
            }

            $rentalModel = $this->loadModel('Booking_rental_model');
            $rentals = $rentalModel->getByBooking($id);
            $addons = $this->bookingAddonModel->getByBooking($id);

            // Fetch business name from companies table
            $businessName = 'Business';
            try {
                $company = $this->db->fetchOne(
                    "SELECT name FROM `" . $this->db->getPrefix() . "companies` ORDER BY id ASC LIMIT 1"
                );
                if ($company && !empty($company['name'])) {
                    $businessName = $company['name'];
                }
            } catch (Exception $e) {
                // fall through to default
            }

            $data = [
                'page_title'    => 'Invoice - ' . $booking['booking_number'],
                'booking'       => $booking,
                'payments'      => $this->paymentModel->getByBooking($id),
                'addons'        => $addons,
                'rentals'       => $rentals,
                'business_name' => $businessName,
            ];

            $this->load->view('bookings/invoice', $data);
        } catch (Exception $e) {
            error_log('Bookings invoice error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error generating invoice.');
            redirect('bookings/view/' . $id);
        }
    }

    public function send_reminders() {
        // Only allow from CLI or specific secret key if called via URL
        if (PHP_SAPI !== 'cli' && ($_GET['key'] ?? '') !== 'REMIND_SECRET_KEY') {
            http_response_code(404);
            echo "404 Not Found";
            exit;
        }

        try {
            $today = date('Y-m-d');
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            
            // Find bookings where deadline is today or tomorrow and reminder not sent
            // AND payment_status is not 'paid'
            $bookings = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "bookings` 
                 WHERE payment_plan = 'part' 
                 AND payment_status != 'paid'
                 AND is_reminder_sent = 0
                 AND (payment_deadline = ? OR payment_deadline = ?)",
                [$today, $tomorrow]
            );

            $count = 0;
            foreach ($bookings as $booking) {
                // Send email logic here (placeholder)
                // $this->emailService->sendPaymentReminder($booking);
                
                // Mark as sent
                $this->bookingModel->update($booking['id'], ['is_reminder_sent' => 1]);
                $count++;
            }

            if (PHP_SAPI === 'cli') {
                echo "Sent $count reminders.\n";
            } else {
                echo "Sent $count reminders.";
            }
        } catch (Exception $e) {
            error_log('Bookings reminders error: ' . $e->getMessage());
            if (PHP_SAPI === 'cli') echo "Error: " . $e->getMessage() . "\n";
        }
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
                    'customer_id' => $booking['customer_id'] ?? null,
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
                
                $isSuperAdmin = (($this->session['role'] ?? '') === 'super_admin');
                $newCustomerId = intval($_POST['customer_id'] ?? ($booking['customer_id'] ?? 0));
                $oldCustomerId = intval($booking['customer_id'] ?? 0);
                $customerChanged = $isSuperAdmin && $newCustomerId > 0 && $newCustomerId !== $oldCustomerId;

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

                if ($isSuperAdmin && $newCustomerId > 0) {
                    $targetCustomer = $this->db->fetchOne(
                        "SELECT id, company_name, contact_name, email, phone, address, status
                         FROM `" . $this->db->getPrefix() . "customers`
                         WHERE id = ?",
                        [$newCustomerId]
                    );
                    if (!$targetCustomer || strtolower($targetCustomer['status'] ?? '') !== 'active') {
                        throw new Exception('Selected customer is invalid or inactive.');
                    }

                    $updateData['customer_id'] = $newCustomerId;
                    if ($customerChanged) {
                        // Keep booking-facing fields aligned to selected owner for clean history.
                        $updateData['customer_name'] = $targetCustomer['company_name'] ?: ($targetCustomer['contact_name'] ?: $updateData['customer_name']);
                        $updateData['customer_email'] = $targetCustomer['email'] ?? $updateData['customer_email'];
                        $updateData['customer_phone'] = $targetCustomer['phone'] ?? $updateData['customer_phone'];
                        $updateData['customer_address'] = $targetCustomer['address'] ?? $updateData['customer_address'];
                    }
                }
                
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

                if ($customerChanged) {
                    $invoiceIds = $this->resolveBookingInvoiceIds($booking, $id);
                    if (!empty($invoiceIds)) {
                        $placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));
                        $params = array_merge([$newCustomerId], $invoiceIds);
                        $this->db->query(
                            "UPDATE `" . $this->db->getPrefix() . "invoices`
                             SET customer_id = ?
                             WHERE id IN ({$placeholders})",
                            $params
                        );

                        $paymentRows = $this->db->fetchAll(
                            "SELECT DISTINCT payment_id
                             FROM `" . $this->db->getPrefix() . "payment_allocations`
                             WHERE invoice_id IN ({$placeholders})",
                            $invoiceIds
                        );
                        if (!empty($paymentRows)) {
                            $paymentIds = array_values(array_filter(array_map(function($row) {
                                return intval($row['payment_id'] ?? 0);
                            }, $paymentRows)));
                            if (!empty($paymentIds)) {
                                $paymentPlaceholders = implode(',', array_fill(0, count($paymentIds), '?'));
                                $paymentParams = array_merge([$newCustomerId], $paymentIds);
                                $this->db->query(
                                    "UPDATE `" . $this->db->getPrefix() . "payments`
                                     SET customer_id = ?
                                     WHERE id IN ({$paymentPlaceholders})",
                                    $paymentParams
                                );
                            }
                        }
                    }
                }
                
                // Log modification
                $newValues = json_encode([
                    'customer_id' => $updateData['customer_id'] ?? $oldCustomerId,
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

                if ($customerChanged) {
                    $this->activityModel->log(
                        $this->session['user_id'],
                        'update',
                        'Bookings',
                        'Reassigned booking customer for ' . $booking['booking_number'] . ' from customer #' . $oldCustomerId . ' to customer #' . $newCustomerId . ' (including linked invoices/payments)'
                    );
                }
                
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
            'customers' => (($this->session['role'] ?? '') === 'super_admin')
                ? $this->db->fetchAll(
                    "SELECT id, company_name, contact_name, email, phone, address
                     FROM `" . $this->db->getPrefix() . "customers`
                     WHERE status = 'active'
                     ORDER BY company_name ASC, contact_name ASC"
                )
                : [],
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('bookings/edit', $data);
    }

    public function createCustomerInline() {
        if (($this->session['role'] ?? '') !== 'super_admin') {
            $this->jsonResponse(['ok' => false, 'message' => 'Access denied. Super Admin only.'], 403);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['ok' => false, 'message' => 'Invalid request method.'], 405);
            return;
        }

        check_csrf();

        $companyName = sanitize_input($_POST['company_name'] ?? '');
        $contactName = sanitize_input($_POST['contact_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $address = sanitize_input($_POST['address'] ?? '');

        if ($companyName === '') {
            $this->jsonResponse(['ok' => false, 'message' => 'Company name is required.'], 422);
            return;
        }
        if ($email !== '' && !validate_email($email)) {
            $this->jsonResponse(['ok' => false, 'message' => 'Invalid email address.'], 422);
            return;
        }
        if ($phone !== '' && !validate_phone($phone)) {
            $this->jsonResponse(['ok' => false, 'message' => 'Invalid phone number.'], 422);
            return;
        }
        if ($phone !== '') {
            $phone = sanitize_phone($phone);
        }

        if ($email !== '') {
            $existing = $this->db->fetchOne(
                "SELECT id FROM `" . $this->db->getPrefix() . "customers` WHERE email = ? AND status = 'active'",
                [$email]
            );
            if ($existing) {
                $this->jsonResponse(['ok' => false, 'message' => 'A customer with this email already exists.'], 409);
                return;
            }
        }

        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            $createData = [
                'customer_code' => $this->customerModel->getNextCustomerCode(),
                'company_name' => $companyName,
                'contact_name' => $contactName,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'city' => sanitize_input($_POST['city'] ?? ''),
                'state' => sanitize_input($_POST['state'] ?? ''),
                'zip_code' => sanitize_input($_POST['zip_code'] ?? ''),
                'country' => sanitize_input($_POST['country'] ?? 'Nigeria'),
                'tax_id' => sanitize_input($_POST['tax_id'] ?? ''),
                'credit_limit' => floatval($_POST['credit_limit'] ?? 0),
                'payment_terms' => sanitize_input($_POST['payment_terms'] ?? ''),
                'currency' => sanitize_input($_POST['currency'] ?? 'NGN'),
                'status' => 'active'
            ];
            $customerId = $this->customerModel->create($createData);
            if (!$customerId) {
                throw new Exception('Failed to create customer.');
            }

            // Mirror receivables behavior: create customer ledger account when possible.
            $parentAr = $this->accountModel->getByCode('1200');
            if ($parentAr) {
                $suffix = str_pad($customerId, 4, '0', STR_PAD_LEFT);
                $newAccountCode = '1200-' . $suffix;
                if (!$this->accountModel->getByCode($newAccountCode)) {
                    $this->accountModel->create([
                        'account_code' => $newAccountCode,
                        'account_name' => $companyName,
                        'account_type' => 'Assets',
                        'parent_account_id' => $parentAr['id'],
                        'is_system' => 0,
                        'status' => 'active',
                        'created_by' => $this->session['user_id']
                    ]);
                }
            }

            $pdo->commit();
            $this->activityModel->log($this->session['user_id'], 'create', 'Bookings', 'Inline customer created during booking edit: ' . $companyName);

            $this->jsonResponse([
                'ok' => true,
                'customer' => [
                    'id' => intval($customerId),
                    'company_name' => $companyName,
                    'contact_name' => $contactName,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address
                ]
            ]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $pdo->rollBack();
            }
            $this->jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function resolveBookingInvoiceIds(array $booking, int $bookingId): array {
        $invoiceIds = [];
        $directId = intval($booking['invoice_id'] ?? 0);
        if ($directId > 0) {
            $invoiceIds[] = $directId;
        }

        $byReference = $this->db->fetchAll(
            "SELECT id FROM `" . $this->db->getPrefix() . "invoices` WHERE reference = ?",
            ['BKG-' . $bookingId]
        );
        foreach ($byReference as $row) {
            $rowId = intval($row['id'] ?? 0);
            if ($rowId > 0) {
                $invoiceIds[] = $rowId;
            }
        }

        return array_values(array_unique($invoiceIds));
    }

    protected function jsonResponse($data, $statusCode = 200) {
        parent::jsonResponse($data, $statusCode);
    }

    public function recordPayment() {
        $this->requirePermission('bookings', 'update');

        $bookingId = intval($_POST['booking_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'cash');
        $paymentType = sanitize_input($_POST['payment_type'] ?? 'partial');
        $paymentDate = sanitize_input($_POST['payment_date'] ?? date('Y-m-d'));

        if (!$bookingId || $amount <= 0) {
            $this->setFlashMessage('danger', 'Invalid payment details.');
            redirect('bookings');
        }

        try {
            $this->processPayment($bookingId, $amount, $paymentMethod, $paymentType, $paymentDate);
            $this->setFlashMessage('success', 'Payment recorded successfully.');
            redirect('bookings/view/' . $bookingId);
        } catch (Exception $e) {
            error_log('Bookings recordPayment error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Failed to record payment: ' . $e->getMessage());
            redirect('bookings/view/' . $bookingId);
        }
    }

    private function processPayment($bookingId, $amount, $paymentMethod, $paymentType, $paymentDate = null) {
        if (!$paymentDate) $paymentDate = date('Y-m-d');
        
        $pdo = $this->db->getConnection();
        $isNested = $this->db->inTransaction();
        if (!$isNested) $pdo->beginTransaction();

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
                'payment_date' => $paymentDate,
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
                    $activeCashAccounts = $this->cashAccountModel->getActive();
                    $defaultCashAccount = !empty($activeCashAccounts) ? $activeCashAccounts[0] : null;
                    if ($defaultCashAccount) {
                        // Debit Cash/Bank (asset increases)
                        $payTxnBase = 'PAY-' . date('Ymd', strtotime($paymentDate)) . '-' . str_pad($bookingId, 6, '0', STR_PAD_LEFT);
                        $this->transactionModel->create([
                            'transaction_number' => $payTxnBase . '-CASH',
                            'account_id' => $defaultCashAccount['account_id'] ?? $defaultCashAccount['id'],
                            'debit' => $amount,
                            'credit' => 0,
                            'description' => ucfirst($paymentMethod) . ' payment for booking: ' . ($booking['booking_number'] ?? $bookingId) . ' (Service Date: ' . $booking['booking_date'] . ')',
                            'reference_type' => 'booking_payment',
                            'reference_id' => $paymentId,
                            'transaction_date' => $paymentDate,
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
                                    'transaction_number' => $payTxnBase . '-AR',
                                    'account_id' => $arAccount['id'],
                                    'debit' => 0,
                                    'credit' => $amount,
                                    'description' => 'Payment received - booking: ' . ($booking['booking_number'] ?? $bookingId) . ' (Service Date: ' . $booking['booking_date'] . ')',
                                    'reference_type' => 'booking_payment',
                                    'reference_id' => $paymentId,
                                    'transaction_date' => $paymentDate,
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

            if (!$isNested) $pdo->commit();

            // Sync paid_amount/balance_amount from actual payment records (self-healing)
            $this->paymentModel->syncBookingBalance($bookingId);
            
            // Log activity
            $this->activityModel->log(
                $this->session['user_id'], 
                'create', 
                'Booking Payments', 
                'Recorded payment of ' . number_format($amount, 2) . ' for booking: ' . ($booking['booking_number'] ?? $bookingId)
            );
            
            return $paymentId;
            
        } catch (Exception $e) {
            if (isset($pdo) && !$isNested && $this->db->inTransaction()) {
                $pdo->rollBack();
            }
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
                
                // Delete slots to free up the calendar
                $this->db->delete('booking_slots', "booking_id = ?", [$id]);
                
                // Log modification
                $this->bookingModificationModel->logModification(
                    $id,
                    'status_change',
                    $booking['status'],
                    'cancelled',
                    $reason,
                    $this->session['user_id']
                );
                
                // Remove booking-linked journal entries on cancellation
                if ($this->journalCleanupService) {
                    $this->journalCleanupService->deleteBookingJournalEntries($id);
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
                    // Remove booking-linked journal entries on cancellation
                    if ($this->journalCleanupService) {
                        $this->journalCleanupService->deleteBookingJournalEntries($id);
                    }
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
    
    /**
     * Get available time slots by facility_id — used by reschedule views
     * Accepts: facility_id, date, exclude_booking_id
     */
    public function getSlots() {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');

        $facilityId       = intval($_GET['facility_id'] ?? 0);
        $date             = sanitize_input($_GET['date'] ?? '');
        $excludeBookingId = intval($_GET['exclude_booking_id'] ?? 0);

        // Normalise date format
        $dt = DateTime::createFromFormat('d/m/Y', $date);
        if ($dt && $dt->format('d/m/Y') === $date) $date = $dt->format('Y-m-d');

        // If facility_id is 0 but we have a booking_id, look it up
        if (!$facilityId && $excludeBookingId) {
            try {
                $booking = $this->bookingModel->getById($excludeBookingId);
                if ($booking) {
                    $facilityId = intval($booking['facility_id'] ?? 0);
                    // If still 0, try space_id → facility_id via spaces table
                    if (!$facilityId && !empty($booking['space_id'])) {
                        $space = $this->db->fetchOne(
                            "SELECT facility_id FROM `" . $this->db->getPrefix() . "spaces` WHERE id = ?",
                            [$booking['space_id']]
                        );
                        $facilityId = intval($space['facility_id'] ?? 0);
                    }
                }
            } catch (Exception $e) {
                error_log('Bookings getSlots lookup error: ' . $e->getMessage());
            }
        }

        if (!$facilityId || !$date) {
            echo json_encode(['success' => false, 'message' => 'Missing facility_id or date. facility_id=' . $facilityId . ' date=' . $date]);
            exit;
        }

        try {
            $result = $this->facilityModel->getAvailableTimeSlots($facilityId, $date, $date);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
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
            $bookingRevenueAccount = $this->accountModel->getByCode('4000');
            if (!$bookingRevenueAccount) {
                $revenueAccounts = $this->accountModel->getByType('Revenue');
                $bookingRevenueAccount = !empty($revenueAccounts) ? $revenueAccounts[0] : null;
            }
            if (!$bookingRevenueAccount) {
                error_log('recognizeBookingRevenue: No revenue account found, skipping journal entry.');
                return;
            }

            $totalAmount = floatval($booking['total_amount'] ?? 0);
            if ($totalAmount <= 0) return;

            $taxAmount  = floatval($booking['tax_amount'] ?? 0);
            $netRevenue = $totalAmount - $taxAmount;

            // Build a balanced journal: DR Accounts Receivable = CR Revenue + CR VAT
            $arAccount = $this->accountModel->getByCode('1200');
            if (!$arAccount) {
                $arAccounts = $this->accountModel->getByType('Assets');
                foreach ($arAccounts as $acc) {
                    if (stripos($acc['account_name'], 'receivable') !== false) { $arAccount = $acc; break; }
                }
            }

            if (!$arAccount) {
                error_log('recognizeBookingRevenue: No AR account found, skipping journal entry.');
                return;
            }

            $entries = [
                // DR Accounts Receivable (full invoice amount)
                [
                    'account_id' => $arAccount['id'],
                    'debit'      => $totalAmount,
                    'credit'     => 0,
                    'description' => 'Accounts Receivable - Booking #' . ($booking['booking_number'] ?? $bookingId)
                ],
                // CR Revenue (net of VAT)
                [
                    'account_id' => $bookingRevenueAccount['id'],
                    'debit'      => 0,
                    'credit'     => $netRevenue,
                    'description' => 'Booking Revenue - Booking #' . ($booking['booking_number'] ?? $bookingId)
                ],
            ];

            // CR VAT Payable
            if ($taxAmount > 0) {
                $vatAccount = $this->accountModel->getOrCreateVatAccount();
                if ($vatAccount) {
                    $entries[] = [
                        'account_id' => $vatAccount['id'],
                        'debit'      => 0,
                        'credit'     => $taxAmount,
                        'description' => 'VAT Payable - Booking #' . ($booking['booking_number'] ?? $bookingId)
                    ];
                } else {
                    // No VAT account — add to revenue to keep entries balanced
                    $entries[1]['credit'] += $taxAmount;
                }
            }

            $this->transactionService->postJournalEntry([
                'date'           => date('Y-m-d'),
                'reference_type' => 'booking_revenue',
                'reference_id'   => $bookingId,
                'description'    => 'Booking Revenue - #' . ($booking['booking_number'] ?? $bookingId),
                'journal_type'   => 'sales',
                'entries'        => $entries,
                'created_by'     => $this->session['user_id'] ?? null,
                'auto_post'      => true
            ]);

        } catch (Exception $e) {
            // Non-fatal — log but never block booking creation
            error_log('Bookings recognizeBookingRevenue error: ' . $e->getMessage());
        }
    }

    /**
     * Auto-complete bookings whose booking_date has passed and are still confirmed/in_progress
     */
    private function autoCompleteExpiredBookings() {
        try {
            $today = date('Y-m-d');

            // Find all bookings that should be auto-completed
            $expired = $this->db->fetchAll(
                "SELECT id, booking_number FROM `" . $this->db->getPrefix() . "bookings`
                 WHERE status IN ('confirmed', 'in_progress')
                 AND booking_date < ?",
                [$today]
            );

            if (empty($expired)) {
                return;
            }

            $ids = array_column($expired, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $this->db->query(
                "UPDATE `" . $this->db->getPrefix() . "bookings`
                 SET status = 'completed', completed_at = NOW()
                 WHERE id IN ({$placeholders})",
                $ids
            );

            foreach ($expired as $booking) {
                $this->finalizeBookingRevenue($booking['id']);
            }
        } catch (Exception $e) {
            error_log('Bookings autoCompleteExpiredBookings error: ' . $e->getMessage());
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
    
    /**
     * Check out a rental item for a booking
     */
    public function checkoutRental($rentalId) {
        $this->requirePermission('bookings', 'update');
        
        try {
            $rentalModel = $this->loadModel('Booking_rental_model');
            $rental = $rentalModel->getById($rentalId);
            
            if (!$rental) {
                $this->setFlashMessage('danger', 'Rental item not found.');
                redirect('bookings');
            }
            
            $rentalModel->checkout($rentalId);
            $this->activityModel->log($this->session['user_id'], 'update', 'Bookings', 'Checked out rental item for booking ID: ' . $rental['booking_id']);
            $this->setFlashMessage('success', 'Equipment marked as checked out.');
            
            redirect('bookings/view/' . $rental['booking_id']);
        } catch (Exception $e) {
            error_log('Bookings checkoutRental error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Failed to check out equipment: ' . $e->getMessage());
            redirect('bookings');
        }
    }

    /**
     * Return a rental item for a booking
     */
    public function returnRental($rentalId) {
        $this->requirePermission('bookings', 'update');
        
        try {
            $rentalModel = $this->loadModel('Booking_rental_model');
            $rental = $rentalModel->getById($rentalId);
            
            if (!$rental) {
                $this->setFlashMessage('danger', 'Rental item not found.');
                redirect('bookings');
            }
            
            $condition = $_POST['condition'] ?? 'good';
            $notes = sanitize_input($_POST['notes'] ?? '');
            
            $rentalModel->returnItem($rentalId, $condition, $notes);
            $this->activityModel->log($this->session['user_id'], 'update', 'Bookings', 'Returned rental item for booking ID: ' . $rental['booking_id']);
            $this->setFlashMessage('success', 'Equipment marked as returned.');
            
            redirect('bookings/view/' . $rental['booking_id']);
        } catch (Exception $e) {
            error_log('Bookings returnRental error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Failed to return equipment: ' . $e->getMessage());
            redirect('bookings');
        }
    }

    /**
     * Delete a booking - Super Admin only
     * Exhaustive cleanup of all related records
     */
    public function delete($id) {
        if (($this->session['role'] ?? '') !== 'super_admin') {
            $this->setFlashMessage('danger', 'Access denied. Super Admin only.');
            redirect('bookings');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('bookings');
            return;
        }

        check_csrf();
        $id = intval($id);
        
        try {
            $booking = $this->bookingModel->getById($id);
            if (!$booking) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('bookings');
                return;
            }

            $this->db->beginTransaction();

            // 1. Handle Invoices and related accounting
            $invoiceId = $booking['invoice_id'] ?? null;
            if (!$invoiceId) {
                // Try finding by reference if invoice_id is null
                $invoice = $this->db->fetchOne(
                    "SELECT id FROM `" . $this->db->getPrefix() . "invoices` WHERE reference = ?",
                    ['BKG-' . $id]
                );
                $invoiceId = $invoice ? $invoice['id'] : null;
            }

            if ($invoiceId) {
                // Delete Invoice Items
                $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "invoice_items` WHERE invoice_id = ?", [$invoiceId]);
                
                // Delete Transactions linked to invoice
                $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "transactions` WHERE reference_type = 'invoice' AND reference_id = ?", [$invoiceId]);
                
                // Delete Journal Entries linked to invoice
                $journals = $this->db->fetchAll("SELECT id FROM `" . $this->db->getPrefix() . "journal_entries` WHERE reference = ?", ['booking_invoice:' . $invoiceId]);
                foreach ($journals as $j) {
                    $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "journal_entry_lines` WHERE journal_entry_id = ?", [$j['id']]);
                    $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "journal_entries` WHERE id = ?", [$j['id']]);
                }
                
                // Finally delete invoice
                $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "invoices` WHERE id = ?", [$invoiceId]);
            }

            // 2. Handle Booking Payments and related accounting
            $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "transactions` WHERE reference_type = 'booking_payment' AND reference_id = ?", [$id]);
            
            $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "booking_payments` WHERE booking_id = ?", [$id]);

            // 3. Delete Metadata
            $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "booking_slots` WHERE booking_id = ?", [$id]);
            $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "booking_resources` WHERE booking_id = ?", [$id]);
            $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "booking_addons` WHERE booking_id = ?", [$id]);
            $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "payment_schedule` WHERE booking_id = ?", [$id]);
            $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "booking_rentals` WHERE booking_id = ?", [$id]);

            // 4. Delete Booking
            if ($this->bookingModel->delete($id)) {
                $this->activityModel->log($this->session['user_id'], 'delete', 'Bookings', 'Exhaustive delete for booking: ' . $booking['booking_number']);
                $this->db->commit();
                $this->setFlashMessage('success', 'Booking and all associated records deleted successfully.');
            } else {
                $this->db->rollBack();
                $this->setFlashMessage('danger', 'Failed to delete booking record.');
            }

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Bookings delete error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error deleting booking: ' . $e->getMessage());
        }

        redirect('bookings');
    }
}

