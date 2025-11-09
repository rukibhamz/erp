<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$is_compliance = true;
include(__DIR__ . '/../_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Tax Compliance - Deadlines</h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('tax/compliance') ?>" class="btn btn-outline-dark">
                <i class="bi bi-calendar"></i> Calendar View
            </a>
            <a href="<?= base_url('tax/compliance/create-deadline') ?>" class="btn btn-dark">
                <i class="bi bi-plus-circle"></i> Create Deadline
            </a>
        </div>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Overdue Deadlines -->
<?php if (!empty($overdue_deadlines ?? [])): ?>
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Overdue Deadlines</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tax Type</th>
                            <th>Deadline Date</th>
                            <th>Deadline Type</th>
                            <th>Period</th>
                            <th>Days Overdue</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdue_deadlines as $deadline): ?>
                            <?php
                            $deadlineDate = strtotime($deadline['deadline_date'] ?? '');
                            $daysOverdue = $deadlineDate ? floor((time() - $deadlineDate) / 86400) : 0;
                            $urgent = $daysOverdue > 30;
                            ?>
                            <tr class="<?= $urgent ? 'table-danger' : '' ?>">
                                <td><strong><?= htmlspecialchars($deadline['tax_type'] ?? 'N/A') ?></strong></td>
                                <td><?= $deadlineDate ? date('M d, Y', $deadlineDate) : 'N/A' ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= ucfirst(htmlspecialchars($deadline['deadline_type'] ?? 'filing')) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($deadline['period_covered'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-danger"><?= $daysOverdue ?> days</span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= ($deadline['status'] ?? '') === 'completed' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($deadline['status'] ?? 'upcoming') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (($deadline['status'] ?? '') !== 'completed'): ?>
                                        <button class="btn btn-sm btn-success" onclick="markCompleted(<?= intval($deadline['id'] ?? 0) ?>)">
                                            <i class="bi bi-check-circle"></i> Mark Complete
                                        </button>
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

<!-- Upcoming Deadlines -->
<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Upcoming Deadlines</h5>
    </div>
    <div class="card-body">
        <?php if (empty($upcoming_deadlines ?? [])): ?>
            <p class="text-muted mb-0">No upcoming deadlines scheduled.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tax Type</th>
                            <th>Deadline Date</th>
                            <th>Deadline Type</th>
                            <th>Period</th>
                            <th>Days Remaining</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_deadlines as $deadline): ?>
                            <?php
                            $deadlineDate = strtotime($deadline['deadline_date'] ?? '');
                            $daysRemaining = $deadlineDate ? ceil(($deadlineDate - time()) / 86400) : 0;
                            ?>
                            <tr class="<?= $daysRemaining <= 7 ? 'table-warning' : '' ?>">
                                <td><strong><?= htmlspecialchars($deadline['tax_type'] ?? 'N/A') ?></strong></td>
                                <td><?= $deadlineDate ? date('M d, Y', $deadlineDate) : 'N/A' ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= ucfirst(htmlspecialchars($deadline['deadline_type'] ?? 'filing')) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($deadline['period_covered'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= $daysRemaining <= 7 ? 'danger' : ($daysRemaining <= 14 ? 'warning' : 'info') ?>">
                                        <?= $daysRemaining ?> days
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= ucfirst($deadline['status'] ?? 'upcoming') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (($deadline['status'] ?? '') !== 'completed'): ?>
                                        <button class="btn btn-sm btn-success" onclick="markCompleted(<?= intval($deadline['id'] ?? 0) ?>)">
                                            <i class="bi bi-check-circle"></i> Mark Complete
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function markCompleted(deadlineId) {
    if (!deadlineId || deadlineId <= 0) {
        alert('Invalid deadline ID');
        return;
    }
    
    if (confirm('Mark this deadline as completed?')) {
        fetch('<?= base_url('tax/compliance/mark-completed') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'deadline_id=' + deadlineId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to mark as completed'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error marking deadline as completed');
        });
    }
}
</script>

