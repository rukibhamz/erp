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
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('ledger') ?>" class="row g-3">
            <?php $search_placeholder = 'Entry #, reference, description…'; include(BASEPATH . 'views/partials/list_search_field.php'); ?>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="draft" <?= ($selected_status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="approved" <?= ($selected_status ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="posted" <?= ($selected_status ?? '') === 'posted' ? 'selected' : '' ?>>Posted</option>
                    <option value="rejected" <?= ($selected_status ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Records</label>
                <?php render_pagination_per_page_select(intval($pagination['per_page'] ?? 50)); ?>
                <input type="hidden" name="page" value="1">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Journal Entries Table -->
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="card-title mb-0">All Journal Entries</h5>
    </div>
    <div class="card-body">
        <?php
        $bulk_delete_enabled = hasPermission('ledger', 'delete');
        $ledger_deletable = array_values(array_filter($entries ?? [], function ($e) {
            return ($e['status'] ?? '') === 'draft';
        }));
        bulk_delete_render_toolbar($bulk_delete_enabled, $ledger_deletable, base_url('ledger/bulk-delete'), 'journal entry', 'Are you sure you want to delete the selected journal entries?');
        ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <?php bulk_delete_render_checkbox_th($bulk_delete_enabled); ?>
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
                                <?php if ($bulk_delete_enabled && ($entry['status'] ?? '') === 'draft'): ?>
                                    <?php bulk_delete_render_checkbox_td(true, (int)$entry['id'], 'journal entry ' . ($entry['entry_number'] ?? $entry['id'])); ?>
                                <?php elseif ($bulk_delete_enabled): ?>
                                    <td></td>
                                <?php endif; ?>
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
                                            <form method="POST" action="<?= base_url('ledger/delete/' . intval($entry['id'])) ?>
<?php echo csrf_field(); ?>" 
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
                            <td colspan="<?= bulk_delete_colspan(7, $bulk_delete_enabled) ?>" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-journal-text"></i>
                                    <p class="mb-0">No journal entries found.</p>
                                    <a href="<?= base_url('ledger/create') ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-circle"></i> Create Entry
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include BASEPATH . 'views/partials/accounting_table_footer.php'; ?>
</div>

