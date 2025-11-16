<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Receive Stock</h1>
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
        <form action="<?= base_url('inventory/receive') ?>
            <?php echo csrf_field(); ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="item_id" class="form-label">Item <span class="text-danger">*</span></label>
                    <select class="form-select" id="item_id" name="item_id" required onchange="loadItemDetails(this.value)">
                        <option value="">Select Item</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= $item['id'] ?>" <?= ($selected_item_id ?? null) == $item['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['sku']) ?> - <?= htmlspecialchars($item['item_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="location_id" class="form-label">Location <span class="text-danger">*</span></label>
                    <select class="form-select" id="location_id" name="location_id" required>
                        <option value="">Select Location</option>
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
                
                <div class="col-md-6">
                    <label for="unit_cost" class="form-label">Unit Cost <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="unit_cost" name="unit_cost" required min="0" oninput="calculateTotal()">
                </div>
                
                <div class="col-md-6">
                    <label for="reference_type" class="form-label">Reference Type</label>
                    <select class="form-select" id="reference_type" name="reference_type">
                        <option value="">None</option>
                        <option value="purchase_order">Purchase Order</option>
                        <option value="goods_receipt">Goods Receipt</option>
                        <option value="adjustment">Adjustment</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="reference_number" class="form-label">Reference Number</label>
                    <input type="text" class="form-control" id="reference_number" name="reference_number">
                </div>
                
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                </div>
                
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <strong>Total Cost:</strong> <span id="total_cost">₦0.00</span>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('inventory') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Receive Stock
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function calculateTotal() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const unitCost = parseFloat(document.getElementById('unit_cost').value) || 0;
    const total = quantity * unitCost;
    document.getElementById('total_cost').textContent = '₦' + total.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function loadItemDetails(itemId) {
    // Can be enhanced to load item details via AJAX
}
</script>

