<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Journal Entries</h1>
        <a href="<?= base_url('ledger/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i> Create Journal Entry
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('ledger') ?>" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="draft" <?= ($selected_status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="approved" <?= ($selected_status ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="posted" <?= ($selected_status ?? '') === 'posted' ? 'selected' : '' ?>>Posted</option>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Journal Entries Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">All Journal Entries</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Entry Number</th>
                        <th>Entry Date</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($entries)): ?>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($entry['entry_number'] ?? 'N/A') ?></strong></td>
                                <td><?= htmlspecialchars($entry['entry_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($entry['reference'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($entry['description'] ?? '-') ?></td>
                                <td class="text-end"><strong><?= format_currency($entry['amount'] ?? 0) ?></strong></td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'draft' => 'secondary',
                                        'approved' => 'info',
                                        'posted' => 'success'
                                    ];
                                    $badgeColor = $statusBadges[$entry['status'] ?? 'draft'] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badgeColor ?>">
                                        <?= ucfirst($entry['status'] ?? 'draft') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    // Get user name (simplified - you might want to join with users table)
                                    echo 'User #' . ($entry['created_by'] ?? 'N/A');
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('ledger/edit/' . $entry['id']) ?>" class="btn btn-outline-secondary" title="View/Edit">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (($entry['status'] ?? 'draft') === 'draft'): ?>
                                            <a href="<?= base_url('ledger/approve/' . $entry['id']) ?>" class="btn btn-outline-info" title="Approve" onclick="return confirm('Approve this journal entry?');">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (($entry['status'] ?? '') === 'approved'): ?>
                                            <a href="<?= base_url('ledger/post/' . $entry['id']) ?>" class="btn btn-outline-success" title="Post" onclick="return confirm('Post this journal entry to the ledger?');">
                                                <i class="bi bi-bookmark-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No journal entries found. <a href="<?= base_url('ledger/create') ?>">Create your first journal entry</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

