<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Inventory Dashboard</h1>
</div>

<?php include(BASEPATH . 'views/inventory/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Items</h6>
                <h2 class="mb-0"><?= number_format($stats['total_items']) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Inventory Value</h6>
                <h2 class="mb-0"><?= format_currency($stats['total_value']) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Low Stock Items</h6>
                <h2 class="mb-0 text-warning"><?= $stats['low_stock_count'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Out of Stock</h6>
                <h2 class="mb-0 text-danger"><?= $stats['out_of_stock_count'] ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Low Stock Items</h5>
            </div>
            <div class="card-body">
                <?php if (empty($low_stock_items)): ?>
                    <p class="text-muted mb-0">No low stock items.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>SKU</th>
                                    <th>Available</th>
                                    <th>Reorder Point</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= htmlspecialchars($item['sku']) ?></td>
                                        <td>
                                            <span class="badge bg-warning"><?= number_format($item['available_qty'], 2) ?></span>
                                        </td>
                                        <td><?= number_format($item['reorder_point'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="<?= base_url('inventory/items?filter=low_stock') ?>" class="btn btn-sm btn-primary mt-2">
                        View All
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Out of Stock Items</h5>
            </div>
            <div class="card-body">
                <?php if (empty($out_of_stock_items)): ?>
                    <p class="text-muted mb-0">No out of stock items.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>SKU</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($out_of_stock_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= htmlspecialchars($item['sku']) ?></td>
                                        <td><?= htmlspecialchars($item['location_name'] ?? 'N/A') ?></td>
                                        <td><span class="badge bg-danger">Out of Stock</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="<?= base_url('inventory/items?filter=out_of_stock') ?>" class="btn btn-sm btn-primary mt-2">
                        View All
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Locations</h5>
            </div>
            <div class="card-body">
                <?php if (empty($locations)): ?>
                    <p class="text-muted mb-3">No locations defined. <a href="<?= base_url('inventory/locations/create') ?>">Create your first location</a></p>
                <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($locations as $location): ?>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body p-3">
                                        <h6 class="mb-1"><?= htmlspecialchars($location['location_name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($location['location_code']) ?></small>
                                        <br>
                                        <small class="text-muted"><?= ucfirst(str_replace('_', ' ', $location['location_type'])) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= base_url('inventory/locations') ?>" class="btn btn-sm btn-primary mt-3">
                        Manage Locations
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

