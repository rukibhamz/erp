<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="page-header mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
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
                <div class="col-lg-8">
                    <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                        <span class="text-muted small fw-semibold text-uppercase">Split at checkout</span>
                        <?php if ($subaccounts_enabled): ?>
                            <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                <i class="bi bi-check-circle me-1"></i> Enabled
                            </span>
                        <?php else: ?>
                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2">
                                <i class="bi bi-exclamation-circle me-1"></i> Disabled
                            </span>
                        <?php endif; ?>

                        <span class="text-muted d-none d-md-inline">|</span>

                        <span class="text-muted small fw-semibold text-uppercase">Split logging</span>
                        <?php if ($log_split): ?>
                            <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">On</span>
                        <?php else: ?>
                            <span class="badge rounded-pill bg-secondary-subtle text-secondary border px-3 py-2">Off</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!$subaccounts_enabled): ?>
                        <p class="small text-muted mb-0 mt-2">
                            Turn on <strong>Enable split payments</strong> in
                            <a href="<?= base_url('settings/payment-gateways/edit/' . (int) $gateway['id']) ?>">gateway settings</a>
                            after you add subaccounts and split rules.
                        </p>
                    <?php endif; ?>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                        <a href="<?= base_url('settings/flutterwave/split-rules') ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-diagram-3"></i> Split rules
                        </a>
                        <a href="<?= base_url('settings/payment-gateways') ?>" class="btn btn-outline-secondary btn-sm">
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
                    <span class="badge bg-light text-dark border"><?= count($subaccounts) ?> total</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($subaccounts)): ?>
            <div class="card-body">
                <div class="empty-state">
                    <i class="bi bi-bank2 d-block"></i>
                    <p class="text-muted">No subaccounts yet. Create one to split booking payments at Flutterwave checkout.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="<?= base_url('settings/flutterwave/subaccounts/create') ?>" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add first subaccount
                        </a>
                        <a href="<?= base_url('settings/flutterwave/split-rules/create') ?>" class="btn btn-outline-primary">
                            <i class="bi bi-diagram-3"></i> Add split rule
                        </a>
                    </div>
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
                                <th class="text-end pe-3" style="width: 11rem;">Actions</th>
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
                                        <span class="text-dark">·</span>
                                        <?= htmlspecialchars($row['account_number_masked'] ?: '****') ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($splitLabel) ?></span></td>
                                    <td>
                                        <?php if ($row['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3 text-nowrap">
                                        <a href="<?= base_url('settings/flutterwave/subaccounts/edit/' . (int) $row['id']) ?>"
                                           class="btn btn-sm btn-outline-primary">Edit</a>
                                        <?php if ($row['is_active']): ?>
                                            <a href="<?= base_url('settings/flutterwave/subaccounts/delete/' . (int) $row['id']) ?>"
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Deactivate this subaccount locally?');">Deactivate</a>
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
