<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Bank Reconciliation: <?= htmlspecialchars($cash_account['account_name'] ?? '') ?></h1>
        <a href="<?= base_url('banking') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Uncleared Transactions</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="reconcileForm">
<?php echo csrf_field(); ?>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Statement Date *</label>
                                <input type="date" name="statement_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Statement Balance *</label>
                                <input type="number" name="statement_balance" class="form-control" step="0.01" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Adjustments</label>
                                <input type="number" name="adjustments" class="form-control" step="0.01" value="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>Book Balance:</strong> <?= format_currency($book_balance, 'USD') ?>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll" onchange="toggleAll()"></th>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($uncleared_transactions)): ?>
                                        <?php foreach ($uncleared_transactions as $trans): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="cleared_transactions[]" value="<?= $trans['id'] ?>">
                                                </td>
                                                <td><?= date('M d, Y', strtotime($trans['transaction_date'])) ?></td>
                                                <td><?= htmlspecialchars($trans['description'] ?? '-') ?></td>
                                                <td><?= ucfirst($trans['transaction_type']) ?></td>
                                                <td class="<?= in_array($trans['transaction_type'], ['deposit', 'transfer']) ? 'text-success' : 'text-danger' ?>">
                                                    <?= in_array($trans['transaction_type'], ['deposit', 'transfer']) ? '+' : '-' ?>
                                                    <?= format_currency($trans['amount'], $trans['currency'] ?? 'USD') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No uncleared transactions found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?= base_url('banking') ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Complete Reconciliation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Reconciliation Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Book Balance:</strong><br>
                        <h5><?= format_currency($book_balance, 'USD') ?></h5>
                    </div>
                    <div class="mb-3">
                        <strong>Uncleared Transactions:</strong><br>
                        <span><?= count($uncleared_transactions) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="cleared_transactions[]"]');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}
</script>


