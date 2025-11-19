<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create WHT Return</h1>
        <a href="<?= base_url('tax/wht') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/tax/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_url('tax/wht/create') ?>">
            <?php echo csrf_field(); ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="month" class="form-label">Month <span class="text-danger">*</span></label>
                    <select class="form-select" id="month" name="month" required>
                        <option value="">Select Month</option>
                        <?php
                        $months = [
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                        foreach ($months as $num => $name): ?>
                            <option value="<?= $num ?>" <?= date('n') == $num ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
                    <select class="form-select" id="year" name="year" required>
                        <option value="">Select Year</option>
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= date('Y') == $y ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> 
                <strong>Note:</strong> The WHT return will be calculated automatically based on WHT transactions 
                recorded for the selected month and year. The return will include a schedule of WHT by type 
                (Rent, Professional Services, etc.) and calculate the total WHT payable.
            </div>

            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> 
                <strong>Important:</strong> Ensure all WHT transactions for this period are properly recorded 
                before creating the WHT return. Only one return can be created per month/year period.
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('tax/wht') ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create WHT Return
                </button>
            </div>
        </form>
    </div>
</div>

