<?php
$page_title = $page_title ?? 'Users';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Users</h1>
        <a href="<?= base_url('users/create') ?>" class="btn btn-primary">Create User</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
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
                                <td>
                                    <?php 
                                    $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                                    echo htmlspecialchars($fullName ?: $user['username']);
                                    ?>
                                    <br><small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><span class="badge bg-<?= getRoleBadgeClass($user['role']) ?>"><?= ucfirst(str_replace('_', ' ', $user['role'])) ?></span></td>
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

