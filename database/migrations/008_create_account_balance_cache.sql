-- Create account_balance_cache table for performance optimization
CREATE TABLE IF NOT EXISTS `account_balance_cache` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `account_id` INT(11) NOT NULL,
  `balance` DECIMAL(15,2) DEFAULT 0.00,
  `as_of_date` DATE NOT NULL,
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_date` (`account_id`, `as_of_date`),
  KEY `account_id` (`account_id`),
  FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create index for faster lookups
CREATE INDEX `idx_as_of_date` ON `account_balance_cache` (`as_of_date`);
