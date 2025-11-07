<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Product/Service</h1>
        <a href="<?= base_url('products') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Product Code *</label>
                            <input type="text" name="product_code" class="form-control" value="<?= htmlspecialchars($product['product_code']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product['product_name']) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Type *</label>
                            <select name="type" class="form-select" required>
                                <option value="product" <?= $product['type'] === 'product' ? 'selected' : '' ?>>Product</option>
                                <option value="service" <?= $product['type'] === 'service' ? 'selected' : '' ?>>Service</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($product['category'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Unit Price</label>
                            <input type="number" name="unit_price" class="form-control" step="0.01" value="<?= $product['unit_price'] ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Cost Price</label>
                            <input type="number" name="cost_price" class="form-control" step="0.01" value="<?= $product['cost_price'] ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tax</label>
                            <select name="tax_id" class="form-select">
                                <option value="">No Tax</option>
                                <?php foreach ($taxes as $tax): ?>
                                    <option value="<?= $tax['id'] ?>" <?= $product['tax_id'] == $tax['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tax['tax_name']) ?> (<?= $tax['rate'] ?>%)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Revenue Account</label>
                            <select name="account_id" class="form-select">
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?= $account['id'] ?>" <?= $product['account_id'] == $account['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="inventory_tracked" id="inventory_tracked" 
                           <?= $product['inventory_tracked'] ? 'checked' : '' ?> onchange="toggleInventory()">
                    <label class="form-check-label" for="inventory_tracked">Track Inventory</label>
                </div>

                <div id="inventory_fields" style="display: <?= $product['inventory_tracked'] ? 'block' : 'none' ?>;">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Stock Quantity</label>
                                <input type="number" name="stock_quantity" class="form-control" step="0.01" value="<?= $product['stock_quantity'] ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Reorder Level</label>
                                <input type="number" name="reorder_level" class="form-control" step="0.01" value="<?= $product['reorder_level'] ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Unit of Measure</label>
                                <input type="text" name="unit_of_measure" class="form-control" value="<?= htmlspecialchars($product['unit_of_measure']) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('products') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleInventory() {
    const checkbox = document.getElementById('inventory_tracked');
    const fields = document.getElementById('inventory_fields');
    fields.style.display = checkbox.checked ? 'block' : 'none';
}
</script>


