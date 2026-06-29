<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Transactions</h1>
        <?php if (hasPermission('accounting', 'create')): ?>
            <a href="<?= base_url('transactions/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Transaction
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
<div class="card shadow-sm mb-4 list-filters-card">
    <div class="card-body">
        <form method="GET" action="<?= base_url('transactions') ?>" class="list-filters-form">
            <div class="row g-2 align-items-end list-filters-row">
                <?php
                $search_col_class = 'col-12 col-md';
                $search_placeholder = 'Txn #, description, account, ID…';
                include(BASEPATH . 'views/partials/list_search_field.php');
                ?>
            <div class="col-md-3">
                <label for="account_id" class="form-label">Account</label>
                <select class="form-select" id="account_id" name="account_id">
                    <option value="">All Accounts</option>
                    <?php if (!empty($accounts)): ?>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>" <?= ($selected_account_id ?? '') == $acc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="draft" <?= ($selected_status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="posted" <?= ($selected_status ?? '') === 'posted' ? 'selected' : '' ?>>Posted</option>
                </select>
            </div>
                <?php render_list_filter_per_page(intval($pagination['per_page'] ?? 50)); ?>
                <?php render_list_filter_submit_buttons(base_url('transactions')); ?>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="card-title mb-0">All Transactions</h5>
    </div>
    <div class="card-body">
        <?php
        $bulk_delete_enabled = hasPermission('accounting', 'delete');
        bulk_delete_render_toolbar($bulk_delete_enabled, $transactions, base_url('transactions/bulk-delete'), 'transaction', 'Are you sure you want to delete the selected transactions?');
        ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <?php bulk_delete_render_checkbox_th($bulk_delete_enabled); ?>
                        <th>Date</th>
                        <th>Transaction #</th>
                        <th>Account</th>
                        <th>Description</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $txn): ?>
                            <?php $txn_deletable = ($txn['status'] ?? '') !== 'posted' || isSuperAdmin(); ?>
                            <tr>
                                <?php if ($bulk_delete_enabled && $txn_deletable): ?>
                                    <?php bulk_delete_render_checkbox_td(true, (int)$txn['id'], 'transaction ' . ($txn['transaction_number'] ?? $txn['id'])); ?>
                                <?php elseif ($bulk_delete_enabled): ?>
                                    <td></td>
                                <?php endif; ?>
                                <td><?= format_date($txn['transaction_date'] ?? '') ?></td>
                                <td><strong><?= htmlspecialchars($txn['transaction_number'] ?? 'N/A') ?></strong></td>
                                <td><?= htmlspecialchars(($txn['account_code'] ?? '') . ' - ' . ($txn['account_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($txn['description'] ?? '') ?></td>
                                <td class="text-end">
                                    <?php if ($txn['debit'] > 0): ?>
                                        <span class="text-danger"><?= format_currency($txn['debit']) ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($txn['credit'] > 0): ?>
                                        <span class="text-success"><?= format_currency($txn['credit']) ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= ($txn['status'] ?? '') === 'posted' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($txn['status'] ?? 'draft') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('transactions/view/' . intval($txn['id'])) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('accounting', 'update') && ($txn['status'] ?? '') !== 'posted'): ?>
                                            <a href="<?= base_url('transactions/edit/' . intval($txn['id'])) ?>" class="btn btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('accounting', 'delete') && (($txn['status'] ?? '') !== 'posted' || isSuperAdmin())): ?>
                                            <form method="POST" action="<?= base_url('transactions/delete/' . intval($txn['id'])) ?>" 

                                                  style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this transaction?');">
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
                            <td colspan="<?= bulk_delete_colspan(8, $bulk_delete_enabled) ?>" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-arrow-left-right"></i>
                                    <p class="mb-0">No transactions found.</p>
                                    <a href="<?= base_url('transactions/create') ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-circle"></i> Create Transaction
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include BASEPATH . 'views/partials/accounting_table_footer.php'; ?>
</div>

