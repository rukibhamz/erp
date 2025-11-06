# Troubleshooting HTTP 500 Error

## Quick Fixes to Try

### 1. Check Error Logs

The error should be logged in:
- `logs/error.log` (application logs)
- Server error logs (check your hosting control panel)

### 2. Enable Error Display Temporarily

Edit `application/config/config.installed.php` and change:
```php
'environment' => 'development', // Change from 'production' to 'development'
```

This will show errors on screen instead of hiding them.

### 3. Check File Permissions

Ensure these directories are writable:
```bash
chmod 755 logs/
chmod 644 application/config/config.installed.php
```

### 4. Verify Database Connection

Check that your database credentials in `application/config/config.installed.php` are correct:
```php
'db' => [
    'hostname' => 'localhost', // or your DB host
    'username' => 'your_db_user',
    'password' => 'your_db_password',
    'database' => 'your_database_name',
    // ...
]
```

### 5. Check PHP Version

Ensure PHP 7.4+ is installed:
```bash
php -v
```

### 6. Common Causes

1. **Missing logs directory** - Fixed: Code now creates it automatically
2. **CSP Header too strict** - Fixed: Temporarily commented out
3. **Database connection failure** - Check credentials
4. **Missing required PHP extensions** - Check PDO, mbstring, etc.
5. **File permissions** - Ensure files are readable

### 7. Test Database Connection

Create a test file `test_db.php` in root:
```php
<?php
$config = require 'application/config/config.installed.php';
$db = $config['db'];

try {
    $pdo = new PDO(
        "mysql:host={$db['hostname']};dbname={$db['database']};charset=utf8mb4",
        $db['username'],
        $db['password']
    );
    echo "Database connection successful!";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}
```

### 8. Check Server Error Logs

Check your hosting provider's error logs:
- cPanel: Error Log section
- Plesk: Logs section
- Direct server: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`

### 9. Re-enable CSP After Fixing

Once the site works, uncomment the CSP header in `.htaccess`:
```apache
Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com; font-src 'self' fonts.gstatic.com cdn.jsdelivr.net data:; img-src 'self' data: https: ui-avatars.com; connect-src 'self'; frame-ancestors 'self';"
```

## Next Steps

1. Check `logs/error.log` for the actual error message
2. Enable development mode temporarily to see errors
3. Verify database connection
4. Check file permissions
5. Review server error logs

