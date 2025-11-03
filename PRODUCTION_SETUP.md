# Production Setup Guide

## Overview
This guide helps you deploy the ERP system to production and configure it for a fresh installation.

## System Readiness Assessment

### ✅ **Ready for Production**

1. **Installer System**
   - Complete installation wizard
   - Automatic database creation
   - Migration system in place
   - Admin account creation
   - Configuration file generation

2. **Security Features**
   - Security headers configured
   - SQL injection prevention (prepared statements)
   - XSS protection (input sanitization)
   - Rate limiting on login
   - Password hashing (bcrypt)
   - CSRF protection framework

3. **Database**
   - Migration system for all tables
   - Indexes for performance
   - Foreign key constraints

### ⚠️ **Needs Production Configuration**

1. **Error Handling** - Currently set to development mode
2. **Environment Detection** - Needs production/development toggle
3. **HTTPS Enforcement** - Currently commented out
4. **Logging** - Error logging configured but display should be off in production

## Fresh Installation Steps

### 1. Server Requirements

- PHP 8.1 or higher
- MySQL 5.7+ / MariaDB 10.3+
- Apache with mod_rewrite enabled
- PHP Extensions:
  - PDO
  - PDO_MySQL
  - GD Library
  - mbstring
  - openssl

### 2. Upload Files

Upload all files to your web server, maintaining the directory structure:
```
/your-domain/
├── application/
├── assets/
├── install/
├── logs/
├── uploads/
├── backups/
├── .htaccess
├── index.php
└── README.md
```

### 3. Set File Permissions

```bash
# Directories
find . -type d -exec chmod 755 {} \;

# Files
find . -type f -exec chmod 644 {} \;

# Writeable directories
chmod 775 uploads/
chmod 775 backups/
chmod 775 logs/
```

### 4. Run Installation Wizard

1. Navigate to: `http://your-domain/install/`
2. Follow the installation steps:
   - **Step 1**: Welcome screen
   - **Step 2**: Requirements check
   - **Step 3**: Database configuration
   - **Step 4**: Admin account setup
   - **Step 5**: Installation complete

### 5. Post-Installation Configuration

After installation, edit `application/config/config.installed.php`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

return [
    'installed' => true,
    'environment' => 'production', // Set to 'production'
    'base_url' => 'https://your-domain.com/', // Your production URL
    'db' => [
        'hostname' => 'localhost',
        'username' => 'your_db_user',
        'password' => 'your_secure_password',
        'database' => 'your_database_name',
        'dbprefix' => 'erp_',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci'
    ],
    'encryption_key' => 'your-unique-encryption-key-here'
];
```

### 6. Production Configuration

#### Enable HTTPS in .htaccess

Uncomment these lines in `.htaccess`:
```apache
# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### Configure Production Error Handling

Edit `index.php` (around line 50-52):
```php
// Production: Hide errors from users
$environment = $config['environment'] ?? 'development';
if ($environment === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // Hide errors in production
    ini_set('log_errors', 1);
    ini_set('error_log', ROOTPATH . 'logs/error.log');
} else {
    // Development: Show errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOTPATH . 'logs/error.log');
}
```

### 7. Security Hardening

1. **Remove Install Directory** (Recommended after installation):
   ```bash
   # Backup first!
   rm -rf install/
   ```

2. **Protect Config Files**:
   - Ensure `.htaccess` has rules to block access to config files
   - Set restrictive file permissions on `config.installed.php`

3. **Database Security**:
   - Use a dedicated database user with minimal privileges
   - Use strong database passwords
   - Enable SSL for database connections if available

4. **Server Security**:
   - Keep PHP updated
   - Keep MySQL updated
   - Enable firewall
   - Regular security audits

### 8. System Settings

After logging in, configure:

1. **System Settings** (`Settings > System Settings`):
   - Company information
   - Email configuration
   - SMS configuration (if applicable)
   - Timezone settings

2. **User Permissions**:
   - Set up user roles
   - Configure module permissions
   - Set up field-level permissions if needed

3. **Backup Configuration**:
   - Configure automatic backups
   - Set backup storage location
   - Test backup and restore process

## Post-Deployment Checklist

- [ ] HTTPS enabled and working
- [ ] Error display disabled in production
- [ ] Logs directory is writable
- [ ] Uploads directory is writable
- [ ] Backups directory is writable
- [ ] Email sending configured and tested
- [ ] Admin account password changed
- [ ] All default passwords changed
- [ ] Database backups scheduled
- [ ] Security scan completed
- [ ] Performance testing done
- [ ] User training completed

## Troubleshooting

### Installation Issues

1. **Database Connection Error**:
   - Verify database credentials
   - Check database server is running
   - Ensure database user has CREATE DATABASE permission

2. **Permission Errors**:
   - Check file permissions
   - Ensure web server has write access to logs/, uploads/, backups/

3. **URL Rewriting Not Working**:
   - Verify mod_rewrite is enabled
   - Check .htaccess is present
   - Verify RewriteBase is correct for your installation

### Post-Installation Issues

1. **403 Forbidden on Config Files**:
   - This is normal - config files should be protected
   - Access is intentionally blocked

2. **White Screen of Death**:
   - Check error logs in `logs/error.log`
   - Enable error display temporarily for debugging
   - Verify all files uploaded correctly

## Support

For issues or questions:
- Check error logs: `logs/error.log`
- Review system logs in the admin panel
- Contact your system administrator

---

**Last Updated**: System is ready for production deployment with the configuration steps above.

