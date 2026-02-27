<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Booking: <?= htmlspecialchars($booking['booking_number'] ?? '') ?></h1>
        <a href="<?= base_url('customer-portal/bookings') ?>" class="btn btn-secondary">
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
                <!-- Booking Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Booking Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Resource:</strong><br>
                                <?= htmlspecialchars($booking['facility_name']) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Booking Date:</strong><br>
                                <?= date('F j, Y', strtotime($booking['booking_date'])) ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Time:</strong><br>
                                <?= date('g:i A', strtotime($booking['start_time'])) ?> - 
                                <?= date('g:i A', strtotime($booking['end_time'])) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Duration:</strong><br>
                                <?= number_format($booking['duration_hours'], 1) ?> hours
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                <span class="badge bg-<?= 
                                    $booking['status'] === 'confirmed' ? 'success' : 
                                    ($booking['status'] === 'pending' ? 'warning' : 
                                    ($booking['status'] === 'completed' ? 'info' : 
                                    ($booking['status'] === 'cancelled' ? 'danger' : 'secondary')))
                                ?>">
                                    <?= ucfirst($booking['status']) ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <strong>Number of Guests:</strong><br>
                                <?= $booking['number_of_guests'] ?>
                            </div>
                        </div>

                        <?php if (!empty($booking['special_requests'])): ?>
                            <hr>
                            <strong>Special Requests:</strong><br>
                            <p><?= nl2br(htmlspecialchars($booking['special_requests'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Payment History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($payments)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Payment #</th>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Method</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($payment['payment_number']) ?></td>
                                                <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                                <td><?= ucfirst($payment['payment_type']) ?></td>
                                                <td><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></td>
                                                <td><?= format_currency($payment['amount']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $payment['status'] === 'completed' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst($payment['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No payments recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Payment Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Payment Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal:</span>
                            <span><?= format_currency($booking['subtotal'] ?? 0) ?></span>
                        </div>
                        <?php if (($booking['discount_amount'] ?? 0) > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Discount:</span>
                            <span>-<?= format_currency($booking['discount_amount']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (($booking['tax_amount'] ?? 0) > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-muted">
                            <span>Tax <?php if(($booking['tax_rate']??0)>0) echo '('.number_format($booking['tax_rate'], 1).'%)'; ?>:</span>
                            <span><?= format_currency($booking['tax_amount']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (($booking['security_deposit'] ?? 0) > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-muted">
                            <span>Security Deposit:</span>
                            <span><?= format_currency($booking['security_deposit']) ?></span>
                        </div>
                        <?php endif; ?>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Total Amount:</strong>
                            <strong><?= format_currency($booking['total_amount']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Paid Amount:</span>
                            <strong class="text-success"><?= format_currency($booking['paid_amount']) ?></strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Balance:</strong>
                            <strong class="<?= $booking['balance_amount'] > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= format_currency($booking['balance_amount']) ?>
                            </strong>
                        </div>

                        <?php if ($booking['balance_amount'] > 0 && $booking['status'] !== 'cancelled'): ?>
                            <hr>
                            <a href="<?= base_url('booking-wizard/index?booking_id=' . $booking['id'] . '&action=pay') ?>" 
                               class="btn btn-primary w-100">
                                <i class="bi bi-credit-card"></i> Make Payment
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Booking Info -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Info</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Booking Number:</strong><br><?= htmlspecialchars($booking['booking_number']) ?></p>
                        <p class="mb-2"><strong>Created:</strong><br><?= date('M d, Y', strtotime($booking['created_at'])) ?></p>
                        <?php if ($booking['confirmed_at']): ?>
                            <p class="mb-2"><strong>Confirmed:</strong><br><?= date('M d, Y', strtotime($booking['confirmed_at'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

