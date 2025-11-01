<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Booking Modifications History</h1>
        <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Booking
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($booking): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5>Booking: <?= htmlspecialchars($booking['booking_number']) ?></h5>
                <p class="mb-0 text-muted">Complete history of all changes made to this booking</p>
            </div>
        </div>

        <?php if (!empty($modifications)): ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Change Type</th>
                                    <th>Changed By</th>
                                    <th>Old Value</th>
                                    <th>New Value</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modifications as $mod): ?>
                                    <tr>
                                        <td><?= date('M d, Y h:i A', strtotime($mod['created_at'])) ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= ucfirst(str_replace('_', ' ', $mod['change_type'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($mod['changed_by']): ?>
                                                <?= htmlspecialchars(($mod['first_name'] ?? '') . ' ' . ($mod['last_name'] ?? '')) ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($mod['username'] ?? '') ?></small>
                                            <?php else: ?>
                                                <em>Customer</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $oldVal = $mod['old_value'];
                                            if (json_decode($oldVal, true)) {
                                                $oldVal = json_decode($oldVal, true);
                                                echo '<pre class="mb-0 small">' . print_r($oldVal, true) . '</pre>';
                                            } else {
                                                echo htmlspecialchars(substr($oldVal, 0, 50));
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $newVal = $mod['new_value'];
                                            if (json_decode($newVal, true)) {
                                                $newVal = json_decode($newVal, true);
                                                echo '<pre class="mb-0 small">' . print_r($newVal, true) . '</pre>';
                                            } else {
                                                echo htmlspecialchars(substr($newVal, 0, 50));
                                            }
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($mod['reason'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No modifications have been made to this booking.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

