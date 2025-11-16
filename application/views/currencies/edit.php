<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Currency</h1>
        <a href="<?= base_url('currencies') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Currency Code *</label>
                            <input type="text" name="currency_code" class="form-control" maxlength="3" required 
                                   value="<?= htmlspecialchars($currency['currency_code']) ?>" 
                                   style="text-transform: uppercase;">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Currency Name *</label>
                            <input type="text" name="currency_name" class="form-control" required 
                                   value="<?= htmlspecialchars($currency['currency_name']) ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Symbol *</label>
                            <input type="text" name="symbol" class="form-control" required 
                                   value="<?= htmlspecialchars($currency['symbol']) ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Exchange Rate *</label>
                            <input type="number" name="exchange_rate" class="form-control" step="0.0001" 
                                   value="<?= $currency['exchange_rate'] ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <select name="position" class="form-select">
                                <option value="before" <?= ($currency['position'] ?? 'before') === 'before' ? 'selected' : '' ?>>Before Amount</option>
                                <option value="after" <?= ($currency['position'] ?? '') === 'after' ? 'selected' : '' ?>>After Amount</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Decimal Precision</label>
                            <input type="number" name="precision" class="form-control" min="0" max="4" 
                                   value="<?= $currency['precision'] ?? 2 ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="is_base" id="is_base" value="1" 
                                   <?= $currency['is_base'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_base">
                                Set as Base Currency
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $currency['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $currency['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('currencies') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Currency</button>
                </div>
            </form>
        </div>
    </div>
</div>

