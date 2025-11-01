<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">My Bookings</h1>
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

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="<?= base_url('customer-portal/bookings') ?>" 
                   class="btn btn-<?= $selected_status === null ? 'primary' : 'outline-primary' ?>">
                    All
                </a>
                <a href="<?= base_url('customer-portal/bookings/pending') ?>" 
                   class="btn btn-<?= $selected_status === 'pending' ? 'primary' : 'outline-primary' ?>">
                    Pending
                </a>
                <a href="<?= base_url('customer-portal/bookings/confirmed') ?>" 
                   class="btn btn-<?= $selected_status === 'confirmed' ? 'primary' : 'outline-primary' ?>">
                    Confirmed
                </a>
                <a href="<?= base_url('customer-portal/bookings/completed') ?>" 
                   class="btn btn-<?= $selected_status === 'completed' ? 'primary' : 'outline-primary' ?>">
                    Completed
                </a>
                <a href="<?= base_url('customer-portal/bookings/cancelled') ?>" 
                   class="btn btn-<?= $selected_status === 'cancelled' ? 'primary' : 'outline-primary' ?>">
                    Cancelled
                </a>
            </div>
        </div>
    </div>

    <!-- Bookings List -->
    <?php if (!empty($bookings)): ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Booking #</th>
                                <th>Resource</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($booking['booking_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($booking['facility_name']) ?></td>
                                    <td>
                                        <?= date('M d, Y', strtotime($booking['booking_date'])) ?><br>
                                        <small class="text-muted">
                                            <?= date('g:i A', strtotime($booking['start_time'])) ?> - 
                                            <?= date('g:i A', strtotime($booking['end_time'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $booking['status'] === 'confirmed' ? 'success' : 
                                            ($booking['status'] === 'pending' ? 'warning' : 
                                            ($booking['status'] === 'completed' ? 'info' : 
                                            ($booking['status'] === 'cancelled' ? 'danger' : 'secondary')))
                                        ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= format_currency($booking['total_amount']) ?></td>
                                    <td>
                                        <span class="<?= $booking['balance_amount'] > 0 ? 'text-danger' : 'text-success' ?>">
                                            <?= format_currency($booking['balance_amount']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('customer-portal/booking/' . $booking['id']) ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar-x" style="font-size: 3rem; color: #ccc;"></i>
                <h5 class="mt-3">No bookings found</h5>
                <p class="text-muted">Start by creating a new booking</p>
                <a href="<?= base_url('booking-wizard') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New Booking
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

