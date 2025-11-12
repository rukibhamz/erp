<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">My Profile</h1>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($user): ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= base_url('customer-portal/profile') ?>">
                            <?php echo csrf_field(); ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" 
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" class="form-control" 
                                       value="<?= htmlspecialchars($user['company_name'] ?? '') ?>">
                            </div>

                            <hr>

                            <h6 class="mb-3">Address</h6>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city" class="form-control" 
                                           value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">State</label>
                                    <input type="text" name="state" class="form-control" 
                                           value="<?= htmlspecialchars($user['state'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Zip Code</label>
                                    <input type="text" name="zip_code" class="form-control" 
                                           value="<?= htmlspecialchars($user['zip_code'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Country</label>
                                    <input type="text" name="country" class="form-control" 
                                           value="<?= htmlspecialchars($user['country'] ?? '') ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Account Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Email:</strong><br><?= htmlspecialchars($user['email']) ?></p>
                        <p class="mb-2"><strong>Member Since:</strong><br><?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                        <?php if ($user['last_login']): ?>
                            <p class="mb-2"><strong>Last Login:</strong><br><?= date('M d, Y g:i A', strtotime($user['last_login'])) ?></p>
                        <?php endif; ?>
                        <hr>
                        <a href="<?= base_url('customer-portal/logout') ?>" class="btn btn-outline-danger w-100">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

