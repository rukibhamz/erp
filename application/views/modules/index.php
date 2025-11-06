<?php
$page_title = $page_title ?? 'Module Management';
?>

<div class="page-header">
    <h1 class="page-title mb-0">Module Management</h1>
    <p class="text-muted">Activate, deactivate, and customize module names</p>
</div>

<?php if (isset($flash) && !empty($flash)): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">System Modules</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th>Display Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($modules)): ?>
                                <?php foreach ($modules as $module): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($module['icon']): ?>
                                                    <i class="bi <?= htmlspecialchars($module['icon']) ?> me-2 text-primary"></i>
                                                <?php endif; ?>
                                                <strong><?= htmlspecialchars($module['module_key']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <form method="POST" action="<?= base_url('modules/updateName') ?>" class="d-inline" id="nameForm_<?= $module['id'] ?>">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="module_key" value="<?= htmlspecialchars($module['module_key']) ?>">
                                                <div class="input-group input-group-sm" style="width: 250px;">
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="display_name" 
                                                           value="<?= htmlspecialchars($module['display_name']) ?>"
                                                           id="display_name_<?= $module['id'] ?>"
                                                           maxlength="100"
                                                           required>
                                                    <button type="submit" class="btn btn-outline-primary btn-sm" title="Save name">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($module['description'] ?? 'No description') ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($module['is_active'] == 1): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="<?= base_url('modules/toggle') ?>" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="module_key" value="<?= htmlspecialchars($module['module_key']) ?>">
                                                <input type="hidden" name="is_active" value="<?= $module['is_active'] == 1 ? 0 : 1 ?>">
                                                <button type="submit" 
                                                        class="btn btn-sm <?= $module['is_active'] == 1 ? 'btn-warning' : 'btn-success' ?>"
                                                        onclick="return confirm('<?= $module['is_active'] == 1 ? 'Deactivate' : 'Activate' ?> module \'<?= htmlspecialchars($module['display_name']) ?>\'?')">
                                                    <i class="bi <?= $module['is_active'] == 1 ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                                    <?= $module['is_active'] == 1 ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary ms-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModuleModal_<?= $module['id'] ?>"
                                                    title="Edit module details">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Module Modal -->
                                    <div class="modal fade" id="editModuleModal_<?= $module['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="<?= base_url('modules/update') ?>">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="module_key" value="<?= htmlspecialchars($module['module_key']) ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Module: <?= htmlspecialchars($module['display_name']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Display Name</label>
                                                            <input type="text" 
                                                                   class="form-control" 
                                                                   name="display_name" 
                                                                   value="<?= htmlspecialchars($module['display_name']) ?>"
                                                                   maxlength="100"
                                                                   required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Description</label>
                                                            <textarea class="form-control" 
                                                                      name="description" 
                                                                      rows="3"><?= htmlspecialchars($module['description'] ?? '') ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Icon Class</label>
                                                            <input type="text" 
                                                                   class="form-control" 
                                                                   name="icon" 
                                                                   value="<?= htmlspecialchars($module['icon'] ?? '') ?>"
                                                                   placeholder="bi-icon-name">
                                                            <small class="text-muted">Bootstrap Icons class (e.g., bi-calculator)</small>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Sort Order</label>
                                                            <input type="number" 
                                                                   class="form-control" 
                                                                   name="sort_order" 
                                                                   value="<?= htmlspecialchars($module['sort_order'] ?? 0) ?>"
                                                                   min="0">
                                                            <small class="text-muted">Lower numbers appear first in navigation</small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox"></i> No modules found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Note:</strong> Deactivated modules will be hidden from navigation and inaccessible to all users except super administrators.
        </div>
    </div>
</div>



