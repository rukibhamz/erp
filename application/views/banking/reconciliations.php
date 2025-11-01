<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Reconciliation History: <?= htmlspecialchars($cash_account['account_name'] ?? '') ?></h1>
        <div>
            <a href="<?= base_url('banking/reconcile/' . ($cash_account['id'] ?? '')) ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New Reconciliation
            </a>
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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Reconciliation Date</th>
                            <th>Statement Balance</th>
                            <th>Book Balance</th>
                            <th>Cleared Transactions</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reconciliations)): ?>
                            <?php foreach ($reconciliations as $recon): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($recon['reconciliation_date'])) ?></td>
                                    <td><?= format_currency($recon['bank_statement_balance'] ?? 0, 'USD') ?></td>
                                    <td><?= format_currency($recon['opening_balance'] ?? 0, 'USD') ?></td>
                                    <td><?= $recon['cleared_transactions_count'] ?? 0 ?></td>
                                    <td>
                                        <span class="badge bg-<?= $recon['status'] === 'completed' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($recon['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No reconciliation history found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('layouts/footer'); ?>

