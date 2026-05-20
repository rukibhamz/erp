<?php defined('BASEPATH') OR exit('No direct script access allowed');
$isEdit = !empty($rule);
$scopeType = $rule['scope_type'] ?? 'global';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?= $isEdit ? 'Edit' : 'Create' ?> Split Rule</h1>
        <a href="<?= base_url('settings/flutterwave/split-rules') ?>" class="btn btn-secondary">Back</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?php echo csrf_field(); ?>

                <div class="mb-3">
                    <label class="form-label">Rule name *</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($rule['name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Subaccount *</label>
                    <select name="subaccount_row_id" class="form-select" required>
                        <option value="">Select subaccount</option>
                        <?php foreach ($subaccounts as $sa): ?>
                            <option value="<?= (int) $sa['id'] ?>"
                                <?= ($isEdit && (int) ($rule['subaccount_row_id'] ?? 0) === (int) $sa['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sa['business_name']) ?> (<?= htmlspecialchars($sa['subaccount_id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Scope *</label>
                        <select name="scope_type" id="scope_type" class="form-select">
                            <option value="global" <?= $scopeType === 'global' ? 'selected' : '' ?>>Global (all bookings)</option>
                            <option value="property" <?= $scopeType === 'property' ? 'selected' : '' ?>>Property / location</option>
                            <option value="space" <?= $scopeType === 'space' ? 'selected' : '' ?>>Space</option>
                        </select>
                    </div>
                    <div class="col-md-8 mb-3" id="scope_id_wrap">
                        <label class="form-label">Scope target</label>
                        <select name="scope_id" id="scope_id" class="form-select">
                            <option value="">—</option>
                            <optgroup label="Properties" id="opt_properties">
                                <?php foreach ($properties as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>" data-scope="property"
                                        <?= ($scopeType === 'property' && (int) ($rule['scope_id'] ?? 0) === (int) $p['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['property_name'] ?? ('Property #' . $p['id'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Spaces" id="opt_spaces">
                                <?php foreach ($spaces as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>" data-scope="space"
                                        <?= ($scopeType === 'space' && (int) ($rule['scope_id'] ?? 0) === (int) $s['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['space_name'] ?? ('Space #' . $s['id'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Priority</label>
                        <input type="number" name="priority" class="form-control" value="<?= (int) ($rule['priority'] ?? 0) ?>">
                        <small class="text-muted">Higher wins when multiple rules could match.</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Currency filter</label>
                        <input type="text" name="currency" class="form-control" maxlength="10"
                               placeholder="NGN or empty for any"
                               value="<?= htmlspecialchars($rule['currency'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3 form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                               <?= !$isEdit || !empty($rule['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>

                <hr>
                <h6>Optional per-transaction override</h6>
                <p class="small text-muted">Leave blank to use the subaccount default split from Flutterwave.</p>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Override charge type</label>
                        <select name="override_charge_type" class="form-select">
                            <option value="">— Use subaccount default —</option>
                            <option value="percentage" <?= ($rule['override_charge_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>percentage</option>
                            <option value="flat" <?= ($rule['override_charge_type'] ?? '') === 'flat' ? 'selected' : '' ?>>flat</option>
                            <option value="flat_subaccount" <?= ($rule['override_charge_type'] ?? '') === 'flat_subaccount' ? 'selected' : '' ?>>flat_subaccount</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Override charge value</label>
                        <input type="number" step="0.0001" name="override_charge" class="form-control"
                               value="<?= htmlspecialchars((string) ($rule['override_charge'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Split ratio (multi-vendor)</label>
                        <input type="number" name="split_ratio" class="form-control"
                               value="<?= htmlspecialchars((string) ($rule['split_ratio'] ?? '')) ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save rule</button>
            </form>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function () {
    const scopeType = document.getElementById('scope_type');
    const scopeId = document.getElementById('scope_id');
    const wrap = document.getElementById('scope_id_wrap');

    function refreshScope() {
        const t = scopeType.value;
        wrap.style.display = t === 'global' ? 'none' : '';
        Array.from(scopeId.options).forEach(function (opt) {
            if (!opt.value) return;
            const s = opt.getAttribute('data-scope');
            opt.hidden = (t === 'property' && s !== 'property') || (t === 'space' && s !== 'space');
        });
    }
    scopeType.addEventListener('change', refreshScope);
    refreshScope();
})();
</script>
