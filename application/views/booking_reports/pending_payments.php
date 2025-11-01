<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Pending Payments Report</h1>
        <div>
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="<?= base_url('booking-reports') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php
    $totalPending = 0;
    foreach ($pending_payments as $payment) {
        $totalPending += floatval($payment['balance_amount']);
    }
    ?>

    <!-- Summary -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Total Pending Amount</h5>
                    <h3 class="text-warning"><?= format_currency($totalPending) ?></h3>
                </div>
                <div class="col-md-6">
                    <h5>Number of Bookings</h5>
                    <h3><?= count($pending_payments) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Booking #</th>
                            <th>Facility</th>
                            <th>Customer</th>
                            <th>Booking Date</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-end">Paid Amount</th>
                            <th class="text-end">Balance</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pending_payments)): ?>
                            <?php foreach ($pending_payments as $booking): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($booking['booking_number']) ?></strong></td>
                                    <td>
                                        <?php
                                        try {
                                            $facility = $this->loadModel('Facility_model')->getById($booking['facility_id']);
                                            echo htmlspecialchars($facility['facility_name'] ?? '-');
                                        } catch (Exception $e) {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($booking['customer_name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($booking['customer_phone'] ?? '') ?></small>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($booking['booking_date'])) ?></td>
                                    <td class="text-end"><?= format_currency($booking['total_amount']) ?></td>
                                    <td class="text-end text-success"><?= format_currency($booking['paid_amount']) ?></td>
                                    <td class="text-end text-danger"><strong><?= format_currency($booking['balance_amount']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?= $booking['payment_status'] === 'partial' ? 'warning' : 'danger' ?>">
                                            <?= ucfirst($booking['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No pending payments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-primary">
                        <tr>
                            <th colspan="6">Total Pending</th>
                            <th class="text-end"><?= format_currency($totalPending) ?></th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
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

