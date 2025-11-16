<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Location</h1>
        <a href="<?= base_url('inventory/locations') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/inventory/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="<?= base_url('inventory/locations/create') ?>
            <?php echo csrf_field(); ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="location_code" class="form-label">Location Code</label>
                    <input type="text" class="form-control" id="location_code" name="location_code" placeholder="Leave blank for auto-generation">
                    <small class="text-muted">Auto-generated if left blank</small>
                </div>
                
                <div class="col-md-6">
                    <label for="location_name" class="form-label">Location Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="location_name" name="location_name" required>
                </div>
                
                <div class="col-md-6">
                    <label for="location_type" class="form-label">Location Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="location_type" name="location_type" required>
                        <option value="warehouse">Warehouse</option>
                        <option value="store">Store</option>
                        <option value="room">Room</option>
                        <option value="shelf">Shelf</option>
                        <option value="bin">Bin</option>
                        <option value="aisle">Aisle</option>
                        <option value="rack">Rack</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="parent_id" class="form-label">Parent Location</label>
                    <select class="form-select" id="parent_id" name="parent_id">
                        <option value="">None (Top Level)</option>
                        <?php foreach ($parent_locations as $parent): ?>
                            <option value="<?= $parent['id'] ?>">
                                <?= htmlspecialchars($parent['location_name']) ?> (<?= htmlspecialchars($parent['location_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">For hierarchical locations (e.g., Shelf within Room)</small>
                </div>
                
                <div class="col-md-6">
                    <label for="barcode" class="form-label">Barcode</label>
                    <input type="text" class="form-control" id="barcode" name="barcode">
                </div>
                
                <div class="col-md-6">
                    <label for="capacity" class="form-label">Capacity</label>
                    <input type="number" step="0.01" class="form-control" id="capacity" name="capacity" placeholder="Optional capacity limit">
                </div>
                
                <div class="col-12">
                    <label for="address" class="form-label">Address/Description</label>
                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('inventory/locations') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Location
                </button>
            </div>
        </form>
    </div>
</div>

