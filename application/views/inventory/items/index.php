<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Items</h1>
        <a href="<?= base_url('inventory/items/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Item
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

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Filter</label>
                <select name="filter" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $selected_filter === 'all' ? 'selected' : '' ?>>All Items</option>
                    <option value="low_stock" <?= $selected_filter === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                    <option value="out_of_stock" <?= $selected_filter === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="item_type" class="form-select" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="inventory" <?= $selected_item_type === 'inventory' ? 'selected' : '' ?>>Inventory</option>
                    <option value="non_inventory" <?= $selected_item_type === 'non_inventory' ? 'selected' : '' ?>>Non-Inventory</option>
                    <option value="service" <?= $selected_item_type === 'service' ? 'selected' : '' ?>>Service</option>
                    <option value="fixed_asset" <?= $selected_item_type === 'fixed_asset' ? 'selected' : '' ?>>Fixed Asset</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Category</label>
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $selected_category === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search_term ?? '') ?>" 
                       placeholder="SKU, Name, Barcode">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
            <div class="col-md-12">
                <a href="<?= base_url('inventory/items') ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($items)): ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="empty-state">
                <i class="bi bi-box"></i>
                <p class="mb-0">No items found.</p>
                <a href="<?= base_url('inventory/items/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add First Item
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Cost Price</th>
                            <th>Selling Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($item['sku']) ?></strong></td>
                                <td>
                                    <a href="<?= base_url('inventory/items/view/' . $item['id']) ?>">
                                        <?= htmlspecialchars($item['item_name']) ?>
                                    </a>
                                    <?php if (($item['is_wholesale_enabled'] ?? 0) == 1): ?>
                                        <span class="badge bg-info ms-1" title="Wholesale Enabled">WS</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= ucfirst(str_replace('_', ' ', $item['item_type'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($item['category'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($item['unit_of_measure']) ?></td>
                                <td><?= format_currency($item['cost_price']) ?></td>
                                <td><?= format_currency($item['selling_price']) ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $item['item_status'] === 'active' ? 'success' : 
                                        ($item['item_status'] === 'discontinued' ? 'secondary' : 'danger') ?>">
                                        <?= ucfirst(str_replace('_', ' ', $item['item_status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('inventory/items/view/' . $item['id']) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url('inventory/items/edit/' . $item['id']) ?>" class="btn btn-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
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

