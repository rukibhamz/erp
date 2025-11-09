<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Base_Controller {
    private $userModel;
    private $entityModel; // Entity_model (formerly Company_model)
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
        $this->entityModel = $this->loadModel('Entity_model');
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
    
    /**
     * Initialize dashboard models with error handling
     * Refactored to reduce duplication across dashboard methods
     */
    private function initializeDashboardModels($includeTax = true) {
        $models = [
            'invoiceModel' => 'Invoice_model',
            'bookingModel' => 'Booking_model',
            'propertyModel' => 'Property_model',
            'transactionModel' => 'Transaction_model',
            'stockModel' => 'Stock_level_model',
            'leaseModel' => 'Lease_model',
            'workOrderModel' => 'Work_order_model',
            'cashAccountModel' => 'Cash_account_model',
        ];
        
        if ($includeTax) {
            $models['taxModel'] = 'Tax_type_model';
        }
        
        foreach ($models as $property => $modelName) {
            try {
                $this->$property = $this->loadModel($modelName);
            } catch (Exception $e) {
                $this->$property = null;
                if (in_array($property, ['invoiceModel', 'bookingModel'])) {
                    error_log("Dashboard {$modelName} load error: " . $e->getMessage());
                }
            }
        }
    }
    
    private function superAdminDashboard() {
        // Initialize models with error handling
        $this->initializeDashboardModels(true);
        
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
        $this->initializeDashboardModels(true);
        
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
        // Initialize models with error handling (exclude tax)
        $this->initializeDashboardModels(false);
        
        // Get KPIs (excluding tax-related) - use manager-specific function
        // Note: Managers should not have access to tax data, so we use getManagerKPIs()
        $kpis = $this->getManagerKPIs();
        
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
            'total_entities' => $this->entityModel->count(),
            'total_companies' => $this->entityModel->count(), // Legacy compatibility
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
            'cash_balance' => $this->extractValue($this->getCashBalance()),
            'inventory_value' => $this->extractValue($this->getInventoryValue()),
            'active_users' => $this->userModel->count() ?? 0
        ];
    }
    
    /**
     * Get KPIs for manager role (excludes tax-related data)
     * Managers should not have access to tax information
     */
    private function getManagerKPIs() {
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
            'cash_balance' => $this->extractValue($this->getCashBalance()),
            'inventory_value' => $this->extractValue($this->getInventoryValue()),
            'active_users' => $this->userModel->count() ?? 0
            // Note: tax_liability is intentionally excluded for managers
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
    
    /**
     * Helper method to create error response for dashboard data
     * Returns array with 'error' flag so frontend can distinguish errors from zero values
     */
    private function createErrorResponse($message, $defaultValue = 0) {
        error_log('Dashboard error: ' . $message);
        return [
            'value' => $defaultValue,
            'error' => true,
            'message' => $message
        ];
    }
    
    /**
     * Helper method to create success response for dashboard data
     */
    private function createSuccessResponse($value) {
        return [
            'value' => $value,
            'error' => false
        ];
    }
    
    /**
     * Extract value from response (handles both old format and new error-aware format)
     * For backward compatibility during transition
     */
    private function extractValue($response) {
        if (is_array($response) && isset($response['value'])) {
            return $response['value'];
        }
        return $response; // Old format - just return the value
    }
    
    private function getCashBalance() {
        if (!$this->db || !$this->cashAccountModel) {
            return $this->createErrorResponse('Database or cash account model not available', 0);
        }
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(current_balance) as total FROM `" . $this->db->getPrefix() . "cash_accounts` 
                 WHERE status = 'active'"
            );
            $value = floatval($result['total'] ?? 0);
            return $this->createSuccessResponse($value);
        } catch (Exception $e) {
            return $this->createErrorResponse('Failed to load cash balance: ' . $e->getMessage(), 0);
        }
    }
    
    private function getInventoryValue() {
        if (!$this->db || !$this->stockModel) {
            return $this->createErrorResponse('Database or model not available', 0);
        }
        try {
            // Fixed: Only sum active stock levels, handle NULL unit_cost
            $result = $this->db->fetchOne(
                "SELECT SUM(quantity * COALESCE(unit_cost, 0)) as total 
                 FROM `" . $this->db->getPrefix() . "stock_levels`
                 WHERE status = 'active'"
            );
            $value = floatval($result['total'] ?? 0);
            return $this->createSuccessResponse($value);
        } catch (Exception $e) {
            return $this->createErrorResponse('Failed to load inventory value: ' . $e->getMessage(), 0);
        }
    }
    
    private function getRevenueTrend() {
        if (!$this->db || !$this->invoiceModel) {
            return [];
        }
        try {
            // Fixed: N+1 query problem - fetch all 12 months in a single query
            $startDate = date('Y-m-01', strtotime('-11 months'));
            $endDate = date('Y-m-t');
            
            $result = $this->db->fetchAll(
                "SELECT 
                    DATE_FORMAT(invoice_date, '%Y-%m') as month_key,
                    DATE_FORMAT(invoice_date, '%M %Y') as month_label,
                    SUM(total_amount) as revenue
                 FROM `" . $this->db->getPrefix() . "invoices`
                 WHERE invoice_date >= ? 
                 AND invoice_date <= ?
                 AND status = 'paid'
                 GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
                 ORDER BY month_key ASC",
                [$startDate, $endDate]
            );
            
            // Create array with all 12 months, filling in zeros for months with no data
            $months = [];
            $dataByMonth = [];
            foreach ($result as $row) {
                $dataByMonth[$row['month_key']] = [
                    'month' => $row['month_label'],
                    'revenue' => floatval($row['revenue'] ?? 0)
                ];
            }
            
            // Fill in all 12 months
            for ($i = 11; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-{$i} months"));
                $monthLabel = date('M Y', strtotime($date . '-01'));
                
                if (isset($dataByMonth[$date])) {
                    $months[] = $dataByMonth[$date];
                } else {
                    $months[] = [
                        'month' => $monthLabel,
                        'revenue' => 0
                    ];
                }
            }
            
            return $months;
        } catch (Exception $e) {
            error_log('Dashboard getRevenueTrend error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getBookingTrend() {
        if (!$this->db || !$this->bookingModel) {
            return [];
        }
        try {
            // Fixed: N+1 query problem - fetch all 12 months in a single query
            $startDate = date('Y-m-01', strtotime('-11 months'));
            $endDate = date('Y-m-t');
            
            $result = $this->db->fetchAll(
                "SELECT 
                    DATE_FORMAT(booking_date, '%Y-%m') as month_key,
                    DATE_FORMAT(booking_date, '%M %Y') as month_label,
                    COUNT(*) as count
                 FROM `" . $this->db->getPrefix() . "bookings`
                 WHERE booking_date >= ?
                 AND booking_date <= ?
                 GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
                 ORDER BY month_key ASC",
                [$startDate, $endDate]
            );
            
            // Create array with all 12 months, filling in zeros for months with no data
            $months = [];
            $dataByMonth = [];
            foreach ($result as $row) {
                $dataByMonth[$row['month_key']] = [
                    'month' => $row['month_label'],
                    'count' => intval($row['count'] ?? 0)
                ];
            }
            
            // Fill in all 12 months
            for ($i = 11; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-{$i} months"));
                $monthLabel = date('M Y', strtotime($date . '-01'));
                
                if (isset($dataByMonth[$date])) {
                    $months[] = $dataByMonth[$date];
                } else {
                    $months[] = [
                        'month' => $monthLabel,
                        'count' => 0
                    ];
                }
            }
            
            return $months;
        } catch (Exception $e) {
            error_log('Dashboard getBookingTrend error: ' . $e->getMessage());
            return [];
        }
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
            // Fixed: Use 'debit' column instead of non-existent 'amount' column
            // The transactions table uses 'debit' and 'credit' columns, not 'amount'
            $result = $this->db->fetchAll(
                "SELECT a.account_name, SUM(t.debit) as total 
                 FROM `" . $this->db->getPrefix() . "transactions` t
                 JOIN `" . $this->db->getPrefix() . "accounts` a ON t.account_id = a.id
                 WHERE a.account_type = 'Expenses' 
                 AND DATE(t.transaction_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 AND t.transaction_type IN ('payment', 'bill', 'expense')
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
            // Fixed: Use item_name instead of name, and handle missing item_code
            return $this->db->fetchAll(
                "SELECT s.*, i.item_name, i.sku, i.name, 
                        COALESCE(s.reorder_point, s.reorder_level, i.reorder_point, i.reorder_level, 0) as reorder_level
                 FROM `" . $this->db->getPrefix() . "stock_levels` s
                 JOIN `" . $this->db->getPrefix() . "items` i ON s.item_id = i.id
                 WHERE s.quantity <= COALESCE(s.reorder_point, s.reorder_level, i.reorder_point, i.reorder_level, 0)
                 AND s.quantity > 0
                 AND s.status = 'active'
                 ORDER BY (s.quantity / GREATEST(COALESCE(s.reorder_point, s.reorder_level, i.reorder_point, i.reorder_level, 1), 1)) ASC
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
                'pos' => ['pos_sales'] // Fixed: Use pos_sales instead of pos_transactions
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
            // Fixed: Use parameterized query for LIMIT to prevent SQL injection
            // Note: MySQL doesn't support LIMIT as a parameter, so we validate strictly
            $limit = max(1, min(100, intval($limit))); // Ensure limit is between 1 and 100
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "bookings` 
                 ORDER BY created_at DESC
                 LIMIT " . $limit
            ) ?? [];
        } catch (Exception $e) {
            error_log('Dashboard getRecentBookings error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getRecentPayments($limit = 5) {
        if (!$this->db) return [];
        try {
            // Fixed: Validate limit to prevent SQL injection
            $limit = max(1, min(100, intval($limit))); // Ensure limit is between 1 and 100
            return $this->db->fetchAll(
                "SELECT p.*, i.invoice_number, c.name as customer_name
                 FROM `" . $this->db->getPrefix() . "payments` p
                 LEFT JOIN `" . $this->db->getPrefix() . "invoices` i ON p.invoice_id = i.id
                 LEFT JOIN `" . $this->db->getPrefix() . "customers` c ON i.customer_id = c.id
                 ORDER BY p.payment_date DESC
                 LIMIT " . $limit
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
        
        // Tax compliance module removed - no alerts for deadlines
        
        return $alerts;
    }
}
