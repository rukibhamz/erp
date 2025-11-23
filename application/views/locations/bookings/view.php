<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Booking: <?= htmlspecialchars($booking['booking_number']) ?></h1>
        <div class="d-flex gap-2">
            <?php if ($booking['status'] === 'pending'): ?>
                <a href="<?= base_url('space-bookings/confirm/' . $booking['id']) ?>" class="btn btn-primary" onclick="return confirm('Confirm this booking?')">
                    <i class="bi bi-check-circle"></i> Confirm
                </a>
            <?php endif; ?>
            <a href="<?= base_url('locations/bookings') ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
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

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-calendar-check"></i> Booking Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Booking Number:</dt>
                    <dd class="col-sm-8"><strong><?= htmlspecialchars($booking['booking_number']) ?></strong></dd>
                    
                    <?php if ($location): ?>
                        <dt class="col-sm-4">Location:</dt>
                        <dd class="col-sm-8">
                            <a href="<?= base_url('locations/view/' . $location['id']) ?>">
                                <?= htmlspecialchars($location['Location_name'] ?? $location['property_name'] ?? 'N/A') ?>
                            </a>
                        </dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-4">Space:</dt>
                    <dd class="col-sm-8">
                        <a href="<?= base_url('spaces/view/' . $booking['space_id']) ?>">
                            <?= htmlspecialchars($space['space_name'] ?? 'N/A') ?>
                        </a>
                    </dd>
                    
                    <dt class="col-sm-4">Customer:</dt>
                    <dd class="col-sm-8">
                        <?= htmlspecialchars($booking['customer_name'] ?? 'N/A') ?>
                        <?php if ($booking['customer_email']): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($booking['customer_email']) ?></small>
                        <?php endif; ?>
                        <?php if ($booking['customer_phone']): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($booking['customer_phone']) ?></small>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-4">Booking Date:</dt>
                    <dd class="col-sm-8"><?= date('l, F d, Y', strtotime($booking['booking_date'])) ?></dd>
                    
                    <dt class="col-sm-4">Time:</dt>
                    <dd class="col-sm-8">
                        <?= date('g:i A', strtotime($booking['start_time'])) ?> - 
                        <?= date('g:i A', strtotime($booking['end_time'])) ?>
                    </dd>
                    
                    <dt class="col-sm-4">Duration:</dt>
                    <dd class="col-sm-8"><?= number_format($booking['duration_hours'], 2) ?> hours</dd>
                    
                    <dt class="col-sm-4">Booking Type:</dt>
                    <dd class="col-sm-8"><?= ucfirst(str_replace('_', ' ', $booking['booking_type'] ?? 'hourly')) ?></dd>
                    
                    <dt class="col-sm-4">Number of Guests:</dt>
                    <dd class="col-sm-8"><?= $booking['number_of_guests'] ?: 'N/A' ?></dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= $booking['status'] === 'confirmed' ? 'success' : ($booking['status'] === 'pending' ? 'warning' : 'secondary') ?>">
                            <?= ucfirst($booking['status']) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4">Payment Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= $booking['payment_status'] === 'paid' ? 'success' : ($booking['payment_status'] === 'partial' ? 'warning' : 'secondary') ?>">
                            <?= ucfirst($booking['payment_status']) ?>
                        </span>
                    </dd>
                    
                    <?php if ($booking['booking_notes']): ?>
                        <dt class="col-sm-4">Booking Notes:</dt>
                        <dd class="col-sm-8"><?= nl2br(htmlspecialchars($booking['booking_notes'])) ?></dd>
                    <?php endif; ?>
                    
                    <?php if ($booking['special_requests']): ?>
                        <dt class="col-sm-4">Special Requests:</dt>
                        <dd class="col-sm-8"><?= nl2br(htmlspecialchars($booking['special_requests'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-currency-dollar"></i> Financial Summary</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Base Amount</h6>
                    <h4 class="mb-0"><?= format_currency($booking['base_amount'] ?? 0) ?></h4>
                </div>
                
                <div class="border-top pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Total Amount:</h6>
                        <h4 class="mb-0 text-primary"><?= format_currency($booking['total_amount'] ?? 0) ?></h4>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Paid:</span>
                        <strong><?= format_currency($booking['paid_amount'] ?? 0) ?></strong>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Balance:</span>
                        <strong class="text-<?= ($booking['total_amount'] ?? 0) - ($booking['paid_amount'] ?? 0) > 0 ? 'danger' : 'success' ?>">
                            <?= format_currency(($booking['total_amount'] ?? 0) - ($booking['paid_amount'] ?? 0)) ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="<?= base_url('locations/booking-calendar/' . ($location ? $location['id'] : '') . '/' . $booking['space_id']) ?>" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-calendar-month"></i> View Calendar
                </a>
                <?php if ($space): ?>
                    <a href="<?= base_url('spaces/view/' . $space['id']) ?>" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-door-open"></i> View Space
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

