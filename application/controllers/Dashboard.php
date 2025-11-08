<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Base_Controller {
    private $userModel;
    private $companyModel;
    private $activityModel;
    private $invoiceModel;
    private $bookingModel;
    private $propertyModel;
    private $transactionModel;
    private $stockModel;
    private $taxModel;
    private $leaseModel;
    private $workOrderModel;
    private $cashAccountModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = $this->loadModel('User_model');
        $this->companyModel = $this->loadModel('Company_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        // Enforce authentication - double check
        if (empty($this->session['user_id'])) {
            redirect('login');
            return;
        }
        
        $userRole = $this->session['role'] ?? 'user';
        
        // Load role-specific dashboard
        switch ($userRole) {
            case 'super_admin':
                $this->superAdminDashboard();
                break;
            case 'admin':
                $this->adminDashboard();
                break;
            case 'manager':
                $this->managerDashboard();
                break;
            case 'staff':
                $this->staffDashboard();
                break;
            case 'customer':
                redirect('customer-portal/dashboard');
                break;
            default:
                $this->basicDashboard();
        }
    }
    
    private function superAdminDashboard() {
        // Initialize models with error handling
        try {
            $this->invoiceModel = $this->loadModel('Invoice_model');
        } catch (Exception $e) {
            $this->invoiceModel = null;
            error_log('Dashboard Invoice_model load error: ' . $e->getMessage());
        }
        
        try {
            $this->bookingModel = $this->loadModel('Booking_model');
        } catch (Exception $e) {
            $this->bookingModel = null;
            error_log('Dashboard Booking_model load error: ' . $e->getMessage());
        }
        
        try {
            $this->propertyModel = $this->loadModel('Property_model');
        } catch (Exception $e) {
            $this->propertyModel = null;
        }
        
        try {
            $this->transactionModel = $this->loadModel('Transaction_model');
        } catch (Exception $e) {
            $this->transactionModel = null;
        }
        
        try {
            $this->stockModel = $this->loadModel('Stock_level_model');
        } catch (Exception $e) {
            $this->stockModel = null;
        }
        
        try {
            $this->taxModel = $this->loadModel('Tax_type_model');
        } catch (Exception $e) {
            $this->taxModel = null;
        }
        
        try {
            $this->leaseModel = $this->loadModel('Lease_model');
        } catch (Exception $e) {
            $this->leaseModel = null;
        }
        
        try {
            $this->workOrderModel = $this->loadModel('Work_order_model');
        } catch (Exception $e) {
            $this->workOrderModel = null;
        }
        
        try {
            $this->cashAccountModel = $this->loadModel('Cash_account_model');
        } catch (Exception $e) {
            $this->cashAccountModel = null;
        }
        
        // Get KPIs
        $kpis = $this->getSystemKPIs();
        
        // Get revenue trends
        $revenueTrend = $this->getRevenueTrend();
        
        // Get booking trends
        $bookingTrend = $this->getBookingTrend();
        
        // Get expense breakdown
        $expenseBreakdown = $this->getExpenseBreakdown();
        
        // Get occupancy data
        $occupancyData = $this->getOccupancyData();
        
        // Get tax liability
        $taxLiability = $this->getTaxLiability();
        
        // Get quick stats
        $quickStats = $this->getQuickStats();
        
        // Get activity feed
        $activityFeed = $this->activityModel->getRecent(20) ?? [];
        
        // Recent bookings, payments, activities
        $recentBookings = $this->getRecentBookings(5);
        $recentPayments = $this->getRecentPayments(5);
        $systemAlerts = $this->getSystemAlerts();
        
        $data = [
            'page_title' => 'Dashboard',
            'user_role' => $this->session['role'] ?? 'user',
            'kpis' => $kpis,
            'revenue_trend' => $revenueTrend,
            'booking_trend' => $bookingTrend,
            'expense_breakdown' => $expenseBreakdown,
            'occupancy_data' => $occupancyData,
            'tax_liability' => $taxLiability,
            'quick_stats' => $quickStats,
            'activity_feed' => $activityFeed,
            'recent_bookings' => $recentBookings,
            'recent_payments' => $recentPayments,
            'system_alerts' => $systemAlerts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('dashboard/super_admin', $data);
    }
    
    private function adminDashboard() {
        // Admin dashboard - similar to super admin but without companies and activity log
        // Initialize models with error handling
        try {
            $this->invoiceModel = $this->loadModel('Invoice_model');
        } catch (Exception $e) {
            $this->invoiceModel = null;
            error_log('Dashboard Invoice_model load error: ' . $e->getMessage());
        }
        
        try {
            $this->bookingModel = $this->loadModel('Booking_model');
        } catch (Exception $e) {
            $this->bookingModel = null;
            error_log('Dashboard Booking_model load error: ' . $e->getMessage());
        }
        
        try {
            $this->propertyModel = $this->loadModel('Property_model');
        } catch (Exception $e) {
            $this->propertyModel = null;
        }
        
        try {
            $this->transactionModel = $this->loadModel('Transaction_model');
        } catch (Exception $e) {
            $this->transactionModel = null;
        }
        
        try {
            $this->stockModel = $this->loadModel('Stock_level_model');
        } catch (Exception $e) {
            $this->stockModel = null;
        }
        
        try {
            $this->taxModel = $this->loadModel('Tax_type_model');
        } catch (Exception $e) {
            $this->taxModel = null;
        }
        
        try {
            $this->leaseModel = $this->loadModel('Lease_model');
        } catch (Exception $e) {
            $this->leaseModel = null;
        }
        
        try {
            $this->workOrderModel = $this->loadModel('Work_order_model');
        } catch (Exception $e) {
            $this->workOrderModel = null;
        }
        
        try {
            $this->cashAccountModel = $this->loadModel('Cash_account_model');
        } catch (Exception $e) {
            $this->cashAccountModel = null;
        }
        
        // Get KPIs
        $kpis = $this->getSystemKPIs();
        
        // Get revenue trends
        $revenueTrend = $this->getRevenueTrend();
        
        // Get booking trends
        $bookingTrend = $this->getBookingTrend();
        
        // Get expense breakdown
        $expenseBreakdown = $this->getExpenseBreakdown();
        
        // Get occupancy data
        $occupancyData = $this->getOccupancyData();
        
        // Get tax liability
        $taxLiability = $this->getTaxLiability();
        
        // Get quick stats
        $quickStats = $this->getQuickStats();
        
        // Recent bookings, payments
        $recentBookings = $this->getRecentBookings(5);
        $recentPayments = $this->getRecentPayments(5);
        $systemAlerts = $this->getSystemAlerts();
        
        $data = [
            'page_title' => 'Dashboard',
            'user_role' => 'admin',
            'kpis' => $kpis,
            'revenue_trend' => $revenueTrend,
            'booking_trend' => $bookingTrend,
            'expense_breakdown' => $expenseBreakdown,
            'occupancy_data' => $occupancyData,
            'tax_liability' => $taxLiability,
            'quick_stats' => $quickStats,
            'recent_bookings' => $recentBookings,
            'recent_payments' => $recentPayments,
            'system_alerts' => $systemAlerts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('dashboard/admin', $data);
    }
    
    private function managerDashboard() {
        // Manager dashboard - similar to admin but without tax module access
        // Initialize models with error handling
        try {
            $this->invoiceModel = $this->loadModel('Invoice_model');
        } catch (Exception $e) {
            $this->invoiceModel = null;
            error_log('Dashboard Invoice_model load error: ' . $e->getMessage());
        }
        
        try {
            $this->bookingModel = $this->loadModel('Booking_model');
        } catch (Exception $e) {
            $this->bookingModel = null;
            error_log('Dashboard Booking_model load error: ' . $e->getMessage());
        }
        
        try {
            $this->propertyModel = $this->loadModel('Property_model');
        } catch (Exception $e) {
            $this->propertyModel = null;
        }
        
        try {
            $this->transactionModel = $this->loadModel('Transaction_model');
        } catch (Exception $e) {
            $this->transactionModel = null;
        }
        
        try {
            $this->stockModel = $this->loadModel('Stock_level_model');
        } catch (Exception $e) {
            $this->stockModel = null;
        }
        
        try {
            $this->leaseModel = $this->loadModel('Lease_model');
        } catch (Exception $e) {
            $this->leaseModel = null;
        }
        
        try {
            $this->workOrderModel = $this->loadModel('Work_order_model');
        } catch (Exception $e) {
            $this->workOrderModel = null;
        }
        
        try {
            $this->cashAccountModel = $this->loadModel('Cash_account_model');
        } catch (Exception $e) {
            $this->cashAccountModel = null;
        }
        
        // Get KPIs (excluding tax-related)
        $kpis = $this->getSystemKPIs();
        
        // Get revenue trends
        $revenueTrend = $this->getRevenueTrend();
        
        // Get booking trends
        $bookingTrend = $this->getBookingTrend();
        
        // Get expense breakdown
        $expenseBreakdown = $this->getExpenseBreakdown();
        
        // Get occupancy data
        $occupancyData = $this->getOccupancyData();
        
        // Get quick stats
        $quickStats = $this->getQuickStats();
        
        // Recent bookings
        $recentBookings = $this->getRecentBookings(5);
        
        // Get module activity (excluding tax)
        $moduleActivity = $this->getModuleActivity(['tax']);
        
        $data = [
            'page_title' => 'Manager Dashboard',
            'user_role' => 'manager',
            'kpis' => $kpis,
            'revenue_trend' => $revenueTrend,
            'booking_trend' => $bookingTrend,
            'expense_breakdown' => $expenseBreakdown,
            'occupancy_data' => $occupancyData,
            'quick_stats' => $quickStats,
            'recent_bookings' => $recentBookings,
            'module_activity' => $moduleActivity,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('dashboard/manager', $data);
    }
    
    private function staffDashboard() {
        // Load models for staff dashboard
        try {
            $this->bookingModel = $this->loadModel('Booking_model');
        } catch (Exception $e) {
            $this->bookingModel = null;
        }
        
        try {
            $this->stockModel = $this->loadModel('Stock_model');
        } catch (Exception $e) {
            $this->stockModel = null;
        }
        
        // Get today's date
        $today = date('Y-m-d');
        $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
        $thisWeekEnd = date('Y-m-d', strtotime('sunday this week'));
        
        // Get today's bookings
        $todayBookings = [];
        if ($this->bookingModel) {
            try {
                $todayBookings = $this->bookingModel->getByDateRange($today, $today);
            } catch (Exception $e) {
                error_log('Staff dashboard getTodayBookings error: ' . $e->getMessage());
            }
        }
        
        // Get this week's bookings
        $weekBookings = [];
        if ($this->bookingModel) {
            try {
                $weekBookings = $this->bookingModel->getByDateRange($thisWeekStart, $thisWeekEnd);
            } catch (Exception $e) {
                error_log('Staff dashboard getWeekBookings error: ' . $e->getMessage());
            }
        }
        
        // Get recent bookings
        $recentBookings = $this->getRecentBookings(5);
        
        // Get low stock items
        $lowStockItems = [];
        if ($this->stockModel && method_exists($this->stockModel, 'getLowStock')) {
            try {
                $lowStockItems = $this->stockModel->getLowStock(10);
            } catch (Exception $e) {
                error_log('Staff dashboard getLowStock error: ' . $e->getMessage());
            }
        } else if ($this->db) {
            try {
                $lowStockItems = $this->db->fetchAll(
                    "SELECT * FROM `" . $this->db->getPrefix() . "stock` 
                     WHERE quantity <= reorder_level 
                     AND status = 'active'
                     ORDER BY quantity ASC 
                     LIMIT 10"
                ) ?? [];
            } catch (Exception $e) {
                error_log('Staff dashboard getLowStock error: ' . $e->getMessage());
            }
        }
        
        // Get recent POS transactions
        $recentPOSTransactions = [];
        if ($this->db) {
            try {
                $recentPOSTransactions = $this->db->fetchAll(
                    "SELECT * FROM `" . $this->db->getPrefix() . "pos_transactions` 
                     ORDER BY created_at DESC 
                     LIMIT 10"
                ) ?? [];
            } catch (Exception $e) {
                // Table might not exist, that's okay
            }
        }
        
        // Calculate stats
        $stats = [
            'today_bookings' => count($todayBookings),
            'week_bookings' => count($weekBookings),
            'low_stock_count' => count($lowStockItems),
            'pending_bookings' => 0
        ];
        
        // Count pending bookings
        if ($this->bookingModel) {
            try {
                $pendingResult = $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . "bookings` 
                     WHERE status IN ('pending', 'confirmed') 
                     AND booking_date >= ?",
                    [$today]
                );
                $stats['pending_bookings'] = intval($pendingResult['count'] ?? 0);
            } catch (Exception $e) {
                error_log('Staff dashboard getPendingBookings error: ' . $e->getMessage());
            }
        }
        
        // Staff task-oriented dashboard
        $data = [
            'page_title' => 'My Dashboard',
            'user_role' => 'staff',
            'stats' => $stats,
            'today_bookings' => $todayBookings,
            'week_bookings' => $weekBookings,
            'recent_bookings' => $recentBookings,
            'low_stock_items' => $lowStockItems,
            'recent_pos_transactions' => $recentPOSTransactions,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('dashboard/staff', $data);
    }
    
    private function basicDashboard() {
        $data = [
            'page_title' => 'Dashboard',
            'total_users' => $this->userModel->count(),
            'total_companies' => $this->companyModel->count(),
            'recent_activities' => $this->activityModel->getRecent(10),
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('dashboard/index', $data);
    }
    
    private function getSystemKPIs() {
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $monthStart = date('Y-m-01');
        $yearStart = date('Y-01-01');
        
        return [
            'revenue_today' => $this->getRevenueForPeriod($today, $today),
            'revenue_week' => $this->getRevenueForPeriod($weekStart, $today),
            'revenue_month' => $this->getRevenueForPeriod($monthStart, $today),
            'revenue_year' => $this->getRevenueForPeriod($yearStart, $today),
            'bookings_confirmed' => $this->getBookingCount('confirmed'),
            'bookings_pending' => $this->getBookingCount('pending'),
            'bookings_completed' => $this->getBookingCount('completed'),
            'occupancy_rate' => $this->getOccupancyRate(),
            'outstanding_receivables' => $this->getOutstandingReceivables(),
            'cash_balance' => $this->getCashBalance(),
            'inventory_value' => $this->getInventoryValue(),
            'active_users' => $this->userModel->count() ?? 0
        ];
    }
    
    private function getRevenueForPeriod($startDate, $endDate) {
        if (!$this->db || !$this->invoiceModel) return 0;
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(total_amount) as total FROM `" . $this->db->getPrefix() . "invoices` 
                 WHERE invoice_date >= ? AND invoice_date <= ? AND status = 'paid'",
                [$startDate, $endDate]
            );
            return floatval($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Dashboard getRevenueForPeriod error: ' . $e->getMessage());
            return 0;
        }
    }
    
    private function getBookingCount($status) {
        if (!$this->db || !$this->bookingModel) return 0;
        try {
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . "bookings` 
                 WHERE status = ?",
                [$status]
            );
            return intval($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log('Dashboard getBookingCount error: ' . $e->getMessage());
            return 0;
        }
    }
    
    private function getOccupancyRate() {
        if (!$this->db || !$this->propertyModel) return 0;
        try {
            $totalSpaces = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . "spaces` 
                 WHERE status = 'active'"
            );
            $occupiedSpaces = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . "spaces` 
                 WHERE status = 'active' AND operational_mode = 'leased'"
            );
            
            if (($totalSpaces['count'] ?? 0) == 0) return 0;
            return round((($occupiedSpaces['count'] ?? 0) / ($totalSpaces['count'] ?? 1)) * 100, 1);
        } catch (Exception $e) {
            error_log('Dashboard getOccupancyRate error: ' . $e->getMessage());
            return 0;
        }
    }
    
    private function getOutstandingReceivables() {
        if (!$this->db || !$this->invoiceModel) return 0;
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(balance_amount) as total FROM `" . $this->db->getPrefix() . "invoices` 
                 WHERE status != 'paid' AND balance_amount > 0"
            );
            return floatval($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Dashboard getOutstandingReceivables error: ' . $e->getMessage());
            return 0;
        }
    }
    
    private function getCashBalance() {
        if (!$this->db || !$this->cashAccountModel) return 0;
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(current_balance) as total FROM `" . $this->db->getPrefix() . "cash_accounts` 
                 WHERE status = 'active'"
            );
            return floatval($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Dashboard getCashBalance error: ' . $e->getMessage());
            return 0;
        }
    }
    
    private function getInventoryValue() {
        if (!$this->db || !$this->stockModel) return 0;
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(quantity * unit_cost) as total FROM `" . $this->db->getPrefix() . "stock_levels`"
            );
            return floatval($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Dashboard getInventoryValue error: ' . $e->getMessage());
            return 0;
        }
    }
    
    private function getRevenueTrend() {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-{$i} months"));
            $months[] = [
                'month' => date('M Y', strtotime($date . '-01')),
                'revenue' => $this->getRevenueForPeriod($date . '-01', date('Y-m-t', strtotime($date . '-01')))
            ];
        }
        return $months;
    }
    
    private function getBookingTrend() {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-{$i} months"));
            $months[] = [
                'month' => date('M Y', strtotime($date . '-01')),
                'count' => $this->getBookingCountForMonth($date)
            ];
        }
        return $months;
    }
    
    private function getBookingCountForMonth($month) {
        if (!$this->db || !$this->bookingModel) return 0;
        try {
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . "bookings` 
                 WHERE DATE_FORMAT(booking_date, '%Y-%m') = ?",
                [$month]
            );
            return intval($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log('Dashboard getBookingCountForMonth error: ' . $e->getMessage());
            return 0;
        }
    }
    
    private function getExpenseBreakdown() {
        if (!$this->db) {
            return [];
        }
        try {
            $result = $this->db->fetchAll(
                "SELECT a.account_name, SUM(t.amount) as total 
                 FROM `" . $this->db->getPrefix() . "transactions` t
                 JOIN `" . $this->db->getPrefix() . "accounts` a ON t.account_id = a.id
                 WHERE a.account_type = 'Expenses' AND DATE(t.date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 AND t.type = 'debit'
                 GROUP BY a.id, a.account_name
                 ORDER BY total DESC
                 LIMIT 10"
            );
            return $result ?? [];
        } catch (Exception $e) {
            error_log('Dashboard getExpenseBreakdown error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getOccupancyData() {
        if (!$this->db) return [];
        try {
            $result = $this->db->fetchAll(
                "SELECT DATE_FORMAT(booking_date, '%Y-%m-%d') as date, COUNT(*) as count 
                 FROM `" . $this->db->getPrefix() . "bookings` 
                 WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY DATE(booking_date)"
            );
            return $result ?? [];
        } catch (Exception $e) {
            error_log('Dashboard getOccupancyData error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getTaxLiability() {
        if (!$this->db) {
            return 0;
        }
        try {
            $payeTotal = 0;
            $vatTotal = 0;
            
            try {
                $payeResult = $this->db->fetchOne(
                    "SELECT SUM(total_paye) as total FROM `" . $this->db->getPrefix() . "paye_returns` 
                     WHERE status != 'paid'"
                );
                $payeTotal = floatval($payeResult['total'] ?? 0);
            } catch (Exception $e) {
                // Table might not exist
            }
            
            try {
                $vatResult = $this->db->fetchOne(
                    "SELECT SUM(vat_payable) as total FROM `" . $this->db->getPrefix() . "vat_returns` 
                     WHERE status != 'paid'"
                );
                $vatTotal = floatval($vatResult['total'] ?? 0);
            } catch (Exception $e) {
                // Table might not exist
            }
            
            return $payeTotal + $vatTotal;
        } catch (Exception $e) {
            error_log('Dashboard getTaxLiability error: ' . $e->getMessage());
            return 0;
        }
    }
    
    private function getQuickStats() {
        return [
            'pending_payments' => $this->getPendingPayments(),
            'expiring_leases' => $this->getExpiringLeases(60),
            'low_stock_items' => $this->getLowStockItems(),
            'overdue_maintenance' => $this->getOverdueMaintenance(),
            'upcoming_tax_deadlines' => $this->getUpcomingTaxDeadlines(30)
        ];
    }
    
    private function getPendingPayments() {
        if (!$this->db) return ['count' => 0, 'amount' => 0];
        try {
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count, SUM(total_amount) as total 
                 FROM `" . $this->db->getPrefix() . "invoices` 
                 WHERE status = 'pending'"
            );
            return [
                'count' => intval($result['count'] ?? 0),
                'amount' => floatval($result['total'] ?? 0)
            ];
        } catch (Exception $e) {
            error_log('Dashboard getPendingPayments error: ' . $e->getMessage());
            return ['count' => 0, 'amount' => 0];
        }
    }
    
    private function getExpiringLeases($days) {
        if (!$this->db || !$this->leaseModel) return [];
        try {
            $endDate = date('Y-m-d', strtotime("+{$days} days"));
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "leases` 
                 WHERE end_date <= ? AND end_date >= CURDATE() AND status = 'active'
                 ORDER BY end_date ASC
                 LIMIT 10",
                [$endDate]
            ) ?? [];
        } catch (Exception $e) {
            error_log('Dashboard getExpiringLeases error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getLowStockItems() {
        if (!$this->db || !$this->stockModel) return [];
        try {
            return $this->db->fetchAll(
                "SELECT s.*, i.name, i.item_code 
                 FROM `" . $this->db->getPrefix() . "stock_levels` s
                 JOIN `" . $this->db->getPrefix() . "items` i ON s.item_id = i.id
                 WHERE s.quantity <= s.reorder_point AND s.quantity > 0
                 ORDER BY (s.quantity / s.reorder_point) ASC
                 LIMIT 10"
            ) ?? [];
        } catch (Exception $e) {
            error_log('Dashboard getLowStockItems error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getOverdueMaintenance() {
        if (!$this->db || !$this->workOrderModel) return [];
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "work_orders` 
                 WHERE status != 'completed' AND due_date < CURDATE()
                 ORDER BY due_date ASC
                 LIMIT 10"
            ) ?? [];
        } catch (Exception $e) {
            error_log('Dashboard getOverdueMaintenance error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getUpcomingTaxDeadlines($days) {
        if (!$this->db) return [];
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "tax_deadlines` 
                 WHERE deadline_date >= CURDATE() AND deadline_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                 AND status != 'completed'
                 ORDER BY deadline_date ASC
                 LIMIT 10",
                [$days]
            ) ?? [];
        } catch (Exception $e) {
            error_log('Dashboard getUpcomingTaxDeadlines error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getModuleActivity($excludeModules = []) {
        if (!$this->db) return [];
        try {
            $moduleActivity = [];
            $modules = [
                'accounting' => ['invoices', 'payments', 'bills'],
                'bookings' => ['bookings'],
                'properties' => ['leases', 'spaces'],
                'utilities' => ['utility_bills'],
                'inventory' => ['items', 'stock_levels'],
                'pos' => ['pos_transactions']
            ];
            
            foreach ($modules as $module => $tables) {
                if (in_array($module, $excludeModules)) {
                    continue;
                }
                
                $count = 0;
                foreach ($tables as $table) {
                    try {
                        $result = $this->db->fetchOne(
                            "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . $table . "` 
                             WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
                        );
                        $count += intval($result['count'] ?? 0);
                    } catch (Exception $e) {
                        // Table might not exist, skip
                        continue;
                    }
                }
                
                if ($count > 0) {
                    $moduleActivity[] = [
                        'module' => ucfirst($module),
                        'count' => $count
                    ];
                }
            }
            
            return $moduleActivity;
        } catch (Exception $e) {
            error_log('Dashboard getModuleActivity error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getRecentBookings($limit = 5) {
        if (!$this->db || !$this->bookingModel) return [];
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "bookings` 
                 ORDER BY created_at DESC
                 LIMIT " . intval($limit)
            ) ?? [];
        } catch (Exception $e) {
            error_log('Dashboard getRecentBookings error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getRecentPayments($limit = 5) {
        if (!$this->db) return [];
        try {
            return $this->db->fetchAll(
                "SELECT p.*, i.invoice_number, c.name as customer_name
                 FROM `" . $this->db->getPrefix() . "payments` p
                 LEFT JOIN `" . $this->db->getPrefix() . "invoices` i ON p.invoice_id = i.id
                 LEFT JOIN `" . $this->db->getPrefix() . "customers` c ON i.customer_id = c.id
                 ORDER BY p.payment_date DESC
                 LIMIT " . intval($limit)
            ) ?? [];
        } catch (Exception $e) {
            error_log('Dashboard getRecentPayments error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getSystemAlerts() {
        $alerts = [];
        
        if (!$this->db) return $alerts;
        
        // Low stock alerts
        $lowStock = $this->getLowStockItems();
        if (count($lowStock) > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => count($lowStock) . ' items are low on stock',
                'link' => base_url('inventory/stock-levels')
            ];
        }
        
        // Overdue invoices
        try {
            $overdueInvoices = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . "invoices` 
                 WHERE due_date < CURDATE() AND status != 'paid'"
            );
            if (($overdueInvoices['count'] ?? 0) > 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'message' => $overdueInvoices['count'] . ' invoices are overdue',
                    'link' => base_url('receivables/invoices')
                ];
            }
        } catch (Exception $e) {
            error_log('Dashboard getSystemAlerts overdue invoices error: ' . $e->getMessage());
        }
        
        // Upcoming tax deadlines
        $upcomingDeadlines = count($this->getUpcomingTaxDeadlines(7));
        if ($upcomingDeadlines > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => $upcomingDeadlines . ' tax deadlines approaching',
                'link' => base_url('tax/compliance')
            ];
        }
        
        return $alerts;
    }
}
