<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-shield-lock"></i> <?= htmlspecialchars($page_title) ?>
        </h1>
        <a href="<?= base_url('settings/roles') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Roles
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($role): ?>
        <form method="POST">
            <?php echo csrf_field(); ?>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Role Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Role Name</label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($role['role_name']) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Role Code</label>
                                <p class="form-control-plaintext"><code><?= htmlspecialchars($role['role_code']) ?></code></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <p class="form-control-plaintext text-muted"><?= htmlspecialchars($role['description'] ?? 'No description') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <p class="form-control-plaintext">
                                    <?php if ($role['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                    <?php if ($role['is_system']): ?>
                                        <span class="badge bg-info">System Role</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <hr>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Permissions
                                </button>
                                <a href="<?= base_url('settings/roles') ?>" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (in_array($role['role_code'], ['super_admin', 'admin'])): ?>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> Admin roles automatically have full access and bypass permission checks.
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Module Permissions</h5>
                                <div>
                                    <button type="button" class="btn btn-sm btn-success" onclick="selectAll()">
                                        <i class="bi bi-check-all"></i> Select All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAll()">
                                        <i class="bi bi-x-lg"></i> Clear All
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($permissions)): ?>
                                <p class="text-muted text-center py-4">No permissions found in the system.</p>
                            <?php else: ?>
                                <div class="accordion" id="permissionsAccordion">
                                    <?php $i = 0; foreach ($permissions as $module => $modulePerms): $i++; ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button <?= $i > 1 ? 'collapsed' : '' ?>" 
                                                        type="button" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#module_<?= $i ?>">
                                                    <i class="bi bi-folder me-2"></i>
                                                    <strong><?= ucfirst(htmlspecialchars($module)) ?></strong>
                                                    <span class="badge bg-secondary ms-2"><?= count($modulePerms) ?> permissions</span>
                                                </button>
                                            </h2>
                                            <div id="module_<?= $i ?>" 
                                                 class="accordion-collapse collapse <?= $i == 1 ? 'show' : '' ?>" 
                                                 data-bs-parent="#permissionsAccordion">
                                                <div class="accordion-body">
                                                    <div class="row">
                                                        <?php foreach ($modulePerms as $perm): ?>
                                                            <div class="col-md-4 mb-2">
                                                                <div class="form-check">
                                                                    <input class="form-check-input permission-checkbox" 
                                                                           type="checkbox" 
                                                                           name="permissions[]" 
                                                                           value="<?= $perm['id'] ?>"
                                                                           id="perm_<?= $perm['id'] ?>"
                                                                           <?= in_array($perm['id'], $role_permissions) ? 'checked' : '' ?>>
                                                                    <label class="form-check-label" for="perm_<?= $perm['id'] ?>">
                                                                        <?php
                                                                        $permIcon = 'bi-eye';
                                                                        if ($perm['permission'] === 'write' || $perm['permission'] === 'create') $permIcon = 'bi-plus-circle';
                                                                        elseif ($perm['permission'] === 'update') $permIcon = 'bi-pencil';
                                                                        elseif ($perm['permission'] === 'delete') $permIcon = 'bi-trash';
                                                                        ?>
                                                                        <i class="bi <?= $permIcon ?>"></i>
                                                                        <?= ucfirst($perm['permission']) ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    
                                                    <div class="mt-2">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="selectModule(<?= $i ?>)">
                                                            Select All in <?= ucfirst($module) ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-danger">Role not found.</div>
    <?php endif; ?>
</div>

<script>
function selectAll() {
    document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = true);
}

function clearAll() {
    document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
}

function selectModule(moduleIndex) {
    const container = document.getElementById('module_' + moduleIndex);
    container.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = true);
}
</script>
