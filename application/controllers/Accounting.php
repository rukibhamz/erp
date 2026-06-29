<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accounting extends Base_Controller {
    private const PAYMENT_REFERENCE_TYPES = [
        'booking_payment',
        'invoice_payment',
        'bill_payment',
        'rent_payment',
        'utility_payment',
        'payment',
        'education_tax_payment',
    ];

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
        $recentPayments = [];
        $recentLedgerActivity = [];
        $overdueInvoices = [];
        $overdueBills = [];
        
        try {
            $cashBalance = $this->getCashBalance();
            $receivables = $this->getTotalReceivables();
            $payables = $this->getTotalPayables();
            $profitLoss = $this->getProfitLoss();
            
            $recentPayments = $this->getRecentPayments(10);
            $recentLedgerActivity = $this->getRecentLedgerActivity(10);
            
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
            'recent_payments' => $recentPayments,
            'recent_ledger_activity' => $recentLedgerActivity,
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
    
    private function getRecentTransactionBaseSql(): string {
        $prefix = $this->db->getPrefix();

        return "SELECT t.*,
                        a.account_code,
                        a.account_name,
                        b.booking_number,
                        b.customer_name AS booking_customer,
                        i.invoice_number,
                        ic.company_name AS invoice_customer,
                        COALESCE(b.customer_name, ic.company_name) AS customer_name
                 FROM `{$prefix}transactions` t
                 JOIN `{$prefix}accounts` a ON t.account_id = a.id
                 LEFT JOIN `{$prefix}bookings` b
                    ON t.reference_type IN ('booking_payment', 'booking_revenue') AND t.reference_id = b.id
                 LEFT JOIN `{$prefix}invoices` i
                    ON t.reference_type = 'invoice' AND t.reference_id = i.id
                 LEFT JOIN `{$prefix}customers` ic ON i.customer_id = ic.id";
    }

    private function getPaymentReferenceTypeInList(): string {
        return "'" . implode("','", self::PAYMENT_REFERENCE_TYPES) . "'";
    }

    /**
     * Cash/bank side of payment postings only (one row per payment, not AR/AP clearing lines).
     */
    private function getRecentPayments($limit = 10) {
        try {
            $prefix = $this->db->getPrefix();
            $paymentTypes = $this->getPaymentReferenceTypeInList();
            $cashAccountIds = $this->getCashGlAccountIdsSubquery($prefix);

            return $this->db->fetchAll(
                $this->getRecentTransactionBaseSql() . "
                 WHERE t.status = 'posted'
                 AND t.reference_type IN ({$paymentTypes})
                 AND (
                     t.account_id IN ({$cashAccountIds})
                     OR (a.account_type = 'Assets' AND a.account_code LIKE '10%')
                 )
                 ORDER BY t.transaction_date DESC, t.id DESC
                 LIMIT " . intval($limit)
            );
        } catch (Exception $e) {
            error_log('getRecentPayments error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Non-payment ledger lines (invoices raised, revenue, VAT, journals, bills, etc.).
     */
    private function getRecentLedgerActivity($limit = 10) {
        try {
            $paymentTypes = $this->getPaymentReferenceTypeInList();

            return $this->db->fetchAll(
                $this->getRecentTransactionBaseSql() . "
                 WHERE t.status = 'posted'
                 AND t.reference_type NOT IN ({$paymentTypes})
                 ORDER BY t.transaction_date DESC, t.id DESC
                 LIMIT " . intval($limit)
            );
        } catch (Exception $e) {
            error_log('getRecentLedgerActivity error: ' . $e->getMessage());
            return [];
        }
    }

    private function getCashGlAccountIdsSubquery(string $prefix): string {
        return "SELECT ca.account_id
                FROM `{$prefix}cash_accounts` ca
                WHERE ca.account_id IS NOT NULL";
    }

    /** @deprecated Use getRecentPayments() or getRecentLedgerActivity() */
    private function getRecentTransactions($limit = 10) {
        return $this->getRecentLedgerActivity($limit);
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
