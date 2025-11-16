<?php
$page_title = $page_title ?? 'Dashboard';
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon primary me-3">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <div class="stat-number"><?= format_large_number($total_users ?? 0) ?></div>
                    <div class="stat-label">Users</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon success me-3">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <div class="stat-number"><?= format_large_number($total_companies ?? 0) ?></div>
                    <div class="stat-label">Companies</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                Recent Activity
            </div>
            <div class="card-body">
                <?php if (!empty($recent_activities)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Module</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($activity['username'] ?? 'System') ?></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($activity['action']) ?></span></td>
                                        <td><?= htmlspecialchars($activity['module'] ?? 'N/A') ?></td>
                                        <td><?= date('M d, H:i', strtotime($activity['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No recent activity</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

