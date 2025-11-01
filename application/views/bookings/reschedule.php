<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Reschedule Booking</h1>
        <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($booking): ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Current Booking Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Resource:</strong> <?= htmlspecialchars($booking['facility_name']) ?><br>
                                <strong>Current Date:</strong> <?= date('M d, Y', strtotime($booking['booking_date'])) ?><br>
                                <strong>Current Time:</strong> <?= date('h:i A', strtotime($booking['start_time'])) ?> - <?= date('h:i A', strtotime($booking['end_time'])) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">New Booking Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= base_url('bookings/reschedule/' . $booking['id']) ?>">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">New Date <span class="text-danger">*</span></label>
                                    <input type="date" name="booking_date" class="form-control" required
                                           min="<?= date('Y-m-d') ?>"
                                           value="<?= htmlspecialchars($booking['booking_date']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                    <input type="time" name="start_time" class="form-control" required
                                           value="<?= htmlspecialchars($booking['start_time']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Time <span class="text-danger">*</span></label>
                                    <input type="time" name="end_time" class="form-control" required
                                           value="<?= htmlspecialchars($booking['end_time']) ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reason for Reschedule</label>
                                <textarea name="reason" class="form-control" rows="3" 
                                          placeholder="Optional reason for rescheduling..."></textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-calendar-check"></i> Reschedule Booking
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Important Notes</h5>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>New date and time must be available</li>
                            <li>All modifications are logged for audit</li>
                            <li>Customer will be notified of changes</li>
                            <li>If price changes, booking total will be updated</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

