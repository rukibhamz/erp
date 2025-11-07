<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Period Management: <?= htmlspecialchars($financial_year['year_name'] ?? '') ?></h1>
        <a href="<?= base_url('financial-years') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($financial_year): ?>
        <div class="alert alert-info">
            <strong>Period:</strong> <?= date('M d, Y', strtotime($financial_year['start_date'])) ?> - 
            <?= date('M d, Y', strtotime($financial_year['end_date'])) ?>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Status</th>
                                <th>Locked By</th>
                                <th>Locked At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($periods)): ?>
                                <?php foreach ($periods as $period): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($period['month_name']) ?></td>
                                        <td>
                                            <?php if ($period['is_locked']): ?>
                                                <span class="badge bg-danger">Locked</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Open</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>
                                            <?php if ($period['is_locked']): ?>
                                                <?php if (has_permission('settings', 'update')): ?>
                                                    <form method="POST" action="<?= base_url('financial-years/unlock-period') ?>" class="d-inline">
                                                        <input type="hidden" name="financial_year_id" value="<?= $financial_year['id'] ?>">
                                                        <input type="hidden" name="month" value="<?= $period['month'] ?>">
                                                        <input type="hidden" name="year" value="<?= $period['year'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-success" 
                                                                onclick="return confirm('Unlock this period?')">
                                                            <i class="bi bi-unlock"></i> Unlock
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if (has_permission('settings', 'update')): ?>
                                                    <form method="POST" action="<?= base_url('financial-years/lock-period') ?>" class="d-inline">
                                                        <input type="hidden" name="financial_year_id" value="<?= $financial_year['id'] ?>">
                                                        <input type="hidden" name="month" value="<?= $period['month'] ?>">
                                                        <input type="hidden" name="year" value="<?= $period['year'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('Lock this period? Transactions in locked periods cannot be edited.')">
                                                            <i class="bi bi-lock"></i> Lock
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No periods found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>


