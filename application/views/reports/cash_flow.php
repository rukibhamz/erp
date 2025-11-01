<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Cash Flow Statement</h1>
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
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Method</label>
                    <select name="method" class="form-select">
                        <option value="indirect" <?= $method === 'indirect' ? 'selected' : '' ?>>Indirect Method</option>
                        <option value="direct" <?= $method === 'direct' ? 'selected' : '' ?>>Direct Method</option>
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
                <h4>Cash Flow Statement</h4>
                <p class="text-muted">For the period <?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?></p>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <!-- Operating Activities -->
                        <tr class="table-primary">
                            <td colspan="2"><strong>CASH FLOW FROM OPERATING ACTIVITIES</strong></td>
                        </tr>
                        <?php if ($method === 'indirect'): ?>
                            <tr>
                                <td style="padding-left: 30px;">Net Income</td>
                                <td class="text-end"><?= format_currency($net_income) ?></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Adjustments for non-cash items</td>
                                <td class="text-end">-</td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td><strong>Net Cash from Operating Activities</strong></td>
                            <td class="text-end"><strong><?= format_currency($cash_flow_operating) ?></strong></td>
                        </tr>

                        <!-- Investing Activities -->
                        <tr class="table-info">
                            <td colspan="2"><strong>CASH FLOW FROM INVESTING ACTIVITIES</strong></td>
                        </tr>
                        <?php foreach ($investing['accounts'] as $item): ?>
                            <tr>
                                <td style="padding-left: 30px;"><?= htmlspecialchars($item['account']['account_name']) ?></td>
                                <td class="text-end"><?= format_currency($item['balance']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><strong>Net Cash from Investing Activities</strong></td>
                            <td class="text-end"><strong><?= format_currency($cash_flow_investing) ?></strong></td>
                        </tr>

                        <!-- Financing Activities -->
                        <tr class="table-warning">
                            <td colspan="2"><strong>CASH FLOW FROM FINANCING ACTIVITIES</strong></td>
                        </tr>
                        <?php foreach ($financing['liabilities']['accounts'] as $item): ?>
                            <tr>
                                <td style="padding-left: 30px;"><?= htmlspecialchars($item['account']['account_name']) ?></td>
                                <td class="text-end"><?= format_currency($item['balance']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php foreach ($financing['equity']['accounts'] as $item): ?>
                            <tr>
                                <td style="padding-left: 30px;"><?= htmlspecialchars($item['account']['account_name']) ?></td>
                                <td class="text-end"><?= format_currency($item['balance']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><strong>Net Cash from Financing Activities</strong></td>
                            <td class="text-end"><strong><?= format_currency($cash_flow_financing) ?></strong></td>
                        </tr>

                        <!-- Net Cash Flow -->
                        <tr class="table-success">
                            <td><strong>NET INCREASE (DECREASE) IN CASH</strong></td>
                            <td class="text-end"><strong><?= format_currency($net_cash_flow) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
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

