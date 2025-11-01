<?php
$page_title = $page_title ?? 'Dashboard';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0"><?= htmlspecialchars($page_title) ?></h1>
        <nav aria-label="breadcrumb" class="mt-2">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-people-fill text-primary fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Users</h6>
                        <h3 class="mb-0"><?= number_format($total_users ?? 0) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-building text-success fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Companies</h6>
                        <h3 class="mb-0"><?= number_format($total_companies ?? 0) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_activities)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Module</th>
                                    <th>IP Address</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($activity['username'] ?? 'System') ?></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($activity['action']) ?></span></td>
                                        <td><?= htmlspecialchars($activity['module'] ?? 'N/A') ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($activity['ip_address'] ?? 'N/A') ?></small></td>
                                        <td><small class="text-muted"><?= date('M d, Y H:i', strtotime($activity['created_at'])) ?></small></td>
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

