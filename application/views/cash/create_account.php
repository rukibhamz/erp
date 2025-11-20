<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Cash Account</h1>
        <a href="<?= base_url('cash/accounts') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Account Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('cash/accounts/create') ?>" id="createAccountForm">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account_name" name="account_name" 
                                   value="<?= htmlspecialchars($_POST['account_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="account_type" name="account_type" required>
                                <option value="bank_account" <?= ($_POST['account_type'] ?? '') === 'bank_account' ? 'selected' : '' ?>>Bank Account</option>
                                <option value="petty_cash" <?= ($_POST['account_type'] ?? '') === 'petty_cash' ? 'selected' : '' ?>>Petty Cash</option>
                                <option value="cash_register" <?= ($_POST['account_type'] ?? '') === 'cash_register' ? 'selected' : '' ?>>Cash Register</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bank_name" class="form-label">Bank Name</label>
                            <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                   value="<?= htmlspecialchars($_POST['bank_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="account_number" class="form-label">Account Number <small class="text-muted">(10 digits, numbers only)</small></label>
                            <input type="text" class="form-control" id="account_number" name="account_number" 
                                   value="<?= htmlspecialchars($_POST['account_number'] ?? '') ?>"
                                   pattern="[0-9]{10}" 
                                   maxlength="10"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                                   title="Account number must be exactly 10 digits (numbers only)">
                            <small class="form-text text-muted">Enter exactly 10 digits (numbers only, no letters or special characters)</small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="routing_number" class="form-label">Routing Number</label>
                            <input type="text" class="form-control" id="routing_number" name="routing_number" 
                                   value="<?= htmlspecialchars($_POST['routing_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="swift_code" class="form-label">SWIFT Code</label>
                            <input type="text" class="form-control" id="swift_code" name="swift_code" 
                                   value="<?= htmlspecialchars($_POST['swift_code'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="opening_balance" class="form-label">Opening Balance <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="opening_balance" name="opening_balance" 
                                   value="<?= htmlspecialchars($_POST['opening_balance'] ?? '0.00') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                            <select class="form-select" id="currency" name="currency" required>
                                <?php
                                $currencies = get_all_currencies();
                                foreach ($currencies as $code => $name):
                                ?>
                                    <option value="<?= $code ?>" <?= ($_POST['currency'] ?? 'USD') === $code ? 'selected' : '' ?>>
                                        <?= $code ?> - <?= $name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('cash/accounts') ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Account Information</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Note:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Account number must be exactly 10 digits (numbers only)</li>
                        <li>Opening balance will be set as the initial current balance</li>
                        <li>You can record receipts and payments to adjust the balance</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

