<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Reschedule Booking: <?= htmlspecialchars($booking['booking_number'] ?? '') ?></h1>
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

    <div class="row">
        <div class="col-lg-6">
            <!-- Current Booking Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Current Schedule</h5>
                </div>
                <div class="card-body">
                    <p><strong>Date:</strong> <?= date('M d, Y', strtotime($booking['booking_date'])) ?></p>
                    <p><strong>Time:</strong> <?= date('h:i A', strtotime($booking['start_time'])) ?> - <?= date('h:i A', strtotime($booking['end_time'])) ?></p>
                    <p class="mb-0"><strong>Duration:</strong> <?= number_format($booking['duration_hours'], 1) ?> hours</p>
                </div>
            </div>

            <!-- Reschedule Form -->
            <div class="card shadow-sm">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">New Schedule</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        
                        <div class="mb-3">
                            <label class="form-label">New Date <span class="text-danger">*</span></label>
                            <input type="date" name="booking_date" class="form-control" required 
                                   min="<?= date('Y-m-d') ?>"
                                   value="<?= $booking['booking_date'] ?>">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" required
                                       value="<?= $booking['start_time'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control" required
                                       value="<?= $booking['end_time'] ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason for Rescheduling</label>
                            <textarea name="reason" class="form-control" rows="3" 
                                      placeholder="Enter reason for rescheduling..."></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> The system will check availability for the new time slot before confirming.
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-warning">Reschedule Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
