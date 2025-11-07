<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Financial Years</h1>
        <?php if (has_permission('settings', 'create')): ?>
            <a href="<?= base_url('financial-years/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Financial Year
            </a>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($current_year): ?>
        <div class="alert alert-info">
            <strong>Current Financial Year:</strong> <?= htmlspecialchars($current_year['year_name']) ?> 
            (<?= date('M d, Y', strtotime($current_year['start_date'])) ?> - <?= date('M d, Y', strtotime($current_year['end_date'])) ?>)
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Year Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($financial_years)): ?>
                            <?php foreach ($financial_years as $year): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($year['year_name']) ?>
                                        <?php if ($current_year && $current_year['id'] == $year['id']): ?>
                                            <span class="badge bg-success">Current</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($year['start_date'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($year['end_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $year['status'] === 'open' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($year['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('financial-years/periods/' . $year['id']) ?>" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-calendar"></i> Periods
                                        </a>
                                        <?php if ($year['status'] === 'open' && has_permission('settings', 'update')): ?>
                                            <a href="<?= base_url('financial-years/close/' . $year['id']) ?>" class="btn btn-sm btn-outline-warning" 
                                               onclick="return confirm('Are you sure you want to close this financial year? This action cannot be undone.')">
                                                <i class="bi bi-lock"></i> Close
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No financial years found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


