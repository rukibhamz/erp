<?php
$page_title = $page_title ?? 'Edit Company';
?>

<div class="page-header">
    <h1 class="page-title mb-0">Edit Company</h1>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_url('companies/edit/' . $company['id']) ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Company Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($company['name']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tax_id" class="form-label">Tax ID</label>
                    <input type="text" class="form-control" id="tax_id" name="tax_id" value="<?= htmlspecialchars($company['tax_id'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($company['address'] ?? '') ?>">
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="city" class="form-label">City</label>
                    <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($company['city'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="state" class="form-label">State</label>
                    <input type="text" class="form-control" id="state" name="state" value="<?= htmlspecialchars($company['state'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="zip_code" class="form-label">Zip Code</label>
                    <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?= htmlspecialchars($company['zip_code'] ?? '') ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="country" class="form-label">Country</label>
                    <input type="text" class="form-control" id="country" name="country" value="<?= htmlspecialchars($company['country'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($company['phone'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($company['email'] ?? '') ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="website" class="form-label">Website</label>
                    <input type="url" class="form-control" id="website" name="website" value="<?= htmlspecialchars($company['website'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="currency" class="form-label">Currency</label>
                    <select class="form-select" id="currency" name="currency">
                        <option value="USD" <?= ($company['currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
                        <option value="EUR" <?= ($company['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                        <option value="GBP" <?= ($company['currency'] ?? '') === 'GBP' ? 'selected' : '' ?>>GBP - British Pound</option>
                        <option value="JPY" <?= ($company['currency'] ?? '') === 'JPY' ? 'selected' : '' ?>>JPY - Japanese Yen</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= base_url('companies') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Update Company
                </button>
            </div>
        </form>
    </div>
</div>

