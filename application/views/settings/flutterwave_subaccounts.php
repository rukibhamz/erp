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
                    Link your <code>RS_…</code> code and enable splits in gateway settings. Flutterwave handles how much each party receives.
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('settings/payment-gateways/edit/' . (int) $gateway['id']) ?>" class="btn btn-secondary">
                    <i class="bi bi-gear"></i> Gateway settings
                </a>
                <a href="<?= base_url('settings/flutterwave/subaccounts/create?mode=link') ?>" class="btn btn-primary">
                    <i class="bi bi-link-45deg"></i> Activate code
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
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <span class="text-muted small me-2">Split payments</span>
                    <?php if ($subaccounts_enabled): ?>
                        <span class="badge bg-success">On</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Off</span>
                        <span class="small text-muted ms-2">Enable in <a href="<?= base_url('settings/payment-gateways/edit/' . (int) $gateway['id']) ?>">gateway settings</a></span>
                    <?php endif; ?>
                    <?php if (!empty($subaccounts)): ?>
                        <span class="text-muted small ms-3"><?= count($subaccounts) ?> code(s) linked</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="<?= base_url('settings/flutterwave/split-rules') ?>" class="btn btn-sm btn-secondary">
                        Advanced: per property/space
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h2 class="h6 mb-0 fw-semibold">Linked subaccount codes</h2>
        </div>

        <?php if (empty($subaccounts)): ?>
            <div class="card-body text-center py-5">
                <i class="bi bi-link-45deg" style="font-size: 3rem; color: #dee2e6;"></i>
                <h5 class="mt-3 text-muted">No subaccount code yet</h5>
                <p class="text-muted mb-4">Paste the <code>RS_…</code> code Flutterwave gave you, then enable split payments in gateway settings.</p>
                <a href="<?= base_url('settings/flutterwave/subaccounts/create?mode=link') ?>" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Activate subaccount code
                </a>
            </div>
        <?php else: ?>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Name</th>
                                <th>Code</th>
                                <th>Used for payments</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subaccounts as $row): ?>
                                <tr>
                                    <td class="ps-3 fw-medium"><?= htmlspecialchars($row['business_name']) ?></td>
                                    <td><code class="small"><?= htmlspecialchars($row['subaccount_id']) ?></code></td>
                                    <td>
                                        <?php if (!empty($row['is_default'])): ?>
                                            <span class="badge bg-primary">Default (all bookings)</span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= !empty($row['is_active']) ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
                                    </td>
                                    <td class="text-end pe-3 text-nowrap">
                                        <?php if (!empty($row['is_active']) && empty($row['is_default'])): ?>
                                            <a href="<?= base_url('settings/flutterwave/subaccounts/default/' . (int) $row['id']) ?>"
                                               class="btn btn-sm btn-secondary me-1">Set default</a>
                                        <?php endif; ?>
                                        <a href="<?= base_url('settings/flutterwave/subaccounts/edit/' . (int) $row['id']) ?>"
                                           class="btn btn-sm btn-primary me-1">Edit</a>
                                        <?php if ($row['is_active']): ?>
                                            <a href="<?= base_url('settings/flutterwave/subaccounts/delete/' . (int) $row['id']) ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Deactivate this code?');">Deactivate</a>
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
