<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_reports extends Base_Controller {
    private $bookingModel;
    private $facilityModel;
    private $bookingPaymentModel;
    private $customerPortalUserModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('bookings', 'read');
        $this->bookingModel = $this->loadModel('Booking_model');
        $this->facilityModel = $this->loadModel('Facility_model');
        $this->bookingPaymentModel = $this->loadModel('Booking_payment_model');
        $this->customerPortalUserModel = $this->loadModel('Customer_portal_user_model');
    }
    
    public function index() {
        // Get date range from request or default to current month
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        try {
            // Quick stats
            $totalBookings = count($this->bookingModel->getByDateRange($startDate, $endDate));
            $totalRevenue = array_sum(array_column(
                $this->bookingModel->getByDateRange($startDate, $endDate), 
                'total_amount'
            ));
            $paidRevenue = array_sum(array_column(
                $this->bookingModel->getByDateRange($startDate, $endDate), 
                'paid_amount'
            ));
            $pendingRevenue = $totalRevenue - $paidRevenue;
            
            // Revenue by facility
            $revenueByFacility = $this->bookingModel->getRevenueByFacility($startDate, $endDate);
            
            // Bookings by status
            $bookingsByStatus = [
                'pending' => count($this->bookingModel->getByStatus('pending')),
                'confirmed' => count($this->bookingModel->getByStatus('confirmed')),
                'completed' => count($this->bookingModel->getByStatus('completed')),
                'cancelled' => count($this->bookingModel->getByStatus('cancelled'))
            ];
        } catch (Exception $e) {
            $totalBookings = 0;
            $totalRevenue = 0;
            $paidRevenue = 0;
            $pendingRevenue = 0;
            $revenueByFacility = [];
            $bookingsByStatus = [];
        }
        
        $data = [
            'page_title' => 'Booking Reports',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'stats' => [
                'total_bookings' => $totalBookings,
                'total_revenue' => $totalRevenue,
                'paid_revenue' => $paidRevenue,
                'pending_revenue' => $pendingRevenue
            ],
            'revenue_by_facility' => $revenueByFacility,
            'bookings_by_status' => $bookingsByStatus,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('booking_reports/index', $data);
    }
    
    public function revenue() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $facilityId = $_GET['facility_id'] ?? null;
        
        try {
            $bookings = $this->bookingModel->getByDateRange($startDate, $endDate, $facilityId);
            
            // Group by date
            $revenueByDate = [];
            foreach ($bookings as $booking) {
                $date = $booking['booking_date'];
                if (!isset($revenueByDate[$date])) {
                    $revenueByDate[$date] = [
                        'date' => $date,
                        'total_bookings' => 0,
                        'total_revenue' => 0,
                        'paid_revenue' => 0,
                        'pending_revenue' => 0
                    ];
                }
                $revenueByDate[$date]['total_bookings']++;
                $revenueByDate[$date]['total_revenue'] += floatval($booking['total_amount']);
                $revenueByDate[$date]['paid_revenue'] += floatval($booking['paid_amount']);
                $revenueByDate[$date]['pending_revenue'] += floatval($booking['balance_amount']);
            }
            
            // Revenue by facility
            $revenueByFacility = $this->bookingModel->getRevenueByFacility($startDate, $endDate);
            
            // Facilities list for filter
            $facilities = $this->facilityModel->getActive();
        } catch (Exception $e) {
            $bookings = [];
            $revenueByDate = [];
            $revenueByFacility = [];
            $facilities = [];
        }
        
        $data = [
            'page_title' => 'Revenue Report',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'facility_id' => $facilityId,
            'bookings' => $bookings,
            'revenue_by_date' => $revenueByDate,
            'revenue_by_facility' => $revenueByFacility,
            'facilities' => $facilities,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('booking_reports/revenue', $data);
    }
    
    public function utilization() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $facilityId = $_GET['facility_id'] ?? null;
        
        try {
            $facilities = $facilityId 
                ? [$this->facilityModel->getById($facilityId)] 
                : $this->facilityModel->getActive();
            
            $utilizationData = [];
            foreach ($facilities as $facility) {
                if (!$facility) continue;
                
                $bookings = $this->bookingModel->getByDateRange($startDate, $endDate, $facility['id']);
                $totalHours = array_sum(array_column($bookings, 'duration_hours'));
                
                // Calculate available hours (assuming 24/7 for now, can be enhanced)
                $days = (strtotime($endDate) - strtotime($startDate)) / 86400 + 1;
                $availableHours = $days * 24;
                
                $utilizationRate = $availableHours > 0 ? ($totalHours / $availableHours) * 100 : 0;
                
                $utilizationData[] = [
                    'facility' => $facility,
                    'total_bookings' => count($bookings),
                    'total_hours' => $totalHours,
                    'available_hours' => $availableHours,
                    'utilization_rate' => $utilizationRate,
                    'total_revenue' => array_sum(array_column($bookings, 'total_amount'))
                ];
            }
        } catch (Exception $e) {
            $utilizationData = [];
            $facilities = [];
        }
        
        $data = [
            'page_title' => 'Utilization Report',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'facility_id' => $facilityId,
            'utilization_data' => $utilizationData,
            'facilities' => $this->facilityModel->getActive(),
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('booking_reports/utilization', $data);
    }
    
    public function customerHistory() {
        $customerEmail = $_GET['customer_email'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        try {
            if ($customerEmail) {
                // Get bookings for specific customer
                $bookings = $this->bookingModel->getByCustomerEmail($customerEmail, $startDate, $endDate);
                $customerStats = $this->calculateCustomerStats($customerEmail);
            } else {
                // Get all customers with booking counts
                $bookings = [];
                $customerStats = $this->getAllCustomerStats($startDate, $endDate);
            }
        } catch (Exception $e) {
            $bookings = [];
            $customerStats = [];
        }
        
        $data = [
            'page_title' => 'Customer History',
            'customer_email' => $customerEmail,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'bookings' => $bookings,
            'customer_stats' => $customerStats,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('booking_reports/customer_history', $data);
    }
    
    public function pendingPayments() {
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        try {
            $bookings = $this->bookingModel->getByDateRange(
                $startDate ?? '2000-01-01', 
                $endDate ?? date('Y-m-d')
            );
            
            // Filter bookings with outstanding balance
            $pendingPayments = array_filter($bookings, function($booking) {
                return floatval($booking['balance_amount']) > 0 && 
                       !in_array($booking['status'], ['cancelled']);
            });
            
            // Calculate totals
            $totalOutstanding = array_sum(array_column($pendingPayments, 'balance_amount'));
            $overduePayments = array_filter($pendingPayments, function($booking) {
                return strtotime($booking['booking_date']) < strtotime(date('Y-m-d'));
            });
            $totalOverdue = array_sum(array_column($overduePayments, 'balance_amount'));
        } catch (Exception $e) {
            $pendingPayments = [];
            $totalOutstanding = 0;
            $overduePayments = [];
            $totalOverdue = 0;
        }
        
        $data = [
            'page_title' => 'Pending Payments',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'pending_payments' => $pendingPayments,
            'overdue_payments' => $overduePayments,
            'total_outstanding' => $totalOutstanding,
            'total_overdue' => $totalOverdue,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('booking_reports/pending_payments', $data);
    }
    
    private function calculateCustomerStats($customerEmail) {
        try {
            $bookings = $this->bookingModel->getByCustomerEmail($customerEmail);
            return [
                'total_bookings' => count($bookings),
                'total_spent' => array_sum(array_column($bookings, 'paid_amount')),
                'outstanding_balance' => array_sum(array_column($bookings, 'balance_amount')),
                'avg_booking_value' => count($bookings) > 0 
                    ? array_sum(array_column($bookings, 'total_amount')) / count($bookings) 
                    : 0
            ];
        } catch (Exception $e) {
            return [
                'total_bookings' => 0,
                'total_spent' => 0,
                'outstanding_balance' => 0,
                'avg_booking_value' => 0
            ];
        }
    }
    
    private function getAllCustomerStats($startDate = null, $endDate = null) {
        try {
            $bookings = $this->bookingModel->getByDateRange(
                $startDate ?? '2000-01-01', 
                $endDate ?? date('Y-m-d')
            );
            
            // Group by customer email
            $customerStats = [];
            foreach ($bookings as $booking) {
                $email = $booking['customer_email'];
                if (!$email) continue;
                
                if (!isset($customerStats[$email])) {
                    $customerStats[$email] = [
                        'email' => $email,
                        'name' => $booking['customer_name'],
                        'total_bookings' => 0,
                        'total_spent' => 0,
                        'outstanding_balance' => 0
                    ];
                }
                
                $customerStats[$email]['total_bookings']++;
                $customerStats[$email]['total_spent'] += floatval($booking['paid_amount']);
                $customerStats[$email]['outstanding_balance'] += floatval($booking['balance_amount']);
            }
            
            // Sort by total spent
            usort($customerStats, function($a, $b) {
                return $b['total_spent'] - $a['total_spent'];
            });
            
            return $customerStats;
        } catch (Exception $e) {
            return [];
        }
    }
}

