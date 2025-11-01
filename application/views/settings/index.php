<?php
$page_title = $page_title ?? 'Settings';
?>

<div class="page-header">
    <h1 class="page-title mb-0">Settings</h1>
</div>

<div class="row">
    <div class="col-lg-3 mb-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3">Settings Menu</h6>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <a href="<?= base_url('settings') ?>" class="text-decoration-none d-flex align-items-center text-dark fw-bold">
                            <i class="bi bi-gear me-2"></i> General Settings
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= base_url('settings/modules') ?>" class="text-decoration-none d-flex align-items-center text-muted">
                            <i class="bi bi-puzzle me-2"></i> Modules
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= base_url('settings/payment-gateways') ?>" class="text-decoration-none d-flex align-items-center text-muted">
                            <i class="bi bi-credit-card me-2"></i> Payment Gateways
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">General Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('settings') ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="site_name" class="form-label">Site Name</label>
                            <input type="text" class="form-control" id="site_name" name="site_name" value="<?= htmlspecialchars($config['site_name'] ?? 'Business ERP') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="site_email" class="form-label">Site Email</label>
                            <input type="email" class="form-control" id="site_email" name="site_email" value="<?= htmlspecialchars($config['site_email'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="timezone" class="form-label">Timezone</label>
                            <select class="form-select" id="timezone" name="timezone">
                                <option value="UTC" <?= ($config['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                <option value="America/New_York" <?= ($config['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>Eastern Time (US)</option>
                                <option value="America/Chicago" <?= ($config['timezone'] ?? '') === 'America/Chicago' ? 'selected' : '' ?>>Central Time (US)</option>
                                <option value="America/Denver" <?= ($config['timezone'] ?? '') === 'America/Denver' ? 'selected' : '' ?>>Mountain Time (US)</option>
                                <option value="America/Los_Angeles" <?= ($config['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : '' ?>>Pacific Time (US)</option>
                                <option value="Europe/London" <?= ($config['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' ?>>London</option>
                                <option value="Europe/Paris" <?= ($config['timezone'] ?? '') === 'Europe/Paris' ? 'selected' : '' ?>>Paris</option>
                                <option value="Asia/Tokyo" <?= ($config['timezone'] ?? '') === 'Asia/Tokyo' ? 'selected' : '' ?>>Tokyo</option>
                                <option value="Asia/Dubai" <?= ($config['timezone'] ?? '') === 'Asia/Dubai' ? 'selected' : '' ?>>Dubai</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="date_format" class="form-label">Date Format</label>
                            <select class="form-select" id="date_format" name="date_format">
                                <option value="Y-m-d" <?= ($config['date_format'] ?? 'Y-m-d') === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                                <option value="m/d/Y" <?= ($config['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                                <option value="d/m/Y" <?= ($config['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                                <option value="Y-m-d H:i:s" <?= ($config['date_format'] ?? '') === 'Y-m-d H:i:s' ? 'selected' : '' ?>>YYYY-MM-DD HH:MM:SS</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="maintenance_mode" class="form-label">Maintenance Mode</label>
                        <select class="form-select" id="maintenance_mode" name="maintenance_mode">
                            <option value="0" <?= ($config['maintenance_mode'] ?? 0) == 0 ? 'selected' : '' ?>>Disabled</option>
                            <option value="1" <?= ($config['maintenance_mode'] ?? 0) == 1 ? 'selected' : '' ?>>Enabled</option>
                        </select>
                        <small class="text-muted">When enabled, only administrators can access the site.</small>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

