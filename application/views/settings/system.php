<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$activeTab = $active_tab ?? 'company';
?>

<div class="page-header mb-4">
    <h1 class="page-title mb-0">System Settings</h1>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Settings Navigation Tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'company' ? 'active' : '' ?>" 
           href="<?= base_url('settings/system?tab=company') ?>">
            <i class="bi bi-building"></i> Company
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'email' ? 'active' : '' ?>" 
           href="<?= base_url('settings/system?tab=email') ?>">
            <i class="bi bi-envelope"></i> Email
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'sms' ? 'active' : '' ?>" 
           href="<?= base_url('settings/system?tab=sms') ?>">
            <i class="bi bi-chat-dots"></i> SMS
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'preferences' ? 'active' : '' ?>" 
           href="<?= base_url('settings/system?tab=preferences') ?>">
            <i class="bi bi-gear"></i> Preferences
        </a>
    </li>
</ul>

<!-- Company Settings -->
<?php if ($activeTab === 'company'): ?>
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-building"></i> Company Information</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= base_url('settings/system/save') ?>" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tab" value="company">
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="company_name" class="form-control" 
                               value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Company Logo</label>
                        <input type="file" name="company_logo" class="form-control" accept="image/*">
                        <?php if (!empty($settings['company_logo'] ?? '')): ?>
                            <small class="text-muted">Current logo: <?= htmlspecialchars($settings['company_logo']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Company Address</label>
                    <textarea name="company_address" class="form-control" rows="3"><?= htmlspecialchars($settings['company_address'] ?? '') ?></textarea>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="company_phone" class="form-control" 
                               value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="company_email" class="form-control" 
                               value="<?= htmlspecialchars($settings['company_email'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Website</label>
                        <input type="url" name="company_website" class="form-control" 
                               value="<?= htmlspecialchars($settings['company_website'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Tax ID / Registration Number</label>
                        <input type="text" name="tax_id" class="form-control" 
                               value="<?= htmlspecialchars($settings['tax_id'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Brand Color (Hex)</label>
                        <input type="color" name="brand_color" class="form-control form-control-color" 
                               value="<?= htmlspecialchars($settings['brand_color'] ?? '#000000') ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-dark">
                    <i class="bi bi-save"></i> Save Company Settings
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Email Settings -->
<?php if ($activeTab === 'email'): ?>
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-envelope"></i> Email Configuration</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= base_url('settings/system/save') ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tab" value="email">
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Configure SMTP settings for sending emails from the system.
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-control" 
                               value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" 
                               placeholder="smtp.gmail.com">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" name="smtp_port" class="form-control" 
                               value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Encryption</label>
                        <select name="smtp_encryption" class="form-select">
                            <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            <option value="none" <?= ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                        </select>
                    </div>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" name="smtp_username" class="form-control" 
                               value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" name="smtp_password" class="form-control" 
                               value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>" 
                               placeholder="Leave blank to keep current">
                    </div>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">From Email</label>
                        <input type="email" name="from_email" class="form-control" 
                               value="<?= htmlspecialchars($settings['from_email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">From Name</label>
                        <input type="text" name="from_name" class="form-control" 
                               value="<?= htmlspecialchars($settings['from_name'] ?? '') ?>">
                    </div>
                </div>
                
                <!-- Test Email Section -->
                <div class="card border-primary mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-envelope-check"></i> Test Email Configuration</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Send a test email to verify your SMTP settings are working correctly.</p>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Test Email Address</label>
                                <input type="email" id="test_email_address" class="form-control" 
                                       placeholder="Enter email address to send test to (optional - will use your email if left blank)"
                                       value="">
                                <small class="text-muted">Leave blank to use your account email address</small>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" onclick="testEmail()" id="testEmailBtn">
                                    <i class="bi bi-send"></i> Send Test Email
                                </button>
                            </div>
                        </div>
                        <div id="testEmailResult" class="mt-3" style="display: none;"></div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark">
                        <i class="bi bi-save"></i> Save Email Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- SMS Settings -->
<?php if ($activeTab === 'sms'): ?>
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-chat-dots"></i> SMS Configuration</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= base_url('settings/system/save') ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tab" value="sms">
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Configure SMS gateway for sending SMS notifications.
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">SMS Gateway Provider</label>
                        <select name="sms_gateway" class="form-select">
                            <option value="">Select Gateway</option>
                            <option value="twilio" <?= ($settings['sms_gateway'] ?? '') === 'twilio' ? 'selected' : '' ?>>Twilio</option>
                            <option value="nexmo" <?= ($settings['sms_gateway'] ?? '') === 'nexmo' ? 'selected' : '' ?>>Vonage (Nexmo)</option>
                            <option value="termii" <?= ($settings['sms_gateway'] ?? '') === 'termii' ? 'selected' : '' ?>>Termii (Nigeria)</option>
                            <option value="custom" <?= ($settings['sms_gateway'] ?? '') === 'custom' ? 'selected' : '' ?>>Custom API</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sender ID</label>
                        <input type="text" name="sms_sender_id" class="form-control" 
                               value="<?= htmlspecialchars($settings['sms_sender_id'] ?? '') ?>" 
                               placeholder="Your company name or number" maxlength="11">
                        <small class="text-muted">Maximum 11 characters</small>
                    </div>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">API Key</label>
                        <input type="text" name="sms_api_key" class="form-control" 
                               value="<?= htmlspecialchars($settings['sms_api_key'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">API Secret</label>
                        <input type="password" name="sms_api_secret" class="form-control" 
                               value="<?= htmlspecialchars($settings['sms_api_secret'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">API Endpoint (for custom gateway)</label>
                    <input type="url" name="sms_api_endpoint" class="form-control" 
                           value="<?= htmlspecialchars($settings['sms_api_endpoint'] ?? '') ?>" 
                           placeholder="https://api.example.com/send">
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark">
                        <i class="bi bi-save"></i> Save SMS Settings
                    </button>
                    <button type="button" class="btn btn-primary" onclick="testSMS()">
                        <i class="bi bi-send"></i> Test SMS
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Preferences -->
<?php if ($activeTab === 'preferences'): ?>
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-gear"></i> System Preferences</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= base_url('settings/system/save') ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tab" value="preferences">
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Timezone</label>
                        <select name="timezone" class="form-select">
                            <option value="Africa/Lagos" <?= ($settings['timezone'] ?? 'Africa/Lagos') === 'Africa/Lagos' ? 'selected' : '' ?>>Africa/Lagos (WAT)</option>
                            <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                            <option value="America/New_York" <?= ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>America/New_York (EST)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date Format</label>
                        <select name="date_format" class="form-select">
                            <option value="Y-m-d" <?= ($settings['date_format'] ?? 'Y-m-d') === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                            <option value="d/m/Y" <?= ($settings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                            <option value="m/d/Y" <?= ($settings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                            <option value="d-m-Y" <?= ($settings['date_format'] ?? '') === 'd-m-Y' ? 'selected' : '' ?>>DD-MM-YYYY</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Time Format</label>
                        <select name="time_format" class="form-select">
                            <option value="H:i:s" <?= ($settings['time_format'] ?? 'H:i:s') === 'H:i:s' ? 'selected' : '' ?>>24 Hour (HH:MM:SS)</option>
                            <option value="h:i:s A" <?= ($settings['time_format'] ?? '') === 'h:i:s A' ? 'selected' : '' ?>>12 Hour (HH:MM:SS AM/PM)</option>
                        </select>
                    </div>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Currency</label>
                        <select name="currency_code" class="form-select">
                            <option value="NGN" <?= ($settings['currency_code'] ?? 'NGN') === 'NGN' ? 'selected' : '' ?>>NGN - Nigerian Naira</option>
                            <option value="USD" <?= ($settings['currency_code'] ?? '') === 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
                            <option value="GBP" <?= ($settings['currency_code'] ?? '') === 'GBP' ? 'selected' : '' ?>>GBP - British Pound</option>
                            <option value="EUR" <?= ($settings['currency_code'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Items Per Page</label>
                        <select name="items_per_page" class="form-select">
                            <option value="10" <?= ($settings['items_per_page'] ?? 25) == 10 ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= ($settings['items_per_page'] ?? 25) == 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= ($settings['items_per_page'] ?? 25) == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= ($settings['items_per_page'] ?? 25) == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Session Timeout (minutes)</label>
                        <input type="number" name="session_timeout" class="form-control" 
                               value="<?= htmlspecialchars($settings['session_timeout'] ?? 3600) / 60 ?>" min="5" max="1440">
                        <small class="text-muted">Current: <?= ($settings['session_timeout'] ?? 3600) / 60 ?> minutes</small>
                    </div>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Default Dashboard</label>
                        <select name="default_dashboard" class="form-select">
                            <option value="super_admin" <?= ($settings['default_dashboard'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin Dashboard</option>
                            <option value="manager" <?= ($settings['default_dashboard'] ?? '') === 'manager' ? 'selected' : '' ?>>Manager Dashboard</option>
                            <option value="staff" <?= ($settings['default_dashboard'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff Dashboard</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password Policy</label>
                        <select name="password_policy" class="form-select">
                            <option value="basic" <?= ($settings['password_policy'] ?? 'basic') === 'basic' ? 'selected' : '' ?>>Basic (6+ characters)</option>
                            <option value="medium" <?= ($settings['password_policy'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium (8+ chars, letters & numbers)</option>
                            <option value="strong" <?= ($settings['password_policy'] ?? '') === 'strong' ? 'selected' : '' ?>>Strong (8+ chars, mixed case, numbers, symbols)</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="maintenance_mode" 
                               id="maintenance_mode" value="1" 
                               <?= !empty($settings['maintenance_mode'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="maintenance_mode">
                            Enable Maintenance Mode
                        </label>
                    </div>
                    <small class="text-muted">When enabled, only admins can access the system</small>
                </div>
                
                <button type="submit" class="btn btn-dark">
                    <i class="bi bi-save"></i> Save Preferences
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
function testEmail() {
    const btn = document.getElementById('testEmailBtn');
    const resultDiv = document.getElementById('testEmailResult');
    const testEmail = document.getElementById('test_email_address').value.trim();
    
    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
    resultDiv.style.display = 'none';
    
    // Prepare form data
    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    if (testEmail) {
        formData.append('test_email', testEmail);
    }
    
    // Send AJAX request
    fetch('<?= base_url("settings/system/test-email") ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        // Check if response is OK
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        // Try to parse as JSON
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                // If not JSON, return the text as error
                throw new Error('Invalid response: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        // Reset button
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send"></i> Send Test Email';
        
        // Show result
        resultDiv.style.display = 'block';
        if (data && data.success) {
            resultDiv.className = 'mt-3 alert alert-success';
            resultDiv.innerHTML = '<i class="bi bi-check-circle"></i> ' + (data.message || 'Email sent successfully');
        } else {
            resultDiv.className = 'mt-3 alert alert-danger';
            resultDiv.innerHTML = '<i class="bi bi-x-circle"></i> ' + (data.message || data.error || 'Unknown error occurred');
        }
        
        // Scroll to result
        resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    })
    .catch(error => {
        // Reset button
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send"></i> Send Test Email';
        
        // Show error
        resultDiv.style.display = 'block';
        resultDiv.className = 'mt-3 alert alert-danger';
        resultDiv.innerHTML = '<i class="bi bi-x-circle"></i> Error: ' + (error.message || 'Failed to send test email. Please check your configuration.');
        
        console.error('Test email error:', error);
    });
}

function testSMS() {
    alert('SMS test functionality will be implemented');
    // TODO: Implement AJAX call to test SMS
}
</script>


