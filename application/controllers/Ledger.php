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
            
            $sql .= " ORDER BY entry_date DESC, id DESC";
            
            $entries = $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
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
            check_csrf(); // CSRF Protection
            $entryDate = sanitize_input($_POST['entry_date'] ?? date('Y-m-d'));
            $reference = sanitize_input($_POST['reference'] ?? '');
            $description = sanitize_input($_POST['description'] ?? '');
            $lines = $_POST['lines'] ?? [];
            
            // Validate that debits equal credits
            $totalDebit = 0;
            $totalCredit = 0;
            
            foreach ($lines as $line) {
                $totalDebit += floatval($line['debit'] ?? 0);
                $totalCredit += floatval($line['credit'] ?? 0);
            }
            
            if (abs($totalDebit - $totalCredit) > 0.01) {
                $this->setFlashMessage('danger', 'Debits and credits must be equal. Difference: ' . abs($totalDebit - $totalCredit));
                redirect('ledger/create');
            }
            
            // Calculate total amount
            $amount = max($totalDebit, $totalCredit);
            
            // Create journal entry
            $entryData = [
                'entry_number' => $this->journalModel->getNextEntryNumber(),
                'entry_date' => $entryDate,
                'reference' => $reference,
                'description' => $description,
                'amount' => $amount,
                'status' => sanitize_input($_POST['status'] ?? 'draft'),
                'created_by' => $this->session['user_id']
            ];
            
            $entryId = $this->journalModel->create($entryData);
            
            if ($entryId) {
                // Create journal entry lines using Journal_entry_model's addLine method
                foreach ($lines as $line) {
                    if (!empty($line['account_id']) && (floatval($line['debit'] ?? 0) > 0 || floatval($line['credit'] ?? 0) > 0)) {
                        $lineData = [
                            'account_id' => intval($line['account_id']),
                            'description' => sanitize_input($line['description'] ?? ''),
                            'debit' => floatval($line['debit'] ?? 0),
                            'credit' => floatval($line['credit'] ?? 0)
                        ];
                        
                        $this->journalModel->addLine($entryId, $lineData);
                    }
                }
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Ledger', 'Created journal entry: ' . $entryData['entry_number']);
                $this->setFlashMessage('success', 'Journal entry created successfully.');
                redirect('ledger/edit/' . $entryId);
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
        $this->requirePermission('ledger', 'read');
        
        $entry = $this->journalModel->getById($id);
        if (!$entry) {
            $this->setFlashMessage('danger', 'Journal entry not found.');
            redirect('ledger');
        }
        
        $lines = $this->journalModel->getLines($id);
        
        $data = [
            'page_title' => 'Journal Entry: ' . $entry['entry_number'],
            'entry' => $entry,
            'lines' => $lines,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('ledger/edit', $data);
    }
    
    public function approve($id) {
        $this->requirePermission('accounting', 'approve');
        
        $entry = $this->journalModel->getById($id);
        if (!$entry) {
            $this->setFlashMessage('danger', 'Journal entry not found.');
            redirect('ledger');
        }
        
        if ($this->journalModel->approve($id, $this->session['user_id'])) {
            $this->activityModel->log($this->session['user_id'], 'approve', 'Ledger', 'Approved journal entry: ' . $entry['entry_number']);
            $this->setFlashMessage('success', 'Journal entry approved.');
        } else {
            $this->setFlashMessage('danger', 'Failed to approve journal entry.');
        }
        
        redirect('ledger/edit/' . $id);
    }
    
    public function post($id) {
        $this->requirePermission('accounting', 'post');
        
        $entry = $this->journalModel->getById($id);
        if (!$entry) {
            $this->setFlashMessage('danger', 'Journal entry not found.');
            redirect('ledger');
        }
        
        if ($entry['status'] !== 'approved') {
            $this->setFlashMessage('danger', 'Journal entry must be approved before posting.');
            redirect('ledger/edit/' . $id);
        }
        
        if ($this->journalModel->post($id, $this->session['user_id'])) {
            $this->activityModel->log($this->session['user_id'], 'post', 'Ledger', 'Posted journal entry: ' . $entry['entry_number']);
            $this->setFlashMessage('success', 'Journal entry posted successfully.');
        } else {
            $this->setFlashMessage('danger', 'Failed to post journal entry.');
        }
        
        redirect('ledger/edit/' . $id);
    }
}

