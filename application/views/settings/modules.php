<?php
$page_title = $page_title ?? 'Module Settings';
?>

<div class="page-header">
    <h1 class="page-title mb-0">Module Settings</h1>
</div>

<div class="row">
    <div class="col-lg-3 mb-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3">Settings Menu</h6>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <a href="<?= base_url('settings') ?>" class="text-decoration-none d-flex align-items-center text-muted">
                            <i class="bi bi-gear me-2"></i> General Settings
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= base_url('settings/modules') ?>" class="text-decoration-none d-flex align-items-center text-dark fw-bold">
                            <i class="bi bi-puzzle me-2"></i> Modules
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Available Modules</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Module Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <i class="bi bi-building me-2"></i>
                                    <strong>Companies</strong>
                                </td>
                                <td>Manage company information and details</td>
                                <td>
                                    <span class="badge bg-success">Active</span>
                                </td>
                                <td>
                                    <a href="<?= base_url('companies') ?>" class="btn btn-sm btn-primary">Manage</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="bi bi-people me-2"></i>
                                    <strong>Users</strong>
                                </td>
                                <td>User management and access control</td>
                                <td>
                                    <span class="badge bg-success">Active</span>
                                </td>
                                <td>
                                    <a href="<?= base_url('users') ?>" class="btn btn-sm btn-primary">Manage</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="bi bi-file-text me-2"></i>
                                    <strong>Activity Log</strong>
                                </td>
                                <td>View system activity and user actions</td>
                                <td>
                                    <span class="badge bg-success">Active</span>
                                </td>
                                <td>
                                    <a href="<?= base_url('activity') ?>" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="bi bi-gear me-2"></i>
                                    <strong>Settings</strong>
                                </td>
                                <td>System configuration and preferences</td>
                                <td>
                                    <span class="badge bg-success">Active</span>
                                </td>
                                <td>
                                    <a href="<?= base_url('settings') ?>" class="btn btn-sm btn-primary">Configure</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> More modules will be available in future updates. Module management features will be added to enable/disable modules as needed.
                </div>
            </div>
        </div>
    </div>
</div>

