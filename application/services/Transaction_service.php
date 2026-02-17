<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Transaction Service
 * 
 * Centralized service for posting all financial transactions as journal entries.
 * Ensures proper double-entry bookkeeping and audit trails.
 */
class Transaction_service {
    private $journalModel;
    private $accountModel;
    private $db;
    private $balanceCalculator;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->journalModel = $this->loadModel('Journal_entry_model');
        $this->accountModel = $this->loadModel('Account_model');
        
        // Load Balance Calculator
        require_once BASEPATH . 'services/Balance_calculator.php';
        $this->balanceCalculator = new Balance_calculator();
    }
    
    /**
     * Post a journal entry with multiple line items
     * 
     * @param array $data Journal entry data
     * @return int|false Journal entry ID or false on failure
     * 
     * Example:
     * $data = [
     *     'date' => '2025-01-26',
     *     'reference_type' => 'payroll_run',
     *     'reference_id' => 123,
     *     'description' => 'Payroll for January 2025',
     *     'entries' => [
     *         [
     *             'account_id' => 1,
     *             'debit' => 50000.00,
     *             'credit' => 0.00,
     *             'description' => 'Payroll expense'
     *         ],
     *         [
     *             'account_id' => 2,
     *             'debit' => 0.00,
     *             'credit' => 50000.00,
     *             'description' => 'Cash payment'
     *         ]
     *     ],
     *     'created_by' => 1
     * ]
     */
    public function postJournalEntry($data) {
        try {
            // Validate required fields
            if (empty($data['entries']) || !is_array($data['entries'])) {
                throw new Exception('Journal entries are required');
            }
            
            if (empty($data['date'])) {
                throw new Exception('Entry date is required');
            }
            
            if (empty($data['created_by'])) {
                throw new Exception('Created by user ID is required');
            }
            
            // Validate that debits equal credits
            if (!$this->validateEntry($data['entries'])) {
                throw new Exception('Debits must equal credits');
            }
            
            // Calculate total amount
            $totalDebit = 0;
            foreach ($data['entries'] as $entry) {
                $totalDebit += floatval($entry['debit'] ?? 0);
            }
            
            $this->db->beginTransaction();
            
            // Create journal entry header
            $entryData = [
                'entry_number' => $this->journalModel->getNextEntryNumber(),
                'entry_date' => $data['date'],
                'reference' => isset($data['reference_type']) && isset($data['reference_id']) 
                               ? $data['reference_type'] . ':' . $data['reference_id'] 
                               : ($data['reference'] ?? null),
                'description' => $data['description'] ?? '',
                'amount' => $totalDebit,
                'status' => 'draft',
                'journal_type' => $data['journal_type'] ?? 'general',
                'created_by' => $data['created_by']
            ];
            
            $entryId = $this->journalModel->create($entryData);
            if (!$entryId) {
                throw new Exception('Failed to create journal entry');
            }
            
            // Add journal entry lines
            foreach ($data['entries'] as $line) {
                $lineData = [
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?? '',
                    'debit' => floatval($line['debit'] ?? 0),
                    'credit' => floatval($line['credit'] ?? 0)
                ];
                
                if (!$this->journalModel->addLine($entryId, $lineData)) {
                    throw new Exception('Failed to add journal entry line');
                }
            }
            
            // Auto-approve and post if configured
            if ($data['auto_post'] ?? false) {
                $this->journalModel->approve($entryId, $data['created_by']);
                $this->journalModel->post($entryId, $data['created_by']);
                
                // Update balances and invalidate cache for all affected accounts
                foreach ($data['entries'] as $entry) {
                    $this->balanceCalculator->invalidateCache($entry['account_id']);
                    
                    // Update account table balance (denormalization)
                    if (!empty($entry['debit']) && $entry['debit'] > 0) {
                        $this->accountModel->updateBalance($entry['account_id'], $entry['debit'], 'debit');
                    }
                    if (!empty($entry['credit']) && $entry['credit'] > 0) {
                        $this->accountModel->updateBalance($entry['account_id'], $entry['credit'], 'credit');
                    }
                }
            }
            
            $this->db->commit();
            
            // Log activity
            if (isset($data['reference_type']) && isset($data['reference_id'])) {
                $this->logActivity(
                    $data['created_by'],
                    'create',
                    'Journal Entry',
                    "Posted journal entry {$entryData['entry_number']} for {$data['reference_type']} #{$data['reference_id']}"
                );
            }
            
            return $entryId;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Transaction_service postJournalEntry error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Validate that debits equal credits
     * 
     * @param array $entries Array of journal entry lines
     * @return bool True if balanced, false otherwise
     */
    public function validateEntry($entries) {
        $totalDebit = 0;
        $totalCredit = 0;
        
        foreach ($entries as $entry) {
            $totalDebit += floatval($entry['debit'] ?? 0);
            $totalCredit += floatval($entry['credit'] ?? 0);
        }
        
        // Allow for floating point precision errors
        return abs($totalDebit - $totalCredit) < 0.01;
    }
    
    /**
     * Reverse a journal entry
     * 
     * @param int $journalId Journal entry ID to reverse
     * @param int $userId User ID performing the reversal
     * @return int|false Reversed entry ID or false on failure
     */
    public function reverseEntry($journalId, $userId) {
        try {
            $reversedId = $this->journalModel->reverse($journalId, $userId);
            
            if ($reversedId) {
                // Auto-approve and post the reversal
                $this->journalModel->approve($reversedId, $userId);
                $this->journalModel->post($reversedId, $userId);
                
                // Update balances and invalidate cache for all affected accounts
                $lines = $this->journalModel->getLines($reversedId);
                foreach ($lines as $line) {
                    $this->balanceCalculator->invalidateCache($line['account_id']);
                    
                    // Update account table balance (denormalization)
                    if (!empty($line['debit']) && $line['debit'] > 0) {
                        $this->accountModel->updateBalance($line['account_id'], $line['debit'], 'debit');
                    }
                    if (!empty($line['credit']) && $line['credit'] > 0) {
                        $this->accountModel->updateBalance($line['account_id'], $line['credit'], 'credit');
                    }
                }
                
                $this->logActivity(
                    $userId,
                    'reverse',
                    'Journal Entry',
                    "Reversed journal entry #{$journalId}"
                );
            }
            
            return $reversedId;
        } catch (Exception $e) {
            error_log('Transaction_service reverseEntry error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get journal entry by ID with all line items
     * 
     * @param int $id Journal entry ID
     * @return array|false Entry data with lines or false if not found
     */
    public function getEntryById($id) {
        try {
            $entry = $this->journalModel->getById($id);
            if (!$entry) {
                return false;
            }
            
            $entry['lines'] = $this->journalModel->getLines($id);
            return $entry;
        } catch (Exception $e) {
            error_log('Transaction_service getEntryById error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get journal entries by reference
     * 
     * @param string $type Reference type (e.g., 'payroll_run', 'invoice')
     * @param int $refId Reference ID
     * @return array Array of journal entries
     */
    public function getEntriesByReference($type, $refId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "journal_entries` 
                 WHERE reference = ? 
                 ORDER BY entry_date DESC, id DESC",
                [$type . ':' . $refId]
            );
        } catch (Exception $e) {
            error_log('Transaction_service getEntriesByReference error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Load a model
     * 
     * @param string $modelName Model name
     * @return object Model instance
     */
    private function loadModel($modelName) {
        require_once BASEPATH . 'models/' . $modelName . '.php';
        return new $modelName();
    }
    
    /**
     * Log activity
     * 
     * @param int $userId User ID
     * @param string $action Action type
     * @param string $module Module name
     * @param string $description Description
     */
    private function logActivity($userId, $action, $module, $description) {
        try {
            $activityModel = $this->loadModel('Activity_model');
            $activityModel->log($userId, $action, $module, $description);
        } catch (Exception $e) {
            error_log('Transaction_service logActivity error: ' . $e->getMessage());
        }
    }
}
