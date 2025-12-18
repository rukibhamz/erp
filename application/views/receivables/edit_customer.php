<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit Customer</h1>
        <a href="<?= base_url('receivables/customers') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Customer Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('receivables/customers/edit/' . $customer['id']) ?>">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="customer_code" class="form-label">Customer Code</label>
                            <input type="text" class="form-control" id="customer_code" name="customer_code" 
                                   value="<?= htmlspecialchars($customer['customer_code'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   value="<?= htmlspecialchars($customer['company_name'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="contact_name" class="form-label">Contact Name</label>
                            <input type="text" class="form-control" id="contact_name" name="contact_name" 
                                   value="<?= htmlspecialchars($customer['contact_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="tax_id" class="form-label">Tax ID</label>
                            <input type="text" class="form-control" id="tax_id" name="tax_id" 
                                   value="<?= htmlspecialchars($customer['tax_id'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   value="<?= htmlspecialchars($customer['address'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   value="<?= htmlspecialchars($customer['city'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state" 
                                   value="<?= htmlspecialchars($customer['state'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="zip_code" class="form-label">Zip Code</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                   value="<?= htmlspecialchars($customer['zip_code'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="country" name="country" 
                                   value="<?= htmlspecialchars($customer['country'] ?? 'Nigeria') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-select" id="currency" name="currency">
                                <?php
                                $currencies = get_all_currencies();
                                $selectedCurrency = $customer['currency'] ?? 'NGN';
                                foreach ($currencies as $code => $name):
                                ?>
                                    <option value="<?= $code ?>" <?= $code === $selectedCurrency ? 'selected' : '' ?>>
                                        <?= $code ?> - <?= $name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="credit_limit" class="form-label">Credit Limit</label>
                            <input type="number" step="0.01" class="form-control" id="credit_limit" name="credit_limit" 
                                   value="<?= htmlspecialchars($customer['credit_limit'] ?? 0) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="payment_terms" class="form-label">Payment Terms</label>
                            <input type="text" class="form-control" id="payment_terms" name="payment_terms" 
                                   value="<?= htmlspecialchars($customer['payment_terms'] ?? '') ?>" placeholder="e.g., Net 30">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="customer_type_id" class="form-label">Customer Type</label>
                            <select class="form-select" id="customer_type_id" name="customer_type_id">
                                <option value="">Standard / Retail</option>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?= $type['id'] ?>" <?= ($customer['customer_type_id'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                                        <?= esc($type['name']) ?> (<?= $type['discount_percentage'] ?>%)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?= ($customer['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($customer['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('receivables/customers') ?>" class="btn btn-primary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

