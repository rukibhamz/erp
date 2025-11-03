<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title mb-0">Report Builder</h1>
            <p class="text-muted mb-0">Create and manage custom reports</p>
        </div>
        <a href="<?= base_url('report-builder/create') ?>" class="btn btn-dark">
            <i class="bi bi-plus-circle"></i> Create Report
        </a>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <?php if (empty($reports)): ?>
        <div class="col-12">
            <div class="card text-center py-5">
                <div class="card-body">
                    <i class="bi bi-file-earmark-text display-1 text-muted"></i>
                    <h4 class="mt-3">No Reports Yet</h4>
                    <p class="text-muted">Create your first custom report to get started.</p>
                    <a href="<?= base_url('report-builder/create') ?>" class="btn btn-dark mt-3">
                        <i class="bi bi-plus-circle"></i> Create Report
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($reports as $report): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($report['report_name']) ?></h5>
                            <span class="badge bg-dark"><?= ucfirst($report['report_type']) ?></span>
                        </div>
                        <p class="text-muted small mb-2"><?= htmlspecialchars($report['description'] ?? '') ?></p>
                        <div class="small text-muted mb-3">
                            <i class="bi bi-folder"></i> <?= htmlspecialchars($report['module']) ?><br>
                            <i class="bi bi-database"></i> <?= htmlspecialchars($report['data_source']) ?>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?= base_url('report-builder/view/' . $report['id']) ?>" class="btn btn-sm btn-outline-dark">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="<?= base_url('report-builder/export/' . $report['id'] . '/csv') ?>" class="btn btn-sm btn-outline-dark">
                                <i class="bi bi-download"></i> Export
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


