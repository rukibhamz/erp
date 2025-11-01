<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Banking & Reconciliation</h1>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if (!empty($cash_accounts)): ?>
            <?php foreach ($cash_accounts as $account): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($account['account_name']) ?></h5>
                            <p class="text-muted mb-2"><?= htmlspecialchars($account['account_code']) ?></p>
                            <h3 class="mb-3"><?= format_currency($account['current_balance'], $account['currency'] ?? 'USD') ?></h3>
                            <div class="d-flex gap-2">
                                <a href="<?= base_url('banking/transactions/' . $account['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-list"></i> Transactions
                                </a>
                                <a href="<?= base_url('banking/reconcile/' . $account['id']) ?>" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-check-circle"></i> Reconcile
                                </a>
                                <a href="<?= base_url('banking/reconciliations/' . $account['id']) ?>" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-clock-history"></i> History
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    No bank accounts found. Please create cash accounts first.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $this->load->view('layouts/footer'); ?>

