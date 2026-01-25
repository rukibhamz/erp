<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Bookings</h1>
        <a href="<?= base_url('locations/create-booking') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Booking
        </a>
    </div>
</div>

<!-- Location Management Navigation -->
<div class="Location-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('locations') ?>">
            <i class="bi bi-building"></i> Locations
        </a>
        <a class="nav-link" href="<?= base_url('spaces') ?>">
            <i class="bi bi-door-open"></i> Spaces
        </a>
        <a class="nav-link" href="<?= base_url('leases') ?>">
            <i class="bi bi-file-earmark-text"></i> Leases
        </a>
        <a class="nav-link" href="<?= base_url('tenants') ?>">
            <i class="bi bi-people"></i> Tenants
        </a>
        <a class="nav-link active" href="<?= base_url('locations/bookings') ?>">
            <i class="bi bi-calendar-check"></i> Bookings
        </a>
        <a class="nav-link" href="<?= base_url('rent-invoices') ?>">
            <i class="bi bi-receipt"></i> Rent Invoices
        </a>
    </nav>
</div>

<?php endif; ?>

<!-- DEBUG DATA -->
<div class="alert alert-info">
    <h4>Debug Data</h4>
    <pre><?= print_r($bookings, true) ?></pre>
</div>
<!-- END DEBUG -->

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="location_filter" class="form-label">Filter by Location</label>
                <select name="location_id" id="location_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc['id'] ?>" <?= ($selected_location_id == $loc['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loc['Location_name'] ?? $loc['property_name'] ?? 'N/A') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="status_filter" class="form-label">Filter by Status</label>
                <select name="status" id="status_filter" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $selected_status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="pending" <?= $selected_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= $selected_status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="cancelled" <?= $selected_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-4">
                <a href="<?= base_url('locations/booking-calendar') ?>" class="btn btn-info w-100">
                    <i class="bi bi-calendar-month"></i> View Calendar
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($bookings)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-calendar-check" style="font-size: 4rem; color: #dee2e6;"></i>
            <h5 class="mt-3 text-muted">No Bookings Found</h5>
            <p class="text-muted">Create your first booking to get started.</p>
            <a href="<?= base_url('locations/create-booking') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New Booking
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Booking #</th>
                            <th>Location</th>
                            <th>Space</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($booking['booking_number']) ?></strong></td>
                                <td><?= htmlspecialchars($booking['location_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($booking['space_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($booking['tenant_name'] ?? $booking['customer_name'] ?? 'N/A') ?></td>
                                <td><?= date('M d, Y', strtotime($booking['booking_date'])) ?></td>
                                <td>
                                    <?= date('g:i A', strtotime($booking['start_time'])) ?> - 
                                    <?= date('g:i A', strtotime($booking['end_time'])) ?>
                                </td>
                                <td><?= number_format($booking['duration_hours'], 2) ?> hrs</td>
                                <td><?= format_currency($booking['total_amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $booking['status'] === 'confirmed' ? 'success' : ($booking['status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                        <?= ucfirst($booking['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $booking['payment_status'] === 'paid' ? 'success' : ($booking['payment_status'] === 'partial' ? 'warning' : 'secondary') ?>">
                                        <?= ucfirst($booking['payment_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url('locations/view-booking/' . $booking['id']) ?>" class="btn btn-sm btn-primary">
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
<?php endif; ?>

