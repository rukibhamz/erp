<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit Item</h1>
        <a href="<?= base_url('inventory/items/view/' . $item['id']) ?>" class="btn btn-outline-secondary">
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
        <form action="<?= base_url('inventory/items/edit/' . $item['id']) ?>" method="POST" id="editItemForm">
            <?php echo csrf_field(); ?>
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#basic" type="button">Basic Info</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pricing" type="button">Pricing</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#stock" type="button">Stock Control</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tracking" type="button">Tracking</button>
                </li>
            </ul>
            
            <div class="tab-content">
                <!-- Basic Info Tab -->
                <div class="tab-pane fade show active" id="basic">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="sku" class="form-label">SKU/Part Number</label>
                            <input type="text" class="form-control" id="sku" name="sku" value="<?= htmlspecialchars($item['sku']) ?>" readonly>
                            <small class="text-muted">SKU cannot be changed</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="item_name" class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="item_name" name="item_name" value="<?= htmlspecialchars($item['item_name']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="item_type" class="form-label">Item Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="item_type" name="item_type" required>
                                <option value="inventory" <?= $item['item_type'] === 'inventory' ? 'selected' : '' ?>>Inventory Item</option>
                                <option value="non_inventory" <?= $item['item_type'] === 'non_inventory' ? 'selected' : '' ?>>Non-Inventory Item</option>
                                <option value="service" <?= $item['item_type'] === 'service' ? 'selected' : '' ?>>Service</option>
                                <option value="fixed_asset" <?= $item['item_type'] === 'fixed_asset' ? 'selected' : '' ?>>Fixed Asset</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category" value="<?= htmlspecialchars($item['category'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="subcategory" class="form-label">Subcategory</label>
                            <input type="text" class="form-control" id="subcategory" name="subcategory" value="<?= htmlspecialchars($item['subcategory'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="barcode" class="form-label">Barcode</label>
                            <input type="text" class="form-control" id="barcode" name="barcode" value="<?= htmlspecialchars($item['barcode'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="brand" class="form-label">Brand</label>
                            <input type="text" class="form-control" id="brand" name="brand" value="<?= htmlspecialchars($item['brand'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="manufacturer" class="form-label">Manufacturer</label>
                            <input type="text" class="form-control" id="manufacturer" name="manufacturer" value="<?= htmlspecialchars($item['manufacturer'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="model_number" class="form-label">Model Number</label>
                            <input type="text" class="form-control" id="model_number" name="model_number" value="<?= htmlspecialchars($item['model_number'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="unit_of_measure" class="form-label">Unit of Measure</label>
                            <select class="form-select" id="unit_of_measure" name="unit_of_measure">
                                <option value="each" <?= $item['unit_of_measure'] === 'each' ? 'selected' : '' ?>>Each</option>
                                <option value="box" <?= $item['unit_of_measure'] === 'box' ? 'selected' : '' ?>>Box</option>
                                <option value="kg" <?= $item['unit_of_measure'] === 'kg' ? 'selected' : '' ?>>Kilogram (kg)</option>
                                <option value="g" <?= $item['unit_of_measure'] === 'g' ? 'selected' : '' ?>>Gram (g)</option>
                                <option value="liter" <?= $item['unit_of_measure'] === 'liter' ? 'selected' : '' ?>>Liter</option>
                                <option value="ml" <?= $item['unit_of_measure'] === 'ml' ? 'selected' : '' ?>>Milliliter (ml)</option>
                                <option value="meter" <?= $item['unit_of_measure'] === 'meter' ? 'selected' : '' ?>>Meter</option>
                                <option value="cm" <?= $item['unit_of_measure'] === 'cm' ? 'selected' : '' ?>>Centimeter (cm)</option>
                                <option value="piece" <?= $item['unit_of_measure'] === 'piece' ? 'selected' : '' ?>>Piece</option>
                                <option value="set" <?= $item['unit_of_measure'] === 'set' ? 'selected' : '' ?>>Set</option>
                                <option value="pack" <?= $item['unit_of_measure'] === 'pack' ? 'selected' : '' ?>>Pack</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="item_status" class="form-label">Status</label>
                            <select class="form-select" id="item_status" name="item_status">
                                <option value="active" <?= $item['item_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="discontinued" <?= $item['item_status'] === 'discontinued' ? 'selected' : '' ?>>Discontinued</option>
                                <option value="out_of_stock" <?= $item['item_status'] === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Pricing Tab -->
                <div class="tab-pane fade" id="pricing">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cost_price" class="form-label">Cost Price</label>
                            <input type="number" step="0.01" class="form-control" id="cost_price" name="cost_price" value="<?= $item['cost_price'] ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="selling_price" class="form-label">Selling Price</label>
                            <input type="number" step="0.01" class="form-control" id="selling_price" name="selling_price" value="<?= $item['selling_price'] ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="retail_price" class="form-label">Retail Price</label>
                            <input type="number" step="0.01" class="form-control" id="retail_price" name="retail_price" value="<?= $item['retail_price'] ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="wholesale_price" class="form-label">Wholesale Price</label>
                            <input type="number" step="0.01" class="form-control" id="wholesale_price" name="wholesale_price" value="<?= $item['wholesale_price'] ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="wholesale_moq" class="form-label">Wholesale MOQ</label>
                            <input type="number" step="0.01" class="form-control" id="wholesale_moq" name="wholesale_moq" value="<?= $item['wholesale_moq'] ?? 0 ?>">
                        </div>

                        <div class="col-md-6 pt-4">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_wholesale_enabled" id="isWholesale" <?= !empty($item['is_wholesale_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isWholesale">Enable Wholesale Pricing</label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="costing_method" class="form-label">Costing Method</label>
                            <select class="form-select" id="costing_method" name="costing_method">
                                <option value="weighted_average" <?= $item['costing_method'] === 'weighted_average' ? 'selected' : '' ?>>Weighted Average</option>
                                <option value="fifo" <?= $item['costing_method'] === 'fifo' ? 'selected' : '' ?>>FIFO</option>
                                <option value="lifo" <?= $item['costing_method'] === 'lifo' ? 'selected' : '' ?>>LIFO</option>
                                <option value="standard" <?= $item['costing_method'] === 'standard' ? 'selected' : '' ?>>Standard Cost</option>
                                <option value="actual" <?= $item['costing_method'] === 'actual' ? 'selected' : '' ?>>Actual Cost</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Stock Control Tab -->
                <div class="tab-pane fade" id="stock">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="reorder_point" class="form-label">Reorder Point</label>
                            <input type="number" step="0.01" class="form-control" id="reorder_point" name="reorder_point" value="<?= $item['reorder_point'] ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="reorder_quantity" class="form-label">Reorder Quantity</label>
                            <input type="number" step="0.01" class="form-control" id="reorder_quantity" name="reorder_quantity" value="<?= $item['reorder_quantity'] ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="safety_stock" class="form-label">Safety Stock</label>
                            <input type="number" step="0.01" class="form-control" id="safety_stock" name="safety_stock" value="<?= $item['safety_stock'] ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="max_stock" class="form-label">Maximum Stock</label>
                            <input type="number" step="0.01" class="form-control" id="max_stock" name="max_stock" value="<?= $item['max_stock'] ?? '' ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="lead_time_days" class="form-label">Lead Time (Days)</label>
                            <input type="number" class="form-control" id="lead_time_days" name="lead_time_days" value="<?= $item['lead_time_days'] ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Tracking Tab -->
                <div class="tab-pane fade" id="tracking">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="track_serial" name="track_serial" value="1" <?= $item['track_serial'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="track_serial">
                                    Track Serial Numbers
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="track_batch" name="track_batch" value="1" <?= $item['track_batch'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="track_batch">
                                    Track Batch/Lot Numbers
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="expiry_tracking" name="expiry_tracking" value="1" <?= $item['expiry_tracking'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="expiry_tracking">
                                    Track Expiry Dates
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('inventory/items/view/' . $item['id']) ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Update Item
                </button>
            </div>
        </form>
    </div>
</div>

