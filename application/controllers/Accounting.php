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
    }
    
    public function index() {
        // Initialize with safe defaults
        $cashBalance = 0;
        $receivables = 0;
        $payables = 0;
        $profitLoss = 0;
        $recentTransactions = [];
        $overdueInvoices = [];
        $overdueBills = [];
        
        // Check if tables exist before querying
        try {
            // Get financial KPIs
            $cashBalance = $this->getCashBalance();
            $receivables = $this->getTotalReceivables();
            $payables = $this->getTotalPayables();
            $profitLoss = $this->getProfitLoss();
            
            // Recent transactions
            $recentTransactions = $this->getRecentTransactions(10);
            
            // Overdue invoices and bills
            $overdueInvoices = $this->getOverdueInvoices(5);
            $overdueBills = $this->getOverdueBills(5);
        } catch (Exception $e) {
            error_log('Accounting Dashboard Error: ' . $e->getMessage());
            // Use default values
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
            $result = $this->db->fetchOne(
                "SELECT SUM(current_balance) as total FROM `" . $this->db->getPrefix() . "cash_accounts` WHERE status = 'active'"
            );
            return $result ? floatval($result['total'] ?? 0) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getTotalReceivables() {
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(balance_amount) as total FROM `" . $this->db->getPrefix() . "invoices` 
                 WHERE status IN ('sent', 'partially_paid', 'overdue')"
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
            
            $revenueResult = $this->db->fetchOne(
                "SELECT COALESCE(SUM(t.credit - t.debit), 0) as total 
                 FROM `" . $this->db->getPrefix() . "transactions` t
                 JOIN `" . $this->db->getPrefix() . "accounts` a ON t.account_id = a.id
                 WHERE a.account_type = 'Revenue' AND t.transaction_date >= ? AND t.transaction_date <= ? AND t.status = 'posted'",
                [$monthStart, $monthEnd]
            );
            $revenue = ($revenueResult && isset($revenueResult['total'])) ? floatval($revenueResult['total']) : 0;
            
            $expenseResult = $this->db->fetchOne(
                "SELECT COALESCE(SUM(t.debit - t.credit), 0) as total 
                 FROM `" . $this->db->getPrefix() . "transactions` t
                 JOIN `" . $this->db->getPrefix() . "accounts` a ON t.account_id = a.id
                 WHERE a.account_type = 'Expenses' AND t.transaction_date >= ? AND t.transaction_date <= ? AND t.status = 'posted'",
                [$monthStart, $monthEnd]
            );
            $expenses = ($expenseResult && isset($expenseResult['total'])) ? floatval($expenseResult['total']) : 0;
            
            return $revenue - $expenses;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getRecentTransactions($limit = 10) {
        try {
            return $this->db->fetchAll(
                "SELECT t.*, a.account_code, a.account_name
                 FROM `" . $this->db->getPrefix() . "transactions` t
                 JOIN `" . $this->db->getPrefix() . "accounts` a ON t.account_id = a.id
                 WHERE t.status = 'posted'
                 ORDER BY t.transaction_date DESC, t.id DESC
                 LIMIT " . intval($limit)
            );
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getOverdueInvoices($limit = 5) {
        try {
            return $this->db->fetchAll(
                "SELECT i.*, c.company_name
                 FROM `" . $this->db->getPrefix() . "invoices` i
                 JOIN `" . $this->db->getPrefix() . "customers` c ON i.customer_id = c.id
                 WHERE i.status IN ('sent', 'partially_paid', 'overdue') 
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

