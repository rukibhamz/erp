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

// Entities (formerly Companies)
$route['entities'] = 'Entities/index';
$route['entities/create'] = 'Entities/create';
$route['entities/edit/(:num)'] = 'Entities/edit/$1';
// Legacy routes for backward compatibility
$route['companies'] = 'Entities/index';
$route['companies/create'] = 'Entities/create';
$route['companies/edit/(:num)'] = 'Entities/edit/$1';

// Users
$route['users'] = 'Users/index';
$route['users/create'] = 'Users/create';
$route['users/edit/(:num)'] = 'Users/edit/$1';
$route['users/permissions/(:num)'] = 'Users/permissions/$1';
$route['users/delete/(:num)'] = 'Users/delete/$1';
$route['users/fix-admin-permissions'] = 'Users/fixAdminPermissions';
$route['users/fix-manager-permissions'] = 'Users/fixManagerPermissions';

// Profile
$route['profile'] = 'Profile/index';
$route['profile/terminate-session/(:any)'] = 'Profile/terminateSession/$1';

// Settings
        $route['settings'] = 'Settings/index';
        
// Module Customization (Super Admin Only)
        $route['module_customization'] = 'Module_customization/index';
        $route['module-customization'] = 'Module_customization/index';
        
        // Global Search
        $route['search'] = 'Search/index';
        $route['search/ajax'] = 'Search/ajax';
        
        // Backup & Restore
        $route['settings/backup'] = 'Backup/index';
        $route['settings/backup/create'] = 'Backup/create';
        $route['settings/backup/download/(:any)'] = 'Backup/download/$1';
        $route['settings/backup/restore'] = 'Backup/restore';
        
        // Import/Export
        $route['import-export'] = 'Import_export/index';
        $route['import-export/import'] = 'Import_export/import';
        $route['import-export/process-import'] = 'Import_export/processImport';
        $route['import-export/export'] = 'Import_export/export';
        $route['import-export/download-template'] = 'Import_export/downloadTemplate';
        
        // System Settings
        $route['settings/system'] = 'System_settings/index';
        $route['settings/system/save'] = 'System_settings/save';
        $route['settings/system/test-email'] = 'System_settings/testEmail';
        $route['settings/system/test-sms'] = 'System_settings/testSMS';
        
        // Notifications
        $route['notifications/settings'] = 'Notifications/settings';
        $route['notifications/save-preferences'] = 'Notifications/savePreferences';
        
        // Report Builder
        $route['report-builder'] = 'Report_builder/index';
        $route['report-builder/create'] = 'Report_builder/create';
        $route['report-builder/save'] = 'Report_builder/save';
        $route['report-builder/view/(:num)'] = 'Report_builder/view/$1';
        $route['report-builder/execute/(:num)'] = 'Report_builder/execute/$1';
        $route['report-builder/export/(:num)/(:any)'] = 'Report_builder/export/$1/$2';

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
$route['receivables/payments'] = 'Receivables/payments';
$route['receivables/payments/create'] = 'Receivables/createPayment';
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
        
        // Employees (standalone module linked to Payroll)
        $route['employees'] = 'Employees/index';
        $route['employees/create'] = 'Employees/create';
        $route['employees/edit/(:num)'] = 'Employees/edit/$1';
        $route['employees/view/(:num)'] = 'Employees/view/$1';

        // Tax Management
        $route['tax'] = 'Tax/index';
        $route['tax/settings'] = 'Tax/settings';
        $route['tax/vat'] = 'Vat/index';
        $route['tax/vat/create'] = 'Vat/create';
        $route['tax/vat/view/(:num)'] = 'Vat/view/$1';
        $route['tax/wht'] = 'Wht/index';
        $route['tax/wht/create'] = 'Wht/create';
        $route['tax/wht/view/(:num)'] = 'Wht/view/$1';
        $route['tax/wht/transactions'] = 'Wht/transactions';
        $route['tax/cit'] = 'Cit/index';
        $route['tax/cit/calculate'] = 'Cit/calculate';
        $route['tax/cit/view/(:num)'] = 'Cit/view/$1';
        $route['tax/paye'] = 'Paye/index';
        $route['tax/paye/calculate/(:num)'] = 'Paye/calculate/$1';
        $route['tax/paye/view/(:num)'] = 'Paye/view/$1';
        $route['tax/reports'] = 'Tax_reports/index';
        $route['tax/reports/export'] = 'Tax_reports/export';
        $route['tax/config'] = 'Tax_config/index';
        $route['tax/config/updateRate'] = 'Tax_config/updateRate';
        $route['tax/config/updateRates'] = 'Tax_config/updateRates';
        $route['tax/config/create'] = 'Tax_config/create';
        $route['tax/config/edit/(:num)'] = 'Tax_config/edit/$1';
        $route['tax/config/delete/(:num)'] = 'Tax_config/delete/$1';
        $route['tax/config/toggle/(:num)'] = 'Tax_config/toggleStatus/$1';
        $route['tax/payments'] = 'Tax_payments/index';
        $route['tax/payments/create'] = 'Tax_payments/create';
        
        // POS System
        $route['pos'] = 'Pos/index';
        $route['pos/process'] = 'Pos/processSale';
        $route['pos/receipt/(:num)'] = 'Pos/receipt/$1';
        $route['pos/terminals'] = 'Pos/terminals';
        $route['pos/create-terminal'] = 'Pos/createTerminal';
        $route['pos/reports'] = 'Pos/reports';

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
        $route['bookings/reschedule/(:num)'] = 'Bookings/reschedule/$1';
        $route['bookings/cancel/(:num)'] = 'Bookings/cancel/$1';
        $route['bookings/modifications/(:num)'] = 'Bookings/modifications/$1';
        $route['bookings/add-resource/(:num)'] = 'Bookings/addResource/$1';
        $route['bookings/remove-resource/(:num)'] = 'Bookings/removeResource/$1';
        
        // Public Booking Portal - Enhanced Multi-step Wizard
        // Legacy routes redirect to new wizard
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
        
        // Customer Portal
        $route['customer-portal'] = 'Customer_portal/login';
        $route['customer-portal/register'] = 'Customer_portal/register';
        $route['customer-portal/login'] = 'Customer_portal/login';
        $route['customer-portal/logout'] = 'Customer_portal/logout';
        $route['customer-portal/dashboard'] = 'Customer_portal/dashboard';
        $route['customer-portal/bookings'] = 'Customer_portal/bookings';
        $route['customer-portal/bookings/(:any)'] = 'Customer_portal/bookings/$1';
        $route['customer-portal/booking/(:num)'] = 'Customer_portal/viewBooking/$1';
        $route['customer-portal/profile'] = 'Customer_portal/profile';
        
        // Notifications
        $route['notifications'] = 'Notifications/getNotifications';
        $route['notifications/mark-read/(:num)'] = 'Notifications/markRead/$1';
        $route['notifications/mark-all-read'] = 'Notifications/markAllRead';
        $route['notifications/settings'] = 'Notifications/settings';
        
        // Location Management (formerly Property Management)
        $route['locations'] = 'Locations/index';
        $route['locations/create'] = 'Locations/create';
        $route['locations/view/(:num)'] = 'Locations/view/$1';
        $route['locations/edit/(:num)'] = 'Locations/edit/$1';
        // Legacy routes for backward compatibility
        $route['properties'] = 'Locations/index';
        $route['properties/create'] = 'Locations/create';
        $route['properties/view/(:num)'] = 'Locations/view/$1';
        $route['properties/edit/(:num)'] = 'Locations/edit/$1';
        $route['spaces'] = 'Spaces/index';
        $route['spaces/(:num)'] = 'Spaces/index/$1';
        $route['spaces/create'] = 'Spaces/create';
        $route['spaces/create/(:num)'] = 'Spaces/create/$1';
        $route['spaces/view/(:num)'] = 'Spaces/view/$1';
        $route['spaces/edit/(:num)'] = 'Spaces/edit/$1';
        $route['spaces/sync/(:num)'] = 'Spaces/syncToBooking/$1';
        $route['tenants'] = 'Tenants/index';
        $route['tenants/create'] = 'Tenants/create';
        $route['tenants/view/(:num)'] = 'Tenants/view/$1';
        $route['tenants/edit/(:num)'] = 'Tenants/edit/$1';
        $route['leases'] = 'Leases/index';
        $route['leases/create'] = 'Leases/create';
        $route['leases/view/(:num)'] = 'Leases/view/$1';
        $route['rent-invoices'] = 'Rent_invoices/index';
        $route['rent-invoices/generate/(:num)'] = 'Rent_invoices/generate/$1';
        $route['rent-invoices/auto-generate'] = 'Rent_invoices/autoGenerate';
        $route['rent-invoices/view/(:num)'] = 'Rent_invoices/view/$1';
        $route['rent-invoices/record-payment'] = 'Rent_invoices/recordPayment';
        
        // Utilities Management
        $route['utilities'] = 'Utilities/index';
        $route['utilities/meters'] = 'Meters/index';
        $route['utilities/meters/create'] = 'Meters/create';
        $route['utilities/meters/view/(:num)'] = 'Meters/view/$1';
        $route['utilities/meters/edit/(:num)'] = 'Meters/edit/$1';
        $route['utilities/readings'] = 'Meter_readings/index';
        $route['utilities/readings/create'] = 'Meter_readings/create';
        $route['utilities/bills'] = 'Utility_bills/index';
        $route['utilities/bills/generate'] = 'Utility_bills/generate';
        $route['utilities/bills/generate/(:num)'] = 'Utility_bills/generate/$1';
        $route['utilities/bills/view/(:num)'] = 'Utility_bills/view/$1';
        $route['utilities/providers'] = 'Utility_providers/index';
        $route['utilities/providers/create'] = 'Utility_providers/create';
        $route['utilities/providers/view/(:num)'] = 'Utility_providers/view/$1';
        $route['utilities/providers/edit/(:num)'] = 'Utility_providers/edit/$1';
        $route['utilities/providers/delete/(:num)'] = 'Utility_providers/delete/$1';
        $route['utilities/tariffs'] = 'Tariffs/index';
        $route['utilities/tariffs/create'] = 'Tariffs/create';
        $route['utilities/tariffs/view/(:num)'] = 'Tariffs/view/$1';
        $route['utilities/tariffs/edit/(:num)'] = 'Tariffs/edit/$1';
        $route['utilities/tariffs/delete/(:num)'] = 'Tariffs/delete/$1';
        $route['utilities/payments'] = 'Utility_payments/index';
        $route['utilities/payments/record/(:num)'] = 'Utility_payments/record/$1';
        $route['utilities/reports'] = 'Utility_reports/index';
        $route['utilities/reports/consumption'] = 'Utility_reports/consumption';
        $route['utilities/reports/cost'] = 'Utility_reports/cost';
        $route['utilities/reports/billing'] = 'Utility_reports/billing';
        $route['utilities/vendor-bills'] = 'Vendor_utility_bills/index';
        $route['utilities/vendor-bills/create'] = 'Vendor_utility_bills/create';
        $route['utilities/vendor-bills/view/(:num)'] = 'Vendor_utility_bills/view/$1';
        $route['utilities/vendor-bills/approve/(:num)'] = 'Vendor_utility_bills/approve/$1';
        $route['utilities/allocations/allocate/(:num)'] = 'Utility_allocations/allocate/$1';
        $route['utilities/alerts'] = 'Utility_alerts/index';
        $route['utilities/alerts/resolve/(:num)'] = 'Utility_alerts/resolve/$1';
        
        // Inventory Management
        $route['inventory'] = 'Inventory/index';
        $route['inventory/items'] = 'Items/index';
        $route['inventory/items/create'] = 'Items/create';
        $route['inventory/items/view/(:num)'] = 'Items/view/$1';
        $route['inventory/items/edit/(:num)'] = 'Items/edit/$1';
        $route['inventory/locations'] = 'Locations/index';
        $route['inventory/locations/create'] = 'Locations/create';
        $route['inventory/locations/view/(:num)'] = 'Locations/view/$1';
        $route['inventory/receive'] = 'Stock_movements/receive';
        $route['inventory/issue'] = 'Stock_movements/issue';
        $route['inventory/transfer'] = 'Stock_movements/transfer';
        $route['inventory/purchase-orders'] = 'Purchase_orders/index';
        $route['inventory/purchase-orders/create'] = 'Purchase_orders/create';
        $route['inventory/purchase-orders/view/(:num)'] = 'Purchase_orders/view/$1';
        $route['inventory/suppliers'] = 'Suppliers/index';
        $route['inventory/suppliers/create'] = 'Suppliers/create';
        $route['inventory/suppliers/view/(:num)'] = 'Suppliers/view/$1';
        $route['inventory/goods-receipts'] = 'Goods_receipts/index';
        $route['inventory/goods-receipts/create'] = 'Goods_receipts/create';
        $route['inventory/goods-receipts/view/(:num)'] = 'Goods_receipts/view/$1';
        $route['inventory/adjustments'] = 'Stock_adjustments/index';
        $route['inventory/adjustments/create'] = 'Stock_adjustments/create';
        $route['inventory/adjustments/view/(:num)'] = 'Stock_adjustments/view/$1';
        $route['inventory/adjustments/approve/(:num)'] = 'Stock_adjustments/approve/$1';
        $route['inventory/api/stock-level'] = 'Stock_adjustments/getStockLevel';
        $route['inventory/stock-takes'] = 'Stock_takes/index';
        $route['inventory/stock-takes/create'] = 'Stock_takes/create';
        $route['inventory/stock-takes/view/(:num)'] = 'Stock_takes/view/$1';
        $route['inventory/stock-takes/start/(:num)'] = 'Stock_takes/start/$1';
        $route['inventory/stock-takes/update-count'] = 'Stock_takes/updateCount';
        $route['inventory/stock-takes/complete/(:num)'] = 'Stock_takes/complete/$1';
        $route['inventory/assets'] = 'Fixed_assets/index';
        $route['inventory/assets/create'] = 'Fixed_assets/create';
        $route['inventory/assets/view/(:num)'] = 'Fixed_assets/view/$1';
        $route['inventory/assets/edit/(:num)'] = 'Fixed_assets/edit/$1';
        $route['inventory/reports'] = 'Inventory_reports/index';
        $route['inventory/reports/stock'] = 'Inventory_reports/stock';
        $route['inventory/reports/movements'] = 'Inventory_reports/movements';
        $route['inventory/reports/valuation'] = 'Inventory_reports/valuation';
        $route['inventory/reports/reorder'] = 'Inventory_reports/reorder';
        $route['inventory/reports/movement-analysis'] = 'Inventory_reports/movementAnalysis';
        $route['inventory/reports/purchases'] = 'Inventory_reports/purchases';

        return $route;

