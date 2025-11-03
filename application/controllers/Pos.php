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
    private $taxModel;
    
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
        $this->taxModel = $this->loadModel('Tax_model');
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
        
        // Get items for quick add
        try {
            $items = $this->itemModel->getAllActive(100);
        } catch (Exception $e) {
            $items = [];
        }
        
        // Get walk-in customer
        try {
            $walkInCustomer = $this->customerModel->getByCode('WALK-IN');
            if (!$walkInCustomer) {
                // Create walk-in customer if doesn't exist
                $walkInCustomer = $this->customerModel->create([
                    'customer_code' => 'WALK-IN',
                    'name' => 'Walk-in Customer',
                    'type' => 'retail',
                    'status' => 'active'
                ]);
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
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('pos/index', $data);
    }
    
    public function processSale() {
        $this->requirePermission('pos', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('pos');
        }
        
        try {
            $terminalId = intval($_POST['terminal_id'] ?? 0);
            $customerId = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
            $items = json_decode($_POST['items'] ?? '[]', true);
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
            
            foreach ($items as $itemData) {
                $item = $this->itemModel->getById($itemData['item_id']);
                if (!$item) continue;
                
                $quantity = floatval($itemData['quantity'] ?? 1);
                $unitPrice = floatval($itemData['price'] ?? $item['selling_price'] ?? 0);
                $itemDiscount = floatval($itemData['discount'] ?? 0);
                $itemTaxRate = floatval($itemData['tax_rate'] ?? 0);
                
                $lineTotal = ($unitPrice * $quantity) - $itemDiscount;
                $lineTax = $lineTotal * ($itemTaxRate / 100);
                
                $subtotal += $lineTotal;
                $taxAmount += $lineTax;
                
                $saleItems[] = [
                    'item_id' => $item['id'],
                    'item_name' => $item['name'],
                    'item_code' => $item['item_code'],
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
            $subtotal -= $discountAmount;
            $totalAmount = $subtotal + $taxAmount;
            
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
                throw new Exception('Failed to create sale');
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
            $this->createAccountingEntries($saleId, $totalAmount, $taxAmount, $paymentMethod, $terminalId);
            
            // Update session totals
            $session = $this->sessionModel->getOpenSession($terminalId, $this->session['user_id']);
            if ($session) {
                $this->sessionModel->updateSessionTotals($session['id']);
            }
            
            $this->activityModel->log($this->session['user_id'], 'create', 'POS', 'Completed POS sale: ' . $saleData['sale_number']);
            
            // Return success with sale ID for receipt printing
            if (isset($_POST['ajax'])) {
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
            
        } catch (Exception $e) {
            error_log('POS processSale error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error processing sale: ' . $e->getMessage());
            redirect('pos');
        }
    }
    
    private function createAccountingEntries($saleId, $totalAmount, $taxAmount, $paymentMethod, $terminalId) {
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
            
            if ($cashAccountId && $salesAccountId) {
                // Debit Cash, Credit Sales Revenue
                $this->transactionModel->create([
                    'account_id' => $cashAccountId,
                    'type' => 'debit',
                    'amount' => $totalAmount,
                    'description' => 'POS Sale #' . $saleId,
                    'reference' => 'POS-' . $saleId,
                    'date' => date('Y-m-d')
                ]);
                
                $this->transactionModel->create([
                    'account_id' => $salesAccountId,
                    'type' => 'credit',
                    'amount' => $totalAmount - $taxAmount,
                    'description' => 'POS Sale #' . $saleId,
                    'reference' => 'POS-' . $saleId,
                    'date' => date('Y-m-d')
                ]);
                
                // If tax, create tax liability entry
                if ($taxAmount > 0 && $taxAccountId) {
                    $this->transactionModel->create([
                        'account_id' => $taxAccountId,
                        'type' => 'credit',
                        'amount' => $taxAmount,
                        'description' => 'VAT on POS Sale #' . $saleId,
                        'reference' => 'POS-' . $saleId,
                        'date' => date('Y-m-d')
                    ]);
                }
            }
            
            // Get walk-in customer if needed
            $walkInCustomer = null;
            if (empty($customerId)) {
                try {
                    $walkInCustomer = $this->customerModel->getByCode('WALK-IN');
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
                $this->saleModel->update(['invoice_id' => $invoiceId], "id = ?", [$saleId]);
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
        $this->requirePermission('pos', 'manage');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['terminal_code'])) {
            $terminalCode = !empty($_POST['terminal_code']) ? sanitize_input($_POST['terminal_code']) : $this->terminalModel->getNextTerminalCode();
            
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

