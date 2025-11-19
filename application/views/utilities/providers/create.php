<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Add Provider</h1>
        <a href="<?= base_url('utilities/providers') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/utilities/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_url('utilities/providers/create') ?>">
            <?php echo csrf_field(); ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="provider_name" class="form-label">Provider Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="provider_name" name="provider_name" required>
                </div>
                <div class="col-md-6">
                    <label for="utility_type_id" class="form-label">Utility Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="utility_type_id" name="utility_type_id" required>
                        <option value="">Select Utility Type</option>
                        <?php foreach ($utility_types ?? [] as $type): ?>
                            <option value="<?= $type['id'] ?>">
                                <?= htmlspecialchars($type['utility_type_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="account_number" class="form-label">Account Number</label>
                    <input type="text" class="form-control" id="account_number" name="account_number">
                </div>
                <div class="col-md-6">
                    <label for="contact_person" class="form-label">Contact Person</label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                    <label for="service_areas" class="form-label">Service Areas</label>
                    <textarea class="form-control" id="service_areas" name="service_areas" rows="2" 
                              placeholder="Areas where this provider offers service"></textarea>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="payment_terms" class="form-label">Payment Terms (Days)</label>
                    <input type="number" class="form-control" id="payment_terms" name="payment_terms" 
                           value="30" min="0">
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('utilities/providers') ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Provider
                </button>
            </div>
        </form>
    </div>
</div>

