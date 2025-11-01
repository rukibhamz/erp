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

        // Booking System - Resources
        $route['facilities'] = 'Facilities/index';
        $route['facilities/create'] = 'Facilities/create';
        $route['facilities/edit/(:num)'] = 'Facilities/edit/$1';
        $route['resource-management/availability/(:num)'] = 'Resource_management/availability/$1';
        $route['resource-management/blockouts/(:num)'] = 'Resource_management/blockouts/$1';
        $route['resource-management/add-blockout'] = 'Resource_management/addBlockout';
        $route['resource-management/delete-blockout/(:num)'] = 'Resource_management/deleteBlockout/$1';
        $route['resource-management/pricing/(:num)'] = 'Resource_management/pricing/$1';
        $route['resource-management/add-pricing'] = 'Resource_management/addPricing';
        $route['resource-management/delete-pricing/(:num)'] = 'Resource_management/deletePricing/$1';
        $route['resource-management/addons'] = 'Resource_management/addons';
        $route['resource-management/addons/(:num)'] = 'Resource_management/addons/$1';
        
        // Booking System - Bookings
        $route['bookings'] = 'Bookings/index';
        $route['bookings/create'] = 'Bookings/create';
        $route['bookings/calendar'] = 'Bookings/calendar';
        $route['bookings/view/(:num)'] = 'Bookings/view/$1';
        $route['bookings/payment'] = 'Bookings/recordPayment';
        $route['bookings/status/(:num)'] = 'Bookings/updateStatus/$1';
        
        // Public Booking Portal (Legacy)
        $route['booking-portal'] = 'Booking_portal/index';
        $route['booking-portal/facility/(:num)'] = 'Booking_portal/facility/$1';
        $route['booking-portal/check-availability'] = 'Booking_portal/checkAvailability';
        $route['booking-portal/calculate-price'] = 'Booking_portal/calculatePrice';
        $route['booking-portal/submit'] = 'Booking_portal/submitBooking';
        
        // Enhanced Booking Wizard (Multi-step)
        $route['booking-wizard'] = 'Booking_wizard/step1';
        $route['booking-wizard/step1'] = 'Booking_wizard/step1';
        $route['booking-wizard/step2/(:num)'] = 'Booking_wizard/step2/$1';
        $route['booking-wizard/step3/(:num)'] = 'Booking_wizard/step3/$1';
        $route['booking-wizard/step4'] = 'Booking_wizard/step4';
        $route['booking-wizard/step5'] = 'Booking_wizard/step5';
        $route['booking-wizard/get-time-slots'] = 'Booking_wizard/getTimeSlots';
        $route['booking-wizard/save-step'] = 'Booking_wizard/saveStep';
        $route['booking-wizard/validate-promo'] = 'Booking_wizard/validatePromoCode';
        $route['booking-wizard/finalize'] = 'Booking_wizard/finalize';
        $route['booking-wizard/confirmation/(:num)'] = 'Booking_wizard/confirmation/$1';
        
        // Booking Reports
        $route['booking-reports'] = 'Booking_reports/index';
        $route['booking-reports/revenue'] = 'Booking_reports/revenue';
        $route['booking-reports/utilization'] = 'Booking_reports/utilization';
        $route['booking-reports/customer-history'] = 'Booking_reports/customerHistory';
        $route['booking-reports/pending-payments'] = 'Booking_reports/pendingPayments';
        
        // Payment Gateway Settings
        $route['settings/payment-gateways'] = 'Settings/paymentGateways';
        $route['settings/payment-gateways/edit/(:num)'] = 'Settings/editGateway/$1';
        $route['settings/payment-gateways/toggle/(:num)'] = 'Settings/toggleGateway/$1';
        
        // Payment Processing
        $route['payment/initialize'] = 'Payment/initialize';
        $route['payment/callback'] = 'Payment/callback';
        $route['payment/webhook'] = 'Payment/webhook';
        $route['payment/verify'] = 'Payment/verify';

        return $route;

