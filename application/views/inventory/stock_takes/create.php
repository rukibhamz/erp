<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include BASEPATH . 'views/layouts/header.php';
include BASEPATH . 'views/inventory/_nav.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
            <h3><?= htmlspecialchars($page_title) ?></h3>
            <a href="<?= base_url('inventory/stock-takes') ?>" class="btn btn-outline-dark">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <?php if (isset($flash) && $flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Create Stock Take</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('inventory/stock-takes/create') ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <select name="location_id" class="form-select" required>
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['location_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                            <input type="date" name="scheduled_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="full">Full Count</option>
                                <option value="cycle">Cycle Count</option>
                                <option value="spot">Spot Check</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes about this stock take"></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> After creating the stock take, a count sheet will be automatically generated with all items that have stock at the selected location.
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('inventory/stock-takes') ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-dark">Create Stock Take</button>
                    </div>
                </form>
            </div>
        </div>

<?php include BASEPATH . 'views/layouts/footer.php'; ?>

