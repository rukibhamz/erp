<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Product/Service</h1>
        <a href="<?= base_url('products') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
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
                <?php echo csrf_field(); ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="product_code" class="form-label">
                            Product Code <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="product_code" name="product_code" class="form-control" required>
                        <small class="text-muted">Leave empty to auto-generate</small>
                    </div>
                    <div class="col-md-6">
                        <label for="product_name" class="form-label">
                            Product Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="product_name" name="product_name" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="type" class="form-label">
                            Type <span class="text-danger">*</span>
                        </label>
                            <select name="type" class="form-select" required>
                                <option value="product">Product</option>
                                <option value="service">Service</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Unit Price</label>
                            <input type="number" name="unit_price" class="form-control" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Cost Price</label>
                            <input type="number" name="cost_price" class="form-control" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tax</label>
                            <select name="tax_id" class="form-select">
                                <option value="">No Tax</option>
                                <?php foreach ($taxes as $tax): ?>
                                    <option value="<?= $tax['id'] ?>"><?= htmlspecialchars($tax['tax_name']) ?> (<?= $tax['rate'] ?>%)</option>
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
                                    <option value="<?= $account['id'] ?>"><?= htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="inventory_tracked" id="inventory_tracked" onchange="toggleInventory()">
                    <label class="form-check-label" for="inventory_tracked">Track Inventory</label>
                </div>

                <div id="inventory_fields" style="display: none;">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Stock Quantity</label>
                                <input type="number" name="stock_quantity" class="form-control" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Reorder Level</label>
                                <input type="number" name="reorder_level" class="form-control" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Unit of Measure</label>
                                <input type="text" name="unit_of_measure" class="form-control" value="unit">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('products') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Product</button>
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


