<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accounting extends Base_Controller {
    private $accountModel;
    private $transactionModel;
    private $invoiceModel;
    private $billModel;
    private $customerModel;
    private $vendorModel;
    private $cashAccountModel;
    private $activityModel;
    private $balanceCalculator;
    private $financialReporting;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('accounting', 'read');
        $this->accountModel = $this->loadModel('Account_model');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->billModel = $this->loadModel('Bill_model');
        $this->customerModel = $this->loadModel('Customer_model');
        $this->vendorModel = $this->loadModel('Vendor_model');
        $this->cashAccountModel = $this->loadModel('Cash_account_model');
        $this->activityModel = $this->loadModel('Activity_model');

        require_once BASEPATH . 'services/Balance_calculator.php';
        $this->balanceCalculator = new Balance_calculator();
        require_once BASEPATH . 'services/Financial_reporting_service.php';
        $this->financialReporting = new Financial_reporting_service();
    }
    
    public function index() {
        $cashBalance = 0;
        $receivables = 0;
        $payables = 0;
        $profitLoss = 0;
        $recentTransactions = [];
        $overdueInvoices = [];
        $overdueBills = [];
        
        try {
            $cashBalance = $this->getCashBalance();
            $receivables = $this->getTotalReceivables();
            $payables = $this->getTotalPayables();
            $profitLoss = $this->getProfitLoss();
            
            $recentTransactions = $this->getRecentTransactions(10);
            
            $overdueInvoices = $this->getOverdueInvoices(5);
            $overdueBills = $this->getOverdueBills(5);
        } catch (Exception $e) {
            error_log('Accounting Dashboard Error: ' . $e->getMessage());
        }
        
        $data = [
            'page_title' => 'Accounting Dashboard',
            'cash_balance' => $cashBalance,
            'receivables' => $receivables,
            'payables' => $payables,
            'profit_loss' => $profitLoss,
            'recent_transactions' => $recentTransactions,
            'overdue_invoices' => $overdueInvoices,
            'overdue_bills' => $overdueBills,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('accounting/dashboard', $data);
    }
    
    private function getCashBalance() {
        try {
            return $this->financialReporting->getTotalCashGlBalance(
                date('Y-m-d'),
                $this->balanceCalculator
            );
        } catch (Exception $e) {
            error_log('Accounting getCashBalance error: ' . $e->getMessage());
            return 0;
        }
    }
    
    private function getTotalReceivables() {
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(balance_amount) as total FROM `" . $this->db->getPrefix() . "invoices` 
                 WHERE status NOT IN ('paid', 'cancelled')"
            );
            return $result ? floatval($result['total'] ?? 0) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getTotalPayables() {
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(balance_amount) as total FROM `" . $this->db->getPrefix() . "bills` 
                 WHERE status IN ('received', 'partially_paid', 'overdue')"
            );
            return $result ? floatval($result['total'] ?? 0) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getProfitLoss() {
        try {
            $monthStart = date('Y-m-01');
            $monthEnd = date('Y-m-t');
            return $this->financialReporting->calculateNetIncomeForPeriod($monthStart, $monthEnd);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getRecentTransactions($limit = 10) {
        try {
            $prefix = $this->db->getPrefix();
            
            return $this->db->fetchAll(
                "SELECT t.*, 
                        a.account_code, 
                        a.account_name,
                        b.booking_number,
                        b.customer_name as booking_customer,
                        i.invoice_number,
                        ic.company_name as invoice_customer,
                        COALESCE(b.customer_name, ic.company_name) as customer_name
                 FROM `{$prefix}transactions` t
                 JOIN `{$prefix}accounts` a ON t.account_id = a.id
                 LEFT JOIN `{$prefix}bookings` b ON t.reference_type IN ('booking_payment', 'booking_revenue') AND t.reference_id = b.id
                 LEFT JOIN `{$prefix}invoices` i ON t.reference_type = 'invoice' AND t.reference_id = i.id
                 LEFT JOIN `{$prefix}customers` ic ON i.customer_id = ic.id
                 WHERE t.status = 'posted'
                 ORDER BY t.transaction_date DESC, t.id DESC
                 LIMIT " . intval($limit)
            );
        } catch (Exception $e) {
            error_log('getRecentTransactions error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getOverdueInvoices($limit = 5) {
        try {
            return $this->db->fetchAll(
                "SELECT i.*, c.company_name
                 FROM `" . $this->db->getPrefix() . "invoices` i
                 JOIN `" . $this->db->getPrefix() . "customers` c ON i.customer_id = c.id
                 WHERE i.status NOT IN ('paid', 'cancelled') 
                 AND i.due_date < CURDATE() AND i.balance_amount > 0
                 ORDER BY i.due_date ASC
                 LIMIT " . intval($limit)
            );
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getOverdueBills($limit = 5) {
        try {
            return $this->db->fetchAll(
                "SELECT b.*, v.company_name
                 FROM `" . $this->db->getPrefix() . "bills` b
                 JOIN `" . $this->db->getPrefix() . "vendors` v ON b.vendor_id = v.id
                 WHERE b.status IN ('received', 'partially_paid', 'overdue') 
                 AND b.due_date < CURDATE() AND b.balance_amount > 0
                 ORDER BY b.due_date ASC
                 LIMIT " . intval($limit)
            );
        } catch (Exception $e) {
            return [];
        }
    }
}
