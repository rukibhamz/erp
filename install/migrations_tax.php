<?php
/**
 * Database Migration Script for Tax Management Module (Nigerian Tax System)
 */

function runTaxMigrations($pdo, $prefix = 'erp_') {
    try {
        // Tax Types Configuration
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tax_types` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `code` VARCHAR(20) NOT NULL UNIQUE,
            `rate` DECIMAL(10,4) DEFAULT 0,
            `calculation_method` ENUM('percentage', 'fixed', 'progressive') DEFAULT 'percentage',
            `authority` ENUM('FIRS', 'State', 'Local', 'Multiple') DEFAULT 'FIRS',
            `filing_frequency` ENUM('monthly', 'quarterly', 'annually', 'none') DEFAULT 'monthly',
            `is_active` TINYINT(1) DEFAULT 1,
            `description` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tax Settings (Company Tax Profile)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tax_settings` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `company_tin` VARCHAR(20) DEFAULT NULL UNIQUE,
            `company_registration_number` VARCHAR(50) DEFAULT NULL,
            `tax_office` VARCHAR(255) DEFAULT NULL,
            `tax_office_code` VARCHAR(20) DEFAULT NULL,
            `industry_sector_code` VARCHAR(50) DEFAULT NULL,
            `accounting_year_end_month` INT(11) DEFAULT 12,
            `tax_year` YEAR DEFAULT NULL,
            `small_company_relief` TINYINT(1) DEFAULT 0,
            `pioneer_status` TINYINT(1) DEFAULT 0,
            `pioneer_expiry_date` DATE DEFAULT NULL,
            `vat_registration_number` VARCHAR(50) DEFAULT NULL,
            `vat_registration_date` DATE DEFAULT NULL,
            `vat_scheme` ENUM('standard', 'cash_accounting', 'flat_rate') DEFAULT 'standard',
            `vat_threshold` DECIMAL(15,2) DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // VAT Transactions
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}vat_transactions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `transaction_type` ENUM('sale', 'purchase', 'import', 'export') NOT NULL,
            `transaction_id` INT(11) DEFAULT NULL,
            `transaction_reference` VARCHAR(100) DEFAULT NULL,
            `customer_id` INT(11) DEFAULT NULL,
            `vendor_id` INT(11) DEFAULT NULL,
            `vat_amount` DECIMAL(15,2) DEFAULT 0,
            `vat_rate` DECIMAL(10,4) DEFAULT 7.5,
            `gross_amount` DECIMAL(15,2) DEFAULT 0,
            `net_amount` DECIMAL(15,2) DEFAULT 0,
            `vat_type` ENUM('standard', 'zero_rated', 'exempt', 'non_qualified') DEFAULT 'standard',
            `vat_return_id` INT(11) DEFAULT NULL,
            `date` DATE NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `transaction_type` (`transaction_type`, `transaction_id`),
            KEY `date` (`date`),
            KEY `vat_return_id` (`vat_return_id`),
            KEY `customer_id` (`customer_id`),
            KEY `vendor_id` (`vendor_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // VAT Returns
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}vat_returns` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `return_number` VARCHAR(50) DEFAULT NULL UNIQUE,
            `period_start` DATE NOT NULL,
            `period_end` DATE NOT NULL,
            `output_vat` DECIMAL(15,2) DEFAULT 0,
            `input_vat` DECIMAL(15,2) DEFAULT 0,
            `net_vat` DECIMAL(15,2) DEFAULT 0,
            `vat_payable` DECIMAL(15,2) DEFAULT 0,
            `vat_refundable` DECIMAL(15,2) DEFAULT 0,
            `filed_date` DATE DEFAULT NULL,
            `filing_deadline` DATE NOT NULL,
            `payment_date` DATE DEFAULT NULL,
            `payment_deadline` DATE NOT NULL,
            `payment_amount` DECIMAL(15,2) DEFAULT 0,
            `payment_reference` VARCHAR(100) DEFAULT NULL,
            `status` ENUM('draft', 'filed', 'paid', 'overdue', 'under_review') DEFAULT 'draft',
            `filed_by` INT(11) DEFAULT NULL,
            `confirmation_number` VARCHAR(100) DEFAULT NULL,
            `penalty_amount` DECIMAL(15,2) DEFAULT 0,
            `interest_amount` DECIMAL(15,2) DEFAULT 0,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `period_start` (`period_start`, `period_end`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // WHT Transactions
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}wht_transactions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `payment_id` INT(11) DEFAULT NULL,
            `wht_type` ENUM('dividends', 'interest', 'rent', 'royalties', 'professional_fees', 'directors_fees', 'consultancy', 'construction', 'commission', 'technical_services', 'other') NOT NULL,
            `wht_rate` DECIMAL(10,4) DEFAULT 10.0,
            `gross_amount` DECIMAL(15,2) DEFAULT 0,
            `wht_amount` DECIMAL(15,2) DEFAULT 0,
            `net_amount` DECIMAL(15,2) DEFAULT 0,
            `date` DATE NOT NULL,
            `beneficiary_id` INT(11) DEFAULT NULL,
            `beneficiary_name` VARCHAR(255) DEFAULT NULL,
            `beneficiary_tin` VARCHAR(20) DEFAULT NULL,
            `wht_return_id` INT(11) DEFAULT NULL,
            `certificate_issued` TINYINT(1) DEFAULT 0,
            `certificate_id` INT(11) DEFAULT NULL,
            `transaction_reference` VARCHAR(100) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `date` (`date`),
            KEY `wht_return_id` (`wht_return_id`),
            KEY `beneficiary_id` (`beneficiary_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // WHT Returns
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}wht_returns` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `return_number` VARCHAR(50) DEFAULT NULL UNIQUE,
            `month` INT(11) NOT NULL,
            `year` INT(11) NOT NULL,
            `total_wht` DECIMAL(15,2) DEFAULT 0,
            `schedule_json` JSON DEFAULT NULL,
            `filed_date` DATE DEFAULT NULL,
            `filing_deadline` DATE NOT NULL,
            `payment_date` DATE DEFAULT NULL,
            `payment_deadline` DATE NOT NULL,
            `payment_amount` DECIMAL(15,2) DEFAULT 0,
            `payment_reference` VARCHAR(100) DEFAULT NULL,
            `status` ENUM('draft', 'filed', 'paid', 'overdue') DEFAULT 'draft',
            `filed_by` INT(11) DEFAULT NULL,
            `confirmation_number` VARCHAR(100) DEFAULT NULL,
            `penalty_amount` DECIMAL(15,2) DEFAULT 0,
            `interest_amount` DECIMAL(15,2) DEFAULT 0,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `month_year` (`month`, `year`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // WHT Certificates
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}wht_certificates` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `certificate_number` VARCHAR(50) NOT NULL UNIQUE,
            `beneficiary_id` INT(11) DEFAULT NULL,
            `beneficiary_name` VARCHAR(255) NOT NULL,
            `beneficiary_tin` VARCHAR(20) DEFAULT NULL,
            `period_start` DATE NOT NULL,
            `period_end` DATE NOT NULL,
            `wht_amount` DECIMAL(15,2) DEFAULT 0,
            `issue_date` DATE NOT NULL,
            `issued_by` INT(11) DEFAULT NULL,
            `certificate_url` VARCHAR(255) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `certificate_number` (`certificate_number`),
            KEY `beneficiary_id` (`beneficiary_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // CIT Calculations
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}cit_calculations` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `year` INT(11) NOT NULL,
            `profit_before_tax` DECIMAL(15,2) DEFAULT 0,
            `adjustments_json` JSON DEFAULT NULL,
            `total_adjustments` DECIMAL(15,2) DEFAULT 0,
            `assessable_profit` DECIMAL(15,2) DEFAULT 0,
            `cit_rate` DECIMAL(10,4) DEFAULT 30.0,
            `cit_amount` DECIMAL(15,2) DEFAULT 0,
            `capital_allowances_total` DECIMAL(15,2) DEFAULT 0,
            `tax_reliefs_total` DECIMAL(15,2) DEFAULT 0,
            `minimum_tax` DECIMAL(15,2) DEFAULT 0,
            `turnover_for_min_tax` DECIMAL(15,2) DEFAULT 0,
            `final_tax_liability` DECIMAL(15,2) DEFAULT 0,
            `status` ENUM('draft', 'calculated', 'filed', 'assessed') DEFAULT 'draft',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `year` (`year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Capital Allowances
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}capital_allowances` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `asset_id` INT(11) DEFAULT NULL,
            `asset_type` VARCHAR(100) DEFAULT NULL,
            `asset_description` VARCHAR(255) DEFAULT NULL,
            `cost` DECIMAL(15,2) DEFAULT 0,
            `annual_rate` DECIMAL(10,4) DEFAULT 0,
            `initial_rate` DECIMAL(10,4) DEFAULT 0,
            `allowance_amount` DECIMAL(15,2) DEFAULT 0,
            `year` INT(11) NOT NULL,
            `cit_calculation_id` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `year` (`year`),
            KEY `cit_calculation_id` (`cit_calculation_id`),
            KEY `asset_id` (`asset_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tax Reliefs
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tax_reliefs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `relief_type` ENUM('pioneer_status', 'investment_tax_credit', 'rural_investment', 'rd_allowance', 'export_incentive', 'other') NOT NULL,
            `amount` DECIMAL(15,2) DEFAULT 0,
            `year` INT(11) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `supporting_documents` JSON DEFAULT NULL,
            `approved` TINYINT(1) DEFAULT 0,
            `approved_date` DATE DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `year` (`year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // PAYE Deductions
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}paye_deductions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `employee_id` INT(11) NOT NULL,
            `period` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM format',
            `gross_income` DECIMAL(15,2) DEFAULT 0,
            `pension_contribution` DECIMAL(15,2) DEFAULT 0,
            `nhf_contribution` DECIMAL(15,2) DEFAULT 0,
            `consolidated_relief` DECIMAL(15,2) DEFAULT 0,
            `taxable_income` DECIMAL(15,2) DEFAULT 0,
            `tax_calculated` DECIMAL(15,2) DEFAULT 0,
            `minimum_tax` DECIMAL(15,2) DEFAULT 0,
            `paye_amount` DECIMAL(15,2) DEFAULT 0,
            `tax_bands_json` JSON DEFAULT NULL,
            `paye_return_id` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `employee_period` (`employee_id`, `period`),
            KEY `period` (`period`),
            KEY `paye_return_id` (`paye_return_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // PAYE Returns
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}paye_returns` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `return_number` VARCHAR(50) DEFAULT NULL UNIQUE,
            `period` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM format',
            `total_paye` DECIMAL(15,2) DEFAULT 0,
            `employee_count` INT(11) DEFAULT 0,
            `schedule_json` JSON DEFAULT NULL,
            `filed_date` DATE DEFAULT NULL,
            `filing_deadline` DATE NOT NULL,
            `payment_date` DATE DEFAULT NULL,
            `payment_deadline` DATE NOT NULL,
            `payment_amount` DECIMAL(15,2) DEFAULT 0,
            `payment_reference` VARCHAR(100) DEFAULT NULL,
            `status` ENUM('draft', 'filed', 'paid', 'overdue') DEFAULT 'draft',
            `filed_by` INT(11) DEFAULT NULL,
            `confirmation_number` VARCHAR(100) DEFAULT NULL,
            `penalty_amount` DECIMAL(15,2) DEFAULT 0,
            `interest_amount` DECIMAL(15,2) DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `period` (`period`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tax Payments
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tax_payments` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `tax_type` VARCHAR(50) NOT NULL,
            `amount` DECIMAL(15,2) DEFAULT 0,
            `payment_date` DATE NOT NULL,
            `payment_method` ENUM('bank_transfer', 'cash', 'cheque', 'online', 'other') DEFAULT 'bank_transfer',
            `reference` VARCHAR(100) DEFAULT NULL,
            `receipt_url` VARCHAR(255) DEFAULT NULL,
            `period_covered` VARCHAR(20) DEFAULT NULL,
            `tax_return_id` INT(11) DEFAULT NULL,
            `bank_name` VARCHAR(255) DEFAULT NULL,
            `account_number` VARCHAR(50) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `tax_type` (`tax_type`),
            KEY `payment_date` (`payment_date`),
            KEY `tax_return_id` (`tax_return_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tax Filings
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tax_filings` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `tax_type` VARCHAR(50) NOT NULL,
            `filing_date` DATE DEFAULT NULL,
            `period_covered` VARCHAR(20) NOT NULL,
            `amount` DECIMAL(15,2) DEFAULT 0,
            `status` ENUM('draft', 'filed', 'acknowledged', 'under_review', 'accepted', 'rejected') DEFAULT 'draft',
            `confirmation_number` VARCHAR(100) DEFAULT NULL,
            `filed_by` INT(11) DEFAULT NULL,
            `filing_method` ENUM('manual', 'e_file', 'online_portal') DEFAULT 'manual',
            `return_file_url` VARCHAR(255) DEFAULT NULL,
            `acknowledgment_url` VARCHAR(255) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `tax_type` (`tax_type`, `period_covered`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tax Assessments
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tax_assessments` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `tax_type` VARCHAR(50) NOT NULL,
            `assessment_date` DATE NOT NULL,
            `period_covered` VARCHAR(20) NOT NULL,
            `assessed_amount` DECIMAL(15,2) DEFAULT 0,
            `self_assessed_amount` DECIMAL(15,2) DEFAULT 0,
            `variance` DECIMAL(15,2) DEFAULT 0,
            `objection_filed` TINYINT(1) DEFAULT 0,
            `objection_date` DATE DEFAULT NULL,
            `objection_amount` DECIMAL(15,2) DEFAULT 0,
            `resolution` ENUM('pending', 'accepted', 'rejected', 'partially_accepted', 'withdrawn') DEFAULT 'pending',
            `resolution_date` DATE DEFAULT NULL,
            `final_amount` DECIMAL(15,2) DEFAULT 0,
            `document_url` VARCHAR(255) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `tax_type` (`tax_type`),
            KEY `assessment_date` (`assessment_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tax Deadlines
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tax_deadlines` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `tax_type` VARCHAR(50) NOT NULL,
            `deadline_date` DATE NOT NULL,
            `deadline_type` ENUM('filing', 'payment') NOT NULL,
            `period_covered` VARCHAR(20) DEFAULT NULL,
            `reminder_sent` TINYINT(1) DEFAULT 0,
            `reminder_date` DATE DEFAULT NULL,
            `status` ENUM('upcoming', 'due_today', 'overdue', 'completed') DEFAULT 'upcoming',
            `completed` TINYINT(1) DEFAULT 0,
            `completed_date` DATE DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `deadline_date` (`deadline_date`),
            KEY `status` (`status`),
            KEY `tax_type` (`tax_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tax Documents
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tax_documents` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `document_type` ENUM('return', 'receipt', 'assessment', 'correspondence', 'certificate', 'other') NOT NULL,
            `tax_type` VARCHAR(50) DEFAULT NULL,
            `file_url` VARCHAR(255) NOT NULL,
            `file_name` VARCHAR(255) DEFAULT NULL,
            `file_size` INT(11) DEFAULT NULL,
            `upload_date` DATE NOT NULL,
            `period` VARCHAR(20) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `uploaded_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `document_type` (`document_type`),
            KEY `tax_type` (`tax_type`),
            KEY `period` (`period`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tax Provisions
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tax_provisions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `period` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM format',
            `tax_type` VARCHAR(50) NOT NULL,
            `provision_amount` DECIMAL(15,2) DEFAULT 0,
            `payment_to_date` DECIMAL(15,2) DEFAULT 0,
            `balance` DECIMAL(15,2) DEFAULT 0,
            `adjustment_amount` DECIMAL(15,2) DEFAULT 0,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `period_tax_type` (`period`, `tax_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Deferred Tax
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}deferred_tax` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `period` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM format',
            `temporary_difference_type` ENUM('depreciation', 'provision', 'revenue_recognition', 'other') NOT NULL,
            `book_value` DECIMAL(15,2) DEFAULT 0,
            `tax_value` DECIMAL(15,2) DEFAULT 0,
            `difference` DECIMAL(15,2) DEFAULT 0,
            `tax_rate` DECIMAL(10,4) DEFAULT 30.0,
            `deferred_tax_amount` DECIMAL(15,2) DEFAULT 0,
            `is_asset` TINYINT(1) DEFAULT 0,
            `description` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `period` (`period`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tax Clearance Certificates
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tax_clearance_certificates` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `certificate_number` VARCHAR(50) DEFAULT NULL UNIQUE,
            `application_date` DATE NOT NULL,
            `issue_date` DATE DEFAULT NULL,
            `expiry_date` DATE DEFAULT NULL,
            `status` ENUM('applied', 'processing', 'issued', 'expired', 'renewed', 'revoked') DEFAULT 'applied',
            `certificate_url` VARCHAR(255) DEFAULT NULL,
            `requirements_checklist_json` JSON DEFAULT NULL,
            `uses_json` JSON DEFAULT NULL,
            `renewal_reminder_sent` TINYINT(1) DEFAULT 0,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `status` (`status`),
            KEY `expiry_date` (`expiry_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tax Correspondences
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tax_correspondences` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `from_authority` VARCHAR(255) NOT NULL,
            `to_company` VARCHAR(255) DEFAULT NULL,
            `date_received` DATE NOT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `content` TEXT DEFAULT NULL,
            `document_url` VARCHAR(255) DEFAULT NULL,
            `response_date` DATE DEFAULT NULL,
            `response_content` TEXT DEFAULT NULL,
            `response_document_url` VARCHAR(255) DEFAULT NULL,
            `status` ENUM('pending', 'responded', 'resolved', 'escalated', 'closed') DEFAULT 'pending',
            `assigned_to` INT(11) DEFAULT NULL,
            `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            `follow_up_date` DATE DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `date_received` (`date_received`),
            KEY `status` (`status`),
            KEY `assigned_to` (`assigned_to`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // EDT Calculations
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}edt_calculations` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `year` INT(11) NOT NULL,
            `assessable_profit` DECIMAL(15,2) DEFAULT 0,
            `edt_rate` DECIMAL(10,4) DEFAULT 2.5,
            `edt_amount` DECIMAL(15,2) DEFAULT 0,
            `filed_date` DATE DEFAULT NULL,
            `payment_date` DATE DEFAULT NULL,
            `status` ENUM('draft', 'calculated', 'filed', 'paid') DEFAULT 'draft',
            `cit_calculation_id` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `year` (`year`),
            KEY `cit_calculation_id` (`cit_calculation_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Local Tax (AMAC/Tenement Rate)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}local_taxes` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `tax_type` ENUM('tenement_rate', 'business_premises', 'signage', 'other') NOT NULL,
            `property_id` INT(11) DEFAULT NULL,
            `property_name` VARCHAR(255) DEFAULT NULL,
            `assessment_year` INT(11) NOT NULL,
            `assessed_value` DECIMAL(15,2) DEFAULT 0,
            `tax_rate` DECIMAL(10,4) DEFAULT 0,
            `tax_amount` DECIMAL(15,2) DEFAULT 0,
            `payment_date` DATE DEFAULT NULL,
            `payment_reference` VARCHAR(100) DEFAULT NULL,
            `status` ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
            `renewal_date` DATE DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `property_id` (`property_id`),
            KEY `assessment_year` (`assessment_year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Tax Management tables created successfully.\n";
    } catch (PDOException $e) {
        error_log("Tax migration error: " . $e->getMessage());
        throw $e;
    }
}

function insertDefaultTaxTypes($pdo, $prefix = 'erp_') {
    $taxTypes = [
        ['name' => 'VAT', 'code' => 'VAT', 'rate' => 7.5, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'monthly', 'description' => 'Value Added Tax at 7.5%'],
        ['name' => 'WHT - Dividends', 'code' => 'WHT_DIV', 'rate' => 10, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'monthly', 'description' => 'Withholding Tax on Dividends at 10%'],
        ['name' => 'WHT - Interest', 'code' => 'WHT_INT', 'rate' => 10, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'monthly', 'description' => 'Withholding Tax on Interest at 10%'],
        ['name' => 'WHT - Rent', 'code' => 'WHT_RENT', 'rate' => 10, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'monthly', 'description' => 'Withholding Tax on Rent at 10%'],
        ['name' => 'WHT - Professional Fees', 'code' => 'WHT_PROF', 'rate' => 10, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'monthly', 'description' => 'Withholding Tax on Professional Fees at 10%'],
        ['name' => 'WHT - Directors Fees', 'code' => 'WHT_DIR', 'rate' => 10, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'monthly', 'description' => 'Withholding Tax on Directors Fees at 10%'],
        ['name' => 'WHT - Consultancy', 'code' => 'WHT_CONS', 'rate' => 5, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'monthly', 'description' => 'Withholding Tax on Consultancy at 5%'],
        ['name' => 'WHT - Construction', 'code' => 'WHT_CONST', 'rate' => 5, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'monthly', 'description' => 'Withholding Tax on Construction at 5%'],
        ['name' => 'WHT - Commission', 'code' => 'WHT_COMM', 'rate' => 5, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'monthly', 'description' => 'Withholding Tax on Commission at 5%'],
        ['name' => 'WHT - Technical Services', 'code' => 'WHT_TECH', 'rate' => 10, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'monthly', 'description' => 'Withholding Tax on Technical Services at 10%'],
        ['name' => 'Company Income Tax', 'code' => 'CIT', 'rate' => 30, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'annually', 'description' => 'Company Income Tax at 30%'],
        ['name' => 'Education Tax', 'code' => 'EDT', 'rate' => 2.5, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'annually', 'description' => 'Education Tax at 2.5%'],
        ['name' => 'PAYE', 'code' => 'PAYE', 'rate' => 0, 'calculation_method' => 'progressive', 'authority' => 'FIRS', 'filing_frequency' => 'monthly', 'description' => 'Pay As You Earn - Progressive rates'],
        ['name' => 'Capital Gains Tax', 'code' => 'CGT', 'rate' => 10, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'none', 'description' => 'Capital Gains Tax at 10%'],
        ['name' => 'Stamp Duties', 'code' => 'STAMP', 'rate' => 0, 'calculation_method' => 'fixed', 'authority' => 'State', 'filing_frequency' => 'none', 'description' => 'Stamp Duties'],
        ['name' => 'NITDA Levy', 'code' => 'NITDA', 'rate' => 1, 'calculation_method' => 'percentage', 'authority' => 'FIRS', 'filing_frequency' => 'annually', 'description' => 'NITDA Levy at 1% of PBT for companies with turnover > 100M'],
    ];
    
    foreach ($taxTypes as $tax) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}tax_types` (name, code, rate, calculation_method, authority, filing_frequency, description, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
            $stmt->execute([
                $tax['name'],
                $tax['code'],
                $tax['rate'],
                $tax['calculation_method'],
                $tax['authority'],
                $tax['filing_frequency'],
                $tax['description']
            ]);
        } catch (PDOException $e) {
            // Ignore duplicate errors
        }
    }
}

