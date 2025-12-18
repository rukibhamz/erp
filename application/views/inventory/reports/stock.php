<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Stock on Hand Report</h1>
        <p class="text-muted">List of all items and their current aggregate stock levels.</p>
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
                        <th>Type</th>
                        <th>UOM</th>
                        <th class="text-end">Reorder Point</th>
                        <th class="text-end">Stock on Hand</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $row): 
                        $lowStock = $row['total_stock'] <= $row['reorder_point'];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['sku']) ?></td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td><?= ucfirst($row['item_type']) ?></td>
                            <td><?= htmlspecialchars($row['unit_of_measure']) ?></td>
                            <td class="text-end text-muted"><?= number_format($row['reorder_point'], 2) ?></td>
                            <td class="text-end">
                                <span class="badge <?= $lowStock ? 'bg-danger' : 'bg-success' ?>">
                                    <?= number_format($row['total_stock'], 2) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($lowStock): ?>
                                    <span class="text-danger small"><i class="bi bi-exclamation-triangle"></i> Low Stock</span>
                                <?php else: ?>
                                    <span class="text-success small"><i class="bi bi-check-circle"></i> Healthy</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
