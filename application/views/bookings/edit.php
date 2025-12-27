<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Booking: <?= htmlspecialchars($booking['booking_number'] ?? '') ?></h1>
        <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Edit Booking Details</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <?php echo csrf_field(); ?>
                
                <!-- Booking Info (Read-only) -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Booking Number</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($booking['booking_number'] ?? '') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" value="<?= date('M d, Y', strtotime($booking['booking_date'])) ?>" readonly>
                        <small class="text-muted">Use Reschedule to change date/time</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Time</label>
                        <input type="text" class="form-control" value="<?= date('h:i A', strtotime($booking['start_time'])) ?> - <?= date('h:i A', strtotime($booking['end_time'])) ?>" readonly>
                    </div>
                </div>

                <!-- Customer Information -->
                <h5 class="mb-3">Customer Information</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control" 
                                   value="<?= htmlspecialchars($booking['customer_name'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="customer_email" class="form-control" 
                                   value="<?= htmlspecialchars($booking['customer_email'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="customer_phone" class="form-control" 
                                   value="<?= htmlspecialchars($booking['customer_phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Number of Guests</label>
                            <input type="number" name="number_of_guests" class="form-control" min="0"
                                   value="<?= intval($booking['number_of_guests'] ?? 0) ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Customer Address</label>
                    <textarea name="customer_address" class="form-control" rows="2"><?= htmlspecialchars($booking['customer_address'] ?? '') ?></textarea>
                </div>

                <!-- Booking Notes -->
                <h5 class="mb-3 mt-4">Additional Information</h5>
                <div class="mb-3">
                    <label class="form-label">Booking Notes</label>
                    <textarea name="booking_notes" class="form-control" rows="2"><?= htmlspecialchars($booking['booking_notes'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Special Requests</label>
                    <textarea name="special_requests" class="form-control" rows="2"><?= htmlspecialchars($booking['special_requests'] ?? '') ?></textarea>
                </div>

                <!-- Pricing -->
                <h5 class="mb-3 mt-4">Pricing</h5>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Base Amount</label>
                        <input type="text" class="form-control" value="<?= format_currency($booking['base_amount'] ?? 0) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Discount Amount</label>
                        <input type="number" name="discount_amount" class="form-control" step="0.01" min="0"
                               value="<?= floatval($booking['discount_amount'] ?? 0) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Amount</label>
                        <input type="text" class="form-control" value="<?= format_currency($booking['total_amount'] ?? 0) ?>" readonly>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
