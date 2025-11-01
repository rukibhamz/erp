<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Financial Reports</h1>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Financial Statements -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Financial Statements</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="<?= base_url('reports/profit-loss') ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="bi bi-graph-up"></i> Profit & Loss Statement</h6>
                            </div>
                            <p class="mb-1 text-muted">Income statement showing revenue, expenses, and net income</p>
                        </a>
                        <a href="<?= base_url('reports/balance-sheet') ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="bi bi-bar-chart"></i> Balance Sheet</h6>
                            </div>
                            <p class="mb-1 text-muted">Assets, liabilities, and equity as of a specific date</p>
                        </a>
                        <a href="<?= base_url('reports/cash-flow') ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="bi bi-cash-coin"></i> Cash Flow Statement</h6>
                            </div>
                            <p class="mb-1 text-muted">Operating, investing, and financing activities</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ledger Reports -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-journal-text"></i> Ledger Reports</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="<?= base_url('reports/trial-balance') ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="bi bi-check2-square"></i> Trial Balance</h6>
                            </div>
                            <p class="mb-1 text-muted">All accounts with debit and credit balances</p>
                        </a>
                        <a href="<?= base_url('reports/general-ledger') ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="bi bi-list-ul"></i> General Ledger</h6>
                            </div>
                            <p class="mb-1 text-muted">Detailed transaction history for an account</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Revenue</h6>
                    <h4 class="text-success mb-0">
                        <?php
                        try {
                            $accountModel = new Account_model();
                            $revenueAccounts = $accountModel->getByType('Revenue');
                            $total = 0;
                            foreach ($revenueAccounts as $acc) {
                                $total += floatval($acc['balance'] ?? 0);
                            }
                            echo format_currency($total, 'USD');
                        } catch (Exception $e) {
                            echo format_currency(0, 'USD');
                        }
                        ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Expenses</h6>
                    <h4 class="text-danger mb-0">
                        <?php
                        try {
                            $accountModel = new Account_model();
                            $expenseAccounts = $accountModel->getByType('Expenses');
                            $total = 0;
                            foreach ($expenseAccounts as $acc) {
                                $total += floatval($acc['balance'] ?? 0);
                            }
                            echo format_currency($total, 'USD');
                        } catch (Exception $e) {
                            echo format_currency(0, 'USD');
                        }
                        ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Net Income</h6>
                    <h4 class="mb-0">
                        <?php
                        try {
                            $accountModel = new Account_model();
                            $revenueAccounts = $accountModel->getByType('Revenue');
                            $expenseAccounts = $accountModel->getByType('Expenses');
                            $revenue = 0;
                            $expenses = 0;
                            foreach ($revenueAccounts as $acc) {
                                $revenue += floatval($acc['balance'] ?? 0);
                            }
                            foreach ($expenseAccounts as $acc) {
                                $expenses += floatval($acc['balance'] ?? 0);
                            }
                            $netIncome = $revenue - $expenses;
                            $class = $netIncome >= 0 ? 'text-success' : 'text-danger';
                            echo '<span class="' . $class . '">' . format_currency($netIncome, 'USD') . '</span>';
                        } catch (Exception $e) {
                            echo format_currency(0, 'USD');
                        }
                        ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Assets</h6>
                    <h4 class="text-primary mb-0">
                        <?php
                        try {
                            $accountModel = new Account_model();
                            $assetAccounts = $accountModel->getByType('Assets');
                            $total = 0;
                            foreach ($assetAccounts as $acc) {
                                $total += floatval($acc['balance'] ?? 0);
                            }
                            echo format_currency($total, 'USD');
                        } catch (Exception $e) {
                            echo format_currency(0, 'USD');
                        }
                        ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('layouts/footer'); ?>

