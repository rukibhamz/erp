<?php
$page_title = $page_title ?? 'Activity Log';
$perPage = intval($pagination['per_page'] ?? 50);
$hasFilters = list_search_term() !== '';
?>

<div class="page-header list-filters-page-header">
    <h1 class="page-title mb-0">Activity Log</h1>
</div>

<div class="card shadow-sm mb-4 list-filters-card">
    <div class="card-body">
        <form method="GET" action="<?= base_url('activity') ?>" class="list-filters-form">
            <div class="row g-2 align-items-end list-filters-row">
                <?php
                $search_col_class = 'col-12 col-md';
                $search_placeholder = 'User, action, module, description…';
                include(BASEPATH . 'views/partials/list_search_field.php');
                ?>
                <?php render_list_filter_per_page($perPage); ?>
                <?php render_list_filter_submit_buttons(base_url('activity')); ?>
            </div>

            <?php if ($hasFilters): ?>
            <div class="list-active-filters">
                <span class="small text-muted me-1"><i class="bi bi-funnel"></i> Active:</span>
                <span class="badge bg-secondary">Search: <?= htmlspecialchars(list_search_term()) ?></span>
                <a href="<?= base_url('activity') ?>" class="small ms-1">Clear all</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M d, Y H:i', strtotime($activity['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($activity['username']): ?>
                                        <strong><?= htmlspecialchars($activity['username']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($activity['email'] ?? '') ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($activity['action']) ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($activity['module'] ?? 'N/A') ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($activity['description'] ?? '') ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?= htmlspecialchars($activity['ip_address'] ?? 'N/A') ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No activity records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php render_pagination_controls($pagination ?? null); ?>
    </div>
</div>
