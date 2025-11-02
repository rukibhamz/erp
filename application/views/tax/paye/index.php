<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <h1 class="page-title mb-0">PAYE (Pay As You Earn)</h1>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-person-badge" style="font-size: 3rem; color: #ccc;"></i>
        <p class="text-muted mt-3">PAYE module coming soon. This will integrate with the Payroll system to automatically calculate and track PAYE deductions.</p>
        <a href="<?= base_url('payroll') ?>" class="btn btn-dark">
            <i class="bi bi-arrow-left"></i> Go to Payroll
        </a>
    </div>
</div>
