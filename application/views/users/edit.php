<?php
$page_title = $page_title ?? 'Edit User';
$userPermissions = $userPermissions ?? [];
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0"><?= htmlspecialchars($page_title) ?></h1>
        <nav aria-label="breadcrumb" class="mt-2">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('users') ?>">Users</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-person-gear"></i> User Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('users/edit/' . $user['id']) ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8">
                            <small class="text-muted">Leave blank to keep current password</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="manager" <?= $user['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="super_admin" <?= $user['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                <option value="locked" <?= $user['status'] === 'locked' ? 'selected' : '' ?>>Locked</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('users') ?>" class="btn btn-secondary">Cancel</a>
                        <a href="<?= base_url('users/permissions/' . $user['id']) ?>" class="btn btn-info">
                            <i class="bi bi-shield-check"></i> Permissions
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> User Stats</h5>
            </div>
            <div class="card-body">
                <p><strong>Created:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                <?php if ($user['last_login']): ?>
                    <p><strong>Last Login:</strong> <?= date('M d, Y H:i', strtotime($user['last_login'])) ?></p>
                <?php else: ?>
                    <p><strong>Last Login:</strong> Never</p>
                <?php endif; ?>
                <?php if ($user['failed_login_attempts'] > 0): ?>
                    <p><strong>Failed Logins:</strong> <span class="badge bg-warning"><?= $user['failed_login_attempts'] ?></span></p>
                <?php endif; ?>
                <?php if ($user['locked_until']): ?>
                    <p><strong>Locked Until:</strong> <span class="badge bg-danger"><?= date('M d, Y H:i', strtotime($user['locked_until'])) ?></span></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
