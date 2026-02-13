<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-plus-circle"></i> Create Financial Year</h1>
        <a href="<?= base_url('financial-years') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= base_url('financial-years/create') ?>">
                <?php echo csrf_field(); ?>

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Name <small class="text-muted">(optional — auto-generated if blank)</small></label>
                        <input type="text" name="name" class="form-control" 
                               placeholder="e.g. FY 2026/2027"
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" required
                               value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" required
                               value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Create Financial Year
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
