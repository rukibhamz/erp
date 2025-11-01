<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Balance Sheet</h1>
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
                <div class="col-md-3">
                    <label class="form-label">As of Date</label>
                    <input type="date" name="as_of_date" class="form-control" value="<?= htmlspecialchars($as_of_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Comparison</label>
                    <select name="comparison" class="form-select">
                        <option value="none" <?= $comparison === 'none' ? 'selected' : '' ?>>No Comparison</option>
                        <option value="previous_period" <?= $comparison === 'previous_period' ? 'selected' : '' ?>>Previous Period</option>
                    </select>
                </div>
                <div class="col-md-3">
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
                <h4>Balance Sheet</h4>
                <p class="text-muted">As of <?= date('M d, Y', strtotime($as_of_date)) ?></p>
            </div>

            <div class="row">
                <!-- Assets -->
                <div class="col-md-6">
                    <h5 class="mb-3">ASSETS</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <?php foreach ($assets['accounts'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['account']['account_name']) ?></td>
                                        <td class="text-end"><?= format_currency($item['balance']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-primary">
                                    <td><strong>Total Assets</strong></td>
                                    <td class="text-end"><strong><?= format_currency($total_assets) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Liabilities & Equity -->
                <div class="col-md-6">
                    <h5 class="mb-3">LIABILITIES & EQUITY</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <!-- Liabilities -->
                                <tr>
                                    <td colspan="2"><strong>LIABILITIES</strong></td>
                                </tr>
                                <?php foreach ($liabilities['accounts'] as $item): ?>
                                    <tr>
                                        <td style="padding-left: 30px;"><?= htmlspecialchars($item['account']['account_name']) ?></td>
                                        <td class="text-end"><?= format_currency($item['balance']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td><strong>Total Liabilities</strong></td>
                                    <td class="text-end"><strong><?= format_currency($liabilities['total']) ?></strong></td>
                                </tr>

                                <!-- Equity -->
                                <tr>
                                    <td colspan="2"><strong>EQUITY</strong></td>
                                </tr>
                                <?php foreach ($equity['accounts'] as $item): ?>
                                    <tr>
                                        <td style="padding-left: 30px;"><?= htmlspecialchars($item['account']['account_name']) ?></td>
                                        <td class="text-end"><?= format_currency($item['balance']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td>Retained Earnings</td>
                                    <td class="text-end"><?= format_currency($retained_earnings) ?></td>
                                </tr>
                                <tr class="table-primary">
                                    <td><strong>Total Liabilities & Equity</strong></td>
                                    <td class="text-end"><strong><?= format_currency($total_liabilities_equity) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Balance Check -->
            <div class="alert alert-<?= abs($total_assets - $total_liabilities_equity) < 0.01 ? 'success' : 'warning' ?> mt-4">
                <strong>Balance Check:</strong> 
                <?php if (abs($total_assets - $total_liabilities_equity) < 0.01): ?>
                    Assets = Liabilities + Equity (Balanced ✓)
                <?php else: ?>
                    Assets ≠ Liabilities + Equity (Difference: <?= format_currency(abs($total_assets - $total_liabilities_equity)) ?>)
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

