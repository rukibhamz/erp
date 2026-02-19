<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pos extends Base_Controller {
    private $terminalModel;
    private $saleModel;
    private $sessionModel;
    private $itemModel;
    private $customerModel;
    private $accountModel;
    private $transactionModel;
    private $invoiceModel;
    private $stockModel;
    private $activityModel;
    private $taxTypeModel;
    private $transactionService;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('pos', 'read');
        $this->terminalModel = $this->loadModel('Pos_terminal_model');
        $this->saleModel = $this->loadModel('Pos_sale_model');
        $this->sessionModel = $this->loadModel('Pos_session_model');
        $this->itemModel = $this->loadModel('Item_model');
        $this->customerModel = $this->loadModel('Customer_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->stockModel = $this->loadModel('Stock_level_model');
        $this->activityModel = $this->loadModel('Activity_model');
        $this->taxTypeModel = $this->loadModel('Tax_type_model'); // Use Tax_type_model for erp_tax_types table
        
        // Load Transaction Service
        // Use APPPATH for application/services
        require_once APPPATH . 'services/Transaction_service.php';
        $this->transactionService = new Transaction_service();
    }
    
    public function index() {
        $terminalId = $_GET['terminal'] ?? null;
        $terminals = $this->terminalModel->getActiveTerminals();
        
        if (empty($terminals)) {
            $this->setFlashMessage('warning', 'No active POS terminals found. Please create a terminal first.');
            redirect('pos/terminals');
        }
        
        if (!$terminalId && !empty($terminals)) {
            $terminalId = $terminals[0]['id'];
        }
        
        // Get open session
        $session = $this->sessionModel->getOpenSession($terminalId, $this->session['user_id']);
        
        // Get items for quick add (only sellable items)
        try {
            $items = $this->itemModel->getSellableItems();
        } catch (Exception $e) {
            $items = [];
        }
        
        // Get default VAT rate from erp_tax_types table
        $defaultVatRate = 7.5; // Default fallback
        try {
            $vatTax = $this->taxTypeModel->getByCode('VAT');
            if ($vatTax && isset($vatTax['rate'])) {
                $defaultVatRate = floatval($vatTax['rate']);
            } else {
                // Try to get first active tax as fallback
                $activeTaxes = $this->taxTypeModel->getAllActive();
                if (!empty($activeTaxes) && isset($activeTaxes[0]['rate'])) {
                    $defaultVatRate = floatval($activeTaxes[0]['rate']);
                }
            }
        } catch (Exception $e) {
            error_log('POS VAT rate error: ' . $e->getMessage());
        }
        
        // Get walk-in customer
        try {
            $walkInCustomer = $this->customerModel->getByCode('WALK-IN');
            if (!$walkInCustomer) {
                // Create walk-in customer if doesn't exist
                $customerId = $this->customerModel->create([
                    'customer_code' => 'WALK-IN',
                    'company_name' => 'Walk-in Customer',
                    'contact_name' => 'Walk-in Customer',
                    'email' => '',
                    'phone' => '',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                if ($customerId) {
                    $walkInCustomer = $this->customerModel->getById($customerId);
                } else {
                    $walkInCustomer = null;
                }
            }
        } catch (Exception $e) {
            error_log('POS walk-in customer error: ' . $e->getMessage());
            $walkInCustomer = null;
        }
        
        $data = [
            'page_title' => 'Point of Sale',
            'terminal_id' => $terminalId,
            'terminals' => $terminals,
            'current_terminal' => $this->terminalModel->getById($terminalId),
            'session' => $session,
            'items' => $items,
            'walk_in_customer' => $walkInCustomer,
            'default_vat_rate' => $defaultVatRate,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('pos/index', $data);
    }

    /**
     * Alias for index or specific terminal view
     * Handles calls to pos/terminal
     */
    public function terminal($id = null) {
        if ($id) {
            redirect('pos/index?terminal=' . $id);
        } else {
            redirect('pos/index');
        }
    }
    
    public function processSale() {
        // Log start of request (to confirm we reach here)
        file_put_contents(__DIR__ . '/../../pos_debug.log', date('Y-m-d H:i:s') . " - START processSale request\n", FILE_APPEND);

        try {
            $this->requirePermission('pos', 'create');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                redirect('pos');
            }
            
            check_csrf(); // CSRF Protection
            
            $terminalId = intval($_POST['terminal_id'] ?? 0);
            $customerId = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
            // Use safe JSON decoding with validation
            $items = safe_json_decode($_POST['items'] ?? '[]', true, []);
            $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'cash');
            $amountPaid = floatval($_POST['amount_paid'] ?? 0);
            $discountAmount = floatval($_POST['discount_amount'] ?? 0);
            $discountType = sanitize_input($_POST['discount_type'] ?? 'fixed');
            
            if (empty($items)) {
                $this->setFlashMessage('danger', 'No items in cart.');
                redirect('pos');
            }
            
            // Calculate totals
            $subtotal = 0;
            $taxAmount = 0;
            $saleItems = [];
            
            // Get default VAT rate from erp_tax_types table
            $defaultVatRate = 7.5;
            try {
                $vatTax = $this->taxTypeModel->getByCode('VAT');
                if ($vatTax && isset($vatTax['rate'])) {
                    $defaultVatRate = floatval($vatTax['rate']);
                } else {
                    $activeTaxes = $this->taxTypeModel->getAllActive();
                    if (!empty($activeTaxes) && isset($activeTaxes[0]['rate'])) {
                        $defaultVatRate = floatval($activeTaxes[0]['rate']);
                    }
                }
            } catch (Exception $e) {
                error_log('POS VAT rate error: ' . $e->getMessage());
            }
            
            foreach ($items as $itemData) {
                $item = $this->itemModel->getById($itemData['item_id']);
                if (!$item) continue;
                
                $quantity = floatval($itemData['quantity'] ?? 1);
                $unitPrice = floatval($itemData['price'] ?? $item['selling_price'] ?? 0);
                
                // Wholesale Logic
                if (($item['is_wholesale_enabled'] ?? 0) == 1 && $quantity >= ($item['wholesale_moq'] ?? 0)) {
                    $unitPrice = floatval($item['wholesale_price'] ?? $unitPrice);
                }
                
                $itemDiscount = floatval($itemData['discount'] ?? 0);
                
                // Use tax_rate from item data, or default VAT rate, or 0
                $itemTaxRate = floatval($itemData['tax_rate'] ?? $defaultVatRate);
                
                $lineTotal = ($unitPrice * $quantity) - $itemDiscount;
                $lineTax = $lineTotal * ($itemTaxRate / 100);
                
                $subtotal += $lineTotal;
                $taxAmount += $lineTax;
                
                $saleItems[] = [
                    'item_id' => $item['id'],
                    'item_name' => $item['item_name'] ?? $item['name'] ?? '',
                    'item_code' => $item['sku'] ?? $item['item_code'] ?? '',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $itemDiscount,
                    'tax_rate' => $itemTaxRate,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineTotal + $lineTax
                ];
            }
            
            // Apply overall discount
            if ($discountType === 'percentage') {
                $discountAmount = $subtotal * ($discountAmount / 100);
            }
            
            // Apply discount to subtotal first
            $discountedSubtotal = $subtotal - $discountAmount;
            
            // Recalculate VAT on discounted amount (standard practice)
            $taxAmount = $discountedSubtotal * ($defaultVatRate / 100);
            
            $totalAmount = $discountedSubtotal + $taxAmount;
            
            $changeAmount = max(0, $amountPaid - $totalAmount);
            
            // Create sale
            $saleData = [
                'sale_number' => $this->saleModel->getNextSaleNumber(),
                'terminal_id' => $terminalId,
                'cashier_id' => $this->session['user_id'],
                'customer_id' => $customerId,
                'sale_date' => date('Y-m-d H:i:s'),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_type' => $discountType,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'amount_paid' => $amountPaid,
                'change_amount' => $changeAmount,
                'status' => 'completed'
            ];
            
            $saleId = $this->saleModel->createSale($saleData, $saleItems);
            
            if (!$saleId) {
                throw new Exception('Failed to create sale in database');
            }
            
            // Update inventory
            foreach ($saleItems as $saleItem) {
                try {
                    $this->stockModel->decreaseStock($saleItem['item_id'], $saleItem['quantity'], 'POS Sale', $saleId);
                } catch (Exception $e) {
                    error_log('POS inventory update error: ' . $e->getMessage());
                }
            }
            
            // Create accounting entries
            try {
                $this->createAccountingEntries($saleId, $totalAmount, $taxAmount, $paymentMethod, $terminalId, $saleItems);
            } catch (Exception $e) {
                error_log('POS Accounting Error: ' . $e->getMessage());
                // Don't fail the whole sale for accounting error
            }

            // Update session totals
            $session = $this->sessionModel->getOpenSession($terminalId, $this->session['user_id']);
            if ($session) {
                $this->sessionModel->updateSessionTotals($session['id']);
            }
            
            $this->activityModel->log($this->session['user_id'], 'create', 'POS', 'Completed POS sale: ' . $saleData['sale_number']);
            
            // Return success with sale ID for receipt printing
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'sale_id' => $saleId,
                    'sale_number' => $saleData['sale_number'],
                    'change' => $changeAmount
                ]);
                exit;
            }
            
            $this->setFlashMessage('success', 'Sale completed successfully!');
            redirect('pos/receipt/' . $saleId);

        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            $trace = $e->getTraceAsString();
            
            file_put_contents(__DIR__ . '/../../pos_debug.log', date('Y-m-d H:i:s') . " - EXCEPTION: " . $errorMessage . "\n" . $trace . "\n", FILE_APPEND);
            error_log('POS processSale fatal error: ' . $errorMessage);
            
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $errorMessage
                ]);
                exit;
            }
            
            $this->setFlashMessage('danger', 'Error processing sale: ' . $errorMessage);
            redirect('pos');
        }
    }
    
    private function createAccountingEntries($saleId, $totalAmount, $taxAmount, $paymentMethod, $terminalId, $saleItems = []) {
        try {
            // Get terminal cash account
            $terminal = $this->terminalModel->getById($terminalId);
            $cashAccountId = $terminal['cash_account_id'] ?? null;
            
            if (!$cashAccountId) {
                // Get default cash account
                $cashAccount = $this->accountModel->getByCode('1001'); // Default Cash Account
                $cashAccountId = $cashAccount['id'] ?? null;
            }
            
            // Get sales revenue account
            $salesAccount = $this->accountModel->getByType('Revenue');
            $salesAccountId = $salesAccount[0]['id'] ?? null;
            
            // Get tax account
            $taxAccount = $this->accountModel->getByType('Liabilities');
            $taxAccountId = $taxAccount[0]['id'] ?? null;
            
            // Log account IDs for debugging
            file_put_contents(__DIR__ . '/../../pos_debug.log', date('Y-m-d H:i:s') . " - Accounting Debug: CashID={$cashAccountId}, SalesID={$salesAccountId}, TaxID={$taxAccountId}\n", FILE_APPEND);

            if ($cashAccountId && $salesAccountId) {
                // Prepare journal entry data
                $journalData = [
                    'date' => date('Y-m-d'),
                    'reference_type' => 'pos_sale',
                    'reference_id' => $saleId,
                    'description' => 'POS Sale #' . $saleId,
                    'journal_type' => 'sales',
                    'entries' => [
                        // Debit Cash
                        [
                            'account_id' => $cashAccountId,
                            'debit' => $totalAmount,
                            'credit' => 0,
                            'description' => 'POS Sale Collection'
                        ],
                        // Credit Sales Revenue
                        [
                            'account_id' => $salesAccountId,
                            'debit' => 0,
                            'credit' => $totalAmount - $taxAmount,
                            'description' => 'Sales Revenue'
                        ]
                    ],
                    'created_by' => $this->session['user_id'],
                    'auto_post' => true
                ];
                
                // Add tax entry if applicable
                if ($taxAmount > 0 && $taxAccountId) {
                    $journalData['entries'][] = [
                        'account_id' => $taxAccountId,
                        'debit' => 0,
                        'credit' => $taxAmount,
                        'description' => 'VAT Liability'
                    ];
                }
                
                // Post journal entry using Transaction Service
                $this->transactionService->postJournalEntry($journalData);
                
                // Update cash account balance
                $this->loadModel('Cash_account_model')->updateBalance($cashAccountId, $totalAmount, 'deposit');
                
                file_put_contents(__DIR__ . '/../../pos_debug.log', date('Y-m-d H:i:s') . " - Accounting: Journal posted successfully.\n", FILE_APPEND);
            } else {
                file_put_contents(__DIR__ . '/../../pos_debug.log', date('Y-m-d H:i:s') . " - Accounting Skipped: Missing Cash or Sales Account ID.\n", FILE_APPEND);
            }
            
            // Get walk-in customer if needed
            $walkInCustomer = null;
            if (empty($customerId)) {
                try {
                    $walkInCustomer = $this->customerModel->getByCode('WALK-IN');
                    // If walk-in customer doesn't exist, create it
                    if (!$walkInCustomer) {
                        $customerId = $this->customerModel->create([
                            'customer_code' => 'WALK-IN',
                            'company_name' => 'Walk-in Customer',
                            'contact_name' => 'Walk-in Customer',
                            'email' => '',
                            'phone' => '',
                            'status' => 'active',
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                        if ($customerId) {
                            $walkInCustomer = $this->customerModel->getById($customerId);
                        }
                    }
                } catch (Exception $e) {
                    error_log('POS walk-in customer fetch error: ' . $e->getMessage());
                }
            }
            
            // Create invoice in accounting
            $invoiceData = [
                'invoice_number' => $this->invoiceModel->getNextInvoiceNumber(),
                'customer_id' => $customerId ?? ($walkInCustomer['id'] ?? null),
                'invoice_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d'),
                'subtotal' => $totalAmount - $taxAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => 'paid',
                'payment_date' => date('Y-m-d'),
                'reference' => 'POS-' . $saleId
            ];
            
            $invoiceId = $this->invoiceModel->create($invoiceData);
            
            // Link invoice to sale
            if ($invoiceId) {
                // Base_Model::update($id, $data)
                $this->saleModel->update($saleId, ['invoice_id' => $invoiceId]);
                
                // Add invoice items
                foreach ($saleItems as $item) {
                    $this->invoiceModel->addItem($invoiceId, [
                        'product_id' => $item['item_id'],
                        'item_description' => $item['item_name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'tax_rate' => $item['tax_rate'],
                        'tax_amount' => $item['tax_amount'],
                        'discount_amount' => $item['discount_amount'],
                        'line_total' => $item['line_total']
                    ]);
                }
            }
            
        } catch (Exception $e) {
            error_log('POS accounting entries error: ' . $e->getMessage());
        }
    }
    
    public function receipt($saleId) {
        $sale = $this->saleModel->getSaleWithItems($saleId);
        if (!$sale) {
            $this->setFlashMessage('danger', 'Sale not found.');
            redirect('pos');
        }
        
        $data = [
            'page_title' => 'Receipt - ' . $sale['sale_number'],
            'sale' => $sale,
            'terminal' => $this->terminalModel->getById($sale['terminal_id']),
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('pos/receipt', $data);
    }
    
    public function terminals() {
        // Allow admin and super_admin to manage terminals (they have all permissions)
        // For other roles, require 'pos.manage' permission
        $userRole = $this->session['role'] ?? '';
        if (!in_array($userRole, ['super_admin', 'admin'])) {
            $this->requirePermission('pos', 'manage');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['terminal_code'])) {
            check_csrf(); // CSRF Protection
            $terminalCodeInput = sanitize_input($_POST['terminal_code'] ?? '');
            // Auto-generate terminal code if empty (leave blank to auto-generate)
            $terminalCode = is_empty_or_whitespace($terminalCodeInput) ? $this->terminalModel->getNextTerminalCode() : $terminalCodeInput;
            
            $data = [
                'terminal_code' => $terminalCode,
                'name' => sanitize_input($_POST['name'] ?? ''),
                'location' => sanitize_input($_POST['location'] ?? ''),
                'status' => 'active'
            ];
            
            if ($this->terminalModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'POS', 'Created POS terminal: ' . $data['name']);
                $this->setFlashMessage('success', 'Terminal created successfully.');
                redirect('pos/terminals');
            } else {
                $this->setFlashMessage('danger', 'Failed to create terminal.');
            }
        }
        
        try {
            $terminals = $this->terminalModel->getAll();
        } catch (Exception $e) {
            $terminals = [];
        }
        
        $data = [
            'page_title' => 'POS Terminals',
            'terminals' => $terminals,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('pos/terminals', $data);
    }
    
    public function createTerminal() {
        $this->terminals(); // Reuse terminals method
    }
    
    public function reports() {
        $this->requirePermission('pos', 'read');
        
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $terminalId = !empty($_GET['terminal_id']) ? intval($_GET['terminal_id']) : null;
        
        try {
            $sales = $this->saleModel->getByDateRange($startDate, $endDate, $terminalId);
            $summary = $this->saleModel->getSalesSummary($terminalId, $startDate, $endDate);
            $terminals = $this->terminalModel->getActiveTerminals();
        } catch (Exception $e) {
            error_log('POS reports error: ' . $e->getMessage());
            $sales = [];
            $summary = ['total_sales' => 0, 'total_revenue' => 0];
            $terminals = [];
        }
        
        $data = [
            'page_title' => 'POS Reports',
            'sales' => $sales,
            'summary' => $summary,
            'terminals' => $terminals,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'terminal_id' => $terminalId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('pos/reports', $data);
    }
}

