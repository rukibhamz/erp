<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Flutterwave Split Rules</h1>
        <div>
            <a href="<?= base_url('settings/flutterwave/subaccounts') ?>" class="btn btn-secondary">
                <i class="bi bi-bank2"></i> Subaccounts
            </a>
            <a href="<?= base_url('settings/flutterwave/split-rules/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add rule
            </a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <p class="text-muted">
        Rules map bookings to a subaccount: <strong>space</strong> beats <strong>property</strong> beats <strong>global</strong>.
        Booking payment amount in ERP stays the full total; Flutterwave settles the split.
    </p>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Scope</th>
                        <th>Subaccount</th>
                        <th>Override</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rules)): ?>
                        <?php foreach ($rules as $rule): ?>
                            <tr>
                                <td><?= htmlspecialchars($rule['name']) ?></td>
                                <td>
                                    <?= htmlspecialchars($rule['scope_type']) ?>
                                    <?php if ($rule['scope_id']): ?>
                                        #<?= (int) $rule['scope_id'] ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($rule['business_name'] ?? '') ?>
                                    <br><small><code><?= htmlspecialchars($rule['subaccount_id'] ?? '') ?></code></small>
                                </td>
                                <td>
                                    <?php if (!empty($rule['override_charge_type'])): ?>
                                        <?= htmlspecialchars($rule['override_charge_type']) ?>
                                        <?= htmlspecialchars((string) $rule['override_charge']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Default</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) $rule['priority'] ?></td>
                                <td>
                                    <?= !empty($rule['is_active']) ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Off</span>' ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('settings/flutterwave/split-rules/edit/' . (int) $rule['id']) ?>" class="btn btn-sm btn-primary me-1">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <?php if (!empty($rule['is_active'])): ?>
                                        <a href="<?= base_url('settings/flutterwave/split-rules/delete/' . (int) $rule['id']) ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Deactivate this rule?');">
                                            <i class="bi bi-x-circle"></i> Deactivate
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No split rules. Add a global rule or scope by property/space.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
