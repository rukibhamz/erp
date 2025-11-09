<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');

// Check if user is admin or super_admin
$isAdmin = in_array($current_user['role'] ?? '', ['super_admin', 'admin']);
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Tax Configuration</h1>
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
        <h5 class="mb-0"><i class="bi bi-percent"></i> Tax Rates Configuration</h5>
    </div>
    <div class="card-body">
        <?php if (!$isAdmin): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Only Administrators can modify tax rates.
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= base_url('tax/config/updateRates') ?>" id="taxRatesForm">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tax Type</th>
                            <th>Code</th>
                            <th>Current Rate (%)</th>
                            <th>New Rate (%)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Define the 4 main taxes
                        $mainTaxes = ['VAT' => 'Value Added Tax', 'WHT' => 'Withholding Tax', 'CIT' => 'Company Income Tax', 'PAYE' => 'Pay As You Earn'];
                        $taxesFound = [];
                        
                        // Find existing taxes - map by code (case-insensitive)
                        foreach ($tax_types ?? [] as $tax) {
                            $code = strtoupper(trim($tax['code'] ?? ''));
                            if (isset($mainTaxes[$code])) {
                                $taxesFound[$code] = $tax;
                            }
                        }
                        
                        // Display all 4 taxes (create placeholders if missing)
                        foreach ($mainTaxes as $code => $name): 
                            $tax = $taxesFound[$code] ?? null;
                            // Get current rate from database - this is the actual current value
                            $currentRate = $tax ? floatval($tax['rate'] ?? 0) : 0;
                            $taxId = $tax ? intval($tax['id'] ?? 0) : 0;
                            $isActive = $tax ? (intval($tax['is_active'] ?? 0) == 1) : false;
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($name) ?></strong>
                                </td>
                                <td><code><?= htmlspecialchars($code) ?></code></td>
                                <td>
                                    <!-- Current Rate - shows actual database value -->
                                    <span class="badge bg-<?= $currentRate > 0 ? 'success' : 'secondary' ?>">
                                        <?= number_format($currentRate, 2) ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isAdmin): ?>
                                        <!-- Hidden field to pass tax ID for mapping -->
                                        <input type="hidden" name="tax_ids[<?= htmlspecialchars($code) ?>]" value="<?= $taxId ?>">
                                        <!-- New Rate input - will update the current rate when saved -->
                                        <input type="number" 
                                               name="tax_rates[<?= htmlspecialchars($code) ?>]" 
                                               class="form-control form-control-sm" 
                                               value="<?= number_format($currentRate, 2, '.', '') ?>" 
                                               step="0.01" 
                                               min="0" 
                                               max="100"
                                               style="width: 120px;"
                                               <?= $code === 'PAYE' ? 'readonly title="PAYE is progressive - rate calculated based on income brackets"' : '' ?>>
                                    <?php else: ?>
                                        <span class="text-muted"><?= number_format($currentRate, 2) ?>%</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $isActive ? 'success' : 'secondary' ?>">
                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                    </span>
                                    <?php if (!$tax): ?>
                                        <small class="text-danger d-block">Not configured</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($isAdmin): ?>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="submit" class="btn btn-dark" id="saveRatesBtn">
                        <i class="bi bi-save"></i> Update Tax Rates
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($isAdmin): ?>
<script>
document.getElementById('taxRatesForm')?.addEventListener('submit', function(e) {
    if (!confirm('Update tax rates? This will affect all future tax calculations.')) {
        e.preventDefault();
        return false;
    }
});
</script>
<?php endif; ?>
