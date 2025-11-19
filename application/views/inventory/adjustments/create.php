<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Stock Adjustment</h1>
        <a href="<?= base_url('inventory/adjustments') ?>" class="btn btn-primary">
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
        <form method="POST" action="<?= base_url('inventory/adjustments/create') ?>">
            <?php echo csrf_field(); ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="item_id" class="form-label">Item <span class="text-danger">*</span></label>
                    <select class="form-select" id="item_id" name="item_id" required>
                        <option value="">Select Item</option>
                        <?php foreach ($items ?? [] as $item): ?>
                            <option value="<?= $item['id'] ?>" <?= ($selected_item_id ?? null) == $item['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['sku'] ?? '') ?> - <?= htmlspecialchars($item['item_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="location_id" class="form-label">Location <span class="text-danger">*</span></label>
                    <select class="form-select" id="location_id" name="location_id" required>
                        <option value="">Select Location</option>
                        <?php foreach ($locations ?? [] as $location): ?>
                            <option value="<?= $location['id'] ?>" <?= ($selected_location_id ?? null) == $location['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($location['Location_name'] ?? $location['property_name'] ?? 'N/A') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Current Stock</label>
                    <input type="text" class="form-control" id="current_stock" readonly 
                           value="Select item and location to see current stock">
                </div>
                <div class="col-md-4">
                    <label for="quantity_after" class="form-label">New Quantity <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="quantity_after" 
                           name="quantity_after" required min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Adjustment</label>
                    <input type="text" class="form-control" id="adjustment_qty" readonly>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                    <select class="form-select" id="reason" name="reason" required>
                        <option value="correction">Correction</option>
                        <option value="damage">Damage</option>
                        <option value="theft">Theft</option>
                        <option value="expired">Expired</option>
                        <option value="found">Found</option>
                        <option value="stock_take">Stock Take</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="notes" class="form-label">Notes</label>
                    <input type="text" class="form-control" id="notes" name="notes" 
                           placeholder="Additional notes (optional)">
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('inventory/adjustments') ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemSelect = document.getElementById('item_id');
    const locationSelect = document.getElementById('location_id');
    const currentStockInput = document.getElementById('current_stock');
    const quantityAfterInput = document.getElementById('quantity_after');
    const adjustmentQtyInput = document.getElementById('adjustment_qty');

    function loadStockLevel() {
        const itemId = itemSelect.value;
        const locationId = locationSelect.value;
        
        if (itemId && locationId) {
            fetch('<?= base_url('inventory/adjustments/get-stock-level') ?>?item_id=' + itemId + '&location_id=' + locationId)
                .then(response => response.json())
                .then(data => {
                    currentStockInput.value = data.quantity || 0;
                    calculateAdjustment();
                })
                .catch(error => {
                    currentStockInput.value = 'Error loading stock';
                });
        } else {
            currentStockInput.value = 'Select item and location to see current stock';
            adjustmentQtyInput.value = '';
        }
    }

    function calculateAdjustment() {
        const currentStock = parseFloat(currentStockInput.value) || 0;
        const quantityAfter = parseFloat(quantityAfterInput.value) || 0;
        const adjustment = quantityAfter - currentStock;
        
        adjustmentQtyInput.value = adjustment.toFixed(2);
        if (adjustmentQtyInput.value) {
            adjustmentQtyInput.classList.remove('text-success', 'text-danger');
            adjustmentQtyInput.classList.add(adjustment >= 0 ? 'text-success' : 'text-danger');
        }
    }

    itemSelect.addEventListener('change', loadStockLevel);
    locationSelect.addEventListener('change', loadStockLevel);
    quantityAfterInput.addEventListener('input', calculateAdjustment);
    
    // Load initial stock if item and location are preselected
    if (itemSelect.value && locationSelect.value) {
        loadStockLevel();
    }
});
</script>

