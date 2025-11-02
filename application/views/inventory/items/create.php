<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Item</h1>
        <a href="<?= base_url('inventory/items') ?>" class="btn btn-outline-secondary">
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
        <form action="<?= base_url('inventory/items/create') ?>" method="POST">
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
                            <input type="text" class="form-control" id="sku" name="sku" placeholder="Leave blank for auto-generation">
                            <small class="text-muted">Auto-generated if left blank</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="item_name" class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="item_name" name="item_name" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="item_type" class="form-label">Item Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="item_type" name="item_type" required>
                                <option value="inventory">Inventory Item</option>
                                <option value="non_inventory">Non-Inventory Item</option>
                                <option value="service">Service</option>
                                <option value="fixed_asset">Fixed Asset</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="subcategory" class="form-label">Subcategory</label>
                            <input type="text" class="form-control" id="subcategory" name="subcategory">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="barcode" class="form-label">Barcode</label>
                            <input type="text" class="form-control" id="barcode" name="barcode">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="brand" class="form-label">Brand</label>
                            <input type="text" class="form-control" id="brand" name="brand">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="manufacturer" class="form-label">Manufacturer</label>
                            <input type="text" class="form-control" id="manufacturer" name="manufacturer">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="model_number" class="form-label">Model Number</label>
                            <input type="text" class="form-control" id="model_number" name="model_number">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="unit_of_measure" class="form-label">Unit of Measure</label>
                            <select class="form-select" id="unit_of_measure" name="unit_of_measure">
                                <option value="each">Each</option>
                                <option value="box">Box</option>
                                <option value="kg">Kilogram (kg)</option>
                                <option value="g">Gram (g)</option>
                                <option value="liter">Liter</option>
                                <option value="ml">Milliliter (ml)</option>
                                <option value="meter">Meter</option>
                                <option value="cm">Centimeter (cm)</option>
                                <option value="piece">Piece</option>
                                <option value="set">Set</option>
                                <option value="pack">Pack</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="item_status" class="form-label">Status</label>
                            <select class="form-select" id="item_status" name="item_status">
                                <option value="active">Active</option>
                                <option value="discontinued">Discontinued</option>
                                <option value="out_of_stock">Out of Stock</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Pricing Tab -->
                <div class="tab-pane fade" id="pricing">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cost_price" class="form-label">Cost Price</label>
                            <input type="number" step="0.01" class="form-control" id="cost_price" name="cost_price" value="0">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="selling_price" class="form-label">Selling Price</label>
                            <input type="number" step="0.01" class="form-control" id="selling_price" name="selling_price" value="0">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="retail_price" class="form-label">Retail Price</label>
                            <input type="number" step="0.01" class="form-control" id="retail_price" name="retail_price" value="0">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="wholesale_price" class="form-label">Wholesale Price</label>
                            <input type="number" step="0.01" class="form-control" id="wholesale_price" name="wholesale_price" value="0">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="costing_method" class="form-label">Costing Method</label>
                            <select class="form-select" id="costing_method" name="costing_method">
                                <option value="weighted_average">Weighted Average</option>
                                <option value="fifo">FIFO (First In First Out)</option>
                                <option value="lifo">LIFO (Last In First Out)</option>
                                <option value="standard">Standard Cost</option>
                                <option value="actual">Actual Cost</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Stock Control Tab -->
                <div class="tab-pane fade" id="stock">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="reorder_point" class="form-label">Reorder Point</label>
                            <input type="number" step="0.01" class="form-control" id="reorder_point" name="reorder_point" value="0">
                            <small class="text-muted">Minimum stock level before reordering</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="reorder_quantity" class="form-label">Reorder Quantity</label>
                            <input type="number" step="0.01" class="form-control" id="reorder_quantity" name="reorder_quantity" value="0">
                            <small class="text-muted">Suggested purchase quantity</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="safety_stock" class="form-label">Safety Stock</label>
                            <input type="number" step="0.01" class="form-control" id="safety_stock" name="safety_stock" value="0">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="max_stock" class="form-label">Maximum Stock</label>
                            <input type="number" step="0.01" class="form-control" id="max_stock" name="max_stock">
                            <small class="text-muted">Optional - Maximum stock level</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="lead_time_days" class="form-label">Lead Time (Days)</label>
                            <input type="number" class="form-control" id="lead_time_days" name="lead_time_days" value="0">
                            <small class="text-muted">Days to restock after ordering</small>
                        </div>
                    </div>
                </div>
                
                <!-- Tracking Tab -->
                <div class="tab-pane fade" id="tracking">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="track_serial" name="track_serial" value="1">
                                <label class="form-check-label" for="track_serial">
                                    Track Serial Numbers
                                </label>
                                <small class="text-muted d-block">Enable for high-value items requiring unique serial tracking</small>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="track_batch" name="track_batch" value="1">
                                <label class="form-check-label" for="track_batch">
                                    Track Batch/Lot Numbers
                                </label>
                                <small class="text-muted d-block">Enable for items with batch/lot tracking requirements</small>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="expiry_tracking" name="expiry_tracking" value="1">
                                <label class="form-check-label" for="expiry_tracking">
                                    Track Expiry Dates
                                </label>
                                <small class="text-muted d-block">Enable for perishable items</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('inventory/items') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Item
                </button>
            </div>
        </form>
    </div>
</div>

