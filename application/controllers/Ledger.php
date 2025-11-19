<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ledger extends Base_Controller {
    private $journalModel;
    private $accountModel;
    private $transactionModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('ledger', 'read');
        $this->journalModel = $this->loadModel('Journal_entry_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $status = $_GET['status'] ?? null;
        
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . "journal_entries`";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY entry_date DESC, id DESC LIMIT 200";
            $entries = $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Ledger index error: ' . $e->getMessage());
            $entries = [];
        }
        
        $data = [
            'page_title' => 'Journal Entries',
            'entries' => $entries,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('ledger/index', $data);
    }
    
    public function create() {
        $this->requirePermission('ledger', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $entryData = [
                'entry_number' => $this->journalModel->getNextEntryNumber(),
                'entry_date' => sanitize_input($_POST['entry_date'] ?? date('Y-m-d')),
                'reference' => sanitize_input($_POST['reference'] ?? ''),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'status' => 'draft',
                'created_by' => $this->session['user_id']
            ];
            
            $entryId = $this->journalModel->create($entryData);
            
            if ($entryId) {
                // Add journal entry lines
                $lines = $_POST['lines'] ?? [];
                $totalDebit = 0;
                $totalCredit = 0;
                
                foreach ($lines as $line) {
                    $accountId = intval($line['account_id'] ?? 0);
                    $debit = floatval($line['debit'] ?? 0);
                    $credit = floatval($line['credit'] ?? 0);
                    $description = sanitize_input($line['description'] ?? '');
                    
                    if ($accountId > 0 && ($debit > 0 || $credit > 0)) {
                        $this->journalModel->addLine($entryId, [
                            'account_id' => $accountId,
                            'description' => $description,
                            'debit' => $debit,
                            'credit' => $credit
                        ]);
                        
                        $totalDebit += $debit;
                        $totalCredit += $credit;
                    }
                }
                
                // Validate balanced
                if (abs($totalDebit - $totalCredit) > 0.01) {
                    $this->journalModel->delete($entryId);
                    $this->setFlashMessage('danger', 'Journal entry must be balanced. Debits and credits must be equal.');
                    redirect('ledger/create');
                    return;
                }
                
                // Update entry amount
                $this->journalModel->update($entryId, ['amount' => $totalDebit]);
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Ledger', 'Created journal entry: ' . $entryData['entry_number']);
                $this->setFlashMessage('success', 'Journal entry created successfully.');
                redirect('ledger/view/' . $entryId);
            } else {
                $this->setFlashMessage('danger', 'Failed to create journal entry.');
            }
        }
        
        try {
            $accounts = $this->accountModel->getAll();
        } catch (Exception $e) {
            $accounts = [];
        }
        
        $data = [
            'page_title' => 'Create Journal Entry',
            'accounts' => $accounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('ledger/create', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('ledger', 'update');
        
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid journal entry ID.');
            redirect('ledger');
            return;
        }
        
        try {
            $entry = $this->journalModel->getById($id);
            if (!$entry) {
                $this->setFlashMessage('danger', 'Journal entry not found.');
                redirect('ledger');
                return;
            }
            
            // Don't allow editing posted entries
            if ($entry['status'] === 'posted') {
                $this->setFlashMessage('danger', 'Cannot edit posted journal entries.');
                redirect('ledger');
                return;
            }
            
            $lines = $this->journalModel->getLines($id);
        } catch (Exception $e) {
            error_log('Ledger edit load error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading journal entry.');
            redirect('ledger');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $entryData = [
                'entry_date' => sanitize_input($_POST['entry_date'] ?? date('Y-m-d')),
                'reference' => sanitize_input($_POST['reference'] ?? ''),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Delete existing lines
            $this->db->query("DELETE FROM `" . $this->db->getPrefix() . "journal_entry_lines` WHERE journal_entry_id = ?", [$id]);
            
            // Add new lines
            $lines = $_POST['lines'] ?? [];
            $totalDebit = 0;
            $totalCredit = 0;
            
            foreach ($lines as $line) {
                $accountId = intval($line['account_id'] ?? 0);
                $debit = floatval($line['debit'] ?? 0);
                $credit = floatval($line['credit'] ?? 0);
                $description = sanitize_input($line['description'] ?? '');
                
                if ($accountId > 0 && ($debit > 0 || $credit > 0)) {
                    $this->journalModel->addLine($id, [
                        'account_id' => $accountId,
                        'description' => $description,
                        'debit' => $debit,
                        'credit' => $credit
                    ]);
                    
                    $totalDebit += $debit;
                    $totalCredit += $credit;
                }
            }
            
            // Validate balanced
            if (abs($totalDebit - $totalCredit) > 0.01) {
                $this->setFlashMessage('danger', 'Journal entry must be balanced. Debits and credits must be equal.');
                redirect('ledger/edit/' . $id);
                return;
            }
            
            $entryData['amount'] = $totalDebit;
            
            if ($this->journalModel->update($id, $entryData)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Ledger', 'Updated journal entry: ' . $entry['entry_number']);
                $this->setFlashMessage('success', 'Journal entry updated successfully.');
                redirect('ledger/view/' . $id);
            } else {
                $this->setFlashMessage('danger', 'Failed to update journal entry.');
            }
        }
        
        try {
            $accounts = $this->accountModel->getAll();
        } catch (Exception $e) {
            $accounts = [];
        }
        
        $data = [
            'page_title' => 'Edit Journal Entry',
            'entry' => $entry,
            'lines' => $lines,
            'accounts' => $accounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('ledger/edit', $data);
    }
    
    public function view($id) {
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid journal entry ID.');
            redirect('ledger');
            return;
        }
        
        try {
            $entry = $this->journalModel->getById($id);
            if (!$entry) {
                $this->setFlashMessage('danger', 'Journal entry not found.');
                redirect('ledger');
                return;
            }
            
            $lines = $this->journalModel->getLines($id);
        } catch (Exception $e) {
            error_log('Ledger view error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading journal entry details.');
            redirect('ledger');
            return;
        }
        
        $data = [
            'page_title' => 'Journal Entry: ' . ($entry['entry_number'] ?? 'N/A'),
            'entry' => $entry,
            'lines' => $lines,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('ledger/view', $data);
    }
    
    public function approve($id) {
        $this->requirePermission('ledger', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('ledger');
            return;
        }
        
        check_csrf();
        
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid journal entry ID.');
            redirect('ledger');
            return;
        }
        
        try {
            $entry = $this->journalModel->getById($id);
            if (!$entry) {
                $this->setFlashMessage('danger', 'Journal entry not found.');
                redirect('ledger');
                return;
            }
            
            // Validate balanced
            if (!$this->journalModel->validateBalanced($id)) {
                $this->setFlashMessage('danger', 'Journal entry must be balanced before approval.');
                redirect('ledger/view/' . $id);
                return;
            }
            
            if ($this->journalModel->approve($id, $this->session['user_id'])) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Ledger', 'Approved journal entry: ' . $entry['entry_number']);
                $this->setFlashMessage('success', 'Journal entry approved successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to approve journal entry.');
            }
        } catch (Exception $e) {
            error_log('Ledger approve error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error approving journal entry.');
        }
        
        redirect('ledger/view/' . $id);
    }
    
    public function post($id) {
        $this->requirePermission('ledger', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('ledger');
            return;
        }
        
        check_csrf();
        
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid journal entry ID.');
            redirect('ledger');
            return;
        }
        
        try {
            $entry = $this->journalModel->getById($id);
            if (!$entry) {
                $this->setFlashMessage('danger', 'Journal entry not found.');
                redirect('ledger');
                return;
            }
            
            if ($entry['status'] !== 'approved') {
                $this->setFlashMessage('danger', 'Journal entry must be approved before posting.');
                redirect('ledger/view/' . $id);
                return;
            }
            
            if ($this->journalModel->post($id, $this->session['user_id'])) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Ledger', 'Posted journal entry: ' . $entry['entry_number']);
                $this->setFlashMessage('success', 'Journal entry posted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to post journal entry.');
            }
        } catch (Exception $e) {
            error_log('Ledger post error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error posting journal entry.');
        }
        
        redirect('ledger/view/' . $id);
    }
    
    public function delete($id) {
        $this->requirePermission('ledger', 'delete');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('ledger');
            return;
        }
        
        check_csrf();
        
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid journal entry ID.');
            redirect('ledger');
            return;
        }
        
        try {
            $entry = $this->journalModel->getById($id);
            if (!$entry) {
                $this->setFlashMessage('danger', 'Journal entry not found.');
                redirect('ledger');
                return;
            }
            
            // Don't allow deleting posted entries
            if ($entry['status'] === 'posted') {
                $this->setFlashMessage('danger', 'Cannot delete posted journal entries.');
                redirect('ledger');
                return;
            }
            
            if ($this->journalModel->delete($id)) {
                $this->activityModel->log($this->session['user_id'], 'delete', 'Ledger', 'Deleted journal entry: ' . $entry['entry_number']);
                $this->setFlashMessage('success', 'Journal entry deleted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to delete journal entry.');
            }
        } catch (Exception $e) {
            error_log('Ledger delete error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error deleting journal entry.');
        }
        
        redirect('ledger');
    }
}

