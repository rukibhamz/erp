<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Pay for Booking</h1>
        <a href="<?= base_url('customer-portal/booking/' . (int) $booking['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Booking
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?= htmlspecialchars($booking['booking_number'] ?? '') ?></h5>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Resource:</strong> <?= htmlspecialchars($booking['facility_name'] ?? '') ?></p>
                    <p class="mb-3 text-muted">
                        <?= date('F j, Y', strtotime($booking['booking_date'] ?? 'now')) ?> —
                        <?= date('g:i A', strtotime($booking['start_time'] ?? '00:00')) ?> to
                        <?= date('g:i A', strtotime($booking['end_time'] ?? '00:00')) ?>
                    </p>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total</span>
                        <strong><?= format_currency($booking['total_amount'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Paid</span>
                        <span class="text-success"><?= format_currency($booking['paid_amount'] ?? 0) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Amount due now</strong>
                        <strong class="text-danger fs-5"><?= format_currency($amount_due) ?></strong>
                    </div>
                </div>
            </div>

            <?php if (empty($gateways)): ?>
                <div class="alert alert-warning mb-0">
                    Online payment is not available right now. Please contact support.
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="<?= base_url('customer-portal/pay-booking/' . (int) $booking['id']) ?>">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label">Payment method</label>
                                <?php
                                $hasDefaultGatewayOption = false;
                                foreach ($gateways as $_g) {
                                    if (!empty($_g['is_default'])) {
                                        $hasDefaultGatewayOption = true;
                                        break;
                                    }
                                }
                                ?>
                                <select name="gateway_code" class="form-select" required>
                                    <?php foreach ($gateways as $index => $gateway): ?>
                                        <option value="<?= htmlspecialchars($gateway['gateway_code']) ?>"
                                            <?= !empty($gateway['is_default']) || ($index === 0 && !$hasDefaultGatewayOption) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($gateway['gateway_name']) ?><?= !empty($gateway['is_default']) ? ' (Default)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">If a provider is unavailable, the default gateway will be used automatically.</small>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-lg">
                                <i class="bi bi-credit-card"></i> Continue to secure checkout
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
