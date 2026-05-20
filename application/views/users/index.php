<?php
$page_title = $page_title ?? 'Users';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Users</h1>
        <div class="d-flex gap-2">
            <?php if (isset($current_user['role']) && $current_user['role'] === 'super_admin'): ?>
                <a href="<?= base_url('users/fix-manager-permissions') ?>" class="btn btn-info" onclick="return confirm('This will assign create, read, update permissions for all modules (except tax) to all manager users. Continue?');">
                    <i class="bi bi-tools"></i> Fix Manager Permissions
                </a>
                <a href="<?= base_url('users/fix-admin-permissions') ?>" class="btn btn-warning" onclick="return confirm('This will assign all permissions to all admin users. Continue?');">
                    <i class="bi bi-tools"></i> Fix Admin Permissions
                </a>
            <?php endif; ?>
            <?php if (isset($canCreate) && $canCreate): ?>
                <a href="<?= base_url('users/create') ?>" class="btn btn-primary">Create User</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-end py-2">
        <form method="GET" action="<?= base_url('users') ?>" class="d-flex align-items-center gap-2 mb-0 flex-wrap">
            <input type="search" name="search" class="form-control form-control-sm" style="min-width:220px"
                   value="<?= htmlspecialchars(list_search_term()) ?>"
                   placeholder="Username, email, name…">
            <input type="hidden" name="page" value="1">
            <label class="small text-muted mb-0">Records</label>
            <?php render_pagination_per_page_select(intval($pagination['per_page'] ?? 50), 'per_page', 'form-select form-select-sm'); ?>
            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
        </form>
    </div>
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
                                    <a href="<?= base_url('users/edit/' . $user['id']) ?>" class="btn btn-sm btn-primary">
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

<?php render_pagination_controls($pagination ?? null); ?>
    </div>
</div>

