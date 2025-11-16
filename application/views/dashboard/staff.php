<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header mb-4">
    <h1 class="page-title mb-0">My Dashboard</h1>
    <p class="text-muted mb-0">Welcome back! Here's what's happening today.</p>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Quick Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Today's Bookings</div>
                    <div class="stat-number"><?= $stats['today_bookings'] ?? 0 ?></div>
                </div>
                <div class="stat-icon primary">
                    <i class="bi bi-calendar-check"></i>
                </div>
            </div>
            <a href="<?= base_url('bookings') ?>" class="btn btn-sm btn-primary mt-2 w-100">View Bookings</a>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">This Week</div>
                    <div class="stat-number"><?= $stats['week_bookings'] ?? 0 ?></div>
                </div>
                <div class="stat-icon success">
                    <i class="bi bi-calendar-week"></i>
                </div>
            </div>
            <a href="<?= base_url('bookings') ?>" class="btn btn-sm btn-outline-success mt-2 w-100">View All</a>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Pending Bookings</div>
                    <div class="stat-number"><?= $stats['pending_bookings'] ?? 0 ?></div>
                </div>
                <div class="stat-icon warning">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
            <a href="<?= base_url('bookings?status=pending') ?>" class="btn btn-sm btn-outline-warning mt-2 w-100">Review</a>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Low Stock Items</div>
                    <div class="stat-number"><?= $stats['low_stock_count'] ?? 0 ?></div>
                </div>
                <div class="stat-icon danger">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>
            <a href="<?= base_url('inventory?filter=low_stock') ?>" class="btn btn-sm btn-outline-danger mt-2 w-100">Check Inventory</a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="row g-3">
    <!-- Today's Bookings -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Today's Bookings</h6>
                <a href="<?= base_url('bookings/create') ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle"></i> New Booking
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($today_bookings)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Booking #</th>
                                    <th>Facility</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($today_bookings, 0, 5) as $booking): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($booking['booking_number'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($booking['facility_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($booking['start_time'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                ($booking['status'] ?? '') === 'confirmed' ? 'success' : 
                                                (($booking['status'] ?? '') === 'pending' ? 'warning' : 'secondary') 
                                            ?>">
                                                <?= ucfirst($booking['status'] ?? 'unknown') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('bookings/view/' . ($booking['id'] ?? '')) ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($today_bookings) > 5): ?>
                        <div class="text-center mt-2">
                            <a href="<?= base_url('bookings') ?>" class="btn btn-sm btn-primary">View All (<?= count($today_bookings) ?>)</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-calendar-x text-muted display-1"></i>
                        <p class="text-muted mt-2">No bookings scheduled for today</p>
                        <a href="<?= base_url('bookings/create') ?>" class="btn btn-primary btn-sm mt-2">
                            <i class="bi bi-plus-circle"></i> Create Booking
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <a href="<?= base_url('pos') ?>" class="btn btn-primary w-100 d-flex align-items-center justify-content-center py-3">
                            <i class="bi bi-cash-register me-2 fs-4"></i>
                            <div class="text-start">
                                <div class="fw-bold">POS</div>
                                <small class="text-muted">Point of Sale</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?= base_url('bookings/create') ?>" class="btn btn-outline-success w-100 d-flex align-items-center justify-content-center py-3">
                            <i class="bi bi-calendar-plus me-2 fs-4"></i>
                            <div class="text-start">
                                <div class="fw-bold">New Booking</div>
                                <small class="text-muted">Create booking</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?= base_url('inventory') ?>" class="btn btn-outline-info w-100 d-flex align-items-center justify-content-center py-3">
                            <i class="bi bi-box-seam me-2 fs-4"></i>
                            <div class="text-start">
                                <div class="fw-bold">Inventory</div>
                                <small class="text-muted">Manage stock</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?= base_url('utilities') ?>" class="btn btn-outline-warning w-100 d-flex align-items-center justify-content-center py-3">
                            <i class="bi bi-tools me-2 fs-4"></i>
                            <div class="text-start">
                                <div class="fw-bold">Utilities</div>
                                <small class="text-muted">Utilities & Services</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Low Stock Alert -->
    <?php if (!empty($low_stock_items)): ?>
    <div class="col-lg-6">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Alert</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Current Stock</th>
                                <th>Reorder Level</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($low_stock_items, 0, 5) as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['item_name'] ?? $item['name'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge bg-danger"><?= htmlspecialchars($item['quantity'] ?? 0) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($item['reorder_level'] ?? 0) ?></td>
                                    <td>
                                        <a href="<?= base_url('inventory/edit/' . ($item['id'] ?? '')) ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i> Update
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($low_stock_items) > 5): ?>
                    <div class="text-center mt-2">
                        <a href="<?= base_url('inventory?filter=low_stock') ?>" class="btn btn-sm btn-outline-warning">View All (<?= count($low_stock_items) ?>)</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Bookings -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Bookings</h6>
                <a href="<?= base_url('bookings') ?>" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_bookings)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($recent_bookings, 0, 5) as $booking): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($booking['booking_number'] ?? 'N/A') ?></h6>
                                        <p class="mb-1 text-muted small">
                                            <?= htmlspecialchars($booking['facility_name'] ?? 'N/A') ?> - 
                                            <?= date('M d, Y', strtotime($booking['booking_date'] ?? 'now')) ?>
                                        </p>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?= 
                                            ($booking['status'] ?? '') === 'confirmed' ? 'success' : 
                                            (($booking['status'] ?? '') === 'pending' ? 'warning' : 'secondary') 
                                        ?>">
                                            <?= ucfirst($booking['status'] ?? 'unknown') ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <a href="<?= base_url('bookings/view/' . ($booking['id'] ?? '')) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="<?= base_url('bookings/edit/' . ($booking['id'] ?? '')) ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox text-muted display-1"></i>
                        <p class="text-muted mt-2">No recent bookings</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Module Access Cards -->
<div class="row g-3 mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-grid me-2"></i>My Modules</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <a href="<?= base_url('pos') ?>" class="text-decoration-none">
                            <div class="card border-primary h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-cash-register text-primary display-4"></i>
                                    <h6 class="mt-3 mb-0">POS</h6>
                                    <small class="text-muted">Point of Sale</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="<?= base_url('bookings') ?>" class="text-decoration-none">
                            <div class="card border-success h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-calendar-check text-success display-4"></i>
                                    <h6 class="mt-3 mb-0">Bookings</h6>
                                    <small class="text-muted">Manage bookings</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="<?= base_url('inventory') ?>" class="text-decoration-none">
                            <div class="card border-info h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-box-seam text-info display-4"></i>
                                    <h6 class="mt-3 mb-0">Inventory</h6>
                                    <small class="text-muted">Stock management</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="<?= base_url('utilities') ?>" class="text-decoration-none">
                            <div class="card border-warning h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-tools text-warning display-4"></i>
                                    <h6 class="mt-3 mb-0">Utilities</h6>
                                    <small class="text-muted">Utilities & services</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
