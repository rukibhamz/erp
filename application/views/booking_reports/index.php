<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Booking Reports</h1>
        <a href="<?= base_url('bookings') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Bookings
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-cash-coin display-4 text-success mb-3"></i>
                    <h5>Revenue Report</h5>
                    <p class="text-muted">Revenue by facility</p>
                    <a href="<?= base_url('booking-reports/revenue') ?>" class="btn btn-outline-success">
                        View Report
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-bar-chart display-4 text-info mb-3"></i>
                    <h5>Utilization Report</h5>
                    <p class="text-muted">Facility usage statistics</p>
                    <a href="<?= base_url('booking-reports/utilization') ?>" class="btn btn-outline-info">
                        View Report
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-person-history display-4 text-primary mb-3"></i>
                    <h5>Customer History</h5>
                    <p class="text-muted">Booking history by customer</p>
                    <a href="<?= base_url('booking-reports/customer-history') ?>" class="btn btn-outline-primary">
                        View Report
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-clock-history display-4 text-warning mb-3"></i>
                    <h5>Pending Payments</h5>
                    <p class="text-muted">Outstanding payment balances</p>
                    <a href="<?= base_url('booking-reports/pending-payments') ?>" class="btn btn-outline-warning">
                        View Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

