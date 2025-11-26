    /**
     * Record payment for a bill
     */
    public function recordPayment($billId) {
        $this->requirePermission('payables', 'create');
        
        $billId = intval($billId);
        if ($billId <= 0) {
            $this->setFlashMessage('danger', 'Invalid bill ID.');
            redirect('payables/bills');
            return;
        }
        
        $bill = $this->billModel->getById($billId);
        if (!$bill) {
            $this->setFlashMessage('danger', 'Bill not found.');
            redirect('payables/bills');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $amount = floatval($_POST['amount'] ?? 0);
            $paymentDate = sanitize_input($_POST['payment_date'] ?? date('Y-m-d'));
            $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'bank_transfer');
            $cashAccountId = intval($_POST['cash_account_id'] ?? 0);
            $notes = sanitize_input($_POST['notes'] ?? '');
            
            if ($amount <= 0 || $amount > $bill['balance_amount']) {
                $this->setFlashMessage('danger', 'Invalid payment amount.');
                redirect('payables/bills/view/' . $billId);
                return;
            }
            
            $cashAccount = $this->cashAccountModel->getById($cashAccountId);
            if (!$cashAccount) {
                $this->setFlashMessage('danger', 'Cash account not found.');
                redirect('payables/bills/view/' . $billId);
                return;
            }
            
            // Create payment record
            $paymentData = [
                'payment_number' => $this->paymentModel->getNextPaymentNumber('payment'),
                'payment_date' => $paymentDate,
                'payment_type' => 'payment',
                'reference_type' => 'bill',
                'reference_id' => $billId,
                'vendor_id' => $bill['vendor_id'],
                'account_id' => $cashAccount['account_id'],
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'status' => 'posted',
                'created_by' => $this->session['user_id']
            ];
            
            $paymentId = $this->paymentModel->create($paymentData);
            
            if ($paymentId) {
                // Update bill
                $this->billModel->addPayment($billId, $amount);
                $this->billModel->updateStatus($billId);
                
                // Create journal entry using Transaction Service
                try {
                    // Get Accounts Payable account (2100)
                    $apAccount = $this->accountModel->getByCode('2100');
                    
                    if ($apAccount) {
                        $journalData = [
                            'date' => $paymentDate,
                            'reference_type' => 'bill_payment',
                            'reference_id' => $paymentId,
                            'description' => 'Payment for Bill ' . $bill['bill_number'],
                            'journal_type' => 'payment',
                            'entries' => [
                                [
                                    'account_id' => $apAccount['id'],
                                    'debit' => $amount,
                                    'credit' => 0.00,
                                    'description' => 'Accounts Payable'
                                ],
                                [
                                    'account_id' => $cashAccount['account_id'],
                                    'debit' => 0.00,
                                    'credit' => $amount,
                                    'description' => 'Cash Payment'
                                ]
                            ],
                            'created_by' => $this->session['user_id'],
                            'auto_post' => true
                        ];
                        
                        $this->transactionService->postJournalEntry($journalData);
                        
                        // Update cash account balance
                        $this->cashAccountModel->updateBalance($cashAccountId, $amount, 'withdrawal');
                    } else {
                        error_log('Payables recordPayment: Accounts Payable account (2100) not found');
                    }
                } catch (Exception $e) {
                    error_log('Payables recordPayment journal entry error: ' . $e->getMessage());
                    $this->setFlashMessage('warning', 'Payment recorded but journal entry failed. Please check logs.');
                }
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Payables', 'Recorded payment for bill: ' . $bill['bill_number']);
                $this->setFlashMessage('success', 'Payment recorded successfully.');
                redirect('payables/bills/view/' . $billId);
            } else {
                $this->setFlashMessage('danger', 'Failed to record payment.');
            }
        }
        
        $cashAccounts = $this->cashAccountModel->getActive();
        
        $data = [
            'page_title' => 'Record Payment',
            'bill' => $bill,
            'cash_accounts' => $cashAccounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('payables/record_payment', $data);
    }
