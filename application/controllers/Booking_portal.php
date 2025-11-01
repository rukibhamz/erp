<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_portal extends Base_Controller {
    private $facilityModel;
    private $bookingModel;

    public function __construct() {
        // Call parent constructor which will check auth, but Booking_portal is in publicControllers list
        parent::__construct();
        
        // Load models
        try {
            $this->facilityModel = $this->loadModel('Facility_model');
            $this->bookingModel = $this->loadModel('Booking_model');
        } catch (Exception $e) {
            error_log('Booking_portal constructor error: ' . $e->getMessage());
        }
    }
    
    protected function requirePermission($module, $action) {
        // Public controller - skip permission checks
        return true;
    }
    
    protected function loadView($view, $data = []) {
        $data['config'] = $this->config;
        $data['session'] = $this->session;
        
        $this->loader->view('layouts/header_public', $data);
        $this->loader->view($view, $data);
        $this->loader->view('layouts/footer_public', $data);
    }
    
    protected function setFlashMessage($type, $message) {
        $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
    }
    
    protected function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $flash;
        }
        return null;
    }

    public function index() {
        try {
            $facilities = $this->facilityModel->getActive();
        } catch (Exception $e) {
            $facilities = [];
        }

        $data = [
            'page_title' => 'Book a Facility',
            'facilities' => $facilities
        ];

        $this->loadView('booking_portal/index', $data);
    }

    public function facility($facilityId) {
        try {
            $facility = $this->facilityModel->getWithPhotos($facilityId);
            if (!$facility || $facility['status'] !== 'active') {
                redirect('booking-portal');
            }
        } catch (Exception $e) {
            redirect('booking-portal');
        }

        $data = [
            'page_title' => 'Book: ' . $facility['facility_name'],
            'facility' => $facility
        ];

        $this->loadView('booking_portal/facility', $data);
    }

    public function checkAvailability() {
        header('Content-Type: application/json');
        
        $facilityId = intval($_GET['facility_id'] ?? 0);
        $date = sanitize_input($_GET['date'] ?? '');
        
        if (!$facilityId || !$date) {
            echo json_encode(['available' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        try {
            // Get all bookings for this facility on this date
            $bookings = $this->bookingModel->getByDateRange($date, $date, $facilityId);
            
            // Generate available time slots
            $facility = $this->facilityModel->getById($facilityId);
            $minDuration = $facility['minimum_duration'] ?? 1;
            
            $bookedSlots = [];
            foreach ($bookings as $booking) {
                if ($booking['status'] !== 'cancelled') {
                    $bookedSlots[] = [
                        'start' => $booking['start_time'],
                        'end' => $booking['end_time']
                    ];
                }
            }
            
            // Generate hourly slots from 8 AM to 10 PM
            $availableSlots = [];
            for ($hour = 8; $hour < 22; $hour++) {
                $slotStart = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                $slotEnd = str_pad($hour + $minDuration, 2, '0', STR_PAD_LEFT) . ':00';
                
                // Check if this slot conflicts with any booking
                $isAvailable = true;
                foreach ($bookedSlots as $booked) {
                    if (!($slotEnd <= $booked['start'] || $slotStart >= $booked['end'])) {
                        $isAvailable = false;
                        break;
                    }
                }
                
                if ($isAvailable) {
                    $availableSlots[] = [
                        'start' => $slotStart,
                        'end' => $slotEnd,
                        'display' => date('g:i A', strtotime($slotStart)) . ' - ' . date('g:i A', strtotime($slotEnd))
                    ];
                }
            }
            
            echo json_encode([
                'available' => true,
                'slots' => $availableSlots
            ]);
        } catch (Exception $e) {
            echo json_encode(['available' => false, 'message' => 'Error checking availability']);
        }
        exit;
    }

    public function submitBooking() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $facilityId = intval($_POST['facility_id'] ?? 0);
        $bookingDate = sanitize_input($_POST['booking_date'] ?? '');
        $startTime = sanitize_input($_POST['start_time'] ?? '');
        $endTime = sanitize_input($_POST['end_time'] ?? '');
        $bookingType = sanitize_input($_POST['booking_type'] ?? 'hourly');

        // Validate
        if (!$facilityId || !$bookingDate || !$startTime || !$endTime) {
            echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
            exit;
        }

        // Check availability
        if (!$this->facilityModel->checkAvailability($facilityId, $bookingDate, $startTime, $endTime)) {
            echo json_encode(['success' => false, 'message' => 'Selected time slot is no longer available']);
            exit;
        }

        $facility = $this->facilityModel->getById($facilityId);
        if (!$facility) {
            echo json_encode(['success' => false, 'message' => 'Facility not found']);
            exit;
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
            'discount_amount' => 0,
            'tax_amount' => 0,
            'security_deposit' => floatval($facility['security_deposit'] ?? 0),
            'total_amount' => $baseAmount + floatval($facility['security_deposit'] ?? 0),
            'paid_amount' => 0,
            'balance_amount' => $baseAmount + floatval($facility['security_deposit'] ?? 0),
            'currency' => 'NGN',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'booking_notes' => sanitize_input($_POST['booking_notes'] ?? ''),
            'special_requests' => sanitize_input($_POST['special_requests'] ?? ''),
            'created_by' => null // Public booking
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

            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Booking submitted successfully. We will contact you shortly.',
                'booking_number' => $data['booking_number']
            ]);
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log('Booking_portal submitBooking error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to submit booking. Please try again.']);
        }
        exit;
    }
}

