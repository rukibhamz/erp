<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-4">RECEIPT</h4>
                    <p class="mb-1"><strong><?= htmlspecialchars($terminal['name'] ?? 'POS Terminal') ?></strong></p>
                    <p class="mb-1 small text-muted"><?= htmlspecialchars($terminal['location'] ?? '') ?></p>
                    <p class="mb-3 small"><?= date('F d, Y H:i:s', strtotime($sale['sale_date'])) ?></p>
                    
                    <hr>
                    <p class="mb-2"><strong>Sale #:</strong> <?= htmlspecialchars($sale['sale_number']) ?></p>
                    <?php if ($sale['customer_id']): ?>
                        <p class="mb-2"><strong>Customer:</strong> <?= htmlspecialchars($sale['customer_id']) ?></p>
                    <?php endif; ?>
                    <hr>
                    
                    <table class="table table-sm w-100">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sale['items'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td class="text-end"><?= number_format($item['quantity'], 2) ?></td>
                                    <td class="text-end"><?= format_currency($item['line_total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <hr>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Subtotal:</span>
                        <span><?= format_currency($sale['subtotal']) ?></span>
                    </div>
                    <?php if ($sale['discount_amount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Discount:</span>
                            <span>-<?= format_currency($sale['discount_amount']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Tax:</span>
                        <span><?= format_currency($sale['tax_amount']) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>TOTAL:</strong>
                        <strong><?= format_currency($sale['total_amount']) ?></strong>
                    </div>
                    
                    <div class="mb-2">
                        <strong>Payment:</strong> <?= ucfirst(str_replace('_', ' ', $sale['payment_method'])) ?><br>
                        <strong>Paid:</strong> <?= format_currency($sale['amount_paid']) ?><br>
                        <?php if ($sale['change_amount'] > 0): ?>
                            <strong>Change:</strong> <?= format_currency($sale['change_amount']) ?>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    <p class="small text-muted mb-0">Thank you for your business!</p>
                    
                    <div class="mt-4">
                        <button class="btn btn-dark" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print Receipt
                        </button>
                        <a href="<?= base_url('pos') ?>" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> New Sale
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .container-fluid { padding: 0; }
    .btn { display: none; }
    .card { border: none; box-shadow: none; }
}
</style>



