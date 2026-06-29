<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Stock Takes</h1>
        <?php if (hasPermission('inventory', 'create')): ?>
            <a href="<?= base_url('inventory/stock-takes/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Stock Take
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
$list_filter_action = base_url('inventory/stock-takes');
$search_placeholder = 'Stock take #, location…';
$list_filter_extra_keys = ['search', 'status'];
$selected_status = $selected_status ?? 'all';
if ($selected_status !== 'all') {
    $list_filter_active_badges = '<span class="badge bg-secondary">Status: ' . htmlspecialchars(ucfirst(str_replace('_', ' ', $selected_status))) . '</span>';
}
ob_start();
?>
<div class="list-filters-secondary d-flex flex-row flex-wrap align-items-center gap-2 mt-2">
    <span class="filter-group-label">Status</span>
    <?php
    $statuses = ['all' => 'All', 'scheduled' => 'Scheduled', 'in_progress' => 'In Progress', 'completed' => 'Completed'];
    foreach ($statuses as $val => $label):
        $active = ($selected_status === $val);
        $href = base_url('inventory/stock-takes') . list_filter_query(['status' => $val === 'all' ? null : $val]);
    ?>
    <a href="<?= htmlspecialchars($href) ?>" class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline-primary' ?>"><?= htmlspecialchars($label) ?></a>
    <?php endforeach; ?>
</div>
<?php
$list_filter_secondary = ob_get_clean();
include(BASEPATH . 'views/partials/list_filters_bar.php');
?>

<?php if (empty($stock_takes)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-clipboard-check" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No stock takes found.</p>
            <?php if (hasPermission('inventory', 'create')): ?>
                <a href="<?= base_url('inventory/stock-takes/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Create First Stock Take
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
                            <th>Stock Take #</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Scheduled Date</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stock_takes as $st): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($st['stock_take_number']) ?></strong></td>
                                <td><?= htmlspecialchars($st['location_name'] ?? 'N/A') ?></td>
                                <td><?= ucfirst($st['type']) ?></td>
                                <td><?= date('M d, Y', strtotime($st['scheduled_date'])) ?></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'scheduled' => 'secondary',
                                        'in_progress' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    $class = $statusClass[$st['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $class ?>"><?= ucfirst(str_replace('_', ' ', $st['status'])) ?></span>
                                </td>
                                <td>User ID: <?= $st['created_by'] ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('inventory/stock-takes/view/' . $st['id']) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
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

