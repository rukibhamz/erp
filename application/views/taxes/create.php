<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Tax Rate</h1>
        <a href="<?= base_url('taxes') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="tax_name" class="form-label">
                            Tax Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="tax_name" name="tax_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tax_code" class="form-label">Tax Code</label>
                        <input type="text" id="tax_code" name="tax_code" class="form-control">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="tax_type" class="form-label">
                            Tax Type <span class="text-danger">*</span>
                        </label>
                            <select name="tax_type" class="form-select" required id="tax_type" onchange="toggleRateType()">
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                                <option value="compound">Compound</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Rate *</label>
                            <input type="number" name="rate" class="form-control" step="0.01" required id="rate">
                            <small class="text-muted" id="rate_hint">Enter percentage (e.g., 10 for 10%)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tax Inclusive</label>
                            <select name="tax_inclusive" class="form-select">
                                <option value="0">No (Tax added on top)</option>
                                <option value="1">Yes (Tax included in price)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('taxes') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Tax Rate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleRateType() {
    const type = document.getElementById('tax_type').value;
    const hint = document.getElementById('rate_hint');
    if (type === 'percentage') {
        hint.textContent = 'Enter percentage (e.g., 10 for 10%)';
    } else {
        hint.textContent = 'Enter fixed amount';
    }
}
</script>


