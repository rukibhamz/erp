<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Booking: <?= htmlspecialchars($booking['booking_number'] ?? '') ?></h1>
        <div>
            <a href="<?= base_url('bookings') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($booking): ?>
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Booking Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Facility:</strong> <?= htmlspecialchars($booking['facility_name']) ?><br>
                                <strong>Booking Date:</strong> <?= date('M d, Y', strtotime($booking['booking_date'])) ?><br>
                                <strong>Time:</strong> <?= date('h:i A', strtotime($booking['start_time'])) ?> - <?= date('h:i A', strtotime($booking['end_time'])) ?><br>
                                <strong>Duration:</strong> <?= number_format($booking['duration_hours'], 1) ?> hours
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong> 
                                <span class="badge bg-<?= 
                                    $booking['status'] === 'confirmed' ? 'success' : 
                                    ($booking['status'] === 'pending' ? 'warning' : 
                                    ($booking['status'] === 'completed' ? 'info' : 'secondary')) 
                                ?>">
                                    <?= ucfirst($booking['status']) ?>
                                </span><br>
                                <strong>Payment Status:</strong> 
                                <span class="badge bg-<?= $booking['payment_status'] === 'paid' ? 'success' : ($booking['payment_status'] === 'partial' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($booking['payment_status']) ?>
                                </span><br>
                                <strong>Number of Guests:</strong> <?= $booking['number_of_guests'] ?>
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Customer Information</h6>
                                <strong>Name:</strong> <?= htmlspecialchars($booking['customer_name']) ?><br>
                                <strong>Email:</strong> <?= htmlspecialchars($booking['customer_email'] ?? '-') ?><br>
                                <strong>Phone:</strong> <?= htmlspecialchars($booking['customer_phone'] ?? '-') ?><br>
                                <strong>Address:</strong> <?= htmlspecialchars($booking['customer_address'] ?? '-') ?>
                            </div>
                            <div class="col-md-6">
                                <h6>Amount Details</h6>
                                <strong>Base Amount:</strong> <?= format_currency($booking['base_amount']) ?><br>
                                <strong>Discount:</strong> <?= format_currency($booking['discount_amount']) ?><br>
                                <strong>Tax:</strong> <?= format_currency($booking['tax_amount']) ?><br>
                                <strong>Security Deposit:</strong> <?= format_currency($booking['security_deposit']) ?><br>
                                <strong>Total Amount:</strong> <span class="fw-bold"><?= format_currency($booking['total_amount']) ?></span><br>
                                <strong>Paid:</strong> <?= format_currency($booking['paid_amount']) ?><br>
                                <strong>Balance:</strong> <span class="<?= $booking['balance_amount'] > 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= format_currency($booking['balance_amount']) ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($booking['booking_notes']): ?>
                            <hr>
                            <strong>Booking Notes:</strong><br>
                            <p><?= nl2br(htmlspecialchars($booking['booking_notes'])) ?></p>
                        <?php endif; ?>

                        <?php if ($booking['special_requests']): ?>
                            <hr>
                            <strong>Special Requests:</strong><br>
                            <p><?= nl2br(htmlspecialchars($booking['special_requests'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Actions -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2 mb-3">
                            <?php if ($booking['status'] !== 'cancelled'): ?>
                                <a href="<?= base_url('bookings/reschedule/' . $booking['id']) ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-calendar-event"></i> Reschedule
                                </a>
                                <?php if ($booking['status'] !== 'completed'): ?>
                                    <a href="<?= base_url('bookings/cancel/' . $booking['id']) ?>" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-x-circle"></i> Cancel Booking
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="<?= base_url('bookings/modifications/' . $booking['id']) ?>" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-clock-history"></i> View History
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Status Update -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Update Status</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= base_url('bookings/status/' . $booking['id']) ?>">
                            <div class="mb-3">
                                <select name="status" class="form-select">
                                    <option value="pending" <?= $booking['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $booking['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="in_progress" <?= $booking['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="completed" <?= $booking['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $booking['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason (optional)</label>
                                <textarea name="reason" class="form-control" rows="2" placeholder="Reason for status change..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Update Status</button>
                        </form>
                    </div>
                </div>

                <!-- Record Payment -->
                <?php if ($booking['balance_amount'] > 0): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Record Payment</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= base_url('bookings/payment') ?>">
                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Amount</label>
                                    <input type="number" name="amount" class="form-control" step="0.01" max="<?= $booking['balance_amount'] ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="card">Card</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Payment Type</label>
                                    <select name="payment_type" class="form-select" required>
                                        <option value="partial">Partial Payment</option>
                                        <option value="full">Full Payment</option>
                                        <option value="deposit">Deposit</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success w-100">Record Payment</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Payment Progress -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Payment Progress</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $paidPercent = $booking['total_amount'] > 0 ? ($booking['paid_amount'] / $booking['total_amount']) * 100 : 0;
                        ?>
                        <div class="progress mb-2" style="height: 25px;">
                            <div class="progress-bar <?= $paidPercent >= 100 ? 'bg-success' : 'bg-warning' ?>" 
                                 role="progressbar" 
                                 style="width: <?= min(100, $paidPercent) ?>%">
                                <?= number_format($paidPercent, 1) ?>%
                            </div>
                        </div>
                        <small class="text-muted">
                            Paid: <?= format_currency($booking['paid_amount']) ?> / <?= format_currency($booking['total_amount']) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payment History</h5>
            </div>
            <div class="card-body">
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
                            <?php if (!empty($payments)): ?>
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
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No payments recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

