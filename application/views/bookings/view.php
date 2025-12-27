<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Booking: <?= htmlspecialchars($booking['booking_number'] ?? '') ?></h1>
        <div>
            <a href="<?= base_url('bookings') ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <?php if (has_permission('bookings', 'update') && !in_array($booking['status'], ['cancelled', 'completed'])): ?>
                <a href="<?= base_url('bookings/edit/' . $booking['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="<?= base_url('bookings/reschedule/' . $booking['id']) ?>" class="btn btn-warning">
                    <i class="bi bi-calendar"></i> Reschedule
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

    <div class="row">
        <!-- Booking Details -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Booking Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Status:</strong>
                            <span class="badge bg-<?= 
                                $booking['status'] === 'confirmed' ? 'success' : 
                                ($booking['status'] === 'pending' ? 'warning' : 
                                ($booking['status'] === 'completed' ? 'info' : 'secondary')) 
                            ?> ms-2">
                                <?= ucfirst($booking['status']) ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Payment Status:</strong>
                            <span class="badge bg-<?= $booking['payment_status'] === 'paid' ? 'success' : ($booking['payment_status'] === 'partial' ? 'warning' : 'danger') ?> ms-2">
                                <?= ucfirst($booking['payment_status']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Facility:</strong>
                            <?= htmlspecialchars($booking['facility_name'] ?? 'N/A') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Booking Type:</strong>
                            <?= ucfirst($booking['booking_type'] ?? 'hourly') ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Date:</strong>
                            <?= date('M d, Y', strtotime($booking['booking_date'])) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Time:</strong>
                            <?= date('h:i A', strtotime($booking['start_time'])) ?> - <?= date('h:i A', strtotime($booking['end_time'])) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Duration:</strong>
                            <?= number_format($booking['duration_hours'], 1) ?> hours
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Guests:</strong>
                            <?= intval($booking['number_of_guests'] ?? 0) ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($booking['booking_notes'])): ?>
                    <div class="mb-3">
                        <strong>Notes:</strong>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($booking['booking_notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($booking['special_requests'])): ?>
                    <div class="mb-3">
                        <strong>Special Requests:</strong>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($booking['special_requests'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?= htmlspecialchars($booking['customer_name'] ?? 'N/A') ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($booking['customer_phone'] ?? 'N/A') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <?= htmlspecialchars($booking['customer_email'] ?? 'N/A') ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($booking['customer_address'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Payment History</h5>
                    <?php if (has_permission('bookings', 'update') && $booking['status'] !== 'cancelled' && floatval($booking['balance_amount']) > 0): ?>
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                            <i class="bi bi-plus"></i> Record Payment
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($payments)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Payment #</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($payment['payment_number'] ?? '') ?></td>
                                            <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                            <td><?= ucfirst($payment['payment_method'] ?? 'cash') ?></td>
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

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Financial Summary -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Financial Summary</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td>Base Amount:</td>
                            <td class="text-end"><?= format_currency($booking['base_amount']) ?></td>
                        </tr>
                        <?php if (floatval($booking['discount_amount']) > 0): ?>
                        <tr>
                            <td>Discount:</td>
                            <td class="text-end text-danger">-<?= format_currency($booking['discount_amount']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (floatval($booking['tax_amount']) > 0): ?>
                        <tr>
                            <td>Tax:</td>
                            <td class="text-end"><?= format_currency($booking['tax_amount']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="fw-bold">
                            <td>Total:</td>
                            <td class="text-end"><?= format_currency($booking['total_amount']) ?></td>
                        </tr>
                        <tr>
                            <td>Paid:</td>
                            <td class="text-end text-success"><?= format_currency($booking['paid_amount']) ?></td>
                        </tr>
                        <tr class="fw-bold">
                            <td>Balance:</td>
                            <td class="text-end <?= floatval($booking['balance_amount']) > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= format_currency($booking['balance_amount']) ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Status Actions -->
            <?php if (has_permission('bookings', 'update') && !in_array($booking['status'], ['cancelled', 'completed'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= base_url('bookings/updateStatus/' . $booking['id']) ?>">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Update Status</label>
                            <select name="status" class="form-select">
                                <?php if ($booking['status'] === 'pending'): ?>
                                    <option value="confirmed">Confirm Booking</option>
                                <?php endif; ?>
                                <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                    <option value="in_progress">Mark In Progress</option>
                                <?php endif; ?>
                                <?php if (in_array($booking['status'], ['confirmed', 'in_progress'])): ?>
                                    <option value="completed">Mark Completed</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Status</button>
                    </form>
                    
                    <hr>
                    
                    <a href="<?= base_url('bookings/cancel/' . $booking['id']) ?>" class="btn btn-danger w-100">
                        <i class="bi bi-x-circle"></i> Cancel Booking
                    </a>
                    
                    <hr>
                    
                    <a href="<?= base_url('bookings/modifications/' . $booking['id']) ?>" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-clock-history"></i> View History
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<?php if (has_permission('bookings', 'update') && $booking['status'] !== 'cancelled'): ?>
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= base_url('bookings/recordPayment') ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" 
                               max="<?= floatval($booking['balance_amount']) ?>" 
                               value="<?= floatval($booking['balance_amount']) ?>" required>
                        <small class="text-muted">Balance: <?= format_currency($booking['balance_amount']) ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="transfer">Bank Transfer</option>
                            <option value="pos">POS</option>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
