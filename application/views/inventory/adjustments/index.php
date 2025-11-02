<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include BASEPATH . 'views/layouts/header.php';
include BASEPATH . 'views/inventory/_nav.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><?= htmlspecialchars($page_title) ?></h3>
            <?php if (hasPermission('inventory', 'create')): ?>
                <a href="<?= base_url('inventory/adjustments/create') ?>" class="btn btn-dark">
                    <i class="bi bi-plus-circle"></i> Create Adjustment
                </a>
            <?php endif; ?>
        </div>

        <?php if (isset($flash) && $flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Status Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="btn-group" role="group">
                    <a href="?status=all" class="btn btn-sm <?= $selected_status === 'all' ? 'btn-dark' : 'btn-outline-dark' ?>">All</a>
                    <a href="?status=pending" class="btn btn-sm <?= $selected_status === 'pending' ? 'btn-dark' : 'btn-outline-dark' ?>">Pending</a>
                    <a href="?status=approved" class="btn btn-sm <?= $selected_status === 'approved' ? 'btn-dark' : 'btn-outline-dark' ?>">Approved</a>
                    <a href="?status=rejected" class="btn btn-sm <?= $selected_status === 'rejected' ? 'btn-dark' : 'btn-outline-dark' ?>">Rejected</a>
                </div>
            </div>
        </div>

        <!-- Adjustments Table -->
        <?php if (empty($adjustments)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-pencil-square" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">No adjustments found.</p>
                    <?php if (hasPermission('inventory', 'create')): ?>
                        <a href="<?= base_url('inventory/adjustments/create') ?>" class="btn btn-dark">
                            <i class="bi bi-plus-circle"></i> Create First Adjustment
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Adjustment #</th>
                                    <th>Item</th>
                                    <th>Location</th>
                                    <th>Before</th>
                                    <th>After</th>
                                    <th>Adjustment</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($adjustments as $adj): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($adj['adjustment_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($adj['item_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($adj['location_name'] ?? 'N/A') ?></td>
                                        <td><?= number_format($adj['quantity_before'], 2) ?></td>
                                        <td><?= number_format($adj['quantity_after'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= floatval($adj['adjustment_qty']) >= 0 ? 'success' : 'danger' ?>">
                                                <?= number_format($adj['adjustment_qty'], 2) ?>
                                            </span>
                                        </td>
                                        <td><?= ucfirst(str_replace('_', ' ', htmlspecialchars($adj['reason']))) ?></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'rejected' => 'danger'
                                            ];
                                            $class = $statusClass[$adj['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $class ?>"><?= ucfirst($adj['status']) ?></span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($adj['adjustment_date'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= base_url('inventory/adjustments/view/' . $adj['id']) ?>" class="btn btn-outline-dark" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($adj['status'] === 'pending' && hasPermission('inventory', 'update')): ?>
                                                    <a href="<?= base_url('inventory/adjustments/approve/' . $adj['id']) ?>" class="btn btn-outline-success" title="Approve" onclick="return confirm('Approve this adjustment?')">
                                                        <i class="bi bi-check-circle"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include BASEPATH . 'views/layouts/footer.php'; ?>

