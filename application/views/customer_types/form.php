<div class="container-fluid px-4">
    <div class="mb-4">
        <?= back_button('customer_types') ?>
        <h1 class="h3 mt-3"><?= $page_title ?></h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="" method="POST">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Type Name</label>
                        <input type="text" name="name" class="form-control" value="<?= esc($type['name'] ?? '') ?>" required placeholder="e.g. Wholesale">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Type Code</label>
                        <input type="text" name="code" class="form-control" value="<?= esc($type['code'] ?? '') ?>" <?= isset($type) ? 'readonly' : 'required' ?> placeholder="e.g. WHOLESALE">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= esc($type['description'] ?? '') ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Default Discount (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="discount_percentage" class="form-control" value="<?= $type['discount_percentage'] ?? '0.00' ?>">
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text">This discount applies automatically to all customers of this type.</div>
                    </div>
                    <div class="col-md-6 mb-3 pt-4">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= (!isset($type) || !empty($type['is_active'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4">Save Customer Type</button>
                    <a href="<?= base_url('customer_types') ?>" class="btn btn-link">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
