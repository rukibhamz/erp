<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Profit & Loss Statement</h1>
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
                    <label class="form-label">Comparison</label>
                    <select name="comparison" class="form-select">
                        <option value="none" <?= $comparison === 'none' ? 'selected' : '' ?>>No Comparison</option>
                        <option value="previous_period" <?= $comparison === 'previous_period' ? 'selected' : '' ?>>Previous Period</option>
                        <option value="previous_year" <?= $comparison === 'previous_year' ? 'selected' : '' ?>>Previous Year</option>
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
                <h4>Profit & Loss Statement</h4>
                <p class="text-muted">For the period <?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?></p>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60%">Account</th>
                            <th class="text-end" style="width: 20%">
                                <?= date('M Y', strtotime($start_date)) ?>
                                <?php if ($comparison_data): ?>
                                    / <?= date('M Y', strtotime($start_date . ' -1 month')) ?>
                                <?php endif; ?>
                            </th>
                            <?php if ($comparison_data): ?>
                                <th class="text-end" style="width: 20%">Change</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Revenue Section -->
                        <tr class="table-success">
                            <td colspan="<?= $comparison_data ? '3' : '2' ?>"><strong>REVENUE</strong></td>
                        </tr>
                        <?php foreach ($revenue['accounts'] as $item): ?>
                            <tr>
                                <td style="padding-left: 30px;"><?= htmlspecialchars($item['account']['account_name']) ?></td>
                                <td class="text-end"><?= format_currency($item['balance']) ?></td>
                                <?php if ($comparison_data): ?>
                                    <td class="text-end">
                                        <?php
                                        $prevAmount = 0;
                                        foreach ($comparison_data['revenue']['accounts'] as $prev) {
                                            if ($prev['account']['id'] == $item['account']['id']) {
                                                $prevAmount = $prev['balance'];
                                                break;
                                            }
                                        }
                                        $change = $item['balance'] - $prevAmount;
                                        $class = $change >= 0 ? 'text-success' : 'text-danger';
                                        echo '<span class="' . $class . '">' . format_currency($change) . '</span>';
                                        ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-success">
                            <td><strong>Total Revenue</strong></td>
                            <td class="text-end"><strong><?= format_currency($revenue['total']) ?></strong></td>
                            <?php if ($comparison_data): ?>
                                <td class="text-end">
                                    <?php
                                    $change = $revenue['total'] - $comparison_data['revenue']['total'];
                                    $class = $change >= 0 ? 'text-success' : 'text-danger';
                                    echo '<strong class="' . $class . '">' . format_currency($change) . '</strong>';
                                    ?>
                                </td>
                            <?php endif; ?>
                        </tr>

                        <!-- Expenses Section -->
                        <tr class="table-danger">
                            <td colspan="<?= $comparison_data ? '3' : '2' ?>"><strong>EXPENSES</strong></td>
                        </tr>
                        <?php foreach ($expenses['accounts'] as $item): ?>
                            <tr>
                                <td style="padding-left: 30px;"><?= htmlspecialchars($item['account']['account_name']) ?></td>
                                <td class="text-end"><?= format_currency($item['balance']) ?></td>
                                <?php if ($comparison_data): ?>
                                    <td class="text-end">
                                        <?php
                                        $prevAmount = 0;
                                        foreach ($comparison_data['expenses']['accounts'] as $prev) {
                                            if ($prev['account']['id'] == $item['account']['id']) {
                                                $prevAmount = $prev['balance'];
                                                break;
                                            }
                                        }
                                        $change = $item['balance'] - $prevAmount;
                                        $class = $change >= 0 ? 'text-danger' : 'text-success';
                                        echo '<span class="' . $class . '">' . format_currency($change) . '</span>';
                                        ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-danger">
                            <td><strong>Total Expenses</strong></td>
                            <td class="text-end"><strong><?= format_currency($expenses['total'], 'USD') ?></strong></td>
                            <?php if ($comparison_data): ?>
                                <td class="text-end">
                                    <?php
                                    $change = $expenses['total'] - $comparison_data['expenses']['total'];
                                    $class = $change >= 0 ? 'text-danger' : 'text-success';
                                    echo '<strong class="' . $class . '">' . format_currency($change) . '</strong>';
                                    ?>
                                </td>
                            <?php endif; ?>
                        </tr>

                        <!-- Net Income -->
                        <tr class="<?= $net_income >= 0 ? 'table-success' : 'table-danger' ?>">
                            <td><strong>NET INCOME</strong></td>
                            <td class="text-end">
                                <strong><?= format_currency($net_income) ?></strong>
                            </td>
                            <?php if ($comparison_data): ?>
                                <td class="text-end">
                                    <?php
                                    $change = $net_income - $comparison_data['net_income'];
                                    $class = $change >= 0 ? 'text-success' : 'text-danger';
                                    echo '<strong class="' . $class . '">' . format_currency($change) . '</strong>';
                                    ?>
                                </td>
                            <?php endif; ?>
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


