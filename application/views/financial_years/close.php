<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-lock"></i> Close Financial Year</h1>
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

    <div class="card shadow-sm border-warning">
        <div class="card-header bg-warning bg-opacity-10">
            <h5 class="card-title mb-0 text-warning">
                <i class="bi bi-exclamation-triangle"></i> Confirm Year-End Close
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <strong>Warning:</strong> Closing a financial year is a significant action. 
                Revenue and expense balances will be transferred to retained earnings, and 
                no further transactions can be posted to this period.
            </div>

            <table class="table table-bordered">
                <tr>
                    <th style="width: 200px;">Financial Year</th>
                    <td><?= htmlspecialchars($financial_year['name'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Period</th>
                    <td><?= date('M d, Y', strtotime($financial_year['start_date'])) ?> — <?= date('M d, Y', strtotime($financial_year['end_date'])) ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><span class="badge bg-success">Open</span></td>
                </tr>
                <tr>
                    <th>Retained Earnings</th>
                    <td class="fw-bold fs-5"><?= number_format($retained_earnings, 2) ?></td>
                </tr>
            </table>

            <form method="POST" action="<?= base_url('financial-years/close/' . $financial_year['id']) ?>" 
                  onsubmit="return confirm('Are you sure you want to close this financial year? This action cannot be easily undone.');">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-lock-fill"></i> Close Financial Year
                </button>
                <a href="<?= base_url('financial-years') ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>
