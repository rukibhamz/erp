<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Tax Type</h1>
        <a href="<?= base_url('tax/config') ?>" class="btn btn-primary">
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
        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> New Tax Type</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('tax/config/create') ?>
            <?php echo csrf_field(); ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tax Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required 
                           placeholder="e.g., Value Added Tax">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tax Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" required 
                           placeholder="e.g., VAT" maxlength="20" style="text-transform: uppercase;">
                    <small class="text-muted">Unique identifier (uppercase, no spaces)</small>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Tax Rate (%)</label>
                    <input type="number" name="rate" class="form-control" value="0" 
                           step="0.01" min="0" max="100">
                    <small class="text-muted">Leave 0 for progressive taxes (e.g., PAYE)</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Calculation Method</label>
                    <select name="calculation_method" class="form-select" required>
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed Amount</option>
                        <option value="progressive">Progressive (e.g., PAYE)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Authority</label>
                    <select name="authority" class="form-select" required>
                        <option value="FIRS">FIRS (Federal)</option>
                        <option value="State">State</option>
                        <option value="Local">Local Government</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Filing Frequency</label>
                    <select name="filing_frequency" class="form-select">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="annually">Annually</option>
                        <option value="none">None</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tax Inclusive Pricing</label>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="tax_inclusive" id="tax_inclusive" value="1">
                        <label class="form-check-label" for="tax_inclusive">
                            Tax is included in price
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" 
                          placeholder="Additional details about this tax type"></textarea>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                    <label class="form-check-label" for="is_active">
                        Active (available for use)
                    </label>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= base_url('tax/config') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-dark">
                    <i class="bi bi-save"></i> Create Tax Type
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



