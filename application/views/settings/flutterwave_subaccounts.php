<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="page-header mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="<?= base_url('settings') ?>">Settings</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('settings/payment-gateways') ?>">Payment gateways</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Flutterwave subaccounts</li>
                    </ol>
                </nav>
                <h1 class="page-title mb-1">Flutterwave Subaccounts</h1>
                <p class="text-muted mb-0 small">
                    Register bank beneficiaries for split settlements. Booking amounts in ERP stay unchanged.
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('settings/payment-gateways/edit/' . (int) $gateway['id']) ?>" class="btn btn-secondary">
                    <i class="bi bi-gear"></i> Gateway settings
                </a>
                <a href="<?= base_url('settings/flutterwave/subaccounts/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add subaccount
                </a>
            </div>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-center">
                <div class="col-lg-7">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted small">Split at checkout</span>
                        <?php if ($subaccounts_enabled): ?>
                            <span class="badge bg-success">Enabled</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Disabled</span>
                        <?php endif; ?>
                        <span class="text-muted small ms-2">Split logging</span>
                        <?php if ($log_split): ?>
                            <span class="badge bg-info">On</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Off</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!$subaccounts_enabled): ?>
                        <p class="small text-muted mb-0 mt-2">
                            Enable <strong>split payments</strong> in
                            <a href="<?= base_url('settings/payment-gateways/edit/' . (int) $gateway['id']) ?>">gateway settings</a>
                            after adding subaccounts and split rules.
                        </p>
                    <?php endif; ?>
                </div>
                <div class="col-lg-5">
                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                        <a href="<?= base_url('settings/flutterwave/split-rules') ?>" class="btn btn-sm btn-secondary">
                            <i class="bi bi-diagram-3"></i> Split rules
                        </a>
                        <a href="<?= base_url('settings/payment-gateways') ?>" class="btn btn-sm btn-secondary">
                            <i class="bi bi-credit-card"></i> All gateways
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h2 class="h6 mb-0 fw-semibold">Subaccounts</h2>
                <?php if (!empty($subaccounts)): ?>
                    <span class="badge bg-secondary"><?= count($subaccounts) ?> total</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($subaccounts)): ?>
            <div class="card-body text-center py-5">
                <i class="bi bi-bank2" style="font-size: 3rem; color: #dee2e6;"></i>
                <h5 class="mt-3 text-muted">No subaccounts yet</h5>
                <p class="text-muted mb-4">Create one to split booking payments at Flutterwave checkout.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="<?= base_url('settings/flutterwave/subaccounts/create') ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add first subaccount
                    </a>
                    <a href="<?= base_url('settings/flutterwave/split-rules/create') ?>" class="btn btn-secondary">
                        <i class="bi bi-diagram-3"></i> Add split rule
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Business</th>
                                <th>Flutterwave ID</th>
                                <th>Bank / account</th>
                                <th>Default split</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subaccounts as $row): ?>
                                <?php
                                $splitLabel = ($row['split_type'] ?? '') === 'percentage'
                                    ? (round((float) ($row['split_value'] ?? 0) * 100, 2)) . '% to beneficiary'
                                    : 'Flat ' . number_format((float) ($row['split_value'] ?? 0), 2);
                                ?>
                                <tr>
                                    <td class="ps-3">
                                        <span class="fw-medium"><?= htmlspecialchars($row['business_name']) ?></span>
                                    </td>
                                    <td>
                                        <code class="small"><?= htmlspecialchars($row['subaccount_id']) ?></code>
                                    </td>
                                    <td class="text-muted small">
                                        <?= htmlspecialchars($row['account_bank']) ?>
                                        · <?= htmlspecialchars($row['account_number_masked'] ?: '****') ?>
                                    </td>
                                    <td><?= htmlspecialchars($splitLabel) ?></td>
                                    <td>
                                        <?php if ($row['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3 text-nowrap">
                                        <a href="<?= base_url('settings/flutterwave/subaccounts/edit/' . (int) $row['id']) ?>"
                                           class="btn btn-sm btn-primary me-1">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <?php if ($row['is_active']): ?>
                                            <a href="<?= base_url('settings/flutterwave/subaccounts/delete/' . (int) $row['id']) ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Deactivate this subaccount locally?');">
                                                <i class="bi bi-x-circle"></i> Deactivate
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
