<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-arrow-repeat"></i> Recurring Transactions</h1>
        <?php if (!empty($due_transactions)): ?>
            <form method="POST" action="<?= base_url('recurring/process') ?>" class="d-inline">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-success" 
                        onclick="return confirm('Process all <?= count($due_transactions) ?> due transactions now?')">
                    <i class="bi bi-play-fill"></i> Process Due (<?= count($due_transactions) ?>)
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($due_transactions)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            <strong><?= count($due_transactions) ?></strong> transaction(s) are due for processing.
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Frequency</th>
                            <th>Next Run</th>
                            <th>End Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-arrow-repeat fs-1 d-block mb-2"></i>
                                    No recurring transactions set up.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                                <?php
                                    $isDue = !empty($t['next_run_date']) && strtotime($t['next_run_date']) <= time();
                                ?>
                                <tr class="<?= $isDue ? 'table-warning' : '' ?>">
                                    <td>
                                        <span class="badge bg-<?= match($t['type'] ?? '') {
                                            'invoice' => 'primary',
                                            'bill' => 'danger',
                                            'payment' => 'success',
                                            'journal' => 'info',
                                            default => 'secondary'
                                        } ?>">
                                            <?= ucfirst(htmlspecialchars($t['type'] ?? 'N/A')) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($t['description'] ?? $t['reference'] ?? '—') ?></td>
                                    <td><?= ucfirst(htmlspecialchars($t['frequency'] ?? '')) ?></td>
                                    <td>
                                        <?php if (!empty($t['next_run_date'])): ?>
                                            <?= date('M d, Y', strtotime($t['next_run_date'])) ?>
                                            <?php if ($isDue): ?>
                                                <span class="badge bg-warning text-dark ms-1">Due</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= !empty($t['end_date']) ? date('M d, Y', strtotime($t['end_date'])) : '<span class="text-muted">No end</span>' ?>
                                    </td>
                                    <td>
                                        <?php if (($t['status'] ?? '') === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif (($t['status'] ?? '') === 'paused'): ?>
                                            <span class="badge bg-warning">Paused</span>
                                        <?php elseif (($t['status'] ?? '') === 'completed'): ?>
                                            <span class="badge bg-secondary">Completed</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= ucfirst(htmlspecialchars($t['status'] ?? 'unknown')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
