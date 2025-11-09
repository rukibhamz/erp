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
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tax Type</th>
                        <th>Code</th>
                        <th>Current Rate (%)</th>
                        <th>New Rate (%)</th>
                        <th>Status</th>
                        <th>Action</th>
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
                                    <form method="POST" action="<?= base_url('tax/config/updateRate') ?>" class="d-inline" id="form_<?= htmlspecialchars($code) ?>">
                                        <input type="hidden" name="tax_id" value="<?= $taxId ?>">
                                        <input type="hidden" name="tax_code" value="<?= htmlspecialchars($code) ?>">
                                        <div class="input-group input-group-sm" style="width: 200px;">
                                            <input type="number" 
                                                   name="tax_rate" 
                                                   class="form-control" 
                                                   value="<?= number_format($currentRate, 2, '.', '') ?>" 
                                                   step="0.01" 
                                                   min="0" 
                                                   max="100"
                                                   id="rate_<?= htmlspecialchars($code) ?>"
                                                   <?= $code === 'PAYE' ? 'readonly title="PAYE is progressive - rate calculated based on income brackets"' : '' ?>>
                                            <button type="submit" class="btn btn-dark" title="Update <?= htmlspecialchars($code) ?> rate">
                                                <i class="bi bi-save"></i> Update
                                            </button>
                                        </div>
                                    </form>
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
                            <td>
                                <?php if ($isAdmin && $taxId > 0): ?>
                                    <a href="<?= base_url('tax/config/edit/' . $taxId) ?>" class="btn btn-sm btn-outline-dark" title="Edit Tax Details">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<script>
// Add confirmation and feedback for individual updates
document.querySelectorAll('form[id^="form_"]').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        const code = form.querySelector('input[name="tax_code"]').value;
        const rate = parseFloat(form.querySelector('input[name="tax_rate"]').value);
        
        if (!confirm('Update ' + code + ' rate to ' + rate.toFixed(2) + '%?')) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        const btn = form.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Updating...';
    });
});
</script>
<?php endif; ?>
