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

<!-- Property Management Navigation -->
<div class="property-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('properties') ?>">
            <i class="bi bi-building"></i> Properties
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
.property-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.property-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.property-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.property-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.property-nav .nav-link i {
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
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-file-earmark-text" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No leases found. Create your first lease to get started.</p>
            <a href="<?= base_url('leases/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Lease
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Lease #</th>
                            <th>Space</th>
                            <th>Property</th>
                            <th>Tenant</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Rent Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leases as $lease): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($lease['lease_number']) ?></strong></td>
                                <td><?= htmlspecialchars($lease['space_name']) ?></td>
                                <td><?= htmlspecialchars($lease['property_name']) ?></td>
                                <td><?= htmlspecialchars($lease['business_name'] ?? $lease['contact_person']) ?></td>
                                <td><?= date('M d, Y', strtotime($lease['start_date'])) ?></td>
                                <td>
                                    <?= $lease['end_date'] ? date('M d, Y', strtotime($lease['end_date'])) : 'Ongoing' ?>
                                    <?php if ($lease['end_date'] && strtotime($lease['end_date']) < strtotime('+90 days')): ?>
                                        <span class="badge bg-warning ms-1">Expiring</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= format_currency($lease['rent_amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $lease['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($lease['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url('leases/view/' . $lease['id']) ?>" class="btn btn-sm btn-outline-dark">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

