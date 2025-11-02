<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Calculate Company Income Tax (CIT)</h1>
        <a href="<?= base_url('tax/cit') ?>" class="btn btn-outline-dark">
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
        <h5 class="mb-0"><i class="bi bi-calculator"></i> CIT Calculation</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('tax/cit/calculate') ?>" id="citForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Financial Year <span class="text-danger">*</span></label>
                    <select name="year" class="form-select" required>
                        <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                            <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Turnover <span class="text-danger">*</span></label>
                    <input type="number" name="turnover" class="form-control" step="0.01" min="0" required>
                    <small class="text-muted">For minimum tax calculation</small>
                </div>
            </div>
            
            <h5 class="mb-3 mt-4">Basic Information</h5>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Profit Before Tax <span class="text-danger">*</span></label>
                    <input type="number" name="profit_before_tax" class="form-control" step="0.01" required>
                    <small class="text-muted">From your P&L statement</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Capital Allowances</label>
                    <input type="number" name="capital_allowances" class="form-control" step="0.01" min="0" value="0">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tax Reliefs</label>
                    <input type="number" name="tax_reliefs" class="form-control" step="0.01" min="0" value="0">
                </div>
            </div>
            
            <h5 class="mb-3 mt-4">Tax Adjustments</h5>
            <div id="adjustments-container">
                <div class="adjustment-row mb-2">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" name="adjustments[0][description]" class="form-control" placeholder="Adjustment description">
                        </div>
                        <div class="col-md-4">
                            <input type="number" name="adjustments[0][amount]" class="form-control" step="0.01" placeholder="Amount">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-adjustment" style="display:none;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-dark" id="add-adjustment">
                <i class="bi bi-plus-circle"></i> Add Adjustment
            </button>
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?= base_url('tax/cit') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-dark">Calculate CIT</button>
            </div>
        </form>
    </div>
</div>

<script>
let adjustmentCount = 1;

document.getElementById('add-adjustment').addEventListener('click', function() {
    const container = document.getElementById('adjustments-container');
    const newRow = document.createElement('div');
    newRow.className = 'adjustment-row mb-2';
    newRow.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <input type="text" name="adjustments[${adjustmentCount}][description]" class="form-control" placeholder="Adjustment description">
            </div>
            <div class="col-md-4">
                <input type="number" name="adjustments[${adjustmentCount}][amount]" class="form-control" step="0.01" placeholder="Amount">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-outline-danger remove-adjustment">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
    adjustmentCount++;
    
    // Show remove buttons if more than one row
    if (container.children.length > 1) {
        document.querySelectorAll('.remove-adjustment').forEach(btn => btn.style.display = 'block');
    }
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-adjustment')) {
        e.target.closest('.adjustment-row').remove();
        // Hide remove buttons if only one row left
        if (document.getElementById('adjustments-container').children.length <= 1) {
            document.querySelectorAll('.remove-adjustment').forEach(btn => btn.style.display = 'none');
        }
    }
});
</script>
