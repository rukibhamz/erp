<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Tax Settings</h1>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-gear"></i> Company Tax Profile</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('tax/settings') ?>">
            <?php echo csrf_field(); ?>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Company TIN</label>
                    <input type="text" name="company_tin" class="form-control" value="<?= htmlspecialchars($settings['company_tin'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Company Registration Number</label>
                    <input type="text" name="company_registration_number" class="form-control" value="<?= htmlspecialchars($settings['company_registration_number'] ?? '') ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">VAT Registration Number</label>
                    <input type="text" name="vat_registration_number" class="form-control" value="<?= htmlspecialchars($settings['vat_registration_number'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tax Office</label>
                    <input type="text" name="tax_office" class="form-control" value="<?= htmlspecialchars($settings['tax_office'] ?? '') ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Accounting Year End Month</label>
                    <select name="accounting_year_end_month" class="form-select">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= ($settings['accounting_year_end_month'] ?? 12) == $i ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="submit" class="btn btn-dark">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<!-- Tax Rates Management -->
<div class="card mt-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-percent"></i> Tax Rates Configuration</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('tax/settings') ?>" id="taxRatesForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="update_tax_rates" value="1">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Recommended rates are shown in parentheses. You can adjust rates as needed.
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tax Type</th>
                            <th>Code</th>
                            <th>Authority</th>
                            <th>Current Rate (%)</th>
                            <th>Recommended Rate (%)</th>
                            <th>New Rate (%)</th>
                            <th>Calculation Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tax_types ?? [])): ?>
                            <?php foreach ($tax_types as $tax): ?>
                                <?php
                                $currentRate = floatval($tax['rate'] ?? 0);
                                $recommendedRate = $recommended_rates[$tax['code'] ?? ''] ?? $currentRate;
                                $isProgressive = ($tax['calculation_method'] ?? '') === 'progressive';
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($tax['name'] ?? '') ?></strong></td>
                                    <td><code><?= htmlspecialchars($tax['code'] ?? '') ?></code></td>
                                    <td><?= htmlspecialchars($tax['authority'] ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-<?= abs($currentRate - $recommendedRate) < 0.01 ? 'success' : 'warning' ?>">
                                            <?= number_format($currentRate, 2) ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <?= $isProgressive ? 'Progressive' : number_format($recommendedRate, 2) . '%' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($isProgressive): ?>
                                            <span class="text-muted">Progressive (not editable)</span>
                                        <?php else: ?>
                                            <input type="number" 
                                                   name="tax_rates[<?= $tax['id'] ?>]" 
                                                   class="form-control form-control-sm" 
                                                   value="<?= $currentRate ?>" 
                                                   step="0.01" 
                                                   min="0" 
                                                   max="100"
                                                   style="width: 120px;">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= ucfirst($tax['calculation_method'] ?? 'percentage') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No tax types found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="submit" class="btn btn-dark" id="saveRatesBtn">
                    <i class="bi bi-save"></i> Update Tax Rates
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('taxRatesForm')?.addEventListener('submit', function(e) {
    const form = this;
    const rates = form.querySelectorAll('input[name^="tax_rates"]');
    let hasChanges = false;
    
    rates.forEach(input => {
        if (input.value && parseFloat(input.value) >= 0) {
            hasChanges = true;
        }
    });
    
    if (!hasChanges) {
        e.preventDefault();
        alert('No changes detected.');
        return false;
    }
    
    if (!confirm('Update tax rates? This will affect all future tax calculations.')) {
        e.preventDefault();
        return false;
    }
});
</script>
