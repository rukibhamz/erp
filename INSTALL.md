# ERP System - Installation Guide

## Overview

This guide will help you install the ERP system from scratch or upgrade an existing installation.

---

## Prerequisites

### Server Requirements
- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL**: 5.7 or higher (8.0+ recommended)
- **Web Server**: Apache 2.4+ or Nginx
- **PHP Extensions**:
  - PDO
  - pdo_mysql
  - mbstring
  - json
  - openssl
  - curl
  - fileinfo

### Recommended
- **Composer**: For dependency management
- **Git**: For version control
- **SSL Certificate**: For production deployment

---

## Installation Methods

### Method 1: Automatic Installation (Recommended)

The system uses **AutoMigration** to automatically set up the database on first run.

#### Steps:

1. **Upload Files**
   ```bash
   # Clone or upload the ERP files to your web server
   cd /var/www/html
   git clone https://github.com/yourrepo/erp.git
   # OR upload via FTP/SFTP
   ```

2. **Set Permissions**
   ```bash
   chmod -R 755 /var/www/html/erp
   chmod -R 777 /var/www/html/erp/application/logs
   chmod -R 777 /var/www/html/erp/uploads
   ```

3. **Create Database**
   ```sql
   CREATE DATABASE erp_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'erp_user'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT ALL PRIVILEGES ON erp_system.* TO 'erp_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

4. **Configure Database Connection**
   
   Copy `application/config/config.php.example` to `application/config/config.php`:
   ```bash
   cp application/config/config.php.example application/config/config.php
   ```
   
   Edit `application/config/config.php`:
   ```php
   $config['db'] = [
       'hostname' => 'localhost',
       'database' => 'erp_system',
       'username' => 'erp_user',
       'password' => 'strong_password',
       'dbprefix' => 'erp_',
       'charset' => 'utf8mb4'
   ];
   
   $config['installed'] = true;
   ```

5. **Access the Application**
   
   Navigate to: `http://your-domain.com/erp`
   
   **AutoMigration will automatically**:
   - Create all database tables
   - Install default Chart of Accounts
   - Add Phase 12 accounts (Fixed Assets, etc.)
   - Create performance indexes
   - Set up default admin account

6. **Login**
   
   **Username**: `admin`  
   **Password**: `admin123`
   
   ⚠️ **Change this immediately!**

---

### Method 2: Manual Installation

If you prefer manual control or AutoMigration fails:

#### Steps:

1. **Follow Steps 1-4 from Method 1**

2. **Run SQL Migrations Manually**
   
   ```bash
   cd database/migrations
   
   # Run migrations in order
   mysql -u erp_user -p erp_system < 000_complete_system_migration.sql
   ```
   
   Or run individual migrations:
   ```bash
   mysql -u erp_user -p erp_system < 001_create_initial_tables.sql
   mysql -u erp_user -p erp_system < 002_create_accounting_tables.sql
   mysql -u erp_user -p erp_system < 003_create_module_tables.sql
   mysql -u erp_user -p erp_system < 004_standardize_accounts_table.sql
   mysql -u erp_user -p erp_system < 005_create_payroll_tables.sql
   mysql -u erp_user -p erp_system < 006_install_default_coa.sql
   mysql -u erp_user -p erp_system < 007_add_payroll_posted_columns.sql
   mysql -u erp_user -p erp_system < 008_add_phase12_accounts.sql
   mysql -u erp_user -p erp_system < 009_add_performance_indexes.sql
   ```

3. **Verify Installation**
   
   ```sql
   USE erp_system;
   SHOW TABLES;
   SELECT COUNT(*) FROM erp_accounts;
   ```

4. **Access Application** (same as Method 1, step 5-6)

---

## First-Time Configuration

After installation, configure the following:

### 1. Change Admin Password
- Login as admin
- Go to **Profile → Change Password**
- Set a strong password

### 2. Configure Email (SMTP)
- Go to **Settings → System Settings → Email**
- Enter SMTP details (see `docs/EMAIL_CONFIGURATION.md`)
- Test email delivery

### 3. Add Users
- Go to **Settings → Users**
- Create user accounts
- Assign roles and permissions

### 4. Configure Tax Rates
- Go to **Tax → Tax Configuration**
- Set VAT rate (e.g., 7.5%)
- Configure PAYE rates
- Set WHT rates

### 5. Review Chart of Accounts
- Go to **Accounting → Chart of Accounts**
- Verify default accounts
- Add custom accounts as needed

### 6. Set Opening Balances
- Go to **Accounting → Opening Balances**
- Enter beginning balances
- Ensure debits = credits

---

## Post-Installation Checklist

- [ ] Database created and configured
- [ ] All migrations run successfully
- [ ] Admin password changed
- [ ] Email configured and tested
- [ ] Users created with proper permissions
- [ ] Tax rates configured
- [ ] Chart of Accounts reviewed
- [ ] Opening balances entered
- [ ] Backup schedule configured
- [ ] SSL certificate installed (production)

---

## Upgrading from Previous Version

If you're upgrading an existing installation:

### Steps:

1. **Backup Everything**
   ```bash
   # Backup database
   mysqldump -u erp_user -p erp_system > backup_$(date +%Y%m%d).sql
   
   # Backup files
   tar -czf erp_backup_$(date +%Y%m%d).tar.gz /var/www/html/erp
   ```

2. **Upload New Files**
   ```bash
   # Backup old files
   mv /var/www/html/erp /var/www/html/erp_old
   
   # Upload new files
   # ... upload process ...
   
   # Copy config
   cp /var/www/html/erp_old/application/config/config.php \
      /var/www/html/erp/application/config/config.php
   ```

3. **Run AutoMigration**
   
   Access the application - AutoMigration will run automatically and:
   - Add new tables
   - Add new columns
   - Install Phase 12 accounts
   - Add performance indexes
   
   Check logs: `application/logs/`

4. **Verify Upgrade**
   ```sql
   -- Check for Phase 12 accounts
   SELECT account_code, account_name 
   FROM erp_accounts 
   WHERE account_code IN ('1500', '1510', '1520', '1530', '1540', '1550', '1590', '2210', '4900', '6200', '7000');
   
   -- Check indexes
   SHOW INDEX FROM erp_journal_entries;
   ```

5. **Test Functionality**
   - Create test invoice
   - Process test payroll
   - Run reports
   - Verify accounting entries

---

## Troubleshooting

### "Database connection failed"
- Verify database credentials in `config.php`
- Check MySQL service is running
- Verify user has proper permissions

### "Table doesn't exist"
- Run migrations manually
- Check AutoMigration logs
- Verify database user has CREATE privileges

### "Permission denied" errors
- Check file permissions (755 for directories, 644 for files)
- Ensure logs directory is writable (777)
- Check uploads directory permissions

### "Class not found" errors
- Verify all files uploaded correctly
- Check file permissions
- Clear any opcode cache (OPcache, APC)

### AutoMigration not running
- Check `config.php` has `'installed' => true`
- Verify database connection works
- Check error logs: `application/logs/`
- Try manual migration

### Slow performance
- Run performance indexes migration (009)
- Enable MySQL query cache
- Optimize MySQL configuration
- Consider adding more RAM

---

## Database Structure

### Core Tables
- `erp_users` - User accounts
- `erp_permissions` - Permission definitions
- `erp_accounts` - Chart of Accounts
- `erp_journal_entries` - Journal entries
- `erp_journal_entry_lines` - Journal entry lines

### Module Tables
- `erp_invoices` - Customer invoices
- `erp_bills` - Vendor bills
- `erp_payroll_runs` - Payroll processing
- `erp_fixed_assets` - Asset register
- `erp_bookings` - Booking management
- And 50+ more...

### Total Tables: ~70

---

## System Requirements Check

Run this SQL to verify your system:

```sql
-- Check MySQL version
SELECT VERSION();

-- Check character set
SHOW VARIABLES LIKE 'character_set%';

-- Check table count
SELECT COUNT(*) as table_count 
FROM information_schema.tables 
WHERE table_schema = 'erp_system';

-- Check account count
SELECT COUNT(*) as account_count 
FROM erp_accounts;
```

Expected results:
- MySQL version: 5.7+
- Character set: utf8mb4
- Table count: ~70
- Account count: 30+

---

## Security Recommendations

### Production Deployment

1. **Use HTTPS**
   - Install SSL certificate
   - Force HTTPS redirect

2. **Secure Database**
   - Use strong passwords
   - Limit database user privileges
   - Don't use root user

3. **File Permissions**
   ```bash
   find /var/www/html/erp -type d -exec chmod 755 {} \;
   find /var/www/html/erp -type f -exec chmod 644 {} \;
   chmod 777 /var/www/html/erp/application/logs
   chmod 777 /var/www/html/erp/uploads
   ```

4. **Disable Directory Listing**
   
   Add to `.htaccess`:
   ```apache
   Options -Indexes
   ```

5. **Hide PHP Version**
   
   In `php.ini`:
   ```ini
   expose_php = Off
   ```

6. **Enable Error Logging**
   
   In `config.php`:
   ```php
   $config['log_errors'] = true;
   $config['display_errors'] = false; // Production only
   ```

---

## Backup Strategy

### Automated Daily Backups

Create cron job:
```bash
# Edit crontab
crontab -e

# Add daily backup at 2 AM
0 2 * * * /path/to/backup_script.sh
```

**backup_script.sh**:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d)
BACKUP_DIR="/backups/erp"
DB_NAME="erp_system"
DB_USER="erp_user"
DB_PASS="password"

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/erp

# Keep only last 30 days
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

---

## Support

### Documentation
- Quick Start: `docs/QUICK_START.md`
- Email Setup: `docs/EMAIL_CONFIGURATION.md`
- Migrations: `database/migrations/README.md`

### Getting Help
- Check error logs: `application/logs/`
- Review documentation
- Contact support: support@yourcompany.com

---

## Next Steps

After installation:

1. ✅ Read `docs/QUICK_START.md`
2. ✅ Configure email
3. ✅ Add users
4. ✅ Set up customers/suppliers
5. ✅ Enter opening balances
6. ✅ Create first invoice
7. ✅ Process first payroll
8. ✅ Run first reports

**Congratulations! Your ERP system is ready to use!** 🎉
