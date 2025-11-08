<?php
$page_title = $title ?? 'Module Customization';
include 'application/views/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="page-header mb-4">
        <h1 class="page-title">Module Customization</h1>
        <p class="page-description">
            Customize module names, icons, and visibility. Changes will be visible to all users.
        </p>
    </div>

    <!-- Success/Error Messages -->
    <div id="message-container"></div>

    <!-- Module List -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Navigation Modules</h2>
            <p class="text-muted mb-0">Drag to reorder, click to edit</p>
        </div>
        <div class="card-body">
            <div id="module-list" class="module-list">
                <?php foreach ($modules as $module): ?>
                <div class="module-item" 
                     data-module-code="<?= htmlspecialchars($module['module_code']) ?>"
                     data-order="<?= htmlspecialchars($module['display_order']) ?>">
                    <div class="module-item-drag">
                        <i class="bi bi-grip-vertical"></i>
                    </div>
                    <div class="module-item-icon">
                        <i class="<?= htmlspecialchars($module['icon_class'] ?? 'bi bi-circle') ?>" 
                           id="icon-<?= htmlspecialchars($module['module_code']) ?>"></i>
                    </div>
                    <div class="module-item-content">
                        <div class="module-item-label">
                            <strong><?= htmlspecialchars($module['display_label']) ?></strong>
                            <span class="text-muted">(<?= htmlspecialchars($module['module_code']) ?>)</span>
                        </div>
                        <?php if (!empty($module['custom_label']) && $module['custom_label'] !== $module['default_label']): ?>
                        <div class="module-item-meta">
                            <span class="badge badge-info">Customized</span>
                            <span class="text-muted">Default: <?= htmlspecialchars($module['default_label']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="module-item-actions">
                        <!-- Toggle Visibility -->
                        <label class="toggle-switch" title="Toggle Visibility">
                            <input type="checkbox" 
                                   class="visibility-toggle"
                                   data-module-code="<?= htmlspecialchars($module['module_code']) ?>"
                                   <?= $module['is_active'] ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <!-- Edit Button -->
                        <button class="btn btn-sm btn-ghost edit-module"
                                data-module-code="<?= htmlspecialchars($module['module_code']) ?>"
                                data-default-label="<?= htmlspecialchars($module['default_label']) ?>"
                                data-custom-label="<?= htmlspecialchars($module['custom_label'] ?? '') ?>"
                                data-icon-class="<?= htmlspecialchars($module['icon_class'] ?? '') ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <!-- Reset Button -->
                        <?php if (!empty($module['custom_label'])): ?>
                        <button class="btn btn-sm btn-ghost reset-module"
                                data-module-code="<?= htmlspecialchars($module['module_code']) ?>"
                                title="Reset to default">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Change History -->
    <?php if (!empty($history)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h2 class="card-title">Recent Changes</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Default Label</th>
                            <th>Custom Label</th>
                            <th>Changed By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $change): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($change['module_code']) ?></code></td>
                            <td><?= htmlspecialchars($change['default_label']) ?></td>
                            <td><strong><?= htmlspecialchars($change['custom_label']) ?></strong></td>
                            <td><?= htmlspecialchars($change['updated_by_name'] ?? 'Unknown') ?></td>
                            <td><?= date('M d, Y H:i', strtotime($change['updated_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Edit Module Modal -->
<div id="edit-module-modal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeEditModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Module</h3>
            <button class="modal-close" onclick="closeEditModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="edit-module-form">
                <input type="hidden" id="edit-module-code" name="module_code">
                
                <div class="form-group">
                    <label class="form-label">Module Code</label>
                    <input type="text" id="edit-module-code-display" class="form-control" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Default Label</label>
                    <input type="text" id="edit-default-label" class="form-control" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label required">Custom Label</label>
                    <input type="text" 
                           id="edit-custom-label" 
                           name="custom_label" 
                           class="form-control" 
                           placeholder="Enter custom label"
                           maxlength="100"
                           required>
                    <span class="form-text">This label will be displayed to all users</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Icon Class</label>
                    <div class="icon-picker">
                        <input type="text" 
                               id="edit-icon-class" 
                               name="icon_class" 
                               class="form-control" 
                               placeholder="bi bi-house">
                        <div class="icon-preview">
                            <i id="edit-icon-preview" class="bi bi-house"></i>
                        </div>
                    </div>
                    <span class="form-text">Bootstrap Icons: bi bi-house, bi bi-calculator, bi bi-calendar, etc.</span>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveModuleEdit()">Save Changes</button>
        </div>
    </div>
</div>

<link href="<?= base_url('assets/css/design-system.css') ?>" rel="stylesheet">
<link href="<?= base_url('assets/css/module-customization.css') ?>" rel="stylesheet">
<script src="<?= base_url('assets/js/module-customization.js') ?>"></script>

<?php include 'application/views/layouts/footer.php'; ?>

