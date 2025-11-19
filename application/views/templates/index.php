<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Templates</h1>
        <?php if (has_permission('settings', 'create')): ?>
            <a href="<?= base_url('templates/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Template
            </a>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="invoice" <?= $selected_type === 'invoice' ? 'selected' : '' ?>>Invoice</option>
                        <option value="bill" <?= $selected_type === 'bill' ? 'selected' : '' ?>>Bill</option>
                        <option value="estimate" <?= $selected_type === 'estimate' ? 'selected' : '' ?>>Estimate</option>
                        <option value="receipt" <?= $selected_type === 'receipt' ? 'selected' : '' ?>>Receipt</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-secondary">Filter</button>
                    <a href="<?= base_url('templates') ?>" class="btn btn-primary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Templates Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Template Name</th>
                            <th>Type</th>
                            <th>Default</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($templates)): ?>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><?= htmlspecialchars($template['template_name']) ?></td>
                                    <td><span class="badge bg-info"><?= ucfirst($template['template_type']) ?></span></td>
                                    <td>
                                        <?php if ($template['is_default']): ?>
                                            <span class="badge bg-success">Default</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $template['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($template['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (has_permission('settings', 'update')): ?>
                                            <a href="<?= base_url('templates/edit/' . $template['id']) ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No templates found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


