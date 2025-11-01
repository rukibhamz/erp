<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_reports extends Base_Controller {
    private $bookingModel;
    private $facilityModel;
    private $paymentModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('bookings', 'read');
        $this->bookingModel = $this->loadModel('Booking_model');
        $this->facilityModel = $this->loadModel('Facility_model');
        $this->paymentModel = $this->loadModel('Booking_payment_model');
    }

    public function index() {
        $data = [
            'page_title' => 'Booking Reports',
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_reports/index', $data);
    }

    public function revenue() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        try {
            $revenueByFacility = $this->bookingModel->getRevenueByFacility($startDate, $endDate);
            
            $totalRevenue = 0;
            $totalPaid = 0;
            $totalPending = 0;
            foreach ($revenueByFacility as $item) {
                $totalRevenue += floatval($item['total_revenue']);
                $totalPaid += floatval($item['paid_revenue']);
                $totalPending += floatval($item['pending_revenue']);
            }
        } catch (Exception $e) {
            $revenueByFacility = [];
            $totalRevenue = 0;
            $totalPaid = 0;
            $totalPending = 0;
        }

        $data = [
            'page_title' => 'Revenue by Facility',
            'revenue_by_facility' => $revenueByFacility,
            'total_revenue' => $totalRevenue,
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_reports/revenue', $data);
    }

    public function utilization() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        try {
            $facilities = $this->facilityModel->getActive();
            $utilization = [];
            
            // Calculate total available hours (assuming 14 hours per day: 8 AM to 10 PM)
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $days = $end->diff($start)->days + 1;
            $totalAvailableHours = $days * 14;
            
            foreach ($facilities as $facility) {
                $bookings = $this->bookingModel->getByDateRange($startDate, $endDate, $facility['id']);
                
                $bookedHours = 0;
                $totalBookings = 0;
                foreach ($bookings as $booking) {
                    if ($booking['status'] !== 'cancelled') {
                        $bookedHours += floatval($booking['duration_hours']);
                        $totalBookings++;
                    }
                }
                
                $utilizationPercent = $totalAvailableHours > 0 ? ($bookedHours / $totalAvailableHours) * 100 : 0;
                
                $utilization[] = [
                    'facility' => $facility,
                    'total_bookings' => $totalBookings,
                    'booked_hours' => $bookedHours,
                    'available_hours' => $totalAvailableHours,
                    'utilization_percent' => $utilizationPercent
                ];
            }
        } catch (Exception $e) {
            $utilization = [];
        }

        $data = [
            'page_title' => 'Facility Utilization Report',
            'utilization' => $utilization,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_reports/utilization', $data);
    }

    public function customerHistory() {
        $customerEmail = $_GET['email'] ?? '';
        $customerPhone = $_GET['phone'] ?? '';
        
        try {
            $bookings = [];
            
            if ($customerEmail || $customerPhone) {
                $sql = "SELECT b.*, f.facility_name 
                        FROM `" . $this->db->getPrefix() . "bookings` b
                        JOIN `" . $this->db->getPrefix() . "facilities` f ON b.facility_id = f.id
                        WHERE 1=1";
                $params = [];
                
                if ($customerEmail) {
                    $sql .= " AND b.customer_email = ?";
                    $params[] = $customerEmail;
                }
                
                if ($customerPhone) {
                    $sql .= " AND b.customer_phone = ?";
                    $params[] = $customerPhone;
                }
                
                $sql .= " ORDER BY b.booking_date DESC, b.created_at DESC";
                
                $bookings = $this->db->fetchAll($sql, $params);
            }
        } catch (Exception $e) {
            $bookings = [];
        }

        $data = [
            'page_title' => 'Customer Booking History',
            'bookings' => $bookings,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_reports/customer_history', $data);
    }

    public function pendingPayments() {
        try {
            $bookings = $this->bookingModel->getByStatus('confirmed');
            
            $pendingPayments = [];
            foreach ($bookings as $booking) {
                if (floatval($booking['balance_amount']) > 0) {
                    $pendingPayments[] = $booking;
                }
            }
        } catch (Exception $e) {
            $pendingPayments = [];
        }

        $data = [
            'page_title' => 'Pending Payments Report',
            'pending_payments' => $pendingPayments,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_reports/pending_payments', $data);
    }
}

