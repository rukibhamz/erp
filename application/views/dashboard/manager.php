<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header mb-4">
    <h1 class="page-title mb-0">Manager Dashboard</h1>
    <p class="text-muted mb-0">Department-specific metrics and KPIs</p>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-speedometer2" style="font-size: 3rem; color: #ccc;"></i>
        <p class="text-muted mt-3">Manager dashboard is being configured based on your assigned departments and properties.</p>
    </div>
</div>



