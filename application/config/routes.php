<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'Dashboard';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Auth routes
$route['login'] = 'Auth/login';
$route['logout'] = 'Auth/logout';
$route['forgot-password'] = 'Auth/forgotPassword';
$route['reset-password'] = 'Auth/resetPassword';

// Dashboard
$route['dashboard'] = 'Dashboard/index';

// Companies
$route['companies'] = 'Companies/index';
$route['companies/create'] = 'Companies/create';
$route['companies/edit/(:num)'] = 'Companies/edit/$1';

// Users
$route['users'] = 'Users/index';
$route['users/create'] = 'Users/create';
$route['users/edit/(:num)'] = 'Users/edit/$1';
$route['users/permissions/(:num)'] = 'Users/permissions/$1';
$route['users/delete/(:num)'] = 'Users/delete/$1';

// Profile
$route['profile'] = 'Profile/index';
$route['profile/terminate-session/(:any)'] = 'Profile/terminateSession/$1';

// Settings
$route['settings'] = 'Settings/index';
$route['settings/modules'] = 'Settings/modules';

// Activity Log
$route['activity'] = 'Activity/index';

// Accounting Routes
$route['accounting'] = 'Accounting/index';
$route['accounting/dashboard'] = 'Accounting/index';

// Chart of Accounts
$route['accounts'] = 'Accounts/index';
$route['accounts/create'] = 'Accounts/create';
$route['accounts/edit/(:num)'] = 'Accounts/edit/$1';
$route['accounts/delete/(:num)'] = 'Accounts/delete/$1';

// Cash Management
$route['cash'] = 'Cash/index';
$route['cash/accounts'] = 'Cash/accounts';
$route['cash/accounts/create'] = 'Cash/createAccount';
$route['cash/receipts'] = 'Cash/receipts';
$route['cash/payments'] = 'Cash/payments';

// Accounts Receivable
$route['receivables'] = 'Receivables/customers';
$route['receivables/customers'] = 'Receivables/customers';
$route['receivables/customers/create'] = 'Receivables/createCustomer';
$route['receivables/customers/edit/(:num)'] = 'Receivables/editCustomer/$1';
$route['receivables/invoices'] = 'Receivables/invoices';
$route['receivables/invoices/create'] = 'Receivables/createInvoice';
$route['receivables/invoices/edit/(:num)'] = 'Receivables/editInvoice/$1';
$route['receivables/invoices/payment/(:num)'] = 'Receivables/recordPayment/$1';
$route['receivables/aging'] = 'Receivables/aging';

// Accounts Payable
$route['payables'] = 'Payables/vendors';
$route['payables/vendors'] = 'Payables/vendors';
$route['payables/vendors/create'] = 'Payables/createVendor';
$route['payables/bills'] = 'Payables/bills';
$route['payables/bills/create'] = 'Payables/createBill';
        $route['payables/bills/edit/(:num)'] = 'Payables/editBill/$1';
        $route['payables/batch-payment'] = 'Payables/batchPayment';
        $route['payables/aging'] = 'Payables/aging';
        $route['payables/vendors/edit/(:num)'] = 'Payables/editVendor/$1';

// General Ledger
$route['ledger'] = 'Ledger/index';
$route['ledger/index'] = 'Ledger/index';
$route['journal'] = 'Ledger/index';
$route['journal/create'] = 'Ledger/create';
$route['ledger/create'] = 'Ledger/create';
$route['ledger/edit/(:num)'] = 'Ledger/edit/$1';
        $route['ledger/approve/(:num)'] = 'Ledger/approve/$1';
        $route['ledger/post/(:num)'] = 'Ledger/post/$1';

        // Financial Reports
        $route['reports'] = 'Reports/index';
        $route['reports/profit-loss'] = 'Reports/profitLoss';
        $route['reports/balance-sheet'] = 'Reports/balanceSheet';
        $route['reports/cash-flow'] = 'Reports/cashFlow';
        $route['reports/trial-balance'] = 'Reports/trialBalance';
        $route['reports/general-ledger'] = 'Reports/generalLedger';

        // Payroll
        $route['payroll'] = 'Payroll/index';
        $route['payroll/employees'] = 'Payroll/employees';
        $route['payroll/employees/create'] = 'Payroll/createEmployee';
        $route['payroll/process'] = 'Payroll/processPayroll';
        $route['payroll/view/(:num)'] = 'Payroll/view/$1';
        $route['payroll/post/(:num)'] = 'Payroll/postPayroll/$1';

        // Financial Year Management
        $route['financial-years'] = 'Financial_years/index';
        $route['financial-years/create'] = 'Financial_years/create';
        $route['financial-years/close/(:num)'] = 'Financial_years/close/$1';
        $route['financial-years/periods/(:num)'] = 'Financial_years/periods/$1';
        $route['financial-years/lock-period'] = 'Financial_years/lockPeriod';
        $route['financial-years/unlock-period'] = 'Financial_years/unlockPeriod';

        // Recurring Transactions
        $route['recurring'] = 'Recurring/index';
        $route['recurring/process'] = 'Recurring/process';

        return $route;

