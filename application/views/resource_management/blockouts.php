<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-ban me-2"></i>Blockout Periods</h5>
                    <div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBlockoutModal">
                            <i class="fas fa-plus me-1"></i> Add Blockout
                        </button>
                        <a href="<?= site_url('facilities') ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($flash) && $flash): ?>
                        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
                            <?= htmlspecialchars($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($facility) && $facility): ?>
                        <div class="alert alert-info">
                            <strong>Facility:</strong> <?= htmlspecialchars($facility['facility_name'] ?? '') ?>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($blockouts)): ?>
                                    <?php foreach ($blockouts as $blockout): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($blockout['title'] ?? 'Blockout') ?></td>
                                            <td><?= date('M d, Y H:i', strtotime($blockout['start_datetime'] ?? '')) ?></td>
                                            <td><?= date('M d, Y H:i', strtotime($blockout['end_datetime'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars($blockout['reason'] ?? '-') ?></td>
                                            <td>
                                                <?php 
                                                $now = time();
                                                $start = strtotime($blockout['start_datetime'] ?? '');
                                                $end = strtotime($blockout['end_datetime'] ?? '');
                                                if ($now < $start): ?>
                                                    <span class="badge bg-warning">Upcoming</span>
                                                <?php elseif ($now >= $start && $now <= $end): ?>
                                                    <span class="badge bg-danger">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Expired</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?= site_url('resource-management/delete-blockout/' . ($blockout['id'] ?? '')) ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this blockout?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                                            No blockout periods defined
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Blockout Modal -->
<div class="modal fade" id="addBlockoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= site_url('resource-management/add-blockout') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="facility_id" value="<?= htmlspecialchars($facility['id'] ?? '') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add Blockout Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" placeholder="e.g., Maintenance, Holiday" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Start Date & Time</label>
                            <input type="datetime-local" class="form-control" name="start_datetime" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control" name="end_datetime" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" name="reason" rows="2" placeholder="Optional reason for blockout"></textarea>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_recurring" id="is_recurring">
                        <label class="form-check-label" for="is_recurring">
                            Recurring blockout (e.g., weekly maintenance)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Blockout</button>
                </div>
            </form>
        </div>
    </div>
</div>
