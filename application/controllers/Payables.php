<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payables extends Base_Controller {
    private $vendorModel;
    private $billModel;
    private $paymentModel;
    private $accountModel;
    private $transactionModel;
    private $cashAccountModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('payables', 'read');
        $this->vendorModel = $this->loadModel('Vendor_model');
        $this->billModel = $this->loadModel('Bill_model');
        $this->paymentModel = $this->loadModel('Payment_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->cashAccountModel = $this->loadModel('Cash_account_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function vendors() {
        try {
            $vendors = $this->vendorModel->getAll();
            
            // Add outstanding balance to each vendor
            foreach ($vendors as &$vendor) {
                $vendor['outstanding'] = $this->vendorModel->getTotalOutstanding($vendor['id']);
            }
        } catch (Exception $e) {
            $vendors = [];
        }
        
        $data = [
            'page_title' => 'Vendors',
            'vendors' => $vendors,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('payables/vendors', $data);
    }
    
    public function createVendor() {
        $this->requirePermission('payables', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'vendor_code' => sanitize_input($_POST['vendor_code'] ?? ''),
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
            
            if (empty($data['vendor_code'])) {
                $data['vendor_code'] = $this->vendorModel->getNextVendorCode();
            }
            
            if ($this->vendorModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Payables', 'Created vendor: ' . $data['company_name']);
                $this->setFlashMessage('success', 'Vendor created successfully.');
                redirect('payables/vendors');
            } else {
                $this->setFlashMessage('danger', 'Failed to create vendor.');
            }
        }
        
        $data = [
            'page_title' => 'Create Vendor',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('payables/create_vendor', $data);
    }
    
    public function bills() {
        $status = $_GET['status'] ?? null;
        $vendorId = $_GET['vendor_id'] ?? null;
        
        $sql = "SELECT b.*, v.company_name 
                FROM `" . $this->db->getPrefix() . "bills` b
                JOIN `" . $this->db->getPrefix() . "vendors` v ON b.vendor_id = v.id";
        
        $params = [];
        $where = [];
        
        if ($status) {
            $where[] = "b.status = ?";
            $params[] = $status;
        }
        
        if ($vendorId) {
            $where[] = "b.vendor_id = ?";
            $params[] = $vendorId;
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY b.bill_date DESC";
        
        try {
            $bills = $this->db->fetchAll($sql, $params);
            $vendors = $this->vendorModel->getAll();
        } catch (Exception $e) {
            $bills = [];
            $vendors = [];
        }
        
        $data = [
            'page_title' => 'Bills',
            'bills' => $bills,
            'vendors' => $vendors,
            'selected_status' => $status,
            'selected_vendor' => $vendorId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('payables/bills', $data);
    }
    
    public function createBill() {
        $this->requirePermission('payables', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vendorId = intval($_POST['vendor_id'] ?? 0);
            $billDate = sanitize_input($_POST['bill_date'] ?? date('Y-m-d'));
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
            
            // Create bill
            $billData = [
                'bill_number' => $this->billModel->getNextBillNumber(),
                'vendor_id' => $vendorId,
                'bill_date' => $billDate,
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
            
            $billId = $this->billModel->create($billData);
            
            if ($billId) {
                // Create bill items
                $itemModel = $this->loadModel('Base_Model');
                $itemModel->table = 'bill_items';
                
                foreach ($items as $item) {
                    $quantity = floatval($item['quantity'] ?? 0);
                    $unitPrice = floatval($item['unit_price'] ?? 0);
                    $itemTaxRate = floatval($item['tax_rate'] ?? $taxRate);
                    $lineTotal = $quantity * $unitPrice;
                    
                    $itemModel->create([
                        'bill_id' => $billId,
                        'item_description' => sanitize_input($item['description'] ?? ''),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'tax_rate' => $itemTaxRate,
                        'line_total' => $lineTotal,
                        'account_id' => !empty($item['account_id']) ? intval($item['account_id']) : null
                    ]);
                }
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Payables', 'Created bill: ' . $billData['bill_number']);
                $this->setFlashMessage('success', 'Bill created successfully.');
                redirect('payables/bills/edit/' . $billId);
            } else {
                $this->setFlashMessage('danger', 'Failed to create bill.');
            }
        }
        
        try {
            $vendors = $this->vendorModel->getAll();
            $expenseAccounts = $this->accountModel->getByType('Expenses');
        } catch (Exception $e) {
            $vendors = [];
            $expenseAccounts = [];
        }
        
        $data = [
            'page_title' => 'Create Bill',
            'vendors' => $vendors,
            'expense_accounts' => $expenseAccounts,
            'currencies' => get_all_currencies(),
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('payables/create_bill', $data);
    }
    
    public function editBill($id) {
        $this->requirePermission('payables', 'update');
        
        $bill = $this->billModel->getWithVendor($id);
        if (!$bill) {
            $this->setFlashMessage('danger', 'Bill not found.');
            redirect('payables/bills');
        }
        
        $items = $this->billModel->getItems($id);
        
        $data = [
            'page_title' => 'Edit Bill',
            'bill' => $bill,
            'items' => $items,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('payables/edit_bill', $data);
    }
    
    public function aging() {
        $this->requirePermission('payables', 'read');
        
        $vendorId = $_GET['vendor_id'] ?? null;
        
        try {
            $agingReport = $this->vendorModel->getAgingReport($vendorId);
            $vendors = $this->vendorModel->getAll();
        } catch (Exception $e) {
            $agingReport = [];
            $vendors = [];
        }
        
        $data = [
            'page_title' => 'Accounts Payable Aging',
            'aging_report' => $agingReport,
            'vendors' => $vendors,
            'selected_vendor' => $vendorId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('payables/aging', $data);
    }
}

