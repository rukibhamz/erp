<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payables extends Base_Controller {
    private $vendorModel;
    private $billModel;
    private $paymentModel;
    private $paymentAllocationModel;
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
        $this->paymentAllocationModel = $this->loadModel('Payment_allocation_model');
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
            
            // Validate email
            if (!empty($data['email']) && !validate_email($data['email'])) {
                $this->setFlashMessage('danger', 'Invalid email address.');
                redirect('payables/vendors/create');
            }
            
            // Validate phone
            if (!empty($data['phone']) && !validate_phone($data['phone'])) {
                $this->setFlashMessage('danger', 'Invalid phone number. Please enter a valid phone number.');
                redirect('payables/vendors/create');
            }
            
            // Sanitize phone
            if (!empty($data['phone'])) {
                $data['phone'] = sanitize_phone($data['phone']);
            }
            
            // Auto-generate vendor code if empty (leave blank to auto-generate)
            if (is_empty_or_whitespace($data['vendor_code'])) {
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
    
    public function editVendor($id) {
        $this->requirePermission('payables', 'update');
        
        $vendor = $this->vendorModel->getById($id);
        if (!$vendor) {
            $this->setFlashMessage('danger', 'Vendor not found.');
            redirect('payables/vendors');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
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
            
            if ($this->vendorModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Payables', 'Updated vendor: ' . $data['company_name']);
                $this->setFlashMessage('success', 'Vendor updated successfully.');
                redirect('payables/vendors');
            } else {
                $this->setFlashMessage('danger', 'Failed to update vendor.');
            }
        }
        
        $data = [
            'page_title' => 'Edit Vendor',
            'vendor' => $vendor,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('payables/edit_vendor', $data);
    }
    
    public function bills() {
        $status = $_GET['status'] ?? null;
        $vendorId = $_GET['vendor_id'] ?? null;

        try {
            if ($status || $vendorId) {
                $sql = "SELECT b.*, v.company_name 
                        FROM `" . $this->db->getPrefix() . "bills` b
                        JOIN `" . $this->db->getPrefix() . "vendors` v ON b.vendor_id = v.id";
                $conditions = [];
                $params = [];

                if ($status) {
                    $conditions[] = "b.status = ?";
                    $params[] = $status;
                }

                if ($vendorId) {
                    $conditions[] = "b.vendor_id = ?";
                    $params[] = $vendorId;
                }

                if (!empty($conditions)) {
                    $sql .= " WHERE " . implode(' AND ', $conditions);
                }

                $sql .= " ORDER BY b.bill_date DESC";
                $bills = $this->db->fetchAll($sql, $params);
            } else {
                $bills = $this->billModel->getAll();
                foreach ($bills as &$bill) {
                    $vendor = $this->vendorModel->getById($bill['vendor_id']);
                    $bill['company_name'] = $vendor ? $vendor['company_name'] : '-';
                }
            }

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
        
        $bill = $this->billModel->getById($id);
        if (!$bill) {
            $this->setFlashMessage('danger', 'Bill not found.');
            redirect('payables/bills');
        }
        
        try {
            $items = $this->billModel->getItems($id);
            $vendor = $this->vendorModel->getById($bill['vendor_id']);
        } catch (Exception $e) {
            $items = [];
            $vendor = null;
        }
        
        $data = [
            'page_title' => 'Edit Bill',
            'bill' => $bill,
            'items' => $items,
            'vendor' => $vendor,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('payables/edit_bill', $data);
    }
    
    public function batchPayment() {
        $this->requirePermission('payables', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $billIds = $_POST['bill_ids'] ?? [];
            $paymentDate = sanitize_input($_POST['payment_date'] ?? date('Y-m-d'));
            $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'bank_transfer');
            $cashAccountId = intval($_POST['cash_account_id'] ?? 0);
            $reference = sanitize_input($_POST['reference'] ?? '');
            
            if (empty($billIds)) {
                $this->setFlashMessage('danger', 'Please select at least one bill to pay.');
                redirect('payables/batch-payment');
            }
            
            $cashAccount = $this->cashAccountModel->getById($cashAccountId);
            if (!$cashAccount) {
                $this->setFlashMessage('danger', 'Cash account not found.');
                redirect('payables/batch-payment');
            }
            
            $totalAmount = 0;
            $allocations = [];
            
            // Calculate total and allocations
            foreach ($billIds as $billId) {
                $bill = $this->billModel->getById($billId);
                if ($bill && $bill['balance_amount'] > 0) {
                    $amount = floatval($_POST['amount_' . $billId] ?? $bill['balance_amount']);
                    if ($amount > $bill['balance_amount']) {
                        $amount = $bill['balance_amount'];
                    }
                    
                    $allocations[$billId] = $amount;
                    $totalAmount += $amount;
                }
            }
            
            if ($totalAmount <= 0) {
                $this->setFlashMessage('danger', 'No valid payment amount.');
                redirect('payables/batch-payment');
            }
            
            try {
                $this->db->beginTransaction();
                
                // Create single payment for batch
                $paymentData = [
                    'payment_number' => $this->paymentModel->getNextPaymentNumber('payment'),
                    'payment_date' => $paymentDate,
                    'payment_type' => 'payment',
                    'reference_type' => 'manual',
                    'reference_id' => null,
                    'vendor_id' => null, // Multiple vendors in batch
                    'account_id' => $cashAccount['account_id'],
                    'bank_account_id' => $cashAccountId,
                    'amount' => $totalAmount,
                    'payment_method' => $paymentMethod,
                    'reference' => $reference,
                    'notes' => 'Batch payment for ' . count($billIds) . ' bills',
                    'status' => 'posted',
                    'created_by' => $this->session['user_id']
                ];
                
                $paymentId = $this->paymentModel->create($paymentData);
                
                if (!$paymentId) {
                    throw new Exception('Failed to create payment');
                }
                
                // Create allocations for each bill
                foreach ($allocations as $billId => $amount) {
                    $bill = $this->billModel->getById($billId);
                    if ($bill) {
                        // Create allocation
                        $this->paymentAllocationModel->allocate($paymentId, $amount, null, $billId, 0);
                        
                        // Update bill
                        $this->billModel->addPayment($billId, $amount);
                        $this->billModel->updateStatus($billId);
                        
                        // Create transactions for vendor account
                        $vendor = $this->vendorModel->getById($bill['vendor_id']);
                        if ($vendor && $vendor['account_id']) {
                            // Credit Accounts Payable
                            $this->transactionModel->create([
                                'transaction_number' => $paymentData['payment_number'] . '-AP-' . $billId,
                                'transaction_date' => $paymentDate,
                                'transaction_type' => 'payment',
                                'reference_id' => $paymentId,
                                'reference_type' => 'payment',
                                'account_id' => $vendor['account_id'],
                                'description' => 'Payment for bill ' . $bill['bill_number'],
                                'debit' => $amount,
                                'credit' => 0,
                                'status' => 'posted',
                                'created_by' => $this->session['user_id']
                            ]);
                            
                            $this->accountModel->updateBalance($vendor['account_id'], $amount, 'debit');
                        }
                    }
                }
                
                // Create cash account transaction (credit)
                $this->transactionModel->create([
                    'transaction_number' => $paymentData['payment_number'] . '-CASH',
                    'transaction_date' => $paymentDate,
                    'transaction_type' => 'payment',
                    'reference_id' => $paymentId,
                    'reference_type' => 'payment',
                    'account_id' => $cashAccount['account_id'],
                    'description' => 'Batch payment for ' . count($billIds) . ' bills',
                    'debit' => 0,
                    'credit' => $totalAmount,
                    'status' => 'posted',
                    'created_by' => $this->session['user_id']
                ]);
                
                $this->accountModel->updateBalance($cashAccount['account_id'], $totalAmount, 'credit');
                $this->cashAccountModel->updateBalance($cashAccountId, $totalAmount, 'withdrawal');
                
                $this->db->commit();
                $this->activityModel->log($this->session['user_id'], 'create', 'Payables', 'Processed batch payment for ' . count($billIds) . ' bills');
                $this->setFlashMessage('success', 'Batch payment processed successfully.');
                redirect('payables/bills');
            } catch (Exception $e) {
                $this->db->rollBack();
                error_log('Payables batchPayment error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to process batch payment: ' . $e->getMessage());
                redirect('payables/batch-payment');
            }
        }
        
        // Get unpaid bills
        try {
            $bills = $this->db->fetchAll(
                "SELECT b.*, v.company_name 
                 FROM `" . $this->db->getPrefix() . "bills` b
                 JOIN `" . $this->db->getPrefix() . "vendors` v ON b.vendor_id = v.id
                 WHERE b.status IN ('received', 'partially_paid', 'overdue') 
                 AND b.balance_amount > 0
                 ORDER BY b.due_date ASC, v.company_name"
            );
            
            $cashAccounts = $this->cashAccountModel->getActive();
        } catch (Exception $e) {
            $bills = [];
            $cashAccounts = [];
        }
        
        $data = [
            'page_title' => 'Batch Bill Payment',
            'bills' => $bills,
            'cash_accounts' => $cashAccounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('payables/batch_payment', $data);
    }
    
    public function aging() {
        try {
            $agingReport = $this->vendorModel->getAgingReport();
        } catch (Exception $e) {
            $agingReport = [];
        }
        
        $data = [
            'page_title' => 'Vendor Aging Report',
            'aging_report' => $agingReport,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('payables/aging', $data);
    }
}
