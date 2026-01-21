<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-shield-lock"></i> Roles & Permissions
        </h1>
        <a href="<?= base_url('settings') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Settings
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-people-fill"></i> System Roles
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Role Name</th>
                            <th>Code</th>
                            <th>Description</th>
                            <th class="text-center">Permissions</th>
                            <th class="text-center">System</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($roles)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No roles found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($roles as $role): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($role['role_name']) ?></strong>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($role['role_code']) ?></code>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($role['description'] ?? '-') ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $permCount = intval($role['permission_count'] ?? 0);
                                        $percentage = $total_permissions > 0 ? round(($permCount / $total_permissions) * 100) : 0;
                                        $badgeClass = 'secondary';
                                        if ($percentage == 100) $badgeClass = 'success';
                                        elseif ($percentage >= 50) $badgeClass = 'primary';
                                        elseif ($percentage >= 25) $badgeClass = 'warning';
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>">
                                            <?= $permCount ?> / <?= $total_permissions ?>
                                        </span>
                                        <div class="progress mt-1" style="height: 4px;">
                                            <div class="progress-bar bg-<?= $badgeClass ?>" style="width: <?= $percentage ?>%"></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($role['is_system']): ?>
                                            <span class="badge bg-info">System</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Custom</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($role['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('settings/edit-role/' . $role['id']) ?>" 
                                           class="btn btn-sm btn-primary"
                                           title="Edit Permissions">
                                            <i class="bi bi-pencil"></i> Edit Permissions
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Role Permission Guidelines</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><span class="badge bg-success">Super Admin / Admin</span></h6>
                    <p class="small text-muted mb-3">Full system access. Can manage users, settings, and all modules.</p>
                    
                    <h6><span class="badge bg-primary">Manager</span></h6>
                    <p class="small text-muted mb-3">Operational access. Can manage bookings, inventory, POS, and view reports. Cannot delete financial records or change settings.</p>
                </div>
                <div class="col-md-6">
                    <h6><span class="badge bg-warning text-dark">Staff</span></h6>
                    <p class="small text-muted mb-3">Limited access. Can create/update in POS, bookings, inventory. Cannot delete records.</p>
                    
                    <h6><span class="badge bg-secondary">Customer</span></h6>
                    <p class="small text-muted mb-3">Minimal access. Can view dashboard and own bookings only.</p>
                </div>
            </div>
        </div>
    </div>
</div>
