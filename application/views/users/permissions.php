<?php
$page_title = $page_title ?? 'Manage Permissions';
$userPermissions = $userPermissions ?? [];
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Manage Permissions</h1>
        <span class="badge bg-<?= getRoleBadgeClass($user['role']) ?>"><?= ucfirst(str_replace('_', ' ', $user['role'])) ?></span>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                Permission Matrix
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('users/permissions/' . $user['id']) ?>">
                    <?php echo csrf_field(); ?>
                    <?php if (!empty($modules)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th class="text-center">Create</th>
                                        <th class="text-center">Read</th>
                                        <th class="text-center">Update</th>
                                        <th class="text-center">Delete</th>
                                        <th class="text-center">
                                            <button type="button" class="btn btn-sm btn-link" onclick="selectAll()">Select All</button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($modules as $module): ?>
                                        <tr>
                                            <td><strong><?= ucfirst($module) ?></strong></td>
                                            <?php
                                            $actions = ['create', 'read', 'update', 'delete'];
                                            foreach ($actions as $action):
                                                $permission = null;
                                                foreach ($permissions[$module] ?? [] as $perm) {
                                                    if ($perm['permission'] === $action) {
                                                        $permission = $perm;
                                                        break;
                                                    }
                                                }
                                                if ($permission):
                                            ?>
                                                <td class="text-center">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="permissions[]" 
                                                               value="<?= $permission['id'] ?>" 
                                                               id="perm_<?= $permission['id'] ?>"
                                                               <?= in_array($permission['id'], $userPermissions) ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="perm_<?= $permission['id'] ?>"></label>
                                                    </div>
                                                </td>
                                            <?php else: ?>
                                                <td class="text-center">-</td>
                                            <?php endif; endforeach; ?>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectModule('<?= $module ?>')">
                                                    All
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No permissions available</p>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= base_url('users/edit/' . $user['id']) ?>" class="btn btn-secondary">Back to Edit</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Permissions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function selectAll() {
    const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
}

function selectModule(module) {
    const moduleRow = event.target.closest('tr');
    const checkboxes = moduleRow.querySelectorAll('input[name="permissions[]"]');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
}
</script>

