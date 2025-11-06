<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Chart of Accounts</h1>
        <a href="<?= base_url('accounts/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Account
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('accounts') ?>" class="row g-3">
            <div class="col-md-4">
                <label for="type" class="form-label">Account Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <option value="Assets" <?= $selected_type === 'Assets' ? 'selected' : '' ?>>Assets</option>
                    <option value="Liabilities" <?= $selected_type === 'Liabilities' ? 'selected' : '' ?>>Liabilities</option>
                    <option value="Equity" <?= $selected_type === 'Equity' ? 'selected' : '' ?>>Equity</option>
                    <option value="Revenue" <?= $selected_type === 'Revenue' ? 'selected' : '' ?>>Revenue</option>
                    <option value="Expenses" <?= $selected_type === 'Expenses' ? 'selected' : '' ?>>Expenses</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Search by code or name..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Accounts Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Account Code</th>
                        <th>Account Name</th>
                        <th>Type</th>
                        <th>Parent Account</th>
                        <th class="text-end">Opening Balance</th>
                        <th class="text-end">Current Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($accounts)): ?>
                        <?php foreach ($accounts as $account): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($account['account_code']) ?></strong></td>
                                <td><?= htmlspecialchars($account['account_name']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($account['account_type']) ?></span></td>
                                <td>
                                    <?php if ($account['parent_id'] && isset($parent_map[$account['parent_id']])): ?>
                                        <?= htmlspecialchars($parent_map[$account['parent_id']]['account_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= format_currency($account['opening_balance']) ?></td>
                                <td class="text-end"><strong><?= format_currency($account['balance']) ?></strong></td>
                                <td>
                                    <span class="badge bg-<?= $account['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($account['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('accounts/edit/' . $account['id']) ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= base_url('accounts/delete/' . $account['id']) ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this account?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No accounts found. <a href="<?= base_url('accounts/create') ?>">Create your first account</a></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


