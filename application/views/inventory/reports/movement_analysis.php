<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Fast/Slow Moving Items</h1>
        <p class="text-muted">Analysis of item turnover based on transaction frequency.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('inventory/reports') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Reports
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">Fast Moving Items (Top 10)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Txns</th>
                                <th class="text-end">Total Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fast_moving as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['item_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($item['sku']) ?></small>
                                    </td>
                                    <td class="text-end"><?= number_format($item['txn_count']) ?></td>
                                    <td class="text-end"><?= number_format($item['total_qty'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">Slow Moving Items (Bottom 10)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Txns</th>
                                <th class="text-end">Total Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slow_moving as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['item_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($item['sku']) ?></small>
                                    </td>
                                    <td class="text-end"><?= number_format($item['txn_count']) ?></td>
                                    <td class="text-end"><?= number_format($item['total_qty'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
