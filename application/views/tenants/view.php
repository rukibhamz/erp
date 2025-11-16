<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Tenant: <?= htmlspecialchars($tenant['business_name'] ?: $tenant['contact_person']) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('tenants/edit/' . $tenant['id']) ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="<?= base_url('tenants') ?>" class="btn btn-outline-secondary">
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
        <a class="nav-link active" href="<?= base_url('tenants') ?>">
            <i class="bi bi-people"></i> Tenants
        </a>
        <a class="nav-link" href="<?= base_url('leases') ?>">
            <i class="bi bi-file-earmark-text"></i> Leases
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

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Tenant Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Tenant Code:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($tenant['tenant_code']) ?></dd>
                    
                    <dt class="col-sm-4">Tenant Type:</dt>
                    <dd class="col-sm-8"><?= ucfirst(str_replace('_', ' ', $tenant['tenant_type'])) ?></dd>
                    
                    <?php if ($tenant['business_name']): ?>
                        <dt class="col-sm-4">Business Name:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($tenant['business_name']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if ($tenant['business_registration']): ?>
                        <dt class="col-sm-4">Business Registration:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($tenant['business_registration']) ?></dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-4">Contact Person:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($tenant['contact_person']) ?></dd>
                    
                    <dt class="col-sm-4">Email:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($tenant['email']) ?></dd>
                    
                    <dt class="col-sm-4">Phone:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($tenant['phone']) ?></dd>
                    
                    <?php if ($tenant['alternate_phone']): ?>
                        <dt class="col-sm-4">Alternate Phone:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($tenant['alternate_phone']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if ($tenant['address']): ?>
                        <dt class="col-sm-4">Address:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($tenant['address']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if ($tenant['city'] || $tenant['state']): ?>
                        <dt class="col-sm-4">Location:</dt>
                        <dd class="col-sm-8">
                            <?= htmlspecialchars($tenant['city'] ?? '') ?><?= $tenant['city'] && $tenant['state'] ? ', ' : '' ?><?= htmlspecialchars($tenant['state'] ?? '') ?>
                            <?= $tenant['country'] ? ', ' . htmlspecialchars($tenant['country']) : '' ?>
                        </dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= $tenant['status'] === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($tenant['status']) ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
        
        <!-- Leases Section -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Active Leases (<?= count($tenant['leases'] ?? []) ?>)</h5>
                <a href="<?= base_url('leases/create?tenant_id=' . $tenant['id']) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle"></i> New Lease
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($tenant['leases'])): ?>
                    <p class="text-muted mb-0">No active leases for this tenant.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Lease #</th>
                                    <th>Space</th>
                                    <th>Property</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Rent</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tenant['leases'] as $lease): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($lease['lease_number']) ?></td>
                                        <td><?= htmlspecialchars($lease['space_name']) ?></td>
                                        <td><?= htmlspecialchars($lease['property_name']) ?></td>
                                        <td><?= date('M d, Y', strtotime($lease['start_date'])) ?></td>
                                        <td><?= $lease['end_date'] ? date('M d, Y', strtotime($lease['end_date'])) : 'Ongoing' ?></td>
                                        <td><?= format_currency($lease['rent_amount']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $lease['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($lease['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('leases/view/' . $lease['id']) ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="<?= base_url('leases/create?tenant_id=' . $tenant['id']) ?>" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-file-earmark-plus"></i> Create Lease
                </a>
            </div>
        </div>
    </div>
</div>

