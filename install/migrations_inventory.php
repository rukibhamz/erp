<?php
/**
 * Database Migration Script for Inventory Management Module
 */

function runInventoryMigrations($pdo, $prefix = 'erp_') {
    try {
        // Items Master
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `sku` VARCHAR(100) NOT NULL UNIQUE,
            `item_name` VARCHAR(255) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `item_type` ENUM('inventory', 'non_inventory', 'service', 'fixed_asset') NOT NULL DEFAULT 'inventory',
            `category` VARCHAR(100) DEFAULT NULL,
            `subcategory` VARCHAR(100) DEFAULT NULL,
            `brand` VARCHAR(100) DEFAULT NULL,
            `manufacturer` VARCHAR(100) DEFAULT NULL,
            `model_number` VARCHAR(100) DEFAULT NULL,
            `barcode` VARCHAR(100) DEFAULT NULL UNIQUE,
            `qr_code` VARCHAR(100) DEFAULT NULL,
            `unit_of_measure` VARCHAR(20) DEFAULT 'each',
            `specifications` JSON DEFAULT NULL,
            `reorder_point` DECIMAL(10,2) DEFAULT 0,
            `reorder_quantity` DECIMAL(10,2) DEFAULT 0,
            `safety_stock` DECIMAL(10,2) DEFAULT 0,
            `max_stock` DECIMAL(10,2) DEFAULT NULL,
            `lead_time_days` INT(11) DEFAULT 0,
            `item_status` ENUM('active', 'discontinued', 'out_of_stock') DEFAULT 'active',
            `cost_price` DECIMAL(15,2) DEFAULT 0,
            `average_cost` DECIMAL(15,2) DEFAULT 0,
            `selling_price` DECIMAL(15,2) DEFAULT 0,
            `retail_price` DECIMAL(15,2) DEFAULT 0,
            `wholesale_price` DECIMAL(15,2) DEFAULT 0,
            `costing_method` ENUM('fifo', 'lifo', 'weighted_average', 'standard', 'actual') DEFAULT 'weighted_average',
            `track_serial` TINYINT(1) DEFAULT 0,
            `track_batch` TINYINT(1) DEFAULT 0,
            `expiry_tracking` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `sku` (`sku`),
            UNIQUE KEY `barcode` (`barcode`),
            KEY `item_type` (`item_type`),
            KEY `category` (`category`),
            KEY `item_status` (`item_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Item Photos
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}item_photos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `item_id` INT(11) NOT NULL,
            `photo_url` VARCHAR(255) NOT NULL,
            `is_primary` TINYINT(1) DEFAULT 0,
            `display_order` INT(11) DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `item_id` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Item Variants
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}item_variants` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `item_id` INT(11) NOT NULL,
            `variant_name` VARCHAR(255) NOT NULL,
            `variant_type` ENUM('size', 'color', 'material', 'other') DEFAULT 'other',
            `sku` VARCHAR(100) NOT NULL UNIQUE,
            `barcode` VARCHAR(100) DEFAULT NULL,
            `price` DECIMAL(15,2) DEFAULT 0,
            `cost_price` DECIMAL(15,2) DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `sku` (`sku`),
            KEY `item_id` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Suppliers
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}suppliers` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `supplier_code` VARCHAR(50) NOT NULL UNIQUE,
            `supplier_name` VARCHAR(255) NOT NULL,
            `contact_person` VARCHAR(255) DEFAULT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `payment_terms` INT(11) DEFAULT 30,
            `lead_time_days` INT(11) DEFAULT 0,
            `rating` DECIMAL(3,2) DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `supplier_code` (`supplier_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Item Suppliers (Many-to-Many)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}item_suppliers` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `item_id` INT(11) NOT NULL,
            `supplier_id` INT(11) NOT NULL,
            `supplier_sku` VARCHAR(100) DEFAULT NULL,
            `cost` DECIMAL(15,2) DEFAULT 0,
            `lead_time_days` INT(11) DEFAULT 0,
            `is_preferred` TINYINT(1) DEFAULT 0,
            `min_order_qty` DECIMAL(10,2) DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `item_supplier` (`item_id`, `supplier_id`),
            KEY `item_id` (`item_id`),
            KEY `supplier_id` (`supplier_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Locations
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}locations` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `location_code` VARCHAR(50) NOT NULL UNIQUE,
            `location_name` VARCHAR(255) NOT NULL,
            `location_type` ENUM('warehouse', 'store', 'room', 'shelf', 'bin', 'aisle', 'rack') DEFAULT 'warehouse',
            `parent_id` INT(11) DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `capacity` DECIMAL(10,2) DEFAULT NULL,
            `barcode` VARCHAR(100) DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `location_code` (`location_code`),
            KEY `parent_id` (`parent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Stock Levels
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}stock_levels` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `item_id` INT(11) NOT NULL,
            `location_id` INT(11) NOT NULL,
            `quantity` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `reserved_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `available_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `reorder_point` DECIMAL(10,2) DEFAULT 0,
            `last_movement_date` DATETIME DEFAULT NULL,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `item_location` (`item_id`, `location_id`),
            KEY `item_id` (`item_id`),
            KEY `location_id` (`location_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Stock Transactions
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}stock_transactions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `transaction_number` VARCHAR(100) NOT NULL UNIQUE,
            `transaction_type` ENUM('receipt', 'issue', 'transfer', 'adjustment', 'return', 'assembly', 'disassembly') NOT NULL,
            `item_id` INT(11) NOT NULL,
            `location_from_id` INT(11) DEFAULT NULL,
            `location_to_id` INT(11) DEFAULT NULL,
            `quantity` DECIMAL(10,2) NOT NULL,
            `unit_cost` DECIMAL(15,2) DEFAULT 0,
            `total_cost` DECIMAL(15,2) DEFAULT 0,
            `reference_type` VARCHAR(50) DEFAULT NULL,
            `reference_id` INT(11) DEFAULT NULL,
            `reference_number` VARCHAR(100) DEFAULT NULL,
            `batch_number` VARCHAR(100) DEFAULT NULL,
            `serial_numbers` JSON DEFAULT NULL,
            `expiry_date` DATE DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `transaction_date` DATETIME NOT NULL,
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `transaction_number` (`transaction_number`),
            KEY `item_id` (`item_id`),
            KEY `location_from_id` (`location_from_id`),
            KEY `location_to_id` (`location_to_id`),
            KEY `transaction_type` (`transaction_type`),
            KEY `transaction_date` (`transaction_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Serial Numbers
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}serial_numbers` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `item_id` INT(11) NOT NULL,
            `serial_number` VARCHAR(100) NOT NULL UNIQUE,
            `status` ENUM('available', 'sold', 'in_use', 'returned', 'scrapped') DEFAULT 'available',
            `purchase_date` DATE DEFAULT NULL,
            `warranty_expiry` DATE DEFAULT NULL,
            `location_id` INT(11) DEFAULT NULL,
            `current_cost` DECIMAL(15,2) DEFAULT 0,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `serial_number` (`serial_number`),
            KEY `item_id` (`item_id`),
            KEY `location_id` (`location_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Batches
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}batches` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `item_id` INT(11) NOT NULL,
            `batch_number` VARCHAR(100) NOT NULL,
            `manufacture_date` DATE DEFAULT NULL,
            `expiry_date` DATE DEFAULT NULL,
            `quantity_received` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `quantity_sold` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `quantity_remaining` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `location_id` INT(11) DEFAULT NULL,
            `unit_cost` DECIMAL(15,2) DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `item_batch` (`item_id`, `batch_number`),
            KEY `item_id` (`item_id`),
            KEY `location_id` (`location_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Purchase Orders
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}purchase_orders` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `po_number` VARCHAR(100) NOT NULL UNIQUE,
            `supplier_id` INT(11) NOT NULL,
            `order_date` DATE NOT NULL,
            `expected_date` DATE DEFAULT NULL,
            `delivery_address` TEXT DEFAULT NULL,
            `status` ENUM('draft', 'sent', 'partial', 'received', 'closed', 'cancelled') DEFAULT 'draft',
            `subtotal` DECIMAL(15,2) DEFAULT 0,
            `tax_amount` DECIMAL(15,2) DEFAULT 0,
            `total_amount` DECIMAL(15,2) DEFAULT 0,
            `notes` TEXT DEFAULT NULL,
            `created_by` INT(11) DEFAULT NULL,
            `approved_by` INT(11) DEFAULT NULL,
            `approved_at` DATETIME DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `po_number` (`po_number`),
            KEY `supplier_id` (`supplier_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Purchase Order Items
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}purchase_order_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `po_id` INT(11) NOT NULL,
            `item_id` INT(11) NOT NULL,
            `quantity` DECIMAL(10,2) NOT NULL,
            `unit_price` DECIMAL(15,2) NOT NULL,
            `quantity_received` DECIMAL(10,2) DEFAULT 0,
            `quantity_pending` DECIMAL(10,2) NOT NULL,
            `line_total` DECIMAL(15,2) NOT NULL,
            `notes` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `po_id` (`po_id`),
            KEY `item_id` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Goods Receipts
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}goods_receipts` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `grn_number` VARCHAR(100) NOT NULL UNIQUE,
            `po_id` INT(11) DEFAULT NULL,
            `supplier_id` INT(11) DEFAULT NULL,
            `receipt_date` DATE NOT NULL,
            `received_by` INT(11) DEFAULT NULL,
            `location_id` INT(11) DEFAULT NULL,
            `quality_inspection` TEXT DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `status` ENUM('draft', 'completed') DEFAULT 'completed',
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `grn_number` (`grn_number`),
            KEY `po_id` (`po_id`),
            KEY `supplier_id` (`supplier_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Goods Receipt Items
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}goods_receipt_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `grn_id` INT(11) NOT NULL,
            `item_id` INT(11) NOT NULL,
            `po_item_id` INT(11) DEFAULT NULL,
            `quantity` DECIMAL(10,2) NOT NULL,
            `unit_cost` DECIMAL(15,2) NOT NULL,
            `batch_number` VARCHAR(100) DEFAULT NULL,
            `serial_numbers` JSON DEFAULT NULL,
            `expiry_date` DATE DEFAULT NULL,
            `location_id` INT(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `grn_id` (`grn_id`),
            KEY `item_id` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Stock Adjustments
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}stock_adjustments` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `adjustment_number` VARCHAR(100) NOT NULL UNIQUE,
            `item_id` INT(11) NOT NULL,
            `location_id` INT(11) NOT NULL,
            `quantity_before` DECIMAL(10,2) NOT NULL,
            `quantity_after` DECIMAL(10,2) NOT NULL,
            `adjustment_qty` DECIMAL(10,2) NOT NULL,
            `reason` ENUM('damage', 'loss', 'found', 'correction', 'theft', 'other') NOT NULL,
            `notes` TEXT DEFAULT NULL,
            `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            `adjusted_by` INT(11) DEFAULT NULL,
            `approved_by` INT(11) DEFAULT NULL,
            `approved_at` DATETIME DEFAULT NULL,
            `adjustment_date` DATETIME NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `adjustment_number` (`adjustment_number`),
            KEY `item_id` (`item_id`),
            KEY `location_id` (`location_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Stock Takes
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}stock_takes` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `stock_take_number` VARCHAR(100) NOT NULL UNIQUE,
            `location_id` INT(11) DEFAULT NULL,
            `scheduled_date` DATE NOT NULL,
            `status` ENUM('scheduled', 'in_progress', 'completed', 'approved', 'posted') DEFAULT 'scheduled',
            `counted_by` INT(11) DEFAULT NULL,
            `approved_by` INT(11) DEFAULT NULL,
            `approved_at` DATETIME DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `stock_take_number` (`stock_take_number`),
            KEY `location_id` (`location_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Stock Take Items
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}stock_take_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `stock_take_id` INT(11) NOT NULL,
            `item_id` INT(11) NOT NULL,
            `expected_qty` DECIMAL(10,2) NOT NULL,
            `counted_qty` DECIMAL(10,2) DEFAULT NULL,
            `variance` DECIMAL(10,2) DEFAULT 0,
            `counted_by` INT(11) DEFAULT NULL,
            `counted_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `stock_take_id` (`stock_take_id`),
            KEY `item_id` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Fixed Assets
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}fixed_assets` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `asset_tag` VARCHAR(100) NOT NULL UNIQUE,
            `asset_name` VARCHAR(255) NOT NULL,
            `asset_category` ENUM('vehicles', 'equipment', 'furniture', 'it', 'buildings', 'other') DEFAULT 'equipment',
            `item_id` INT(11) DEFAULT NULL,
            `purchase_cost` DECIMAL(15,2) NOT NULL,
            `purchase_date` DATE NOT NULL,
            `supplier_id` INT(11) DEFAULT NULL,
            `location_id` INT(11) DEFAULT NULL,
            `custodian_id` INT(11) DEFAULT NULL,
            `depreciation_method` ENUM('straight_line', 'declining_balance') DEFAULT 'straight_line',
            `useful_life_years` INT(11) DEFAULT 5,
            `salvage_value` DECIMAL(15,2) DEFAULT 0,
            `current_value` DECIMAL(15,2) NOT NULL,
            `warranty_expiry` DATE DEFAULT NULL,
            `insurance_details` TEXT DEFAULT NULL,
            `asset_status` ENUM('active', 'disposed', 'retired', 'under_maintenance') DEFAULT 'active',
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `asset_tag` (`asset_tag`),
            KEY `item_id` (`item_id`),
            KEY `location_id` (`location_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Asset Depreciation
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}asset_depreciation` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `asset_id` INT(11) NOT NULL,
            `depreciation_date` DATE NOT NULL,
            `depreciation_amount` DECIMAL(15,2) NOT NULL,
            `accumulated_depreciation` DECIMAL(15,2) NOT NULL,
            `net_book_value` DECIMAL(15,2) NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `asset_id` (`asset_id`),
            KEY `depreciation_date` (`depreciation_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Asset Maintenance
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}asset_maintenance` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `asset_id` INT(11) NOT NULL,
            `maintenance_type` ENUM('repair', 'service', 'inspection', 'calibration', 'other') NOT NULL,
            `maintenance_date` DATE NOT NULL,
            `cost` DECIMAL(15,2) DEFAULT 0,
            `next_due_date` DATE DEFAULT NULL,
            `service_provider` VARCHAR(255) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `asset_id` (`asset_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tool Checkouts
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tool_checkouts` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `item_id` INT(11) NOT NULL,
            `employee_id` INT(11) NOT NULL,
            `checkout_date` DATETIME NOT NULL,
            `expected_return_date` DATETIME DEFAULT NULL,
            `actual_return_date` DATETIME DEFAULT NULL,
            `checkout_condition` TEXT DEFAULT NULL,
            `return_condition` TEXT DEFAULT NULL,
            `status` ENUM('checked_out', 'returned', 'overdue', 'lost') DEFAULT 'checked_out',
            `notes` TEXT DEFAULT NULL,
            `created_by` INT(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `item_id` (`item_id`),
            KEY `employee_id` (`employee_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Bill of Materials (BOM)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}bom_headers` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `finished_item_id` INT(11) NOT NULL,
            `bom_name` VARCHAR(255) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `labor_hours` DECIMAL(10,2) DEFAULT 0,
            `labor_cost` DECIMAL(15,2) DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `finished_item_id` (`finished_item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // BOM Items
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}bom_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `bom_id` INT(11) NOT NULL,
            `component_item_id` INT(11) NOT NULL,
            `quantity` DECIMAL(10,2) NOT NULL,
            `waste_percentage` DECIMAL(5,2) DEFAULT 0,
            `notes` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `bom_id` (`bom_id`),
            KEY `component_item_id` (`component_item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Assembly Orders
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}assembly_orders` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `assembly_number` VARCHAR(100) NOT NULL UNIQUE,
            `bom_id` INT(11) NOT NULL,
            `finished_item_id` INT(11) NOT NULL,
            `quantity` DECIMAL(10,2) NOT NULL,
            `status` ENUM('draft', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft',
            `start_date` DATETIME DEFAULT NULL,
            `completed_date` DATETIME DEFAULT NULL,
            `completed_by` INT(11) DEFAULT NULL,
            `location_id` INT(11) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `assembly_number` (`assembly_number`),
            KEY `bom_id` (`bom_id`),
            KEY `finished_item_id` (`finished_item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Inventory Management tables created successfully.\n";
    } catch (Exception $e) {
        echo "Error creating inventory tables: " . $e->getMessage() . "\n";
        throw $e;
    }
}

