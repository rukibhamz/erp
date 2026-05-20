<?php defined('BASEPATH') OR exit('No direct script access allowed');
$isEdit = !empty($subaccount);
$entryMode = $entry_mode ?? 'link';
if ($isEdit) {
    $entryMode = 'edit';
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <?php if ($isEdit): ?>
                Edit subaccount
            <?php elseif ($entryMode === 'create'): ?>
                Create subaccount on Flutterwave
            <?php else: ?>
                Activate subaccount code
            <?php endif; ?>
        </h1>
        <a href="<?= base_url('settings/flutterwave/subaccounts') ?>" class="btn btn-secondary">Back</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!$isEdit): ?>
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link <?= $entryMode === 'link' ? 'active' : '' ?>"
                   href="<?= base_url('settings/flutterwave/subaccounts/create?mode=link') ?>">
                    Link code (recommended)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $entryMode === 'create' ? 'active' : '' ?>"
                   href="<?= base_url('settings/flutterwave/subaccounts/create?mode=create') ?>">
                    Create new on Flutterwave
                </a>
            </li>
        </ul>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if ($entryMode === 'link' && !$isEdit): ?>
                <p class="text-muted mb-4">
                    Paste your Flutterwave <strong>subaccount ID</strong> (<code>RS_…</code>).
                    Split amounts and settlement are handled entirely in Flutterwave — the ERP only sends this code at checkout.
                </p>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="entry_mode" value="<?= htmlspecialchars($entryMode) ?>">

                <?php if ($isEdit): ?>
                    <div class="mb-3">
                        <label class="form-label">Subaccount code</label>
                        <input type="text" class="form-control font-monospace" readonly
                               value="<?= htmlspecialchars($subaccount['subaccount_id']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display name</label>
                        <input type="text" name="business_name" class="form-control" required
                               value="<?= htmlspecialchars($subaccount['business_name']) ?>">
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                               <?= !empty($subaccount['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>

                <?php elseif ($entryMode === 'link'): ?>
                    <div class="mb-3">
                        <label class="form-label" for="subaccount_id">Subaccount code *</label>
                        <input type="text" name="subaccount_id" id="subaccount_id" class="form-control font-monospace"
                               placeholder="RS_…" required pattern="RS_[A-Za-z0-9]+" title="Must start with RS_">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="link_business_name">Display name *</label>
                        <input type="text" name="business_name" id="link_business_name" class="form-control"
                               placeholder="e.g. Venue owner" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input class="form-check-input" type="checkbox" name="set_as_default" id="set_as_default" value="1" checked>
                        <label class="form-check-label" for="set_as_default">Use for all booking payments</label>
                        <small class="d-block text-muted">If you add more codes later, mark one as default on the list page.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Activate subaccount
                    </button>

                <?php else: ?>
                    <p class="text-muted small">Only use this if the subaccount does not exist yet on Flutterwave.</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Business name *</label>
                            <input type="text" name="business_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Business mobile *</label>
                            <input type="text" name="business_mobile" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bank *</label>
                            <select name="account_bank" class="form-select" required>
                                <option value="">Select bank</option>
                                <?php foreach ($banks as $bank): ?>
                                    <option value="<?= htmlspecialchars($bank['code'] ?? '') ?>">
                                        <?= htmlspecialchars($bank['name'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Account number *</label>
                            <input type="text" name="account_number" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Country *</label>
                            <input type="text" name="country" class="form-control" value="NG" maxlength="2" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Split type *</label>
                            <select name="split_type" class="form-select">
                                <option value="percentage">Percentage (on Flutterwave)</option>
                                <option value="flat">Flat (on Flutterwave)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Split value *</label>
                            <input type="number" step="0.0001" name="split_value" class="form-control" value="0.85" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create on Flutterwave
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($entryMode === 'link' && !$isEdit): ?>
        <div class="card shadow-sm mt-3 border-0 bg-light">
            <div class="card-body small text-muted">
                <strong>After activating:</strong>
                1) Turn on <a href="<?= base_url('settings/payment-gateways/edit/' . (int) ($gateway['id'] ?? 0)) ?>">Enable split payments</a> in Flutterwave gateway settings.
                2) Take a booking payment — Flutterwave applies the split configured on that subaccount.
            </div>
        </div>
    <?php endif; ?>
</div>
