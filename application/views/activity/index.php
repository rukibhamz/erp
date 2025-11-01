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
        
        <?php if (isset($total) && $total > $per_page): ?>
            <nav aria-label="Activity log pagination">
                <ul class="pagination justify-content-center mt-4">
                    <?php
                    $total_pages = ceil($total / $per_page);
                    $prev_page = $current_page > 1 ? $current_page - 1 : 1;
                    $next_page = $current_page < $total_pages ? $current_page + 1 : $total_pages;
                    ?>
                    
                    <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= base_url('activity?page=' . $prev_page) ?>">Previous</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= base_url('activity?page=' . $i) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= base_url('activity?page=' . $next_page) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

