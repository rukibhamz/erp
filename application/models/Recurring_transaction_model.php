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
        // Similar to processRecurringInvoice but for bills
        return false; // TODO: Implement
    }
    
    private function processRecurringPayment($recurring) {
        // Similar to processRecurringInvoice but for payments
        return false; // TODO: Implement
    }
    
    private function processRecurringJournal($recurring) {
        // Similar to processRecurringInvoice but for journal entries
        return false; // TODO: Implement
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

