<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Estimates extends Base_Controller {
    private $estimateModel;
    private $customerModel;
    private $productModel;
    private $accountModel;
    private $invoiceModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('estimates', 'read');
        $this->estimateModel = $this->loadModel('Estimate_model');
        $this->customerModel = $this->loadModel('Customer_model');
        $this->productModel = $this->loadModel('Product_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        $status = $_GET['status'] ?? null;

        try {
            if ($status) {
                $estimates = $this->estimateModel->getByStatus($status);
            } else {
                $estimates = $this->estimateModel->getAll();
            }

            // Add customer info
            foreach ($estimates as &$estimate) {
                $customer = $this->customerModel->getById($estimate['customer_id']);
                $estimate['customer_name'] = $customer ? $customer['company_name'] : '-';
            }
        } catch (Exception $e) {
            $estimates = [];
        }

        $data = [
            'page_title' => 'Estimates / Quotes',
            'estimates' => $estimates,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('estimates/index', $data);
    }

    public function create() {
        $this->requirePermission('estimates', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $customerId = intval($_POST['customer_id'] ?? 0);
            $estimateDate = sanitize_input($_POST['estimate_date'] ?? date('Y-m-d'));
            $expiryDate = sanitize_input($_POST['expiry_date'] ?? '');
            $items = $_POST['items'] ?? [];

            if (!$customerId) {
                $this->setFlashMessage('danger', 'Please select a customer.');
                redirect('estimates/create');
            }

            if (!$expiryDate) {
                $expiryDate = date('Y-m-d', strtotime('+30 days'));
            }

            // Calculate totals
            $subtotal = 0;
            $taxAmount = 0;
            $totalAmount = 0;

            foreach ($items as $item) {
                $qty = floatval($item['quantity'] ?? 1);
                $price = floatval($item['unit_price'] ?? 0);
                $taxRate = floatval($item['tax_rate'] ?? 0);
                $discount = floatval($item['discount_amount'] ?? 0);
                
                $lineTotal = ($qty * $price) - $discount;
                $lineTax = $lineTotal * ($taxRate / 100);
                
                $subtotal += $lineTotal;
                $taxAmount += $lineTax;
            }

            $totalAmount = $subtotal + $taxAmount;

            $estimateData = [
                'estimate_number' => $this->estimateModel->getNextEstimateNumber(),
                'customer_id' => $customerId,
                'estimate_date' => $estimateDate,
                'expiry_date' => $expiryDate,
                'reference' => sanitize_input($_POST['reference'] ?? ''),
                'subtotal' => $subtotal,
                'tax_rate' => $subtotal > 0 ? ($taxAmount / $subtotal) * 100 : 0,
                'tax_amount' => $taxAmount,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'currency' => sanitize_input($_POST['currency'] ?? 'USD'),
                'terms' => sanitize_input($_POST['terms'] ?? ''),
                'notes' => sanitize_input($_POST['notes'] ?? ''),
                'status' => 'draft',
                'created_by' => $this->session['user_id']
            ];

            $estimateId = $this->estimateModel->create($estimateData);

            if ($estimateId) {
                // Create estimate items
                foreach ($items as $item) {
                    $qty = floatval($item['quantity'] ?? 1);
                    $price = floatval($item['unit_price'] ?? 0);
                    $taxRate = floatval($item['tax_rate'] ?? 0);
                    $discount = floatval($item['discount_amount'] ?? 0);
                    
                    $lineTotal = ($qty * $price) - $discount;
                    $lineTax = $lineTotal * ($taxRate / 100);

                    $itemData = [
                        'product_id' => !empty($item['product_id']) ? intval($item['product_id']) : null,
                        'item_description' => sanitize_input($item['description'] ?? ''),
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $lineTax,
                        'discount_rate' => 0,
                        'discount_amount' => $discount,
                        'line_total' => $lineTotal,
                        'account_id' => !empty($item['account_id']) ? intval($item['account_id']) : null
                    ];
                    
                    $this->estimateModel->addItem($estimateId, $itemData);
                }

                $this->activityModel->log($this->session['user_id'], 'create', 'Estimates', 'Created estimate: ' . $estimateData['estimate_number']);
                $this->setFlashMessage('success', 'Estimate created successfully.');
                redirect('estimates');
            } else {
                $this->setFlashMessage('danger', 'Failed to create estimate.');
            }
        }

        try {
            $customers = $this->customerModel->getAll();
            $products = $this->productModel->getActive();
            $accounts = $this->accountModel->getByType('Revenue');
        } catch (Exception $e) {
            $customers = [];
            $products = [];
            $accounts = [];
        }

        $data = [
            'page_title' => 'Create Estimate / Quote',
            'customers' => $customers,
            'products' => $products,
            'accounts' => $accounts,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('estimates/create', $data);
    }

    public function view($id) {
        try {
            $estimate = $this->estimateModel->getById($id);
            if (!$estimate) {
                $this->setFlashMessage('danger', 'Estimate not found.');
                redirect('estimates');
            }

            $items = $this->estimateModel->getItems($id);
            $customer = $this->customerModel->getById($estimate['customer_id']);
        } catch (Exception $e) {
            $estimate = null;
            $items = [];
            $customer = null;
        }

        $data = [
            'page_title' => 'Estimate: ' . ($estimate['estimate_number'] ?? ''),
            'estimate' => $estimate,
            'items' => $items,
            'customer' => $customer,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('estimates/view', $data);
    }

    public function convert($id) {
        $this->requirePermission('estimates', 'create');

        try {
            $estimate = $this->estimateModel->getById($id);
            if (!$estimate) {
                $this->setFlashMessage('danger', 'Estimate not found.');
                redirect('estimates');
            }

            if ($estimate['status'] === 'converted') {
                $this->setFlashMessage('danger', 'Estimate already converted.');
                redirect('estimates');
            }

            // Convert to invoice
            $invoiceData = [
                'invoice_number' => $this->invoiceModel->getNextInvoiceNumber(),
                'customer_id' => $estimate['customer_id'],
                'invoice_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'reference' => $estimate['reference'],
                'subtotal' => $estimate['subtotal'],
                'tax_rate' => $estimate['tax_rate'],
                'tax_amount' => $estimate['tax_amount'],
                'discount_amount' => $estimate['discount_amount'],
                'total_amount' => $estimate['total_amount'],
                'balance_amount' => $estimate['total_amount'],
                'currency' => $estimate['currency'],
                'terms' => $estimate['terms'],
                'notes' => $estimate['notes'],
                'status' => 'draft',
                'created_by' => $this->session['user_id']
            ];

            $invoiceId = $this->estimateModel->convertToInvoice($id, $invoiceData);

            if ($invoiceId) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Estimates', 'Converted estimate to invoice: ' . $estimate['estimate_number']);
                $this->setFlashMessage('success', 'Estimate converted to invoice successfully.');
                redirect('receivables/invoices/edit/' . $invoiceId);
            } else {
                $this->setFlashMessage('danger', 'Failed to convert estimate.');
                redirect('estimates');
            }
        } catch (Exception $e) {
            error_log('Estimates convert error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error converting estimate: ' . $e->getMessage());
            redirect('estimates');
        }
    }

    public function delete($id) {
        $this->requirePermission('estimates', 'delete');

        $estimate = $this->estimateModel->getById($id);
        if (!$estimate) {
            $this->setFlashMessage('danger', 'Estimate not found.');
            redirect('estimates');
        }

        if ($estimate['status'] === 'converted') {
            $this->setFlashMessage('danger', 'Cannot delete converted estimate.');
            redirect('estimates');
        }

        if ($this->estimateModel->delete($id)) {
            $this->activityModel->log($this->session['user_id'], 'delete', 'Estimates', 'Deleted estimate: ' . $estimate['estimate_number']);
            $this->setFlashMessage('success', 'Estimate deleted successfully.');
        } else {
            $this->setFlashMessage('danger', 'Failed to delete estimate.');
        }

        redirect('estimates');
    }
}

