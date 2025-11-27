<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Recurring_transaction_model extends Base_Model {
    protected $table = 'recurring_transactions';
    
    public function getDue() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE status = 'active' AND next_run_date <= CURDATE()
                 ORDER BY next_run_date ASC"
            );
        } catch (Exception $e) {
            error_log('Recurring_transaction_model getDue error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getDueTransactions() {
        // Alias for getDue
        return $this->getDue();
    }
    
    public function calculateNextRunDate($startDate, $frequency, $currentDate = null) {
        $currentDate = $currentDate ?: date('Y-m-d');
        $date = new DateTime($currentDate);
        
        switch ($frequency) {
            case 'daily':
                $date->modify('+1 day');
                break;
            case 'weekly':
                $date->modify('+1 week');
                break;
            case 'monthly':
                $date->modify('+1 month');
                break;
            case 'quarterly':
                $date->modify('+3 months');
                break;
            case 'annually':
                $date->modify('+1 year');
                break;
            default:
                $date->modify('+1 month');
        }
        
        return $date->format('Y-m-d');
    }
    
    public function process($recurringId) {
        try {
            $recurring = $this->getById($recurringId);
            if (!$recurring || $recurring['status'] !== 'active') {
                return false;
            }
            
            // Check if due date has passed
            if (strtotime($recurring['next_run_date']) > time()) {
                return false;
            }
            
            // Check if end date has passed
            if ($recurring['end_date'] && strtotime($recurring['end_date']) < time()) {
                $this->update($recurringId, ['status' => 'completed']);
                return false;
            }
            
            $this->db->beginTransaction();
            
            // Process based on transaction type
            $success = false;
            switch ($recurring['transaction_type']) {
                case 'invoice':
                    $success = $this->processRecurringInvoice($recurring);
                    break;
                case 'bill':
                    $success = $this->processRecurringBill($recurring);
                    break;
                case 'payment':
                    $success = $this->processRecurringPayment($recurring);
                    break;
                case 'journal':
                    $success = $this->processRecurringJournal($recurring);
                    break;
            }
            
            if ($success) {
                // Calculate next run date
                $nextRun = $this->calculateNextRunDate(
                    $recurring['start_date'], 
                    $recurring['frequency'], 
                    $recurring['next_run_date']
                );
                
                // Update next run date
                $this->update($recurringId, ['next_run_date' => $nextRun]);
                
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Recurring_transaction_model process error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function processRecurringInvoice($recurring) {
        try {
            $invoiceModel = $this->loadModel('Invoice_model');
            $originalInvoice = $invoiceModel->getById($recurring['transaction_id']);
            
            if (!$originalInvoice) {
                return false;
            }
            
            // Create new invoice based on original
            $newInvoiceData = [
                'invoice_number' => $invoiceModel->getNextInvoiceNumber(),
                'customer_id' => $originalInvoice['customer_id'],
                'invoice_date' => date('Y-m-d'),
                'due_date' => $this->calculateDueDate($originalInvoice['invoice_date'], $originalInvoice['due_date']),
                'subtotal' => $originalInvoice['subtotal'],
                'tax_rate' => $originalInvoice['tax_rate'],
                'tax_amount' => $originalInvoice['tax_amount'],
                'discount_amount' => $originalInvoice['discount_amount'],
                'total_amount' => $originalInvoice['total_amount'],
                'balance_amount' => $originalInvoice['total_amount'],
                'currency' => $originalInvoice['currency'],
                'terms' => $originalInvoice['terms'],
                'notes' => $originalInvoice['notes'],
                'status' => 'draft',
                'recurring' => 1,
                'recurring_frequency' => $recurring['frequency'],
                'created_by' => $recurring['created_by']
            ];
            
            $newInvoiceId = $invoiceModel->create($newInvoiceData);
            
            // Copy invoice items
            $items = $invoiceModel->getItems($recurring['transaction_id']);
            foreach ($items as $item) {
                $invoiceModel->addItem($newInvoiceId, [
                    'product_id' => $item['product_id'],
                    'item_description' => $item['item_description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'tax_amount' => $item['tax_amount'],
                    'line_total' => $item['line_total']
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Recurring_transaction_model processRecurringInvoice error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function processRecurringBill($recurring) {
        try {
            $billModel = $this->loadModel('Bill_model');
            $originalBill = $billModel->getById($recurring['transaction_id']);
            
            if (!$originalBill) {
                return false;
            }
            
            // Create new bill based on original
            $newBillData = [
                'bill_number' => $billModel->getNextBillNumber(),
                'vendor_id' => $originalBill['vendor_id'],
                'bill_date' => date('Y-m-d'),
                'due_date' => $this->calculateDueDate($originalBill['bill_date'], $originalBill['due_date']),
                'subtotal' => $originalBill['subtotal'],
                'tax_rate' => $originalBill['tax_rate'],
                'tax_amount' => $originalBill['tax_amount'],
                'discount_amount' => $originalBill['discount_amount'],
                'total_amount' => $originalBill['total_amount'],
                'balance_amount' => $originalBill['total_amount'],
                'paid_amount' => 0,
                'currency' => $originalBill['currency'],
                'terms' => $originalBill['terms'],
                'notes' => $originalBill['notes'],
                'status' => 'draft',
                'recurring' => 1,
                'recurring_frequency' => $recurring['frequency'],
                'created_by' => $recurring['created_by'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $newBillId = $billModel->create($newBillData);
            
            if (!$newBillId) {
                return false;
            }
            
            // Copy bill items
            $items = $billModel->getItems($recurring['transaction_id']);
            foreach ($items as $item) {
                $billModel->addItem($newBillId, [
                    'product_id' => $item['product_id'],
                    'item_description' => $item['item_description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'tax_amount' => $item['tax_amount'],
                    'discount_rate' => $item['discount_rate'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'line_total' => $item['line_total'],
                    'account_id' => $item['account_id'] ?? null
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Recurring_transaction_model processRecurringBill error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function processRecurringPayment($recurring) {
        try {
            $paymentModel = $this->loadModel('Payment_model');
            $originalPayment = $paymentModel->getById($recurring['transaction_id']);
            
            if (!$originalPayment) {
                return false;
            }
            
            // Create new payment based on original
            $newPaymentData = [
                'payment_number' => $paymentModel->getNextPaymentNumber($originalPayment['payment_type'] ?? 'receipt'),
                'payment_date' => date('Y-m-d'),
                'payment_type' => $originalPayment['payment_type'],
                'payment_method' => $originalPayment['payment_method'],
                'amount' => $originalPayment['amount'],
                'currency' => $originalPayment['currency'],
                'reference' => $originalPayment['reference'],
                'notes' => $originalPayment['notes'],
                'payee_id' => $originalPayment['payee_id'] ?? null,
                'payer_id' => $originalPayment['payer_id'] ?? null,
                'account_id' => $originalPayment['account_id'] ?? null,
                'cash_account_id' => $originalPayment['cash_account_id'] ?? null,
                'status' => 'pending',
                'recurring' => 1,
                'recurring_frequency' => $recurring['frequency'],
                'created_by' => $recurring['created_by'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $newPaymentId = $paymentModel->create($newPaymentData);
            
            if (!$newPaymentId) {
                return false;
            }
            
            // Note: Payment allocations are not copied automatically
            // They should be handled manually or through a separate workflow
            
            return true;
        } catch (Exception $e) {
            error_log('Recurring_transaction_model processRecurringPayment error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function processRecurringJournal($recurring) {
        try {
            $journalModel = $this->loadModel('Journal_entry_model');
            $originalEntry = $journalModel->getById($recurring['transaction_id']);
            
            if (!$originalEntry) {
                return false;
            }
            
            // Create new journal entry based on original
            $newEntryData = [
                'entry_number' => $journalModel->getNextEntryNumber(),
                'entry_date' => date('Y-m-d'),
                'journal_type' => $originalEntry['journal_type'],
                'description' => $originalEntry['description'],
                'reference' => $originalEntry['reference'],
                'status' => 'draft', // Requires approval before posting
                'recurring' => 1,
                'recurring_frequency' => $recurring['frequency'],
                'created_by' => $recurring['created_by'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $newEntryId = $journalModel->create($newEntryData);
            
            if (!$newEntryId) {
                return false;
            }
            
            // Copy journal entry lines (debits and credits)
            $lines = $journalModel->getLines($recurring['transaction_id']);
            foreach ($lines as $line) {
                $journalModel->addLine($newEntryId, [
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'description' => $line['description'],
                    'reference' => $line['reference'] ?? null
                ]);
            }
            
            // Validate that the entry is balanced
            if (!$journalModel->validateBalanced($newEntryId)) {
                error_log('Recurring_transaction_model processRecurringJournal error: Entry not balanced');
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Recurring_transaction_model processRecurringJournal error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function calculateDueDate($originalDate, $originalDueDate) {
        $days = (strtotime($originalDueDate) - strtotime($originalDate)) / (60 * 60 * 24);
        $newDate = new DateTime();
        $newDate->modify('+' . $days . ' days');
        return $newDate->format('Y-m-d');
    }
    
    private function loadModel($modelName) {
        require_once BASEPATH . 'models/' . $modelName . '.php';
        return new $modelName();
    }
}

