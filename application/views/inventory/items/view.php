<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0"><?= htmlspecialchars($item['item_name']) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('inventory/items/edit/' . $item['id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="<?= base_url('inventory/items') ?>" class="btn btn-outline-secondary">
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
                <h5 class="card-title mb-0">Item Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">SKU:</dt>
                    <dd class="col-sm-8"><strong><?= htmlspecialchars($item['sku']) ?></strong></dd>
                    
                    <dt class="col-sm-4">Item Type:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-secondary">
                            <?= ucfirst(str_replace('_', ' ', $item['item_type'])) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4">Category:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($item['category'] ?: '-') ?></dd>
                    
                    <?php if ($item['description']): ?>
                        <dt class="col-sm-4">Description:</dt>
                        <dd class="col-sm-8"><?= nl2br(htmlspecialchars($item['description'])) ?></dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-4">Unit of Measure:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($item['unit_of_measure']) ?></dd>
                    
                    <dt class="col-sm-4">Barcode:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($item['barcode'] ?: '-') ?></dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= 
                            $item['item_status'] === 'active' ? 'success' : 
                            ($item['item_status'] === 'discontinued' ? 'secondary' : 'danger') ?>">
                            <?= ucfirst(str_replace('_', ' ', $item['item_status'])) ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
        
        <!-- Stock Levels by Location -->
        <?php if (!empty($stock_levels)): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stock Levels by Location</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Location</th>
                                    <th>Quantity</th>
                                    <th>Reserved</th>
                                    <th>Available</th>
                                    <th>Reorder Point</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stock_levels as $stock): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($stock['location_name']) ?></td>
                                        <td><?= number_format($stock['quantity'], 2) ?></td>
                                        <td><?= number_format($stock['reserved_qty'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $stock['available_qty'] <= $stock['reorder_point'] ? 'danger' : 
                                                ($stock['available_qty'] <= ($stock['reorder_point'] * 1.5) ? 'warning' : 'success') ?>">
                                                <?= number_format($stock['available_qty'], 2) ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($stock['reorder_point'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Recent Transactions -->
        <?php if (!empty($transactions)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Transactions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $trans): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($trans['transaction_date'])) ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= ucfirst($trans['transaction_type']) ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($trans['quantity'], 2) ?></td>
                                        <td><?= htmlspecialchars($trans['location_from_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($trans['location_to_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($trans['reference_number'] ?: '-') ?></td>
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
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Pricing</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-6">Cost Price:</dt>
                    <dd class="col-6"><?= format_currency($item['cost_price']) ?></dd>
                    
                    <dt class="col-6">Selling Price:</dt>
                    <dd class="col-6"><strong><?= format_currency($item['selling_price']) ?></strong></dd>
                    
                    <dt class="col-6">Retail Price:</dt>
                    <dd class="col-6"><?= format_currency($item['retail_price']) ?></dd>
                    
                    <dt class="col-6">Wholesale Price:</dt>
                    <dd class="col-6"><?= format_currency($item['wholesale_price']) ?></dd>
                </dl>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Stock Control</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-6">Reorder Point:</dt>
                    <dd class="col-6"><?= number_format($item['reorder_point'], 2) ?></dd>
                    
                    <dt class="col-6">Reorder Quantity:</dt>
                    <dd class="col-6"><?= number_format($item['reorder_quantity'], 2) ?></dd>
                    
                    <dt class="col-6">Safety Stock:</dt>
                    <dd class="col-6"><?= number_format($item['safety_stock'], 2) ?></dd>
                    
                    <?php if ($item['max_stock']): ?>
                        <dt class="col-6">Max Stock:</dt>
                        <dd class="col-6"><?= number_format($item['max_stock'], 2) ?></dd>
                    <?php endif; ?>
                    
                    <dt class="col-6">Lead Time:</dt>
                    <dd class="col-6"><?= $item['lead_time_days'] ?> days</dd>
                </dl>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="<?= base_url('inventory/receive?item_id=' . $item['id']) ?>" class="btn btn-success w-100 mb-2">
                    <i class="bi bi-box-arrow-in-down"></i> Receive Stock
                </a>
                <a href="<?= base_url('inventory/issue?item_id=' . $item['id']) ?>" class="btn btn-danger w-100 mb-2">
                    <i class="bi bi-box-arrow-up"></i> Issue Stock
                </a>
                <a href="<?= base_url('inventory/transfer?item_id=' . $item['id']) ?>" class="btn btn-info w-100 mb-2">
                    <i class="bi bi-arrow-left-right"></i> Transfer Stock
                </a>
                <a href="<?= base_url('inventory/purchase-orders/create?item_id=' . $item['id']) ?>" class="btn btn-primary w-100">
                    <i class="bi bi-cart-plus"></i> Create PO
                </a>
            </div>
        </div>
    </div>
</div>

