<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Credit_notes extends Base_Controller {
    private $creditNoteModel;
    private $customerModel;
    private $invoiceModel;
    private $productModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('receivables', 'read');
        $this->creditNoteModel = $this->loadModel('Credit_note_model');
        $this->customerModel = $this->loadModel('Customer_model');
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->productModel = $this->loadModel('Product_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        $status = $_GET['status'] ?? null;

        try {
            if ($status) {
                $creditNotes = $this->db->fetchAll(
                    "SELECT * FROM `" . $this->db->getPrefix() . "credit_notes` 
                     WHERE status = ? ORDER BY credit_date DESC",
                    [$status]
                );
            } else {
                $creditNotes = $this->creditNoteModel->getAll();
            }

            // Add customer info
            foreach ($creditNotes as &$note) {
                $customer = $this->customerModel->getById($note['customer_id']);
                $note['customer_name'] = $customer ? $customer['company_name'] : '-';
            }
        } catch (Exception $e) {
            $creditNotes = [];
        }

        $data = [
            'page_title' => 'Credit Notes',
            'credit_notes' => $creditNotes,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('credit_notes/index', $data);
    }

    public function create() {
        $this->requirePermission('receivables', 'create');

        $invoiceId = $_GET['invoice_id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            $customerId = intval($_POST['customer_id'] ?? 0);
            $invoiceId = !empty($_POST['invoice_id']) ? intval($_POST['invoice_id']) : null;
            $creditDate = sanitize_input($_POST['credit_date'] ?? date('Y-m-d'));
            $items = $_POST['items'] ?? [];

            if (!$customerId) {
                $this->setFlashMessage('danger', 'Please select a customer.');
                redirect('credit-notes/create');
            }

            // Calculate totals
            $subtotal = 0;
            $taxAmount = floatval($_POST['tax_amount'] ?? 0);
            $totalAmount = 0;

            foreach ($items as $item) {
                $qty = floatval($item['quantity'] ?? 1);
                $price = floatval($item['unit_price'] ?? 0);
                $lineTotal = $qty * $price;
                $subtotal += $lineTotal;
            }

            $totalAmount = $subtotal + $taxAmount;

            $creditNoteData = [
                'credit_note_number' => $this->creditNoteModel->getNextCreditNoteNumber(),
                'invoice_id' => $invoiceId,
                'customer_id' => $customerId,
                'credit_date' => $creditDate,
                'reference' => sanitize_input($_POST['reference'] ?? ''),
                'reason' => sanitize_input($_POST['reason'] ?? ''),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'currency' => sanitize_input($_POST['currency'] ?? 'USD'),
                'status' => 'draft',
                'created_by' => $this->session['user_id']
            ];

            $creditNoteId = $this->creditNoteModel->create($creditNoteData);

            if ($creditNoteId) {
                // Create credit note items using Credit_note_model's addItem method
                foreach ($items as $item) {
                    $qty = floatval($item['quantity'] ?? 1);
                    $price = floatval($item['unit_price'] ?? 0);
                    $lineTotal = $qty * $price;

                    $itemData = [
                        'product_id' => !empty($item['product_id']) ? intval($item['product_id']) : null,
                        'item_description' => sanitize_input($item['description'] ?? ''),
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'line_total' => $lineTotal
                    ];
                    
                    $this->creditNoteModel->addItem($creditNoteId, $itemData);
                }

                $this->activityModel->log($this->session['user_id'], 'create', 'Credit Notes', 'Created credit note: ' . $creditNoteData['credit_note_number']);
                $this->setFlashMessage('success', 'Credit note created successfully.');
                redirect('credit-notes');
            } else {
                $this->setFlashMessage('danger', 'Failed to create credit note.');
            }
        }

        try {
            $customers = $this->customerModel->getAll();
            $products = $this->productModel->getActive();
            $invoice = $invoiceId ? $this->invoiceModel->getById($invoiceId) : null;
        } catch (Exception $e) {
            $customers = [];
            $products = [];
            $invoice = null;
        }

        $data = [
            'page_title' => 'Create Credit Note',
            'customers' => $customers,
            'products' => $products,
            'invoice' => $invoice,
            'invoice_id' => $invoiceId,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('credit_notes/create', $data);
    }

    public function view($id) {
        try {
            $creditNote = $this->creditNoteModel->getById($id);
            if (!$creditNote) {
                $this->setFlashMessage('danger', 'Credit note not found.');
                redirect('credit-notes');
            }

            $items = $this->creditNoteModel->getItems($id);
            $customer = $this->customerModel->getById($creditNote['customer_id']);
            $invoice = $creditNote['invoice_id'] ? $this->invoiceModel->getById($creditNote['invoice_id']) : null;
        } catch (Exception $e) {
            $creditNote = null;
            $items = [];
            $customer = null;
            $invoice = null;
        }

        $data = [
            'page_title' => 'Credit Note: ' . ($creditNote['credit_note_number'] ?? ''),
            'credit_note' => $creditNote,
            'items' => $items,
            'customer' => $customer,
            'invoice' => $invoice,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('credit_notes/view', $data);
    }

    public function apply($id) {
        $this->requirePermission('receivables', 'update');

        // CSRF Protection for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
        }

        $invoiceId = $_POST['invoice_id'] ?? $_GET['invoice_id'] ?? null;

        if (!$invoiceId) {
            $this->setFlashMessage('danger', 'Please select an invoice to apply the credit note to.');
            redirect('credit-notes');
        }

        try {
            if ($this->creditNoteModel->applyToInvoice($id, $invoiceId)) {
                $creditNote = $this->creditNoteModel->getById($id);
                $this->activityModel->log($this->session['user_id'], 'update', 'Credit Notes', 'Applied credit note: ' . $creditNote['credit_note_number']);
                $this->setFlashMessage('success', 'Credit note applied successfully.');
                redirect('receivables/invoices/edit/' . $invoiceId);
            } else {
                $this->setFlashMessage('danger', 'Failed to apply credit note.');
                redirect('credit-notes');
            }
        } catch (Exception $e) {
            error_log('Credit_notes apply error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error applying credit note: ' . $e->getMessage());
            redirect('credit-notes');
        }
    }
}

