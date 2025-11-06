<?php
$page_title = $page_title ?? 'Create User';
?>

<div class="page-header">
    <h1 class="page-title mb-0">Create User</h1>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                User Information
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('users/create') ?>">
                    <?php echo csrf_field(); ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <small class="text-muted">Must be at least 8 characters with uppercase, lowercase, number, and special character</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="user">User</option>
                                <option value="staff">Staff</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="<?= base_url('users') ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                Permissions
            </div>
            <div class="card-body">
                <?php if (!empty($modules)): ?>
                    <div class="accordion" id="permissionsAccordion">
                        <?php foreach ($modules as $module): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= ucfirst($module) ?>">
                                        <?= ucfirst($module) ?>
                                    </button>
                                </h2>
                                <div id="collapse<?= ucfirst($module) ?>" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                    <div class="accordion-body">
                                        <?php if (isset($permissions[$module])): ?>
                                            <?php foreach ($permissions[$module] as $perm): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $perm['id'] ?>" id="perm_<?= $perm['id'] ?>">
                                                    <label class="form-check-label" for="perm_<?= $perm['id'] ?>">
                                                        <?= ucfirst($perm['permission']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No permissions available</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                Note
            </div>
            <div class="card-body">
                <p class="small text-muted mb-0">Select permissions to grant to this user. Super Admin role automatically has all permissions.</p>
            </div>
        </div>
    </div>
</div>
