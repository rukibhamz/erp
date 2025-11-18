<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Cash Payments</h1>
        <a href="<?= base_url('cash') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-arrow-up-circle"></i> Record Cash Payment</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('cash/payments') ?>">
                    <?php echo csrf_field(); ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cash_account_id" class="form-label">Cash Account <span class="text-danger">*</span></label>
                            <select class="form-select" id="cash_account_id" name="cash_account_id" required>
                                <option value="">Select Account</option>
                                <?php if (!empty($cash_accounts)): ?>
                                    <?php foreach ($cash_accounts as $account): ?>
                                        <option value="<?= $account['id'] ?>" <?= (isset($_GET['account']) && $_GET['account'] == $account['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($account['account_name']) ?> 
                                            (<?= format_currency($account['current_balance'], $account['currency']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required min="0.01">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="check">Check</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="card">Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="account_id" class="form-label">Expense Account</label>
                            <select class="form-select" id="account_id" name="account_id">
                                <option value="">Select Expense Account (Optional)</option>
                                <?php if (!empty($expense_accounts)): ?>
                                    <?php foreach ($expense_accounts as $account): ?>
                                        <option value="<?= $account['id'] ?>">
                                            <?= htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Select an expense account to categorize this payment</small>
                        </div>
                        
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter description for this payment"></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="<?= base_url('cash') ?>" class="btn btn-primary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-info-circle"></i> Information</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Record cash payments to track money paid out from your cash accounts. 
                    This will automatically update the account balance and create accounting transactions.
                </p>
                <hr>
                <h6>Quick Tips:</h6>
                <ul class="small text-muted">
                    <li>Select the cash account from which payment was made</li>
                    <li>Enter the exact amount paid</li>
                    <li>Choose the payment method used</li>
                    <li>Optionally select an expense account for categorization</li>
                    <li>Add a description for reference</li>
                </ul>
            </div>
        </div>
    </div>
</div>

