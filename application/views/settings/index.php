<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header mb-4">
    <h1 class="page-title mb-0">Settings</h1>
    <p class="text-muted">Manage system configuration and preferences</p>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Settings Cards Grid -->
<div class="row g-4">
    <!-- System Settings -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-primary text-white me-3">
                        <i class="bi bi-gear-fill" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">System Settings</h5>
                        <p class="text-muted small mb-0">Company, Email, SMS & Preferences</p>
                    </div>
                </div>
                <p class="card-text text-muted small">
                    Configure company information, email settings, SMS configuration, and system preferences.
                </p>
                <a href="<?= base_url('settings/system') ?>" class="btn btn-primary w-100">
                    <i class="bi bi-arrow-right"></i> Open System Settings
                </a>
            </div>
        </div>
    </div>

    <!-- Payment Gateways -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-success text-white me-3">
                        <i class="bi bi-credit-card-fill" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Payment Gateways</h5>
                        <p class="text-muted small mb-0">Payment Integration</p>
                    </div>
                </div>
                <p class="card-text text-muted small">
                    Configure and manage payment gateway integrations for processing transactions.
                </p>
                <a href="<?= base_url('settings/payment-gateways') ?>" class="btn btn-primary w-100">
                    <i class="bi bi-arrow-right"></i> Manage Gateways
                </a>
            </div>
        </div>
    </div>

    <!-- Module Settings -->
    <?php if (hasPermission('settings', 'update')): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-info text-white me-3">
                        <i class="bi bi-grid-3x3-gap-fill" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Module Settings</h5>
                        <p class="text-muted small mb-0">Module Management</p>
                    </div>
                </div>
                <p class="card-text text-muted small">
                    Configure module visibility and settings for different system modules.
                </p>
                <a href="<?= base_url('settings/modules') ?>" class="btn btn-primary w-100">
                    <i class="bi bi-arrow-right"></i> Configure Modules
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Backup & Restore -->
    <?php if (hasPermission('settings', 'update')): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-warning text-white me-3">
                        <i class="bi bi-database-fill" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Backup & Restore</h5>
                        <p class="text-muted small mb-0">Data Management</p>
                    </div>
                </div>
                <p class="card-text text-muted small">
                    Create database backups and restore from previous backups to protect your data.
                </p>
                <a href="<?= base_url('settings/backup') ?>" class="btn btn-primary w-100">
                    <i class="bi bi-arrow-right"></i> Backup & Restore
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Customize Modules (Super Admin Only) -->
    <?php if (in_array($current_user['role'] ?? '', ['super_admin'])): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-purple text-white me-3">
                        <i class="bi bi-palette-fill" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Customize Modules</h5>
                        <p class="text-muted small mb-0">Module Customization</p>
                    </div>
                </div>
                <p class="card-text text-muted small">
                    Customize module names, icons, and visibility for the navigation menu.
                </p>
                <a href="<?= base_url('module_customization') ?>" class="btn btn-primary w-100">
                    <i class="bi bi-arrow-right"></i> Customize Modules
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Roles & Permissions (Admin Only) -->
    <?php if (in_array($current_user['role'] ?? '', ['super_admin', 'admin'])): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-danger text-white me-3">
                        <i class="bi bi-shield-lock-fill" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Roles & Permissions</h5>
                        <p class="text-muted small mb-0">Access Control</p>
                    </div>
                </div>
                <p class="card-text text-muted small">
                    Manage user roles and permissions. Control what each role can access in the system.
                </p>
                <a href="<?= base_url('settings/roles') ?>" class="btn btn-primary w-100">
                    <i class="bi bi-arrow-right"></i> Manage Roles
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.icon-box {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

.bg-purple {
    background-color: #6f42c1 !important;
}

.card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
}
</style>

