<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Journal Entries</h1>
        <?php if (hasPermission('ledger', 'create')): ?>
            <a href="<?= base_url('ledger/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Entry
            </a>
        <?php endif; ?>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('ledger') ?>" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="draft" <?= ($selected_status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="approved" <?= ($selected_status ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="posted" <?= ($selected_status ?? '') === 'posted' ? 'selected' : '' ?>>Posted</option>
                    <option value="rejected" <?= ($selected_status ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
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
                        <th>Entry #</th>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($entries)): ?>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($entry['entry_number'] ?? 'N/A') ?></strong></td>
                                <td><?= format_date($entry['entry_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($entry['reference'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($entry['description'] ?? '-') ?></td>
                                <td class="text-end"><?= format_currency($entry['amount'] ?? 0) ?></td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'draft' => 'secondary',
                                        'approved' => 'info',
                                        'posted' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    $badgeColor = $statusBadges[$entry['status'] ?? 'draft'] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badgeColor ?>">
                                        <?= ucfirst($entry['status'] ?? 'draft') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('ledger/view/' . intval($entry['id'])) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('ledger', 'update') && ($entry['status'] ?? '') === 'draft'): ?>
                                            <a href="<?= base_url('ledger/edit/' . intval($entry['id'])) ?>" class="btn btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('ledger', 'delete') && ($entry['status'] ?? '') === 'draft'): ?>
                                            <form method="POST" action="<?= base_url('ledger/delete/' . intval($entry['id'])) ?>" 
                                                  style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this journal entry?');">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No journal entries found. <a href="<?= base_url('ledger/create') ?>">Create your first entry</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

