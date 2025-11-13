<?php
$page_title = $page_title ?? 'Edit Entity';
?>

<div class="page-header">
    <h1 class="page-title mb-0">Edit Entity</h1>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_url('entities/edit/' . $Entity['id']) ?>">
            <?php echo csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Entity Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($Entity['name']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tax_id" class="form-label">Tax ID</label>
                    <input type="text" class="form-control" id="tax_id" name="tax_id" value="<?= htmlspecialchars($Entity['tax_id'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($Entity['address'] ?? '') ?>">
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="city" class="form-label">City</label>
                    <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($Entity['city'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="state" class="form-label">State</label>
                    <input type="text" class="form-control" id="state" name="state" value="<?= htmlspecialchars($Entity['state'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="zip_code" class="form-label">Zip Code</label>
                    <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?= htmlspecialchars($Entity['zip_code'] ?? '') ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="country" class="form-label">Country</label>
                    <input type="text" class="form-control" id="country" name="country" value="<?= htmlspecialchars($Entity['country'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($Entity['phone'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($Entity['email'] ?? '') ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="website" class="form-label">Website</label>
                    <input type="url" class="form-control" id="website" name="website" value="<?= htmlspecialchars($Entity['website'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="currency" class="form-label">Currency</label>
                    <select class="form-select" id="currency" name="currency">
                        <?php
                        $currencies = get_all_currencies();
                        $currentCurrency = $Entity['currency'] ?? 'USD';
                        foreach ($currencies as $code => $name): ?>
                            <option value="<?= $code ?>" <?= $code === $currentCurrency ? 'selected' : '' ?>>
                                <?= $code ?> - <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= base_url('entities') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Update Entity
                </button>
            </div>
        </form>
    </div>
</div>

