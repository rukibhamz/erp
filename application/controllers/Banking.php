<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Banking extends Base_Controller {
    private $cashAccountModel;
    private $bankTransactionModel;
    private $bankReconciliationModel;
    private $accountModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('cash', 'read');
        $this->cashAccountModel = $this->loadModel('Cash_account_model');
        $this->bankTransactionModel = $this->loadModel('Bank_transaction_model');
        $this->bankReconciliationModel = $this->loadModel('Bank_reconciliation_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        try {
            $cashAccounts = $this->cashAccountModel->getActive();
        } catch (Exception $e) {
            $cashAccounts = [];
        }

        $data = [
            'page_title' => 'Banking & Reconciliation',
            'cash_accounts' => $cashAccounts,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('banking/index', $data);
    }

    public function transactions($accountId) {
        $this->requirePermission('cash', 'read');

        $cleared = $_GET['cleared'] ?? null;

        try {
            $cashAccount = $this->cashAccountModel->getById($accountId);
            if (!$cashAccount) {
                $this->setFlashMessage('danger', 'Bank account not found.');
                redirect('banking');
            }

            if ($cleared !== null) {
                $transactions = $this->bankTransactionModel->getByAccount($accountId, $cleared == 1);
            } else {
                $transactions = $this->bankTransactionModel->getByAccount($accountId);
            }
        } catch (Exception $e) {
            $transactions = [];
            $cashAccount = null;
        }

        $data = [
            'page_title' => 'Bank Transactions',
            'cash_account' => $cashAccount,
            'transactions' => $transactions,
            'selected_cleared' => $cleared,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('banking/transactions', $data);
    }

    public function addTransaction($accountId) {
        $this->requirePermission('cash', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'cash_account_id' => $accountId,
                'transaction_date' => sanitize_input($_POST['transaction_date'] ?? date('Y-m-d')),
                'transaction_type' => sanitize_input($_POST['transaction_type'] ?? 'deposit'),
                'amount' => floatval($_POST['amount'] ?? 0),
                'currency' => sanitize_input($_POST['currency'] ?? 'USD'),
                'payee' => sanitize_input($_POST['payee'] ?? ''),
                'category' => sanitize_input($_POST['category'] ?? ''),
                'reference' => sanitize_input($_POST['reference'] ?? ''),
                'check_number' => sanitize_input($_POST['check_number'] ?? ''),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'cleared' => !empty($_POST['cleared']) ? 1 : 0,
                'cleared_date' => !empty($_POST['cleared']) ? date('Y-m-d') : null,
                'created_by' => $this->session['user_id']
            ];

            if ($this->bankTransactionModel->create($data)) {
                // Update cash account balance
                $cashAccount = $this->cashAccountModel->getById($accountId);
                if ($cashAccount) {
                    $amount = $data['amount'];
                    if (in_array($data['transaction_type'], ['deposit', 'transfer'])) {
                        $this->cashAccountModel->updateBalance($accountId, $amount, 'deposit');
                    } else {
                        $this->cashAccountModel->updateBalance($accountId, $amount, 'withdrawal');
                    }
                }

                $this->activityModel->log($this->session['user_id'], 'create', 'Banking', 'Added bank transaction');
                $this->setFlashMessage('success', 'Transaction added successfully.');
                redirect('banking/transactions/' . $accountId);
            } else {
                $this->setFlashMessage('danger', 'Failed to add transaction.');
            }
        }

        try {
            $cashAccount = $this->cashAccountModel->getById($accountId);
        } catch (Exception $e) {
            $cashAccount = null;
        }

        $data = [
            'page_title' => 'Add Bank Transaction',
            'cash_account' => $cashAccount,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('banking/add_transaction', $data);
    }

    public function reconcile($accountId) {
        $this->requirePermission('cash', 'update');

        try {
            $cashAccount = $this->cashAccountModel->getById($accountId);
            if (!$cashAccount) {
                $this->setFlashMessage('danger', 'Bank account not found.');
                redirect('banking');
            }

            $uncleared = $this->bankTransactionModel->getUncleared($accountId);
            $bookBalance = floatval($cashAccount['current_balance']);
        } catch (Exception $e) {
            $cashAccount = null;
            $uncleared = [];
            $bookBalance = 0;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $statementDate = sanitize_input($_POST['statement_date'] ?? date('Y-m-d'));
            $statementBalance = floatval($_POST['statement_balance'] ?? 0);
            $transactionIds = $_POST['cleared_transactions'] ?? [];
            $adjustments = floatval($_POST['adjustments'] ?? 0);

            try {
                $this->db->beginTransaction();

                // Create reconciliation record
                $reconciliationData = [
                    'cash_account_id' => $accountId,
                    'reconciliation_date' => $statementDate,
                    'opening_balance' => $bookBalance,
                    'bank_statement_balance' => $statementBalance,
                    'closing_balance' => $statementBalance,
                    'ending_balance' => $statementBalance,
                    'adjustments' => $adjustments,
                    'status' => 'completed',
                    'created_by' => $this->session['user_id']
                ];

                $reconciliationId = $this->bankReconciliationModel->create($reconciliationData);

                // Mark transactions as cleared
                $clearedCount = 0;
                foreach ($transactionIds as $transId) {
                    if ($this->bankTransactionModel->markCleared($transId, $reconciliationId)) {
                        $clearedCount++;
                    }
                }

                // Update reconciliation with cleared count
                $this->bankReconciliationModel->update($reconciliationId, [
                    'cleared_transactions_count' => $clearedCount
                ]);

                $this->db->commit();
                $this->activityModel->log($this->session['user_id'], 'create', 'Banking', 'Completed bank reconciliation');
                $this->setFlashMessage('success', 'Bank reconciliation completed successfully.');
                redirect('banking/reconciliations/' . $accountId);
            } catch (Exception $e) {
                $this->db->rollBack();
                error_log('Banking reconcile error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to complete reconciliation: ' . $e->getMessage());
            }
        }

        $data = [
            'page_title' => 'Bank Reconciliation',
            'cash_account' => $cashAccount,
            'uncleared_transactions' => $uncleared,
            'book_balance' => $bookBalance,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('banking/reconcile', $data);
    }

    public function reconciliations($accountId) {
        $this->requirePermission('cash', 'read');

        try {
            $cashAccount = $this->cashAccountModel->getById($accountId);
            $reconciliations = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "bank_reconciliations` 
                 WHERE cash_account_id = ? 
                 ORDER BY reconciliation_date DESC",
                [$accountId]
            );
        } catch (Exception $e) {
            $cashAccount = null;
            $reconciliations = [];
        }

        $data = [
            'page_title' => 'Reconciliation History',
            'cash_account' => $cashAccount,
            'reconciliations' => $reconciliations,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('banking/reconciliations', $data);
    }
}

