<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-calendar-month"></i> Periods — <?= htmlspecialchars($financial_year['name'] ?? '') ?>
        </h1>
        <a href="<?= base_url('financial-years') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Period</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($months)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No periods found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($months as $m): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($m['label']) ?></td>
                                    <td>
                                        <?php if ($m['locked']): ?>
                                            <span class="badge bg-danger"><i class="bi bi-lock-fill"></i> Locked</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><i class="bi bi-unlock"></i> Open</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($financial_year['status'] === 'open'): ?>
                                            <?php if ($m['locked']): ?>
                                                <form method="POST" action="<?= base_url('financial-years/unlock-period') ?>" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="financial_year_id" value="<?= $financial_year['id'] ?>">
                                                    <input type="hidden" name="month" value="<?= $m['month'] ?>">
                                                    <input type="hidden" name="year" value="<?= $m['year'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" 
                                                            onclick="return confirm('Unlock this period?')">
                                                        <i class="bi bi-unlock"></i> Unlock
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="<?= base_url('financial-years/lock-period') ?>" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="financial_year_id" value="<?= $financial_year['id'] ?>">
                                                    <input type="hidden" name="month" value="<?= $m['month'] ?>">
                                                    <input type="hidden" name="year" value="<?= $m['year'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('Lock this period? No transactions can be posted to locked periods.')">
                                                        <i class="bi bi-lock"></i> Lock
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Year closed</span>
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
