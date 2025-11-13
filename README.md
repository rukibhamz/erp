# Business Management System (ERP)

A modern, installable PHP business management application built with MVC architecture, featuring a WordPress-style installer and Bootstrap 5.3 UI.

## Features

- **Modular MVC Architecture** - Clean, maintainable code structure
- **Installation Wizard** - Easy setup process similar to WordPress/Perfex CRM
- **Bootstrap 5.3 UI** - Modern, responsive, minimalist design
- **User Management** - Complete user system with roles and permissions
- **Company Management** - Manage multiple companies
- **Activity Logging** - Track all user actions
- **Security** - Built-in security features (XSS protection, CSRF tokens, password hashing)
- **Mobile-First** - Fully responsive design

## Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache with mod_rewrite (or Nginx with equivalent configuration)
- Required PHP extensions:
  - mysqli
  - pdo
  - pdo_mysql
  - curl
  - zip
  - gd
  - mbstring
  - json

## Installation

1. **Extract files** to your web server directory (e.g., `htdocs`, `www`, `public_html`)

2. **Set permissions** (Linux/Mac):
   ```bash
   chmod 755 application/config
   chmod 755 uploads
   chmod 755 logs
   ```

3. **Run the installer**:
   - Open your browser and navigate to: `http://yourdomain.com/install/`
   - Follow the installation wizard steps:
     - **Step 1**: Welcome screen
     - **Step 2**: System requirements check
     - **Step 3**: Database configuration
     - **Step 4**: Administrator account creation
     - **Step 5**: Installation complete

4. **After installation**:
   - Delete the `install` directory for security
   - **Run database migrations** (see Database Migrations section below)
   - Log in with your administrator credentials

## Directory Structure

```
/
├── application/          # Core application
│   ├── config/          # Configuration files
│   ├── controllers/     # MVC Controllers
│   ├── models/          # Database models
│   ├── views/           # UI templates
│   ├── helpers/         # Helper functions
│   └── core/            # Core classes
├── assets/              # CSS, JS, images
│   ├── css/
│   ├── js/
│   └── images/
├── install/             # Installation wizard
├── modules/             # Business modules
├── uploads/             # User uploaded files
├── logs/                # Application logs
├── vendor/              # Third-party libraries
├── index.php            # Entry point
└── .htaccess            # Apache configuration
```

## Default Routes

- `/` or `/dashboard` - Dashboard
- `/login` - Login page
- `/logout` - Logout
- `/users` - User management
- `/companies` - Company management
- `/settings` - Settings

## Database Migrations

**✅ AUTOMATIC MIGRATIONS:** Migrations run automatically on application startup! No manual steps required.

### How It Works

1. **After Installation:** User logs in or visits any page
2. **Automatic Check:** System checks for pending migrations
3. **Auto-Execution:** Migrations run automatically in background
4. **Seamless Experience:** User continues normally - no interruption

### What Gets Migrated Automatically

- ✅ Permission system (tables, roles, permissions)
- ✅ Business module tables (7 tables)
- ✅ Role-based permission assignments

### Manual Option (If Needed)

If you prefer to run migrations manually:

```bash
# SQL
mysql -u username -p database_name < database/migrations/000_complete_system_migration.sql

# OR Migration Runner
php database/migrations/migrate.php up
```

**See `docs/AUTO_MIGRATION_GUIDE.md` for details on automatic migrations.**

## Database Tables

The installer automatically creates the following core tables:

- `users` - User accounts
- `companies` - Company information
- `modules_settings` - Module configuration
- `activity_log` - User activity tracking

**Additional tables** are created by running migrations:
- Permission system tables (`erp_permissions`, `erp_roles`, `erp_role_permissions`)
- Business module tables (`erp_spaces`, `erp_stock_levels`, `erp_items`, `erp_leases`, `erp_work_orders`, `erp_tax_deadlines`, `erp_utility_bills`)

## Security Features

- Password hashing using bcrypt
- XSS protection
- CSRF token support
- SQL injection protection (prepared statements)
- Config file protection
- Secure session handling

## Development

### Adding a New Module

1. Create controller in `application/controllers/`
2. Create model in `application/models/`
3. Create views in `application/views/`
4. Add routes in `application/config/routes.php`

### Adding a New Model

Extend `Base_Model`:

```php
class Your_model extends Base_Model {
    protected $table = 'your_table';
}
```

### Adding a New Controller

Extend `Base_Controller`:

```php
class Your_Controller extends Base_Controller {
    public function index() {
        $data = ['page_title' => 'Your Page'];
        $this->loadView('your_view', $data);
    }
}
```

## Configuration

Configuration is stored in `application/config/config.php` (auto-generated during installation).

To change settings after installation, edit `application/config/config.installed.php`.

## Troubleshooting

### Installation Issues

- **Database connection fails**: Check database credentials and ensure MySQL is running
- **Permission errors**: Set proper file permissions on `application/config`, `uploads`, and `logs` directories
- **500 Error**: Check PHP error logs and ensure all requirements are met

### Common Issues

- **.htaccess not working**: Ensure mod_rewrite is enabled in Apache
- **Session issues**: Check PHP session configuration
- **Assets not loading**: Verify `base_url` in config file

## License

This project is provided as-is for business management purposes.

## Documentation

**📚 Complete Documentation:** See `docs/COMPLETE_DOCUMENTATION.md` for comprehensive system documentation including:
- Installation guide
- Security documentation
- Architecture & development guide
- Module management
- Permission system
- Database & migrations
- Troubleshooting
- Production deployment

## Support

For issues and questions, please check:
1. PHP error logs in `logs/error.log`
2. Apache/Nginx error logs
3. Browser console for JavaScript errors
4. Complete documentation in `docs/COMPLETE_DOCUMENTATION.md`

## Changelog

### Version 1.0.0
- Initial release
- Core MVC architecture
- User and company management
- Activity logging
- Bootstrap 5.3 UI
- Installation wizard
