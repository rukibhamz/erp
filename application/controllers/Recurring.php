<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Recurring extends Base_Controller {
    private $recurringModel;
    private $invoiceModel;
    private $billModel;
    private $journalModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('accounting', 'read');
        $this->recurringModel = $this->loadModel('Recurring_transaction_model');
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->billModel = $this->loadModel('Bill_model');
        $this->journalModel = $this->loadModel('Journal_entry_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        try {
            $recurringTransactions = $this->recurringModel->getAll();
        } catch (Exception $e) {
            $recurringTransactions = [];
        }

        $data = [
            'page_title' => 'Recurring Transactions',
            'recurring_transactions' => $recurringTransactions,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('recurring/index', $data);
    }

    public function process() {
        $this->requirePermission('accounting', 'create');

        try {
            $dueTransactions = $this->recurringModel->getDueTransactions();

            if (empty($dueTransactions)) {
                $this->setFlashMessage('info', 'No recurring transactions are due for processing.');
                redirect('recurring');
            }

            $processed = 0;
            $errors = [];

            foreach ($dueTransactions as $recurring) {
                try {
                    if ($this->processRecurringTransaction($recurring)) {
                        $processed++;
                        // Update last processed date
                        $this->recurringModel->update($recurring['id'], [
                            'last_processed_date' => date('Y-m-d'),
                            'next_occurrence_date' => $this->calculateNextOccurrence($recurring)
                        ]);
                    } else {
                        $errors[] = 'Failed to process: ' . $recurring['transaction_type'] . ' #' . $recurring['id'];
                    }
                } catch (Exception $e) {
                    error_log('Recurring process error: ' . $e->getMessage());
                    $errors[] = 'Error processing ' . $recurring['transaction_type'] . ' #' . $recurring['id'] . ': ' . $e->getMessage();
                }
            }

            if ($processed > 0) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Recurring', "Processed {$processed} recurring transactions");
                $this->setFlashMessage('success', "Processed {$processed} recurring transaction(s) successfully.");
            }

            if (!empty($errors)) {
                $this->setFlashMessage('warning', implode('<br>', $errors));
            }
        } catch (Exception $e) {
            error_log('Recurring process error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error processing recurring transactions: ' . $e->getMessage());
        }

        redirect('recurring');
    }

    private function processRecurringTransaction($recurring) {
        $transactionType = $recurring['transaction_type'];
        $templateData = json_decode($recurring['template_data'], true);

        switch ($transactionType) {
            case 'invoice':
                return $this->processRecurringInvoice($recurring, $templateData);
            case 'bill':
                return $this->processRecurringBill($recurring, $templateData);
            case 'journal_entry':
                return $this->processRecurringJournal($recurring, $templateData);
            default:
                return false;
        }
    }

    private function processRecurringInvoice($recurring, $templateData) {
        try {
            $this->db->beginTransaction();

            // Generate invoice date (use today or scheduled date)
            $invoiceDate = $recurring['next_occurrence_date'] ?? date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime($invoiceDate . ' +' . ($templateData['payment_terms_days'] ?? 30) . ' days'));

            $invoiceData = [
                'invoice_number' => $this->invoiceModel->getNextInvoiceNumber(),
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'customer_id' => $templateData['customer_id'],
                'subtotal' => floatval($templateData['subtotal'] ?? 0),
                'tax_amount' => floatval($templateData['tax_amount'] ?? 0),
                'discount_amount' => floatval($templateData['discount_amount'] ?? 0),
                'total_amount' => floatval($templateData['total_amount'] ?? 0),
                'status' => 'draft',
                'notes' => $templateData['notes'] ?? 'Recurring invoice',
                'created_by' => $this->session['user_id']
            ];

            $invoiceId = $this->invoiceModel->create($invoiceData);
            if (!$invoiceId) {
                throw new Exception('Failed to create invoice');
            }

            // Add invoice items
            if (!empty($templateData['items'])) {
                foreach ($templateData['items'] as $item) {
                    $this->invoiceModel->addItem($invoiceId, $item);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Recurring invoice error: ' . $e->getMessage());
            return false;
        }
    }

    private function processRecurringBill($recurring, $templateData) {
        try {
            $this->db->beginTransaction();

            $billDate = $recurring['next_occurrence_date'] ?? date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime($billDate . ' +' . ($templateData['payment_terms_days'] ?? 30) . ' days'));

            $billData = [
                'bill_number' => $this->billModel->getNextBillNumber(),
                'bill_date' => $billDate,
                'due_date' => $dueDate,
                'vendor_id' => $templateData['vendor_id'],
                'subtotal' => floatval($templateData['subtotal'] ?? 0),
                'tax_amount' => floatval($templateData['tax_amount'] ?? 0),
                'discount_amount' => floatval($templateData['discount_amount'] ?? 0),
                'total_amount' => floatval($templateData['total_amount'] ?? 0),
                'status' => 'draft',
                'notes' => $templateData['notes'] ?? 'Recurring bill',
                'created_by' => $this->session['user_id']
            ];

            $billId = $this->billModel->create($billData);
            if (!$billId) {
                throw new Exception('Failed to create bill');
            }

            // Add bill items
            if (!empty($templateData['items'])) {
                foreach ($templateData['items'] as $item) {
                    $this->billModel->addItem($billId, $item);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Recurring bill error: ' . $e->getMessage());
            return false;
        }
    }

    private function processRecurringJournal($recurring, $templateData) {
        try {
            $this->db->beginTransaction();

            $journalDate = $recurring['next_occurrence_date'] ?? date('Y-m-d');
            $journalNumber = $this->journalModel->getNextEntryNumber();

            $journalData = [
                'entry_number' => $journalNumber,
                'entry_date' => $journalDate,
                'reference' => 'RECURRING-' . $recurring['id'],
                'description' => $templateData['description'] ?? 'Recurring journal entry',
                'amount' => floatval($templateData['amount'] ?? 0),
                'status' => 'draft',
                'journal_type' => $templateData['journal_type'] ?? 'general',
                'created_by' => $this->session['user_id']
            ];

            $journalId = $this->journalModel->create($journalData);
            if (!$journalId) {
                throw new Exception('Failed to create journal entry');
            }

            // Add journal lines
            if (!empty($templateData['lines'])) {
                foreach ($templateData['lines'] as $line) {
                    $this->db->query(
                        "INSERT INTO `" . $this->db->getPrefix() . "journal_entry_lines` 
                         (journal_entry_id, account_id, description, debit, credit, created_at) 
                         VALUES (?, ?, ?, ?, ?, NOW())",
                        [
                            $journalId,
                            $line['account_id'],
                            $line['description'] ?? '',
                            floatval($line['debit'] ?? 0),
                            floatval($line['credit'] ?? 0)
                        ]
                    );
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Recurring journal error: ' . $e->getMessage());
            return false;
        }
    }

    private function calculateNextOccurrence($recurring) {
        $frequency = $recurring['frequency'];
        $interval = intval($recurring['frequency_interval'] ?? 1);
        $lastDate = $recurring['last_processed_date'] ?: $recurring['start_date'];

        switch ($frequency) {
            case 'daily':
                return date('Y-m-d', strtotime($lastDate . " +{$interval} days"));
            case 'weekly':
                return date('Y-m-d', strtotime($lastDate . " +{$interval} weeks"));
            case 'monthly':
                return date('Y-m-d', strtotime($lastDate . " +{$interval} months"));
            case 'quarterly':
                return date('Y-m-d', strtotime($lastDate . " +" . ($interval * 3) . " months"));
            case 'yearly':
                return date('Y-m-d', strtotime($lastDate . " +{$interval} years"));
            default:
                return date('Y-m-d', strtotime($lastDate . " +1 month"));
        }
    }
}

