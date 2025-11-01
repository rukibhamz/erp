<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Cancel Booking</h1>
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
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Cancel Booking: <?= htmlspecialchars($booking['booking_number']) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>Warning:</strong> This action cannot be undone. All accounting entries will be reversed if booking was confirmed.
                        </div>

                        <div class="mb-4">
                            <h6>Booking Details:</h6>
                            <ul>
                                <li><strong>Resource:</strong> <?= htmlspecialchars($booking['facility_name']) ?></li>
                                <li><strong>Date:</strong> <?= date('M d, Y', strtotime($booking['booking_date'])) ?></li>
                                <li><strong>Time:</strong> <?= date('h:i A', strtotime($booking['start_time'])) ?> - <?= date('h:i A', strtotime($booking['end_time'])) ?></li>
                                <li><strong>Customer:</strong> <?= htmlspecialchars($booking['customer_name']) ?></li>
                                <li><strong>Total Amount:</strong> <?= format_currency($booking['total_amount']) ?></li>
                                <li><strong>Paid Amount:</strong> <?= format_currency($booking['paid_amount']) ?></li>
                            </ul>
                        </div>

                        <?php if ($cancellation_policy): ?>
                            <div class="mb-4">
                                <h6>Cancellation Policy:</h6>
                                <p><?= htmlspecialchars($cancellation_policy['description'] ?? '') ?></p>
                                <?php 
                                $rules = json_decode($cancellation_policy['rules'] ?? '[]', true);
                                if ($rules): ?>
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Days Before</th>
                                                <th>Refund %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rules as $rule): ?>
                                                <tr>
                                                    <td><?= $rule['days_before'] ?? 0 ?>+ days</td>
                                                    <td><?= $rule['refund_percentage'] ?? 0 ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>

                            <?php if ($potential_refund > 0): ?>
                                <div class="alert alert-info">
                                    <strong>Potential Refund:</strong> <?= format_currency($potential_refund) ?>
                                    <br><small>Based on cancellation policy and timing</small>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <form method="POST" action="<?= base_url('bookings/cancel/' . $booking['id']) ?>" 
                              onsubmit="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.');">
                            <div class="mb-3">
                                <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                                <textarea name="cancellation_reason" class="form-control" rows="4" required
                                          placeholder="Please provide a reason for cancellation..."></textarea>
                            </div>

                            <?php if ($cancellation_policy && $potential_refund > 0): ?>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="apply_refund" id="apply_refund" value="1" checked>
                                        <label class="form-check-label" for="apply_refund">
                                            Apply refund of <?= format_currency($potential_refund) ?> based on cancellation policy
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between">
                                <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-outline-secondary">
                                    Keep Booking
                                </a>
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-x-circle"></i> Confirm Cancellation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">What Happens When You Cancel</h5>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Booking status will be set to "Cancelled"</li>
                            <li>All accounting entries will be reversed</li>
                            <li>Resource slots will be released</li>
                            <?php if ($potential_refund > 0): ?>
                                <li>Refund will be processed if selected</li>
                            <?php endif; ?>
                            <li>Customer will be notified</li>
                            <li>Modification will be logged for audit</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

