<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Journal_entry_model extends Base_Model {
    protected $table = 'journal_entries';
    
    public function getNextEntryNumber() {
        $year = date('Y');
        $result = $this->db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(entry_number, -6) AS UNSIGNED)) as max_num 
             FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE entry_number LIKE 'JE-{$year}-%'"
        );
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return 'JE-' . $year . '-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    }
    
    public function getLines($entryId) {
        return $this->db->fetchAll(
            "SELECT jel.*, a.account_code, a.account_name, a.account_type
             FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
             JOIN `" . $this->db->getPrefix() . "accounts` a ON jel.account_id = a.id
             WHERE jel.journal_entry_id = ? ORDER BY jel.id",
            [$entryId]
        );
    }
    
    public function approve($entryId, $userId) {
        return $this->update($entryId, [
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function reject($entryId) {
        return $this->update($entryId, ['status' => 'rejected']);
    }
    
    public function post($entryId, $userId) {
        // Post journal entry - create transactions
        $entry = $this->getById($entryId);
        if (!$entry || $entry['status'] !== 'approved') {
            return false;
        }
        
        $lines = $this->getLines($entryId);
        $transactionModel = $this->loadModel('Transaction_model');
        $accountModel = $this->loadModel('Account_model');
        
        foreach ($lines as $line) {
            // Create debit transaction
            if ($line['debit'] > 0) {
                $transactionModel->create([
                    'transaction_number' => $entry['entry_number'] . '-D' . $line['id'],
                    'transaction_date' => $entry['entry_date'],
                    'transaction_type' => 'journal',
                    'reference_id' => $entryId,
                    'reference_type' => 'journal_entry',
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?: $entry['description'],
                    'debit' => $line['debit'],
                    'credit' => 0,
                    'status' => 'posted',
                    'created_by' => $userId
                ]);
                
                // Update account balance
                $accountModel->updateBalance($line['account_id'], $line['debit'], 'debit');
            }
            
            // Create credit transaction
            if ($line['credit'] > 0) {
                $transactionModel->create([
                    'transaction_number' => $entry['entry_number'] . '-C' . $line['id'],
                    'transaction_date' => $entry['entry_date'],
                    'transaction_type' => 'journal',
                    'reference_id' => $entryId,
                    'reference_type' => 'journal_entry',
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?: $entry['description'],
                    'debit' => 0,
                    'credit' => $line['credit'],
                    'status' => 'posted',
                    'created_by' => $userId
                ]);
                
                // Update account balance
                $accountModel->updateBalance($line['account_id'], $line['credit'], 'credit');
            }
        }
        
        return $this->update($entryId, [
            'status' => 'posted',
            'posted_by' => $userId,
            'posted_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function validateBalanced($entryId) {
        try {
            $lines = $this->getLines($entryId);
            $totalDebit = 0;
            $totalCredit = 0;
            
            foreach ($lines as $line) {
                $totalDebit += floatval($line['debit']);
                $totalCredit += floatval($line['credit']);
            }
            
            return abs($totalDebit - $totalCredit) < 0.01; // Allow for floating point precision
        } catch (Exception $e) {
            error_log('Journal_entry_model validateBalanced error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function reverse($entryId, $userId) {
        try {
            $entry = $this->getById($entryId);
            if (!$entry || $entry['status'] !== 'posted') {
                return false;
            }
            
            $this->db->beginTransaction();
            
            // Create reversed entry
            $reversedData = [
                'entry_number' => $this->getNextEntryNumber(),
                'entry_date' => date('Y-m-d'),
                'reference' => 'REV-' . $entry['entry_number'],
                'description' => 'Reversal of ' . $entry['entry_number'] . ': ' . ($entry['description'] ?? ''),
                'amount' => $entry['amount'],
                'status' => 'draft',
                'journal_type' => $entry['journal_type'] ?? 'general',
                'reversed_entry_id' => $entryId,
                'created_by' => $userId
            ];
            
            $reversedId = $this->create($reversedData);
            if (!$reversedId) {
                throw new Exception('Failed to create reversal entry');
            }
            
            // Reverse all lines
            $lines = $this->getLines($entryId);
            foreach ($lines as $line) {
                $reversedLine = [
                    'journal_entry_id' => $reversedId,
                    'account_id' => $line['account_id'],
                    'description' => 'Reversal: ' . ($line['description'] ?? ''),
                    'debit' => $line['credit'], // Swap debit and credit
                    'credit' => $line['debit']
                ];
                
                $this->db->query(
                    "INSERT INTO `" . $this->db->getPrefix() . "journal_entry_lines` 
                     (journal_entry_id, account_id, description, debit, credit, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())",
                    [$reversedLine['journal_entry_id'], $reversedLine['account_id'], 
                     $reversedLine['description'], $reversedLine['debit'], $reversedLine['credit']]
                );
            }
            
            // Update original entry
            $this->update($entryId, ['reversed_entry_id' => $reversedId]);
            
            $this->db->commit();
            return $reversedId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Journal_entry_model reverse error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getByType($type) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE journal_type = ? 
                 ORDER BY entry_date DESC, id DESC",
                [$type]
            );
        } catch (Exception $e) {
            error_log('Journal_entry_model getByType error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getAttachments($entryId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "journal_entry_attachments` 
                 WHERE journal_entry_id = ? 
                 ORDER BY created_at DESC",
                [$entryId]
            );
        } catch (Exception $e) {
            error_log('Journal_entry_model getAttachments error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function addLine($entryId, $lineData) {
        try {
            $sql = "INSERT INTO `" . $this->db->getPrefix() . "journal_entry_lines` 
                    (journal_entry_id, account_id, description, debit, credit, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            return $this->db->query($sql, [
                $entryId,
                $lineData['account_id'] ?? null,
                $lineData['description'] ?? '',
                $lineData['debit'] ?? 0,
                $lineData['credit'] ?? 0
            ]);
        } catch (Exception $e) {
            error_log('Journal_entry_model addLine error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function loadModel($modelName) {
        require_once BASEPATH . 'models/' . $modelName . '.php';
        return new $modelName();
    }
}

