<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function runPerformanceMigrations($pdo, $prefix = 'erp_') {
    try {
        // Add indexes for frequently queried columns
        $indexes = [
            // Transactions
            "CREATE INDEX IF NOT EXISTS idx_transactions_date ON `{$prefix}transactions`(date)",
            "CREATE INDEX IF NOT EXISTS idx_transactions_account ON `{$prefix}transactions`(account_id)",
            "CREATE INDEX IF NOT EXISTS idx_transactions_reference ON `{$prefix}transactions`(reference)",
            
            // Invoices
            "CREATE INDEX IF NOT EXISTS idx_invoices_date ON `{$prefix}invoices`(invoice_date)",
            "CREATE INDEX IF NOT EXISTS idx_invoices_customer ON `{$prefix}invoices`(customer_id)",
            "CREATE INDEX IF NOT EXISTS idx_invoices_status ON `{$prefix}invoices`(status)",
            "CREATE INDEX IF NOT EXISTS idx_invoices_number ON `{$prefix}invoices`(invoice_number)",
            
            // Bookings
            "CREATE INDEX IF NOT EXISTS idx_bookings_date ON `{$prefix}bookings`(booking_date)",
            "CREATE INDEX IF NOT EXISTS idx_bookings_status ON `{$prefix}bookings`(status)",
            "CREATE INDEX IF NOT EXISTS idx_bookings_resource ON `{$prefix}bookings`(resource_id)",
            
            // Payments
            "CREATE INDEX IF NOT EXISTS idx_payments_date ON `{$prefix}payments`(payment_date)",
            "CREATE INDEX IF NOT EXISTS idx_payments_invoice ON `{$prefix}payments`(invoice_id)",
            
            // Stock Levels
            "CREATE INDEX IF NOT EXISTS idx_stock_item ON `{$prefix}stock_levels`(item_id)",
            "CREATE INDEX IF NOT EXISTS idx_stock_location ON `{$prefix}stock_levels`(location_id)",
            
            // Activities
            "CREATE INDEX IF NOT EXISTS idx_activities_user ON `{$prefix}activity_log`(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_activities_date ON `{$prefix}activity_log`(created_at)",
            "CREATE INDEX IF NOT EXISTS idx_activities_module ON `{$prefix}activity_log`(module)",
            
            // POS Sales
            "CREATE INDEX IF NOT EXISTS idx_pos_sales_date ON `{$prefix}pos_sales`(sale_date)",
            "CREATE INDEX IF NOT EXISTS idx_pos_sales_terminal ON `{$prefix}pos_sales`(terminal_id)",
            "CREATE INDEX IF NOT EXISTS idx_pos_sales_status ON `{$prefix}pos_sales`(status)",
            
            // Notifications
            "CREATE INDEX IF NOT EXISTS idx_notifications_user ON `{$prefix}notifications`(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_notifications_read ON `{$prefix}notifications`(is_read)",
        ];
        
        foreach ($indexes as $index) {
            try {
                $pdo->exec($index);
            } catch (PDOException $e) {
                // Index might already exist, skip
                error_log("Index creation warning: " . $e->getMessage());
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Performance migrations error: " . $e->getMessage());
        throw $e;
    }
}



