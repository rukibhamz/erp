<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Fixed Asset</h1>
        <a href="<?= base_url('inventory/assets') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
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

<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Asset Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('inventory/assets/create') ?>
            <?php echo csrf_field(); ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Asset Name <span class="text-danger">*</span></label>
                    <input type="text" name="asset_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Asset Category <span class="text-danger">*</span></label>
                    <select name="asset_category" class="form-select" required>
                        <option value="equipment">Equipment</option>
                        <option value="vehicle">Vehicle</option>
                        <option value="furniture">Furniture</option>
                        <option value="it">IT Equipment</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Purchase Cost <span class="text-danger">*</span></label>
                    <input type="number" name="purchase_cost" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                    <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">Select Supplier</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['supplier_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <select name="location_id" class="form-select">
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['location_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Depreciation Method</label>
                    <select name="depreciation_method" class="form-select">
                        <option value="straight_line">Straight Line</option>
                        <option value="declining_balance">Declining Balance</option>
                        <option value="none">No Depreciation</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Useful Life (Years)</label>
                    <input type="number" name="useful_life_years" class="form-control" value="5" min="1">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Salvage Value</label>
                    <input type="number" name="salvage_value" class="form-control" step="0.01" min="0" value="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Link to Inventory Item</label>
                    <select name="item_id" class="form-select">
                        <option value="">None</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['item_name'] . ' (' . $item['sku'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= base_url('inventory/assets') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-dark">Create Asset</button>
            </div>
        </form>
    </div>
</div>

