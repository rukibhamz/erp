<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Locations</h1>
        <a href="<?= base_url('inventory/locations/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Location
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/inventory/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($locations)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-geo-alt" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No locations defined.</p>
            <a href="<?= base_url('inventory/locations/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create First Location
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
                            <th>Location Code</th>
                            <th>Location Name</th>
                            <th>Type</th>
                            <th>Items</th>
                            <th>Total Quantity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $location): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($location['location_code']) ?></strong></td>
                                <td><?= htmlspecialchars($location['location_name']) ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= ucfirst(str_replace('_', ' ', $location['location_type'])) ?>
                                    </span>
                                </td>
                                <td><?= $location['item_count'] ?? 0 ?></td>
                                <td><?= number_format($location['total_qty'] ?? 0, 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $location['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $location['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url('inventory/locations/view/' . $location['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i>
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

