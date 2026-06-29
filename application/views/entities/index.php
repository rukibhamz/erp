<?php
$page_title = $page_title ?? 'Entities';
$perPage = intval($pagination['per_page'] ?? 50);
$hasFilters = list_search_term() !== '';
?>

<div class="page-header list-filters-page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Entities</h1>
        <?php if (has_permission('entities', 'create')): ?>
            <a href="<?= base_url('entities/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Entity
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4 list-filters-card">
    <div class="card-body">
        <form method="GET" action="<?= base_url('entities') ?>" class="list-filters-form">
            <div class="row g-2 align-items-end list-filters-row">
                <?php
                $search_col_class = 'col-12 col-md';
                $search_placeholder = 'Name, email, phone, tax ID…';
                include(BASEPATH . 'views/partials/list_search_field.php');
                ?>
                <?php render_list_filter_per_page($perPage); ?>
                <?php render_list_filter_submit_buttons(base_url('entities')); ?>
            </div>

            <?php if ($hasFilters): ?>
            <div class="list-active-filters">
                <span class="small text-muted me-1"><i class="bi bi-funnel"></i> Active:</span>
                <span class="badge bg-secondary">Search: <?= htmlspecialchars(list_search_term()) ?></span>
                <a href="<?= base_url('entities') ?>" class="small ms-1">Clear all</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php
        $bulk_delete_enabled = has_permission('entities', 'delete');
        bulk_delete_render_toolbar($bulk_delete_enabled, $entities, base_url('entities/bulk-delete'), 'entity');
        ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <?php bulk_delete_render_checkbox_th($bulk_delete_enabled); ?>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Currency</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($entities)): ?>
                        <?php foreach ($entities as $entity): ?>
                            <tr>
                                <?php bulk_delete_render_checkbox_td($bulk_delete_enabled, (int)$entity['id'], 'entity ' . ($entity['name'] ?? '')); ?>
                                <td><?= htmlspecialchars($entity['id'] ?? '') ?></td>
                                <td><strong><?= htmlspecialchars($entity['name'] ?? '') ?></strong></td>
                                <td><?= htmlspecialchars($entity['address'] ?? '') ?></td>
                                <td><?= htmlspecialchars($entity['phone'] ?? '') ?></td>
                                <td><?= htmlspecialchars($entity['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($entity['currency'] ?? 'USD') ?></td>
                                <td><?= $entity['created_at'] ? date('M d, Y', strtotime($entity['created_at'])) : '-' ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if (has_permission('entities', 'read')): ?>
                                            <a href="<?= base_url('entities/view/' . $entity['id']) ?>" class="btn btn-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (has_permission('entities', 'update')): ?>
                                            <a href="<?= base_url('entities/edit/' . $entity['id']) ?>" class="btn btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (has_permission('entities', 'delete')): ?>
                                            <form method="post" action="<?= base_url('entities/delete/' . $entity['id']) ?>" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this entity?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= bulk_delete_colspan(8, $bulk_delete_enabled) ?>" class="text-center text-muted">No entities found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php render_pagination_controls($pagination ?? null); ?>
    </div>
</div>
