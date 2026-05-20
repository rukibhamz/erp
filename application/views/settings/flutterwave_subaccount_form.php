<?php defined('BASEPATH') OR exit('No direct script access allowed');
$isEdit = !empty($subaccount);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?= $isEdit ? 'Edit' : 'Create' ?> Flutterwave Subaccount</h1>
        <a href="<?= base_url('settings/flutterwave/subaccounts') ?>" class="btn btn-secondary">Back</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?php echo csrf_field(); ?>

                <?php if ($isEdit): ?>
                    <div class="mb-3">
                        <label class="form-label">Flutterwave subaccount ID</label>
                        <input type="text" class="form-control" readonly value="<?= htmlspecialchars($subaccount['subaccount_id']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Business name</label>
                        <input type="text" name="business_name" class="form-control" required
                               value="<?= htmlspecialchars($subaccount['business_name']) ?>">
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                               <?= !empty($subaccount['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Active in ERP</label>
                    </div>
                    <p class="text-muted small">Bank details and split defaults are managed on Flutterwave; recreate if those change.</p>
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
                                <small class="text-warning">Could not load banks — check Flutterwave secret key and test mode.</small>
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
                <?php endif; ?>

                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save' : 'Create on Flutterwave' ?></button>
            </form>
        </div>
    </div>
</div>
