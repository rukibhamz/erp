-- Create payroll_runs table
CREATE TABLE IF NOT EXISTS `payroll_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period` varchar(7) NOT NULL COMMENT 'YYYY-MM format',
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `processed_date` date DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `period` (`period`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create payslips table
CREATE TABLE IF NOT EXISTS `payslips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_run_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `period` varchar(7) NOT NULL,
  `basic_salary` decimal(15,2) DEFAULT 0.00,
  `gross_pay` decimal(15,2) DEFAULT 0.00,
  `total_deductions` decimal(15,2) DEFAULT 0.00,
  `net_pay` decimal(15,2) DEFAULT 0.00,
  `earnings_json` text DEFAULT NULL,
  `deductions_json` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `payroll_run_id` (`payroll_run_id`),
  KEY `employee_id` (`employee_id`),
  KEY `period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update cash account to have a balance (if exists)
UPDATE `cash_accounts` SET `current_balance` = 1000000.00, `opening_balance` = 1000000.00 WHERE `id` = 1;
