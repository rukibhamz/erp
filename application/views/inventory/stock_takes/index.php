<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include BASEPATH . 'views/layouts/header.php';
include BASEPATH . 'views/inventory/_nav.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
            <h3><?= htmlspecialchars($page_title) ?></h3>
            <?php if (hasPermission('inventory', 'create')): ?>
                <a href="<?= base_url('inventory/stock-takes/create') ?>" class="btn btn-dark">
                    <i class="bi bi-plus-circle"></i> Create Stock Take
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
                    <a href="?status=scheduled" class="btn btn-sm <?= $selected_status === 'scheduled' ? 'btn-dark' : 'btn-outline-dark' ?>">Scheduled</a>
                    <a href="?status=in_progress" class="btn btn-sm <?= $selected_status === 'in_progress' ? 'btn-dark' : 'btn-outline-dark' ?>">In Progress</a>
                    <a href="?status=completed" class="btn btn-sm <?= $selected_status === 'completed' ? 'btn-dark' : 'btn-outline-dark' ?>">Completed</a>
                </div>
            </div>
        </div>

        <!-- Stock Takes Table -->
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
                            <?php if (empty($stock_takes)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No stock takes found</td>
                                </tr>
                            <?php else: ?>
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
                                            <a href="<?= base_url('inventory/stock-takes/view/' . $st['id']) ?>" class="btn btn-sm btn-outline-dark">
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

<?php include BASEPATH . 'views/layouts/footer.php'; ?>

