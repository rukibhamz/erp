<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Bank Transactions: <?= htmlspecialchars($cash_account['account_name'] ?? '') ?></h1>
        <div>
            <?php if (has_permission('cash', 'create')): ?>
                <a href="<?= base_url('banking/add-transaction/' . ($cash_account['id'] ?? '')) ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Transaction
                </a>
            <?php endif; ?>
            <a href="<?= base_url('banking') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <select name="cleared" class="form-select">
                        <option value="">All Transactions</option>
                        <option value="1" <?= $selected_cleared === '1' ? 'selected' : '' ?>>Cleared</option>
                        <option value="0" <?= $selected_cleared === '0' ? 'selected' : '' ?>>Uncleared</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-secondary">Filter</button>
                    <a href="<?= base_url('banking/transactions/' . ($cash_account['id'] ?? '')) ?>" class="btn btn-primary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Payee</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Cleared</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $trans): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($trans['transaction_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= in_array($trans['transaction_type'], ['deposit', 'transfer']) ? 'success' : 'danger' ?>">
                                            <?= ucfirst($trans['transaction_type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($trans['description'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($trans['payee'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($trans['reference'] ?? '-') ?></td>
                                    <td class="<?= in_array($trans['transaction_type'], ['deposit', 'transfer']) ? 'text-success' : 'text-danger' ?>">
                                        <?= in_array($trans['transaction_type'], ['deposit', 'transfer']) ? '+' : '-' ?>
                                        <?= format_currency($trans['amount'], $trans['currency'] ?? 'USD') ?>
                                    </td>
                                    <td>
                                        <?php if ($trans['cleared']): ?>
                                            <span class="badge bg-success">Cleared</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Uncleared</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$trans['cleared'] && has_permission('cash', 'update')): ?>
                                            <a href="#" class="btn btn-sm btn-outline-success" onclick="markCleared(<?= $trans['id'] ?>)">
                                                <i class="bi bi-check"></i> Mark Cleared
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No transactions found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function markCleared(transactionId) {
    // TODO: Implement AJAX call to mark transaction as cleared
    if (confirm('Mark this transaction as cleared?')) {
        // AJAX implementation needed
    }
}
</script>


