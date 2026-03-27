<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?= $booking['booking_number'] ?></title>
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fff; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); font-size: 16px; line-height: 24px; color: #555; }
        .invoice-box table { width: 100%; line-height: inherit; text-align: left; }
        .invoice-box table td { padding: 5px; vertical-align: top; }
        .invoice-box table tr td:nth-child(2) { text-align: right; }
        .invoice-box table tr.top table td { padding-bottom: 20px; }
        .invoice-box table tr.top table td.title { font-size: 45px; line-height: 45px; color: #333; }
        .invoice-box table tr.information table td { padding-bottom: 40px; }
        .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
        .invoice-box table tr.details td { padding-bottom: 20px; }
        .invoice-box table tr.item td { border-bottom: 1px solid #eee; }
        .invoice-box table tr.item.last td { border-bottom: none; }
        .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; }
        @media print {
            .no-print { display: none; }
            .invoice-box { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>
    <div class="no-print text-center my-4">
        <button onclick="window.print()" class="btn btn-primary">Print Now</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">
                                <h3>Acropolis Park</h3>
                            </td>
                            <td>
                                Invoice #: <?= $booking['booking_number'] ?><br>
                                Created: <?= date('M d, Y', strtotime($booking['created_at'])) ?><br>
                                Event Date: <?= date('M d, Y', strtotime($booking['booking_date'])) ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                <strong>From:</strong><br>
                                Acropolis Park ERP<br>
                                booking.acropolispark.com
                            </td>
                            <td>
                                <strong>To:</strong><br>
                                <?= htmlspecialchars($booking['customer_name']) ?><br>
                                <?= htmlspecialchars($booking['customer_email']) ?><br>
                                <?= htmlspecialchars($booking['customer_phone']) ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="heading">
                <td>Description</td>
                <td>Price</td>
            </tr>
            <tr class="item">
                <td>
                    Booking of <?= htmlspecialchars($booking['facility_name']) ?><br>
                    <small><?= date('h:i A', strtotime($booking['start_time'])) ?> - <?= date('h:i A', strtotime($booking['end_time'])) ?> (<?= number_format($booking['duration_hours'], 1) ?> hrs)</small>
                </td>
                <td><?= format_currency($booking['base_amount']) ?></td>
            </tr>
            <?php if (floatval($booking['discount_amount']) > 0): ?>
            <tr class="item">
                <td>Discount</td>
                <td>-<?= format_currency($booking['discount_amount']) ?></td>
            </tr>
            <?php endif; ?>
            <tr class="item">
                <td>VAT (7.5%)</td>
                <td><?= format_currency($booking['tax_amount'] ?: ($booking['base_amount'] - $booking['discount_amount']) * 0.075) ?></td>
            </tr>
            <tr class="total">
                <td></td>
                <td>Total: <?= format_currency($booking['total_amount']) ?></td>
            </tr>
            <tr>
                <td colspan="2">
                    <hr>
                    <strong>Payment History:</strong><br>
                    <table class="table table-sm">
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?> - <?= ucfirst($payment['payment_method']) ?></td>
                            <td><?= format_currency($payment['amount']) ?> (<?= ucfirst($payment['status']) ?>)</td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="fw-bold">
                            <td>Paid to Date:</td>
                            <td><?= format_currency($booking['paid_amount']) ?></td>
                        </tr>
                        <tr class="fw-bold text-danger">
                            <td>Balance Due:</td>
                            <td><?= format_currency($booking['balance_amount']) ?></td>
                        </tr>
                        <?php if ($booking['payment_plan'] === 'part' && $booking['payment_deadline']): ?>
                        <tr>
                            <td colspan="2" class="text-danger">
                                <strong>Note:</strong> Balance of <?= format_currency($booking['balance_amount']) ?> must be paid by <?= date('M d, Y', strtotime($booking['payment_deadline'])) ?>.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
