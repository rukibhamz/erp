<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Business Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            width: 100%;
        }
        .btn-login:hover {
            opacity: 0.9;
        }
        .password-strength {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .password-strength.weak { color: #dc3545; }
        .password-strength.medium { color: #ffc107; }
        .password-strength.strong { color: #28a745; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h2 class="mb-0"><i class="bi bi-shield-lock"></i> Set New Password</h2>
            <p class="mb-0 mt-2">Choose a strong password</p>
        </div>
        <div class="login-body">
            <?php if (isset($flash)): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                    <?= htmlspecialchars($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?= base_url('reset-password?token=' . htmlspecialchars($token)) ?>" id="resetForm">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="8">
                    <small class="password-strength text-muted" id="strength"></small>
                    <div class="mt-2">
                        <small class="text-muted d-block">Password must contain:</small>
                        <ul class="small text-muted mb-0">
                            <li>At least 8 characters</li>
                            <li>One uppercase letter</li>
                            <li>One lowercase letter</li>
                            <li>One number</li>
                            <li>One special character</li>
                        </ul>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                    <small class="text-danger" id="matchError" style="display: none;">Passwords do not match</small>
                </div>
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-login text-white">
                        <i class="bi bi-check-circle"></i> Reset Password
                    </button>
                </div>
                <div class="text-center">
                    <a href="<?= base_url('login') ?>" class="text-decoration-none">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const strength = document.getElementById('strength');
        const matchError = document.getElementById('matchError');
        
        password.addEventListener('input', function() {
            const pwd = this.value;
            let strengthValue = 0;
            
            if (pwd.length >= 8) strengthValue++;
            if (/[a-z]/.test(pwd)) strengthValue++;
            if (/[A-Z]/.test(pwd)) strengthValue++;
            if (/[0-9]/.test(pwd)) strengthValue++;
            if (/[^a-zA-Z0-9]/.test(pwd)) strengthValue++;
            
            if (pwd.length === 0) {
                strength.textContent = '';
                strength.className = 'password-strength text-muted';
            } else if (strengthValue <= 2) {
                strength.textContent = 'Weak';
                strength.className = 'password-strength weak';
            } else if (strengthValue <= 4) {
                strength.textContent = 'Medium';
                strength.className = 'password-strength medium';
            } else {
                strength.textContent = 'Strong';
                strength.className = 'password-strength strong';
            }
        });
        
        confirmPassword.addEventListener('input', function() {
            if (this.value !== password.value) {
                matchError.style.display = 'block';
            } else {
                matchError.style.display = 'none';
            }
        });
        
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                matchError.style.display = 'block';
                return false;
            }
        });
    </script>
</body>
</html>

