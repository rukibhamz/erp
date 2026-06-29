<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Locations</h1>
        <a href="<?= base_url('locations/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Location
        </a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

<?php endif; ?>

<!-- Location Management Navigation -->
<div class="Location-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link <?= (strpos($_GET['url'] ?? '', 'locations') === 0 && !strpos($_GET['url'] ?? '', '/')) ? 'active' : '' ?>" href="<?= base_url('locations') ?>">
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

<?php if (empty($locations)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-building" style="font-size: 4rem; color: #dee2e6;"></i>
            <h5 class="mt-3 text-muted">No Locations Found</h5>
            <p class="text-muted">Create your first location to get started with property management.</p>
            <a href="<?= base_url('locations/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Location
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <?php
            $bulk_delete_enabled = has_permission('locations', 'delete');
            bulk_delete_render_toolbar($bulk_delete_enabled, $locations, base_url('locations/bulk-delete'), 'location', 'Are you sure you want to delete the selected locations?');
            ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <?php bulk_delete_render_checkbox_th($bulk_delete_enabled); ?>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Location</th>
                            <th>Built Area</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $Location): ?>
                            <tr>
                                <?php bulk_delete_render_checkbox_td($bulk_delete_enabled, (int)$Location['id'], 'location ' . ($Location['Location_name'] ?? '')); ?>
                                <td><strong><?= htmlspecialchars($Location['Location_name']) ?></strong></td>
                                <td><?= htmlspecialchars($Location['Location_code']) ?></td>
                                <td>
                                    <?php if ($Location['city'] || $Location['state']): ?>
                                        <?= htmlspecialchars($Location['city'] ?? '') ?><?= $Location['city'] && $Location['state'] ? ', ' : '' ?><?= htmlspecialchars($Location['state'] ?? '') ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($Location['built_area']): ?>
                                        <?= number_format($Location['built_area'], 2) ?> sqm
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($Location['Location_type']): ?>
                                        <?= ucfirst(str_replace('_', ' ', $Location['Location_type'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $Location['status'] === 'operational' ? 'success' : ($Location['status'] === 'under_construction' ? 'warning' : 'secondary') ?>">
                                        <?= ucfirst(str_replace('_', ' ', $Location['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('locations/view/' . $Location['id']) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url('locations/edit/' . $Location['id']) ?>" class="btn btn-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= base_url('spaces?location_id=' . $Location['id']) ?>" class="btn btn-primary" title="Spaces">
                                            <i class="bi bi-door-open"></i>
                                        </a>
                                        <?php if (has_permission('locations', 'delete')): ?>
                                            <form method="post" action="<?= base_url('locations/delete/' . $Location['id']) ?>" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this location?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php render_pagination_controls($pagination ?? null); ?>
        </div>
    </div>
<?php endif; ?>
