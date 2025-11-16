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

<!-- Status Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="btn-group" role="group">
            <a href="?status=all" class="btn btn-sm <?= $selected_status === 'all' ? 'btn-dark' : 'btn-primary' ?>">All</a>
            <a href="?status=scheduled" class="btn btn-sm <?= $selected_status === 'scheduled' ? 'btn-dark' : 'btn-primary' ?>">Scheduled</a>
            <a href="?status=in_progress" class="btn btn-sm <?= $selected_status === 'in_progress' ? 'btn-dark' : 'btn-primary' ?>">In Progress</a>
            <a href="?status=completed" class="btn btn-sm <?= $selected_status === 'completed' ? 'btn-dark' : 'btn-primary' ?>">Completed</a>
        </div>
    </div>
</div>

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
        </div>
    </div>
<?php endif; ?>

