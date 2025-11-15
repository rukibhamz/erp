<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Record Payment for Invoice: <?= htmlspecialchars($invoice['invoice_number'] ?? 'N/A') ?></h1>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Invoice Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Invoice Number:</strong> <?= htmlspecialchars($invoice['invoice_number']) ?></p>
                        <p><strong>Customer:</strong> <?= htmlspecialchars($invoice['company_name'] ?? '-') ?></p>
                        <p><strong>Invoice Date:</strong> <?= format_date($invoice['invoice_date']) ?></p>
                        <p><strong>Due Date:</strong> <?= format_date($invoice['due_date']) ?></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p><strong>Total Amount:</strong> <?= format_currency($invoice['total_amount'], $invoice['currency']) ?></p>
                        <p><strong>Paid:</strong> <?= format_currency($invoice['paid_amount'] ?? 0, $invoice['currency']) ?></p>
                        <p class="fs-5"><strong>Balance Due:</strong> <span class="text-danger"><?= format_currency($invoice['balance_amount'], $invoice['currency']) ?></span></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Payment Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('receivables/invoices/payment/' . $invoice['id']) ?>">
                    <?php echo csrf_field(); ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="cash_account_id" class="form-label">Payment Account <span class="text-danger">*</span></label>
                            <select class="form-select" id="cash_account_id" name="cash_account_id" required>
                                <option value="">Select Account</option>
                                <?php if (!empty($cash_accounts)): ?>
                                    <?php foreach ($cash_accounts as $account): ?>
                                        <option value="<?= $account['id'] ?>">
                                            <?= htmlspecialchars($account['account_name']) ?> (<?= format_currency($account['current_balance'], $account['currency']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                   value="<?= htmlspecialchars($invoice['balance_amount']) ?>" 
                                   max="<?= htmlspecialchars($invoice['balance_amount']) ?>" required>
                            <small class="form-text text-muted">Maximum: <?= format_currency($invoice['balance_amount'], $invoice['currency']) ?></small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="reference" class="form-label">Reference</label>
                            <input type="text" class="form-control" id="reference" name="reference" placeholder="Check number, transaction ID, etc.">
                        </div>
                        <div class="col-md-6">
                            <label for="notes" class="form-label">Notes</label>
                            <input type="text" class="form-control" id="notes" name="notes" placeholder="Additional notes">
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Record Payment
                        </button>
                        <a href="<?= base_url('receivables/invoices/edit/' . $invoice['id']) ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

