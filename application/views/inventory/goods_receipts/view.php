<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Goods Receipt: <?= htmlspecialchars($grn['grn_number']) ?></h1>
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

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">GRN Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">GRN Number:</dt>
                    <dd class="col-sm-8"><strong><?= htmlspecialchars($grn['grn_number']) ?></strong></dd>
                    
                    <?php if ($grn['po_number']): ?>
                        <dt class="col-sm-4">PO Number:</dt>
                        <dd class="col-sm-8">
                            <a href="<?= base_url('inventory/purchase-orders/view/' . $grn['po_id']) ?>">
                                <?= htmlspecialchars($grn['po_number']) ?>
                            </a>
                        </dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-4">Supplier:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($grn['supplier_name'] ?? 'N/A') ?></dd>
                    
                    <dt class="col-sm-4">Receipt Date:</dt>
                    <dd class="col-sm-8"><?= date('M d, Y', strtotime($grn['receipt_date'])) ?></dd>
                    
                    <dt class="col-sm-4">Location:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($grn['location_name'] ?? '-') ?></dd>
                    
                    <?php if ($grn['quality_inspection']): ?>
                        <dt class="col-sm-4">Quality Inspection:</dt>
                        <dd class="col-sm-8"><?= nl2br(htmlspecialchars($grn['quality_inspection'])) ?></dd>
                    <?php endif; ?>
                    
                    <?php if ($grn['notes']): ?>
                        <dt class="col-sm-4">Notes:</dt>
                        <dd class="col-sm-8"><?= nl2br(htmlspecialchars($grn['notes'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        
        <!-- GRN Items -->
        <?php if (!empty($grn_items)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Items Received</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>SKU</th>
                                    <th>Quantity</th>
                                    <th>Unit Cost</th>
                                    <th>Total</th>
                                    <th>Batch</th>
                                    <th>Expiry</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grn_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= htmlspecialchars($item['sku']) ?></td>
                                        <td><?= number_format($item['quantity'], 2) ?></td>
                                        <td><?= format_currency($item['unit_cost']) ?></td>
                                        <td><?= format_currency($item['quantity'] * $item['unit_cost']) ?></td>
                                        <td><?= htmlspecialchars($item['batch_number'] ?: '-') ?></td>
                                        <td><?= $item['expiry_date'] ? date('M d, Y', strtotime($item['expiry_date'])) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="<?= base_url('inventory/goods-receipts') ?>" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
                <?php if ($grn['po_id']): ?>
                    <a href="<?= base_url('inventory/purchase-orders/view/' . $grn['po_id']) ?>" class="btn btn-outline-info w-100">
                        <i class="bi bi-file-earmark-text"></i> View PO
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

