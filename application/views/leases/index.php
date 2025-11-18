<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Leases</h1>
        <a href="<?= base_url('leases/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Lease
        </a>
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
        <a class="nav-link active" href="<?= base_url('leases') ?>">
            <i class="bi bi-file-earmark-text"></i> Leases
        </a>
        <a class="nav-link" href="<?= base_url('tenants') ?>">
            <i class="bi bi-people"></i> Tenants
        </a>
        <a class="nav-link" href="<?= base_url('rent-invoices') ?>">
            <i class="bi bi-receipt"></i> Rent Invoices
        </a>
    </nav>
</div>

<style>
.Location-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.Location-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.Location-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.Location-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.Location-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter by Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="active" <?= $selected_status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="expiring" <?= $selected_status === 'expiring' ? 'selected' : '' ?>>Expiring Soon</option>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if (empty($leases)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-file-earmark-text" style="font-size: 4rem; color: #dee2e6;"></i>
            <h5 class="mt-3 text-muted">No Leases Found</h5>
            <p class="text-muted">Create your first lease to get started.</p>
            <a href="<?= base_url('leases/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Lease
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($leases as $lease): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-file-earmark-text"></i> <?= htmlspecialchars($lease['lease_number']) ?></h6>
                        <span class="badge bg-<?= $lease['status'] === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($lease['status']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <p class="mb-2">
                                <strong><i class="bi bi-building"></i> Property:</strong><br>
                                <small class="text-muted"><?= htmlspecialchars($lease['property_name']) ?></small>
                            </p>
                            <p class="mb-2">
                                <strong><i class="bi bi-door-open"></i> Space:</strong><br>
                                <small class="text-muted"><?= htmlspecialchars($lease['space_name']) ?></small>
                            </p>
                            <p class="mb-2">
                                <strong><i class="bi bi-people"></i> Tenant:</strong><br>
                                <small class="text-muted"><?= htmlspecialchars($lease['business_name'] ?? $lease['contact_person']) ?></small>
                            </p>
                            <div class="row g-2 mt-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Start Date</small>
                                    <strong><?= date('M d, Y', strtotime($lease['start_date'])) ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">End Date</small>
                                    <strong>
                                        <?= $lease['end_date'] ? date('M d, Y', strtotime($lease['end_date'])) : 'Ongoing' ?>
                                        <?php if ($lease['end_date'] && strtotime($lease['end_date']) < strtotime('+90 days')): ?>
                                            <span class="badge bg-warning ms-1">Expiring</span>
                                        <?php endif; ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Monthly Rent:</span>
                                <strong class="text-primary"><?= format_currency($lease['rent_amount']) ?></strong>
                            </div>
                            
                            <a href="<?= base_url('leases/view/' . $lease['id']) ?>" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

