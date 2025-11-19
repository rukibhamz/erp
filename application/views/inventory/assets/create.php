<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Add Asset</h1>
        <a href="<?= base_url('inventory/assets') ?>" class="btn btn-primary">
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
    <div class="card-body">
        <form method="POST" action="<?= base_url('inventory/assets/create') ?>">
            <?php echo csrf_field(); ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="asset_name" class="form-label">Asset Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="asset_name" name="asset_name" required>
                </div>
                <div class="col-md-6">
                    <label for="asset_category" class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select" id="asset_category" name="asset_category" required>
                        <option value="equipment">Equipment</option>
                        <option value="vehicle">Vehicle</option>
                        <option value="furniture">Furniture</option>
                        <option value="it">IT Equipment</option>
                        <option value="building">Building</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="item_id" class="form-label">Item (Optional)</label>
                    <select class="form-select" id="item_id" name="item_id">
                        <option value="0">No Item</option>
                        <?php foreach ($items ?? [] as $item): ?>
                            <option value="<?= $item['id'] ?>">
                                <?= htmlspecialchars($item['item_name']) ?> (<?= htmlspecialchars($item['sku']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Link to an existing item</small>
                </div>
                <div class="col-md-4">
                    <label for="supplier_id" class="form-label">Supplier (Optional)</label>
                    <select class="form-select" id="supplier_id" name="supplier_id">
                        <option value="0">No Supplier</option>
                        <?php foreach ($suppliers ?? [] as $supplier): ?>
                            <option value="<?= $supplier['id'] ?>">
                                <?= htmlspecialchars($supplier['supplier_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="location_id" class="form-label">Location</label>
                    <select class="form-select" id="location_id" name="location_id">
                        <option value="0">No Location</option>
                        <?php foreach ($locations ?? [] as $location): ?>
                            <option value="<?= $location['id'] ?>">
                                <?= htmlspecialchars($location['Location_name'] ?? $location['property_name'] ?? 'N/A') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="purchase_date" class="form-label">Purchase Date</label>
                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                           value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label for="purchase_cost" class="form-label">Purchase Cost <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="purchase_cost" 
                           name="purchase_cost" required min="0">
                </div>
                <div class="col-md-4">
                    <label for="salvage_value" class="form-label">Salvage Value</label>
                    <input type="number" step="0.01" class="form-control" id="salvage_value" 
                           name="salvage_value" value="0" min="0">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="depreciation_method" class="form-label">Depreciation Method</label>
                    <select class="form-select" id="depreciation_method" name="depreciation_method">
                        <option value="straight_line">Straight Line</option>
                        <option value="declining_balance">Declining Balance</option>
                        <option value="units_of_production">Units of Production</option>
                        <option value="none">No Depreciation</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="useful_life_years" class="form-label">Useful Life (Years)</label>
                    <input type="number" class="form-control" id="useful_life_years" 
                           name="useful_life_years" value="5" min="1">
                </div>
                <div class="col-md-4">
                    <label for="custodian_id" class="form-label">Custodian (Optional)</label>
                    <input type="number" class="form-control" id="custodian_id" name="custodian_id" 
                           placeholder="User ID">
                    <small class="text-muted">User responsible for this asset</small>
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('inventory/assets') ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Asset
                </button>
            </div>
        </form>
    </div>
</div>


