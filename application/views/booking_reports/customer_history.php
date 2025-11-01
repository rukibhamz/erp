<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Customer Booking History</h1>
        <a href="<?= base_url('booking-reports') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Customer Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($customer_email) ?>" placeholder="Enter customer email">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Customer Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customer_phone) ?>" placeholder="Enter customer phone">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
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
                            <th>Amount</th>
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
                                        <small class="text-muted"><?= htmlspecialchars($booking['customer_email'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <?= date('M d, Y', strtotime($booking['booking_date'])) ?><br>
                                        <small class="text-muted">
                                            <?= date('h:i A', strtotime($booking['start_time'])) ?> - 
                                            <?= date('h:i A', strtotime($booking['end_time'])) ?>
                                        </small>
                                    </td>
                                    <td><?= format_currency($booking['total_amount']) ?></td>
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
                                        <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <?= ($customer_email || $customer_phone) ? 'No bookings found for this customer.' : 'Enter email or phone to search for customer bookings.' ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

