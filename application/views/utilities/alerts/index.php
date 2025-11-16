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
                <a href="<?= base_url('utilities/alerts') ?>" class="btn btn-outline-secondary">
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
                            <th>Date</th>
                            <th>Meter</th>
                            <th>Utility Type</th>
                            <th>Alert Type</th>
                            <th>Severity</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alerts as $alert): ?>
                            <tr class="<?= !$alert['is_resolved'] && $alert['severity'] === 'high' ? 'table-danger' : '' ?>">
                                <td><?= date('M d, Y H:i', strtotime($alert['alert_date'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($alert['meter_number']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($alert['utility_type_name'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= ucfirst(str_replace('_', ' ', $alert['alert_type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $alert['severity'] === 'high' ? 'danger' : 
                                        ($alert['severity'] === 'medium' ? 'warning' : 'info') ?>">
                                        <?= ucfirst($alert['severity']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($alert['description']) ?></td>
                                <td>
                                    <?php if ($alert['is_resolved']): ?>
                                        <span class="badge bg-success">Resolved</span>
                                        <?php if ($alert['resolved_at']): ?>
                                            <br><small class="text-muted"><?= date('M d, Y', strtotime($alert['resolved_at'])) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Unresolved</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$alert['is_resolved']): ?>
                                        <button type="button" class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#resolveModal<?= $alert['id'] ?>">
                                            <i class="bi bi-check-circle"></i> Resolve
                                        </button>
                                    <?php else: ?>
                                        <?php if ($alert['resolution_notes']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#notesModal<?= $alert['id'] ?>">
                                                <i class="bi bi-info-circle"></i> Notes
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Resolve Modal -->
                            <?php if (!$alert['is_resolved']): ?>
                                <div class="modal fade" id="resolveModal<?= $alert['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Resolve Alert</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="<?= base_url('utilities/alerts/resolve/' . $alert['id']) ?>
            <?php echo csrf_field(); ?>" method="POST">
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Alert Details</label>
                                                        <div class="form-control-plaintext">
                                                            <strong><?= htmlspecialchars($alert['meter_number']) ?></strong><br>
                                                            <small><?= htmlspecialchars($alert['description']) ?></small>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="resolution_notes" class="form-label">Resolution Notes</label>
                                                        <textarea class="form-control" id="resolution_notes" name="resolution_notes" rows="3" 
                                                                  placeholder="Describe how this alert was resolved..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Resolve Alert</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Notes Modal -->
                            <?php if ($alert['is_resolved'] && $alert['resolution_notes']): ?>
                                <div class="modal fade" id="notesModal<?= $alert['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Resolution Notes</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Resolved:</strong> <?= date('M d, Y H:i', strtotime($alert['resolved_at'])) ?></p>
                                                <p><strong>Notes:</strong></p>
                                                <p><?= nl2br(htmlspecialchars($alert['resolution_notes'])) ?></p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

