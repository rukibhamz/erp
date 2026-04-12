<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Promo Code</h1>
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
                <h5 class="mb-0">Promo Code Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('promo-codes/create') ?>">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="code" id="promoCodeInput" class="form-control text-uppercase"
                                   placeholder="e.g. SUMMER20 (leave blank to auto-generate)"
                                   style="text-transform:uppercase">
                            <button type="button" class="btn btn-dark" id="generateBtn" title="Generate random code">
                                <i class="bi bi-shuffle"></i> Generate
                            </button>
                        </div>
                        <small class="text-muted">Leave blank to auto-generate. Must be unique.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Summer 2025 discount">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Discount Type <span class="text-danger">*</span></label>
                            <select name="discount_type" class="form-select" id="discountType" required>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (₦)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Discount Value <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" id="discountSymbol">%</span>
                                <input type="number" name="discount_value" class="form-control" step="0.01" min="0.01" required placeholder="e.g. 20">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Minimum Booking Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="minimum_amount" class="form-control" step="0.01" min="0" placeholder="Optional">
                            </div>
                        </div>
                        <div class="col-md-6" id="maxDiscountRow">
                            <label class="form-label">Maximum Discount Cap</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="maximum_discount" class="form-control" step="0.01" min="0" placeholder="Optional — for % discounts">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Valid From <span class="text-danger">*</span></label>
                            <input type="date" name="valid_from" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valid To <span class="text-danger">*</span></label>
                            <input type="date" name="valid_to" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Usage Limit</label>
                            <input type="number" name="usage_limit" class="form-control" min="1" placeholder="Leave blank for unlimited">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Applicable To</label>
                            <select name="applicable_to" class="form-select">
                                <option value="all">All Bookings</option>
                                <option value="resource">Specific Spaces</option>
                                <option value="addon">Specific Add-ons</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= base_url('promo-codes') ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-circle"></i> Create Promo Code
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm border-info">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> How Promo Codes Work</h6>
            </div>
            <div class="card-body small">
                <p><strong>Percentage:</strong> Deducts a % from the booking total. Set a cap to limit the maximum discount amount.</p>
                <p><strong>Fixed:</strong> Deducts a flat amount from the booking total.</p>
                <p><strong>Minimum Amount:</strong> The booking must be at least this value for the code to apply.</p>
                <p><strong>Usage Limit:</strong> How many times the code can be used in total. Leave blank for unlimited.</p>
                <p class="mb-0"><strong>Applicable To:</strong> Restrict the code to specific spaces or add-ons, or leave as "All Bookings".</p>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
document.getElementById('discountType').addEventListener('change', function() {
    const sym = document.getElementById('discountSymbol');
    const maxRow = document.getElementById('maxDiscountRow');
    if (this.value === 'percentage') {
        sym.textContent = '%';
        maxRow.style.display = '';
    } else {
        sym.textContent = '₦';
        maxRow.style.display = 'none';
    }
});

document.getElementById('generateBtn').addEventListener('click', function() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < 8; i++) code += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById('promoCodeInput').value = code;
    document.getElementById('promoCodeInput').focus();
});
</script>
