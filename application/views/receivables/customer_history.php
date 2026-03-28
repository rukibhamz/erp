<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0"><?= htmlspecialchars($page_title ?? 'Transaction History') ?></h1>
        <div class="btn-group">
            <a href="<?= base_url('receivables/customers/view/' . intval($customer['id'])) ?>" class="btn btn-primary">
                <i class="bi bi-person"></i> View Customer
            </a>
            <a href="<?= base_url('receivables/customers') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Entity Summary Card -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Customer Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Company Name:</strong><br>
                        <?= htmlspecialchars($customer['company_name'] ?? 'N/A') ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Customer Code:</strong><br>
                        <?= htmlspecialchars($customer['customer_code'] ?? 'N/A') ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Outstanding Balance:</strong><br>
                        <span class="fs-5 <?= ($outstanding ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format(floatval($outstanding ?? 0), 2) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Date Range Filter -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="GET" action="<?= base_url('receivables/customers/history/' . intval($customer['id'])) ?>" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" id="date_from" name="date_from" class="form-control"
                               value="<?= htmlspecialchars($date_from ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" id="date_to" name="date_to" class="form-control"
                               value="<?= htmlspecialchars($date_to ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Apply
                        </button>
                        <?php if (!empty($date_from) || !empty($date_to)): ?>
                            <a href="<?= base_url('receivables/customers/history/' . intval($customer['id'])) ?>" class="btn btn-secondary ms-2">
                                Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Invoices Table -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Invoices</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Invoice Date</th>
                                <th>Due Date</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No invoices found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $invoice): ?>
                                    <?php
                                    $statusBadges = [
                                        'draft'          => 'secondary',
                                        'sent'           => 'info',
                                        'paid'           => 'success',
                                        'partially_paid' => 'warning',
                                        'overdue'        => 'danger',
                                    ];
                                    $badgeColor = $statusBadges[$invoice['status'] ?? 'draft'] ?? 'secondary';
                                    $dueDate    = $invoice['due_date'] ?? '';
                                    $isOverdue  = $dueDate && strtotime($dueDate) < time() && ($invoice['status'] ?? '') !== 'paid';
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="<?= base_url('receivables/invoices/view/' . intval($invoice['id'])) ?>">
                                                <?= htmlspecialchars($invoice['invoice_number'] ?? 'N/A') ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($invoice['invoice_date'] ?? '') ?></td>
                                        <td>
                                            <span class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                                <?= htmlspecialchars($dueDate) ?>
                                            </span>
                                        </td>
                                        <td class="text-end"><?= number_format(floatval($invoice['total_amount'] ?? 0), 2) ?></td>
                                        <td class="text-end"><?= number_format(floatval($invoice['paid_amount'] ?? 0), 2) ?></td>
                                        <td class="text-end">
                                            <span class="<?= ($invoice['balance_amount'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                                                <?= number_format(floatval($invoice['balance_amount'] ?? 0), 2) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $badgeColor ?>">
                                                <?= ucfirst(str_replace('_', ' ', $invoice['status'] ?? 'draft')) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payments Table -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">Payments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Payment #</th>
                                <th>Payment Date</th>
                                <th class="text-end">Amount</th>
                                <th>Method</th>
                                <th>Applied To</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No payments found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($payment['payment_number'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($payment['payment_date'] ?? '') ?></td>
                                        <td class="text-end"><?= number_format(floatval($payment['amount'] ?? 0), 2) ?></td>
                                        <td><?= htmlspecialchars($payment['payment_method'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($payment['applied_to'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Payments Table -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">Booking Payments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Payment #</th>
                                <th>Booking #</th>
                                <th>Facility</th>
                                <th>Payment Date</th>
                                <th class="text-end">Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($booking_payments)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No booking payments found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($booking_payments as $bp): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($bp['payment_number'] ?? 'N/A') ?></td>
                                        <td>
                                            <a href="<?= base_url('bookings/view/' . intval($bp['id'])) ?>">
                                                <?= htmlspecialchars($bp['booking_number'] ?? 'N/A') ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($bp['facility_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($bp['payment_date'] ?? '') ?></td>
                                        <td class="text-end"><?= number_format(floatval($bp['amount'] ?? 0), 2) ?></td>
                                        <td><?= htmlspecialchars($bp['payment_method'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge bg-<?= ($bp['payment_status'] ?? '') === 'completed' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($bp['payment_status'] ?? 'unknown') ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
