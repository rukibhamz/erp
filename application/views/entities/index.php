<?php
$page_title = $page_title ?? 'Entities';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Entities</h1>
        <?php if (has_permission('entities', 'create')): ?>
            <a href="<?= base_url('entities/create') ?>" class="btn btn-primary">Create Entity</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
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
                                            <a href="<?= base_url('entities/delete/' . $entity['id']) ?>" class="btn btn-danger" 
                                               title="Delete" onclick="return confirm('Are you sure you want to delete this entity?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No entities found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

