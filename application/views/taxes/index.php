<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Tax Rates</h1>
        <?php if (has_permission('taxes', 'create')): ?>
            <a href="<?= base_url('taxes/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Tax Rate
            </a>
        <?php endif; ?>
    </div>
</div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
    <div class="card-header d-flex justify-content-end py-2">
        <form method="GET" action="" class="d-flex align-items-center gap-2 mb-0 flex-wrap">
            <input type="search" name="search" class="form-control form-control-sm" style="min-width:200px" value="<?= htmlspecialchars(list_search_term()) ?>" placeholder="Search name, ID, code…">
            <input type="hidden" name="page" value="1">
            <label class="small text-muted mb-0">Records</label>
            <?php render_pagination_per_page_select(intval($pagination['per_page'] ?? 50), 'per_page', 'form-select form-select-sm'); ?>
            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tax Name</th>
                            <th>Tax Code</th>
                            <th>Type</th>
                            <th>Rate</th>
                            <th>Tax Inclusive</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($taxes)): ?>
                            <?php foreach ($taxes as $tax): ?>
                                <tr>
                                    <td><?= htmlspecialchars($tax['tax_name']) ?></td>
                                    <td><?= htmlspecialchars($tax['tax_code'] ?? '-') ?></td>
                                    <td><span class="badge bg-info"><?= ucfirst($tax['tax_type']) ?></span></td>
                                    <td>
                                        <?php if ($tax['tax_type'] === 'percentage'): ?>
                                            <?= number_format($tax['rate'], 2) ?>%
                                        <?php else: ?>
                                            <?= format_currency($tax['rate'], 'USD') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $tax['tax_inclusive'] ? 'success' : 'secondary' ?>">
                                            <?= $tax['tax_inclusive'] ? 'Yes' : 'No' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $tax['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($tax['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if (has_permission('taxes', 'read')): ?>
                                                <a href="<?= base_url('taxes/view/' . $tax['id']) ?>" class="btn btn-primary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (has_permission('taxes', 'update')): ?>
                                                <a href="<?= base_url('taxes/edit/' . $tax['id']) ?>" class="btn btn-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (has_permission('taxes', 'delete')): ?>
                                                <a href="<?= base_url('taxes/delete/' . $tax['id']) ?>" class="btn btn-danger" 
                                                   title="Delete" onclick="return confirm('Are you sure you want to delete this tax rate?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No tax rates found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php render_pagination_controls($pagination ?? null); ?>
        </div>
    </div>
</div>


