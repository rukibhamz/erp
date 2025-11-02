<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Spaces</h1>
        <a href="<?= base_url('spaces/create' . ($selected_property_id ? '/' . $selected_property_id : '')) ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Space
        </a>
    </div>
</div>

<!-- Property Management Navigation -->
<div class="property-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('properties') ?>">
            <i class="bi bi-building"></i> Properties
        </a>
        <a class="nav-link active" href="<?= base_url('spaces') ?>">
            <i class="bi bi-door-open"></i> Spaces
        </a>
        <a class="nav-link" href="<?= base_url('leases') ?>">
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

<!-- Property Filter -->
<?php if (!empty($properties)): ?>
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="property_filter" class="form-label">Filter by Property</label>
                <select name="property_id" id="property_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">All Properties</option>
                    <?php foreach ($properties as $prop): ?>
                        <option value="<?= $prop['id'] ?>" <?= $selected_property_id == $prop['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prop['property_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <a href="<?= base_url('spaces') ?>" class="btn btn-outline-secondary">Clear Filter</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (empty($spaces)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-door-open" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No spaces found. Create your first space to get started.</p>
            <a href="<?= base_url('spaces/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Space
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
                            <th>Space #</th>
                            <th>Space Name</th>
                            <th>Property</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Mode</th>
                            <th>Bookable</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($spaces as $space): ?>
                            <tr>
                                <td><?= htmlspecialchars($space['space_number'] ?? '-') ?></td>
                                <td>
                                    <a href="<?= base_url('spaces/view/' . $space['id']) ?>">
                                        <?= htmlspecialchars($space['space_name']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($property): ?>
                                        <?= htmlspecialchars($property['property_name']) ?>
                                    <?php else: ?>
                                        <small class="text-muted">N/A</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= ucfirst(str_replace('_', ' ', $space['category'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $space['operational_status'] === 'active' ? 'success' : 'warning' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $space['operational_status'])) ?>
                                    </span>
                                </td>
                                <td><?= ucfirst(str_replace('_', ' ', $space['operational_mode'])) ?></td>
                                <td>
                                    <?php if ($space['is_bookable']): ?>
                                        <span class="badge bg-info">Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('spaces/view/' . $space['id']) ?>" class="btn btn-outline-dark" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url('spaces/edit/' . $space['id']) ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($space['is_bookable']): ?>
                                            <a href="<?= base_url('spaces/sync/' . $space['id']) ?>" class="btn btn-outline-info" title="Sync to Booking">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

