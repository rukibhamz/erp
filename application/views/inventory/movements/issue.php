<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Issue Stock</h1>
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
        <form action="<?= base_url('inventory/issue') ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="item_id" class="form-label">Item <span class="text-danger">*</span></label>
                    <select class="form-select" id="item_id" name="item_id" required onchange="loadStockInfo(this.value, document.getElementById('location_id').value)">
                        <option value="">Select Item</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= $item['id'] ?>" <?= ($selected_item_id ?? null) == $item['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['sku']) ?> - <?= htmlspecialchars($item['item_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="location_id" class="form-label">From Location <span class="text-danger">*</span></label>
                    <select class="form-select" id="location_id" name="location_id" required onchange="loadStockInfo(document.getElementById('item_id').value, this.value)">
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= $location['id'] ?>" <?= ($selected_location_id ?? null) == $location['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($location['location_name']) ?> (<?= htmlspecialchars($location['location_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-12">
                    <div id="stock_info" class="alert alert-info" style="display: none;">
                        <strong>Available Stock:</strong> <span id="available_qty">0</span>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="quantity" name="quantity" required min="0.01">
                </div>
                
                <div class="col-md-6">
                    <label for="issue_type" class="form-label">Issue Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="issue_type" name="issue_type" required>
                        <option value="sale">Sale</option>
                        <option value="internal_use">Internal Use</option>
                        <option value="donation">Donation</option>
                        <option value="damage">Damage</option>
                        <option value="theft">Theft</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="reference_type" class="form-label">Reference Type</label>
                    <select class="form-select" id="reference_type" name="reference_type">
                        <option value="">None</option>
                        <option value="sales_order">Sales Order</option>
                        <option value="work_order">Work Order</option>
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
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('inventory') ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-check-circle"></i> Issue Stock
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
</script>

