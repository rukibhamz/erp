<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Edit Customer</h1>
</div>

<!-- Accounting Navigation -->
<div class="accounting-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('accounting') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link" href="<?= base_url('accounts') ?>">
            <i class="bi bi-diagram-3"></i> Chart of Accounts
        </a>
        <a class="nav-link" href="<?= base_url('cash') ?>">
            <i class="bi bi-wallet2"></i> Cash Management
        </a>
        <a class="nav-link active" href="<?= base_url('receivables') ?>">
            <i class="bi bi-receipt"></i> Receivables
        </a>
        <a class="nav-link" href="<?= base_url('payables') ?>">
            <i class="bi bi-file-earmark-medical"></i> Payables
        </a>
        <a class="nav-link" href="<?= base_url('ledger') ?>">
            <i class="bi bi-journal-text"></i> General Ledger
        </a>
    </nav>
</div>

<style>
.accounting-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.accounting-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.accounting-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.accounting-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.accounting-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= base_url('receivables/customers/edit/' . $customer['id']) ?>">
                    <div class="mb-3">
                        <label for="customer_code" class="form-label">Customer Code</label>
                        <input type="text" class="form-control" id="customer_code" name="customer_code" value="<?= htmlspecialchars($customer['customer_code']) ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="company_name" name="company_name" value="<?= htmlspecialchars($customer['company_name']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_name" class="form-label">Contact Name</label>
                        <input type="text" class="form-control" id="contact_name" name="contact_name" value="<?= htmlspecialchars($customer['contact_name'] ?? '') ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($customer['city'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state" value="<?= htmlspecialchars($customer['state'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="zip_code" class="form-label">Zip Code</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?= htmlspecialchars($customer['zip_code'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="country" name="country" value="<?= htmlspecialchars($customer['country'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="tax_id" class="form-label">Tax ID</label>
                            <input type="text" class="form-control" id="tax_id" name="tax_id" value="<?= htmlspecialchars($customer['tax_id'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="credit_limit" class="form-label">Credit Limit</label>
                            <input type="number" step="0.01" class="form-control" id="credit_limit" name="credit_limit" value="<?= htmlspecialchars($customer['credit_limit'] ?? '0.00') ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-select" id="currency" name="currency">
                                <?php
                                $currencies = get_all_currencies();
                                foreach ($currencies as $code => $name): ?>
                                    <option value="<?= $code ?>" <?= $code === ($customer['currency'] ?? 'USD') ? 'selected' : '' ?>>
                                        <?= $code ?> - <?= $name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_terms" class="form-label">Payment Terms</label>
                        <input type="text" class="form-control" id="payment_terms" name="payment_terms" value="<?= htmlspecialchars($customer['payment_terms'] ?? '') ?>" placeholder="e.g., Net 30, Net 60">
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= ($customer['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($customer['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Customer
                        </button>
                        <a href="<?= base_url('receivables/customers') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

