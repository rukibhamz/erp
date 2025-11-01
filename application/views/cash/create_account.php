<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Create Cash Account</h1>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= base_url('cash/accounts/create') ?>">
                    <div class="mb-3">
                        <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="account_name" name="account_name" required placeholder="e.g., Main Bank Account">
                    </div>
                    
                    <div class="mb-3">
                        <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="account_type" name="account_type" required>
                            <option value="bank_account">Bank Account</option>
                            <option value="petty_cash">Petty Cash</option>
                            <option value="cash_register">Cash Register</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bank_name" class="form-label">Bank Name</label>
                        <input type="text" class="form-control" id="bank_name" name="bank_name" placeholder="Enter bank name">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="account_number" class="form-label">Account Number</label>
                            <input type="text" class="form-control" id="account_number" name="account_number" placeholder="Account number">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="routing_number" class="form-label">Routing Number</label>
                            <input type="text" class="form-control" id="routing_number" name="routing_number" placeholder="Routing number">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="swift_code" class="form-label">SWIFT Code</label>
                        <input type="text" class="form-control" id="swift_code" name="swift_code" placeholder="SWIFT/BIC code">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="opening_balance" class="form-label">Opening Balance</label>
                            <input type="number" step="0.01" class="form-control" id="opening_balance" name="opening_balance" value="0.00">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-select" id="currency" name="currency">
                                <?php
                                $currencies = get_all_currencies();
                                foreach ($currencies as $code => $name): ?>
                                    <option value="<?= $code ?>" <?= $code === 'USD' ? 'selected' : '' ?>>
                                        <?= $code ?> - <?= $name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Account
                        </button>
                        <a href="<?= base_url('cash/accounts') ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Account Types</h6>
            </div>
            <div class="card-body">
                <p class="small mb-2"><strong>Bank Account:</strong> Regular bank checking or savings account</p>
                <p class="small mb-2"><strong>Petty Cash:</strong> Small cash fund for minor expenses</p>
                <p class="small mb-0"><strong>Cash Register:</strong> Point of sale cash register</p>
            </div>
        </div>
    </div>
</div>

