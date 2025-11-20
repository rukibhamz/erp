<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Chart of Accounts</h1>
        <?php if (hasPermission('accounts', 'create')): ?>
            <a href="<?= base_url('accounts/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Account
            </a>
        <?php endif; ?>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('accounts') ?>" class="row g-3">
            <div class="col-md-4">
                <label for="type" class="form-label">Account Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <option value="Assets" <?= ($selected_type ?? '') === 'Assets' ? 'selected' : '' ?>>Assets</option>
                    <option value="Liabilities" <?= ($selected_type ?? '') === 'Liabilities' ? 'selected' : '' ?>>Liabilities</option>
                    <option value="Equity" <?= ($selected_type ?? '') === 'Equity' ? 'selected' : '' ?>>Equity</option>
                    <option value="Revenue" <?= ($selected_type ?? '') === 'Revenue' ? 'selected' : '' ?>>Revenue</option>
                    <option value="Expenses" <?= ($selected_type ?? '') === 'Expenses' ? 'selected' : '' ?>>Expenses</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search by code or name">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Accounts Table -->
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="card-title mb-0">Accounts</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Account Name</th>
                        <?php if ($account_number_enabled ?? false): ?>
                        <th>Account Number</th>
                        <?php endif; ?>
                        <th>Type</th>
                        <th class="text-end">Opening Balance</th>
                        <th class="text-end">Current Balance</th>
                        <th>Currency</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($accounts)): ?>
                        <?php foreach ($accounts as $account): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($account['account_code'] ?? '') ?></strong></td>
                                <td><?= htmlspecialchars($account['account_name'] ?? '') ?></td>
                                <?php if ($account_number_enabled ?? false): ?>
                                <td><?= htmlspecialchars($account['account_number'] ?? '-') ?></td>
                                <?php endif; ?>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($account['account_type'] ?? '') ?></span>
                                </td>
                                <td class="text-end"><?= format_currency($account['opening_balance'] ?? 0, $account['currency'] ?? 'USD') ?></td>
                                <td class="text-end"><strong><?= format_currency($account['balance'] ?? 0, $account['currency'] ?? 'USD') ?></strong></td>
                                <td><?= htmlspecialchars($account['currency'] ?? 'USD') ?></td>
                                <td>
                                    <span class="badge bg-<?= ($account['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($account['status'] ?? 'inactive') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('accounts/view/' . intval($account['id'])) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('accounts', 'update')): ?>
                                            <a href="<?= base_url('accounts/edit/' . intval($account['id'])) ?>" class="btn btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('accounts', 'delete')): ?>
                                            <form method="POST" action="<?= base_url('accounts/delete/' . intval($account['id'])) ?>" 
                                                  style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this account? This action cannot be undone.');">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= ($account_number_enabled ?? false) ? 9 : 8 ?>" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-list-ul"></i>
                                    <p class="mb-0">No accounts found.</p>
                                    <a href="<?= base_url('accounts/create') ?>" class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Create First Account
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

