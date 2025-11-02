<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Tax Compliance Calendar</h1>
        <?php if (hasPermission('tax', 'create')): ?>
            <a href="<?= base_url('tax/compliance/create-deadline') ?>" class="btn btn-dark">
                <i class="bi bi-plus-circle"></i> Create Deadline
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- View Toggle -->
<div class="card mb-4">
    <div class="card-body">
        <div class="btn-group" role="group">
            <a href="?view=list" class="btn btn-sm <?= $view === 'list' ? 'btn-dark' : 'btn-outline-dark' ?>">
                <i class="bi bi-list"></i> List View
            </a>
            <a href="?view=calendar" class="btn btn-sm <?= $view === 'calendar' ? 'btn-dark' : 'btn-outline-dark' ?>">
                <i class="bi bi-calendar"></i> Calendar View
            </a>
        </div>
    </div>
</div>

<!-- Overdue Items Alert -->
<?php if (!empty($overdue)): ?>
    <div class="alert alert-danger mb-4">
        <h5><i class="bi bi-exclamation-triangle"></i> Overdue Deadlines (<?= count($overdue) ?>)</h5>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Tax Type</th>
                        <th>Period</th>
                        <th>Deadline Date</th>
                        <th>Type</th>
                        <th>Days Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overdue as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['tax_type']) ?></strong></td>
                            <td><?= htmlspecialchars($item['period_covered'] ?? 'N/A') ?></td>
                            <td><?= date('M d, Y', strtotime($item['deadline_date'])) ?></td>
                            <td><?= ucfirst($item['deadline_type']) ?></td>
                            <td>
                                <span class="badge bg-danger">
                                    <?= floor((time() - strtotime($item['deadline_date'])) / 86400) ?> days
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Upcoming Deadlines -->
<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Upcoming Deadlines (Next 90 Days)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($upcoming)): ?>
            <p class="text-muted mb-0">No upcoming deadlines in the next 90 days.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tax Type</th>
                            <th>Period</th>
                            <th>Deadline Date</th>
                            <th>Type</th>
                            <th>Days Remaining</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming as $item): ?>
                            <?php
                            $daysRemaining = floor((strtotime($item['deadline_date']) - time()) / 86400);
                            $urgent = $daysRemaining <= 7;
                            ?>
                            <tr class="<?= $urgent ? 'table-warning' : '' ?>">
                                <td><strong><?= htmlspecialchars($item['tax_type']) ?></strong></td>
                                <td><?= htmlspecialchars($item['period_covered'] ?? 'N/A') ?></td>
                                <td><?= date('M d, Y', strtotime($item['deadline_date'])) ?></td>
                                <td><?= ucfirst($item['deadline_type']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $urgent ? 'danger' : ($daysRemaining <= 14 ? 'warning' : 'info') ?>">
                                        <?= $daysRemaining ?> days
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $urgent ? 'warning' : 'secondary' ?>">
                                        <?= ucfirst($item['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-success" onclick="markCompleted(<?= $item['id'] ?>)" title="Mark as Completed">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
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
    if (confirm('Mark this deadline as completed?')) {
        // AJAX call to mark as completed
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
                alert('Error: ' + (data.message || 'Failed to update'));
            }
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
}
</script>
