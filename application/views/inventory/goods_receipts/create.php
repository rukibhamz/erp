<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Goods Receipt</h1>
        <a href="<?= base_url('inventory/goods-receipts') ?>" class="btn btn-outline-secondary">
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

<?php if ($po): ?>
    <div class="alert alert-info">
        <strong>Receiving from Purchase Order:</strong> <?= htmlspecialchars($po['po_number']) ?><br>
        <strong>Supplier:</strong> <?= htmlspecialchars($po['supplier_name'] ?? 'N/A') ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="<?= base_url('inventory/goods-receipts/create') ?>
            <?php echo csrf_field(); ?>" method="POST" id="grnForm">
            <input type="hidden" name="po_id" value="<?= $po['id'] ?? '' ?>">
            
            <div class="row g-3 mb-4">
                <?php if (!$po): ?>
                    <div class="col-md-6">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <select class="form-select" id="supplier_id" name="supplier_id">
                            <option value="">Select Supplier</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>">
                                    <?= htmlspecialchars($supplier['supplier_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="supplier_id" value="<?= $po['supplier_id'] ?>">
                <?php endif; ?>
                
                <div class="col-md-6">
                    <label for="receipt_date" class="form-label">Receipt Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="receipt_date" name="receipt_date" value="<?= date('Y-m-d') ?>" required>
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
                
                <div class="col-12">
                    <label for="quality_inspection" class="form-label">Quality Inspection Notes</label>
                    <textarea class="form-control" id="quality_inspection" name="quality_inspection" rows="2"></textarea>
                </div>
                
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                </div>
            </div>
            
            <!-- Items Table -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Items Received</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($po_items)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Ordered</th>
                                        <th>Received</th>
                                        <th>Unit Cost</th>
                                        <th>Batch Number</th>
                                        <th>Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($po_items as $index => $poItem): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($poItem['item_name']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($poItem['sku']) ?></small>
                                                <input type="hidden" name="items[<?= $index ?>][item_id]" value="<?= $poItem['item_id'] ?>">
                                                <input type="hidden" name="items[<?= $index ?>][po_item_id]" value="<?= $poItem['id'] ?>">
                                            </td>
                                            <td><?= number_format($poItem['quantity_pending'], 2) ?></td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm" 
                                                       name="items[<?= $index ?>][quantity]" 
                                                       max="<?= $poItem['quantity_pending'] ?>" 
                                                       value="<?= $poItem['quantity_pending'] ?>" 
                                                       required min="0.01">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm" 
                                                       name="items[<?= $index ?>][unit_cost]" 
                                                       value="<?= $poItem['unit_price'] ?>" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" 
                                                       name="items[<?= $index ?>][batch_number]">
                                            </td>
                                            <td>
                                                <input type="date" class="form-control form-control-sm" 
                                                       name="items[<?= $index ?>][expiry_date]">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Add items manually or select a Purchase Order.</p>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addItemRow()">
                            <i class="bi bi-plus-circle"></i> Add Item
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= base_url('inventory/goods-receipts') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Create Goods Receipt
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function addItemRow() {
    // TODO: Implement dynamic item row addition for manual GRN
}
</script>

