<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Flutterwave Subaccounts</h1>
        <div>
            <a href="<?= base_url('settings/payment-gateways/edit/' . (int) $gateway['id']) ?>" class="btn btn-outline-secondary">Gateway settings</a>
            <a href="<?= base_url('settings/flutterwave/subaccounts/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add subaccount
            </a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="alert alert-<?= $subaccounts_enabled ? 'success' : 'warning' ?>">
        Split payments at checkout:
        <strong><?= $subaccounts_enabled ? 'Enabled' : 'Disabled' ?></strong>
        — configure in
        <a href="<?= base_url('settings/payment-gateways/edit/' . (int) $gateway['id']) ?>">Flutterwave gateway settings</a>.
        Transaction split logging: <strong><?= $log_split ? 'On' : 'Off' ?></strong>.
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-2">
            <a href="<?= base_url('settings/flutterwave/split-rules') ?>" class="btn btn-outline-primary">
                <i class="bi bi-diagram-3"></i> Split rules
            </a>
            <a href="<?= base_url('settings/payment-gateways') ?>" class="btn btn-outline-secondary">All gateways</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Business</th>
                        <th>Flutterwave ID</th>
                        <th>Bank / Account</th>
                        <th>Default split</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($subaccounts)): ?>
                        <?php foreach ($subaccounts as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['business_name']) ?></td>
                                <td><code><?= htmlspecialchars($row['subaccount_id']) ?></code></td>
                                <td>
                                    <?= htmlspecialchars($row['account_bank']) ?>
                                    / <?= htmlspecialchars($row['account_number_masked'] ?: '****') ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['split_type']) ?>
                                    <?= htmlspecialchars((string) $row['split_value']) ?>
                                </td>
                                <td>
                                    <?php if ($row['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('settings/flutterwave/subaccounts/edit/' . (int) $row['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <?php if ($row['is_active']): ?>
                                        <a href="<?= base_url('settings/flutterwave/subaccounts/delete/' . (int) $row['id']) ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Deactivate this subaccount locally?');">Deactivate</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-muted text-center">No subaccounts yet. Create one to split booking payments.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
