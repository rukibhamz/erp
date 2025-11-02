<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Adjustment: <?= htmlspecialchars($adjustment['adjustment_number']) ?></h1>
        <div class="d-flex gap-2">
            <?php if ($adjustment['status'] === 'pending' && hasPermission('inventory', 'update')): ?>
                <a href="<?= base_url('inventory/adjustments/approve/' . $adjustment['id']) ?>" class="btn btn-success" onclick="return confirm('Approve this adjustment?')">
                    <i class="bi bi-check-circle"></i> Approve
                </a>
            <?php endif; ?>
            <a href="<?= base_url('inventory/adjustments') ?>" class="btn btn-outline-secondary">
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
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Adjustment Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered mb-0">
                    <tr>
                        <th width="40%">Adjustment Number</th>
                        <td><strong><?= htmlspecialchars($adjustment['adjustment_number']) ?></strong></td>
                    </tr>
                    <tr>
                        <th>Item</th>
                        <td><?= htmlspecialchars($item['item_name'] ?? 'N/A') ?> (<?= htmlspecialchars($item['sku'] ?? 'N/A') ?>)</td>
                    </tr>
                    <tr>
                        <th>Location</th>
                        <td><?= htmlspecialchars($location['location_name'] ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <th>Quantity Before</th>
                        <td><?= number_format($adjustment['quantity_before'], 2) ?></td>
                    </tr>
                    <tr>
                        <th>Quantity After</th>
                        <td><?= number_format($adjustment['quantity_after'], 2) ?></td>
                    </tr>
                    <tr>
                        <th>Adjustment Quantity</th>
                        <td>
                            <span class="badge bg-<?= floatval($adjustment['adjustment_qty']) >= 0 ? 'success' : 'danger' ?> fs-6">
                                <?= number_format($adjustment['adjustment_qty'], 2) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Reason</th>
                        <td><?= ucfirst(str_replace('_', ' ', htmlspecialchars($adjustment['reason']))) ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php
                            $statusClass = [
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger'
                            ];
                            $class = $statusClass[$adjustment['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $class ?>"><?= ucfirst($adjustment['status']) ?></span>
                        </td>
                    </tr>
                    <?php if (!empty($adjustment['notes'])): ?>
                        <tr>
                            <th>Notes</th>
                            <td><?= nl2br(htmlspecialchars($adjustment['notes'])) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Adjustment Date</th>
                        <td><?= date('F d, Y H:i', strtotime($adjustment['adjustment_date'])) ?></td>
                    </tr>
                    <?php if ($adjustment['approved_by']): ?>
                        <tr>
                            <th>Approved By</th>
                            <td>User ID: <?= $adjustment['approved_by'] ?></td>
                        </tr>
                        <tr>
                            <th>Approved At</th>
                            <td><?= $adjustment['approved_at'] ? date('F d, Y H:i', strtotime($adjustment['approved_at'])) : 'N/A' ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

