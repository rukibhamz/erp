<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Stock Movements Report</h1>
        <p class="text-muted">History of all stock transactions across all locations.</p>
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
                        <th>Date</th>
                        <th>Transaction #</th>
                        <th>Type</th>
                        <th>SKU</th>
                        <th>Item Name</th>
                        <th>From</th>
                        <th>To</th>
                        <th class="text-end">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td><?= date('Y-m-d H:i', strtotime($txn['transaction_date'])) ?></td>
                            <td><code><?= htmlspecialchars($txn['transaction_number']) ?></code></td>
                            <td>
                                <span class="badge bg-<?= $txn['transaction_type'] === 'purchase' ? 'success' : ($txn['transaction_type'] === 'sale' ? 'primary' : 'info') ?>">
                                    <?= ucfirst($txn['transaction_type']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($txn['sku'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($txn['item_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($txn['location_from_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($txn['location_to_name'] ?? 'N/A') ?></td>
                            <td class="text-end">
                                <strong><?= number_format($txn['quantity'], 2) ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
