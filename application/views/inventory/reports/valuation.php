<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Inventory Valuation Report</h1>
        <p class="text-muted">Current valuation of all stock based on weighted average cost.</p>
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
                        <th>Category</th>
                        <th class="text-end">Total Stock</th>
                        <th class="text-end">Average Cost</th>
                        <th class="text-end">Total Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandTotalValue = 0;
                    $grandTotalQty = 0;
                    foreach ($valuation as $row): 
                        $grandTotalValue += $row['total_value'];
                        $grandTotalQty += $row['total_quantity'];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['sku']) ?></td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= htmlspecialchars($row['category']) ?></span></td>
                            <td class="text-end"><?= number_format($row['total_quantity'], 2) ?></td>
                            <td class="text-end">₦<?= number_format($row['average_cost'], 2) ?></td>
                            <td class="text-end"><strong>₦<?= number_format($row['total_value'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3">Grand Total</th>
                        <th class="text-end"><?= number_format($grandTotalQty, 2) ?></th>
                        <th></th>
                        <th class="text-end"><h5>₦<?= number_format($grandTotalValue, 2) ?></h5></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
