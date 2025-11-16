<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Meter Alerts</h1>
</div>

<?php include(BASEPATH . 'views/utilities/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter by Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="unresolved" <?= $selected_status === 'unresolved' ? 'selected' : '' ?>>Unresolved</option>
                    <option value="resolved" <?= $selected_status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    <option value="all" <?= $selected_status === 'all' ? 'selected' : '' ?>>All</option>
                </select>
            </div>
            <div class="col-md-8">
                <a href="<?= base_url('utilities/alerts') ?>" class="btn btn-primary">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($alerts)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-bell" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No alerts found.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Alert ID</th>
                            <th>Meter</th>
                            <th>Alert Type</th>
                            <th>Message</th>
                            <th>Severity</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alerts as $alert): ?>
                            <tr>
                                <td><strong>#<?= htmlspecialchars($alert['id']) ?></strong></td>
                                <td>
                                    <?php if (!empty($alert['meter_id'])): ?>
                                        <a href="<?= base_url('utilities/meters/view/' . $alert['meter_id']) ?>">
                                            <?= htmlspecialchars($alert['meter_name'] ?? 'Meter #' . $alert['meter_id']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= htmlspecialchars($alert['alert_type'] ?? 'Unknown') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($alert['message'] ?? 'No message') ?></td>
                                <td>
                                    <?php
                                    $severity = $alert['severity'] ?? 'medium';
                                    $badgeClass = 'bg-secondary';
                                    if ($severity === 'high') $badgeClass = 'bg-danger';
                                    elseif ($severity === 'medium') $badgeClass = 'bg-warning';
                                    elseif ($severity === 'low') $badgeClass = 'bg-info';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= ucfirst($severity) ?>
                                    </span>
                                </td>
                                <td><?= date('Y-m-d H:i', strtotime($alert['created_at'])) ?></td>
                                <td>
                                    <?php if (!empty($alert['is_resolved'])): ?>
                                        <span class="badge bg-success">Resolved</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Unresolved</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (empty($alert['is_resolved'])): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#resolveModal<?= $alert['id'] ?>">
                                            <i class="bi bi-check-circle"></i> Resolve
                                        </button>
                                        
                                        <!-- Resolve Modal -->
                                        <div class="modal fade" id="resolveModal<?= $alert['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Resolve Alert</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="<?= base_url('utilities/alerts/resolve/' . $alert['id']) ?>" method="POST">
                                                        <?php echo csrf_field(); ?>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Alert Details</label>
                                                                <div class="form-control-plaintext">
                                                                    <strong>Type:</strong> <?= htmlspecialchars($alert['alert_type'] ?? 'Unknown') ?><br>
                                                                    <strong>Message:</strong> <?= htmlspecialchars($alert['message'] ?? 'No message') ?><br>
                                                                    <strong>Severity:</strong> <?= ucfirst($alert['severity'] ?? 'medium') ?>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="resolution_notes<?= $alert['id'] ?>" class="form-label">Resolution Notes (Optional)</label>
                                                                <textarea class="form-control" id="resolution_notes<?= $alert['id'] ?>" name="resolution_notes" rows="3" placeholder="Add any notes about how this alert was resolved..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Resolve Alert</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Resolved</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

