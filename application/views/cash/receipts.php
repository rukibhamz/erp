<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Cash Receipts</h1>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Record Cash Receipt</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('cash/receipts') ?>">
                    <div class="mb-3">
                        <label for="cash_account_id" class="form-label">Cash Account <span class="text-danger">*</span></label>
                        <select class="form-select" id="cash_account_id" name="cash_account_id" required>
                            <option value="">Select Account</option>
                            <?php if (!empty($cash_accounts)): ?>
                                <?php foreach ($cash_accounts as $account): ?>
                                    <option value="<?= $account['id'] ?>">
                                        <?= htmlspecialchars($account['account_name']) ?> - 
                                        <?= format_currency($account['current_balance']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="payment_date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter receipt description..."></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Record Receipt
                        </button>
                        <a href="<?= base_url('cash') ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Quick Info</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">Cash receipts increase your cash account balance. The transaction will be automatically posted to your ledger.</p>
                <p class="small text-muted mb-0">All receipts are recorded as debit entries to your cash account.</p>
            </div>
        </div>
    </div>
</div>

