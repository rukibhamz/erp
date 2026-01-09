<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Reorder Level Report</h1>
        <p class="text-muted">Items with stock levels below or equal to their reorder points.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('inventory/reports') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Reports
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>SKU</th>
                        <th>Item Name</th>
                        <th class="text-end">Current Stock</th>
                        <th class="text-end">Reorder Level</th>
                        <th class="text-end">Variance</th>
                        <th>Action Required</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        $variance = $item['total_stock'] - $item['reorder_point'];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($item['sku']) ?></td>
                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                            <td class="text-end">
                                <span class="badge bg-danger">
                                    <?= number_format($item['total_stock'], 2) ?>
                                </span>
                            </td>
                            <td class="text-end"><?= number_format($item['reorder_point'], 2) ?></td>
                            <td class="text-end text-danger"><?= number_format($variance, 2) ?></td>
                            <td>
                                <a href="<?= base_url('inventory/purchase-orders/create?item_id=' . $item['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-cart-plus"></i> Order Stock
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
