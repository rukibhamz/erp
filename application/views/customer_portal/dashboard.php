<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Dashboard</h1>
        <a href="<?= base_url('booking-wizard') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Booking
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Bookings</h6>
                    <h2 class="mb-0"><?= $stats['total_bookings'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Pending Bookings</h6>
                    <h2 class="mb-0 text-warning"><?= $stats['pending_bookings'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Spent</h6>
                    <h2 class="mb-0 text-success"><?= format_currency($stats['total_spent']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Outstanding</h6>
                    <h2 class="mb-0 <?= $stats['outstanding_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= format_currency($stats['outstanding_balance']) ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Upcoming Bookings -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Upcoming Bookings</h5>
                    <a href="<?= base_url('customer-portal/bookings') ?>" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcoming_bookings)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($upcoming_bookings, 0, 5) as $booking): ?>
                                <a href="<?= base_url('customer-portal/booking/' . $booking['id']) ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($booking['facility_name']) ?></h6>
                                            <p class="mb-1 small text-muted">
                                                <?= date('M d, Y', strtotime($booking['booking_date'])) ?> 
                                                <?= date('g:i A', strtotime($booking['start_time'])) ?> - 
                                                <?= date('g:i A', strtotime($booking['end_time'])) ?>
                                            </p>
                                            <span class="badge bg-<?= 
                                                $booking['status'] === 'confirmed' ? 'success' : 
                                                ($booking['status'] === 'pending' ? 'warning' : 'info')
                                            ?>">
                                                <?= ucfirst($booking['status']) ?>
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <strong><?= format_currency($booking['total_amount']) ?></strong>
                                            <?php if ($booking['balance_amount'] > 0): ?>
                                                <br><small class="text-danger">Balance: <?= format_currency($booking['balance_amount']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No upcoming bookings</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Bookings</h5>
                    <a href="<?= base_url('customer-portal/bookings') ?>" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($past_bookings)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($past_bookings, 0, 5) as $booking): ?>
                                <a href="<?= base_url('customer-portal/booking/' . $booking['id']) ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($booking['facility_name']) ?></h6>
                                            <p class="mb-1 small text-muted">
                                                <?= date('M d, Y', strtotime($booking['booking_date'])) ?>
                                            </p>
                                            <span class="badge bg-<?= 
                                                $booking['status'] === 'completed' ? 'success' : 
                                                ($booking['status'] === 'cancelled' ? 'danger' : 'secondary')
                                            ?>">
                                                <?= ucfirst($booking['status']) ?>
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <strong><?= format_currency($booking['total_amount']) ?></strong>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No past bookings</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

