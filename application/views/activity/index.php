<?php
$page_title = $page_title ?? 'Activity Log';
?>

<div class="page-header">
    <h1 class="page-title mb-0">Activity Log</h1>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
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

