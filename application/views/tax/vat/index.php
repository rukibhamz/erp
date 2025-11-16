<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">VAT Returns</h1>
        <?php if (hasPermission('tax', 'create')): ?>
            <a href="<?= base_url('tax/vat/create') ?>" class="btn btn-dark">
                <i class="bi bi-plus-circle"></i> Create Return
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

<?php if (empty($vat_returns)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-receipt" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No VAT returns found.</p>
            <?php if (hasPermission('tax', 'create')): ?>
                <a href="<?= base_url('tax/vat/create') ?>" class="btn btn-dark">
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
                            <th>Output VAT</th>
                            <th>Input VAT</th>
                            <th>Net VAT</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vat_returns as $return): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($return['return_number'] ?? 'N/A') ?></strong></td>
                                <td><?= date('M d', strtotime($return['period_start'])) . ' - ' . date('M d, Y', strtotime($return['period_end'])) ?></td>
                                <td><?= format_currency($return['output_vat'] ?? 0) ?></td>
                                <td><?= format_currency($return['input_vat'] ?? 0) ?></td>
                                <td><?= format_currency($return['net_vat'] ?? 0) ?></td>
                                <td>
                                    <span class="badge bg-<?= $return['status'] === 'paid' ? 'success' : ($return['status'] === 'filed' ? 'info' : 'secondary') ?>">
                                        <?= ucfirst($return['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('tax/vat/view/' . $return['id']) ?>" class="btn btn-primary" title="View">
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
