<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Recurring extends Base_Controller {
    private $recurringModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('accounting', 'read');
        $this->recurringModel = $this->loadModel('Recurring_transaction_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    /**
     * List all recurring transactions
     */
    public function index() {
        try {
            $transactions = $this->recurringModel->getAll('next_run_date ASC');
            $dueTransactions = $this->recurringModel->getDue();
        } catch (Exception $e) {
            $transactions = [];
            $dueTransactions = [];
        }

        $data = [
            'page_title' => 'Recurring Transactions',
            'transactions' => $transactions,
            'due_transactions' => $dueTransactions,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('recurring/index', $data);
    }

    /**
     * Process all due recurring transactions
     */
    public function process() {
        $this->requirePermission('accounting', 'create');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('recurring');
        }

        check_csrf();

        try {
            $dueTransactions = $this->recurringModel->getDue();
            $processed = 0;
            $failed = 0;

            foreach ($dueTransactions as $transaction) {
                try {
                    if ($this->recurringModel->process($transaction['id'])) {
                        $processed++;
                    } else {
                        $failed++;
                    }
                } catch (Exception $e) {
                    error_log('Recurring process error for ID ' . $transaction['id'] . ': ' . $e->getMessage());
                    $failed++;
                }
            }

            $this->activityModel->log(
                $this->session['user_id'], 
                'create', 
                'Recurring Transactions', 
                "Processed $processed recurring transactions" . ($failed > 0 ? " ($failed failed)" : '')
            );

            if ($failed > 0) {
                $this->setFlashMessage('warning', "Processed $processed transactions. $failed failed — check error log for details.");
            } else if ($processed > 0) {
                $this->setFlashMessage('success', "Successfully processed $processed recurring transaction(s).");
            } else {
                $this->setFlashMessage('info', 'No recurring transactions are due at this time.');
            }
        } catch (Exception $e) {
            error_log('Recurring process error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'An error occurred while processing recurring transactions.');
        }

        redirect('recurring');
    }
}
