<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Trial Balance</h1>
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
                    <label class="form-label">As of Date</label>
                    <input type="date" name="as_of_date" class="form-control" value="<?= htmlspecialchars($as_of_date) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account</label>
                    <select name="account_id" class="form-select">
                        <option value="">All Accounts</option>
                        <?php
                        // Accounts will be passed from controller
                        if (isset($accounts)):
                            foreach ($accounts as $acc):
                        ?>
                            <option value="<?= $acc['id'] ?>" <?= ($selected_account_id ?? '') == $acc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?>
                            </option>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Content -->
    <div class="card">
        <div class="card-body">
            <div class="text-center mb-4">
                <h4>Trial Balance</h4>
                <p class="text-muted">As of <?= date('M d, Y', strtotime($as_of_date)) ?></p>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%">Account Code</th>
                            <th style="width: 50%">Account Name</th>
                            <th class="text-end" style="width: 20%">Debit</th>
                            <th class="text-end" style="width: 20%">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($trial_balance)): ?>
                            <?php
                            $currentType = '';
                            foreach ($trial_balance as $item):
                                if ($currentType !== $item['account']['account_type']):
                                    $currentType = $item['account']['account_type'];
                            ?>
                                <tr class="table-secondary">
                                    <td colspan="4"><strong><?= strtoupper($currentType) ?></strong></td>
                                </tr>
                            <?php endif; ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['account']['account_code']) ?></td>
                                    <td><?= htmlspecialchars($item['account']['account_name']) ?></td>
                                    <td class="text-end">
                                        <?= $item['debit'] > 0 ? format_currency($item['debit'], 'USD') : '-' ?>
                                    </td>
                                    <td class="text-end">
                                        <?= $item['credit'] > 0 ? format_currency($item['credit'], 'USD') : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No data found for the selected period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-primary">
                        <tr>
                            <td colspan="2"><strong>TOTAL</strong></td>
                            <td class="text-end"><strong><?= format_currency($total_debits, 'USD') ?></strong></td>
                            <td class="text-end"><strong><?= format_currency($total_credits, 'USD') ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Balance Check -->
            <div class="alert alert-<?= abs($total_debits - $total_credits) < 0.01 ? 'success' : 'danger' ?> mt-4">
                <strong>Balance Check:</strong> 
                <?php if (abs($total_debits - $total_credits) < 0.01): ?>
                    Debits = Credits (Balanced ✓)
                <?php else: ?>
                    Debits ≠ Credits (Difference: <?= format_currency(abs($total_debits - $total_credits), 'USD') ?>)
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .card.mb-4 { display: none; }
    .card { border: none; box-shadow: none; }
}
</style>

<?php $this->load->view('layouts/footer'); ?>

