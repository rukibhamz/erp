<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">WHT Returns</h1>
        <div class="d-flex gap-2">
            <?php if (hasPermission('tax', 'create')): ?>
                <a href="<?= base_url('tax/wht/create') ?>" class="btn btn-dark">
                    <i class="bi bi-plus-circle"></i> Create Return
                </a>
            <?php endif; ?>
            <a href="<?= base_url('tax/wht/transactions') ?>" class="btn btn-primary">
                <i class="bi bi-list-ul"></i> Transactions
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

<?php if (empty($wht_returns)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-cash-stack" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No WHT returns found.</p>
            <?php if (hasPermission('tax', 'create')): ?>
                <a href="<?= base_url('tax/wht/create') ?>" class="btn btn-dark">
                    <i class="bi bi-plus-circle"></i> Create First Return
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Return #</th>
                            <th>Period</th>
                            <th>Total WHT</th>
                            <th>Status</th>
                            <th>Filing Deadline</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wht_returns as $return): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($return['return_number'] ?? 'N/A') ?></strong></td>
                                <td><?= date('F Y', mktime(0, 0, 0, $return['month'], 1, $return['year'])) ?></td>
                                <td><?= format_currency($return['total_wht'] ?? 0) ?></td>
                                <td>
                                    <span class="badge bg-<?= $return['status'] === 'paid' ? 'success' : ($return['status'] === 'filed' ? 'info' : 'secondary') ?>">
                                        <?= ucfirst($return['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($return['filing_deadline'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('tax/wht/view/' . $return['id']) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
