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
            $taxRate = floatval($_POST['tax_rate'] ?? $invoice['tax_rate']);
            $reference = sanitize_input($_POST['reference'] ?? '');
            $currency = sanitize_input($_POST['currency'] ?? $invoice['currency']);
            $terms = sanitize_input($_POST['terms'] ?? '');
            $notes = sanitize_input($_POST['notes'] ?? '');
            $status = sanitize_input($_POST['status'] ?? $invoice['status']);
            
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
            $balanceAmount = $totalAmount - floatval($invoice['paid_amount'] ?? 0);
            
            // Update invoice
            $updateData = [
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'reference' => $reference,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
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
        
        $invoice = $this->invoiceModel->getWithCustomer($id);
        if (!$invoice) {
            $this->setFlashMessage('danger', 'Invoice not found.');
            redirect('receivables/invoices');
            return;
        }
        
        $items = $this->invoiceModel->getItems($id);
        
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
        
        try {
            $invoice = $this->invoiceModel->getWithCustomer($id);
            if (!$invoice) {
                $this->setFlashMessage('danger', 'Invoice not found.');
                redirect('receivables/invoices');
            }
            
            $items = $this->invoiceModel->getItems($id);
            
            // Check if PDF exists
            $invoiceDir = BASEPATH . '../uploads/invoices/';
            $pdfPattern = $invoiceDir . 'invoice_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice['invoice_number']) . '_*.pdf';
            $pdfFiles = glob($pdfPattern);
            $pdfExists = !empty($pdfFiles);
            
            // Get the most recent PDF file
            $pdfUrl = null;
            if ($pdfExists) {
                usort($pdfFiles, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $pdfUrl = base_url('uploads/invoices/' . basename($pdfFiles[0]));
            }
            
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
     * Generate and download PDF
     */
    public function pdfInvoice($id) {
        $this->requirePermission('receivables', 'read');
        
        try {
            $invoice = $this->invoiceModel->getWithCustomer($id);
            if (!$invoice) {
                die('Invoice not found.');
            }
            
            $items = $this->invoiceModel->getItems($id);
            
            if (empty($items)) {
                die('Invoice has no items.');
            }
            
            // Get company info
            $entities = $this->entityModel->getAll();
            $company = !empty($entities) ? $entities[0] : [
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
            
            // Load PDF generator
            $pdfGenerator = $this->loadLibrary('Pdf_generator', $company);
            if (!$pdfGenerator) {
                die('PDF generator library not found.');
            }
            
            $result = $pdfGenerator->generateInvoice($invoice, $items, $customer);
            
            if (!$result['success']) {
                die('Error generating PDF: ' . $result['error']);
            }
            
            // Output PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="Invoice-' . $invoice['invoice_number'] . '.pdf"');
            header('Content-Length: ' . strlen($result['pdf_content']));
            
            echo $result['pdf_content'];
            exit;
            
        } catch (Exception $e) {
            error_log('PDF generation error: ' . $e->getMessage());
            die('Error generating PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate and download/view invoice PDF (legacy method)
     */
    public function pdf($id) {
        // Redirect to new method
        $this->pdfInvoice($id);
    }
    
    /**
     * Send invoice via email
     */
    public function sendInvoice($id) {
        $this->requirePermission('receivables', 'update');
        
        try {
            $invoice = $this->invoiceModel->getWithCustomer($id);
            if (!$invoice || empty($invoice['email'])) {
                $this->setFlashMessage('danger', 'Invoice not found or customer has no email.');
                redirect('receivables/invoices/view/' . $id);
            }
            
            // Generate PDF
            $items = $this->invoiceModel->getItems($id);
            
            if (empty($items)) {
                $this->setFlashMessage('danger', 'Invoice has no items.');
                redirect('receivables/invoices/view/' . $id);
            }
            
            // Get company info
            $entities = $this->entityModel->getAll();
            $company = !empty($entities) ? $entities[0] : [
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
            
            // Generate PDF
            $pdfGenerator = $this->loadLibrary('Pdf_generator', $company);
            if (!$pdfGenerator) {
                throw new Exception('PDF generator library not found.');
            }
            
            $pdfResult = $pdfGenerator->generateInvoice($invoice, $items, $customer);
            
            if (!$pdfResult['success']) {
                throw new Exception('PDF generation failed: ' . $pdfResult['error']);
            }
            
            // Send email
            $emailSender = $this->loadLibrary('Email_sender');
            if (!$emailSender) {
                throw new Exception('Email sender library not found.');
            }
            
            $subject = 'Invoice ' . $invoice['invoice_number'] . ' from ' . $company['name'];
            $body = $this->loadEmailTemplate($invoice, $customer, $company);
            
            $emailResult = $emailSender->sendInvoice(
                $invoice['email'],
                $subject,
                $body,
                $pdfResult['file_path'],
                'Invoice-' . $invoice['invoice_number'] . '.pdf'
            );
            
            if ($emailResult['success']) {
                // Update invoice status to 'sent'
                $this->invoiceModel->update($id, ['status' => 'sent']);
                $this->activityModel->log($this->session['user_id'], 'update', 'Receivables', 'Sent invoice: ' . $invoice['invoice_number']);
                $this->setFlashMessage('success', 'Invoice sent successfully to ' . $invoice['email']);
            } else {
                throw new Exception('Email send failed: ' . $emailResult['error']);
            }
            
        } catch (Exception $e) {
            error_log('Send invoice error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Failed to send invoice: ' . $e->getMessage());
        }
        
        redirect('receivables/invoices/view/' . $id);
    }
    
    /**
     * Load email template for invoice
     */
    private function loadEmailTemplate($invoice, $customer, $company) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #0066cc; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; }
        .invoice-details { background-color: white; padding: 15px; margin: 15px 0; border-left: 4px solid #0066cc; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Invoice from ' . htmlspecialchars($company['name']) . '</h2>
        </div>
        <div class="content">
            <p>Dear ' . htmlspecialchars($customer['company_name'] ?: 'Customer') . ',</p>
            <p>Please find attached invoice #' . htmlspecialchars($invoice['invoice_number']) . '</p>
            
            <div class="invoice-details">
                <p><strong>Invoice Date:</strong> ' . date('M d, Y', strtotime($invoice['invoice_date'])) . '</p>
                <p><strong>Due Date:</strong> ' . date('M d, Y', strtotime($invoice['due_date'])) . '</p>
                <p><strong>Total Amount:</strong> ' . format_currency($invoice['total_amount'], $invoice['currency']) . '</p>';
        
        if ($invoice['balance_amount'] > 0) {
            $html .= '<p><strong>Balance Due:</strong> ' . format_currency($invoice['balance_amount'], $invoice['currency']) . '</p>';
        }
        
        $html .= '</div>
            <p>Thank you for your business!</p>
        </div>
        <div class="footer">
            <p><strong>' . htmlspecialchars($company['name']) . '</strong></p>';
        
        if (!empty($company['email'])) {
            $html .= '<p>Email: ' . htmlspecialchars($company['email']) . '</p>';
        }
        if (!empty($company['phone'])) {
            $html .= '<p>Phone: ' . htmlspecialchars($company['phone']) . '</p>';
        }
        
        $html .= '</div>
    </div>
</body>
</html>';
        
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

