<?php defined('BASEPATH') OR exit('No direct script access allowed');
$isEdit = !empty($subaccount);
$entryMode = $entry_mode ?? 'create';
if ($isEdit) {
    $entryMode = 'edit';
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <?php if ($isEdit): ?>
                Edit Flutterwave Subaccount
            <?php elseif ($entryMode === 'link'): ?>
                Link existing subaccount code
            <?php else: ?>
                Create Flutterwave Subaccount
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
                <a class="nav-link <?= $entryMode === 'link' ? '' : 'active' ?>"
                   href="<?= base_url('settings/flutterwave/subaccounts/create') ?>">
                    Create new (bank details)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $entryMode === 'link' ? 'active' : '' ?>"
                   href="<?= base_url('settings/flutterwave/subaccounts/create?mode=link') ?>">
                    Link existing code
                </a>
            </li>
        </ul>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if ($entryMode === 'link' && !$isEdit): ?>
                <div class="alert alert-info mb-4">
                    <strong>Already have a subaccount?</strong>
                    Paste the Flutterwave <strong>subaccount ID</strong> from your dashboard or API
                    (starts with <code>RS_</code>). The ERP stores it so split rules and checkout can use it.
                    You do not need bank details for this option.
                </div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="entry_mode" value="<?= htmlspecialchars($entryMode) ?>">

                <?php if ($isEdit): ?>
                    <div class="mb-3">
                        <label class="form-label">Flutterwave subaccount ID</label>
                        <input type="text" class="form-control font-monospace" readonly
                               value="<?= htmlspecialchars($subaccount['subaccount_id']) ?>">
                        <small class="text-muted">This is the code used when splitting payments.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display name in ERP</label>
                        <input type="text" name="business_name" class="form-control" required
                               value="<?= htmlspecialchars($subaccount['business_name']) ?>">
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                               <?= !empty($subaccount['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Active in ERP</label>
                    </div>
                    <p class="text-muted small">Bank and split defaults are managed in Flutterwave.</p>
                    <button type="submit" class="btn btn-primary">Save</button>

                <?php elseif ($entryMode === 'link'): ?>
                    <div class="mb-3">
                        <label class="form-label" for="subaccount_id">Subaccount code *</label>
                        <input type="text" name="subaccount_id" id="subaccount_id" class="form-control font-monospace"
                               placeholder="RS_FB312AA6C2C84A13421F3079E714F2CB" required
                               pattern="RS_[A-Za-z0-9]+" title="Must start with RS_">
                        <small class="text-muted">
                            From Flutterwave dashboard → Subaccounts, or the <code>subaccount_id</code> field when the subaccount was created.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="link_business_name">Display name in ERP *</label>
                        <input type="text" name="business_name" id="link_business_name" class="form-control"
                               placeholder="e.g. Venue owner / Landlord" required>
                        <small class="text-muted">Label for staff — we will replace this if Flutterwave returns a business name.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="NG" maxlength="2">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Split type (reference)</label>
                            <select name="split_type" class="form-select">
                                <option value="percentage">Percentage</option>
                                <option value="flat">Flat</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Split value (reference)</label>
                            <input type="number" step="0.0001" name="split_value" class="form-control" value="0.85">
                            <small class="text-muted">e.g. 0.85 = 85% to beneficiary</small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-link-45deg"></i> Link subaccount code
                    </button>

                <?php else: ?>
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
                            <label class="form-label">Business email</label>
                            <input type="email" name="business_email" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Country *</label>
                            <input type="text" name="country" class="form-control" value="NG" maxlength="2" required>
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
                            <?php if (empty($banks)): ?>
                                <small class="text-warning">Could not load banks — check Flutterwave secret key, or use <a href="<?= base_url('settings/flutterwave/subaccounts/create?mode=link') ?>">Link existing code</a>.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Account number *</label>
                            <input type="text" name="account_number" class="form-control" required>
                            <small class="text-muted">Use Flutterwave sandbox test accounts in test mode.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Split type *</label>
                            <select name="split_type" class="form-select">
                                <option value="percentage">Percentage (subaccount share, e.g. 0.85 = 85%)</option>
                                <option value="flat">Flat (fixed amount to subaccount per transaction)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Split value *</label>
                            <input type="number" step="0.0001" name="split_value" class="form-control" value="0.85" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact person</label>
                            <input type="text" name="business_contact" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact mobile</label>
                            <input type="text" name="business_contact_mobile" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create on Flutterwave
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if (!$isEdit): ?>
        <p class="text-muted small mt-3 mb-0">
            Next step: <a href="<?= base_url('settings/flutterwave/split-rules/create') ?>">create a split rule</a>
            to attach this subaccount to a property or space (or use a global rule).
        </p>
    <?php endif; ?>
</div>
