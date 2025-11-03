<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">POS Reports</h1>
</div>

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
                <label class="form-label">Terminal</label>
                <select name="terminal_id" class="form-select">
                    <option value="">All Terminals</option>
                    <?php foreach ($terminals as $term): ?>
                        <option value="<?= $term['id'] ?>" <?= $term['id'] == $terminal_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($term['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-dark w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Sales</h6>
                <h4 class="mb-0"><?= $summary['total_sales'] ?? 0 ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Revenue</h6>
                <h4 class="mb-0 text-primary"><?= format_currency($summary['total_revenue'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Cash Sales</h6>
                <h4 class="mb-0"><?= format_currency($summary['cash_sales'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Card Sales</h6>
                <h4 class="mb-0"><?= format_currency($summary['card_sales'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Sales List -->
<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Sales Transactions</h5>
    </div>
    <div class="card-body">
        <?php if (empty($sales)): ?>
            <p class="text-muted text-center py-4">No sales found for the selected period.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Sale #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($sale['sale_number']) ?></strong></td>
                                <td><?= date('M d, Y H:i', strtotime($sale['sale_date'])) ?></td>
                                <td><?= $sale['customer_id'] ? 'Customer #' . $sale['customer_id'] : 'Walk-in' ?></td>
                                <td><?= format_currency($sale['total_amount']) ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= ucfirst(str_replace('_', ' ', $sale['payment_method'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $sale['status'] === 'completed' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($sale['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url('pos/receipt/' . $sale['id']) ?>" class="btn btn-sm btn-outline-dark">
                                        <i class="bi bi-receipt"></i> Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>



