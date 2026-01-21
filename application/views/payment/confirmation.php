<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-body text-center p-5">
                    <?php if ($status === 'success'): ?>
                        <div class="mb-4">
                            <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px;">
                                <i class="bi bi-check-lg text-white" style="font-size: 3rem;"></i>
                            </div>
                        </div>
                        <h2 class="text-success mb-3">Payment Successful!</h2>
                        <p class="text-muted mb-4">Thank you for your payment. Your transaction has been completed successfully.</p>
                    <?php elseif ($status === 'pending'): ?>
                        <div class="mb-4">
                            <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px;">
                                <i class="bi bi-hourglass-split text-white" style="font-size: 3rem;"></i>
                            </div>
                        </div>
                        <h2 class="text-warning mb-3">Payment Processing</h2>
                        <p class="text-muted mb-4">Your payment is being processed. You will receive a confirmation once complete.</p>
                    <?php else: ?>
                        <div class="mb-4">
                            <div class="bg-danger rounded-circle d-inline-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px;">
                                <i class="bi bi-x-lg text-white" style="font-size: 3rem;"></i>
                            </div>
                        </div>
                        <h2 class="text-danger mb-3">Payment Failed</h2>
                        <p class="text-muted mb-4">Unfortunately, your payment could not be processed. Please try again or contact support.</p>
                    <?php endif; ?>

                    <?php if (!empty($transaction)): ?>
                        <div class="border rounded p-4 mb-4 bg-light text-start">
                            <h6 class="text-muted mb-3">Transaction Details</h6>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Reference:</div>
                                <div class="col-7"><strong><?= htmlspecialchars($transaction['transaction_ref'] ?? '-') ?></strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Amount:</div>
                                <div class="col-7"><strong><?= htmlspecialchars($transaction['currency'] ?? 'NGN') ?> <?= number_format($transaction['amount'] ?? 0, 2) ?></strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Gateway:</div>
                                <div class="col-7"><?= htmlspecialchars(ucfirst($transaction['gateway_code'] ?? '-')) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Date:</div>
                                <div class="col-7"><?= date('M d, Y h:i A', strtotime($transaction['created_at'] ?? 'now')) ?></div>
                            </div>
                            <div class="row">
                                <div class="col-5 text-muted">Status:</div>
                                <div class="col-7">
                                    <?php
                                    $statusClass = 'secondary';
                                    if ($status === 'success') $statusClass = 'success';
                                    elseif ($status === 'pending') $statusClass = 'warning';
                                    elseif ($status === 'failed') $statusClass = 'danger';
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($status) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <?php if (!empty($booking_id)): ?>
                            <?php 
                            // For guests, show link to booking wizard confirmation instead of dashboard bookings
                            $viewUrl = isset($_SESSION['user_id']) 
                                ? base_url('bookings/view/' . $booking_id)
                                : base_url('booking-wizard/confirmation/' . $booking_id);
                            ?>
                            <a href="<?= $viewUrl ?>" class="btn btn-primary">
                                <i class="bi bi-eye"></i> View Booking
                            </a>
                        <?php endif; ?>
                        <a href="<?= base_url() ?>" class="btn btn-outline-dark">
                            <i class="bi bi-house"></i> Back to Home
                        </a>
                    </div>

                    <hr class="my-4">

                    <p class="small text-muted mb-0">
                        <i class="bi bi-shield-check"></i> This transaction is secured and encrypted.<br>
                        If you have any questions, please contact our support team.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
