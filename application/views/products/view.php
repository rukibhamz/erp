<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Product: <?= htmlspecialchars($product['product_name'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if (has_permission('products', 'update')): ?>
                <a href="<?= base_url('products/edit/' . $product['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('products') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Product Information</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Product Code</dt>
                    <dd><strong><?= htmlspecialchars($product['product_code'] ?? 'N/A') ?></strong></dd>
                    
                    <dt>Product Name</dt>
                    <dd><?= htmlspecialchars($product['product_name'] ?? 'N/A') ?></dd>
                    
                    <dt>Type</dt>
                    <dd><span class="badge bg-info"><?= ucfirst($product['type'] ?? 'product') ?></span></dd>
                    
                    <?php if (!empty($product['category'])): ?>
                    <dt>Category</dt>
                    <dd><?= htmlspecialchars($product['category']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['description'])): ?>
                    <dt>Description</dt>
                    <dd><?= htmlspecialchars($product['description']) ?></dd>
                    <?php endif; ?>
                    
                    <dt>Unit of Measure</dt>
                    <dd><?= htmlspecialchars($product['unit_of_measure'] ?? 'unit') ?></dd>
                    
                    <dt>Status</dt>
                    <dd>
                        <span class="badge bg-<?= ($product['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($product['status'] ?? 'inactive') ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Pricing & Inventory</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Unit Price</dt>
                    <dd class="fs-4 fw-bold"><?= format_currency($product['unit_price'] ?? 0, $product['currency'] ?? 'USD') ?></dd>
                    
                    <dt>Cost Price</dt>
                    <dd><?= format_currency($product['cost_price'] ?? 0, $product['currency'] ?? 'USD') ?></dd>
                    
                    <?php if (!empty($product['tax'])): ?>
                    <dt>Tax</dt>
                    <dd><?= htmlspecialchars($product['tax']['tax_name'] ?? '') ?> (<?= number_format($product['tax']['rate'] ?? 0, 2) ?>%)</dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['account'])): ?>
                    <dt>Revenue Account</dt>
                    <dd><?= htmlspecialchars($product['account']['account_name'] ?? '') ?></dd>
                    <?php endif; ?>
                    
                    <dt>Inventory Tracked</dt>
                    <dd>
                        <span class="badge bg-<?= ($product['inventory_tracked'] ?? false) ? 'success' : 'secondary' ?>">
                            <?= ($product['inventory_tracked'] ?? false) ? 'Yes' : 'No' ?>
                        </span>
                    </dd>
                    
                    <?php if ($product['inventory_tracked'] ?? false): ?>
                    <dt>Stock Quantity</dt>
                    <dd>
                        <?= number_format($product['stock_quantity'] ?? 0, 2) ?> <?= htmlspecialchars($product['unit_of_measure'] ?? 'unit') ?>
                        <?php if (($product['stock_quantity'] ?? 0) <= ($product['reorder_level'] ?? 0)): ?>
                            <span class="badge bg-warning">Low Stock</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt>Reorder Level</dt>
                    <dd><?= number_format($product['reorder_level'] ?? 0, 2) ?> <?= htmlspecialchars($product['unit_of_measure'] ?? 'unit') ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if (has_permission('products', 'update')): ?>
                        <a href="<?= base_url('products/edit/' . $product['id']) ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Product
                        </a>
                    <?php endif; ?>
                    <?php if (has_permission('products', 'delete')): ?>
                        <a href="<?= base_url('products/delete/' . $product['id']) ?>" class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this product?')">
                            <i class="bi bi-trash"></i> Delete Product
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

