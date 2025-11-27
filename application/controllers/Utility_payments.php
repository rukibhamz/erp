<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utility_payments extends Base_Controller {
    private $paymentModel;
    private $billModel;
    private $transactionModel;
            // Enhance payments with bill details
            foreach ($allPayments as &$payment) {
                if (!empty($payment['bill_id'])) {
                    try {
                        $bill = $this->billModel->getWithDetails($payment['bill_id']);
                        if ($bill) {
                            $payment['bill_number'] = $bill['bill_number'] ?? null;
                            $payment['meter_number'] = $bill['meter_number'] ?? null;
                        }
                    } catch (Exception $e) {
                        // Continue without bill details
                    }
                }
            }
            unset($payment);
        } catch (Exception $e) {
            $allPayments = [];
        }
        
        $data = [
            'page_title' => 'Utility Payments',
            'payments' => $allPayments,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/payments/index', $data);
    }
    
    public function record($billId) {
        $this->requirePermission('utilities', 'create');
        
        try {
            $bill = $this->billModel->getWithDetails($billId);
            if (!$bill) {
                $this->setFlashMessage('danger', 'Bill not found.');
                redirect('utilities/bills');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading bill.');
            redirect('utilities/bills');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $amount = floatval($_POST['amount'] ?? 0);
            $paymentDate = sanitize_input($_POST['payment_date'] ?? date('Y-m-d'));
            $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'bank_transfer');
            $referenceNumber = sanitize_input($_POST['reference_number'] ?? '');
            $notes = sanitize_input($_POST['notes'] ?? '');
            
            if ($amount <= 0) {
                $this->setFlashMessage('danger', 'Payment amount must be greater than zero.');
            } elseif ($amount > floatval($bill['balance_amount'])) {
                $this->setFlashMessage('danger', 'Payment amount cannot exceed balance amount.');
            } else {
                $paymentData = [
                    'payment_number' => $this->paymentModel->getNextPaymentNumber(),
                    'bill_id' => $billId,
                    'amount' => $amount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $paymentMethod,
                    'reference_number' => $referenceNumber,
                    'notes' => $notes,
                    'created_by' => $this->session['user_id'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $paymentId = $this->paymentModel->create($paymentData);
                
                if ($paymentId) {
                    // Update bill payment status
                    $this->billModel->updatePaymentStatus($billId, $amount);
                    
                    // Post to accounting
                    $this->postPaymentToAccounting($paymentId, $paymentData, $bill);
                    
                    $this->activityModel->log($this->session['user_id'], 'create', 'Utility Payments', 'Recorded payment: ' . $paymentData['payment_number']);
                    $this->setFlashMessage('success', 'Payment recorded successfully.');
                    redirect('utilities/bills/view/' . $billId);
                } else {
                    $this->setFlashMessage('danger', 'Failed to record payment.');
                }
            }
        }
        
        $data = [
            'page_title' => 'Record Payment',
            'bill' => $bill,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/payments/record', $data);
    }
    
    private function postPaymentToAccounting($paymentId, $paymentData, $bill) {
        try {
            // Find Cash/Bank account (1000)
            $cashAccount = $this->accountModel->getByCode('1000');
            if (!$cashAccount) {
                // Fallback search
                $assetAccounts = $this->accountModel->getByType('Assets');
                foreach ($assetAccounts as $acc) {
                    if (stripos($acc['account_name'], 'cash') !== false || 
                        stripos($acc['account_name'], 'bank') !== false) {
                        $cashAccount = $acc;
                        break;
                    }
                }
                if (!$cashAccount && !empty($assetAccounts)) {
                    $cashAccount = $assetAccounts[0];
                }
            }
            
            // Find Accounts Payable account (2000)
            $apAccount = $this->accountModel->getByCode('2000');
            if (!$apAccount) {
                // Fallback search
                $liabilityAccounts = $this->accountModel->getByType('Liabilities');
                foreach ($liabilityAccounts as $acc) {
                    if (stripos($acc['account_name'], 'payable') !== false || 
                        stripos($acc['account_name'], 'ap') !== false) {
                        $apAccount = $acc;
                        break;
                    }
                }
                if (!$apAccount && !empty($liabilityAccounts)) {
                    $apAccount = $liabilityAccounts[0];
                }
            }
            
            if (!$cashAccount || !$apAccount) {
                error_log('No cash or AP account found for payment accounting entry.');
                return;
            }
            
            // Create Journal Entry
            $journalData = [
                'date' => $paymentData['payment_date'],
                'reference_type' => 'utility_payment',
                'reference_id' => $paymentId,
                'description' => 'Utility Payment #' . $paymentData['payment_number'],
                'journal_type' => 'payment',
                'entries' => [
                    // Debit Accounts Payable
                    [
                        'account_id' => $apAccount['id'],
                        'debit' => $paymentData['amount'],
                        'credit' => 0,
                        'description' => 'Utility Bill Payment'
                    ],
                    // Credit Cash/Bank
                    [
                        'account_id' => $cashAccount['id'],
                        'debit' => 0,
                        'credit' => $paymentData['amount'],
                        'description' => 'Payment for Utility Bill #' . $bill['bill_number']
                    ]
                ],
                'created_by' => $this->session['user_id'] ?? null,
                'auto_post' => true
            ];
            
            $this->transactionService->postJournalEntry($journalData);
            
        } catch (Exception $e) {
            error_log('Utility_payments postPaymentToAccounting error: ' . $e->getMessage());
        }
    }
}

