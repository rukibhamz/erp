<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                </div>
                <h1 class="display-5 fw-bold mb-3">Booking Confirmed!</h1>
                <p class="lead text-muted">Your booking has been successfully created</p>
                <div class="alert alert-info">
                    <strong>Booking Number:</strong> <?= htmlspecialchars($booking['booking_number'] ?? '') ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Booking Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Resource:</strong><br>
                            <?= htmlspecialchars($booking['facility_name'] ?? '') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Date:</strong><br>
                            <?= date('F j, Y', strtotime($booking['booking_date'] ?? '')) ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Time:</strong><br>
                            <?= date('g:i A', strtotime($booking['start_time'] ?? '')) ?> - 
                            <?= date('g:i A', strtotime($booking['end_time'] ?? '')) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Duration:</strong><br>
                            <?= $booking['duration_hours'] ?? 0 ?> hour(s)
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Customer:</strong><br>
                            <?= htmlspecialchars($booking['customer_name'] ?? '') ?><br>
                            <?= htmlspecialchars($booking['customer_email'] ?? '') ?><br>
                            <?= htmlspecialchars($booking['customer_phone'] ?? '') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Guests:</strong><br>
                            <?= $booking['number_of_guests'] ?? 1 ?>
                        </div>
                    </div>
                    <?php if (!empty($booking['special_requests'])): ?>
                        <div class="mb-3">
                            <strong>Special Requests:</strong><br>
                            <?= nl2br(htmlspecialchars($booking['special_requests'] ?? '')) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($addons)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Add-ons</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($addons as $addon): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($addon['name']) ?></td>
                                        <td><?= $addon['quantity'] ?></td>
                                        <td class="text-end"><?= format_currency($addon['total_price']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Payment Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <strong><?= format_currency($booking['subtotal'] ?? 0) ?></strong>
                    </div>
                    <?php if (($booking['discount_amount'] ?? 0) > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Discount:</span>
                            <strong>-<?= format_currency($booking['discount_amount'] ?? 0) ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if (($booking['security_deposit'] ?? 0) > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Security Deposit:</span>
                            <strong><?= format_currency($booking['security_deposit'] ?? 0) ?></strong>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong class="h5">Total Amount:</strong>
                        <strong class="h5 text-primary"><?= format_currency($booking['total_amount'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Paid Amount:</span>
                        <strong><?= format_currency($booking['paid_amount'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Balance:</span>
                        <strong><?= format_currency($booking['balance_amount'] ?? 0) ?></strong>
                    </div>
                </div>
            </div>

            <?php if (!empty($payment_schedule)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Payment Schedule</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Payment</th>
                                    <th>Due Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payment_schedule as $schedule): ?>
                                    <tr>
                                        <td>Payment #<?= $schedule['payment_number'] ?></td>
                                        <td><?= date('M j, Y', strtotime($schedule['due_date'])) ?></td>
                                        <td><?= format_currency($schedule['amount']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $schedule['status'] === 'paid' ? 'success' : ($schedule['status'] === 'partial' ? 'warning' : 'secondary') ?>">
                                                <?= ucfirst($schedule['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <h6><i class="bi bi-info-circle"></i> What's Next?</h6>
                <ul class="mb-0">
                    <li>A confirmation email will be sent to <strong><?= htmlspecialchars($booking['customer_email'] ?? '') ?></strong></li>
                    <li>Please complete payment as per the payment schedule</li>
                    <li>You will receive reminders before your booking date</li>
                    <li>Contact us if you need to modify or cancel your booking</li>
                </ul>
            </div>

            <div class="text-center">
                <a href="<?= base_url('booking-wizard') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Book Another Resource
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.bi-check-circle-fill {
    animation: scaleIn 0.5s ease-out;
}
@keyframes scaleIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}
</style>

