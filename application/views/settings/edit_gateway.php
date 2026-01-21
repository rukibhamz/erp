<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Configure <?= htmlspecialchars($gateway['gateway_name'] ?? 'Payment Gateway') ?></h1>
        <a href="<?= base_url('settings/payment-gateways') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($gateway): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
<?php echo csrf_field(); ?>
                            <!-- API Credentials -->
                            <h5 class="mb-3">API Credentials</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Public Key *</label>
                                <input type="text" name="public_key" class="form-control" 
                                       value="<?= htmlspecialchars($gateway['public_key'] ?? '') ?>" required>
                                <small class="text-muted">Your gateway public/API key</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Private Key / Secret Key *</label>
                                <input type="password" name="private_key" class="form-control" 
                                       value="<?= htmlspecialchars($gateway['private_key'] ?? '') ?>" required>
                                <small class="text-muted">Your gateway private/secret key (stored securely)</small>
                            </div>

                            <?php if ($gateway['gateway_code'] === 'paystack' || $gateway['gateway_code'] === 'monnify'): ?>
                                <div class="mb-3">
                                    <label class="form-label">Secret Key (Alternative)</label>
                                    <input type="password" name="secret_key" class="form-control" 
                                           value="<?= htmlspecialchars($gateway['secret_key'] ?? '') ?>">
                                    <small class="text-muted">Some gateways require a separate secret key</small>
                                </div>
                            <?php endif; ?>

                            <?php if ($gateway['gateway_code'] === 'monnify'): ?>
                                <div class="mb-3">
                                    <label class="form-label">Contract Code</label>
                                    <input type="text" name="contract_code" class="form-control" 
                                           value="<?= htmlspecialchars($additional_config['contract_code'] ?? '') ?>">
                                    <small class="text-muted">Monnify contract code</small>
                                </div>
                            <?php endif; ?>

                            <?php if ($gateway['gateway_code'] === 'flutterwave'): ?>
                                <div class="mb-3">
                                    <label class="form-label">Encryption Key</label>
                                    <input type="text" name="encryption_key" class="form-control" 
                                           value="<?= htmlspecialchars($additional_config['encryption_key'] ?? '') ?>">
                                    <small class="text-muted">Flutterwave encryption key (optional)</small>
                                </div>
                            <?php endif; ?>

                            <hr>

                            <!-- URLs -->
                            <h5 class="mb-3">Callback & Webhook URLs</h5>

                            <div class="mb-3">
                                <label class="form-label">Callback URL</label>
                                <input type="url" name="callback_url" class="form-control" 
                                       value="<?= htmlspecialchars($gateway['callback_url'] ?: base_url('payment/callback')) ?>"
                                       placeholder="<?= base_url('payment/callback') ?>">
                                <small class="text-muted">URL where customers are redirected after payment</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Webhook URL</label>
                                <div class="input-group">
                                    <input type="url" name="webhook_url" class="form-control" 
                                           value="<?= htmlspecialchars($gateway['webhook_url'] ?: base_url('payment/webhook?gateway=' . $gateway['gateway_code'])) ?>"
                                           placeholder="<?= base_url('payment/webhook?gateway=' . $gateway['gateway_code']) ?>">
                                    <button class="btn btn-outline-dark" type="button" onclick="copyToClipboard('<?= base_url('payment/webhook?gateway=' . $gateway['gateway_code']) ?>')">
                                        <i class="bi bi-clipboard"></i> Copy
                                    </button>
                                </div>
                                <small class="text-muted">Use this URL in your <?= htmlspecialchars($gateway['gateway_name']) ?> dashboard for webhook notifications</small>
                            </div>

                            <hr>

                            <!-- Settings -->
                            <h5 class="mb-3">Settings</h5>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="test_mode" id="test_mode" 
                                           <?= ($gateway['test_mode'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="test_mode">
                                        Test Mode (Sandbox)
                                    </label>
                                </div>
                                <small class="text-muted">Enable test mode for development and testing</small>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                           <?= ($gateway['is_active'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                                <small class="text-muted">Enable this gateway to accept payments</small>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_default" id="is_default" 
                                           <?= ($gateway['is_default'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_default">
                                        Set as Default Gateway
                                    </label>
                                </div>
                                <small class="text-muted">This gateway will be selected by default for payments</small>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?= base_url('settings/payment-gateways') ?>" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Configuration
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6>Gateway Information</h6>
                        <p><strong>Gateway:</strong> <?= htmlspecialchars($gateway['gateway_name']) ?></p>
                        <p><strong>Code:</strong> <?= strtoupper($gateway['gateway_code']) ?></p>
                        
                        <?php
                        $currencies = json_decode($gateway['supported_currencies'] ?? '[]', true);
                        if (!empty($currencies)):
                        ?>
                            <p><strong>Supported Currencies:</strong></p>
                            <ul>
                                <?php foreach ($currencies as $currency): ?>
                                    <li><?= $currency ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <hr>

                        <h6>Setup Instructions</h6>
                        <ol class="small">
                            <li>Get your API credentials from your <?= htmlspecialchars($gateway['gateway_name']) ?> dashboard</li>
                            <li>Enter the Public Key and Private/Secret Key above</li>
                            <li>Configure the webhook URL in your gateway dashboard</li>
                            <li>Enable Test Mode for testing, then disable for production</li>
                            <li>Save and activate the gateway</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Webhook URL copied to clipboard!');
    }, function(err) {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Webhook URL copied to clipboard!');
    });
}
</script>

