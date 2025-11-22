<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Add Currency</h1>
        <a href="<?= base_url('currencies') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
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
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="currency_code" class="form-label">
                            Currency Code <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="currency_code" name="currency_code" class="form-control" maxlength="3" required 
                               placeholder="USD, EUR, GBP, etc." style="text-transform: uppercase;">
                        <small class="text-muted">ISO 4217 code (3 letters)</small>
                    </div>
                    <div class="col-md-6">
                        <label for="currency_name" class="form-label">
                            Currency Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="currency_name" name="currency_name" class="form-control" required 
                               placeholder="US Dollar, Euro, etc.">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="symbol" class="form-label">
                            Symbol <span class="text-danger">*</span>
                        </label>
                            <input type="text" name="symbol" class="form-control" required 
                                   placeholder="$, €, £, etc.">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Exchange Rate *</label>
                            <input type="number" name="exchange_rate" class="form-control" step="0.0001" value="1.0000" required>
                            <small class="text-muted">Rate relative to base currency</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <select name="position" class="form-select">
                                <option value="before">Before Amount</option>
                                <option value="after">After Amount</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Decimal Precision</label>
                            <input type="number" name="precision" class="form-control" min="0" max="4" value="2">
                            <small class="text-muted">Number of decimal places</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="is_base" id="is_base" value="1">
                            <label class="form-check-label" for="is_base">
                                Set as Base Currency
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('currencies') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Currency</button>
                </div>
            </form>
        </div>
    </div>
</div>

