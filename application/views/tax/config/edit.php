<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit Tax Type</h1>
        <a href="<?= base_url('tax/config') ?>" class="btn btn-outline-dark">
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

<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Tax Type: <?= htmlspecialchars($tax_type['name'] ?? '') ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('tax/config/edit/' . ($tax_type['id'] ?? '')) ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tax Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required 
                           value="<?= htmlspecialchars($tax_type['name'] ?? '') ?>"
                           placeholder="e.g., Value Added Tax">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tax Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" required 
                           value="<?= htmlspecialchars($tax_type['code'] ?? '') ?>"
                           placeholder="e.g., VAT" maxlength="20" style="text-transform: uppercase;">
                    <small class="text-muted">Unique identifier (uppercase, no spaces)</small>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Tax Rate (%)</label>
                    <input type="number" name="rate" class="form-control" 
                           value="<?= number_format($tax_type['rate'] ?? 0, 2) ?>"
                           step="0.01" min="0" max="100">
                    <small class="text-muted">Leave 0 for progressive taxes (e.g., PAYE)</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Calculation Method</label>
                    <select name="calculation_method" class="form-select" required>
                        <option value="percentage" <?= ($tax_type['calculation_method'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                        <option value="fixed" <?= ($tax_type['calculation_method'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount</option>
                        <option value="progressive" <?= ($tax_type['calculation_method'] ?? '') === 'progressive' ? 'selected' : '' ?>>Progressive (e.g., PAYE)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Authority</label>
                    <select name="authority" class="form-select" required>
                        <option value="FIRS" <?= ($tax_type['authority'] ?? '') === 'FIRS' ? 'selected' : '' ?>>FIRS (Federal)</option>
                        <option value="State" <?= ($tax_type['authority'] ?? '') === 'State' ? 'selected' : '' ?>>State</option>
                        <option value="Local" <?= ($tax_type['authority'] ?? '') === 'Local' ? 'selected' : '' ?>>Local Government</option>
                        <option value="Other" <?= ($tax_type['authority'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Filing Frequency</label>
                    <select name="filing_frequency" class="form-select">
                        <option value="monthly" <?= ($tax_type['filing_frequency'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="quarterly" <?= ($tax_type['filing_frequency'] ?? '') === 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
                        <option value="annually" <?= ($tax_type['filing_frequency'] ?? '') === 'annually' ? 'selected' : '' ?>>Annually</option>
                        <option value="none" <?= ($tax_type['filing_frequency'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tax Inclusive Pricing</label>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="tax_inclusive" id="tax_inclusive" value="1"
                               <?= ($tax_type['tax_inclusive'] ?? 0) == 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="tax_inclusive">
                            Tax is included in price
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" 
                          placeholder="Additional details about this tax type"><?= htmlspecialchars($tax_type['description'] ?? '') ?></textarea>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                           <?= ($tax_type['is_active'] ?? 0) == 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">
                        Active (available for use)
                    </label>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= base_url('tax/config') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-dark">
                    <i class="bi bi-save"></i> Update Tax Type
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-uppercase tax code
document.querySelector('input[name="code"]')?.addEventListener('input', function(e) {
    this.value = this.value.toUpperCase().replace(/\s+/g, '');
});
</script>



