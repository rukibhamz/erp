<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Receivables Payments</h1>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">All Payments</h5>
        <a href="<?= base_url('receivables/payments/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Payment
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($payments)): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Payment Number</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= htmlspecialchars($payment['payment_number'] ?? '') ?></td>
                        <td><?= !empty($payment['payment_date']) ? date('M d, Y', strtotime($payment['payment_date'])) : '' ?></td>
                        <td><?= htmlspecialchars($payment['customer_name'] ?? 'N/A') ?></td>
                        <td><?= number_format($payment['amount'] ?? 0, 2) ?></td>
                        <td><?= ucfirst(htmlspecialchars($payment['payment_method'] ?? '')) ?></td>
                        <td>
                            <span class="badge bg-<?= ($payment['status'] ?? '') === 'posted' ? 'success' : 'secondary' ?>">
                                <?= ucfirst(htmlspecialchars($payment['status'] ?? '')) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= base_url('receivables/payments/view/' . ($payment['id'] ?? '')) ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No payments found.
        </div>
        <?php endif; ?>
    </div>
</div>

