<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rent_invoices extends Base_Controller {
    private $rentInvoiceModel;
    private $leaseModel;
    private $rentPaymentModel;
    private $transactionModel;
    private $accountModel;
    private $invoiceModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('locations', 'read');
        $this->rentInvoiceModel = $this->loadModel('Rent_invoice_model');
        $this->leaseModel = $this->loadModel('Lease_model');
        $this->rentPaymentModel = $this->loadModel('Rent_payment_model');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $status = $_GET['status'] ?? 'all';
        
        try {
            if ($status === 'all') {
                $invoices = $this->rentInvoiceModel->getAll();
            } elseif ($status === 'overdue') {
                $invoices = $this->rentInvoiceModel->getOverdue();
            } else {
                $invoices = $this->rentInvoiceModel->getAll();
                $invoices = array_filter($invoices, function($inv) use ($status) {
                    return $inv['status'] === $status;
                });
            }
        } catch (Exception $e) {
            $invoices = [];
        }
        
        $data = [
            'page_title' => 'Rent Invoices',
            'invoices' => $invoices,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('rent_invoices/index', $data);
    }
    
    /**
     * Generate rent invoice for a lease
     */
    public function generate($leaseId, $periodStart = null, $periodEnd = null) {
        $this->requirePermission('locations', 'create');
        
        try {
            $lease = $this->leaseModel->getWithDetails($leaseId);
            if (!$lease) {
                $this->setFlashMessage('danger', 'Lease not found.');
                redirect('leases');
            }
            
            // Default to current period if not specified
            if (!$periodStart) {
                $periodStart = date('Y-m-01'); // First day of current month
            }
            if (!$periodEnd) {
                $periodEnd = date('Y-m-t'); // Last day of current month
            }
            
            // Check if invoice already exists for this period
            $existing = $this->rentInvoiceModel->getByLease($leaseId);
            $duplicate = array_filter($existing, function($inv) use ($periodStart, $periodEnd) {
                return $inv['period_start'] === $periodStart && $inv['period_end'] === $periodEnd;
            });
            
            if (!empty($duplicate)) {
                $this->setFlashMessage('warning', 'Invoice already exists for this period.');
                redirect('rent-invoices');
            }
            
            // Calculate charges
            $rentAmount = floatval($lease['rent_amount']);
            $serviceCharge = floatval($lease['service_charge']);
            $utilityCharge = 0; // Can be calculated separately
            $totalAmount = $rentAmount + $serviceCharge + $utilityCharge;
            
            // Determine due date based on rent_due_date
            $dueDate = date('Y-m-' . str_pad($lease['rent_due_date'], 2, '0', STR_PAD_LEFT));
            if ($dueDate < $periodEnd) {
                // If due date has passed, set for next month
                $dueDate = date('Y-m-' . str_pad($lease['rent_due_date'], 2, '0', STR_PAD_LEFT), strtotime('+1 month'));
            }
            
            $invoiceData = [
                'invoice_number' => $this->rentInvoiceModel->getNextInvoiceNumber(),
                'lease_id' => $leaseId,
                'tenant_id' => $lease['tenant_id'],
                'space_id' => $lease['space_id'],
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'rent_amount' => $rentAmount,
                'service_charge' => $serviceCharge,
                'utility_charge' => $utilityCharge,
                'total_amount' => $totalAmount,
                'balance_amount' => $totalAmount,
                'due_date' => $dueDate,
                'invoice_date' => date('Y-m-d'),
                'status' => 'draft',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $invoiceId = $this->rentInvoiceModel->create($invoiceData);
            
            if ($invoiceId) {
                // Post to accounting - Create Accounts Receivable entry
                $this->postRentInvoiceToAccounting($invoiceId, $invoiceData, $lease);
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Rent Invoices', 'Generated rent invoice: ' . $invoiceData['invoice_number']);
                $this->setFlashMessage('success', 'Rent invoice generated successfully.');
                redirect('rent-invoices/view/' . $invoiceId);
            } else {
                $this->setFlashMessage('danger', 'Failed to generate rent invoice.');
            }
        } catch (Exception $e) {
            error_log('Rent_invoices generate error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error generating invoice: ' . $e->getMessage());
        }
        
        redirect('leases/view/' . $leaseId);
    }
    
    /**
     * Auto-generate monthly invoices for all active leases
     */
    public function autoGenerate() {
        $this->requirePermission('locations', 'create');
        
        try {
            $activeLeases = $this->leaseModel->getActive();
            $generated = 0;
            $errors = 0;
            
            foreach ($activeLeases as $lease) {
                // Check if invoice already exists for current month
                $existing = $this->rentInvoiceModel->getByLease($lease['id']);
                $currentMonthStart = date('Y-m-01');
                $currentMonthEnd = date('Y-m-t');
                
                $duplicate = array_filter($existing, function($inv) use ($currentMonthStart, $currentMonthEnd) {
                    return $inv['period_start'] === $currentMonthStart && $inv['period_end'] === $currentMonthEnd;
                });
                
                if (empty($duplicate)) {
                    // Generate invoice
                    $this->generate($lease['id'], $currentMonthStart, $currentMonthEnd);
                    $generated++;
                }
            }
            
            $this->setFlashMessage('success', "Generated {$generated} rent invoices successfully.");
        } catch (Exception $e) {
            error_log('Rent_invoices autoGenerate error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error auto-generating invoices.');
        }
        
        redirect('rent-invoices');
    }
    
    public function view($id) {
        try {
            $invoice = $this->rentInvoiceModel->getById($id);
            if (!$invoice) {
                $this->setFlashMessage('danger', 'Invoice not found.');
                redirect('rent-invoices');
            }
            
            // Get payments for this invoice
            $payments = $this->rentPaymentModel->getByInvoice($id);
            
            // Get lease details
            $lease = null;
            try {
                $lease = $this->leaseModel->getWithDetails($invoice['lease_id']);
            } catch (Exception $e) {
                error_log('Rent_invoices view - lease load error: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading invoice.');
            redirect('rent-invoices');
        }
        
        $data = [
            'page_title' => 'Rent Invoice: ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'lease' => $lease,
            'payments' => $payments,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('rent_invoices/view', $data);
    }
    
    /**
     * Record rent payment
     */
    public function recordPayment() {
        $this->requirePermission('locations', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $invoiceId = intval($_POST['invoice_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'bank_transfer');
            $paymentDate = sanitize_input($_POST['payment_date'] ?? date('Y-m-d'));
            
            if (!$invoiceId || $amount <= 0) {
                $this->setFlashMessage('danger', 'Invalid payment details.');
                redirect('rent-invoices');
            }
            
            try {
                $this->processRentPayment($invoiceId, $amount, $paymentMethod, $paymentDate);
                $this->setFlashMessage('success', 'Payment recorded successfully.');
            } catch (Exception $e) {
                error_log('Rent_invoices recordPayment error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to record payment: ' . $e->getMessage());
            }
        }
        
        redirect('rent-invoices/view/' . $invoiceId);
    }
    
    /**
     * Process rent payment and post to accounting
     */
    private function processRentPayment($invoiceId, $amount, $paymentMethod, $paymentDate) {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        
        try {
            $invoice = $this->rentInvoiceModel->getById($invoiceId);
            if (!$invoice) {
                throw new Exception('Invoice not found');
            }
            
            $lease = $this->leaseModel->getWithDetails($invoice['lease_id']);
            
            // Create payment record
            $paymentData = [
                'payment_number' => $this->rentPaymentModel->getNextPaymentNumber(),
                'receipt_number' => $this->rentPaymentModel->getNextReceiptNumber(),
                'invoice_id' => $invoiceId,
                'lease_id' => $lease['id'],
                'tenant_id' => $invoice['tenant_id'],
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod,
                'reference_number' => sanitize_input($_POST['reference_number'] ?? ''),
                'notes' => sanitize_input($_POST['notes'] ?? ''),
                'created_by' => $this->session['user_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $paymentId = $this->rentPaymentModel->create($paymentData);
            
            // Update invoice payment status
            $this->rentInvoiceModel->updatePaymentStatus($invoiceId, $amount);
            
            // Post to accounting
            $this->postRentPaymentToAccounting($paymentId, $paymentData, $invoice, $lease);
            
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Post rent invoice to accounting (AR entry)
     */
    private function postRentInvoiceToAccounting($invoiceId, $invoiceData, $lease) {
        try {
            // Try to find AR account by searching account name
            $arAccounts = $this->accountModel->getByType('Assets');
            $arAccount = null;
            foreach ($arAccounts as $acc) {
                if (stripos($acc['account_name'], 'receivable') !== false || 
                    stripos($acc['account_name'], 'ar') !== false) {
                    $arAccount = $acc;
                    break;
                }
            }
            if (!$arAccount && !empty($arAccounts)) {
                $arAccount = $arAccounts[0]; // Fallback to first asset account
            }
            
            // Try to find Rent Revenue account
            $revenueAccounts = $this->accountModel->getByType('Revenue');
            $rentRevenueAccount = null;
            foreach ($revenueAccounts as $acc) {
                if (stripos($acc['account_name'], 'rent') !== false || 
                    stripos($acc['account_name'], 'rental') !== false) {
                    $rentRevenueAccount = $acc;
                    break;
                }
            }
            if (!$rentRevenueAccount && !empty($revenueAccounts)) {
                $rentRevenueAccount = $revenueAccounts[0]; // Fallback to first revenue account
            }
            
            if (!$arAccount || !$rentRevenueAccount) {
                error_log('No AR or Revenue account found for rent invoice accounting entry.');
                return;
            }
            
            // Entry 1: Debit Accounts Receivable
            $this->transactionModel->create([
                'transaction_number' => $invoiceData['invoice_number'] . '-AR',
                'transaction_date' => $invoiceData['invoice_date'],
                'transaction_type' => 'invoice',
                'reference_id' => $invoiceId,
                'reference_type' => 'rent_invoice',
                'account_id' => $arAccount['id'],
                'description' => 'Rent receivable - ' . $invoiceData['invoice_number'],
                'debit' => $invoiceData['total_amount'],
                'credit' => 0,
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($arAccount['id'], $invoiceData['total_amount'], 'debit');
            
            // Entry 2: Credit Rent Revenue
            $this->transactionModel->create([
                'transaction_number' => $invoiceData['invoice_number'] . '-REV',
                'transaction_date' => $invoiceData['invoice_date'],
                'transaction_type' => 'revenue',
                'reference_id' => $invoiceId,
                'reference_type' => 'rent_invoice',
                'account_id' => $rentRevenueAccount['id'],
                'description' => 'Rent revenue - ' . $invoiceData['invoice_number'],
                'debit' => 0,
                'credit' => $invoiceData['total_amount'],
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($rentRevenueAccount['id'], $invoiceData['total_amount'], 'credit');
            
        } catch (Exception $e) {
            error_log('Rent_invoices postRentInvoiceToAccounting error: ' . $e->getMessage());
        }
    }
    
    /**
     * Post rent payment to accounting (Cash receipt)
     */
    private function postRentPaymentToAccounting($paymentId, $paymentData, $invoice, $lease) {
        try {
            // Find Cash account
            $assetAccounts = $this->accountModel->getByType('Assets');
            $cashAccount = null;
            foreach ($assetAccounts as $acc) {
                if (stripos($acc['account_name'], 'cash') !== false || 
                    stripos($acc['account_name'], 'bank') !== false) {
                    $cashAccount = $acc;
                    break;
                }
            }
            if (!$cashAccount && !empty($assetAccounts)) {
                $cashAccount = $assetAccounts[0]; // Fallback
            }
            
            // Find AR account
            $arAccount = null;
            foreach ($assetAccounts as $acc) {
                if (stripos($acc['account_name'], 'receivable') !== false || 
                    stripos($acc['account_name'], 'ar') !== false) {
                    $arAccount = $acc;
                    break;
                }
            }
            if (!$arAccount && !empty($assetAccounts)) {
                $arAccount = $assetAccounts[0]; // Fallback
            }
            
            if (!$cashAccount || !$arAccount) {
                error_log('No Cash or AR account found for rent payment accounting entry.');
                return;
            }
            
            // Entry 1: Debit Cash
            $this->transactionModel->create([
                'transaction_number' => $paymentData['payment_number'] . '-CASH',
                'transaction_date' => $paymentData['payment_date'],
                'transaction_type' => 'receipt',
                'reference_id' => $paymentId,
                'reference_type' => 'rent_payment',
                'account_id' => $cashAccount['id'],
                'description' => 'Rent payment received - ' . $paymentData['payment_number'],
                'debit' => $paymentData['amount'],
                'credit' => 0,
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($cashAccount['id'], $paymentData['amount'], 'debit');
            
            // Entry 2: Credit Accounts Receivable
            $this->transactionModel->create([
                'transaction_number' => $paymentData['payment_number'] . '-AR',
                'transaction_date' => $paymentData['payment_date'],
                'transaction_type' => 'receipt',
                'reference_id' => $paymentId,
                'reference_type' => 'rent_payment',
                'account_id' => $arAccount['id'],
                'description' => 'Rent payment - ' . $paymentData['payment_number'],
                'debit' => 0,
                'credit' => $paymentData['amount'],
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($arAccount['id'], $paymentData['amount'], 'credit');
            
        } catch (Exception $e) {
            error_log('Rent_invoices postRentPaymentToAccounting error: ' . $e->getMessage());
        }
    }
}

