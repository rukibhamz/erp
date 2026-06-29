<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Fixed Assets</h1>
        <?php if (hasPermission('inventory', 'create')): ?>
            <a href="<?= base_url('inventory/assets/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Asset
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
$list_filter_action = base_url('inventory/assets');
$search_placeholder = 'Asset tag, name, location…';
$list_filter_extra_keys = ['search', 'category'];
$selected_category = $selected_category ?? 'all';
if ($selected_category !== 'all') {
    $list_filter_active_badges = '<span class="badge bg-secondary">Category: ' . htmlspecialchars(ucfirst($selected_category)) . '</span>';
}
ob_start();
?>
<div class="list-filters-secondary d-flex flex-row flex-wrap align-items-center gap-2 mt-2">
    <span class="filter-group-label">Category</span>
    <?php
    $categories = ['all' => 'All', 'equipment' => 'Equipment', 'vehicle' => 'Vehicles', 'furniture' => 'Furniture', 'it' => 'IT'];
    foreach ($categories as $val => $label):
        $active = ($selected_category === $val);
        $href = base_url('inventory/assets') . list_filter_query(['category' => $val === 'all' ? null : $val]);
    ?>
    <a href="<?= htmlspecialchars($href) ?>" class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline-primary' ?>"><?= htmlspecialchars($label) ?></a>
    <?php endforeach; ?>
</div>
<?php
$list_filter_secondary = ob_get_clean();
include(BASEPATH . 'views/partials/list_filters_bar.php');
?>

<?php if (empty($assets)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-building" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No assets found.</p>
            <?php if (hasPermission('inventory', 'create')): ?>
                <a href="<?= base_url('inventory/assets/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add First Asset
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
                            <th>Asset Tag</th>
                            <th>Asset Name</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Purchase Cost</th>
                            <th>Current Value</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($asset['asset_tag']) ?></strong></td>
                                <td>
                                    <a href="<?= base_url('inventory/assets/view/' . $asset['id']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($asset['asset_name']) ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= ucfirst($asset['asset_category']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($asset['location_name'] ?? 'N/A') ?></td>
                                <td><?= format_currency($asset['purchase_cost']) ?></td>
                                <td><?= format_currency($asset['current_value']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $asset['asset_status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($asset['asset_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('inventory/assets/view/' . $asset['id']) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('inventory', 'update')): ?>
                                            <a href="<?= base_url('inventory/assets/edit/' . $asset['id']) ?>" class="btn btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
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

