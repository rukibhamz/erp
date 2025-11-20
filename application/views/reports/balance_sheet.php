<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Balance Sheet</h1>
        <a href="<?= base_url('reports') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back to Reports
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('reports/balance-sheet') ?>" class="row g-3">
            <div class="col-md-4">
                <label for="as_of_date" class="form-label">As Of Date</label>
                <input type="date" class="form-control" id="as_of_date" name="as_of_date" value="<?= htmlspecialchars($as_of_date ?? date('Y-m-t')) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Balance Sheet -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Balance Sheet</h5>
        <small class="text-muted">As of: <?= format_date($as_of_date ?? '') ?></small>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold">Assets</h6>
                <?php if (!empty($assets)): ?>
                    <table class="table table-sm">
                        <tbody>
                            <?php foreach ($assets as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['account_code'] . ' - ' . $item['account_name']) ?></td>
                                    <td class="text-end"><?= format_currency($item['balance'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold table-primary">
                                <td>Total Assets</td>
                                <td class="text-end"><?= format_currency($total_assets ?? 0) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No asset accounts</p>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <h6 class="fw-bold">Liabilities</h6>
                <?php if (!empty($liabilities)): ?>
                    <table class="table table-sm">
                        <tbody>
                            <?php foreach ($liabilities as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['account_code'] . ' - ' . $item['account_name']) ?></td>
                                    <td class="text-end"><?= format_currency($item['balance'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total Liabilities</td>
                                <td class="text-end"><?= format_currency($total_liabilities ?? 0) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No liability accounts</p>
                <?php endif; ?>
                
                <h6 class="fw-bold mt-4">Equity</h6>
                <?php if (!empty($equity)): ?>
                    <table class="table table-sm">
                        <tbody>
                            <?php foreach ($equity as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['account_code'] . ' - ' . $item['account_name']) ?></td>
                                    <td class="text-end"><?= format_currency($item['balance'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total Equity</td>
                                <td class="text-end"><?= format_currency($total_equity ?? 0) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No equity accounts</p>
                <?php endif; ?>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-<?= abs(($total_assets ?? 0) - (($total_liabilities ?? 0) + ($total_equity ?? 0))) < 0.01 ? 'success' : 'warning' ?>">
                    <h5 class="mb-0">
                        Total Assets: <?= format_currency($total_assets ?? 0) ?><br>
                        Total Liabilities + Equity: <?= format_currency(($total_liabilities ?? 0) + ($total_equity ?? 0)) ?>
                    </h5>
                    <?php if (abs(($total_assets ?? 0) - (($total_liabilities ?? 0) + ($total_equity ?? 0))) < 0.01): ?>
                        <p class="mb-0 mt-2"><i class="bi bi-check-circle"></i> Balance sheet is balanced.</p>
                    <?php else: ?>
                        <p class="mb-0 mt-2"><i class="bi bi-exclamation-triangle"></i> Balance sheet is not balanced!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

