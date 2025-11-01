<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Payment Gateways</h1>
        <div>
            <a href="<?= base_url('settings') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Settings
            </a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <p class="text-muted mb-4">
                Configure payment gateways to accept online payments. Each gateway requires API credentials 
                (public key, private key) and webhook/callback URLs for payment verification.
            </p>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Gateway</th>
                            <th>Status</th>
                            <th>Test Mode</th>
                            <th>Supported Currencies</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($gateways)): ?>
                            <?php foreach ($gateways as $gateway): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($gateway['logo_url']): ?>
                                                <img src="<?= htmlspecialchars($gateway['logo_url']) ?>" 
                                                     alt="<?= htmlspecialchars($gateway['gateway_name']) ?>" 
                                                     style="height: 30px; margin-right: 10px;">
                                            <?php endif; ?>
                                            <div>
                                                <strong><?= htmlspecialchars($gateway['gateway_name']) ?></strong>
                                                <?php if ($gateway['is_default']): ?>
                                                    <span class="badge bg-primary ms-2">Default</span>
                                                <?php endif; ?>
                                                <br>
                                                <small class="text-muted"><?= strtoupper($gateway['gateway_code']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($gateway['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($gateway['test_mode']): ?>
                                            <span class="badge bg-warning">Test Mode</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Live Mode</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $currencies = json_decode($gateway['supported_currencies'] ?? '[]', true);
                                        if (!empty($currencies)) {
                                            echo '<small>' . implode(', ', $currencies) . '</small>';
                                        } else {
                                            echo '<small class="text-muted">-</small>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('settings/payment-gateways/edit/' . $gateway['id']) ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-gear"></i> Configure
                                        </a>
                                        <a href="<?= base_url('settings/payment-gateways/toggle/' . $gateway['id']) ?>" 
                                           class="btn btn-sm btn-outline-<?= $gateway['is_active'] ? 'secondary' : 'success' ?>"
                                           onclick="return confirm('Are you sure you want to <?= $gateway['is_active'] ? 'deactivate' : 'activate' ?> this gateway?')">
                                            <i class="bi bi-<?= $gateway['is_active'] ? 'x-circle' : 'check-circle' ?>"></i>
                                            <?= $gateway['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No payment gateways found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <h6><i class="bi bi-info-circle"></i> Webhook URL Setup</h6>
        <p class="mb-0">
            Use the following webhook URL in your payment gateway dashboard to receive payment notifications:
            <br>
            <code><?= base_url('payment/webhook') ?></code>
        </p>
    </div>
</div>

