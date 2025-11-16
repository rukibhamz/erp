<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create WHT Return</h1>
        <a href="<?= base_url('tax/wht') ?>" class="btn btn-outline-dark">
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
        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> WHT Return Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('tax/wht/create') ?>
            <?php echo csrf_field(); ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Month <span class="text-danger">*</span></label>
                    <select name="month" class="form-select" required>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= $i == date('m') ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Year <span class="text-danger">*</span></label>
                    <select name="year" class="form-select" required>
                        <?php for ($i = date('Y'); $i >= date('Y') - 2; $i--): ?>
                            <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> The system will automatically calculate total WHT from all WHT transactions for the selected month.
            </div>
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?= base_url('tax/wht') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-dark">Create Return</button>
            </div>
        </form>
    </div>
</div>
