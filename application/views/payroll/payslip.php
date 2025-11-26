<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Payslip</h1>
        <div>
            <a href="<?= base_url('payroll/view/' . ($payroll_run['id'] ?? '')) ?>" class="btn btn-primary me-2">
                <i class="bi bi-arrow-left"></i> Back to Payroll Run
            </a>
            <button onclick="window.print()" class="btn btn-success me-2">
                <i class="bi bi-printer"></i> Print
            </button>
            <button onclick="downloadPDF()" class="btn btn-danger">
                <i class="bi bi-file-pdf"></i> Download PDF
            </button>
        </div>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <!-- Company Header -->
        <div class="text-center mb-4">
            <h2>Payslip</h2>
            <p class="text-muted">Period: <?= htmlspecialchars($payslip['period'] ?? '') ?></p>
        </div>

        <hr>

        <!-- Employee Information -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Employee Information</h5>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td><?= htmlspecialchars(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Employee Code:</strong></td>
                        <td><?= htmlspecialchars($employee['employee_code'] ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Department:</strong></td>
                        <td><?= htmlspecialchars($employee['department'] ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Position:</strong></td>
                        <td><?= htmlspecialchars($employee['position'] ?? 'N/A') ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Payment Information</h5>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Pay Period:</strong></td>
                        <td><?= htmlspecialchars($payslip['period'] ?? '') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Payment Date:</strong></td>
                        <td><?= date('M d, Y', strtotime($payroll_run['processed_date'] ?? 'now')) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="badge bg-<?= $payslip['status'] === 'posted' ? 'success' : 'warning' ?>">
                                <?= ucfirst($payslip['status']) ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <hr>

        <!-- Earnings and Deductions -->
        <div class="row">
            <div class="col-md-6">
                <h5>Earnings</h5>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Basic Salary</td>
                            <td class="text-end"><?= format_currency($payslip['basic_salary'] ?? 0) ?></td>
                        </tr>
                        <?php if (!empty($earnings)): ?>
                            <?php foreach ($earnings as $earning): ?>
                                <tr>
                                    <td><?= htmlspecialchars($earning['name'] ?? $earning['type'] ?? 'Allowance') ?></td>
                                    <td class="text-end"><?= format_currency($earning['amount'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <th>Gross Pay</th>
                            <th class="text-end"><?= format_currency($payslip['gross_pay'] ?? 0) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="col-md-6">
                <h5>Deductions</h5>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($deductions)): ?>
                            <?php foreach ($deductions as $deduction): ?>
                                <tr>
                                    <td><?= htmlspecialchars($deduction['name'] ?? $deduction['type'] ?? 'Deduction') ?></td>
                                    <td class="text-end"><?= format_currency($deduction['amount'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">No deductions</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <th>Total Deductions</th>
                            <th class="text-end"><?= format_currency($payslip['total_deductions'] ?? 0) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <hr>

        <!-- Net Pay -->
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Net Pay</h4>
                        <h3 class="mb-0"><?= format_currency($payslip['net_pay'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 text-muted">
            <small>This is a computer-generated payslip and does not require a signature.</small>
        </div>
    </div>
</div>

<style>
@media print {
    .page-header, .btn, .alert {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<script>
function downloadPDF() {
    // Set document title for PDF filename
    const employeeName = '<?= htmlspecialchars(($employee['first_name'] ?? '') . '_' . ($employee['last_name'] ?? '')) ?>';
    const period = '<?= htmlspecialchars($payslip['period'] ?? '') ?>';
    const originalTitle = document.title;
    document.title = `Payslip_${employeeName}_${period}`;
    
    // Trigger print dialog (user can save as PDF)
    window.print();
    
    // Restore original title
    setTimeout(() => {
        document.title = originalTitle;
    }, 100);
}
</script>
