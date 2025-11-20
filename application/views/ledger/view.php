<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Journal Entry: <?= htmlspecialchars($entry['entry_number'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if (hasPermission('ledger', 'update')): ?>
                <?php if (($entry['status'] ?? '') === 'draft'): ?>
                    <a href="<?= base_url('ledger/edit/' . $entry['id']) ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                <?php elseif (($entry['status'] ?? '') === 'approved'): ?>
                    <form method="POST" action="<?= base_url('ledger/post/' . $entry['id']) ?>" style="display: inline;">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-success" onclick="return confirm('Post this journal entry? This will create transactions.');">
                            <i class="bi bi-check-circle"></i> Post Entry
                        </button>
                    </form>
                <?php endif; ?>
                <?php if (($entry['status'] ?? '') === 'draft'): ?>
                    <form method="POST" action="<?= base_url('ledger/approve/' . $entry['id']) ?>" style="display: inline;">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-info" onclick="return confirm('Approve this journal entry?');">
                            <i class="bi bi-check"></i> Approve
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <a href="<?= base_url('ledger') ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Journal Entry Information</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-3">
                    <dt class="col-sm-4">Entry Number</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($entry['entry_number'] ?? 'N/A') ?></dd>
                    
                    <dt class="col-sm-4">Entry Date</dt>
                    <dd class="col-sm-8"><?= format_date($entry['entry_date'] ?? '') ?></dd>
                    
                    <dt class="col-sm-4">Reference</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($entry['reference'] ?? '-') ?></dd>
                    
                    <dt class="col-sm-4">Description</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($entry['description'] ?? '-') ?></dd>
                    
                    <dt class="col-sm-4">Total Amount</dt>
                    <dd class="col-sm-8"><?= format_currency($entry['amount'] ?? 0) ?></dd>
                    
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <?php
                        $statusBadges = [
                            'draft' => 'secondary',
                            'approved' => 'info',
                            'posted' => 'success',
                            'rejected' => 'danger'
                        ];
                        $badgeColor = $statusBadges[$entry['status'] ?? 'draft'] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $badgeColor ?>">
                            <?= ucfirst($entry['status'] ?? 'draft') ?>
                        </span>
                    </dd>
                </dl>
                
                <hr>
                
                <h5>Journal Entry Lines</h5>
                <?php if (!empty($lines)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th>Description</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalDebit = 0;
                                $totalCredit = 0;
                                foreach ($lines as $line): 
                                    $totalDebit += floatval($line['debit'] ?? 0);
                                    $totalCredit += floatval($line['credit'] ?? 0);
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars(($line['account_code'] ?? '') . ' - ' . ($line['account_name'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars($line['description'] ?? '-') ?></td>
                                        <td class="text-end">
                                            <?php if ($line['debit'] > 0): ?>
                                                <?= format_currency($line['debit']) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($line['credit'] > 0): ?>
                                                <?= format_currency($line['credit']) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="2" class="text-end">Totals:</td>
                                    <td class="text-end"><?= format_currency($totalDebit) ?></td>
                                    <td class="text-end"><?= format_currency($totalCredit) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if (abs($totalDebit - $totalCredit) < 0.01): ?>
                        <div class="alert alert-success mt-3">
                            <i class="bi bi-check-circle"></i> Journal entry is balanced.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger mt-3">
                            <i class="bi bi-exclamation-triangle"></i> Journal entry is not balanced! 
                            Difference: <?= format_currency(abs($totalDebit - $totalCredit)) ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">No lines found for this journal entry.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

