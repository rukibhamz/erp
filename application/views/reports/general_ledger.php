<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">General Ledger</h1>
        <div>
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="<?= base_url('reports') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Account *</label>
                    <select name="account_id" class="form-select" required onchange="this.form.submit()">
                        <option value="">Select Account</option>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>" <?= ($selected_account_id ?? '') == $acc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Generate</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($account): ?>
        <!-- Report Content -->
        <div class="card">
            <div class="card-body">
                <div class="mb-4">
                    <h5>Account: <?= htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']) ?></h5>
                    <p class="text-muted mb-0">
                        Period: <?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?>
                    </p>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Transaction #</th>
                                <th>Description</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($transactions)): ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($transaction['transaction_date'])) ?></td>
                                        <td><?= htmlspecialchars($transaction['transaction_number'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($transaction['description'] ?? '-') ?></td>
                                        <td class="text-end">
                                            <?= $transaction['debit'] > 0 ? format_currency($transaction['debit']) : '-' ?>
                                        </td>
                                        <td class="text-end">
                                            <?= $transaction['credit'] > 0 ? format_currency($transaction['credit']) : '-' ?>
                                        </td>
                                        <td class="text-end">
                                            <strong><?= format_currency($transaction['running_balance']) ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No transactions found for the selected period.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            Please select an account to view the general ledger.
        </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .btn, .card.mb-4 { display: none; }
    .card { border: none; box-shadow: none; }
}
</style>

<?php $this->load->view('layouts/footer'); ?>

