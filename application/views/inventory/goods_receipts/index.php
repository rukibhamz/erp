<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Goods Receipts</h1>
        <a href="<?= base_url('inventory/goods-receipts/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create GRN
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

<?php if (empty($grns)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-box-arrow-in-down" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No goods receipts found.</p>
            <a href="<?= base_url('inventory/goods-receipts/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create First GRN
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>GRN Number</th>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Receipt Date</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grns as $grn): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($grn['grn_number']) ?></strong></td>
                                <td><?= htmlspecialchars($grn['po_number'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($grn['supplier_name'] ?? 'N/A') ?></td>
                                <td><?= date('M d, Y', strtotime($grn['receipt_date'])) ?></td>
                                <td><?= htmlspecialchars($grn['location_name'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= $grn['status'] === 'completed' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($grn['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url('inventory/goods-receipts/view/' . $grn['id']) ?>" class="btn btn-sm btn-outline-dark">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

