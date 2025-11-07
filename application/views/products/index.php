<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Products & Services</h1>
        <?php if (has_permission('products', 'create')): ?>
            <a href="<?= base_url('products/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Product/Service
            </a>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="product" <?= (isset($_GET['type']) && $_GET['type'] === 'product') ? 'selected' : '' ?>>Product</option>
                        <option value="service" <?= (isset($_GET['type']) && $_GET['type'] === 'service') ? 'selected' : '' ?>>Service</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-secondary">Filter</button>
                    <a href="<?= base_url('products') ?>" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Unit Price</th>
                            <th>Cost Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($products)): ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['product_code']) ?></td>
                                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                                    <td><span class="badge bg-info"><?= ucfirst($product['type']) ?></span></td>
                                    <td><?= htmlspecialchars($product['category'] ?? '-') ?></td>
                                    <td><?= format_currency($product['unit_price'], $product['currency'] ?? 'USD') ?></td>
                                    <td><?= format_currency($product['cost_price'], $product['currency'] ?? 'USD') ?></td>
                                    <td>
                                        <?php if ($product['inventory_tracked']): ?>
                                            <?= number_format($product['stock_quantity'], 2) . ' ' . htmlspecialchars($product['unit_of_measure']) ?>
                                            <?php if ($product['stock_quantity'] <= $product['reorder_level']): ?>
                                                <span class="badge bg-warning">Low Stock</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($product['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (has_permission('products', 'update')): ?>
                                            <a href="<?= base_url('products/edit/' . $product['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (has_permission('products', 'delete')): ?>
                                            <a href="<?= base_url('products/delete/' . $product['id']) ?>" class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No products found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


