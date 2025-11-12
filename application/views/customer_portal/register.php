<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold mb-2">Create Account</h2>
                        <p class="text-muted">Register to manage your bookings</p>
                    </div>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                            <?= htmlspecialchars($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= base_url('customer-portal/register') ?>">
                        <?php echo csrf_field(); ?>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" required
                                       value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" required
                                       value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                            <small class="text-muted">Minimum 8 characters</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control"
                                   value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Address (Optional)</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control"
                                       value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control"
                                       value="<?= htmlspecialchars($_POST['state'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Zip Code</label>
                                <input type="text" name="zip_code" class="form-control"
                                       value="<?= htmlspecialchars($_POST['zip_code'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control"
                                       value="<?= htmlspecialchars($_POST['country'] ?? '') ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-person-plus"></i> Create Account
                        </button>
                    </form>

                    <div class="text-center">
                        <a href="<?= base_url('customer-portal/login') ?>" class="text-decoration-none">
                            Already have an account? Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

