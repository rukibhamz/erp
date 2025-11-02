<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit Asset: <?= htmlspecialchars($asset['asset_tag']) ?></h1>
        <a href="<?= base_url('inventory/assets/view/' . $asset['id']) ?>" class="btn btn-outline-secondary">
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
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Asset Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('inventory/assets/edit/' . $asset['id']) ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Asset Name <span class="text-danger">*</span></label>
                    <input type="text" name="asset_name" class="form-control" value="<?= htmlspecialchars($asset['asset_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Asset Category <span class="text-danger">*</span></label>
                    <select name="asset_category" class="form-select" required>
                        <option value="equipment" <?= $asset['asset_category'] === 'equipment' ? 'selected' : '' ?>>Equipment</option>
                        <option value="vehicle" <?= $asset['asset_category'] === 'vehicle' ? 'selected' : '' ?>>Vehicle</option>
                        <option value="furniture" <?= $asset['asset_category'] === 'furniture' ? 'selected' : '' ?>>Furniture</option>
                        <option value="it" <?= $asset['asset_category'] === 'it' ? 'selected' : '' ?>>IT Equipment</option>
                        <option value="other" <?= $asset['asset_category'] === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <select name="location_id" class="form-select">
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc['id'] ?>" <?= $asset['location_id'] == $loc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc['location_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Asset Status</label>
                    <select name="asset_status" class="form-select">
                        <option value="active" <?= $asset['asset_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="disposed" <?= $asset['asset_status'] === 'disposed' ? 'selected' : '' ?>>Disposed</option>
                        <option value="retired" <?= $asset['asset_status'] === 'retired' ? 'selected' : '' ?>>Retired</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($asset['description'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= base_url('inventory/assets/view/' . $asset['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-dark">Update Asset</button>
            </div>
        </form>
    </div>
</div>

