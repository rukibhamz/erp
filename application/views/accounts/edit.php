<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Edit Account</h1>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= base_url('accounts/edit/' . $account['id']) ?>
<?php echo csrf_field(); ?>">
                    <div class="mb-3">
                        <label for="account_code" class="form-label">Account Code</label>
                        <input type="text" class="form-control" id="account_code" name="account_code" value="<?= htmlspecialchars($account['account_code']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="account_name" name="account_name" value="<?= htmlspecialchars($account['account_name']) ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="account_type" name="account_type" required>
                                <option value="Assets" <?= $account['account_type'] === 'Assets' ? 'selected' : '' ?>>Assets</option>
                                <option value="Liabilities" <?= $account['account_type'] === 'Liabilities' ? 'selected' : '' ?>>Liabilities</option>
                                <option value="Equity" <?= $account['account_type'] === 'Equity' ? 'selected' : '' ?>>Equity</option>
                                <option value="Revenue" <?= $account['account_type'] === 'Revenue' ? 'selected' : '' ?>>Revenue</option>
                                <option value="Expenses" <?= $account['account_type'] === 'Expenses' ? 'selected' : '' ?>>Expenses</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="parent_id" class="form-label">Parent Account</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">None (Top Level)</option>
                                <?php if (!empty($parent_accounts)): ?>
                                    <?php foreach ($parent_accounts as $parent): ?>
                                        <?php if ($parent['id'] != $account['id']): ?>
                                            <option value="<?= $parent['id'] ?>" <?= $account['parent_id'] == $parent['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($parent['account_code'] . ' - ' . $parent['account_name']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="opening_balance" class="form-label">Opening Balance</label>
                            <input type="number" step="0.01" class="form-control" id="opening_balance" name="opening_balance" value="<?= $account['opening_balance'] ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-select" id="currency" name="currency">
                                <?php
                                $currencies = get_all_currencies();
                                $currentCurrency = $account['currency'] ?? 'USD';
                                foreach ($currencies as $code => $name): ?>
                                    <option value="<?= $code ?>" <?= $code === $currentCurrency ? 'selected' : '' ?>>
                                        <?= $code ?> - <?= $name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($account['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= $account['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $account['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Account
                        </button>
                        <a href="<?= base_url('accounts') ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Account Information</h6>
            </div>
            <div class="card-body">
                <p><strong>Current Balance:</strong> <?= format_currency($account['balance']) ?></p>
                <p><strong>Created:</strong> <?= date('M d, Y', strtotime($account['created_at'])) ?></p>
                <?php if ($account['updated_at']): ?>
                    <p><strong>Last Updated:</strong> <?= date('M d, Y', strtotime($account['updated_at'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


