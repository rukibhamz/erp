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
    private $productModel;
    private $companyModel;
    private $transactionService;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('receivables', 'read');
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->customerModel = $this->loadModel('Customer_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->cashAccountModel = $this->loadModel('Cash_account_model');
        $this->paymentModel = $this->loadModel('Payment_model');
        $this->activityModel = $this->loadModel('Activity_model');
        $this->entityModel = $this->loadModel('Entity_model');
        $this->productModel = $this->loadModel('Product_model');
        $this->companyModel = $this->loadModel('Company_model');
        
        // Load Transaction Service
        require_once BASEPATH . 'services/Transaction_service.php';
        $this->transactionService = new Transaction_service();
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
    
    public function viewCustomer($id) {
        $this->requirePermission('receivables', 'read');
        
        // Validate ID parameter
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid customer ID.');
            redirect('receivables/customers');
            return;
        }
        
        try {
            $customer = $this->customerModel->getById($id);
            if (!$customer) {
                error_log("Receivables viewCustomer: Customer not found for ID: {$id}");
                $this->setFlashMessage('danger', 'Customer not found.');
                redirect('receivables/customers');
                return;
            }
            
            error_log("Receivables viewCustomer: Successfully loaded customer ID: {$id}");
            
            // Get customer's invoices
            $invoices = $this->invoiceModel->getByCustomer($id);
            
            // Get outstanding balance
            $outstanding = $this->customerModel->getTotalOutstanding($id);
            
            $data = [
                'page_title' => 'Customer: ' . ($customer['company_name'] ?? 'N/A'),
                'customer' => $customer,
                'invoices' => $invoices,
                'outstanding' => $outstanding,
                'flash' => $this->getFlashMessage()
            ];
            
            $this->loadView('receivables/view_customer', $data);
            
        } catch (Exception $e) {
            error_log('Receivables viewCustomer error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading customer.');
            redirect('receivables/customers');
        }
    }
    
    public function editCustomer($id) {
        $this->requirePermission('receivables', 'update');
        
        // Validate ID parameter
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid customer ID.');
            redirect('receivables/customers');
            return;
        }
        
        try {
            // Load complete customer data with all columns
            $customer = $this->customerModel->getById($id);
            if (!$customer) {
                error_log("Receivables editCustomer: Customer not found for ID: {$id}");
                $this->setFlashMessage('danger', 'Customer not found.');
                redirect('receivables/customers');
                return;
            }
            
            // Ensure all fields are present with defaults
            $customer['customer_code'] = $customer['customer_code'] ?? '';
            $customer['company_name'] = $customer['company_name'] ?? '';
            $customer['contact_name'] = $customer['contact_name'] ?? '';
            $customer['email'] = $customer['email'] ?? '';
            $customer['phone'] = $customer['phone'] ?? '';
            $customer['address'] = $customer['address'] ?? '';
            $customer['city'] = $customer['city'] ?? '';
            $customer['state'] = $customer['state'] ?? '';
            $customer['zip_code'] = $customer['zip_code'] ?? '';
            $customer['country'] = $customer['country'] ?? '';
            $customer['tax_id'] = $customer['tax_id'] ?? '';
            $customer['credit_limit'] = $customer['credit_limit'] ?? 0;
            $customer['payment_terms'] = $customer['payment_terms'] ?? '';
            $customer['currency'] = $customer['currency'] ?? 'USD';
            $customer['status'] = $customer['status'] ?? 'active';
            
            error_log("Receivables editCustomer: Successfully loaded customer ID: {$id} with all fields");
        } catch (Exception $e) {
            error_log('Receivables editCustomer load error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading customer.');
            redirect('receivables/customers');
            return;
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
            $sql = "SELECT i.id, i.invoice_number, i.invoice_date, i.due_date, i.total_amount, 
                           i.paid_amount, i.balance_amount, i.status, i.currency,
                           c.company_name 
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
        
        // Get customer_id from query string if provided (when creating from customer list)
        $preselectedCustomerId = intval($_GET['customer_id'] ?? 0);
        
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
                
                // If status is 'sent', create journal entry
            if ($invoiceData['status'] === 'sent') {
                try {
                    // Get Accounts Receivable account (1200)
                    $arAccount = $this->accountModel->getByCode('1200');
                    // Get Sales Revenue account (4000) - or use first revenue account
                    $revenueAccount = $this->accountModel->getByCode('4000');
                    if (!$revenueAccount) {
                        $revenueAccounts = $this->accountModel->getByType('Revenue');
                        $revenueAccount = !empty($revenueAccounts) ? $revenueAccounts[0] : null;
                    }
                    
                    if ($arAccount && $revenueAccount) {
                        $journalData = [
                            'date' => $invoiceDate,
                            'reference_type' => 'invoice',
                            'reference_id' => $invoiceId,
                            'description' => 'Invoice ' . $invoiceData['invoice_number'],
                            'journal_type' => 'sales',
                            'entries' => [
                                [
                                    'account_id' => $arAccount['id'],
                                    'debit' => $totalAmount,
                                    'credit' => 0.00,
                                    'description' => 'Accounts Receivable'
                                ],
                                [
                                    'account_id' => $revenueAccount['id'],
                                    'debit' => 0.00,
                                    'credit' => $totalAmount,
                                    'description' => 'Sales Revenue'
                                ]
                            ],
                            'created_by' => $this->session['user_id'],
                            'auto_post' => true
                        ];
                        
                        $this->transactionService->postJournalEntry($journalData);
                    }
                } catch (Exception $e) {
                    error_log('Receivables createInvoice journal entry error: ' . $e->getMessage());
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
            'preselected_customer_id' => $preselectedCustomerId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('receivables/create_invoice', $data);
    }
    
    
    public function editInvoice($id) {
        // Allow both read and update permissions for viewing/editing invoices
        if (!$this->checkPermission('receivables', 'read') && !$this->checkPermission('receivables', 'update')) {
            $this->setFlashMessage('danger', 'You do not have permission to view invoices.');
            redirect('receivables/invoices');
            return;
        }
        
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid invoice ID.');
            redirect('receivables/invoices');
            return;
        }
        
        // Handle POST request for updating invoice
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePermission('receivables', 'update');
            check_csrf();
            
            $invoice = $this->invoiceModel->getById($id);
            if (!$invoice) {
                $this->setFlashMessage('danger', 'Invoice not found.');
                redirect('receivables/invoices');
                return;
            }
            
            $invoiceDate = sanitize_input($_POST['invoice_date'] ?? $invoice['invoice_date']);
            $dueDate = sanitize_input($_POST['due_date'] ?? $invoice['due_date']);
            $reference = sanitize_input($_POST['reference'] ?? '');
            $currency = sanitize_input($_POST['currency'] ?? $invoice['currency']);
            $terms = sanitize_input($_POST['terms'] ?? '');
            $notes = sanitize_input($_POST['notes'] ?? '');
            $status = sanitize_input($_POST['status'] ?? $invoice['status']);
            
            // Calculate totals from items (including individual item tax rates)
            $items = $_POST['items'] ?? [];
            $subtotal = 0;
            $totalTax = 0;
            foreach ($items as $item) {
                $quantity = floatval($item['quantity'] ?? 0);
                $unitPrice = floatval($item['unit_price'] ?? 0);
                $itemTaxRate = floatval($item['tax_rate'] ?? 0);
                $lineSubtotal = $quantity * $unitPrice;
                $lineTax = $lineSubtotal * ($itemTaxRate / 100);
                $subtotal += $lineSubtotal;
                $totalTax += $lineTax;
            }
            
            // Calculate average tax rate for display purposes
            $taxRate = $subtotal > 0 ? ($totalTax / $subtotal) * 100 : 0;
            
            $discountAmount = floatval($_POST['discount_amount'] ?? 0);
            $totalAmount = $subtotal + $totalTax - $discountAmount;
            $balanceAmount = $totalAmount - floatval($invoice['paid_amount'] ?? 0);
            
            // Update invoice
            $updateData = [
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'reference' => $reference,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $totalTax,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'balance_amount' => max(0, $balanceAmount),
                'currency' => $currency,
                'terms' => $terms,
                'notes' => $notes,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->invoiceModel->update($id, $updateData)) {
                // Delete existing items and recreate
                $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "invoice_items` WHERE invoice_id = ?", [$id]);
                
                // Add new items
                foreach ($items as $item) {
                    $quantity = floatval($item['quantity'] ?? 0);
                    $unitPrice = floatval($item['unit_price'] ?? 0);
                    $itemTaxRate = floatval($item['tax_rate'] ?? 0);
                    $lineSubtotal = $quantity * $unitPrice;
                    $lineTax = $lineSubtotal * ($itemTaxRate / 100);
                    $lineTotal = $lineSubtotal + $lineTax;
                    
                    $itemData = [
                        'product_id' => !empty($item['product_id']) ? intval($item['product_id']) : null,
                        'item_description' => sanitize_input($item['description'] ?? ''),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'tax_rate' => $itemTaxRate,
                        'tax_amount' => $lineTax,
                        'discount_rate' => floatval($item['discount_rate'] ?? 0),
                        'discount_amount' => floatval($item['discount_amount'] ?? 0),
                        'line_total' => $lineTotal,
                        'account_id' => !empty($item['account_id']) ? intval($item['account_id']) : null
                    ];
                    
                    $this->invoiceModel->addItem($id, $itemData);
                }
                
                // Auto-generate PDF
                $this->generateInvoicePdf($id);
                
                $this->activityModel->log($this->session['user_id'], 'update', 'Receivables', 'Updated invoice: ' . $invoice['invoice_number']);
                $this->setFlashMessage('success', 'Invoice updated successfully.');
                redirect('receivables/invoices/edit/' . $id);
                return;
            } else {
                $this->setFlashMessage('danger', 'Failed to update invoice.');
            }
        }
        
        // Load complete invoice data with customer information
        $invoice = $this->invoiceModel->getWithCustomer($id);
        if (!$invoice) {
            $this->setFlashMessage('danger', 'Invoice not found.');
            redirect('receivables/invoices');
            return;
        }
        
        // Ensure all invoice fields are present with defaults
        $invoice['invoice_number'] = $invoice['invoice_number'] ?? '';
        $invoice['invoice_date'] = $invoice['invoice_date'] ?? date('Y-m-d');
        $invoice['due_date'] = $invoice['due_date'] ?? date('Y-m-d');
        $invoice['customer_id'] = $invoice['customer_id'] ?? 0;
        $invoice['reference'] = $invoice['reference'] ?? '';
        $invoice['subtotal'] = $invoice['subtotal'] ?? 0;
        $invoice['tax_rate'] = $invoice['tax_rate'] ?? 0;
        $invoice['tax_amount'] = $invoice['tax_amount'] ?? 0;
        $invoice['discount_amount'] = $invoice['discount_amount'] ?? 0;
        $invoice['total_amount'] = $invoice['total_amount'] ?? 0;
        $invoice['paid_amount'] = $invoice['paid_amount'] ?? 0;
        $invoice['balance_amount'] = $invoice['balance_amount'] ?? 0;
        $invoice['currency'] = $invoice['currency'] ?? 'USD';
        $invoice['terms'] = $invoice['terms'] ?? '';
        $invoice['notes'] = $invoice['notes'] ?? '';
        $invoice['status'] = $invoice['status'] ?? 'draft';
        
        // Load invoice items
        $items = $this->invoiceModel->getItems($id);
        
        error_log("Receivables editInvoice: Successfully loaded invoice ID: {$id} with all fields and " . count($items) . " items");
        
        // Get customers and accounts for dropdowns
        try {
            $customers = $this->customerModel->getAll();
            $revenueAccounts = $this->accountModel->getByType('Revenue');
        } catch (Exception $e) {
            $customers = [];
            $revenueAccounts = [];
        }
        
        $data = [
            'page_title' => 'Edit Invoice',
            'invoice' => $invoice,
            'items' => $items,
            'customers' => $customers,
            'revenue_accounts' => $revenueAccounts,
            'currencies' => get_all_currencies(),
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('receivables/edit_invoice', $data);
    }
    
    /**
     * Generate PDF for invoice and save to disk
     * Uses the enhanced Pdf_generator library with Dompdf support
     * 
     * @param int $invoiceId Invoice ID
     * @return string|false File path if successful, false on error
     */
    private function generateInvoicePdf($invoiceId) {
        try {
            $invoice = $this->invoiceModel->getWithCustomer($invoiceId);
            if (!$invoice) {
                return false;
            }
            
            $items = $this->invoiceModel->getItems($invoiceId);
            
            if (empty($items)) {
                error_log('Invoice has no items: ' . $invoiceId);
                return false;
            }
            
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
                'tax_id' => '',
                'logo' => null
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
            
            // Load PDF generator library
            $this->loadLibrary('Pdf_generator');
            $pdfGenerator = new Pdf_generator($companyInfo);
            
            // Generate PDF
            $pdfContent = $pdfGenerator->generateInvoice($invoice, $items, $customer);
            
            // Determine file extension based on content type
            $isPdf = (substr($pdfContent, 0, 4) === '%PDF');
            $extension = $isPdf ? '.pdf' : '.html';
            
            // Create invoices directory if it doesn't exist
            $invoiceDir = BASEPATH . '../invoices/';
            if (!is_dir($invoiceDir)) {
                mkdir($invoiceDir, 0755, true);
            }
            
            // Save PDF to file
            $filename = 'invoice-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice['invoice_number']) . $extension;
            $filepath = $invoiceDir . $filename;
            file_put_contents($filepath, $pdfContent);
            
            return $filepath;
        } catch (Exception $e) {
            error_log('Error generating invoice PDF: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get PDF path for invoice
     * 
     * @param int $invoiceId Invoice ID
     * @return string|null File path if exists, null otherwise
     */
    private function getInvoicePdfPath($invoiceId) {
        $invoice = $this->invoiceModel->getById($invoiceId);
        if (!$invoice) {
            return null;
        }
        
        $invoiceDir = BASEPATH . '../invoices/';
        
        // Check for PDF first
        $pdfFilename = 'invoice-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice['invoice_number']) . '.pdf';
        $pdfPath = $invoiceDir . $pdfFilename;
        
        if (file_exists($pdfPath)) {
            return $pdfPath;
        }
        
        // Check for HTML fallback
        $htmlFilename = 'invoice-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice['invoice_number']) . '.html';
        $htmlPath = $invoiceDir . $htmlFilename;
        
        if (file_exists($htmlPath)) {
            return $htmlPath;
        }
        
        return null;
    }
    
    /**
     * View invoice - Display invoice details with PDF viewer
     */
    public function viewInvoice($id) {
        $this->requirePermission('receivables', 'read');
        
        // Validate ID parameter
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid invoice ID.');
            redirect('receivables/invoices');
            return;
        }
        
        try {
            $invoice = $this->invoiceModel->getWithCustomer($id);
            if (!$invoice) {
                error_log("Receivables viewInvoice: Invoice not found for ID: {$id}");
                $this->setFlashMessage('danger', 'Invoice not found.');
                redirect('receivables/invoices');
                return;
            }
            
            // Load invoice items - ensure we get all items
            $items = $this->invoiceModel->getItems($id);
            error_log("Receivables viewInvoice: Successfully loaded invoice ID: {$id} with " . count($items) . " items");
            
            // If no items found, log a warning
            if (empty($items)) {
                error_log("Receivables viewInvoice: WARNING - No items found for invoice ID: {$id}");
            }
            
            // Check for existing PDF
            $pdfPattern = BASEPATH . '../uploads/invoices/invoice_' . 
                          preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice['invoice_number']) . '_*.pdf';
            $pdfFiles = glob($pdfPattern);
            $pdfExists = !empty($pdfFiles);
            $pdfUrl = $pdfExists ? base_url('uploads/invoices/' . basename($pdfFiles[0])) : null;
            
            $data = [
                'page_title' => 'Invoice: ' . $invoice['invoice_number'],
                'invoice' => $invoice,
                'items' => $items,
                'pdf_exists' => $pdfExists,
                'pdf_url' => $pdfUrl,
                'flash' => $this->getFlashMessage()
            ];
            
            $this->loadView('receivables/view_invoice', $data);
            
        } catch (Exception $e) {
            error_log('View invoice error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading invoice.');
            redirect('receivables/invoices');
        }
    }
    
    /**
     * Generate PDF
     */
    public function pdfInvoice($id) {
        $this->requirePermission('receivables', 'read');
        
        // Validate ID
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid invoice ID.');
            redirect('receivables/invoices');
            return;
        }
        
        $invoice = $this->invoiceModel->getWithCustomer($id);
        if (!$invoice) {
            $this->setFlashMessage('danger', 'Invoice not found.');
            redirect('receivables/invoices');
            return;
        }
        
        // Load invoice items with error handling
        try {
            $items = $this->invoiceModel->getItems($id);
            error_log("Receivables pdfInvoice: Loaded " . count($items) . " items for invoice ID: {$id}");
        } catch (Exception $e) {
            error_log("Receivables pdfInvoice: Error loading items: " . $e->getMessage());
            $items = [];
        }
        
        if (empty($items)) {
            error_log("Receivables pdfInvoice: WARNING - No items found for invoice ID: {$id}");
            $this->setFlashMessage('warning', 'Invoice has no items. Please add items to the invoice before generating PDF.');
            redirect('receivables/invoices/view/' . $id);
            return;
        }
        
        // Get company info
        $entities = $this->entityModel->getAll();
        $company = !empty($entities) ? $entities[0] : ['name' => 'Company Name'];
        
        $customer = [
            'company_name' => $invoice['company_name'] ?? '',
            'address' => $invoice['address'] ?? '',
            'email' => $invoice['email'] ?? '',
            'phone' => $invoice['phone'] ?? ''
        ];
        
        // Load library
        require_once BASEPATH . 'libraries/Pdf_generator.php';
        $pdfGen = new Pdf_generator($company);
        $pdfContent = $pdfGen->generateInvoice($invoice, $items, $customer);
        
        // Save PDF
        $isPdf = (substr($pdfContent, 0, 4) === '%PDF');
        if ($isPdf) {
            $filename = 'invoice_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice['invoice_number']) . '_' . time() . '.pdf';
            $pdfGen->savePdf($pdfContent, $filename);
        }
        
        // Output
        header('Content-Type: ' . ($isPdf ? 'application/pdf' : 'text/html'));
        header('Content-Disposition: inline; filename="Invoice-' . $invoice['invoice_number'] . '.pdf"');
        echo $pdfContent;
        exit;
    }
    
    /**
     * Download PDF
     */
    public function downloadInvoice($id) {
        $this->requirePermission('receivables', 'read');
        
        $invoice = $this->invoiceModel->getWithCustomer($id);
        if (!$invoice) die('Invoice not found.');
        
        $items = $this->invoiceModel->getItems($id);
        
        $entityModel = $this->loadModel('Entity_model');
        $entities = $entityModel->getAll();
        $company = !empty($entities) ? $entities[0] : ['name' => 'Company'];
        
        $customer = ['company_name' => $invoice['company_name'] ?? ''];
        
        require_once BASEPATH . 'libraries/Pdf_generator.php';
        $pdfGen = new Pdf_generator($company);
        $pdfContent = $pdfGen->generateInvoice($invoice, $items, $customer);
        
        $isPdf = (substr($pdfContent, 0, 4) === '%PDF');
        $filename = 'Invoice-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice['invoice_number']) . ($isPdf ? '.pdf' : '.html');
        
        header('Content-Type: ' . ($isPdf ? 'application/pdf' : 'text/html'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $pdfContent;
        exit;
    }
    
    /**
     * Generate and download/view invoice PDF (legacy method)
     */
    public function pdf($id) {
        // Redirect to new method
        $this->pdfInvoice($id);
    }
    
    /**
     * Send invoice email
     */
    public function sendInvoiceEmail($id) {
        $this->requirePermission('receivables', 'update');
        
        $invoice = $this->invoiceModel->getWithCustomer($id);
        if (!$invoice || empty($invoice['email'])) {
            $this->setFlashMessage('danger', 'Invoice not found or no email.');
            redirect('receivables/invoices/view/' . $id);
        }
        
        $items = $this->invoiceModel->getItems($id);
        
        $entityModel = $this->loadModel('Entity_model');
        $entities = $entityModel->getAll();
        $company = !empty($entities) ? $entities[0] : ['name' => 'Company'];
        
        $customer = [
            'company_name' => $invoice['company_name'],
            'email' => $invoice['email']
        ];
        
        // Generate PDF
        require_once BASEPATH . 'libraries/Pdf_generator.php';
        $pdfGen = new Pdf_generator($company);
        $pdfContent = $pdfGen->generateInvoice($invoice, $items, $customer);
        
        $isPdf = (substr($pdfContent, 0, 4) === '%PDF');
        $pdfPath = null;
        
        if ($isPdf) {
            $filename = 'invoice_temp_' . time() . '.pdf';
            $result = $pdfGen->savePdf($pdfContent, $filename);
            if ($result['success']) {
                $pdfPath = $result['file_path'];
            }
        }
        
        // Send email
        require_once BASEPATH . 'libraries/Email_sender.php';
        $emailSender = new Email_sender();
        
        $subject = 'Invoice ' . $invoice['invoice_number'];
        $body = $this->getEmailTemplate($invoice, $customer, $company);
        
        $result = $emailSender->sendInvoice(
            $invoice['email'],
            $subject,
            $body,
            $pdfPath,
            'Invoice-' . $invoice['invoice_number'] . '.pdf'
        );
        
        if ($result['success']) {
            if ($invoice['status'] === 'draft') {
                $this->invoiceModel->update($id, ['status' => 'sent']);
            }
            $this->setFlashMessage('success', 'Invoice sent to ' . $invoice['email']);
        } else {
            $this->setFlashMessage('danger', 'Email failed: ' . $result['error']);
        }
        
        redirect('receivables/invoices/view/' . $id);
    }
    
    /**
     * Send invoice (alias for sendInvoiceEmail)
     */
    public function sendInvoice($id) {
        $this->sendInvoiceEmail($id);
    }
    
    /**
     * Get email template for invoice
     */
    private function getEmailTemplate($invoice, $customer, $company) {
        $html = '<html><body style="font-family: Arial;">';
        $html .= '<h2>Invoice ' . htmlspecialchars($invoice['invoice_number']) . '</h2>';
        $html .= '<p>Dear ' . htmlspecialchars($customer['company_name']) . ',</p>';
        $html .= '<p>Please find your invoice attached.</p>';
        $html .= '<p><strong>Amount Due:</strong> ₦' . number_format($invoice['total_amount'], 2) . '</p>';
        $html .= '<p>Thank you!</p>';
        $html .= '</body></html>';
        return $html;
    }
    
    public function recordPayment($invoiceId) {
        $this->requirePermission('receivables', 'update');
        
        $invoiceId = intval($invoiceId);
        if ($invoiceId <= 0) {
            $this->setFlashMessage('danger', 'Invalid invoice ID.');
            redirect('receivables/invoices');
            return;
        }
        
        $invoice = $this->invoiceModel->getWithCustomer($invoiceId);
        if (!$invoice) {
            $this->setFlashMessage('danger', 'Invoice not found.');
            redirect('receivables/invoices');
            return;
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
                
                // Create journal entry using Transaction Service
            try {
                // Get Accounts Receivable account (1200)
                $arAccount = $this->accountModel->getByCode('1200');
                
                if ($arAccount) {
                    $journalData = [
                        'date' => $paymentDate,
                        'reference_type' => 'invoice_payment',
                        'reference_id' => $paymentId,
                        'description' => 'Payment for Invoice ' . $invoice['invoice_number'],
                        'journal_type' => 'receipt',
                        'entries' => [
                            [
                                'account_id' => $cashAccount['account_id'],
                                'debit' => $amount,
                                'credit' => 0.00,
                                'description' => 'Cash Receipt'
                            ],
                            [
                                'account_id' => $arAccount['id'],
                                'debit' => 0.00,
                                'credit' => $amount,
                                'description' => 'Accounts Receivable'
                            ]
                        ],
                        'created_by' => $this->session['user_id'],
                        'auto_post' => true
                    ];
                    
                    $this->transactionService->postJournalEntry($journalData);
                    
                    // Update cash account balance
                    $this->cashAccountModel->updateBalance($cashAccountId, $amount, 'deposit');
                } else {
                    error_log('Receivables recordPayment: Accounts Receivable account (1200) not found');
                }
            } catch (Exception $e) {
                error_log('Receivables recordPayment journal entry error: ' . $e->getMessage());
                $this->setFlashMessage('warning', 'Payment recorded but journal entry failed. Please check logs.');
            }
                
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

