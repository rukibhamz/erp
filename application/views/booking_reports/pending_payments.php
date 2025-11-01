<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Pending Payments Report</h1>
        <a href="<?= base_url('booking-reports') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= base_url('booking-reports/pending-payments') ?>" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Total Outstanding</h6>
                    <h2><?= format_currency($total_outstanding) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Overdue Payments</h6>
                    <h2><?= format_currency($total_overdue) ?></h2>
                    <small><?= count($overdue_payments) ?> booking(s)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Payments -->
    <?php if (!empty($overdue_payments)): ?>
        <div class="card mb-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Overdue Payments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Booking #</th>
                                <th>Customer</th>
                                <th>Facility</th>
                                <th>Booking Date</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Days Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overdue_payments as $booking): ?>
                                <?php 
                                $daysOverdue = floor((time() - strtotime($booking['booking_date'])) / 86400);
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($booking['booking_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($booking['facility_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($booking['booking_date'])) ?></td>
                                    <td class="text-end"><?= format_currency($booking['total_amount']) ?></td>
                                    <td class="text-end"><?= format_currency($booking['paid_amount']) ?></td>
                                    <td class="text-end"><strong class="text-danger"><?= format_currency($booking['balance_amount']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-danger"><?= $daysOverdue ?> days</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- All Pending Payments -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Pending Payments</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($pending_payments)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Booking #</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Facility</th>
                                <th>Booking Date</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_payments as $booking): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($booking['booking_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($booking['customer_email'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($booking['facility_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($booking['booking_date'])) ?></td>
                                    <td class="text-end"><?= format_currency($booking['total_amount']) ?></td>
                                    <td class="text-end"><?= format_currency($booking['paid_amount']) ?></td>
                                    <td class="text-end"><strong class="text-warning"><?= format_currency($booking['balance_amount']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $booking['status'] === 'confirmed' ? 'success' : 'warning'
                                        ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="6" class="text-end">Total Outstanding:</td>
                                <td class="text-end"><?= format_currency($total_outstanding) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> No pending payments found!
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
