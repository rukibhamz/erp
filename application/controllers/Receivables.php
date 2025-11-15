<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Receivables extends Base_Controller {
    private $customerModel;
    private $invoiceModel;
    private $paymentModel;
    private $paymentAllocationModel;
    private $accountModel;
    private $transactionModel;
    private $cashAccountModel;
    private $activityModel;
    private $entityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('receivables', 'read');
        $this->customerModel = $this->loadModel('Customer_model');
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->paymentModel = $this->loadModel('Payment_model');
        $this->paymentAllocationModel = $this->loadModel('Payment_allocation_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->cashAccountModel = $this->loadModel('Cash_account_model');
        $this->activityModel = $this->loadModel('Activity_model');
        $this->entityModel = $this->loadModel('Entity_model');
    }
    
    // Customers Management
    public function customers() {
        try {
            $customers = $this->customerModel->getAll();
            
            // Add outstanding balance to each customer
            foreach ($customers as &$customer) {
                $customer['outstanding'] = $this->customerModel->getTotalOutstanding($customer['id']);
            }
        } catch (Exception $e) {
            $customers = [];
        }
        
        $data = [
            'page_title' => 'Customers',
            'customers' => $customers,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('receivables/customers', $data);
    }
    
    public function createCustomer() {
        $this->requirePermission('receivables', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            $data = [
                'customer_code' => sanitize_input($_POST['customer_code'] ?? ''),
                'company_name' => sanitize_input($_POST['company_name'] ?? ''),
                'contact_name' => sanitize_input($_POST['contact_name'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'city' => sanitize_input($_POST['city'] ?? ''),
                'state' => sanitize_input($_POST['state'] ?? ''),
                'zip_code' => sanitize_input($_POST['zip_code'] ?? ''),
                'country' => sanitize_input($_POST['country'] ?? ''),
                'tax_id' => sanitize_input($_POST['tax_id'] ?? ''),
                'credit_limit' => floatval($_POST['credit_limit'] ?? 0),
                'payment_terms' => sanitize_input($_POST['payment_terms'] ?? ''),
                'currency' => sanitize_input($_POST['currency'] ?? 'USD'),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];
            
            // Validate email
            if (!empty($data['email']) && !validate_email($data['email'])) {
                $this->setFlashMessage('danger', 'Invalid email address.');
                redirect('receivables/customers/create');
            }
            
            // Validate phone
            if (!empty($data['phone']) && !validate_phone($data['phone'])) {
                $this->setFlashMessage('danger', 'Invalid phone number. Please enter a valid phone number.');
                redirect('receivables/customers/create');
            }
            
            // Sanitize phone
            if (!empty($data['phone'])) {
                $data['phone'] = sanitize_phone($data['phone']);
            }
            
            // Auto-generate customer code if empty (leave blank to auto-generate)
            if (is_empty_or_whitespace($data['customer_code'])) {
                $data['customer_code'] = $this->customerModel->getNextCustomerCode();
            }
            
            if ($this->customerModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Receivables', 'Created customer: ' . $data['company_name']);
                $this->setFlashMessage('success', 'Customer created successfully.');
                redirect('receivables/customers');
            } else {
                $this->setFlashMessage('danger', 'Failed to create customer.');
            }
        }
        
        $data = [
            'page_title' => 'Create Customer',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('receivables/create_customer', $data);
    }
    
    public function editCustomer($id) {
        $this->requirePermission('receivables', 'update');
        
        $customer = $this->customerModel->getById($id);
        if (!$customer) {
            $this->setFlashMessage('danger', 'Customer not found.');
            redirect('receivables/customers');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            $data = [
                'customer_code' => sanitize_input($_POST['customer_code'] ?? ''),
                'company_name' => sanitize_input($_POST['company_name'] ?? ''),
                'contact_name' => sanitize_input($_POST['contact_name'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'city' => sanitize_input($_POST['city'] ?? ''),
                'state' => sanitize_input($_POST['state'] ?? ''),
                'zip_code' => sanitize_input($_POST['zip_code'] ?? ''),
                'country' => sanitize_input($_POST['country'] ?? ''),
                'tax_id' => sanitize_input($_POST['tax_id'] ?? ''),
                'credit_limit' => floatval($_POST['credit_limit'] ?? 0),
                'payment_terms' => sanitize_input($_POST['payment_terms'] ?? ''),
                'currency' => sanitize_input($_POST['currency'] ?? 'USD'),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];
            
            if ($this->customerModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Receivables', 'Updated customer: ' . $data['company_name']);
                $this->setFlashMessage('success', 'Customer updated successfully.');
                redirect('receivables/customers');
            } else {
                $this->setFlashMessage('danger', 'Failed to update customer.');
            }
        }
        
        $data = [
            'page_title' => 'Edit Customer',
            'customer' => $customer,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('receivables/edit_customer', $data);
    }
    
    // Invoices Management
    public function invoices() {
        $status = $_GET['status'] ?? null;
        $customerId = $_GET['customer_id'] ?? null;
        
        try {
            $sql = "SELECT i.*, c.company_name 
                    FROM `" . $this->db->getPrefix() . "invoices` i
                    JOIN `" . $this->db->getPrefix() . "customers` c ON i.customer_id = c.id";
            
            $params = [];
            $where = [];
            
            if ($status) {
                $where[] = "i.status = ?";
                $params[] = $status;
            }
            
            if ($customerId) {
                $where[] = "i.customer_id = ?";
                $params[] = $customerId;
            }
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            $sql .= " ORDER BY i.invoice_date DESC";
            
            $invoices = $this->db->fetchAll($sql, $params);
            $customers = $this->customerModel->getAll();
        } catch (Exception $e) {
            $invoices = [];
            $customers = [];
        }
        
        $data = [
            'page_title' => 'Invoices',
            'invoices' => $invoices,
            'customers' => $customers,
            'selected_status' => $status,
            'selected_customer' => $customerId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('receivables/invoices', $data);
    }
    
    public function createInvoice() {
        $this->requirePermission('receivables', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            $customerId = intval($_POST['customer_id'] ?? 0);
            $invoiceDate = sanitize_input($_POST['invoice_date'] ?? date('Y-m-d'));
            $dueDate = sanitize_input($_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')));
            $taxRate = floatval($_POST['tax_rate'] ?? 0);
            
            // Calculate totals from items
            $items = $_POST['items'] ?? [];
            $subtotal = 0;
            foreach ($items as $item) {
                $quantity = floatval($item['quantity'] ?? 0);
                $unitPrice = floatval($item['unit_price'] ?? 0);
                $lineTotal = $quantity * $unitPrice;
                $subtotal += $lineTotal;
            }
            
            $taxAmount = $subtotal * ($taxRate / 100);
            $discountAmount = floatval($_POST['discount_amount'] ?? 0);
            $totalAmount = $subtotal + $taxAmount - $discountAmount;
            
            // Create invoice
            $invoiceData = [
                'invoice_number' => $this->invoiceModel->getNextInvoiceNumber(),
                'customer_id' => $customerId,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'reference' => sanitize_input($_POST['reference'] ?? ''),
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'balance_amount' => $totalAmount,
                'currency' => sanitize_input($_POST['currency'] ?? 'USD'),
                'terms' => sanitize_input($_POST['terms'] ?? ''),
                'notes' => sanitize_input($_POST['notes'] ?? ''),
                'status' => sanitize_input($_POST['status'] ?? 'draft'),
                'created_by' => $this->session['user_id']
            ];
            
            $invoiceId = $this->invoiceModel->create($invoiceData);
            
            if ($invoiceId) {
                // Create invoice items using Invoice_model's addItem method
                foreach ($items as $item) {
                    $quantity = floatval($item['quantity'] ?? 0);
                    $unitPrice = floatval($item['unit_price'] ?? 0);
                    $itemTaxRate = floatval($item['tax_rate'] ?? $taxRate);
                    $lineTotal = $quantity * $unitPrice;
                    
                    $itemData = [
                        'product_id' => !empty($item['product_id']) ? intval($item['product_id']) : null,
                        'item_description' => sanitize_input($item['description'] ?? ''),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'tax_rate' => $itemTaxRate,
                        'tax_amount' => $lineTotal * ($itemTaxRate / 100),
                        'discount_rate' => floatval($item['discount_rate'] ?? 0),
                        'discount_amount' => floatval($item['discount_amount'] ?? 0),
                        'line_total' => $lineTotal,
                        'account_id' => !empty($item['account_id']) ? intval($item['account_id']) : null
                    ];
                    
                    $this->invoiceModel->addItem($invoiceId, $itemData);
                }
                
                // If status is 'sent', create transaction
                if ($invoiceData['status'] === 'sent') {
                    $customer = $this->customerModel->getById($customerId);
                    if ($customer && $customer['account_id']) {
                        $this->createInvoiceTransaction($invoiceId, $customer['account_id'], $totalAmount);
                    }
                }
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Receivables', 'Created invoice: ' . $invoiceData['invoice_number']);
                $this->setFlashMessage('success', 'Invoice created successfully.');
                redirect('receivables/invoices/edit/' . $invoiceId);
            } else {
                $this->setFlashMessage('danger', 'Failed to create invoice.');
            }
        }
        
        try {
            $customers = $this->customerModel->getAll();
            $revenueAccounts = $this->accountModel->getByType('Revenue');
        } catch (Exception $e) {
            $customers = [];
            $revenueAccounts = [];
        }
        
        $data = [
            'page_title' => 'Create Invoice',
            'customers' => $customers,
            'revenue_accounts' => $revenueAccounts,
            'currencies' => get_all_currencies(),
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('receivables/create_invoice', $data);
    }
    
    public function editInvoice($id) {
        // Allow both read and update permissions for viewing/editing invoices
        if (!$this->checkPermission('receivables', 'read') && !$this->checkPermission('receivables', 'update')) {
            $this->setFlashMessage('danger', 'You do not have permission to view invoices.');
            redirect('receivables/invoices');
        }
        
        $invoice = $this->invoiceModel->getWithCustomer($id);
        if (!$invoice) {
            $this->setFlashMessage('danger', 'Invoice not found.');
            redirect('receivables/invoices');
        }
        
        $items = $this->invoiceModel->getItems($id);
        
        $data = [
            'page_title' => 'Edit Invoice',
            'invoice' => $invoice,
            'items' => $items,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('receivables/edit_invoice', $data);
    }
    
    /**
     * Generate and download/view invoice PDF
     */
    public function pdf($id) {
        $this->requirePermission('receivables', 'read');
        
        $invoice = $this->invoiceModel->getWithCustomer($id);
        if (!$invoice) {
            $this->setFlashMessage('danger', 'Invoice not found.');
            redirect('receivables/invoices');
        }
        
        $items = $this->invoiceModel->getItems($id);
        
        // Get company/entity information
        $entities = $this->entityModel->getAll();
        $companyInfo = !empty($entities) ? $entities[0] : [
            'name' => $this->config['app_name'] ?? 'Company Name',
            'address' => '',
            'city' => '',
            'state' => '',
            'zip_code' => '',
            'country' => '',
            'phone' => '',
            'email' => '',
            'tax_id' => ''
        ];
        
        // Prepare customer data
        $customer = [
            'company_name' => $invoice['company_name'] ?? '',
            'address' => $invoice['address'] ?? '',
            'city' => $invoice['city'] ?? '',
            'state' => $invoice['state'] ?? '',
            'zip_code' => $invoice['zip_code'] ?? '',
            'country' => $invoice['country'] ?? '',
            'email' => $invoice['email'] ?? '',
            'phone' => $invoice['phone'] ?? ''
        ];
        
        // Load PDF view
        $data = [
            'invoice' => $invoice,
            'items' => $items,
            'customer' => $customer,
            'company' => $companyInfo
        ];
        
        // Load the PDF view directly without header/footer
        $viewFile = BASEPATH . '../application/views/receivables/invoice_pdf.php';
        if (file_exists($viewFile)) {
            extract($data);
            require_once $viewFile;
        } else {
            $this->setFlashMessage('danger', 'Invoice PDF template not found.');
            redirect('receivables/invoices');
        }
        exit;
    }
    
    public function recordPayment($invoiceId) {
        $this->requirePermission('receivables', 'update');
        
        $invoice = $this->invoiceModel->getWithCustomer($invoiceId);
        if (!$invoice) {
            $this->setFlashMessage('danger', 'Invoice not found.');
            redirect('receivables/invoices');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            $amount = floatval($_POST['amount'] ?? 0);
            $paymentDate = sanitize_input($_POST['payment_date'] ?? date('Y-m-d'));
            $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'cash');
            $cashAccountId = intval($_POST['cash_account_id'] ?? 0);
            $notes = sanitize_input($_POST['notes'] ?? '');
            
            if ($amount <= 0 || $amount > $invoice['balance_amount']) {
                $this->setFlashMessage('danger', 'Invalid payment amount.');
                redirect('receivables/invoices/edit/' . $invoiceId);
            }
            
            $customer = $this->customerModel->getById($invoice['customer_id']);
            $cashAccount = $this->cashAccountModel->getById($cashAccountId);
            
            if (!$cashAccount) {
                $this->setFlashMessage('danger', 'Cash account not found.');
                redirect('receivables/invoices/edit/' . $invoiceId);
            }
            
            // Create payment
            $paymentData = [
                'payment_number' => $this->paymentModel->getNextPaymentNumber('receipt'),
                'payment_date' => $paymentDate,
                'payment_type' => 'receipt',
                'reference_type' => 'invoice',
                'reference_id' => $invoiceId,
                'customer_id' => $invoice['customer_id'],
                'account_id' => $cashAccount['account_id'],
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'status' => 'posted',
                'created_by' => $this->session['user_id']
            ];
            
            $paymentId = $this->paymentModel->create($paymentData);
            
            if ($paymentId) {
                // Update invoice
                $this->invoiceModel->addPayment($invoiceId, $amount);
                $this->invoiceModel->updateStatus($invoiceId);
                
                // Create transactions
                // Credit customer account (Accounts Receivable)
                if ($customer && $customer['account_id']) {
                    $this->transactionModel->create([
                        'transaction_number' => $paymentData['payment_number'] . '-AR',
                        'transaction_date' => $paymentDate,
                        'transaction_type' => 'receipt',
                        'reference_id' => $paymentId,
                        'reference_type' => 'payment',
                        'account_id' => $customer['account_id'],
                        'description' => 'Payment for invoice ' . $invoice['invoice_number'],
                        'debit' => 0,
                        'credit' => $amount,
                        'status' => 'posted',
                        'created_by' => $this->session['user_id']
                    ]);
                    
                    $this->accountModel->updateBalance($customer['account_id'], $amount, 'credit');
                }
                
                // Debit cash account
                $this->transactionModel->create([
                    'transaction_number' => $paymentData['payment_number'] . '-CASH',
                    'transaction_date' => $paymentDate,
                    'transaction_type' => 'receipt',
                    'reference_id' => $paymentId,
                    'reference_type' => 'payment',
                    'account_id' => $cashAccount['account_id'],
                    'description' => 'Payment received for invoice ' . $invoice['invoice_number'],
                    'debit' => $amount,
                    'credit' => 0,
                    'status' => 'posted',
                    'created_by' => $this->session['user_id']
                ]);
                
                $this->accountModel->updateBalance($cashAccount['account_id'], $amount, 'debit');
                $this->cashAccountModel->updateBalance($cashAccountId, $amount, 'deposit');
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Receivables', 'Recorded payment for invoice: ' . $invoice['invoice_number']);
                $this->setFlashMessage('success', 'Payment recorded successfully.');
                redirect('receivables/invoices/edit/' . $invoiceId);
            } else {
                $this->setFlashMessage('danger', 'Failed to record payment.');
            }
        }
        
        $cashAccounts = $this->cashAccountModel->getActive();
        
        $data = [
            'page_title' => 'Record Payment',
            'invoice' => $invoice,
            'cash_accounts' => $cashAccounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('receivables/record_payment', $data);
    }
    
    private function createInvoiceTransaction($invoiceId, $accountId, $amount) {
        $invoice = $this->invoiceModel->getById($invoiceId);
        
        // Debit Accounts Receivable
        $this->transactionModel->create([
            'transaction_number' => $invoice['invoice_number'] . '-AR',
            'transaction_date' => $invoice['invoice_date'],
            'transaction_type' => 'invoice',
            'reference_id' => $invoiceId,
            'reference_type' => 'invoice',
            'account_id' => $accountId,
            'description' => 'Invoice ' . $invoice['invoice_number'],
            'debit' => $amount,
            'credit' => 0,
            'status' => 'posted',
            'created_by' => $this->session['user_id']
        ]);
        
        $this->accountModel->updateBalance($accountId, $amount, 'debit');
        
        // Credit Revenue from invoice items
        $items = $this->invoiceModel->getItems($invoiceId);
        foreach ($items as $item) {
            if ($item['account_id']) {
                $this->transactionModel->create([
                    'transaction_number' => $invoice['invoice_number'] . '-REV' . $item['id'],
                    'transaction_date' => $invoice['invoice_date'],
                    'transaction_type' => 'invoice',
                    'reference_id' => $invoiceId,
                    'reference_type' => 'invoice',
                    'account_id' => $item['account_id'],
                    'description' => $item['item_description'],
                    'debit' => 0,
                    'credit' => $item['line_total'],
                    'status' => 'posted',
                    'created_by' => $this->session['user_id']
                ]);
                
                $this->accountModel->updateBalance($item['account_id'], $item['line_total'], 'credit');
            }
        }
    }
    
    public function aging() {
        $this->requirePermission('receivables', 'read');
        
        $customerId = $_GET['customer_id'] ?? null;
        
        try {
            $agingReport = $this->customerModel->getAgingReport($customerId);
            $customers = $this->customerModel->getAll();
        } catch (Exception $e) {
            $agingReport = [];
            $customers = [];
        }
        
        $data = [
            'page_title' => 'Accounts Receivable Aging',
            'aging_report' => $agingReport,
            'customers' => $customers,
            'selected_customer' => $customerId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('receivables/aging', $data);
    }
    
    public function payments() {
        $this->requirePermission('receivables', 'read');
        
        try {
            $payments = $this->paymentModel->getByType('receipt');
        } catch (Exception $e) {
            $payments = [];
        }
        
        $data = [
            'page_title' => 'Receivables Payments',
            'payments' => $payments,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('receivables/payments', $data);
    }
    
    public function createPayment() {
        $this->requirePermission('receivables', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            $amount = floatval($_POST['amount'] ?? 0);
            $paymentDate = sanitize_input($_POST['payment_date'] ?? date('Y-m-d'));
            $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'cash');
            $customerId = intval($_POST['customer_id'] ?? 0);
            $cashAccountId = intval($_POST['cash_account_id'] ?? 0);
            $notes = sanitize_input($_POST['notes'] ?? '');
            
            if ($amount <= 0) {
                $this->setFlashMessage('danger', 'Invalid payment amount.');
                redirect('receivables/payments/create');
            }
            
            $customer = $this->customerModel->getById($customerId);
            $cashAccount = $this->cashAccountModel->getById($cashAccountId);
            
            if (!$customer) {
                $this->setFlashMessage('danger', 'Customer not found.');
                redirect('receivables/payments/create');
            }
            
            if (!$cashAccount) {
                $this->setFlashMessage('danger', 'Cash account not found.');
                redirect('receivables/payments/create');
            }
            
            // Create payment
            $paymentData = [
                'payment_number' => $this->paymentModel->getNextPaymentNumber('receipt'),
                'payment_date' => $paymentDate,
                'payment_type' => 'receipt',
                'customer_id' => $customerId,
                'account_id' => $cashAccount['account_id'],
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'status' => 'posted',
                'created_by' => $this->session['user_id']
            ];
            
            $paymentId = $this->paymentModel->create($paymentData);
            
            if ($paymentId) {
                // Create transactions
                if ($customer && $customer['account_id']) {
                    $this->transactionModel->create([
                        'transaction_number' => $paymentData['payment_number'] . '-AR',
                        'transaction_date' => $paymentDate,
                        'transaction_type' => 'receipt',
                        'reference_id' => $paymentId,
                        'reference_type' => 'payment',
                        'account_id' => $customer['account_id'],
                        'description' => 'Payment received from ' . $customer['customer_name'],
                        'debit' => 0,
                        'credit' => $amount,
                        'status' => 'posted',
                        'created_by' => $this->session['user_id']
                    ]);
                    
                    $this->accountModel->updateBalance($customer['account_id'], $amount, 'credit');
                }
                
                // Debit cash account
                $this->transactionModel->create([
                    'transaction_number' => $paymentData['payment_number'] . '-CASH',
                    'transaction_date' => $paymentDate,
                    'transaction_type' => 'receipt',
                    'reference_id' => $paymentId,
                    'reference_type' => 'payment',
                    'account_id' => $cashAccount['account_id'],
                    'description' => 'Payment received from ' . $customer['customer_name'],
                    'debit' => $amount,
                    'credit' => 0,
                    'status' => 'posted',
                    'created_by' => $this->session['user_id']
                ]);
                
                $this->accountModel->updateBalance($cashAccount['account_id'], $amount, 'debit');
                $this->cashAccountModel->updateBalance($cashAccountId, $amount, 'deposit');
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Receivables', 'Created payment: ' . $paymentData['payment_number']);
                $this->setFlashMessage('success', 'Payment recorded successfully.');
                redirect('receivables/payments');
            } else {
                $this->setFlashMessage('danger', 'Failed to record payment.');
            }
        }
        
        try {
            $customers = $this->customerModel->getAll();
            $cashAccounts = $this->cashAccountModel->getActive();
        } catch (Exception $e) {
            $customers = [];
            $cashAccounts = [];
        }
        
        $data = [
            'page_title' => 'Create Payment',
            'customers' => $customers,
            'cash_accounts' => $cashAccounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('receivables/create_payment', $data);
    }
}

