<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold mb-2">Set Your Password</h2>
                        <p class="text-muted">Choose a secure password for your account</p>
                    </div>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                            <?= htmlspecialchars($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= base_url('customer-portal/reset-password?token=' . urlencode($token)) ?>">
                        <?php echo csrf_field(); ?>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" required 
                                   minlength="8" placeholder="At least 8 characters">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required 
                                   minlength="8" placeholder="Re-enter your password">
                        </div>

                        <div class="alert alert-info small mb-3">
                            <i class="bi bi-info-circle"></i> Password must be at least 8 characters and include uppercase, lowercase, number, and special character.
                        </div>

                        <button type="submit" class="btn btn-success w-100 mb-3">
                            <i class="bi bi-shield-check"></i> Set Password
                        </button>
                    </form>

                    <div class="text-center">
                        <a href="<?= base_url('customer-portal/login') ?>" class="text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
