<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Bookings</h1>
        <div>
            <a href="<?= base_url('booking-reports') ?>" class="btn btn-primary">
                <i class="bi bi-graph-up"></i> Reports
            </a>
            <a href="<?= base_url('bookings/calendar') ?>" class="btn btn-primary">
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
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" id="filter-status" class="form-select">
                        <option value="all" <?= $selected_status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= $selected_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $selected_status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="completed" <?= $selected_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $selected_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="in_progress" <?= $selected_status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date</label>
                    <input type="date" name="date" id="filter-date" class="form-control" value="<?= htmlspecialchars($selected_date) ?>">
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel-fill me-1"></i> Apply Filter
                    </button>
                    <a href="<?= base_url('bookings') ?>" class="btn btn-outline-dark">
                        <i class="bi bi-x-circle me-1"></i> Clear
                    </a>
                </div>
                <?php if ($selected_status !== 'all' || !empty($selected_date)): ?>
                <div class="col-12">
                    <span class="text-muted small">
                        <i class="bi bi-filter-circle-fill text-primary me-1"></i>
                        Filtered by:
                        <?php if ($selected_status !== 'all'): ?>
                            <span class="badge bg-primary"><?= ucfirst($selected_status) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($selected_date)): ?>
                            <span class="badge bg-secondary"><?= date('M Y', strtotime($selected_date)) ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
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
                                        <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-sm btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (isSuperAdmin()): ?>
                                            <form method="POST" action="<?= base_url('bookings/delete/' . intval($booking['id'])) ?>" 
                                                  style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this booking? This will also delete associated invoices and transactions!');">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
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

