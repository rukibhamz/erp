<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit: <code><?= htmlspecialchars($code['code']) ?></code></h1>
        <a href="<?= base_url('promo-codes') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Edit Promo Code</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('promo-codes/edit/' . $code['id']) ?>">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($code['code']) ?>" disabled>
                        <small class="text-muted">Code cannot be changed after creation.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control"
                               value="<?= htmlspecialchars($code['description'] ?? '') ?>">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Discount Type</label>
                            <select name="discount_type" class="form-select" id="discountType">
                                <option value="percentage" <?= $code['discount_type'] === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                                <option value="fixed" <?= $code['discount_type'] === 'fixed' ? 'selected' : '' ?>>Fixed Amount (₦)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Discount Value</label>
                            <div class="input-group">
                                <span class="input-group-text" id="discountSymbol"><?= $code['discount_type'] === 'percentage' ? '%' : '₦' ?></span>
                                <input type="number" name="discount_value" class="form-control" step="0.01" min="0.01"
                                       value="<?= $code['discount_value'] ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Minimum Booking Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="minimum_amount" class="form-control" step="0.01" min="0"
                                       value="<?= $code['minimum_amount'] ?? '' ?>" placeholder="Optional">
                            </div>
                        </div>
                        <div class="col-md-6" id="maxDiscountRow" style="<?= $code['discount_type'] === 'fixed' ? 'display:none' : '' ?>">
                            <label class="form-label">Maximum Discount Cap</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="maximum_discount" class="form-control" step="0.01" min="0"
                                       value="<?= $code['maximum_discount'] ?? '' ?>" placeholder="Optional">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Valid From</label>
                            <input type="date" name="valid_from" class="form-control"
                                   value="<?= $code['valid_from'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valid To</label>
                            <input type="date" name="valid_to" class="form-control"
                                   value="<?= $code['valid_to'] ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Usage Limit</label>
                            <input type="number" name="usage_limit" class="form-control" min="1"
                                   value="<?= $code['usage_limit'] ?? '' ?>" placeholder="Unlimited">
                            <small class="text-muted">Used <?= intval($code['used_count']) ?> time(s) so far.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Applicable To</label>
                            <select name="applicable_to" class="form-select">
                                <option value="all" <?= $code['applicable_to'] === 'all' ? 'selected' : '' ?>>All Bookings</option>
                                <option value="resource" <?= $code['applicable_to'] === 'resource' ? 'selected' : '' ?>>Specific Spaces</option>
                                <option value="addon" <?= $code['applicable_to'] === 'addon' ? 'selected' : '' ?>>Specific Add-ons</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="apply_to_addons" id="applyToAddons"
                                   <?= !empty($code['apply_to_addons']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="applyToAddons">
                                Also apply discount to add-ons
                            </label>
                        </div>
                        <small class="text-muted">If disabled, this promo discounts resource cost only.</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                                   <?= $code['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= base_url('promo-codes') ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
document.getElementById('discountType').addEventListener('change', function() {
    const sym = document.getElementById('discountSymbol');
    const maxRow = document.getElementById('maxDiscountRow');
    sym.textContent = this.value === 'percentage' ? '%' : '₦';
    maxRow.style.display = this.value === 'percentage' ? '' : 'none';
});
</script>
