<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Tax Rate</h1>
        <a href="<?= base_url('taxes') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
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
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tax Name *</label>
                            <input type="text" name="tax_name" class="form-control" value="<?= htmlspecialchars($tax['tax_name']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tax Code</label>
                            <input type="text" name="tax_code" class="form-control" value="<?= htmlspecialchars($tax['tax_code'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tax Type *</label>
                            <select name="tax_type" class="form-select" required id="tax_type" onchange="toggleRateType()">
                                <option value="percentage" <?= $tax['tax_type'] === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                                <option value="fixed" <?= $tax['tax_type'] === 'fixed' ? 'selected' : '' ?>>Fixed Amount</option>
                                <option value="compound" <?= $tax['tax_type'] === 'compound' ? 'selected' : '' ?>>Compound</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Rate *</label>
                            <input type="number" name="rate" class="form-control" step="0.01" value="<?= $tax['rate'] ?>" required id="rate">
                            <small class="text-muted" id="rate_hint">
                                <?= $tax['tax_type'] === 'percentage' ? 'Enter percentage (e.g., 10 for 10%)' : 'Enter fixed amount' ?>
                            </small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tax Inclusive</label>
                            <select name="tax_inclusive" class="form-select">
                                <option value="0" <?= !$tax['tax_inclusive'] ? 'selected' : '' ?>>No (Tax added on top)</option>
                                <option value="1" <?= $tax['tax_inclusive'] ? 'selected' : '' ?>>Yes (Tax included in price)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($tax['description'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $tax['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $tax['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('taxes') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Tax Rate</button>
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

<?php $this->load->view('layouts/footer'); ?>

