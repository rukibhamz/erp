<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Property Management Module Migration
 * Creates all tables for comprehensive property management system
 */

function runPropertyManagementMigrations($pdo, $prefix = 'erp_') {
    try {
        // Properties table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}properties` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `property_code` varchar(50) NOT NULL UNIQUE,
            `property_name` varchar(255) NOT NULL,
            `property_type` enum('multi_purpose','standalone_building','land','other') DEFAULT 'multi_purpose',
            `address` text DEFAULT NULL,
            `city` varchar(100) DEFAULT NULL,
            `state` varchar(100) DEFAULT NULL,
            `country` varchar(100) DEFAULT NULL,
            `postal_code` varchar(20) DEFAULT NULL,
            `gps_latitude` decimal(10,8) DEFAULT NULL,
            `gps_longitude` decimal(11,8) DEFAULT NULL,
            `land_area` decimal(10,2) DEFAULT NULL COMMENT 'in square meters',
            `built_area` decimal(10,2) DEFAULT NULL COMMENT 'in square meters',
            `year_built` int(4) DEFAULT NULL,
            `year_acquired` int(4) DEFAULT NULL,
            `property_value` decimal(15,2) DEFAULT NULL,
            `manager_id` int(11) DEFAULT NULL COMMENT 'user_id',
            `status` enum('operational','under_construction','under_renovation','closed') DEFAULT 'operational',
            `ownership_status` enum('owned','leased','joint_venture') DEFAULT 'owned',
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `property_code` (`property_code`),
            KEY `manager_id` (`manager_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Property documents
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}property_documents` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `property_id` int(11) NOT NULL,
            `document_type` varchar(100) NOT NULL,
            `document_name` varchar(255) NOT NULL,
            `file_url` varchar(500) NOT NULL,
            `expiry_date` date DEFAULT NULL,
            `upload_date` datetime NOT NULL,
            `uploaded_by` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `property_id` (`property_id`),
            KEY `document_type` (`document_type`),
            KEY `expiry_date` (`expiry_date`),
            CONSTRAINT `{$prefix}property_documents_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `{$prefix}properties` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Spaces/Units table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}spaces` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `property_id` int(11) NOT NULL,
            `space_number` varchar(50) DEFAULT NULL,
            `space_name` varchar(255) NOT NULL,
            `parent_space_id` int(11) DEFAULT NULL COMMENT 'For sub-spaces',
            `category` enum('event_space','commercial','hospitality','storage','parking','residential','other') NOT NULL,
            `space_type` varchar(100) DEFAULT NULL COMMENT 'hall,meeting_room,store,office,restaurant,etc.',
            `floor` varchar(50) DEFAULT NULL,
            `area` decimal(10,2) DEFAULT NULL COMMENT 'square meters',
            `capacity` int(11) DEFAULT NULL COMMENT 'persons or vehicles',
            `configuration` varchar(255) DEFAULT NULL COMMENT 'theater,classroom,banquet,etc.',
            `amenities` text DEFAULT NULL COMMENT 'JSON array',
            `accessibility_features` text DEFAULT NULL COMMENT 'JSON array',
            `operational_status` enum('active','under_maintenance','under_renovation','temporarily_closed','decommissioned') DEFAULT 'active',
            `operational_mode` enum('available_for_booking','leased','owner_operated','reserved','vacant') DEFAULT 'vacant',
            `is_bookable` tinyint(1) DEFAULT 0,
            `facility_id` int(11) DEFAULT NULL COMMENT 'Link to facilities table when bookable',
            `description` text DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `property_id` (`property_id`),
            KEY `parent_space_id` (`parent_space_id`),
            KEY `category` (`category`),
            KEY `operational_status` (`operational_status`),
            KEY `operational_mode` (`operational_mode`),
            KEY `is_bookable` (`is_bookable`),
            KEY `facility_id` (`facility_id`),
            CONSTRAINT `{$prefix}spaces_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `{$prefix}properties` (`id`) ON DELETE CASCADE,
            CONSTRAINT `{$prefix}spaces_ibfk_2` FOREIGN KEY (`parent_space_id`) REFERENCES `{$prefix}spaces` (`id`) ON DELETE SET NULL,
            CONSTRAINT `{$prefix}spaces_ibfk_3` FOREIGN KEY (`facility_id`) REFERENCES `{$prefix}facilities` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Space photos
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}space_photos` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `space_id` int(11) NOT NULL,
            `photo_url` varchar(500) NOT NULL,
            `photo_type` enum('photo','floor_plan','virtual_tour') DEFAULT 'photo',
            `is_primary` tinyint(1) DEFAULT 0,
            `caption` varchar(255) DEFAULT NULL,
            `display_order` int(11) DEFAULT 0,
            `uploaded_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `space_id` (`space_id`),
            KEY `is_primary` (`is_primary`),
            CONSTRAINT `{$prefix}space_photos_ibfk_1` FOREIGN KEY (`space_id`) REFERENCES `{$prefix}spaces` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Bookable configuration
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}bookable_config` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `space_id` int(11) NOT NULL,
            `is_bookable` tinyint(1) DEFAULT 1,
            `booking_types` text DEFAULT NULL COMMENT 'JSON: hourly,half_day,full_day,multi_day',
            `minimum_duration` int(11) DEFAULT 1 COMMENT 'hours',
            `maximum_duration` int(11) DEFAULT NULL COMMENT 'hours',
            `advance_booking_days` int(11) DEFAULT 365 COMMENT 'how far ahead bookings allowed',
            `cancellation_policy_id` int(11) DEFAULT NULL,
            `pricing_rules` text DEFAULT NULL COMMENT 'JSON: base_rates,peak_rates,seasonal,etc.',
            `availability_rules` text DEFAULT NULL COMMENT 'JSON: operating_hours,days_available,blackout_dates',
            `setup_time_buffer` int(11) DEFAULT 0 COMMENT 'minutes',
            `cleanup_time_buffer` int(11) DEFAULT 0 COMMENT 'minutes',
            `simultaneous_limit` int(11) DEFAULT 1,
            `last_synced_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `space_id` (`space_id`),
            KEY `is_bookable` (`is_bookable`),
            CONSTRAINT `{$prefix}bookable_config_ibfk_1` FOREIGN KEY (`space_id`) REFERENCES `{$prefix}spaces` (`id`) ON DELETE CASCADE,
            CONSTRAINT `{$prefix}bookable_config_ibfk_2` FOREIGN KEY (`cancellation_policy_id`) REFERENCES `{$prefix}cancellation_policies` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tenants table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tenants` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `tenant_code` varchar(50) NOT NULL UNIQUE,
            `tenant_type` enum('commercial','residential','short_term','owner_operated') NOT NULL,
            `business_name` varchar(255) DEFAULT NULL,
            `business_registration` varchar(100) DEFAULT NULL,
            `business_type` varchar(100) DEFAULT NULL,
            `contact_person` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `phone` varchar(50) NOT NULL,
            `alternate_phone` varchar(50) DEFAULT NULL,
            `address` text DEFAULT NULL,
            `city` varchar(100) DEFAULT NULL,
            `state` varchar(100) DEFAULT NULL,
            `country` varchar(100) DEFAULT NULL,
            `documents` text DEFAULT NULL COMMENT 'JSON: CAC,permits,etc.',
            `status` enum('active','inactive','past_tenant') DEFAULT 'active',
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `tenant_code` (`tenant_code`),
            KEY `tenant_type` (`tenant_type`),
            KEY `status` (`status`),
            KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Leases table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}leases` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `lease_number` varchar(50) NOT NULL UNIQUE,
            `space_id` int(11) NOT NULL,
            `tenant_id` int(11) NOT NULL,
            `lease_type` enum('commercial','residential','mixed') NOT NULL,
            `lease_term` enum('fixed_term','periodic','month_to_month') NOT NULL,
            `start_date` date NOT NULL,
            `end_date` date DEFAULT NULL COMMENT 'NULL for periodic/month-to-month',
            `rent_amount` decimal(15,2) NOT NULL,
            `payment_frequency` enum('monthly','weekly','quarterly','annually','daily') DEFAULT 'monthly',
            `rent_due_date` int(2) DEFAULT 5 COMMENT 'day of month (1-31)',
            `security_deposit` decimal(15,2) DEFAULT 0.00,
            `service_charge` decimal(15,2) DEFAULT 0.00,
            `utility_responsibility` enum('tenant','landlord','shared') DEFAULT 'tenant',
            `maintenance_responsibility` enum('tenant','landlord','shared') DEFAULT 'landlord',
            `permitted_use` text DEFAULT NULL,
            `subletting_allowed` tinyint(1) DEFAULT 0,
            `rent_escalation_rate` decimal(5,2) DEFAULT 0.00 COMMENT 'percentage annual increase',
            `terms` text DEFAULT NULL COMMENT 'JSON: additional terms',
            `status` enum('active','expired','terminated','pending_renewal') DEFAULT 'active',
            `renewal_notice_sent` tinyint(1) DEFAULT 0,
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `lease_number` (`lease_number`),
            KEY `space_id` (`space_id`),
            KEY `tenant_id` (`tenant_id`),
            KEY `status` (`status`),
            KEY `end_date` (`end_date`),
            CONSTRAINT `{$prefix}leases_ibfk_1` FOREIGN KEY (`space_id`) REFERENCES `{$prefix}spaces` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `{$prefix}leases_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `{$prefix}tenants` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Lease documents
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}lease_documents` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `lease_id` int(11) NOT NULL,
            `document_type` varchar(100) NOT NULL COMMENT 'lease_agreement,tenant_id,insurance,permit',
            `document_name` varchar(255) NOT NULL,
            `file_url` varchar(500) NOT NULL,
            `upload_date` datetime NOT NULL,
            `uploaded_by` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `lease_id` (`lease_id`),
            KEY `document_type` (`document_type`),
            CONSTRAINT `{$prefix}lease_documents_ibfk_1` FOREIGN KEY (`lease_id`) REFERENCES `{$prefix}leases` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Rent invoices
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}rent_invoices` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `invoice_number` varchar(50) NOT NULL UNIQUE,
            `lease_id` int(11) NOT NULL,
            `tenant_id` int(11) NOT NULL,
            `space_id` int(11) NOT NULL,
            `period_start` date NOT NULL,
            `period_end` date NOT NULL,
            `rent_amount` decimal(15,2) NOT NULL,
            `service_charge` decimal(15,2) DEFAULT 0.00,
            `utility_charge` decimal(15,2) DEFAULT 0.00,
            `other_charges` text DEFAULT NULL COMMENT 'JSON: array of additional charges',
            `total_amount` decimal(15,2) NOT NULL,
            `due_date` date NOT NULL,
            `status` enum('draft','sent','paid','partial','overdue','cancelled') DEFAULT 'draft',
            `paid_amount` decimal(15,2) DEFAULT 0.00,
            `balance_amount` decimal(15,2) DEFAULT 0.00,
            `invoice_date` date NOT NULL,
            `sent_date` datetime DEFAULT NULL,
            `paid_date` datetime DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `invoice_number` (`invoice_number`),
            KEY `lease_id` (`lease_id`),
            KEY `tenant_id` (`tenant_id`),
            KEY `space_id` (`space_id`),
            KEY `status` (`status`),
            KEY `due_date` (`due_date`),
            CONSTRAINT `{$prefix}rent_invoices_ibfk_1` FOREIGN KEY (`lease_id`) REFERENCES `{$prefix}leases` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `{$prefix}rent_invoices_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `{$prefix}tenants` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `{$prefix}rent_invoices_ibfk_3` FOREIGN KEY (`space_id`) REFERENCES `{$prefix}spaces` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Rent payments
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}rent_payments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `payment_number` varchar(50) NOT NULL UNIQUE,
            `invoice_id` int(11) DEFAULT NULL COMMENT 'NULL for manual/adjustment payments',
            `lease_id` int(11) NOT NULL,
            `tenant_id` int(11) NOT NULL,
            `amount` decimal(15,2) NOT NULL,
            `payment_date` date NOT NULL,
            `payment_method` enum('cash','bank_transfer','cheque','online','other') DEFAULT 'bank_transfer',
            `reference_number` varchar(100) DEFAULT NULL,
            `receipt_number` varchar(50) DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `payment_number` (`payment_number`),
            KEY `invoice_id` (`invoice_id`),
            KEY `lease_id` (`lease_id`),
            KEY `tenant_id` (`tenant_id`),
            KEY `payment_date` (`payment_date`),
            CONSTRAINT `{$prefix}rent_payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `{$prefix}rent_invoices` (`id`) ON DELETE SET NULL,
            CONSTRAINT `{$prefix}rent_payments_ibfk_2` FOREIGN KEY (`lease_id`) REFERENCES `{$prefix}leases` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `{$prefix}rent_payments_ibfk_3` FOREIGN KEY (`tenant_id`) REFERENCES `{$prefix}tenants` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Maintenance requests
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}maintenance_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `request_number` varchar(50) NOT NULL UNIQUE,
            `space_id` int(11) NOT NULL,
            `tenant_id` int(11) DEFAULT NULL COMMENT 'NULL if reported by property manager',
            `category` enum('plumbing','electrical','hvac','structural','appliance','cleaning','security','other') NOT NULL,
            `priority` enum('low','medium','high','emergency') DEFAULT 'medium',
            `title` varchar(255) NOT NULL,
            `description` text NOT NULL,
            `photos` text DEFAULT NULL COMMENT 'JSON: array of photo URLs',
            `reported_by` int(11) DEFAULT NULL COMMENT 'user_id or tenant_id',
            `reported_date` datetime NOT NULL,
            `status` enum('open','assigned','in_progress','completed','closed','cancelled') DEFAULT 'open',
            `assigned_to` int(11) DEFAULT NULL COMMENT 'user_id or vendor_id',
            `vendor_id` int(11) DEFAULT NULL,
            `completion_date` datetime DEFAULT NULL,
            `tenant_satisfaction_rating` int(1) DEFAULT NULL COMMENT '1-5',
            `notes` text DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `request_number` (`request_number`),
            KEY `space_id` (`space_id`),
            KEY `tenant_id` (`tenant_id`),
            KEY `status` (`status`),
            KEY `priority` (`priority`),
            KEY `reported_date` (`reported_date`),
            CONSTRAINT `{$prefix}maintenance_requests_ibfk_1` FOREIGN KEY (`space_id`) REFERENCES `{$prefix}spaces` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `{$prefix}maintenance_requests_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `{$prefix}tenants` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Work orders
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}work_orders` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `work_order_number` varchar(50) NOT NULL UNIQUE,
            `request_id` int(11) DEFAULT NULL COMMENT 'NULL if created directly',
            `space_id` int(11) NOT NULL,
            `assigned_to` int(11) DEFAULT NULL COMMENT 'user_id for internal staff',
            `vendor_id` int(11) DEFAULT NULL COMMENT 'external vendor',
            `title` varchar(255) NOT NULL,
            `description` text NOT NULL,
            `scheduled_date` datetime DEFAULT NULL,
            `estimated_cost` decimal(15,2) DEFAULT 0.00,
            `actual_cost` decimal(15,2) DEFAULT 0.00,
            `parts_used` text DEFAULT NULL COMMENT 'JSON: array of parts with costs',
            `status` enum('open','assigned','in_progress','completed','closed','cancelled') DEFAULT 'open',
            `completion_notes` text DEFAULT NULL,
            `completion_photos` text DEFAULT NULL COMMENT 'JSON: before/after photos',
            `completed_at` datetime DEFAULT NULL,
            `tenant_signoff` tinyint(1) DEFAULT 0,
            `tenant_signoff_date` datetime DEFAULT NULL,
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `work_order_number` (`work_order_number`),
            KEY `request_id` (`request_id`),
            KEY `space_id` (`space_id`),
            KEY `assigned_to` (`assigned_to`),
            KEY `vendor_id` (`vendor_id`),
            KEY `status` (`status`),
            CONSTRAINT `{$prefix}work_orders_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `{$prefix}maintenance_requests` (`id`) ON DELETE SET NULL,
            CONSTRAINT `{$prefix}work_orders_ibfk_2` FOREIGN KEY (`space_id`) REFERENCES `{$prefix}spaces` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Preventive maintenance schedules
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}preventive_maintenance_schedules` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `space_id` int(11) NOT NULL,
            `asset_type` varchar(100) NOT NULL COMMENT 'HVAC,Generator,Fire Equipment,etc.',
            `maintenance_type` varchar(100) NOT NULL,
            `frequency` enum('daily','weekly','monthly','quarterly','semi_annual','annual') NOT NULL,
            `last_done_date` date DEFAULT NULL,
            `next_due_date` date NOT NULL,
            `assigned_vendor_id` int(11) DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `space_id` (`space_id`),
            KEY `next_due_date` (`next_due_date`),
            KEY `is_active` (`is_active`),
            CONSTRAINT `{$prefix}preventive_maintenance_schedules_ibfk_1` FOREIGN KEY (`space_id`) REFERENCES `{$prefix}spaces` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Vendors/Contractors
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}property_vendors` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `vendor_code` varchar(50) NOT NULL UNIQUE,
            `name` varchar(255) NOT NULL,
            `category` enum('plumber','electrician','hvac','painter','cleaner','security','landscaping','waste_management','other') NOT NULL,
            `contact_person` varchar(255) DEFAULT NULL,
            `phone` varchar(50) NOT NULL,
            `email` varchar(255) DEFAULT NULL,
            `address` text DEFAULT NULL,
            `services` text DEFAULT NULL COMMENT 'JSON: array of services',
            `rate_card` text DEFAULT NULL COMMENT 'JSON: service rates',
            `response_time` varchar(50) DEFAULT NULL COMMENT 'e.g., 24 hours',
            `insurance_details` text DEFAULT NULL,
            `license_certification` varchar(255) DEFAULT NULL,
            `is_preferred` tinyint(1) DEFAULT 0,
            `rating` decimal(3,2) DEFAULT NULL COMMENT '1.00 to 5.00',
            `total_jobs` int(11) DEFAULT 0,
            `avg_completion_time` int(11) DEFAULT NULL COMMENT 'hours',
            `status` enum('active','inactive') DEFAULT 'active',
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `vendor_code` (`vendor_code`),
            KEY `category` (`category`),
            KEY `is_preferred` (`is_preferred`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Vendor invoices
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}property_vendor_invoices` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `invoice_number` varchar(50) NOT NULL UNIQUE,
            `vendor_id` int(11) NOT NULL,
            `work_order_id` int(11) DEFAULT NULL,
            `space_id` int(11) DEFAULT NULL,
            `invoice_date` date NOT NULL,
            `amount` decimal(15,2) NOT NULL,
            `description` text DEFAULT NULL,
            `file_url` varchar(500) DEFAULT NULL,
            `status` enum('pending','approved','paid','cancelled') DEFAULT 'pending',
            `approved_by` int(11) DEFAULT NULL,
            `approved_date` datetime DEFAULT NULL,
            `paid_date` datetime DEFAULT NULL,
            `payment_reference` varchar(100) DEFAULT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `invoice_number` (`invoice_number`),
            KEY `vendor_id` (`vendor_id`),
            KEY `work_order_id` (`work_order_id`),
            KEY `space_id` (`space_id`),
            KEY `status` (`status`),
            CONSTRAINT `{$prefix}property_vendor_invoices_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `{$prefix}property_vendors` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `{$prefix}property_vendor_invoices_ibfk_2` FOREIGN KEY (`work_order_id`) REFERENCES `{$prefix}work_orders` (`id`) ON DELETE SET NULL,
            CONSTRAINT `{$prefix}property_vendor_invoices_ibfk_3` FOREIGN KEY (`space_id`) REFERENCES `{$prefix}spaces` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Inspections
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}inspections` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `inspection_number` varchar(50) NOT NULL UNIQUE,
            `space_id` int(11) NOT NULL,
            `lease_id` int(11) DEFAULT NULL COMMENT 'For move-in/move-out inspections',
            `inspection_type` enum('move_in','move_out','routine','maintenance','safety','compliance') NOT NULL,
            `inspection_date` date NOT NULL,
            `inspector_id` int(11) NOT NULL COMMENT 'user_id',
            `checklist` text DEFAULT NULL COMMENT 'JSON: inspection checklist',
            `findings` text DEFAULT NULL,
            `condition_rating` enum('excellent','good','fair','poor') DEFAULT NULL,
            `photos` text DEFAULT NULL COMMENT 'JSON: array of photo URLs',
            `inspector_signature` text DEFAULT NULL,
            `tenant_signature` text DEFAULT NULL,
            `next_inspection_date` date DEFAULT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `inspection_number` (`inspection_number`),
            KEY `space_id` (`space_id`),
            KEY `lease_id` (`lease_id`),
            KEY `inspection_type` (`inspection_type`),
            KEY `inspection_date` (`inspection_date`),
            CONSTRAINT `{$prefix}inspections_ibfk_1` FOREIGN KEY (`space_id`) REFERENCES `{$prefix}spaces` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `{$prefix}inspections_ibfk_2` FOREIGN KEY (`lease_id`) REFERENCES `{$prefix}leases` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Insurance policies
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}insurance_policies` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `policy_number` varchar(100) NOT NULL,
            `property_id` int(11) NOT NULL,
            `insurer` varchar(255) NOT NULL,
            `coverage_type` varchar(100) NOT NULL COMMENT 'Property, Liability, etc.',
            `coverage_amount` decimal(15,2) NOT NULL,
            `premium_amount` decimal(15,2) NOT NULL,
            `premium_frequency` enum('monthly','quarterly','semi_annual','annual') DEFAULT 'annual',
            `start_date` date NOT NULL,
            `expiry_date` date NOT NULL,
            `deductible` decimal(15,2) DEFAULT NULL,
            `document_url` varchar(500) DEFAULT NULL,
            `renewal_reminder_sent` tinyint(1) DEFAULT 0,
            `status` enum('active','expired','cancelled') DEFAULT 'active',
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `property_id` (`property_id`),
            KEY `expiry_date` (`expiry_date`),
            KEY `status` (`status`),
            CONSTRAINT `{$prefix}insurance_policies_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `{$prefix}properties` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Insurance claims
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}insurance_claims` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `claim_number` varchar(50) NOT NULL UNIQUE,
            `policy_id` int(11) NOT NULL,
            `property_id` int(11) NOT NULL,
            `space_id` int(11) DEFAULT NULL,
            `claim_date` date NOT NULL,
            `incident_date` date NOT NULL,
            `description` text NOT NULL,
            `claim_amount` decimal(15,2) NOT NULL,
            `photos` text DEFAULT NULL COMMENT 'JSON: array of photo URLs',
            `status` enum('filed','under_review','approved','rejected','settled') DEFAULT 'filed',
            `settlement_amount` decimal(15,2) DEFAULT NULL,
            `settlement_date` date DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `claim_number` (`claim_number`),
            KEY `policy_id` (`policy_id`),
            KEY `property_id` (`property_id`),
            KEY `space_id` (`space_id`),
            KEY `status` (`status`),
            CONSTRAINT `{$prefix}insurance_claims_ibfk_1` FOREIGN KEY (`policy_id`) REFERENCES `{$prefix}insurance_policies` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `{$prefix}insurance_claims_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `{$prefix}properties` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `{$prefix}insurance_claims_ibfk_3` FOREIGN KEY (`space_id`) REFERENCES `{$prefix}spaces` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Occupancy log
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}occupancy_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `space_id` int(11) NOT NULL,
            `date` date NOT NULL,
            `status` enum('occupied','vacant','under_maintenance') NOT NULL,
            `lease_id` int(11) DEFAULT NULL COMMENT 'If occupied, link to lease',
            `tenant_id` int(11) DEFAULT NULL,
            `notes` text DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `space_id` (`space_id`),
            KEY `date` (`date`),
            KEY `status` (`status`),
            KEY `lease_id` (`lease_id`),
            UNIQUE KEY `space_date` (`space_id`, `date`),
            CONSTRAINT `{$prefix}occupancy_log_ibfk_1` FOREIGN KEY (`space_id`) REFERENCES `{$prefix}spaces` (`id`) ON DELETE CASCADE,
            CONSTRAINT `{$prefix}occupancy_log_ibfk_2` FOREIGN KEY (`lease_id`) REFERENCES `{$prefix}leases` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Property expenses
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}property_expenses` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `expense_number` varchar(50) NOT NULL UNIQUE,
            `property_id` int(11) NOT NULL,
            `space_id` int(11) DEFAULT NULL COMMENT 'NULL if property-wide expense',
            `category` enum('maintenance','utilities','tax','insurance','security','cleaning','administrative','other') NOT NULL,
            `amount` decimal(15,2) NOT NULL,
            `vendor_id` int(11) DEFAULT NULL,
            `expense_date` date NOT NULL,
            `description` text DEFAULT NULL,
            `invoice_url` varchar(500) DEFAULT NULL,
            `reference_number` varchar(100) DEFAULT NULL,
            `account_id` int(11) DEFAULT NULL COMMENT 'Link to accounting account',
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `expense_number` (`expense_number`),
            KEY `property_id` (`property_id`),
            KEY `space_id` (`space_id`),
            KEY `category` (`category`),
            KEY `vendor_id` (`vendor_id`),
            KEY `expense_date` (`expense_date`),
            CONSTRAINT `{$prefix}property_expenses_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `{$prefix}properties` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `{$prefix}property_expenses_ibfk_2` FOREIGN KEY (`space_id`) REFERENCES `{$prefix}spaces` (`id`) ON DELETE SET NULL,
            CONSTRAINT `{$prefix}property_expenses_ibfk_3` FOREIGN KEY (`vendor_id`) REFERENCES `{$prefix}property_vendors` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Lease renewal notices
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}lease_renewal_notices` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `lease_id` int(11) NOT NULL,
            `notice_date` date NOT NULL,
            `notice_type` enum('90_days','60_days','30_days','final') NOT NULL,
            `proposed_terms` text DEFAULT NULL COMMENT 'JSON: new rent, new end_date, etc.',
            `tenant_response` enum('pending','accept','decline','negotiate') DEFAULT 'pending',
            `response_date` date DEFAULT NULL,
            `negotiation_notes` text DEFAULT NULL,
            `sent_date` datetime DEFAULT NULL,
            `sent_via` enum('email','sms','letter','in_person') DEFAULT 'email',
            PRIMARY KEY (`id`),
            KEY `lease_id` (`lease_id`),
            KEY `notice_date` (`notice_date`),
            KEY `tenant_response` (`tenant_response`),
            CONSTRAINT `{$prefix}lease_renewal_notices_ibfk_1` FOREIGN KEY (`lease_id`) REFERENCES `{$prefix}leases` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Space Bookings table (for time-slot based bookings)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}space_bookings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `booking_number` varchar(50) NOT NULL UNIQUE,
            `space_id` int(11) NOT NULL,
            `tenant_id` int(11) NOT NULL,
            `booking_date` date NOT NULL,
            `start_time` time NOT NULL,
            `end_time` time NOT NULL,
            `duration_hours` decimal(10,2) DEFAULT 0.00,
            `number_of_guests` int(11) DEFAULT 0,
            `booking_type` enum('hourly','daily','multi_day') DEFAULT 'hourly',
            `base_amount` decimal(15,2) DEFAULT 0.00,
            `discount_amount` decimal(15,2) DEFAULT 0.00,
            `tax_amount` decimal(15,2) DEFAULT 0.00,
            `total_amount` decimal(15,2) DEFAULT 0.00,
            `paid_amount` decimal(15,2) DEFAULT 0.00,
            `balance_amount` decimal(15,2) DEFAULT 0.00,
            `currency` varchar(3) DEFAULT 'NGN',
            `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
            `payment_status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
            `booking_notes` text DEFAULT NULL,
            `special_requests` text DEFAULT NULL,
            `cancellation_reason` text DEFAULT NULL,
            `confirmed_at` datetime DEFAULT NULL,
            `cancelled_at` datetime DEFAULT NULL,
            `completed_at` datetime DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `booking_number` (`booking_number`),
            KEY `space_id` (`space_id`),
            KEY `tenant_id` (`tenant_id`),
            KEY `booking_date` (`booking_date`),
            KEY `status` (`status`),
            KEY `idx_space_date_time` (`space_id`, `booking_date`, `start_time`, `end_time`),
            CONSTRAINT `{$prefix}space_bookings_ibfk_1` FOREIGN KEY (`space_id`) 
                REFERENCES `{$prefix}spaces` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `{$prefix}space_bookings_ibfk_2` FOREIGN KEY (`tenant_id`) 
                REFERENCES `{$prefix}tenants` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Property management tables created successfully.\n";
        return true;
    } catch (PDOException $e) {
        error_log("Property management migration error: " . $e->getMessage());
        throw $e;
    }
}

