<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Space_bookings extends Base_Controller {
    private $bookingModel;
    private $spaceModel;
    private $tenantModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('locations', 'read');
        $this->bookingModel = $this->loadModel('Space_booking_model');
        $this->spaceModel = $this->loadModel('Space_model');
        $this->tenantModel = $this->loadModel('Tenant_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $this->requirePermission('locations', 'read');
        
        try {
            $bookings = $this->bookingModel->getAllWithDetails();
            // Format tenant name for display
            foreach ($bookings as &$booking) {
                $booking['tenant_name'] = $booking['business_name'] ?? $booking['contact_person'] ?? 'N/A';
            }
        } catch (Exception $e) {
            $bookings = [];
        }
        
        $data = [
            'page_title' => 'Space Bookings',
            'bookings' => $bookings,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('space_bookings/index', $data);
    }
    
    public function create($spaceId = null) {
        $this->requirePermission('locations', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $spaceId = intval($_POST['space_id'] ?? 0);
            $tenantId = intval($_POST['tenant_id'] ?? 0);
            $bookingDate = sanitize_input($_POST['booking_date'] ?? '');
            $startTime = sanitize_input($_POST['start_time'] ?? '');
            $endTime = sanitize_input($_POST['end_time'] ?? '');
            $numberOfGuests = intval($_POST['number_of_guests'] ?? 0);
            $bookingNotes = sanitize_input($_POST['booking_notes'] ?? '');
            $specialRequests = sanitize_input($_POST['special_requests'] ?? '');
            
            // Validate required fields
            if (!$spaceId || !$tenantId || !$bookingDate || !$startTime || !$endTime) {
                $this->setFlashMessage('danger', 'Please fill in all required fields.');
                redirect('space-bookings/create' . ($spaceId ? '/' . $spaceId : ''));
            }
            
            // Check availability
            if (!$this->bookingModel->checkAvailability($spaceId, $bookingDate, $startTime, $endTime)) {
                $this->setFlashMessage('danger', 'The selected time slot is not available. Please choose another time.');
                redirect('space-bookings/create/' . $spaceId);
            }
            
            // Calculate duration
            $start = new DateTime($bookingDate . ' ' . $startTime);
            $end = new DateTime($bookingDate . ' ' . $endTime);
            $duration = $end->diff($start);
            $durationHours = $duration->h + ($duration->i / 60);
            
            // Get space for pricing (default rate if not set)
            $space = $this->spaceModel->getById($spaceId);
            // Try to get hourly rate from space, or use default
            $hourlyRate = floatval($space['hourly_rate'] ?? 5000); // Default 5000 per hour
            $baseAmount = $hourlyRate * $durationHours;
            
            $data = [
                'booking_number' => $this->bookingModel->getNextBookingNumber(),
                'space_id' => $spaceId,
                'tenant_id' => $tenantId,
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_hours' => $durationHours,
                'number_of_guests' => $numberOfGuests,
                'booking_type' => 'hourly',
                'base_amount' => $baseAmount,
                'total_amount' => $baseAmount,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'booking_notes' => $bookingNotes,
                'special_requests' => $specialRequests,
                'created_by' => $this->session['user_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->bookingModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Space Bookings', 'Created booking: ' . $data['booking_number']);
                $this->setFlashMessage('success', 'Booking created successfully.');
                redirect('space-bookings');
            } else {
                $this->setFlashMessage('danger', 'Failed to create booking.');
            }
        }
        
        try {
            $spaces = $this->spaceModel->getAll();
            $tenants = $this->tenantModel->getAll();
            
            $selectedSpace = null;
            if ($spaceId) {
                $selectedSpace = $this->spaceModel->getById($spaceId);
            }
        } catch (Exception $e) {
            $spaces = [];
            $tenants = [];
            $selectedSpace = null;
        }
        
        $data = [
            'page_title' => 'Create Space Booking',
            'spaces' => $spaces,
            'tenants' => $tenants,
            'selected_space' => $selectedSpace,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('space_bookings/create', $data);
    }
    
    public function view($id) {
        $this->requirePermission('locations', 'read');
        
        try {
            $booking = $this->bookingModel->getById($id);
            if (!$booking) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('space-bookings');
            }
            
            $space = $this->spaceModel->getById($booking['space_id']);
            $tenant = $this->tenantModel->getById($booking['tenant_id']);
            
            $booking['space'] = $space;
            $booking['tenant'] = $tenant;
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading booking details.');
            redirect('space-bookings');
        }
        
        $data = [
            'page_title' => 'Booking: ' . $booking['booking_number'],
            'booking' => $booking,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('space_bookings/view', $data);
    }
    
    public function calendar($spaceId = null) {
        $this->requirePermission('locations', 'read');
        
        try {
            $spaces = $this->spaceModel->getAll();
            $selectedSpace = null;
            $bookings = [];
            
            // Get space_id from query string if not in URL
            if (!$spaceId && !empty($_GET['space_id'])) {
                $spaceId = intval($_GET['space_id']);
            }
            
            if ($spaceId) {
                $selectedSpace = $this->spaceModel->getById($spaceId);
                if ($selectedSpace) {
                    // Get bookings for the next 30 days
                    $startDate = date('Y-m-d');
                    $endDate = date('Y-m-d', strtotime('+30 days'));
                    $bookingsData = $this->bookingModel->getAvailabilityCalendar($spaceId, $startDate, $endDate);
                    
                    // Format bookings for display (tenant info already included from model)
                    $bookings = [];
                    foreach ($bookingsData as $booking) {
                        $bookings[] = [
                            'booking_date' => $booking['booking_date'],
                            'start_time' => $booking['start_time'],
                            'end_time' => $booking['end_time'],
                            'status' => $booking['status'],
                            'tenant_name' => $booking['business_name'] ?? $booking['contact_person'] ?? 'N/A'
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            $spaces = [];
            $selectedSpace = null;
            $bookings = [];
        }
        
        $data = [
            'page_title' => 'Booking Calendar',
            'spaces' => $spaces,
            'selected_space' => $selectedSpace,
            'bookings' => $bookings,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('space_bookings/calendar', $data);
    }
    
    public function checkAvailability() {
        $this->requirePermission('locations', 'read');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['available' => false, 'error' => 'Method not allowed']);
            return;
        }
        
        $spaceId = intval($_POST['space_id'] ?? 0);
        $bookingDate = sanitize_input($_POST['booking_date'] ?? '');
        $startTime = sanitize_input($_POST['start_time'] ?? '');
        $endTime = sanitize_input($_POST['end_time'] ?? '');
        $excludeBookingId = !empty($_POST['exclude_booking_id']) ? intval($_POST['exclude_booking_id']) : null;
        
        if (!$spaceId || !$bookingDate || !$startTime || !$endTime) {
            echo json_encode(['available' => false, 'error' => 'Missing required parameters']);
            return;
        }
        
        $available = $this->bookingModel->checkAvailability($spaceId, $bookingDate, $startTime, $endTime, $excludeBookingId);
        
        echo json_encode(['available' => $available]);
    }
    
    public function confirm($id) {
        $this->requirePermission('locations', 'update');
        
        try {
            $booking = $this->bookingModel->getById($id);
            if (!$booking) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('space-bookings');
            }
            
            if ($this->bookingModel->update($id, [
                'status' => 'confirmed',
                'confirmed_at' => date('Y-m-d H:i:s')
            ])) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Space Bookings', 'Confirmed booking: ' . $booking['booking_number']);
                $this->setFlashMessage('success', 'Booking confirmed successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to confirm booking.');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error confirming booking.');
        }
        
        redirect('space-bookings/view/' . $id);
    }
    
    public function cancel($id) {
        $this->requirePermission('locations', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $cancellationReason = sanitize_input($_POST['cancellation_reason'] ?? '');
            
            try {
                $booking = $this->bookingModel->getById($id);
                if (!$booking) {
                    $this->setFlashMessage('danger', 'Booking not found.');
                    redirect('space-bookings');
                }
                
                if ($this->bookingModel->update($id, [
                    'status' => 'cancelled',
                    'cancellation_reason' => $cancellationReason,
                    'cancelled_at' => date('Y-m-d H:i:s')
                ])) {
                    $this->activityModel->log($this->session['user_id'], 'update', 'Space Bookings', 'Cancelled booking: ' . $booking['booking_number']);
                    $this->setFlashMessage('success', 'Booking cancelled successfully.');
                } else {
                    $this->setFlashMessage('danger', 'Failed to cancel booking.');
                }
            } catch (Exception $e) {
                $this->setFlashMessage('danger', 'Error cancelling booking.');
            }
        }
        
        redirect('space-bookings/view/' . $id);
    }
}

