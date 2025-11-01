<?php
$page_title = $page_title ?? 'Create User';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0"><?= htmlspecialchars($page_title) ?></h1>
        <nav aria-label="breadcrumb" class="mt-2">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('users') ?>">Users</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="<?= base_url('users/create') ?>">
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
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role">
                        <option value="user">User</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= base_url('users') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create User
                </button>
            </div>
        </form>
    </div>
</div>

