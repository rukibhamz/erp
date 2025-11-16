<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Template</h1>
        <a href="<?= base_url('templates') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Template Name *</label>
                            <input type="text" name="template_name" class="form-control" value="<?= htmlspecialchars($template['template_name']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Template Type</label>
                            <input type="text" class="form-control" value="<?= ucfirst($template['template_type']) ?>" disabled>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Template HTML *</label>
                    <textarea name="template_html" class="form-control" rows="20" required><?= htmlspecialchars($template['template_html']) ?></textarea>
                    <small class="text-muted">
                        Use placeholders like {{invoice_number}}, {{customer_name}}, {{total_amount}}, etc.
                    </small>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_default" id="is_default" value="1" 
                                   <?= $template['is_default'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_default">
                                Set as Default Template
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $template['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $template['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('templates') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Template</button>
                </div>
            </form>
        </div>
    </div>
</div>


