<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Bookings</h1>
        <div>
            <a href="<?= base_url('booking-reports') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-graph-up"></i> Reports
            </a>
            <a href="<?= base_url('bookings/calendar') ?>" class="btn btn-outline-info">
                <i class="bi bi-calendar-month"></i> Calendar View
            </a>
            <?php if (has_permission('bookings', 'create')): ?>
                <a href="<?= base_url('bookings/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New Booking
                </a>
            <?php endif; ?>
        </div>
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
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $selected_status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= $selected_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $selected_status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="completed" <?= $selected_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $selected_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($selected_date) ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Booking #</th>
                            <th>Facility</th>
                            <th>Customer</th>
                            <th>Date & Time</th>
                            <th>Duration</th>
                            <th>Total Amount</th>
                            <th>Payment Status</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bookings)): ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($booking['booking_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($booking['facility_name']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($booking['customer_name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($booking['customer_phone'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <?= date('M d, Y', strtotime($booking['booking_date'])) ?><br>
                                        <small class="text-muted">
                                            <?= date('h:i A', strtotime($booking['start_time'])) ?> - 
                                            <?= date('h:i A', strtotime($booking['end_time'])) ?>
                                        </small>
                                    </td>
                                    <td><?= number_format($booking['duration_hours'], 1) ?> hrs</td>
                                    <td><?= format_currency($booking['total_amount']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $booking['payment_status'] === 'paid' ? 'success' : ($booking['payment_status'] === 'partial' ? 'warning' : 'danger') ?>">
                                            <?= ucfirst($booking['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $booking['status'] === 'confirmed' ? 'success' : 
                                            ($booking['status'] === 'pending' ? 'warning' : 
                                            ($booking['status'] === 'completed' ? 'info' : 'secondary')) 
                                        ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No bookings found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

