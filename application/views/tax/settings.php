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
