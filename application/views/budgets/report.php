<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Budget vs Actual Report</h1>
        <a href="<?= base_url('budgets') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Financial Year</label>
                    <select name="financial_year_id" class="form-select">
                        <option value="">Select Year</option>
                        <?php foreach ($financial_years as $year): ?>
                            <option value="<?= $year['id'] ?>" <?= ($selected_year_id ?? '') == $year['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year['year_name'] ?? date('Y', strtotime($year['start_date']))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-select">
                        <?php
                        $months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
                                   7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
                        foreach ($months as $num => $name):
                        ?>
                            <option value="<?= $num ?>" <?= ($selected_month ?? date('n')) == $num ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-control" value="<?= $selected_year ?? date('Y') ?>" min="2000" max="2099">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Generate</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Budget</th>
                            <th>Actual</th>
                            <th>Variance</th>
                            <th>Variance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($budgets)): ?>
                            <?php foreach ($budgets as $budget): ?>
                                <?php
                                $variance = $budget['variance'] ?? ['budget' => 0, 'actual' => 0, 'variance' => 0, 'variance_percent' => 0];
                                $varianceClass = $variance['variance'] < 0 ? 'text-danger' : ($variance['variance'] > 0 ? 'text-success' : '');
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($budget['account_name']) ?></td>
                                    <td><?= format_currency($variance['budget'], 'USD') ?></td>
                                    <td><?= format_currency($variance['actual'], 'USD') ?></td>
                                    <td class="<?= $varianceClass ?>">
                                        <?= format_currency($variance['variance'], 'USD') ?>
                                    </td>
                                    <td class="<?= $varianceClass ?>">
                                        <?= number_format($variance['variance_percent'], 2) ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No budget data found for the selected period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


