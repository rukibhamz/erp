<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Credit_note_model extends Base_Model {
    protected $table = 'credit_notes';
    
    public function getNextCreditNoteNumber() {
        try {
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(credit_note_number, 5) AS UNSIGNED)) as max_code
                 FROM `" . $this->db->getPrefix() . $this->table . "`
                 WHERE credit_note_number LIKE 'CN-%'"
            );
            $nextNum = ($result && isset($result['max_code'])) ? intval($result['max_code']) + 1 : 1;
            return 'CN-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Credit_note_model getNextCreditNoteNumber error: ' . $e->getMessage());
            return 'CN-00001';
        }
    }
    
    public function getItems($creditNoteId) {
        try {
            return $this->db->fetchAll(
                "SELECT cni.*, p.product_name, p.product_code
                 FROM `" . $this->db->getPrefix() . "credit_note_items` cni
                 LEFT JOIN `" . $this->db->getPrefix() . "products` p ON cni.product_id = p.id
                 WHERE cni.credit_note_id = ?
                 ORDER BY cni.id",
                [$creditNoteId]
            );
        } catch (Exception $e) {
            error_log('Credit_note_model getItems error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function applyToInvoice($creditNoteId, $invoiceId) {
        try {
            $creditNote = $this->getById($creditNoteId);
            if (!$creditNote || $creditNote['status'] !== 'issued') {
                return false;
            }
            
            $invoiceModel = $this->loadModel('Invoice_model');
            $invoice = $invoiceModel->getById($invoiceId);
            
            if (!$invoice) {
                return false;
            }
            
            $this->db->beginTransaction();
            
            // Update invoice balance
            $newBalance = floatval($invoice['balance_amount']) - floatval($creditNote['total_amount']);
            $newPaid = floatval($invoice['paid_amount']) + floatval($creditNote['total_amount']);
            
            $invoiceModel->update($invoiceId, [
                'balance_amount' => max(0, $newBalance),
                'paid_amount' => $newPaid
            ]);
            
            // Update credit note status
            $this->update($creditNoteId, ['status' => 'applied']);
            
            // Create journal entry for credit note application
            $this->createCreditNoteJournalEntry($creditNoteId, $invoiceId);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Credit_note_model applyToInvoice error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function createCreditNoteJournalEntry($creditNoteId, $invoiceId) {
        try {
            $creditNote = $this->getById($creditNoteId);
            $invoiceModel = $this->loadModel('Invoice_model');
            $invoice = $invoiceModel->getById($invoiceId);
            $customerModel = $this->loadModel('Customer_model');
            $customer = $customerModel->getById($invoice['customer_id']);
            $accountModel = $this->loadModel('Account_model');
            
            // Get Accounts Receivable account
            $arAccount = $accountModel->getDefaultAccount('Assets', 'Accounts Receivable');
            if (!$arAccount && $customer && $customer['account_id']) {
                $arAccount = $accountModel->getById($customer['account_id']);
            }
            
            if (!$arAccount) {
                throw new Exception('Accounts Receivable account not found');
            }
            
            // Get Revenue account (default)
            $revenueAccount = $accountModel->getDefaultAccount('Revenue');
            if (!$revenueAccount) {
                throw new Exception('Revenue account not found');
            }
            
            $journalModel = $this->loadModel('Journal_entry_model');
            $entryNumber = $journalModel->getNextEntryNumber();
            
            // Create journal entry
            $entryId = $journalModel->create([
                'entry_number' => $entryNumber,
                'entry_date' => date('Y-m-d'),
                'reference' => $creditNote['credit_note_number'],
                'description' => 'Credit Note Applied: ' . $creditNote['credit_note_number'],
                'amount' => $creditNote['total_amount'],
                'status' => 'draft',
                'journal_type' => 'sales',
                'created_by' => $_SESSION['user_id'] ?? null
            ]);
            
            // Create journal lines
            // DR: Accounts Receivable (decrease AR)
            $this->db->query(
                "INSERT INTO `" . $this->db->getPrefix() . "journal_entry_lines` 
                 (journal_entry_id, account_id, description, debit, credit, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$entryId, $arAccount['id'], 'Credit Note: ' . $creditNote['credit_note_number'], 
                 0, $creditNote['total_amount']]
            );
            
            // CR: Revenue (decrease revenue)
            $this->db->query(
                "INSERT INTO `" . $this->db->getPrefix() . "journal_entry_lines` 
                 (journal_entry_id, account_id, description, debit, credit, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$entryId, $revenueAccount['id'], 'Credit Note: ' . $creditNote['credit_note_number'], 
                 $creditNote['total_amount'], 0]
            );
            
            // Approve and post
            $journalModel->approve($entryId, $_SESSION['user_id'] ?? null);
            $journalModel->post($entryId, $_SESSION['user_id'] ?? null);
            
            return true;
        } catch (Exception $e) {
            error_log('Credit_note_model createCreditNoteJournalEntry error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function loadModel($modelName) {
        require_once BASEPATH . 'models/' . $modelName . '.php';
        return new $modelName();
    }
}

