<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Create Account</h1>
</div>

<!-- Accounting Navigation -->
<div class="accounting-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('accounting') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link active" href="<?= base_url('accounts') ?>">
            <i class="bi bi-diagram-3"></i> Chart of Accounts
        </a>
        <a class="nav-link" href="<?= base_url('cash') ?>">
            <i class="bi bi-wallet2"></i> Cash Management
        </a>
        <a class="nav-link" href="<?= base_url('receivables') ?>">
            <i class="bi bi-receipt"></i> Receivables
        </a>
        <a class="nav-link" href="<?= base_url('payables') ?>">
            <i class="bi bi-file-earmark-medical"></i> Payables
        </a>
        <a class="nav-link" href="<?= base_url('ledger') ?>">
            <i class="bi bi-journal-text"></i> General Ledger
        </a>
    </nav>
</div>

<style>
.accounting-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.accounting-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.accounting-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.accounting-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.accounting-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= base_url('accounts/create') ?>">
                    <div class="mb-3">
                        <label for="account_code" class="form-label">Account Code <span class="text-muted">(Leave empty for auto-generation)</span></label>
                        <input type="text" class="form-control" id="account_code" name="account_code" placeholder="Auto-generated">
                    </div>
                    
                    <div class="mb-3">
                        <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="account_name" name="account_name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="account_type" name="account_type" required>
                                <option value="">Select Type</option>
                                <option value="Assets">Assets</option>
                                <option value="Liabilities">Liabilities</option>
                                <option value="Equity">Equity</option>
                                <option value="Revenue">Revenue</option>
                                <option value="Expenses">Expenses</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="parent_id" class="form-label">Parent Account</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">None (Top Level)</option>
                                <?php if (!empty($parent_accounts)): ?>
                                    <?php foreach ($parent_accounts as $parent): ?>
                                        <option value="<?= $parent['id'] ?>"><?= htmlspecialchars($parent['account_code'] . ' - ' . $parent['account_name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="opening_balance" class="form-label">Opening Balance</label>
                            <input type="number" step="0.01" class="form-control" id="opening_balance" name="opening_balance" value="0.00">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-select" id="currency" name="currency">
                                <?php
                                $currencies = get_all_currencies();
                                foreach ($currencies as $code => $name): ?>
                                    <option value="<?= $code ?>" <?= $code === 'USD' ? 'selected' : '' ?>>
                                        <?= $code ?> - <?= $name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Account
                        </button>
                        <a href="<?= base_url('accounts') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Account Types</h6>
            </div>
            <div class="card-body">
                <p class="small mb-2"><strong>Assets:</strong> Resources owned by the company</p>
                <p class="small mb-2"><strong>Liabilities:</strong> Debts and obligations</p>
                <p class="small mb-2"><strong>Equity:</strong> Owner's interest in the company</p>
                <p class="small mb-2"><strong>Revenue:</strong> Income from operations</p>
                <p class="small mb-0"><strong>Expenses:</strong> Costs incurred in operations</p>
            </div>
        </div>
    </div>
</div>


