<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Stock Adjustment</h1>
        <a href="<?= base_url('inventory/adjustments') ?>" class="btn btn-outline-secondary">
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
        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Adjustment Details</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('inventory/adjustments/create') ?>
            <?php echo csrf_field(); ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Item <span class="text-danger">*</span></label>
                    <select name="item_id" id="item_id" class="form-select" required>
                        <option value="">Select Item</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= $item['id'] ?>" <?= $selected_item_id == $item['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['item_name'] . ' (' . $item['sku'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Location <span class="text-danger">*</span></label>
                    <select name="location_id" id="location_id" class="form-select" required>
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc['id'] ?>" <?= $selected_location_id == $loc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc['location_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Current Quantity</label>
                    <input type="text" id="current_qty" class="form-control" readonly>
                    <small class="text-muted">This will be auto-filled after selecting item and location</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">New Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="quantity_after" id="quantity_after" class="form-control" step="0.01" min="0" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Adjustment Reason <span class="text-danger">*</span></label>
                    <select name="reason" class="form-select" required>
                        <option value="correction">Correction</option>
                        <option value="damage">Damage</option>
                        <option value="theft">Theft</option>
                        <option value="found">Found Stock</option>
                        <option value="expired">Expired</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Adjustment Amount</label>
                    <input type="text" id="adjustment_qty" class="form-control" readonly>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes about this adjustment"></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= base_url('inventory/adjustments') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-dark">Create Adjustment</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemSelect = document.getElementById('item_id');
    const locationSelect = document.getElementById('location_id');
    const currentQtyInput = document.getElementById('current_qty');
    const quantityAfterInput = document.getElementById('quantity_after');
    const adjustmentQtyInput = document.getElementById('adjustment_qty');

    function updateCurrentStock() {
        const itemId = itemSelect.value;
        const locationId = locationSelect.value;

        if (itemId && locationId) {
            fetch(`<?= base_url('inventory/adjustments/getStockLevel') ?>?item_id=${itemId}&location_id=${locationId}`)
                .then(response => response.json())
                .then(data => {
                    currentQtyInput.value = data.quantity || 0;
                    calculateAdjustment();
                })
                .catch(error => {
                    console.error('Error:', error);
                    currentQtyInput.value = 0;
                });
        } else {
            currentQtyInput.value = '';
            adjustmentQtyInput.value = '';
        }
    }

    function calculateAdjustment() {
        const current = parseFloat(currentQtyInput.value) || 0;
        const after = parseFloat(quantityAfterInput.value) || 0;
        const adjustment = after - current;
        adjustmentQtyInput.value = adjustment.toFixed(2);
    }

    itemSelect.addEventListener('change', updateCurrentStock);
    locationSelect.addEventListener('change', updateCurrentStock);
    quantityAfterInput.addEventListener('input', calculateAdjustment);
});
</script>

