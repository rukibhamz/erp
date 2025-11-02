<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Tax Payments</h1>
        <?php if (hasPermission('tax', 'create')): ?>
            <a href="<?= base_url('tax/payments/create') ?>" class="btn btn-dark">
                <i class="bi bi-plus-circle"></i> Record Payment
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tax Type</label>
                <select name="tax_type" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $selected_tax_type === 'all' ? 'selected' : '' ?>>All Types</option>
                    <?php foreach ($tax_types as $type): ?>
                        <option value="<?= htmlspecialchars($type['code']) ?>" <?= $selected_tax_type === $type['code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Period Start</label>
                <input type="date" name="period_start" class="form-control" value="<?= htmlspecialchars($period_start) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Period End</label>
                <input type="date" name="period_end" class="form-control" value="<?= htmlspecialchars($period_end) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-dark w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($payments)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-credit-card" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No tax payments found.</p>
            <?php if (hasPermission('tax', 'create')): ?>
                <a href="<?= base_url('tax/payments/create') ?>" class="btn btn-dark">
                    <i class="bi bi-plus-circle"></i> Record First Payment
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Payment Date</th>
                            <th>Tax Type</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Reference</th>
                            <th>Period Covered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= htmlspecialchars($payment['tax_type']) ?>
                                    </span>
                                </td>
                                <td><strong><?= format_currency($payment['amount'] ?? 0) ?></strong></td>
                                <td><?= ucfirst(str_replace('_', ' ', $payment['payment_method'] ?? 'N/A')) ?></td>
                                <td><?= htmlspecialchars($payment['reference'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($payment['period_covered'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($payment['receipt_url'])): ?>
                                        <a href="<?= base_url($payment['receipt_url']) ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="View Receipt">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-primary">
                        <tr>
                            <td colspan="2"><strong>Total</strong></td>
                            <td><strong><?= format_currency(array_sum(array_column($payments, 'amount'))) ?></strong></td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
