<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Transaction</h1>
        <a href="<?= base_url('transactions') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Transaction Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('transactions/create') ?>">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="transaction_date" class="form-label">Transaction Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="account_id" class="form-label">Account <span class="text-danger">*</span></label>
                            <select class="form-select" id="account_id" name="account_id" required>
                                <option value="">Select Account</option>
                                <?php if (!empty($accounts)): ?>
                                    <?php foreach ($accounts as $acc): ?>
                                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description" 
                                   placeholder="Transaction description">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="debit" class="form-label">Debit Amount</label>
                            <input type="number" step="0.01" class="form-control" id="debit" name="debit" 
                                   value="0" min="0" onchange="document.getElementById('credit').value = this.value > 0 ? 0 : document.getElementById('credit').value">
                        </div>
                        <div class="col-md-6">
                            <label for="credit" class="form-label">Credit Amount</label>
                            <input type="number" step="0.01" class="form-control" id="credit" name="credit" 
                                   value="0" min="0" onchange="document.getElementById('debit').value = this.value > 0 ? 0 : document.getElementById('debit').value">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="transaction_type" class="form-label">Transaction Type</label>
                            <select class="form-select" id="transaction_type" name="transaction_type">
                                <option value="manual" selected>Manual Entry</option>
                                <option value="invoice">Invoice</option>
                                <option value="bill">Bill</option>
                                <option value="payment">Payment</option>
                                <option value="receipt">Receipt</option>
                                <option value="journal">Journal Entry</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="draft" selected>Draft</option>
                                <option value="posted">Posted</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="reference" class="form-label">Reference</label>
                            <input type="text" class="form-control" id="reference" name="reference" 
                                   placeholder="Optional reference number">
                        </div>
                        <div class="col-md-6">
                            <label for="reference_type" class="form-label">Reference Type</label>
                            <input type="text" class="form-control" id="reference_type" name="reference_type" 
                                   placeholder="e.g., invoice, bill">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> Enter either a debit amount OR a credit amount, not both. 
                        Debits increase Assets and Expenses, Credits increase Liabilities, Equity, and Revenue.
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('transactions') ?>" class="btn btn-primary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

