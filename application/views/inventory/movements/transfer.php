<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Transfer Stock</h1>
        <a href="<?= base_url('inventory') ?>" class="btn btn-outline-secondary">
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
        <form action="<?= base_url('inventory/transfer') ?>" >
            <?php echo csrf_field(); ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="item_id" class="form-label">Item <span class="text-danger">*</span></label>
                    <select class="form-select" id="item_id" name="item_id" required onchange="loadStockInfo(this.value, document.getElementById('location_from_id').value)">
                        <option value="">Select Item</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= $item['id'] ?>" <?= ($selected_item_id ?? null) == $item['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['sku']) ?> - <?= htmlspecialchars($item['item_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="location_from_id" class="form-label">From Location <span class="text-danger">*</span></label>
                    <select class="form-select" id="location_from_id" name="location_from_id" required onchange="loadStockInfo(document.getElementById('item_id').value, this.value); disableSameLocation();">
                        <option value="">Select Source Location</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= $location['id'] ?>">
                                <?= htmlspecialchars($location['location_name']) ?> (<?= htmlspecialchars($location['location_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="location_to_id" class="form-label">To Location <span class="text-danger">*</span></label>
                    <select class="form-select" id="location_to_id" name="location_to_id" required onchange="disableSameLocation();">
                        <option value="">Select Destination Location</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= $location['id'] ?>">
                                <?= htmlspecialchars($location['location_name']) ?> (<?= htmlspecialchars($location['location_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="quantity" name="quantity" required min="0.01">
                </div>
                
                <div class="col-md-12">
                    <div id="stock_info" class="alert alert-info" style="display: none;">
                        <strong>Available Stock:</strong> <span id="available_qty">0</span>
                    </div>
                </div>
                
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('inventory') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-info">
                    <i class="bi bi-check-circle"></i> Transfer Stock
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function loadStockInfo(itemId, locationId) {
    if (itemId && locationId) {
        // TODO: AJAX call to get stock info
        document.getElementById('stock_info').style.display = 'block';
    } else {
        document.getElementById('stock_info').style.display = 'none';
    }
}

function disableSameLocation() {
    const fromLocation = document.getElementById('location_from_id').value;
    const toLocation = document.getElementById('location_to_id');
    
    Array.from(toLocation.options).forEach(option => {
        option.disabled = (option.value === fromLocation && fromLocation !== '');
    });
}
</script>

