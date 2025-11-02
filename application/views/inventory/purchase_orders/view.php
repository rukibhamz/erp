<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Purchase Order: <?= htmlspecialchars($po['po_number']) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('inventory/goods-receipts/create?po_id=' . $po['id']) ?>" class="btn btn-success">
                <i class="bi bi-box-arrow-in-down"></i> Receive Goods
            </a>
            <a href="<?= base_url('inventory/purchase-orders') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
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
                <h5 class="card-title mb-0">PO Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">PO Number:</dt>
                    <dd class="col-sm-8"><strong><?= htmlspecialchars($po['po_number']) ?></strong></dd>
                    
                    <dt class="col-sm-4">Supplier:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($po['supplier_name'] ?? 'N/A') ?></dd>
                    
                    <dt class="col-sm-4">Order Date:</dt>
                    <dd class="col-sm-8"><?= date('M d, Y', strtotime($po['order_date'])) ?></dd>
                    
                    <dt class="col-sm-4">Expected Date:</dt>
                    <dd class="col-sm-8"><?= $po['expected_date'] ? date('M d, Y', strtotime($po['expected_date'])) : '-' ?></dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= 
                            $po['status'] === 'closed' ? 'success' : 
                            ($po['status'] === 'received' ? 'info' : 
                            ($po['status'] === 'partial' ? 'warning' : 
                            ($po['status'] === 'sent' ? 'primary' : 'secondary'))) ?>">
                            <?= ucfirst($po['status']) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4">Total Amount:</dt>
                    <dd class="col-sm-8"><strong><?= format_currency($po['total_amount']) ?></strong></dd>
                </dl>
            </div>
        </div>
        
        <!-- PO Items -->
        <?php if (!empty($po_items)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>SKU</th>
                                    <th>Quantity</th>
                                    <th>Received</th>
                                    <th>Pending</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($po_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= htmlspecialchars($item['sku']) ?></td>
                                        <td><?= number_format($item['quantity'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-success"><?= number_format($item['quantity_received'], 2) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $item['quantity_pending'] > 0 ? 'warning' : 'success' ?>">
                                                <?= number_format($item['quantity_pending'], 2) ?>
                                            </span>
                                        </td>
                                        <td><?= format_currency($item['unit_price']) ?></td>
                                        <td><?= format_currency($item['line_total']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="6" class="text-end">Total:</th>
                                    <th><?= format_currency($po['total_amount']) ?></th>
                                </tr>
                            </tfoot>
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
                <a href="<?= base_url('inventory/goods-receipts/create?po_id=' . $po['id']) ?>" class="btn btn-success w-100 mb-2">
                    <i class="bi bi-box-arrow-in-down"></i> Receive Goods
                </a>
                <a href="<?= base_url('inventory/purchase-orders') ?>" class="btn btn-outline-dark w-100">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

