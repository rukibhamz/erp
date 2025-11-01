<?php
$page_title = $page_title ?? 'Users';
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-0"><?= htmlspecialchars($page_title) ?></h1>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Users</li>
                </ol>
            </nav>
        </div>
        <a href="<?= base_url('users/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create User
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'manager' ? 'warning' : 'secondary') ?>"><?= ucfirst($user['role']) ?></span></td>
                                <td><span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($user['status']) ?></span></td>
                                <td><small class="text-muted"><?= date('M d, Y', strtotime($user['created_at'])) ?></small></td>
                                <td>
                                    <a href="<?= base_url('users/edit/' . $user['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

