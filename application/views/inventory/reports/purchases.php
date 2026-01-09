<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Purchase Analysis Report</h1>
        <p class="text-muted">Overview of purchase orders and supplier activity.</p>
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
                        <th>PO #</th>
                        <th>Supplier</th>
                        <th class="text-end">Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No purchase orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $po): ?>
                            <tr>
                                <td><?= date('Y-m-d', strtotime($po['order_date'])) ?></td>
                                <td><code><?= htmlspecialchars($po['po_number']) ?></code></td>
                                <td><?= htmlspecialchars($po['supplier_name']) ?></td>
                                <td class="text-end"><?= format_currency($po['total_amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $po['status'] === 'received' ? 'success' : ($po['status'] === 'ordered' ? 'primary' : 'secondary') ?>">
                                        <?= ucfirst($po['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url('inventory/purchase-orders/view/' . $po['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
