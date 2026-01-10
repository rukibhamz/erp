<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-puzzle-piece me-2"></i>Booking Add-ons</h5>
                    <div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAddonModal">
                            <i class="fas fa-plus me-1"></i> Add Add-on
                        </button>
                        <a href="<?= site_url('facilities') ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($flash) && $flash): ?>
                        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
                            <?= htmlspecialchars($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($facility) && $facility): ?>
                        <div class="alert alert-info">
                            <strong>Facility:</strong> <?= htmlspecialchars($facility['facility_name'] ?? '') ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <?php if (!empty($addons)): ?>
                            <?php foreach ($addons as $addon): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-0"><?= htmlspecialchars($addon['name'] ?? '') ?></h6>
                                                <span class="badge bg-<?= ($addon['is_active'] ?? 0) ? 'success' : 'secondary' ?>">
                                                    <?= ($addon['is_active'] ?? 0) ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </div>
                                            <p class="card-text text-muted small">
                                                <?= htmlspecialchars($addon['description'] ?? 'No description') ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong class="text-primary">₦<?= number_format(floatval($addon['price'] ?? 0), 2) ?></strong>
                                                    <small class="text-muted">
                                                        / <?= htmlspecialchars($addon['pricing_type'] ?? 'per booking') ?>
                                                    </small>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editAddonModal<?= $addon['id'] ?? '' ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="<?= site_url('resource-management/delete-addon/' . ($addon['id'] ?? '')) ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Delete this add-on?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-light">
                                            <small class="text-muted">
                                                <i class="fas fa-box me-1"></i>
                                                <?php if (($addon['max_quantity'] ?? 0) > 0): ?>
                                                    Max: <?= intval($addon['max_quantity']) ?> per booking
                                                <?php else: ?>
                                                    Unlimited quantity
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-puzzle-piece fa-4x mb-3 d-block opacity-50"></i>
                                    <h5>No Add-ons Configured</h5>
                                    <p>Add-ons allow customers to enhance their booking with additional services or equipment.</p>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddonModal">
                                        <i class="fas fa-plus me-1"></i> Create First Add-on
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Add-on Modal -->
<div class="modal fade" id="addAddonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="facility_id" value="<?= htmlspecialchars($facility['id'] ?? '') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add New Add-on</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Add-on Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="e.g., Projector, Catering, Sound System" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Brief description of the add-on"></textarea>
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
                            <input type="number" class="form-control" name="max_quantity" min="0" value="0" placeholder="0 = unlimited">
                            <small class="text-muted">Leave 0 for unlimited</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <option value="equipment">Equipment</option>
                                <option value="service">Service</option>
                                <option value="catering">Catering</option>
                                <option value="setup">Setup/Decoration</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="addon_active" checked>
                        <label class="form-check-label" for="addon_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Add-on</button>
                </div>
            </form>
        </div>
    </div>
</div>
