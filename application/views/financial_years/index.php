<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-calendar3"></i> Financial Years</h1>
        <a href="<?= base_url('financial-years/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Financial Year
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
                            <th>Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($financial_years)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                                    No financial years found. Create one to get started.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($financial_years as $fy): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($fy['name'] ?? '') ?></td>
                                    <td><?= date('M d, Y', strtotime($fy['start_date'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($fy['end_date'])) ?></td>
                                    <td>
                                        <?php if ($fy['status'] === 'open'): ?>
                                            <span class="badge bg-success">Open</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Closed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= base_url('financial-years/periods/' . $fy['id']) ?>" class="btn btn-sm btn-outline-primary" title="Manage Periods">
                                            <i class="bi bi-calendar-month"></i> Periods
                                        </a>
                                        <?php if ($fy['status'] === 'open'): ?>
                                            <a href="<?= base_url('financial-years/close/' . $fy['id']) ?>" class="btn btn-sm btn-outline-warning" title="Close Year">
                                                <i class="bi bi-lock"></i> Close
                                            </a>
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
