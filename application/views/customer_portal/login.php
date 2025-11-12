<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold mb-2">Customer Login</h2>
                        <p class="text-muted">Access your booking dashboard</p>
                    </div>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                            <?= htmlspecialchars($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= base_url('customer-portal/login') ?>">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="remember_me" class="form-check-input" id="remember_me">
                            <label class="form-check-label" for="remember_me">
                                Remember me
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </form>

                    <div class="text-center">
                        <a href="<?= base_url('customer-portal/register') ?>" class="text-decoration-none">
                            Don't have an account? Register
                        </a>
                        <br>
                        <a href="<?= base_url('booking-wizard') ?>" class="text-decoration-none small">
                            Continue as guest
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

