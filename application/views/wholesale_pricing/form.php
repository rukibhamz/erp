<div class="container-fluid px-4">
    <div class="mb-4">
        <?= back_button('inventory/items/view/' . $item['id']) ?>
        <h1 class="h3 mt-3"><?= $page_title ?></h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-light">
            <h6 class="m-0 font-weight-bold text-primary">Item Details: <?= esc($item['item_name']) ?> (<?= esc($item['item_code']) ?>)</h6>
        </div>
        <div class="card-body">
            <form action="" method="POST">
                <?= csrf_field() ?>
                
                <div class="form-check form-switch mb-4 p-3 border rounded bg-light">
                    <input class="form-check-input ms-0 me-2" type="checkbox" name="is_wholesale_enabled" id="isWholesale" <?= !empty($item['is_wholesale_enabled']) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="isWholesale">Enable Wholesale Pricing for this item</label>
                    <div class="form-text ms-4">If disabled, the standard retail price will be used regardless of customer type or quantity.</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Current Retail Price</label>
                        <input type="text" class="form-control bg-light" value="<?= number_format($item['retail_price'], 2) ?>" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Wholesale Price</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" name="wholesale_price" class="form-control" value="<?= $item['wholesale_price'] ?? '0.00' ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Minimum Order Quantity (MOQ)</label>
                        <input type="number" step="0.01" name="wholesale_moq" class="form-control" value="<?= $item['wholesale_moq'] ?? '0.00' ?>">
                        <div class="form-text">The minimum quantity required to trigger the wholesale price.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Reason for Price Change</label>
                        <input type="text" name="change_reason" class="form-control" placeholder="Optional notes for price history">
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4">Update Wholesale Rules</button>
                    <a href="<?= base_url('inventory/items/view/' . $item['id']) ?>" class="btn btn-link">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
