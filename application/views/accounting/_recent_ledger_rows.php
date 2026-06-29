<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * @var array $ledger_rows
 * @var bool $payments_view When true, debit = money in (green), credit = money out (red)
 */
$ledger_rows = $ledger_rows ?? [];
$payments_view = !empty($payments_view);
$refBadge = [
    'booking_payment' => 'primary',
    'invoice_payment' => 'primary',
    'bill_payment' => 'danger',
    'rent_payment' => 'primary',
    'utility_payment' => 'danger',
    'payment' => 'danger',
    'education_tax_payment' => 'danger',
    'booking_revenue' => 'success',
    'invoice' => 'info',
    'rent_invoice' => 'info',
    'bill' => 'warning',
    'journal_entry' => 'secondary',
    'booking_adjustment' => 'secondary',
];
?>
<?php if (!empty($ledger_rows)): ?>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Account</th>
                    <th>Customer</th>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ledger_rows as $txn): ?>
                    <tr>
                        <td><?= format_date($txn['transaction_date']) ?></td>
                        <td><small><?= htmlspecialchars($txn['account_code'] ?? '') ?></small></td>
                        <td><small><?= htmlspecialchars($txn['customer_name'] ?? '—') ?></small></td>
                        <td>
                            <small><?= htmlspecialchars($txn['description'] ?? '') ?></small>
                            <?php
                                $ref = $txn['reference_type'] ?? '';
                                $badge = $refBadge[$ref] ?? 'light';
                                $label = ucwords(str_replace('_', ' ', $ref));
                            ?>
                            <?php if ($label): ?>
                                <span class="badge bg-<?= $badge ?> ms-1" style="font-size:0.65rem"><?= htmlspecialchars($label) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($payments_view): ?>
                                <?php if ((float) ($txn['debit'] ?? 0) > 0): ?>
                                    <span class="text-success"><?= format_currency($txn['debit']) ?></span>
                                <?php else: ?>
                                    <span class="text-danger"><?= format_currency($txn['credit']) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ((float) ($txn['debit'] ?? 0) > 0): ?>
                                    <span class="text-danger"><?= format_currency($txn['debit']) ?></span>
                                <?php else: ?>
                                    <span class="text-success"><?= format_currency($txn['credit']) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-muted mb-0"><?= htmlspecialchars($empty_message ?? 'No records found') ?></p>
<?php endif; ?>
