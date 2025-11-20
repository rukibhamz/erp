<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit Account</h1>
        <a href="<?= base_url('accounts') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Account Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('accounts/edit/' . $account['id']) ?>">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="account_code" class="form-label">Account Code</label>
                            <input type="text" class="form-control" id="account_code" name="account_code" 
                                   value="<?= htmlspecialchars($account['account_code'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account_name" name="account_name" 
                                   value="<?= htmlspecialchars($account['account_name'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="account_type" name="account_type" required>
                                <option value="Assets" <?= ($account['account_type'] ?? '') === 'Assets' ? 'selected' : '' ?>>Assets</option>
                                <option value="Liabilities" <?= ($account['account_type'] ?? '') === 'Liabilities' ? 'selected' : '' ?>>Liabilities</option>
                                <option value="Equity" <?= ($account['account_type'] ?? '') === 'Equity' ? 'selected' : '' ?>>Equity</option>
                                <option value="Revenue" <?= ($account['account_type'] ?? '') === 'Revenue' ? 'selected' : '' ?>>Revenue</option>
                                <option value="Expenses" <?= ($account['account_type'] ?? '') === 'Expenses' ? 'selected' : '' ?>>Expenses</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="parent_id" class="form-label">Parent Account</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">None (Top Level)</option>
                                <?php if (!empty($parent_accounts)): ?>
                                    <?php foreach ($parent_accounts as $parent): ?>
                                        <?php if ($parent['id'] != $account['id']): ?>
                                            <option value="<?= $parent['id'] ?>" <?= ($account['parent_id'] ?? '') == $parent['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($parent['account_code'] . ' - ' . $parent['account_name']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="account_number" class="form-label">Account Number</label>
                            <input type="text" class="form-control" id="account_number" name="account_number" 
                                   value="<?= htmlspecialchars($account['account_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                            <select class="form-select" id="currency" name="currency" required>
                                <?php
                                $currencies = get_all_currencies();
                                $selectedCurrency = $account['currency'] ?? 'USD';
                                foreach ($currencies as $code => $name):
                                ?>
                                    <option value="<?= $code ?>" <?= $code === $selectedCurrency ? 'selected' : '' ?>>
                                        <?= $code ?> - <?= $name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?= ($account['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($account['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info mt-4">
                                <strong>Note:</strong> Opening balance and current balance cannot be edited. 
                                Use transactions to adjust balances.
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('accounts') ?>" class="btn btn-primary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Account Summary</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Opening Balance</dt>
                    <dd><?= format_currency($account['opening_balance'] ?? 0, $account['currency'] ?? 'USD') ?></dd>
                    
                    <dt>Current Balance</dt>
                    <dd class="fs-5 fw-bold"><?= format_currency($account['balance'] ?? 0, $account['currency'] ?? 'USD') ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

