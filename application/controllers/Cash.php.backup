        } catch (Exception $e) {
            $cashAccounts = [];
        }
        
        $data = [
            'page_title' => 'Cash Management',
            'cash_accounts' => $cashAccounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('cash/index', $data);
    }
    
    public function accounts() {
        $this->requirePermission('cash', 'read');
        
        try {
            $cashAccounts = $this->cashAccountModel->getActive();
        } catch (Exception $e) {
            error_log('Cash accounts error: ' . $e->getMessage());
            $cashAccounts = [];
        }
        
        $data = [
            'page_title' => 'Cash Accounts',
            'cash_accounts' => $cashAccounts,
            'flash' => $this->getFlashMessage(),
            'session' => $this->session // Explicitly pass session for role checks
        ];
        
        $this->loadView('cash/accounts', $data);
    }
    
    public function createAccount() {
        $this->requirePermission('cash', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // First create the account
            $accountData = [
                'account_code' => sanitize_input($_POST['account_code'] ?? ''),
                'account_name' => sanitize_input($_POST['account_name'] ?? 'Cash Account: ' . ($_POST['account_name'] ?? '')),
                'account_type' => 'Assets',
                'opening_balance' => floatval($_POST['opening_balance'] ?? 0),
                'balance' => floatval($_POST['opening_balance'] ?? 0),
                'currency' => sanitize_input($_POST['currency'] ?? 'USD'),
                'status' => 'active',
                'created_by' => $this->session['user_id']
            ];
            
            if (empty($accountData['account_code'])) {
                $accountData['account_code'] = $this->accountModel->getNextAccountCode('Assets');
            }
            
            try {
                $accountId = $this->accountModel->create($accountData);
            } catch (Exception $e) {
                // If duplicate account code, try generating a new one
                if (stripos($e->getMessage(), 'Duplicate entry') !== false && stripos($e->getMessage(), 'account_code') !== false) {
                    // Generate a unique code by appending timestamp
                    $accountData['account_code'] = $this->accountModel->getNextAccountCode('Assets') . '-' . time();
                    try {
                        $accountId = $this->accountModel->create($accountData);
                    } catch (Exception $e2) {
                        $this->setFlashMessage('danger', 'Failed to create account: ' . $e2->getMessage());
                        redirect('cash/accounts/create');
                        return;
                    }
                } else {
                    $this->setFlashMessage('danger', 'Failed to create account: ' . $e->getMessage());
                    redirect('cash/accounts/create');
                    return;
                }
            }
            
            if ($accountId) {
                // Validate account_number (10 digits, numbers only)
                $accountNumber = sanitize_input($_POST['account_number'] ?? '');
                if (!empty($accountNumber)) {
                    // Remove any non-numeric characters
                    $accountNumber = preg_replace('/[^0-9]/', '', $accountNumber);
                    // Validate length (minimum 10, maximum 10 digits)
                    if (strlen($accountNumber) < 10 || strlen($accountNumber) > 10) {
                        $this->accountModel->delete($accountId);
                        $this->setFlashMessage('danger', 'Account number must be exactly 10 digits (numbers only).');
                        redirect('cash/accounts/create');
                        return;
                    }
                }
                
                // Create cash account
                $cashAccountData = [
                    'account_name' => sanitize_input($_POST['account_name'] ?? ''),
                    'account_type' => sanitize_input($_POST['account_type'] ?? 'bank_account'),
                    'account_id' => $accountId,
                    'bank_name' => sanitize_input($_POST['bank_name'] ?? ''),
                    'account_number' => $accountNumber,
                    'routing_number' => sanitize_input($_POST['routing_number'] ?? ''),
                    'swift_code' => sanitize_input($_POST['swift_code'] ?? ''),
                    'opening_balance' => floatval($_POST['opening_balance'] ?? 0),
                    'current_balance' => floatval($_POST['opening_balance'] ?? 0),
                    'currency' => sanitize_input($_POST['currency'] ?? 'USD'),
                    'status' => 'active'
                ];
                
                if ($this->cashAccountModel->create($cashAccountData)) {
                    $this->activityModel->log($this->session['user_id'], 'create', 'Cash', 'Created cash account: ' . $cashAccountData['account_name']);
                    $this->setFlashMessage('success', 'Cash account created successfully.');
                    redirect('cash/accounts');
                } else {
                    $this->accountModel->delete($accountId);
                    $this->setFlashMessage('danger', 'Failed to create cash account.');
                }
            } else {
                $this->setFlashMessage('danger', 'Failed to create account.');
            }
        }
        
        $data = [
            'page_title' => 'Create Cash Account',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('cash/create_account', $data);
    }
    
    public function receipts() {
        $this->requirePermission('cash', 'create');
        
        try {
            $cashAccounts = $this->cashAccountModel->getActive();
            $incomeAccounts = $this->accountModel->getByType('Revenue');
        } catch (Exception $e) {
            $cashAccounts = [];
            $incomeAccounts = [];
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            $cashAccountId = intval($_POST['cash_account_id'] ?? 0);
            $incomeAccountId = intval($_POST['income_account_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            $paymentDate = sanitize_input($_POST['payment_date'] ?? date('Y-m-d'));
            $description = sanitize_input($_POST['description'] ?? '');
            $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'cash');
            
            $cashAccount = $this->cashAccountModel->getById($cashAccountId);
            if (!$cashAccount) {
                $this->setFlashMessage('danger', 'Cash account not found.');
                redirect('cash/receipts');
            }
            
            // Get income account (default to first revenue account if not specified)
            if (!$incomeAccountId && !empty($incomeAccounts)) {
                $incomeAccountId = $incomeAccounts[0]['id'];
            }
            
            if (!$incomeAccountId) {
                $this->setFlashMessage('danger', 'Income account not found.');
                redirect('cash/receipts');
            }
            
            try {
                // Create payment record
                $paymentData = [
                    'payment_number' => $this->paymentModel->getNextPaymentNumber('receipt'),
                    'payment_date' => $paymentDate,
                    'payment_type' => 'receipt',
                    'account_id' => $cashAccount['account_id'],
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'notes' => $description,
                    'status' => 'posted',
                    'created_by' => $this->session['user_id']
                ];
                
                $paymentId = $this->paymentModel->create($paymentData);
                
                if (!$paymentId) {
                    throw new Exception('Failed to create payment record');
                }
                
                // Use Transaction Service to post journal entry
                $journalData = [
                    'date' => $paymentDate,
                    'reference_type' => 'cash_receipt',
                    'reference_id' => $paymentId,
                    'description' => 'Cash receipt - ' . ($description ?: 'Payment received'),
                    'journal_type' => 'cash_receipt',
                    'entries' => [
                        [
                            'account_id' => $cashAccount['account_id'],
                            'debit' => $amount,
                            'credit' => 0.00,
                            'description' => 'Cash received'
                        ],
                        [
                            'account_id' => $incomeAccountId,
                            'debit' => 0.00,
                            'credit' => $amount,
                            'description' => 'Income received'
                        ]
                    ],
                    'created_by' => $this->session['user_id'],
                    'auto_post' => true
                ];
                
                $journalId = $this->transactionService->postJournalEntry($journalData);
                
                if (!$journalId) {
                    throw new Exception('Failed to create journal entry');
                }
                
                // Update cash account balance
                $this->cashAccountModel->updateBalance($cashAccountId, $amount, 'deposit');
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Cash', 'Recorded cash receipt: ' . format_currency($amount));
                $this->setFlashMessage('success', 'Cash receipt recorded successfully.');
                redirect('cash/receipts');
            } catch (Exception $e) {
                error_log('Cash receipts error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to record cash receipt: ' . $e->getMessage());
                redirect('cash/receipts');
            }
        }
        
        $data = [
            'page_title' => 'Cash Receipts',
            'cash_accounts' => $cashAccounts,
            'income_accounts' => $incomeAccounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('cash/receipts', $data);
    }
    
    public function payments() {
        $this->requirePermission('cash', 'create');
        
        try {
            $cashAccounts = $this->cashAccountModel->getActive();
            $expenseAccounts = $this->accountModel->getByType('Expenses');
        } catch (Exception $e) {
            $cashAccounts = [];
            $expenseAccounts = [];
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            $cashAccountId = intval($_POST['cash_account_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            $paymentDate = sanitize_input($_POST['payment_date'] ?? date('Y-m-d'));
            $description = sanitize_input($_POST['description'] ?? '');
            $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'cash');
            $expenseAccountId = intval($_POST['expense_account_id'] ?? 0);
            
            $cashAccount = $this->cashAccountModel->getById($cashAccountId);
            if (!$cashAccount) {
                $this->setFlashMessage('danger', 'Cash account not found.');
                redirect('cash/payments');
            }
            
            if ($cashAccount['current_balance'] < $amount) {
                $this->setFlashMessage('danger', 'Insufficient balance in cash account.');
                redirect('cash/payments');
            }
            
            // Get expense account (default to first expense account if not specified)
            if (!$expenseAccountId && !empty($expenseAccounts)) {
                $expenseAccountId = $expenseAccounts[0]['id'];
            }
            
            if (!$expenseAccountId) {
                $this->setFlashMessage('danger', 'Expense account not found.');
                redirect('cash/payments');
            }
            
            try {
                // Create payment record
                $paymentData = [
                    'payment_number' => $this->paymentModel->getNextPaymentNumber('payment'),
                    'payment_date' => $paymentDate,
                    'payment_type' => 'payment',
                    'account_id' => $cashAccount['account_id'],
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'notes' => $description,
                    'status' => 'posted',
                    'created_by' => $this->session['user_id']
                ];
                
                $paymentId = $this->paymentModel->create($paymentData);
                
                if (!$paymentId) {
                    throw new Exception('Failed to create payment record');
                }
                
                // Use Transaction Service to post journal entry
                $journalData = [
                    'date' => $paymentDate,
                    'reference_type' => 'cash_payment',
                    'reference_id' => $paymentId,
                    'description' => 'Cash payment - ' . ($description ?: 'Expense payment'),
                    'journal_type' => 'cash_payment',
                    'entries' => [
                        [
                            'account_id' => $expenseAccountId,
                            'debit' => $amount,
                            'credit' => 0.00,
                            'description' => 'Expense payment'
                        ],
                        [
                            'account_id' => $cashAccount['account_id'],
                            'debit' => 0.00,
                            'credit' => $amount,
                            'description' => 'Cash paid'
                        ]
                    ],
                    'created_by' => $this->session['user_id'],
                    'auto_post' => true
                ];
                
                $journalId = $this->transactionService->postJournalEntry($journalData);
                
                if (!$journalId) {
                    throw new Exception('Failed to create journal entry');
                }
                
                // Update cash account balance
                $this->cashAccountModel->updateBalance($cashAccountId, $amount, 'withdrawal');
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Cash', 'Recorded cash payment: ' . format_currency($amount));
                $this->setFlashMessage('success', 'Cash payment recorded successfully.');
                redirect('cash/payments');
            } catch (Exception $e) {
                error_log('Cash payments error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to record cash payment: ' . $e->getMessage());
                redirect('cash/payments');
            }
        }
        
        $data = [
            'page_title' => 'Cash Payments',
            'cash_accounts' => $cashAccounts,
            'expense_accounts' => $expenseAccounts ?? [],
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('cash/payments', $data);
    }
    
    public function editAccount($id) {
        // Only allow admin and super_admin
        if (!isset($this->session['role']) || !in_array($this->session['role'], ['admin', 'super_admin'])) {
            $this->setFlashMessage('danger', 'You do not have permission to edit cash accounts.');
            redirect('cash/accounts');
            return;
        }
        
        $this->requirePermission('cash', 'update');
        
        // Validate ID parameter
        $id = intval($id);
        if ($id <= 0) {
            error_log("Cash editAccount: Invalid account ID: {$id}");
            $this->setFlashMessage('danger', 'Invalid account ID.');
            redirect('cash/accounts');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            try {
                $cashAccount = $this->cashAccountModel->getById($id);
                if (!$cashAccount) {
                    $this->setFlashMessage('danger', 'Cash account not found.');
                    redirect('cash/accounts');
                    return;
                }
                
                // Validate account_number (10 digits, numbers only)
                $accountNumber = sanitize_input($_POST['account_number'] ?? '');
                if (!empty($accountNumber)) {
                    // Remove any non-numeric characters
                    $accountNumber = preg_replace('/[^0-9]/', '', $accountNumber);
                    // Validate length (minimum 10, maximum 10 digits)
                    if (strlen($accountNumber) < 10 || strlen($accountNumber) > 10) {
                        $this->setFlashMessage('danger', 'Account number must be exactly 10 digits (numbers only).');
                        redirect('cash/accounts/edit/' . $id);
                        return;
                    }
                }
                
                $accountData = [
                    'account_name' => sanitize_input($_POST['account_name'] ?? ''),
                    'account_type' => sanitize_input($_POST['account_type'] ?? 'bank_account'),
                    'bank_name' => sanitize_input($_POST['bank_name'] ?? ''),
                    'account_number' => $accountNumber,
                    'routing_number' => sanitize_input($_POST['routing_number'] ?? ''),
                    'swift_code' => sanitize_input($_POST['swift_code'] ?? ''),
                    'currency' => sanitize_input($_POST['currency'] ?? 'USD'),
                    'status' => sanitize_input($_POST['status'] ?? 'active'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Only include account_number if it's not empty and the column exists
                // Remove account_number from update if it's empty to avoid errors
                if (empty($accountData['account_number'])) {
                    unset($accountData['account_number']);
                }
                
                // Update cash account
                try {
                    if ($this->cashAccountModel->update($id, $accountData)) {
                        // Also update the linked account if account_id exists
                        if (!empty($cashAccount['account_id'])) {
                            $this->accountModel->update($cashAccount['account_id'], [
                                'account_name' => $accountData['account_name'],
                                'currency' => $accountData['currency'],
                                'status' => $accountData['status'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                        }
                        
                        $this->activityModel->log($this->session['user_id'], 'update', 'Cash', 'Updated cash account: ' . $accountData['account_name']);
                        $this->setFlashMessage('success', 'Cash account updated successfully.');
                        redirect('cash/accounts');
                    } else {
                        $this->setFlashMessage('danger', 'Failed to update cash account.');
                    }
                } catch (Exception $updateException) {
                    error_log('Cash editAccount update error: ' . $updateException->getMessage());
                    // If account_number column doesn't exist, try updating without it
                    if (stripos($updateException->getMessage(), 'account_number') !== false) {
                        unset($accountData['account_number']);
                        if ($this->cashAccountModel->update($id, $accountData)) {
                            $this->setFlashMessage('success', 'Cash account updated successfully (account number field skipped).');
                            redirect('cash/accounts');
                        } else {
                            $this->setFlashMessage('danger', 'Failed to update cash account.');
                        }
                    } else {
                        $this->setFlashMessage('danger', 'Error updating cash account: ' . $updateException->getMessage());
                    }
                }
            } catch (Exception $e) {
                error_log('Cash editAccount error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Error updating cash account: ' . $e->getMessage());
            }
        }
        
        try {
            // Load complete cash account data with all columns
            $cashAccount = $this->cashAccountModel->getById($id);
            if (!$cashAccount) {
                error_log("Cash editAccount: Cash account not found for ID: {$id}");
                $this->setFlashMessage('danger', 'Cash account not found.');
                redirect('cash/accounts');
                return;
            }
            
            // Also load related account data if account_id exists
            if (!empty($cashAccount['account_id'])) {
                $linkedAccount = $this->accountModel->getById($cashAccount['account_id']);
                if ($linkedAccount) {
                    // Merge linked account data for reference (don't overwrite cash account data)
                    $cashAccount['linked_account_code'] = $linkedAccount['account_code'] ?? '';
                    $cashAccount['linked_account_name'] = $linkedAccount['account_name'] ?? '';
                }
            }
            
            // Ensure all fields are present with defaults
            $cashAccount['account_name'] = $cashAccount['account_name'] ?? '';
            $cashAccount['account_type'] = $cashAccount['account_type'] ?? 'bank_account';
            $cashAccount['bank_name'] = $cashAccount['bank_name'] ?? '';
            $cashAccount['account_number'] = $cashAccount['account_number'] ?? '';
            $cashAccount['routing_number'] = $cashAccount['routing_number'] ?? '';
            $cashAccount['swift_code'] = $cashAccount['swift_code'] ?? '';
            $cashAccount['currency'] = $cashAccount['currency'] ?? 'USD';
            $cashAccount['status'] = $cashAccount['status'] ?? 'active';
            $cashAccount['opening_balance'] = $cashAccount['opening_balance'] ?? 0;
            $cashAccount['current_balance'] = $cashAccount['current_balance'] ?? 0;
            
            error_log("Cash editAccount: Successfully loaded cash account ID: {$id} with all fields");
        } catch (Exception $e) {
            error_log('Cash editAccount load error: ' . $e->getMessage());
            error_log('Cash editAccount stack trace: ' . $e->getTraceAsString());
            $this->setFlashMessage('danger', 'Error loading cash account: ' . $e->getMessage());
            redirect('cash/accounts');
            return;
        }
        
        $data = [
            'page_title' => 'Edit Cash Account',
            'account' => $cashAccount,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('cash/edit_account', $data);
    }
    
    public function deleteAccount($id) {
        // Only allow admin and super_admin
        if (!isset($this->session['role']) || !in_array($this->session['role'], ['admin', 'super_admin'])) {
            $this->setFlashMessage('danger', 'You do not have permission to delete cash accounts.');
            redirect('cash/accounts');
            return;
        }
        
        $this->requirePermission('cash', 'delete');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('cash/accounts');
            return;
        }
        
        check_csrf();
        
        // Validate ID parameter
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid account ID.');
            redirect('cash/accounts');
            return;
        }
        
        try {
            $cashAccount = $this->cashAccountModel->getById($id);
            if (!$cashAccount) {
                $this->setFlashMessage('danger', 'Cash account not found.');
                redirect('cash/accounts');
                return;
            }
            
            // Check if account has transactions
            $transactions = $this->transactionModel->getByAccount($cashAccount['account_id'] ?? 0);
            if (!empty($transactions)) {
                $this->setFlashMessage('danger', 'Cannot delete cash account with existing transactions. Please deactivate it instead.');
                redirect('cash/accounts');
                return;
            }
            
            // Delete cash account
            if ($this->cashAccountModel->delete($id)) {
                // Also delete linked account if account_id exists
                if (!empty($cashAccount['account_id'])) {
                    $this->accountModel->delete($cashAccount['account_id']);
                }
                
                $this->activityModel->log($this->session['user_id'], 'delete', 'Cash', 'Deleted cash account: ' . $cashAccount['account_name']);
                $this->setFlashMessage('success', 'Cash account deleted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to delete cash account.');
            }
        } catch (Exception $e) {
            error_log('Cash deleteAccount error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error deleting cash account: ' . $e->getMessage());
        }
        
        redirect('cash/accounts');
    }
}

