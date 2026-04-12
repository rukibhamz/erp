<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Booking Add-ons</h1>
        <div>
            <?php if (has_permission('bookings', 'create')): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddonModal">
                <i class="bi bi-plus-circle"></i> Add Add-on
            </button>
            <?php endif; ?>
            <a href="<?= base_url('bookings') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (!empty($addons)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Pricing</th>
                            <th>Max Qty</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($addons as $addon): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($addon['name'] ?? '') ?></strong>
                                <?php if (!empty($addon['description'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($addon['description']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?= ucfirst(htmlspecialchars($addon['addon_type'] ?? 'other')) ?></span></td>
                            <td><strong>₦<?= number_format(floatval($addon['price'] ?? 0), 2) ?></strong></td>
                            <td><small class="text-muted"><?= htmlspecialchars($addon['pricing_type'] ?? 'per booking') ?></small></td>
                            <td>
                                <?php if (($addon['max_quantity'] ?? 0) > 0): ?>
                                    <?= intval($addon['max_quantity']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Unlimited</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= ($addon['is_active'] ?? 0) ? 'success' : 'secondary' ?>">
                                    <?= ($addon['is_active'] ?? 0) ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if (has_permission('bookings', 'update')): ?>
                                <button type="button" class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAddonModal"
                                        data-id="<?= $addon['id'] ?>"
                                        data-name="<?= htmlspecialchars($addon['name'] ?? '') ?>"
                                        data-description="<?= htmlspecialchars($addon['description'] ?? '') ?>"
                                        data-price="<?= floatval($addon['price'] ?? 0) ?>"
                                        data-pricing-type="<?= htmlspecialchars($addon['pricing_type'] ?? 'per_booking') ?>"
                                        data-max-quantity="<?= intval($addon['max_quantity'] ?? 0) ?>"
                                        data-addon-type="<?= htmlspecialchars($addon['addon_type'] ?? 'other') ?>"
                                        data-is-active="<?= intval($addon['is_active'] ?? 1) ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (has_permission('bookings', 'delete')): ?>
                                <form method="POST" action="<?= base_url('resource-management/delete-addon/' . $addon['id']) ?>"
                                      style="display:inline"
                                      onsubmit="return confirm('Delete this add-on?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-puzzle" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:.4;"></i>
                <h5>No Add-ons Yet</h5>
                <p>Add-ons let customers enhance their booking with extra services or equipment.</p>
                <?php if (has_permission('bookings', 'create')): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddonModal">
                    <i class="bi bi-plus-circle"></i> Create First Add-on
                </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Add-on Modal -->
<div class="modal fade" id="addAddonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= base_url('resource-management/addons') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Add-on</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="e.g. Projector, Sound System" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Brief description"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Pricing Type</label>
                            <select class="form-select" name="pricing_type">
                                <option value="per_booking">Per Booking</option>
                                <option value="per_hour">Per Hour</option>
                                <option value="per_day">Per Day</option>
                                <option value="per_person">Per Person</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Max Quantity</label>
                            <input type="number" class="form-control" name="max_quantity" min="0" value="0">
                            <small class="text-muted">0 = unlimited</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="addon_type">
                                <option value="equipment">Equipment</option>
                                <option value="service">Service</option>
                                <option value="catering">Catering</option>
                                <option value="decoration">Decoration</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="add_is_active" value="1" checked>
                        <label class="form-check-label" for="add_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Add-on</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Add-on Modal (single, populated via JS) -->
<div class="modal fade" id="editAddonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editAddonForm" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Add-on</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" class="form-control" name="price" id="edit_price" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Pricing Type</label>
                            <select class="form-select" name="pricing_type" id="edit_pricing_type">
                                <option value="per_booking">Per Booking</option>
                                <option value="per_hour">Per Hour</option>
                                <option value="per_day">Per Day</option>
                                <option value="per_person">Per Person</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Max Quantity</label>
                            <input type="number" class="form-control" name="max_quantity" id="edit_max_quantity" min="0">
                            <small class="text-muted">0 = unlimited</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="addon_type" id="edit_addon_type">
                                <option value="equipment">Equipment</option>
                                <option value="service">Service</option>
                                <option value="catering">Catering</option>
                                <option value="decoration">Decoration</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <label class="form-check-label" for="edit_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Add-on</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('editAddonModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    if (!btn) return;
    const id = btn.dataset.id;
    document.getElementById('editAddonForm').action = '<?= base_url('resource-management/edit-addon/') ?>' + id;
    document.getElementById('edit_name').value = btn.dataset.name || '';
    document.getElementById('edit_description').value = btn.dataset.description || '';
    document.getElementById('edit_price').value = btn.dataset.price || '';
    document.getElementById('edit_max_quantity').value = btn.dataset.maxQuantity || '0';
    document.getElementById('edit_is_active').checked = btn.dataset.isActive == '1';
    const pt = document.getElementById('edit_pricing_type');
    for (let o of pt.options) o.selected = (o.value === (btn.dataset.pricingType || 'per_booking'));
    const at = document.getElementById('edit_addon_type');
    for (let o of at.options) o.selected = (o.value === (btn.dataset.addonType || 'other'));
});
</script>
