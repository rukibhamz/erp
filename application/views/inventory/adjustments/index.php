<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Stock Adjustments</h1>
        <?php if (hasPermission('inventory', 'create')): ?>
            <a href="<?= base_url('inventory/adjustments/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Adjustment
            </a>
        <?php endif; ?>
    </div>
</div>

<?php include(BASEPATH . 'views/inventory/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php
$list_filter_action = base_url('inventory/adjustments');
$search_placeholder = 'Adjustment #, item, location…';
$list_filter_extra_keys = ['search', 'status'];
$selected_status = $selected_status ?? 'all';
if ($selected_status !== 'all') {
    $list_filter_active_badges = '<span class="badge bg-secondary">Status: ' . htmlspecialchars(ucfirst($selected_status)) . '</span>';
}
ob_start();
?>
<div class="list-filters-secondary d-flex flex-row flex-wrap align-items-center gap-2 mt-2">
    <span class="filter-group-label">Status</span>
    <?php
    $statuses = ['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
    foreach ($statuses as $val => $label):
        $active = ($selected_status === $val);
        $href = base_url('inventory/adjustments') . list_filter_query(['status' => $val === 'all' ? null : $val]);
    ?>
    <a href="<?= htmlspecialchars($href) ?>" class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline-primary' ?>"><?= htmlspecialchars($label) ?></a>
    <?php endforeach; ?>
</div>
<?php
$list_filter_secondary = ob_get_clean();
include(BASEPATH . 'views/partials/list_filters_bar.php');
?>

<?php if (empty($adjustments)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-pencil-square" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No adjustments found.</p>
            <?php if (hasPermission('inventory', 'create')): ?>
                <a href="<?= base_url('inventory/adjustments/create') ?>" class="btn btn-primary">
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
                                        <a href="<?= base_url('inventory/adjustments/view/' . $adj['id']) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($adj['status'] === 'pending' && hasPermission('inventory', 'update')): ?>
                                            <form method="post" action="<?= base_url('inventory/adjustments/approve/' . $adj['id']) ?>" class="d-inline"
                                                  onsubmit="return confirm('Approve this adjustment?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-outline-success" title="Approve">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php render_pagination_controls($pagination ?? null); ?>
        </div>
    </div>
<?php endif; ?>

